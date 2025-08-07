<?php
/**
 * Minimal API test for registration
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Token, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Minimal API is working',
        'method' => $method,
        'path' => $path,
        'timestamp' => date('c'),
        'debug' => [
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'unknown',
            'query_string' => $_SERVER['QUERY_STRING'] ?? 'unknown'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'API error: ' . $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
