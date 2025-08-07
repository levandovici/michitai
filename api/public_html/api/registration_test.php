<?php
/**
 * Test registration endpoint with new structure
 */

header('Content-Type: application/json');

// Simulate a POST request to the registration endpoint
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/api/register';

// Test data
$testData = [
    'email' => 'newstructure_test@example.com',
    'password' => 'TestPassword123',
    'newsletter' => false
];

// Simulate the request input
$GLOBALS['_test_input'] = json_encode($testData);

// Override file_get_contents for php://input
if (!function_exists('original_file_get_contents')) {
    function original_file_get_contents($filename, $use_include_path = false, $context = null, $offset = 0, $maxlen = null) {
        if ($filename === 'php://input') {
            return $GLOBALS['_test_input'];
        }
        // For other files, use the original function
        return file_get_contents($filename, $use_include_path, $context, $offset, $maxlen);
    }
}

echo json_encode([
    'test' => 'registration_endpoint_simulation',
    'timestamp' => date('c'),
    'request_data' => $testData,
    'message' => 'Testing registration with new structure...'
], JSON_PRETTY_PRINT);

echo "\n\n--- API Response ---\n";

// Capture output from the main API
ob_start();

try {
    // Include the main API file
    include __DIR__ . '/index.php';
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Exception during API execution',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}

$apiOutput = ob_get_clean();
echo $apiOutput;
?>
