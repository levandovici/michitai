<?php
/**
 * Simple Login Diagnostic Script
 * Minimal version to avoid HTTP 500 errors
 */

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Simple Login Diagnostic</h2>";

// Test 1: Check if files exist
echo "<h3>1. File Existence Check</h3>";
$files = [
    'config/database.php',
    'config/ErrorCodes.php', 
    'classes/Auth.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

// Test 2: Try database connection
echo "<br><h3>2. Database Connection Test</h3>";
try {
    require_once __DIR__ . '/config/database.php';
    $database = Database::getInstance();
    $db = $database->getConnection();
    echo "✅ Database connected successfully<br>";
    
    // Test 3: Check users table structure
    echo "<br><h3>3. Users Table Structure</h3>";
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Check for email verification columns
    $hasEmailVerified = false;
    $hasVerificationToken = false;
    $hasTokenExpires = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'email_verified') $hasEmailVerified = true;
        if ($col['Field'] === 'verification_token') $hasVerificationToken = true;
        if ($col['Field'] === 'token_expires') $hasTokenExpires = true;
    }
    
    echo "<h3>4. Email Verification Columns Check</h3>";
    echo "email_verified: " . ($hasEmailVerified ? "✅ Present" : "❌ Missing") . "<br>";
    echo "verification_token: " . ($hasVerificationToken ? "✅ Present" : "❌ Missing") . "<br>";
    echo "token_expires: " . ($hasTokenExpires ? "✅ Present" : "❌ Missing") . "<br><br>";
    
    // Test 4: Count users
    echo "<h3>5. Users Count</h3>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total users in database: " . $result['count'] . "<br><br>";
    
    if ($result['count'] > 0) {
        echo "<h3>6. Sample User Data</h3>";
        $stmt = $db->query("SELECT user_id, email, created_at FROM users ORDER BY created_at DESC LIMIT 3");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Email</th><th>Created</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['user_id'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            // Handle both timestamp formats
            $created = $user['created_at'];
            if (is_numeric($created)) {
                $created = date('Y-m-d H:i:s', (int)$created);
            }
            echo "<td>" . $created . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<br><h3>7. Next Steps</h3>";
echo "If email verification columns are missing, you need to run:<br>";
echo "1. schema_updates.sql<br>";
echo "2. add_token_expires.sql<br>";
echo "<br>These will add the required columns for login to work properly.";
?>
