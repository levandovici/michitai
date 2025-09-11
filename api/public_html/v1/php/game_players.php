<?php
header('Content-Type: application/json');
require_once 'config.php';

// Helper function to send JSON response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Helper function to validate API key
function validateApiKey($apiKey) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, user_id, game_data FROM api_keys WHERE api_key = ?");
    $stmt->bind_param("s", $apiKey);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Helper function to validate private key
function validatePrivateKey($privateKey) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM game_players WHERE private_key = ?");
    $stmt->bind_param("s", $privateKey);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Register a new player for a game
function registerPlayer($gameId, $playerName, $playerData = []) {
    global $conn;
    
    // Generate a secure private key for the player
    $privateKey = bin2hex(random_bytes(16));
    
    $stmt = $conn->prepare("INSERT INTO game_players (game_id, player_name, private_key, player_data) VALUES (?, ?, ?, ?)");
    $playerDataJson = json_encode($playerData);
    $stmt->bind_param("isss", $gameId, $playerName, $privateKey, $playerDataJson);
    
    if ($stmt->execute()) {
        return [
            'player_id' => $conn->insert_id,
            'private_key' => $privateKey,
            'player_name' => $playerName,
            'game_id' => $gameId
        ];
    }
    
    return false;
}

// Main request handler
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

// Get request headers
$headers = getallheaders();
$apiKey = isset($headers['X-API-Key']) ? $headers['X-API-Key'] : '';
$privateKey = isset($headers['X-Private-Key']) ? $headers['X-Private-Key'] : '';

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

// Route the request
switch ($method) {
    case 'POST':
        // Handle player registration
        if ($endpoint === 'register') {
            if (empty($apiKey)) {
                sendResponse(['error' => 'API key is required'], 401);
            }
            
            $game = validateApiKey($apiKey);
            if (!$game) {
                sendResponse(['error' => 'Invalid API key'], 401);
            }
            
            if (empty($input['player_name'])) {
                sendResponse(['error' => 'Player name is required'], 400);
            }
            
            $playerData = isset($input['player_data']) ? $input['player_data'] : [];
            $result = registerPlayer($game['id'], $input['player_name'], $playerData);
            
            if ($result) {
                sendResponse(['success' => true, 'data' => $result]);
            } else {
                sendResponse(['error' => 'Failed to register player'], 500);
            }
        }
        // Handle player login
        elseif ($endpoint === 'login') {
            if (empty($privateKey)) {
                sendResponse(['error' => 'Private key is required'], 401);
            }
            
            $player = validatePrivateKey($privateKey);
            if (!$player) {
                sendResponse(['error' => 'Invalid private key'], 401);
            }
            
            // Update last login time
            $stmt = $conn->prepare("UPDATE game_players SET last_login = NOW() WHERE id = ?");
            $stmt->bind_param("i", $player['id']);
            $stmt->execute();
            
            // Return player data (excluding sensitive fields)
            unset($player['private_key']);
            sendResponse(['success' => true, 'data' => $player]);
        }
        break;
        
    case 'GET':
        // Get player data
        if ($endpoint === 'data' && $privateKey) {
            $player = validatePrivateKey($privateKey);
            if (!$player) {
                sendResponse(['error' => 'Invalid private key'], 401);
            }
            
            // Return player data (excluding sensitive fields)
            unset($player['private_key']);
            sendResponse(['success' => true, 'data' => $player]);
        }
        // List all players for a game (admin only)
        elseif ($endpoint === 'list' && $apiKey) {
            $game = validateApiKey($apiKey);
            if (!$game) {
                sendResponse(['error' => 'Invalid API key'], 401);
            }
            
            $stmt = $conn->prepare("SELECT id, player_name, is_active, last_login, created_at FROM game_players WHERE game_id = ?");
            $stmt->bind_param("i", $game['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $players = $result->fetch_all(MYSQLI_ASSOC);
            
            sendResponse(['success' => true, 'data' => $players]);
        }
        break;
        
    case 'PUT':
        // Update player data
        if ($endpoint === 'data' && $privateKey) {
            $player = validatePrivateKey($privateKey);
            if (!$player) {
                sendResponse(['error' => 'Invalid private key'], 401);
            }
            
            if (empty($input)) {
                sendResponse(['error' => 'No data provided for update'], 400);
            }
            
            // Update player data
            $updates = [];
            $types = '';
            $params = [];
            
            if (isset($input['player_name'])) {
                $updates[] = 'player_name = ?';
                $types .= 's';
                $params[] = &$input['player_name'];
            }
            
            if (isset($input['player_data'])) {
                $updates[] = 'player_data = ?';
                $types .= 's';
                $playerDataJson = json_encode($input['player_data']);
                $params[] = &$playerDataJson;
            }
            
            if (isset($input['is_active'])) {
                $updates[] = 'is_active = ?';
                $types .= 'i';
                $params[] = &$input['is_active'];
            }
            
            if (empty($updates)) {
                sendResponse(['error' => 'No valid fields to update'], 400);
            }
            
            $query = "UPDATE game_players SET " . implode(', ', $updates) . " WHERE id = ?";
            $types .= 'i';
            $params[] = &$player['id'];
            
            $stmt = $conn->prepare($query);
            array_unshift($params, $types);
            call_user_func_array([$stmt, 'bind_param'], $params);
            
            if ($stmt->execute()) {
                sendResponse(['success' => true, 'message' => 'Player updated successfully']);
            } else {
                sendResponse(['error' => 'Failed to update player'], 500);
            }
        }
        break;
        
    default:
        sendResponse(['error' => 'Method not allowed'], 405);
}

// If no endpoint matched
sendResponse(['error' => 'Not found'], 404);
?>
