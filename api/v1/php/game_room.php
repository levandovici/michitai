<?php
header('Content-Type: application/json');

// Enable error reporting & logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Create logs directory if needed
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0777, true);
}

// Log the request
error_log("=== GAME ROOM REQUEST ===");
error_log("URI: " . $_SERVER['REQUEST_URI']);
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
// error_log("Headers: " . print_r(getallheaders(), true)); // uncomment for debugging

require_once __DIR__ . '/config.php';


// ────────────────────────────────────────────────
//      Helper functions
// ────────────────────────────────────────────────
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function getAuthContext() {
    $headers = getallheaders();
    $apiToken = '';

    // Prefer Bearer token
    if (isset($headers['Authorization']) && preg_match('/Bearer\s+(\S+)/', $headers['Authorization'], $m)) {
        $apiToken = $m[1];
    } elseif (isset($_GET['api_token'])) {
        $apiToken = $_GET['api_token'];
    }

    if (empty($apiToken)) {
        sendResponse(['success' => false, 'error' => 'API token is required'], 401);
    }

    global $pdo;
    $stmt = $pdo->prepare("SELECT id, user_id FROM api_keys WHERE api_key = ?");
    $stmt->execute([$apiToken]);
    $api = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$api) {
        sendResponse(['success' => false, 'error' => 'Invalid API token'], 401);
    }

    $player = null;
    $playerToken = $headers['X-Player-Token'] ?? $_GET['game_player_token'] ?? '';

    if ($playerToken !== '') {
        $stmt = $pdo->prepare("SELECT * FROM game_players WHERE private_key = ?");
        $stmt->execute([$playerToken]);
        $player = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$player) {
            sendResponse(['success' => false, 'error' => 'Invalid player token'], 401);
        }
    }

    return ['api' => $api, 'player' => $player];
}

function requirePlayer($context) {
    if (!$context['player']) {
        sendResponse(['success' => false, 'error' => 'Player token is required'], 401);
    }
    return $context['player'];
}

function getPlayerRoom($playerId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT room_id 
        FROM room_players 
        WHERE player_id = ? AND is_online = TRUE
        LIMIT 1
    ");
    $stmt->execute([$playerId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['room_id'] : null;
}

function addPlayerToRoom($roomId, $playerId, $playerName, $isHost = false) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO room_players 
            (player_id, room_id, player_name, is_host, last_heartbeat, joined_at, is_online) 
        VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, TRUE)
        ON DUPLICATE KEY UPDATE 
            room_id = VALUES(room_id),
            player_name = VALUES(player_name),
            is_host = VALUES(is_host),
            last_heartbeat = CURRENT_TIMESTAMP,
            joined_at = CURRENT_TIMESTAMP,
            is_online = TRUE
    ");
    $stmt->execute([$playerId, $roomId, $playerName, $isHost ? 1 : 0]);
}

function isHost($playerId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT 1 
        FROM room_players 
        WHERE player_id = ? AND is_host = TRUE
        LIMIT 1
    ");
    $stmt->execute([$playerId]);
    return (bool) $stmt->fetchColumn();
}


// ────────────────────────────────────────────────
//      Endpoints
// ────────────────────────────────────────────────

