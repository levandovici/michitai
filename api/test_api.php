<?php
/**
 * API Test Script for Development Debugging
 * Tests the registration endpoint to verify it's working correctly
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Multiplayer API Registration Test ===\n";
echo "Testing registration endpoint...\n\n";

// Test data
$testEmail = 'test@example.com';
$testPassword = 'TestPassword123';

// Prepare the request
$data = json_encode([
    'email' => $testEmail,
    'password' => $testPassword,
    'newsletter' => false
]);

// Set up the context for the HTTP request
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ],
        'content' => $data
    ]
]);

// Test the API endpoint
$url = 'http://localhost/api/register';
echo "Testing URL: $url\n";
echo "Request data: " . json_encode(json_decode($data), JSON_PRETTY_PRINT) . "\n\n";

// Make the request
$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "ERROR: Failed to connect to API endpoint\n";
    echo "Make sure your web server is running and the API is accessible\n";
    exit(1);
}

echo "Raw Response:\n";
echo $response . "\n\n";

// Try to decode the JSON response
$result = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "ERROR: Invalid JSON response\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
    exit(1);
}

echo "Parsed Response:\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// Check the response
if (isset($result['success'])) {
    if ($result['success']) {
        echo "✅ SUCCESS: Registration endpoint is working!\n";
        if (isset($result['data']['api_token'])) {
            echo "✅ API token generated: " . substr($result['data']['api_token'], 0, 20) . "...\n";
        }
    } else {
        echo "❌ FAILED: Registration failed\n";
        echo "Error: " . ($result['error_message'] ?? 'Unknown error') . "\n";
        if (isset($result['error_code'])) {
            echo "Error Code: " . $result['error_code'] . "\n";
        }
    }
} else {
    echo "❌ FAILED: Unexpected response format\n";
}

echo "\n=== Test Complete ===\n";
?>
