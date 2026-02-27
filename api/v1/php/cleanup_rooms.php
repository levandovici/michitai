<?php
/**
 * cleanup_rooms.php
 * Cron job - cleans up inactive players and abandoned rooms
 *
 * Recommended execution frequency: every 5–10 minutes
 *
 * Rules (current 2026 version):
 * • Player → offline if no heartbeat for > 5 minutes
 * • Player → removed completely if no heartbeat for > 60 minutes
 * • Room → marked inactive (soft delete) immediately when NO online players remain
 * • Room → hard deleted (with all related data) if inactive for > 60 minutes
 */

require_once __DIR__ . '/config.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    error_log("cleanup_rooms.php: Database connection not available");
    exit(1);
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ────────────────────────────────────────────────
// Configuration
// ────────────────────────────────────────────────
const HEARTBEAT_TIMEOUT_MIN       = 5;   // player considered offline
const PLAYER_MAX_AGE_MINUTES      = 60;  // remove player completely
const ROOM_INACTIVE_TIMEOUT_MIN   = 60;  // hard delete room after this time of being inactive
// ────────────────────────────────────────────────

$now = new DateTimeImmutable();

$heartbeatThreshold = $now->modify('-' . HEARTBEAT_TIMEOUT_MIN . ' minutes');
$playerHardDeleteThreshold = $now->modify('-' . PLAYER_MAX_AGE_MINUTES . ' minutes');
$roomHardDeleteThreshold = $now->modify('-' . ROOM_INACTIVE_TIMEOUT_MIN . ' minutes');

try {
    $pdo->beginTransaction();

    // 1. Mark players as offline (cosmetic / status)
    $stmt = $pdo->prepare("
        UPDATE room_players
        SET is_online = FALSE
        WHERE is_online = TRUE
          AND last_heartbeat < :threshold
    ");
    $stmt->execute([':threshold' => $heartbeatThreshold->format('Y-m-d H:i:s')]);

    $markedOffline = $stmt->rowCount();
    if ($markedOffline > 0) {
        error_log("cleanup: marked $markedOffline players as offline");
    }

    // 2. Remove very old players (1h+ without heartbeat)
    $stmt = $pdo->prepare("
        DELETE FROM room_players
        WHERE last_heartbeat < :hard_threshold
    ");
    $stmt->execute([':hard_threshold' => $playerHardDeleteThreshold->format('Y-m-d H:i:s')]);

    $deletedPlayers = $stmt->rowCount();
    if ($deletedPlayers > 0) {
        error_log("cleanup: deleted $deletedPlayers very old players");
    }

    // 3. Soft-delete rooms that have NO online players right now
    $stmt = $pdo->prepare("
        UPDATE game_rooms gr
        SET is_active = FALSE,
            updated_at = NOW()
        WHERE gr.is_active = TRUE
          AND NOT EXISTS (
              SELECT 1
              FROM room_players rp
              WHERE rp.room_id = gr.room_id
                AND rp.is_online = TRUE
                AND rp.last_heartbeat >= :active_threshold
          )
    ");
    $stmt->execute([':active_threshold' => $heartbeatThreshold->format('Y-m-d H:i:s')]);

    $softDeletedRooms = $stmt->rowCount();
    if ($softDeletedRooms > 0) {
        error_log("cleanup: soft-deleted $softDeletedRooms rooms (no online players)");
    }

    // 4. Hard delete rooms that have been inactive for too long
    //    (including all players and actions - CASCADE should help, but explicit is safer)
    $stmt = $pdo->prepare("
        DELETE gr, rp, aq
        FROM game_rooms gr
        LEFT JOIN room_players rp ON rp.room_id = gr.room_id
        LEFT JOIN action_queue aq  ON aq.room_id  = gr.room_id
        WHERE gr.is_active = FALSE
          AND gr.updated_at < :hard_delete_threshold
    ");
    $stmt->execute([':hard_delete_threshold' => $roomHardDeleteThreshold->format('Y-m-d H:i:s')]);

    $hardDeleted = $stmt->rowCount();
    if ($hardDeleted > 0) {
        error_log("cleanup: HARD deleted $hardDeleted very old inactive rooms (+ related rows)");
    }

    $pdo->commit();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("cleanup_rooms.php CRITICAL ERROR: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    exit(1);
}

error_log("cleanup_rooms.php completed successfully");
exit(0);