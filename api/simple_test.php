<?php
/**
 * Simple API Test - Tests API components individually
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Simple API Component Test ===\n\n";

// Test 1: Check if API file exists and is readable
echo "1. Testing API file access...\n";
$apiFile = 'public_html/api/index.php';
if (file_exists($apiFile)) {
    echo "✅ API file exists: $apiFile\n";
    if (is_readable($apiFile)) {
        echo "✅ API file is readable\n";
    } else {
        echo "❌ API file is not readable\n";
    }
} else {
    echo "❌ API file not found: $apiFile\n";
}

// Test 2: Check private directory structure
echo "\n2. Testing private directory structure...\n";
$privatePath = 'private/api_backend';
if (is_dir($privatePath)) {
    echo "✅ Private directory exists: $privatePath\n";
} else {
    echo "❌ Private directory not found: $privatePath\n";
    echo "Creating private directory...\n";
    mkdir($privatePath, 0755, true);
}

// Test 3: Check if ErrorCodes class can be loaded
echo "\n3. Testing ErrorCodes class...\n";
$errorCodesFile = 'private/api_backend/config/ErrorCodes.php';
if (file_exists($errorCodesFile)) {
    echo "✅ ErrorCodes file exists\n";
    require_once $errorCodesFile;
    if (class_exists('ErrorCodes')) {
        echo "✅ ErrorCodes class loaded successfully\n";
        $testResponse = ErrorCodes::createSuccessResponse(['test' => 'data'], 'Test message');
        echo "✅ ErrorCodes test response: " . json_encode($testResponse) . "\n";
    } else {
        echo "❌ ErrorCodes class not found after include\n";
    }
} else {
    echo "❌ ErrorCodes file not found: $errorCodesFile\n";
}

// Test 4: Check if Auth class can be loaded
echo "\n4. Testing Auth class...\n";
$authFile = 'private/api_backend/classes/Auth.php';
if (file_exists($authFile)) {
    echo "✅ Auth file exists\n";
    try {
        require_once $authFile;
        if (class_exists('Auth')) {
            echo "✅ Auth class loaded successfully\n";
            
            // Test Auth instantiation
            $auth = new Auth();
            echo "✅ Auth class instantiated successfully\n";
            
            // Test registration method
            $result = $auth->register('test@example.com', 'TestPassword123', false);
            echo "✅ Auth registration test result: " . json_encode($result) . "\n";
            
        } else {
            echo "❌ Auth class not found after include\n";
        }
    } catch (Exception $e) {
        echo "❌ Auth class error: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "❌ Auth file not found: $authFile\n";
}

// Test 5: Check database directory
echo "\n5. Testing database setup...\n";
$dbDir = 'private/database';
if (!is_dir($dbDir)) {
    echo "Creating database directory: $dbDir\n";
    mkdir($dbDir, 0755, true);
}
$dbFile = $dbDir . '/multiplayer_api.db';
echo "Database file path: $dbFile\n";
if (file_exists($dbFile)) {
    echo "✅ Database file exists\n";
} else {
    echo "ℹ️ Database file will be created on first use\n";
}

echo "\n=== Test Complete ===\n";
echo "\nIf all tests pass, the issue is likely with web server routing.\n";
echo "Try accessing the API directly at: http://yourdomain.com/api/register\n";
?>
