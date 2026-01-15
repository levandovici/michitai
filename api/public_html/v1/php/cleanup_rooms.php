<?php
/**
 * cleanup_rooms.php
 * Cron job - cleans up inactive players and abandoned rooms
 * 
 * Recommended execution frequency: every 5–15 minutes
 */

require_once __DIR__ . '/config.php';  // ← should contain $pdo

// Make sure we have PDO connection from config
if (!isset($pdo) || !($pdo instanceof PDO)) {
    error_log("cleanup_rooms.php: Database connection not available");
    exit(1);
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ────────────────────────────────────────────────
// Configuration
// ────────────────────────────────────────────────

// How long without heartbeat → consider player offline
const OFFLINE_THRESHOLD_MINUTES = 3;

// How long without ANY online player in room → consider room abandoned
const ROOM_INACTIVE_THRESHOLD_MINUTES = 15;

// For very old rooms with no activity at all (extra safety net)
const ROOM_MAX_AGE_HOURS = 24;

$now = new DateTime();

// ────────────────────────────────────────────────
// 1. Mark inactive/offline players
// ────────────────────────────────────────────────
$offlineThreshold = (clone $now)->modify('-' . OFFLINE_THRESHOLD_MINUTES . ' minutes');

try {
    $stmt = $pdo->prepare("
        UPDATE room_players 
        SET is_online = FALSE 
        WHERE last_heartbeat < :threshold
          AND is_online = TRUE
    ");
    $stmt->execute([':threshold' => $offlineThreshold->format('Y-m-d H:i:s')]);

    $updated = $stmt->rowCount();
    if ($updated > 0) {
        error_log("cleanup_rooms: Marked $updated players as offline");
    }
} catch (Exception $e) {
    error_log("cleanup_rooms: Error marking offline players - " . $e->getMessage());
}


// ────────────────────────────────────────────────
// 2. Find and deactivate abandoned rooms
// ────────────────────────────────────────────────
$inactiveThreshold = (clone $now)->modify('-' . ROOM_INACTIVE_THRESHOLD_MINUTES . ' minutes');
$maxAgeThreshold   = (clone $now)->modify('-' . ROOM_MAX_AGE_HOURS . ' hours');

try {
    // Find rooms that should be cleaned up
    $stmt = $pdo->prepare("
        SELECT 
            gr.room_id,
            gr.created_at,
            MAX(rp.last_heartbeat) as last_activity,
            COUNT(rp.player_id) as player_count
        FROM game_rooms gr
        LEFT JOIN room_players rp ON gr.room_id = rp.room_id
        WHERE gr.is_active = TRUE
        GROUP BY gr.room_id
        HAVING 
            (MAX(rp.last_heartbeat) < :inactive_threshold OR MAX(rp.last_heartbeat) IS NULL)
            OR gr.created_at < :max_age_threshold
    ");

    $stmt->execute([
        ':inactive_threshold' => $inactiveThreshold->format('Y-m-d H:i:s'),
        ':max_age_threshold'  => $maxAgeThreshold->format('Y-m-d H:i:s')
    ]);

    $roomsToClean = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($roomsToClean)) {
        // Nothing to do - silent success
        exit(0);
    }

    error_log("cleanup_rooms: Found " . count($roomsToClean) . " potentially inactive rooms");

    $pdo->beginTransaction();

    foreach ($roomsToClean as $room) {
        $roomId = $room['room_id'];
        $reason = $room['last_activity'] 
            ? "no activity since {$room['last_activity']}" 
            : "no players ever";

        // 1. Clean action queue (optional - can rely on cascade)
        $pdo->prepare("DELETE FROM action_queue WHERE room_id = ?")
            ->execute([$roomId]);

        // 2. Mark room as inactive (soft delete)
        $pdo->prepare("
            UPDATE game_rooms 
            SET is_active = FALSE,
                updated_at = NOW()
            WHERE room_id = ?
        ")->execute([$roomId]);

        error_log("cleanup_rooms: Deactivated room $roomId - $reason");
    }

    $pdo->commit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("cleanup_rooms: CRITICAL ERROR cleaning rooms - " . $e->getMessage());
    exit(1);
}

error_log("cleanup_rooms: Completed successfully");
exit(0);