function createRoom() {
    $context = getAuthContext();
    $player = requirePlayer($context);

    $data = json_decode(file_get_contents('php://input'), true) ?: [];

    $roomId = bin2hex(random_bytes(16));
    $roomName = trim($data['room_name'] ?? 'Game Room ' . substr($roomId, 0, 6));
    $roomName = mb_substr($roomName, 0, 120);

    $password = !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
    $maxPlayers = max(2, min(16, (int)($data['max_players'] ?? 6)));

    global $pdo;
    try {
        $pdo->beginTransaction();

        $pdo->prepare("
            INSERT INTO game_rooms (room_id, room_name, password, max_players)
            VALUES (?, ?, ?, ?)
        ")->execute([$roomId, $roomName, $password, $maxPlayers]);

        addPlayerToRoom($roomId, $player['id'], $player['player_name'], true);

        $pdo->prepare("
            UPDATE game_rooms SET host_player_id = ? WHERE room_id = ?
        ")->execute([$player['id'], $roomId]);

        $pdo->commit();

        sendResponse([
            'success' => true,
            'room_id' => $roomId,
            'room_name' => $roomName,
            'is_host' => true
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Create room failed: " . $e->getMessage());
        sendResponse(['success' => false, 'error' => 'Failed to create room'], 500);
    }
}

function listRooms() {
    getAuthContext(); // just validate API key

    global $pdo;
    try {
        $stmt = $pdo->query("
            SELECT 
                r.room_id, 
                r.room_name, 
                r.max_players, 
                COUNT(rp.player_id) as current_players,
                r.password IS NOT NULL as has_password
            FROM game_rooms r
            LEFT JOIN room_players rp ON r.room_id = rp.room_id
            WHERE r.is_active = TRUE
            GROUP BY r.room_id
            HAVING current_players < r.max_players
            ORDER BY current_players DESC, r.room_name ASC
        ");

        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse(['success' => true, 'rooms' => $rooms]);
    } catch (Exception $e) {
        error_log("List rooms failed: " . $e->getMessage());
        sendResponse(['success' => false, 'error' => 'Failed to list rooms'], 500);
    }
}

function joinRoom($roomId) {
    $context = getAuthContext();
    $player = requirePlayer($context);

    $data = json_decode(file_get_contents('php://input'), true) ?: [];

    global $pdo;
    try {
        $pdo->beginTransaction();

        $currentRoom = getPlayerRoom($player['id']);
        if ($currentRoom && $currentRoom !== $roomId) {
            throw new Exception('You are already in another room');
        }

        $stmt = $pdo->prepare("
            SELECT password, max_players, is_active,
                   (SELECT COUNT(*) FROM room_players WHERE room_id = ?) as current_players
            FROM game_rooms 
            WHERE room_id = ?
        ");
        $stmt->execute([$roomId, $roomId]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            throw new Exception('Room not found');
        }

        // If inactive, only allow if empty (no stale players)
        if (!$room['is_active'] && $room['current_players'] > 0) {
            throw new Exception('Room inactive');
        }

        if ($room['current_players'] >= $room['max_players']) {
            throw new Exception('Room is full');
        }

        if ($room['password'] !== null) {
            if (!isset($data['password']) || !password_verify($data['password'], $room['password'])) {
                throw new Exception('Incorrect password');
            }
        }

        addPlayerToRoom($roomId, $player['id'], $player['player_name']);

        // Check and reassign host if needed (will also reactivate if inactive)
        checkAndReassignHost($roomId);

        $pdo->commit();

        sendResponse([
            'success' => true,
            'room_id' => $roomId,
            'message' => 'Successfully joined the room'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Join room failed: " . $e->getMessage());
        sendResponse(['success' => false, 'error' => $e->getMessage()], 400);
    }
}

function listRoomPlayers() {
    $context = getAuthContext();
    $player = requirePlayer($context);

    $roomId = getPlayerRoom($player['id']);
    if (!$roomId) {
        sendResponse(['success' => false, 'error' => 'You are not in any room'], 400);
    }

    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT 
                rp.player_id,
                rp.player_name,
                rp.is_host,
                rp.is_online,
                TIMESTAMPDIFF(SECOND, rp.last_heartbeat, NOW())
            FROM room_players rp
            WHERE rp.room_id = ?
            ORDER BY 
                rp.is_host DESC,
                rp.joined_at ASC,
                rp.player_name ASC
        ");
        $stmt->execute([$roomId]);
        $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendResponse([
            'success' => true,
            'players' => $players,
            'last_updated' => date('c')
        ]);
    } catch (Exception $e) {
        error_log("List players failed: " . $e->getMessage());
        sendResponse(['success' => false, 'error' => 'Failed to list players'], 500);
    }
}

function leaveRoom() {
    $context = getAuthContext();
    $player = requirePlayer($context);

    global $pdo;
    try {
        $pdo->beginTransaction();

        $roomId = getPlayerRoom($player['id']);
        if (!$roomId) {
            throw new Exception('You are not in any room');
        }

        $stmt = $pdo->prepare("
            SELECT is_host 
            FROM room_players 
            WHERE player_id = ? AND room_id = ?
        ");
        $stmt->execute([$player['id'], $roomId]);
        $playerData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$playerData) {
            throw new Exception('You are not in this room');
        }

        $isHost = (bool)$playerData['is_host'];

        // Remove leaving player first
        $pdo->prepare("
            DELETE FROM room_players 
            WHERE player_id = ? AND room_id = ?
        ")->execute([$player['id'], $roomId]);

        if ($isHost) {
            // Find next oldest online player to become host
            $stmt = $pdo->prepare("
                SELECT player_id 
                FROM room_players 
                WHERE room_id = ? 
                  AND is_online = TRUE
                ORDER BY joined_at ASC
                LIMIT 1
            ");
            $stmt->execute([$roomId]);
            $newHost = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($newHost) {
                // Assign new host (set all others to false for safety)
                $pdo->prepare("
                    UPDATE room_players 
                    SET is_host = FALSE
                    WHERE room_id = ?
                ")->execute([$roomId]);

                $pdo->prepare("
                    UPDATE room_players 
                    SET is_host = TRUE
                    WHERE player_id = ? AND room_id = ?
                ")->execute([$newHost['player_id'], $roomId]);

                $pdo->prepare("
                    UPDATE game_rooms 
                    SET host_player_id = ?
                    WHERE room_id = ?
                ")->execute([$newHost['player_id'], $roomId]);
            } else {
                // No one left → deactivate room
                $pdo->prepare("DELETE FROM action_queue WHERE room_id = ?")->execute([$roomId]);
                $pdo->prepare("UPDATE game_rooms SET is_active = FALSE WHERE room_id = ?")->execute([$roomId]);
            }
        }

        $pdo->commit();

        sendResponse([
            'success' => true,
            'message' => 'Successfully left the room'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Leave room failed: " . $e->getMessage());
        sendResponse(['success' => false, 'error' => $e->getMessage()], 400);
    }
}

function checkAndReassignHost($roomId) {
    global $pdo;
    
    // Check if current host is still online
    $stmt = $pdo->prepare("
        SELECT player_id, is_online, last_heartbeat,
               TIMESTAMPDIFF(SECOND, last_heartbeat, NOW()) as seconds_since_heartbeat
        FROM room_players
        WHERE room_id = ? AND is_host = TRUE
        LIMIT 1
    ");
    $stmt->execute([$roomId]);
    $currentHost = $stmt->fetch(PDO::FETCH_ASSOC);

    // Host is offline if missing, marked offline, or timed out
    $hostOffline = !$currentHost || 
                   !$currentHost['is_online'];

    if ($hostOffline) {
        try {
            if ($currentHost) {
                // Remove host status from current host
                $pdo->prepare("
                    UPDATE room_players 
                    SET is_host = FALSE 
                    WHERE player_id = ? AND room_id = ?
                ")->execute([$currentHost['player_id'], $roomId]);
            }

            // Find next available player to be host (oldest first)
            $stmt = $pdo->prepare("
                SELECT player_id 
                FROM room_players 
                WHERE room_id = ? 
                  AND is_online = TRUE
                ORDER BY joined_at ASC
                LIMIT 1
            ");
            $stmt->execute([$roomId]);
            $newHost = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($newHost) {
                // Assign new host
                $pdo->prepare("
                    UPDATE room_players 
                    SET is_host = TRUE
                    WHERE player_id = ? AND room_id = ?
                ")->execute([$newHost['player_id'], $roomId]);

                $pdo->prepare("
                    UPDATE game_rooms 
                    SET host_player_id = ?,
                        is_active = TRUE  -- Reactivate if was inactive
                    WHERE room_id = ?
                ")->execute([$newHost['player_id'], $roomId]);

                return true;
            } else {
                // No players left in room → deactivate
                $pdo->prepare("DELETE FROM action_queue WHERE room_id = ?")->execute([$roomId]);
                $pdo->prepare("UPDATE game_rooms SET is_active = FALSE WHERE room_id = ?")->execute([$roomId]);
                return false;
            }
        } catch (Exception $e) {
            error_log("Failed to reassign host: " . $e->getMessage());
            throw $e;  // Let outer transaction handle rollback
        }
    }
    return false;
}

function updateHeartbeat() {
    $context = getAuthContext();
    $player = requirePlayer($context);

    global $pdo;
    $pdo->beginTransaction();
    
    try {
        // Update player's own heartbeat
        $stmt = $pdo->prepare("
            UPDATE room_players 
            SET last_heartbeat = CURRENT_TIMESTAMP,
                is_online = TRUE
            WHERE player_id = ?
        ");
        $stmt->execute([$player['id']]);
        
        // Get current room ID
        $roomId = getPlayerRoom($player['id']);
        if ($roomId) {
            // Check and reassign host if needed
            checkAndReassignHost($roomId);
        }
        
        $pdo->commit();
        sendResponse(['success' => true, 'status' => 'ok']);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Heartbeat update failed: " . $e->getMessage());
        sendResponse(['success' => false, 'error' => 'Failed to update heartbeat'], 500);
    }
}

function submitAction() {
    $context = getAuthContext();
    $player = requirePlayer($context);

    $data = json_decode(file_get_contents('php://input'), true) ?: [];

    if (empty($data['action_type']) || !isset($data['request_data'])) {
        sendResponse(['success' => false, 'error' => 'Missing required fields: action_type and request_data'], 400);
    }

    $roomId = getPlayerRoom($player['id']);
    if (!$roomId) {
        sendResponse(['success' => false, 'error' => 'Player is not in any room'], 400);
    }

    $actionId = bin2hex(random_bytes(16));

    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO action_queue 
        (action_id, room_id, player_id, action_type, request_data, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([
        $actionId,
        $roomId,
        $player['id'],
        $data['action_type'],
        json_encode($data['request_data'], JSON_UNESCAPED_UNICODE)
    ]);

    sendResponse([
        'success' => true,
        'action_id' => $actionId,
        'status' => 'pending'
    ]);
}

function pollActions() {
    $context = getAuthContext();
    $player = requirePlayer($context);

    $roomId = getPlayerRoom($player['id']);
    if (!$roomId) {
        sendResponse(['success' => false, 'error' => 'Player is not in any room'], 400);
    }

    global $pdo;
    $stmt = $pdo->prepare("
        SELECT action_id, action_type, response_data, status
        FROM action_queue
        WHERE player_id = ? 
          AND status IN ('completed', 'failed')
          AND processed_at > NOW() - INTERVAL 1 HOUR
        ORDER BY processed_at DESC
        LIMIT 50
    ");
    $stmt->execute([$player['id']]);
    $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark as read
    if (!empty($actions)) {
        $actionIds = array_column($actions, 'action_id');
        $placeholders = implode(',', array_fill(0, count($actionIds), '?'));
        $pdo->prepare("
            UPDATE action_queue 
            SET status = 'read' 
            WHERE action_id IN ($placeholders)
        ")->execute($actionIds);
    }

    sendResponse(['success' => true, 'actions' => $actions]);
}

function getPendingActions() {
    $context = getAuthContext();
    $player = requirePlayer($context);

    if (!isHost($player['id'])) {
        sendResponse(['success' => false, 'error' => 'Only host can view pending actions'], 403);
    }

    $roomId = getPlayerRoom($player['id']);
    if (!$roomId) {
        sendResponse(['success' => false, 'error' => 'You are not in any room'], 400);
    }

    global $pdo;
    $stmt = $pdo->prepare("
        SELECT a.action_id, a.player_id, a.action_type, a.request_data, a.created_at,
               rp.player_name
        FROM action_queue a
        JOIN room_players rp ON a.player_id = rp.player_id
        WHERE a.room_id = ? AND a.status = 'pending'
        ORDER BY a.created_at ASC
    ");
    $stmt->execute([$roomId]);
    $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(['success' => true, 'actions' => $actions]);
}

function completeAction($actionId) {
    $context = getAuthContext();
    $player = requirePlayer($context);

    if (!isHost($player['id'])) {
        sendResponse(['success' => false, 'error' => 'Only host can complete actions'], 403);
    }

    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $status = in_array($data['status'] ?? 'completed', ['completed', 'failed']) 
        ? $data['status'] 
        : 'completed';

    $responseData = isset($data['response_data']) ? json_encode($data['response_data'], JSON_UNESCAPED_UNICODE) : null;

    global $pdo;
    $stmt = $pdo->prepare("
        UPDATE action_queue 
        SET status = ?,
            response_data = ?,
            processed_at = CURRENT_TIMESTAMP
        WHERE action_id = ?
          AND status = 'pending'
    ");
    $stmt->execute([$status, $responseData, $actionId]);

    if ($stmt->rowCount() === 0) {
        sendResponse(['success' => false, 'error' => 'Action not found or already processed'], 404);
    }

    sendResponse(['success' => true, 'message' => 'Action completed']);
}

function sendUpdates() {
    $context = getAuthContext();
    $player = requirePlayer($context);

    // Only host can send updates
    if (!isHost($player['id'])) {
        sendResponse(['success' => false, 'error' => 'Only host can send updates'], 403);
    }

    $data = json_decode(file_get_contents('php://input'), true) ?: [];

    // Validate required fields
    if (empty($data['roomId']) || empty($data['type']) || !isset($data['dataJson'])) {
        sendResponse(['success' => false, 'error' => 'Missing required fields: roomId, type, dataJson'], 400);
    }

    $roomId = $data['roomId'];
    $updateType = trim($data['type']);
    $dataJson = is_string($data['dataJson']) ? $data['dataJson'] : json_encode($data['dataJson'], JSON_UNESCAPED_UNICODE);

    // Validate JSON
    json_decode($dataJson);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendResponse(['success' => false, 'error' => 'Invalid JSON in dataJson field'], 400);
    }

    // Verify host is in this room
    $hostRoomId = getPlayerRoom($player['id']);
    if ($hostRoomId !== $roomId) {
        sendResponse(['success' => false, 'error' => 'You are not the host of this room'], 403);
    }

    // Get target players
    $targetPlayerIds = $data['targetPlayerIds'] ?? 'all';
    $targets = [];

    global $pdo;
    if ($targetPlayerIds === 'all') {
        // Get all players in room except the host
        $stmt = $pdo->prepare("
            SELECT player_id 
            FROM room_players 
            WHERE room_id = ? AND player_id != ? AND is_online = TRUE
        ");
        $stmt->execute([$roomId, $player['id']]);
        $targets = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } elseif (is_array($targetPlayerIds)) {
        // Validate specific players are in the room and online
        $placeholders = implode(',', array_fill(0, count($targetPlayerIds), '?'));
        $stmt = $pdo->prepare("
            SELECT player_id 
            FROM room_players 
            WHERE room_id = ? AND player_id IN ($placeholders) AND is_online = TRUE
        ");
        $stmt->execute([$roomId, ...$targetPlayerIds]);
        $targets = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        sendResponse(['success' => false, 'error' => 'targetPlayerIds must be "all" or an array'], 400);
    }

    if (empty($targets)) {
        sendResponse(['success' => false, 'error' => 'No valid target players found'], 400);
    }

    // Create updates for each target player
    $updateIds = [];
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO player_updates 
            (update_id, room_id, from_player_id, target_player_id, type, data_json)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($targets as $targetPlayerId) {
            $updateId = bin2hex(random_bytes(16));
            $stmt->execute([$updateId, $roomId, $player['id'], $targetPlayerId, $updateType, $dataJson]);
            $updateIds[] = $updateId;
        }

        $pdo->commit();

        sendResponse([
            'success' => true,
            'updates_sent' => count($updateIds),
            'update_ids' => $updateIds,
            'target_players' => $targets
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Send updates failed: " . $e->getMessage());
        sendResponse(['success' => false, 'error' => 'Failed to send updates'], 500);
    }
}

function pollUpdates() {
    $context = getAuthContext();
    $player = requirePlayer($context);

    $roomId = getPlayerRoom($player['id']);
    if (!$roomId) {
        sendResponse(['success' => false, 'error' => 'Player is not in any room'], 400);
    }

    $lastUpdateId = $_GET['lastUpdateId'] ?? null;

    global $pdo;
    
    // Build query based on lastUpdateId
    $whereClause = "WHERE target_player_id = ? AND room_id = ?";
    $params = [$player['id'], $roomId];
    
    if ($lastUpdateId) {
        $whereClause .= " AND update_id > ?";
        $params[] = $lastUpdateId;
    }

    $stmt = $pdo->prepare("
        SELECT update_id, from_player_id, type, data_json, created_at
        FROM player_updates 
        {$whereClause}
        ORDER BY created_at ASC
        LIMIT 50
    ");
    $stmt->execute($params);
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark updates as delivered
    if (!empty($updates)) {
        $updateIds = array_column($updates, 'update_id');
        $placeholders = implode(',', array_fill(0, count($updateIds), '?'));
        
        $updateStmt = $pdo->prepare("
            UPDATE player_updates 
            SET status = 'delivered', delivered_at = CURRENT_TIMESTAMP
            WHERE update_id IN ($placeholders) AND status = 'pending'
        ");
        $updateStmt->execute($updateIds);
    }

    sendResponse([
        'success' => true,
        'updates' => $updates,
        'last_update_id' => !empty($updates) ? end($updates)['update_id'] : $lastUpdateId
    ]);
}


// ────────────────────────────────────────────────
//      Routing
// ────────────────────────────────────────────────
try {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST' && preg_match('#/rooms/?$#', $path)) {
        createRoom();
    } elseif ($method === 'GET' && preg_match('#/rooms/?$#', $path)) {
        listRooms();
    } elseif ($method === 'POST' && preg_match('#/rooms/([a-f0-9-]{32,36})/join/?$#', $path, $m)) {
        joinRoom($m[1]);
    } elseif ($method === 'GET' && preg_match('#/players/?$#', $path)) {
        listRoomPlayers();
    } elseif ($method === 'POST' && preg_match('#/leave/?$#', $path)) {
        leaveRoom();
    } elseif ($method === 'POST' && preg_match('#/heartbeat/?$#', $path)) {
        updateHeartbeat();
    } elseif ($method === 'POST' && preg_match('#/actions/?$#', $path)) {
        submitAction();
    } elseif ($method === 'GET' && preg_match('#/actions/poll/?$#', $path)) {
        pollActions();
    } elseif ($method === 'GET' && preg_match('#/actions/pending/?$#', $path)) {
        getPendingActions();
    } elseif ($method === 'POST' && preg_match('#/actions/([a-f0-9-]{32,36})/complete/?$#', $path, $m)) {
        completeAction($m[1]);
    } elseif ($method === 'POST' && preg_match('#/updates/?$#', $path)) {
        sendUpdates();
    } elseif ($method === 'GET' && preg_match('#/updates/poll/?$#', $path)) {
        pollUpdates();
    } else {
        sendResponse(['success' => false, 'error' => 'Not found'], 404);
    }
} catch (Exception $e) {
    error_log("Critical error in game_room.php: " . $e->getMessage());
    sendResponse(['success' => false, 'error' => 'Internal server error'], 500);
}