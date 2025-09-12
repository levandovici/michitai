<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'middleware.php';

// Helper function to send JSON response
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Get request body
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// Get request method
$method = strtoupper($_SERVER['REQUEST_METHOD']);

try {
    // Validate API key for all requests
    validateApiKey();
    
    // Get the authenticated user ID from middleware
    $userId = $GLOBALS['api_user_id'] ?? null;
    $projectName = $GLOBALS['api_project_name'] ?? 'default';
    
    // Route the request
    switch ($method) {
        case 'GET':
            // Get game data for the authenticated user's project
            $stmt = $pdo->prepare("
                SELECT g.* 
                FROM games g
                JOIN api_keys ak ON g.user_id = ak.user_id
                WHERE g.user_id = :user_id 
                AND ak.project_name = :project_name
                ORDER BY g.updated_at DESC
                LIMIT 1
            ");
            
            $stmt->execute([
                'user_id' => $userId,
                'project_name' => $projectName
            ]);
            
            $game = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$game) {
                sendResponse([
                    'success' => false,
                    'error' => 'No game data found for this project'
                ], 404);
            }
            
            // Return game data
            $gameData = json_decode($game['json_structure'] ?? '{}', true);
            sendResponse([
                'success' => true,
                'game_id' => $game['id'],
                'project_name' => $projectName,
                'data' => $gameData
        ]);
        break;
        
    case 'PUT':
        // Update game data
        if (empty($input)) {
            sendResponse(['error' => 'No data provided for update'], 400);
        }
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Check if game exists for this user and project
            $stmt = $pdo->prepare("
                SELECT g.id, g.json_structure 
                FROM games g
                JOIN api_keys ak ON g.user_id = ak.user_id
                WHERE g.user_id = :user_id 
                AND ak.project_name = :project_name
                ORDER BY g.updated_at DESC
                LIMIT 1
            ");
            
            $stmt->execute([
                'user_id' => $userId,
                'project_name' => $projectName
            ]);
            
            $game = $stmt->fetch(PDO::FETCH_ASSOC);
            $now = date('Y-m-d H:i:s');
            
            if ($game) {
                // Merge with existing data if needed
                $currentData = !empty($game['json_structure']) ? 
                    json_decode($game['json_structure'], true) : [];
                $updatedData = array_merge($currentData, $input);
                
                // Update existing game
                $stmt = $pdo->prepare("
                    UPDATE games 
                    SET json_structure = :data,
                        updated_at = :updated_at
                    WHERE id = :id AND user_id = :user_id
                ");
                
                $updateResult = $stmt->execute([
                    'data' => json_encode($updatedData, JSON_UNESCAPED_UNICODE),
                    'updated_at' => $now,
                    'id' => $game['id'],
                    'user_id' => $userId
                ]);
                
                if (!$updateResult) {
                    throw new Exception('Failed to update game data in database');
                }
                
                $gameId = $game['id'];
            } else {
                // Create new game with the input data
                $stmt = $pdo->prepare("
                    INSERT INTO games (user_id, json_structure, created_at, updated_at)
                    VALUES (:user_id, :data, :created_at, :updated_at)
                ");
                
                $stmt->execute([
                    'user_id' => $userId,
                    'data' => json_encode($input, JSON_UNESCAPED_UNICODE),
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
                
                $gameId = $pdo->lastInsertId();
            }
            
            $pdo->commit();
            
            sendResponse([
                'success' => true,
                'game_id' => $gameId,
                'project_name' => $projectName,
                'message' => 'Game data saved successfully',
                'updated_at' => $now
            ]);
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            error_log('Game data update failed: ' . $e->getMessage());
            sendResponse([
                'success' => false,
                'error' => 'Failed to save game data',
                'debug' => $e->getMessage()
            ], 500);
        }
        break;
        
    default:
        sendResponse(['error' => 'Method not allowed'], 405);
}
?>
