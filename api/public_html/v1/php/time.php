<?php
// time.php  – place it in the root of your site (or any folder you like)

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0777, true);
}

// Log the request
error_log("=== Time API Request ===");
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Headers: " . print_r(getallheaders(), true));

// ... [previous code remains the same until line 20]

try {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');   // allow Unity WebGL
    require_once 'config.php';

    // Helper function to send JSON response
    function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // Helper function to validate API key
    function validateApiKey($apiKey) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT id, user_id, game_data FROM api_keys WHERE api_key = ?");
        $stmt->execute([$apiKey]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get request method
    $method = $_SERVER['REQUEST_METHOD'];

    // Only allow GET requests
    if ($method !== 'GET') {
        sendResponse([
            'success' => false,
            'error' => 'Method not allowed',
            'allowed_methods' => ['GET']
        ], 405);
    }

    // Get API token from query string
    $apiToken = $_GET['api_token'] ?? '';
    if (empty($apiToken)) {
        sendResponse([
            'success' => false,
            'error' => 'API key is required',
            'hint' => 'Add ?api_token=YOUR_API_KEY to your request'
        ], 401);
    }

    // Validate API key
    $keyData = validateApiKey($apiToken);
    if (!$keyData) {
        sendResponse([
            'success' => false,
            'error' => 'Invalid API key',
            'hint' => 'Please check your API key and try again'
        ], 403);
    }

    // Set timezone to UTC
    date_default_timezone_set('UTC');
    $timestamp = time();
    $utc = gmdate('c');
    $readable = gmdate('Y-m-d H:i:s') . ' UTC';

    // Log successful response
    error_log("Time API Response for user_id: " . $keyData['user_id']);

    // Return time data
    sendResponse([
        'success' => true,
        'utc' => $utc,
        'timestamp' => $timestamp,
        'readable' => $readable
    ]);

} catch (Exception $e) {
    error_log("Time API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    sendResponse([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ], 500);
}