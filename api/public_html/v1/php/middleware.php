<?php
/**
 * API Key Authentication Middleware
 * 
 * This middleware validates the X-API-Key header for all API requests.
 */

function validateApiKey() {
    // Skip API key validation for OPTIONS requests (CORS preflight)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        return true;
    }

    // Get API key from header
    $apiKey = null;
    $headers = getallheaders();
    
    // Check for X-API-Key header (case-insensitive)
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'x-api-key') {
            $apiKey = $value;
            break;
        }
    }

    if (empty($apiKey)) {
        http_response_code(401);
        echo json_encode(['error' => 'API key is required']);
        exit;
    }

    // Validate API key against database
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT user_id, project_name FROM api_keys WHERE api_key = :api_key AND is_active = 1");
        $stmt->execute(['api_key' => $apiKey]);
        $keyData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$keyData) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid or inactive API key']);
            exit;
        }

        // Store user ID in a global variable for use in other scripts
        $GLOBALS['api_user_id'] = $keyData['user_id'];
        $GLOBALS['api_project_name'] = $keyData['project_name'];
        
        return true;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error while validating API key']);
        exit;
    }
}

// Include this in your API endpoints:
// require_once 'middleware.php';
// validateApiKey();
?>
