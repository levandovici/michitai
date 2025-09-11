<?php
header('Content-Type: application/json');
require_once 'config.php';

// Helper function to send JSON response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Helper function to validate API key and return game data
function getGameByApiKey($apiKey) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, user_id, game_data FROM api_keys WHERE api_key = ?");
    $stmt->bind_param("s", $apiKey);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get request headers
$headers = getallheaders();
$apiKey = isset($headers['X-API-Key']) ? $headers['X-API-Key'] : '';

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Route the request
switch ($method) {
    case 'GET':
        // Get game data
        if (empty($apiKey)) {
            sendResponse(['error' => 'API key is required'], 401);
        }
        
        $game = getGameByApiKey($apiKey);
        if (!$game) {
            sendResponse(['error' => 'Invalid API key'], 401);
        }
        
        // Return game data
        $gameData = json_decode($game['game_data'] ?? '{}', true);
        sendResponse([
            'success' => true,
            'game_id' => $game['id'],
            'data' => $gameData
        ]);
        break;
        
    case 'PUT':
        // Update game data
        if (empty($apiKey)) {
            sendResponse(['error' => 'API key is required'], 401);
        }
        
        if (empty($input)) {
            sendResponse(['error' => 'No data provided for update'], 400);
        }
        
        $game = getGameByApiKey($apiKey);
        if (!$game) {
            sendResponse(['error' => 'Invalid API key'], 401);
        }
        
        // If game_data is empty, initialize it as an empty object
        $currentData = !empty($game['game_data']) ? json_decode($game['game_data'], true) : [];
        
        // Merge new data with existing data (overwrites existing keys)
        $updatedData = array_merge($currentData, $input);
        $updatedDataJson = json_encode($updatedData);
        
        // Update the game data in the database
        $stmt = $conn->prepare("UPDATE api_keys SET game_data = ? WHERE id = ?");
        $stmt->bind_param("si", $updatedDataJson, $game['id']);
        
        if ($stmt->execute()) {
            sendResponse([
                'success' => true,
                'message' => 'Game data updated successfully',
                'game_id' => $game['id']
            ]);
        } else {
            sendResponse(['error' => 'Failed to update game data'], 500);
        }
        break;
        
    default:
        sendResponse(['error' => 'Method not allowed'], 405);
}
?>
