<?php
/**
 * Direct API Test - Bypasses HTTP routing to test API logic directly
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Direct API Test ===\n";
echo "Testing registration logic directly...\n\n";

// Simulate the API environment
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/api/register';
$_SERVER['HTTP_HOST'] = 'localhost';

// Set up the request data
$requestData = [
    'email' => 'test@example.com',
    'password' => 'TestPassword123',
    'newsletter' => false
];

echo "Simulating POST request to /api/register\n";
echo "Request data: " . json_encode($requestData, JSON_PRETTY_PRINT) . "\n\n";

// Capture the API output
ob_start();

try {
    // Simulate the request input
    $GLOBALS['_test_input'] = json_encode($requestData);
    
    // Override file_get_contents for php://input
    function file_get_contents($filename, $use_include_path = false, $context = null, $offset = 0, $maxlen = null) {
        if ($filename === 'php://input') {
            return $GLOBALS['_test_input'];
        }
        return call_user_func_array('file_get_contents', func_get_args());
    }
    
    // Include the API
    include 'public_html/api/index.php';
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

$output = ob_get_clean();

echo "API Response:\n";
echo $output . "\n\n";

// Try to parse the response
if ($output) {
    $result = json_decode($output, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "Parsed Response:\n";
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
        
        if (isset($result['success'])) {
            if ($result['success']) {
                echo "✅ SUCCESS: Registration logic is working!\n";
            } else {
                echo "❌ FAILED: " . ($result['error_message'] ?? 'Unknown error') . "\n";
                if (isset($result['error_code'])) {
                    echo "Error Code: " . $result['error_code'] . "\n";
                }
            }
        }
    } else {
        echo "❌ FAILED: Invalid JSON response\n";
        echo "JSON Error: " . json_last_error_msg() . "\n";
    }
} else {
    echo "❌ FAILED: No output from API\n";
}

echo "\n=== Test Complete ===\n";
?>
