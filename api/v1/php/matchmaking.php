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
error_log("=== MATCHMAKING REQUEST ===");
error_log("URI: " . $_SERVER['REQUEST_URI']);
error_log("Method: " . $_SERVER['REQUEST_METHOD']);

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

function getPlayerMatchmaking($playerId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT mp.matchmaking_id, m.host_player_id
        FROM matchmaking_players mp
        JOIN matchmaking m ON mp.matchmaking_id = m.matchmaking_id
        WHERE mp.player_id = ? AND mp.status = 'active' AND m.is_started = FALSE
        LIMIT 1
    ");
    $stmt->execute([$playerId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['matchmaking_id'] : null;
}

function isMatchmakingHost($playerId, $matchmakingId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT 1 
        FROM matchmaking 
        WHERE matchmaking_id = ? AND host_player_id = ?
        LIMIT 1
    ");
    $stmt->execute([$matchmakingId, $playerId]);
    return (bool) $stmt->fetchColumn();
}

// ────────────────────────────────────────────────
//      Endpoints
// ────────────────────────────────────────────────

function listMatchmaking() {
    getAuthContext(); // just validate API key

    global $pdo;
    try {
        $stmt = $pdo->query("
            SELECT 
                m.matchmaking_id,
                m.host_player_id,
                m.max_players,
                m.strict_full,
                m.extra_json_string,
                m.created_at,
                m.last_heartbeat,
                COUNT(mp.player_id) as current_players,
                gp.player_name as host_name
            FROM matchmaking m
            LEFT JOIN matchmaking_players mp ON m.matchmaking_id = mp.matchmaking_id AND mp.status = 'active'
            LEFT JOIN game_players gp ON m.host_player_id = gp.id
            WHERE m.is_started = FALSE
            GROUP BY m.matchmaking_id
            HAVING current_players < m.max_players
            ORDER BY m.created_at ASC
        ");

        $lobbies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse(['success' => true, 'lobbies' => $lobbies]);
    } catch (Exception $e) {
        error_log("List matchmaking failed: " . $e->getMessage());
        sendResponse(['success' => false, 'error' => 'Failed to list matchmaking lobbies'], 500);
    }
}

function createMatchmaking() {
    $context = getAuthContext();
    $player = requirePlayer($context);

    // Check if player is already in a matchmaking lobby
    $existingLobby = getPlayerMatchmaking($player['id']);
    if ($existingLobby) {
        sendResponse(['success' => false, 'error' => 'You are already in a matchmaking lobby'], 400);
    }

    $data = json_decode(file_get_contents('php://input'), true) ?: [];

    // Validate required fields
    if (empty($data['maxPlayers'])) {
        sendResponse(['success' => false, 'error' => 'Missing required field: maxPlayers'], 400);
    }

    $maxPlayers = max(2, min(16, (int)$data['maxPlayers']));
    $strictFull = !empty($data['strictFull']);
    $extraJsonString = isset($data['extraJsonString']) ? json_encode($data['extraJsonString'], JSON_UNESCAPED_UNICODE) : null;

    $matchmakingId = bin2hex(random_bytes(16));

    global $pdo;
    $pdo->beginTransaction();
    
    try {
        // Create matchmaking lobby
        $stmt = $pdo->prepare("
            INSERT INTO matchmaking 
            (matchmaking_id, host_player_id, max_players, strict_full, extra_json_string)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$matchmakingId, $player['id'], $maxPlayers, $strictFull, $extraJsonString]);

        // Add host as first player
        $stmt = $pdo->prepare("
            INSERT INTO matchmaking_players 
            (matchmaking_id, player_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$matchmakingId, $player['id']]);

        $pdo->commit();

        sendResponse([
            'success' => true,
            'matchmaking_id' => $matchmakingId,
            'max_players' => $maxPlayers,
            'strict_full' => $strictFull,
            'is_host' => true
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Create matchmaking failed: " . $e->getMessage());
        sendResponse(['success' => false, 'error' => 'Failed to create matchmaking lobby'], 500);
    }
}

function requestJoin() {
    $context = getAuthContext();
    $player = requirePlayer($context);

    $data = json_decode(file_get_contents('php://input'), true) ?: [];

    if (empty($data['matchmakingId'])) {
        sendResponse(['success' => false, 'error' => 'Missing required field: matchmakingId'], 400);
    }

    $matchmakingId = $data['matchmakingId'];

    // Check if player is already in a matchmaking lobby
    $existingLobby = getPlayerMatchmaking($player['id']);
    if ($existingLobby) {
        sendResponse(['success' => false, 'error' => 'You are already in a matchmaking lobby'], 400);
    }

    global $pdo;
    $pdo->beginTransaction();

    try {
        // Check if matchmaking exists and is not started
        $stmt = $pdo->prepare("
            SELECT m.*, COUNT(mp.player_id) as current_players
            FROM matchmaking m
            LEFT JOIN matchmaking_players mp ON m.matchmaking_id = mp.matchmaking_id AND mp.status = 'active'
            WHERE m.matchmaking_id = ? AND m.is_started = FALSE
            GROUP BY m.matchmaking_id
        ");
        $stmt->execute([$matchmakingId]);
        $matchmaking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$matchmaking) {
            throw new Exception('Matchmaking lobby not found or already started');
        }

        if ($matchmaking['current_players'] >= $matchmaking['max_players']) {
            throw new Exception('Matchmaking lobby is full');
        }

        // Create join request
        $requestId = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("
            INSERT INTO matchmaking_requests 
            (request_id, matchmaking_id, player_id)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$requestId, $matchmakingId, $player['id']]);

        $pdo->commit();

        sendResponse([
            'success' => true,
            'request_id' => $requestId,
            'message' => 'Join request sent to host'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Request join failed: " . $e->getMessage());
        sendResponse(['success' => false, 'error' => $e->getMessage()], 400);
    }
}

function joinMatchmaking() {
    $context = getAuthContext();
    $player = requirePlayer($context);

    $data = json_decode(file_get_contents('php://input'), true) ?: [];

    if (empty($data['matchmakingId'])) {
        sendResponse(['success' => false, 'error' => 'Missing required field: matchmakingId'], 400);
    }

    $matchmakingId = $data['matchmakingId'];

    // Check if player is already in a matchmaking lobby
    $existingLobby = getPlayerMatchmaking($player['id']);
    if ($existingLobby) {
        sendResponse(['success' => false, 'error' => 'You are already in a matchmaking lobby'], 400);
    }

    global $pdo;
    $pdo->beginTransaction();

    try {
        // Check if matchmaking exists and is not started
        $stmt = $pdo->prepare("
            SELECT m.*, COUNT(mp.player_id) as current_players
            FROM matchmaking m
            LEFT JOIN matchmaking_players mp ON m.matchmaking_id = mp.matchmaking_id AND mp.status = 'active'
            WHERE m.matchmaking_id = ? AND m.is_started = FALSE
            GROUP BY m.matchmaking_id
        ");
        $stmt->execute([$matchmakingId]);
        $matchmaking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$matchmaking) {
            throw new Exception('Matchmaking lobby not found or already started');
        }

        if ($matchmaking['current_players'] >= $matchmaking['max_players']) {
            throw new Exception('Matchmaking lobby is full');
        }

        // Add player to matchmaking
        $stmt = $pdo->prepare("
            INSERT INTO matchmaking_players 
            (matchmaking_id, player_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$matchmakingId, $player['id']]);

        $pdo->commit();

        sendResponse([
            'success' => true,
            'matchmaking_id' => $matchmakingId,
            'message' => 'Successfully joined matchmaking lobby'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Join matchmaking failed: " . $e->getMessage());
        sendResponse(['success' => false, 'error' => $e->getMessage()], 400);
    }
}

function leaveMatchmaking() {
    $context = getAuthContext();
    $player = requirePlayer($context);

    global $pdo;
    $pdo->beginTransaction();

    try {
        // Get player's current matchmaking
        $stmt = $pdo->prepare("
            SELECT mp.matchmaking_id, m.host_player_id
            FROM matchmaking_players mp
            JOIN matchmaking m ON mp.matchmaking_id = m.matchmaking_id
            WHERE mp.player_id = ? AND mp.status = 'active' AND m.is_started = FALSE
            LIMIT 1
        ");
        $stmt->execute([$player['id']]);
        $playerLobby = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$playerLobby) {
            throw new Exception('You are not in any matchmaking lobby');
        }

        $matchmakingId = $playerLobby['matchmaking_id'];
        $isHost = ($playerLobby['host_player_id'] === $player['id']);

        // Remove player from matchmaking
        $stmt = $pdo->prepare("
            DELETE FROM matchmaking_players 
            WHERE matchmaking_id = ? AND player_id = ?
        ");
        $stmt->execute([$matchmakingId, $player['id']]);

        if ($isHost) {
            // Find next player to become host
            $stmt = $pdo->prepare("
                SELECT player_id 
                FROM matchmaking_players 
                WHERE matchmaking_id = ? AND status = 'active'
                ORDER BY joined_at ASC
                LIMIT 1
            ");
            $stmt->execute([$matchmakingId]);
            $newHost = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($newHost) {
                // Transfer host to next player
                $stmt = $pdo->prepare("
                    UPDATE matchmaking 
                    SET host_player_id = ?
                    WHERE matchmaking_id = ?
                ");
                $stmt->execute([$newHost['player_id'], $matchmakingId]);
            } else {
                // No players left - delete matchmaking
                $stmt = $pdo->prepare("
                    DELETE FROM matchmaking 
                    WHERE matchmaking_id = ?
                ");
                $stmt->execute([$matchmakingId]);
            }
        }

        $pdo->commit();

        sendResponse([
            'success' => true,
            'message' => 'Successfully left matchmaking lobby'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Leave matchmaking failed: " . $e->getMessage());
        sendResponse(['success' => false, 'error' => $e->getMessage()], 400);
    }
}

function getMatchmakingPlayers() {
    $context = getAuthContext();
    $player = requirePlayer($context);

    $matchmakingId = getPlayerMatchmaking($player['id']);
    if (!$matchmakingId) {
        sendResponse(['success' => false, 'error' => 'You are not in any matchmaking lobby'], 400);
    }

    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT 
                mp.player_id,
                mp.joined_at,
                mp.last_heartbeat,
                mp.status,
                gp.player_name,
                TIMESTAMPDIFF(SECOND, mp.last_heartbeat, NOW()) as seconds_since_heartbeat,
                m.host_player_id = mp.player_id as is_host
            FROM matchmaking_players mp
            JOIN game_players gp ON mp.player_id = gp.id
            JOIN matchmaking m ON mp.matchmaking_id = m.matchmaking_id
            WHERE mp.matchmaking_id = ?
            ORDER BY 
                is_host DESC,
                mp.joined_at ASC
        ");
        $stmt->execute([$matchmakingId]);
        $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendResponse([
            'success' => true,
            'players' => $players,
            'last_updated' => date('c')
        ]);
    } catch (Exception $e) {
        error_log("Get matchmaking players failed: " . $e->getMessage());
        sendResponse(['success' => false, 'error' => 'Failed to get players'], 500);
    }
}

function updateMatchmakingHeartbeat() {
    $context = getAuthContext();
    $player = requirePlayer($context);

    $matchmakingId = getPlayerMatchmaking($player['id']);
    if (!$matchmakingId) {
        sendResponse(['success' => false, 'error' => 'You are not in any matchmaking lobby'], 400);
    }

    global $pdo;
    $pdo->beginTransaction();
    
    try {
        // Update player's heartbeat
        $stmt = $pdo->prepare("
            UPDATE matchmaking_players 
            SET last_heartbeat = CURRENT_TIMESTAMP,
                status = 'active'
            WHERE matchmaking_id = ? AND player_id = ?
        ");
        $stmt->execute([$matchmakingId, $player['id']]);
        
        // Update matchmaking heartbeat
        $stmt = $pdo->prepare("
            UPDATE matchmaking 
            SET last_heartbeat = CURRENT_TIMESTAMP
            WHERE matchmaking_id = ?
        ");
        $stmt->execute([$matchmakingId]);
        
        $pdo->commit();
        sendResponse(['success' => true, 'status' => 'ok']);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Matchmaking heartbeat update failed: " . $e->getMessage());
        sendResponse(['success' => false, 'error' => 'Failed to update heartbeat'], 500);
    }
}

function removeMatchmaking() {
    $context = getAuthContext();
    $player = requirePlayer($context);

    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $matchmakingId = $data['matchmakingId'] ?? null;

    if (!$matchmakingId) {
        sendResponse(['success' => false, 'error' => 'Missing required field: matchmakingId'], 400);
    }

    // Only host can remove matchmaking
    if (!isMatchmakingHost($player['id'], $matchmakingId)) {
        sendResponse(['success' => false, 'error' => 'Only host can remove matchmaking lobby'], 403);
    }

    global $pdo;
    $pdo->beginTransaction();

    try {
        // Remove all players
        $stmt = $pdo->prepare("
            DELETE FROM matchmaking_players 
            WHERE matchmaking_id = ?
        ");
        $stmt->execute([$matchmakingId]);

        // Remove matchmaking
        $stmt = $pdo->prepare("
            DELETE FROM matchmaking 
            WHERE matchmaking_id = ?
        ");
        $stmt->execute([$matchmakingId]);

        $pdo->commit();

        sendResponse([
            'success' => true,
            'message' => 'Matchmaking lobby removed successfully'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Remove matchmaking failed: " . $e->getMessage());
        sendResponse(['success' => false, 'error' => 'Failed to remove matchmaking lobby'], 500);
    }
}

function respondToRequest() {
    $context = getAuthContext();
    $player = requirePlayer($context);

    $data = json_decode(file_get_contents('php://input'), true) ?: [];

    if (empty($data['requestId']) || empty($data['action'])) {
        sendResponse(['success' => false, 'error' => 'Missing required fields: requestId, action'], 400);
    }

    $requestId = $data['requestId'];
    $action = $data['action']; // 'approve' or 'reject'

    if (!in_array($action, ['approve', 'reject'])) {
        sendResponse(['success' => false, 'error' => 'Action must be "approve" or "reject"'], 400);
    }

    global $pdo;
    $pdo->beginTransaction();

    try {
        // Get the request details
        $stmt = $pdo->prepare("
            SELECT mr.*, m.host_player_id, m.matchmaking_id
            FROM matchmaking_requests mr
            JOIN matchmaking m ON mr.matchmaking_id = m.matchmaking_id
            WHERE mr.request_id = ? AND mr.status = 'pending'
        ");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            throw new Exception('Request not found or already processed');
        }

        // Verify the player is the host of this matchmaking
        if ($request['host_player_id'] !== $player['id']) {
            throw new Exception('Only the host can respond to join requests');
        }

        // Check if matchmaking is still active
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as current_players, max_players
            FROM matchmaking m
            LEFT JOIN matchmaking_players mp ON m.matchmaking_id = mp.matchmaking_id AND mp.status = 'active'
            WHERE m.matchmaking_id = ? AND m.is_started = FALSE
            GROUP BY m.matchmaking_id
        ");
        $stmt->execute([$request['matchmaking_id']]);
        $lobbyStatus = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lobbyStatus) {
            throw new Exception('Matchmaking lobby not found or already started');
        }

        // Update the request status
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("
            UPDATE matchmaking_requests 
            SET status = ?, responded_at = CURRENT_TIMESTAMP, responded_by = ?
            WHERE request_id = ?
        ");
        $stmt->execute([$status, $player['id'], $requestId]);

        // If approved, add the player to the matchmaking
        if ($action === 'approve') {
            // Check if lobby is full
            if ($lobbyStatus['current_players'] >= $lobbyStatus['max_players']) {
                throw new Exception('Matchmaking lobby is full');
            }

            // Add player to matchmaking
            $stmt = $pdo->prepare("
                INSERT INTO matchmaking_players 
                (matchmaking_id, player_id)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE
                    status = 'active',
                    joined_at = CURRENT_TIMESTAMP,
                    last_heartbeat = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$request['matchmaking_id'], $request['player_id']]);
        }

        $pdo->commit();

        sendResponse([
            'success' => true,
            'message' => "Join request {$status} successfully",
            'request_id' => $requestId,
            'action' => $action
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Respond to request failed: " . $e->getMessage());
        sendResponse(['success' => false, 'error' => $e->getMessage()], 400);
    }
}

function startMatchmaking() {
    $context = getAuthContext();
    $player = requirePlayer($context);

    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $matchmakingId = $data['matchmakingId'] ?? null;

    if (!$matchmakingId) {
        sendResponse(['success' => false, 'error' => 'Missing required field: matchmakingId'], 400);
    }

    // Only host can start matchmaking
    if (!isMatchmakingHost($player['id'], $matchmakingId)) {
        sendResponse(['success' => false, 'error' => 'Only host can start matchmaking'], 403);
    }

    global $pdo;
    $pdo->beginTransaction();

    try {
        // Get matchmaking details
        $stmt = $pdo->prepare("
            SELECT m.*, COUNT(mp.player_id) as current_players
            FROM matchmaking m
            LEFT JOIN matchmaking_players mp ON m.matchmaking_id = mp.matchmaking_id AND mp.status = 'active'
            WHERE m.matchmaking_id = ? AND m.is_started = FALSE
            GROUP BY m.matchmaking_id
        ");
        $stmt->execute([$matchmakingId]);
        $matchmaking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$matchmaking) {
            throw new Exception('Matchmaking lobby not found or already started');
        }

        // Check if strict_full requirement is met
        if ($matchmaking['strict_full'] && $matchmaking['current_players'] < $matchmaking['max_players']) {
            throw new Exception('Lobby must be full to start (strict_full enabled)');
        }

        // Create game room
        $roomId = bin2hex(random_bytes(16));
        $roomName = 'Game from Matchmaking ' . substr($matchmakingId, 0, 6);

        $stmt = $pdo->prepare("
            INSERT INTO game_rooms (room_id, room_name, max_players, host_player_id)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$roomId, $roomName, $matchmaking['max_players'], $player['id']]);

        // Move all players to game room
        $stmt = $pdo->prepare("
            INSERT INTO room_players (player_id, room_id, player_name, is_host, last_heartbeat, joined_at, is_online)
            SELECT mp.player_id, ?, gp.player_name, (mp.player_id = ?), CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, TRUE
            FROM matchmaking_players mp
            JOIN game_players gp ON mp.player_id = gp.id
            WHERE mp.matchmaking_id = ? AND mp.status = 'active'
        ");
        $stmt->execute([$roomId, $player['id'], $matchmakingId]);

        // Mark matchmaking as started
        $stmt = $pdo->prepare("
            UPDATE matchmaking 
            SET is_started = TRUE, started_at = CURRENT_TIMESTAMP
            WHERE matchmaking_id = ?
        ");
        $stmt->execute([$matchmakingId]);

        $pdo->commit();

        sendResponse([
            'success' => true,
            'room_id' => $roomId,
            'room_name' => $roomName,
            'players_transferred' => $matchmaking['current_players'],
            'message' => 'Game started successfully'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Start matchmaking failed: " . $e->getMessage());
        sendResponse(['success' => false, 'error' => $e->getMessage()], 400);
    }
}

// ────────────────────────────────────────────────
//      Routing
// ────────────────────────────────────────────────
try {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET' && preg_match('#/list/?$#', $path)) {
        listMatchmaking();
    } elseif ($method === 'POST' && preg_match('#/create/?$#', $path)) {
        createMatchmaking();
    } elseif ($method === 'POST' && preg_match('#/request/?$#', $path)) {
        requestJoin();
    } elseif ($method === 'POST' && preg_match('#/response/?$#', $path)) {
        respondToRequest();
    } elseif ($method === 'POST' && preg_match('#/join/?$#', $path)) {
        joinMatchmaking();
    } elseif ($method === 'POST' && preg_match('#/leave/?$#', $path)) {
        leaveMatchmaking();
    } elseif ($method === 'GET' && preg_match('#/players/?$#', $path)) {
        getMatchmakingPlayers();
    } elseif ($method === 'POST' && preg_match('#/heartbeat/?$#', $path)) {
        updateMatchmakingHeartbeat();
    } elseif ($method === 'POST' && preg_match('#/remove/?$#', $path)) {
        removeMatchmaking();
    } elseif ($method === 'POST' && preg_match('#/start/?$#', $path)) {
        startMatchmaking();
    } else {
        sendResponse(['success' => false, 'error' => 'Not found'], 404);
    }
} catch (Exception $e) {
    error_log("Critical error in matchmaking.php: " . $e->getMessage());
    sendResponse(['success' => false, 'error' => 'Internal server error'], 500);
}
?>
