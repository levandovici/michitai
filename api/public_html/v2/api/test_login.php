<?php
/**
 * Login Debug Test Script
 * Test login functionality directly
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set paths
$apiPath = __DIR__;

try {
    require_once $apiPath . '/config/database.php';
    require_once $apiPath . '/config/ErrorCodes.php';
    require_once $apiPath . '/classes/Auth.php';
} catch (Exception $e) {
    die('Error loading required files: ' . $e->getMessage());
}

// Handle form submission
$testEmail = $_POST['email'] ?? '';
$testPassword = $_POST['password'] ?? '';
$doTest = !empty($testEmail) && !empty($testPassword);

echo "<h2>Login Debug Test</h2>";

// Login test form
echo '<form method="POST" style="background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 8px;">';
echo '<h3>Enter Your Login Credentials</h3>';
echo '<p><label>Email: <input type="email" name="email" value="' . htmlspecialchars($testEmail) . '" required style="width: 300px; padding: 8px; margin-left: 10px;"></label></p>';
echo '<p><label>Password: <input type="password" name="password" value="' . htmlspecialchars($testPassword) . '" required style="width: 300px; padding: 8px; margin-left: 10px;"></label></p>';
echo '<p><button type="submit" style="background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">Test Login</button></p>';
echo '</form>';

if ($doTest) {
    try {
        // Initialize database and auth
        $config = new DatabaseConfig();
        $db = $config->getConnection();
        $auth = new Auth($db, true); // Enable debug mode
        
        echo "<h3>Database Connection</h3>";
        echo "✅ Database connected successfully<br><br>";
        
        // Test user lookup
        echo "<h3>User Lookup Test</h3>";
        
        $stmt = $db->prepare("SELECT user_id, email, password, email_verified, plan_type FROM users WHERE email = ?");
        $stmt->execute([$testEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "✅ User found: " . $user['email'] . "<br>";
            echo "User ID: " . $user['user_id'] . "<br>";
            echo "Email Verified: " . (isset($user['email_verified']) ? ($user['email_verified'] ? 'Yes' : 'No') : 'Column missing') . "<br>";
            echo "Plan Type: " . ($user['plan_type'] ?? 'free') . "<br>";
            echo "Password Hash: " . substr($user['password'], 0, 20) . "...<br><br>";
        } else {
            echo "❌ User not found for email: $testEmail<br><br>";
        }
        
        // Test login method
        echo "<h3>Login Method Test</h3>";
        
        if ($user) {
            $loginResult = $auth->login($testEmail, $testPassword);
            echo "Login Result:<br>";
            echo "<pre>" . json_encode($loginResult, JSON_PRETTY_PRINT) . "</pre><br>";
            
            // Test password verification directly
            echo "<h3>Password Verification Test</h3>";
            $passwordCheck = password_verify($testPassword, $user['password']);
            echo "Password verification: " . ($passwordCheck ? '✅ Valid' : '❌ Invalid') . "<br><br>";
        }
    
    // List all users for debugging
    echo "<h3>All Users in Database</h3>";
    $stmt = $db->prepare("SELECT user_id, email, email_verified, plan_type, created_at FROM users ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($users) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Email</th><th>Verified</th><th>Plan</th><th>Created</th></tr>";
        foreach ($users as $u) {
            echo "<tr>";
            echo "<td>" . $u['user_id'] . "</td>";
            echo "<td>" . $u['email'] . "</td>";
            echo "<td>" . ($u['email_verified'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . ($u['plan_type'] ?? 'free') . "</td>";
            echo "<td>" . date('Y-m-d H:i:s', $u['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "No users found in database<br><br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}
?>
