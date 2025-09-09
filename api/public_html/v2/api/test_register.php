<?php
/**
 * Test Registration Endpoint Directly
 * This will help us identify the exact error causing Error Code 5001
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Registration Test</title></head><body>";
echo "<h1>Registration Endpoint Test</h1>";

try {
    // Include required files
    require_once __DIR__ . '/config/ErrorCodes.php';
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/classes/Auth.php';
    
    echo "<h2>✅ All classes loaded successfully</h2>";
    
    // Test Auth instantiation
    $auth = new Auth();
    echo "<h2>✅ Auth class instantiated</h2>";
    
    // Test database connection first
    echo "<h2>Testing Database Connection...</h2>";
    
    // Get database instance
    $database = Database::getInstance();
    $db = $database->getConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Test if users table exists
    try {
        $stmt = $db->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>✅ Users table exists with columns:</p>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>" . htmlspecialchars($column['Field']) . " (" . htmlspecialchars($column['Type']) . ")</li>";
        }
        echo "</ul>";
    } catch (Exception $e) {
        echo "<p>❌ Users table issue: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Test registration call directly with detailed error catching
    echo "<h2>Testing Registration...</h2>";
    
    $testEmail = "test" . time() . "@example.com"; // Use unique email
    $testPassword = "TestPassword123";
    $newsletter = false;
    
    echo "<p>Testing with email: $testEmail</p>";
    
    // Enable debug mode temporarily
    define('DEBUG_MODE', true);
    
    try {
        $result = $auth->register($testEmail, $testPassword, $newsletter);
        
        echo "<h2>Registration Result:</h2>";
        echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
        
        // If it failed, let's try to register manually to see the exact error
        if (!$result['success']) {
            echo "<h2>Manual Registration Test:</h2>";
            
            // Test each step manually
            echo "<p>1. Email validation: " . (filter_var($testEmail, FILTER_VALIDATE_EMAIL) ? "✅ Valid" : "❌ Invalid") . "</p>";
            
            // Test password strength
            $isStrong = strlen($testPassword) >= 8 && 
                       preg_match('/[A-Z]/', $testPassword) && 
                       preg_match('/[a-z]/', $testPassword) && 
                       preg_match('/[0-9]/', $testPassword);
            echo "<p>2. Password strength: " . ($isStrong ? "✅ Strong" : "❌ Weak") . "</p>";
            
            // Test email existence check
            try {
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$testEmail]);
                $exists = $stmt->fetch();
                echo "<p>3. Email exists check: " . ($exists ? "❌ Email exists" : "✅ Email available") . "</p>";
            } catch (Exception $e) {
                echo "<p>3. Email exists check failed: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            
            // Test manual insert
            try {
                echo "<p>4. Testing manual insert...</p>";
                $passwordHash = password_hash($testPassword, PASSWORD_DEFAULT);
                $apiToken = 'mapi_' . bin2hex(random_bytes(32));
                $verificationToken = bin2hex(random_bytes(32));
                $timestamp = time();
                
                $stmt = $db->prepare("
                    INSERT INTO users (email, password_hash, api_token, verification_token, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $insertResult = $stmt->execute([
                    $testEmail, 
                    $passwordHash, 
                    $apiToken, 
                    $verificationToken, 
                    $timestamp, 
                    $timestamp
                ]);
                
                if ($insertResult) {
                    echo "<p>✅ Manual insert successful!</p>";
                    $userId = $db->lastInsertId();
                    echo "<p>New user ID: $userId</p>";
                } else {
                    echo "<p>❌ Manual insert failed</p>";
                }
                
            } catch (Exception $e) {
                echo "<p>❌ Manual insert error: " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p><strong>Error details:</strong></p>";
                echo "<ul>";
                echo "<li>Code: " . $e->getCode() . "</li>";
                echo "<li>File: " . htmlspecialchars($e->getFile()) . "</li>";
                echo "<li>Line: " . $e->getLine() . "</li>";
                echo "</ul>";
            }
        }
        
    } catch (Exception $e) {
        echo "<h2>❌ Registration Test Exception:</h2>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "<h3>Stack Trace:</h3>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error occurred:</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<h3>Stack Trace:</h3>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>
