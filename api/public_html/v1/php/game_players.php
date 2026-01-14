<?php
header('Content-Type: application/json');
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper function to send JSON response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Helper function to validate API key and return game data
function validateApiKey($apiKey) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, user_id, game_data FROM api_keys WHERE api_key = ?");
    $stmt->execute([$apiKey]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Helper function to validate API private key and return game data
function validateApiKeys($apiKey, $apiPrivateKey) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, user_id, game_data FROM api_keys WHERE api_key = ? AND api_private_key = ?");
    $stmt->execute([$apiKey, $apiPrivateKey]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Helper function to validate private key and return player data
function validatePrivateKey($privateKey) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM game_players WHERE private_key = ?");
    $stmt->execute([$privateKey]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Register a new player for a game
function registerPlayer($gameId, $playerName, $playerData = []) {
    global $pdo;
    
    // Generate a secure private key for the player
    $privateKey = bin2hex(random_bytes(16));
    $playerDataJson = json_encode($playerData, JSON_UNESCAPED_UNICODE);
    
    $stmt = $pdo->prepare("INSERT INTO game_players (game_id, player_name, private_key, player_data) VALUES (?, ?, ?, ?)");
    $stmt->execute([$gameId, $playerName, $privateKey, $playerDataJson]);
    
    if ($stmt->rowCount() > 0) {
        return [
            'success' => true,
            'player_id' => $pdo->lastInsertId(),
            'private_key' => $privateKey,
            'player_name' => $playerName,
            'game_id' => $gameId
        ];
    }
    
    return false;
}

// Main request handler
try {
    // Get request method
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Get API token from query string
    $apiToken = $_GET['api_token'] ?? '';
    $apiPrivateToken = $_GET['api_private_token'] ?? '';
    
    // Get game player token from query string
    $gamePlayerToken = $_GET['game_player_token'] ?? '';
    
    // Get request body
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    
    // Route the request
    switch ($method) {
        case 'POST':
            // Register new player for a game
            if (empty($apiToken)) {
                sendResponse(['success' => false, 'error' => 'API token is required'], 401);
            }
            
            // Validate API key
            $game = validateApiKey($apiToken);
            if (!$game) {
                sendResponse(['success' => false, 'error' => 'Invalid API token'], 401);
            }
            
            // Get player name from input
            $playerName = $input['player_name'] ?? '';
            if (empty($playerName)) {
                sendResponse(['success' => false, 'error' => 'Player name is required'], 400);
            }
            
            // Register the player
            $result = registerPlayer($game['id'], $playerName, $input['player_data'] ?? []);
            if ($result['success']) {
                sendResponse($result);
            } else {
                sendResponse($result, 500);
            }
            break;
            
        case 'GET':
            // List all players for a game
            if (empty($apiToken)) {
                sendResponse(['success' => false, 'error' => 'API token is required'], 401);
            }

            if(empty($apiPrivateToken)) {
                sendResponse(['success' => false, 'error' => 'API private token is required'], 401);
            }
            
            // Validate API keys
            $game = validateApiKeys($apiToken, $apiPrivateToken);
            if (!$game) {
                sendResponse(['success' => false, 'error' => 'Invalid API token or API private token'], 401);
            }
            
            // Get all players for this game
            $stmt = $pdo->prepare("SELECT id, player_name, is_active, last_login, created_at FROM game_players WHERE game_id = ?");
            $stmt->execute([$game['id']]);
            $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendResponse([
                'success' => true,
                'count' => count($players),
                'players' => $players
            ]);
            break;
            
        case 'PUT':
            // Authenticate player with private key
            if (empty($apiToken) || empty($gamePlayerToken)) {
                sendResponse(['success' => false, 'error' => 'API token and game player token are required'], 401);
            }
            
            // Validate API key
            $game = validateApiKey($apiToken);
            if (!$game) {
                sendResponse(['success' => false, 'error' => 'Invalid API token'], 401);
            }
            
            // Validate player token
            $player = validatePrivateKey($gamePlayerToken);
            if (!$player) {
                sendResponse(['success' => false, 'error' => 'Invalid game player token'], 401);
            }
            
            // Check if player belongs to this game
            if ($player['game_id'] != $game['id']) {
                sendResponse(['success' => false, 'error' => 'Player does not belong to this game'], 403);
            }
            
            // Update last login time
            $stmt = $pdo->prepare("UPDATE game_players SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$player['id']]);
            
            // Return player data (excluding sensitive fields)
            unset($player['private_key']);
            $player['player_data'] = json_decode($player['player_data'] ?? '{}', true);
            
            sendResponse([
                'success' => true,
                'player' => $player
            ]);
            break;
            
        default:
            sendResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    error_log('Error in game_players.php: ' . $e->getMessage());
    sendResponse([
        'success' => false,
        'error' => 'Internal server error',
        'debug' => (ENVIRONMENT === 'development') ? $e->getMessage() : null
    ], 500);
}
?>
