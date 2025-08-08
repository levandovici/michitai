<?php
/**
 * PHP-based Direct Login Test
 * Test login functionality with PHP form processing
 */

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>PHP Direct Login Test</h2>";

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    
    echo "<h3>Processing Login...</h3>";
    echo "<p>Email: " . htmlspecialchars($_POST['email']) . "</p>";
    echo "<p>Password: [HIDDEN]</p><br>";
    
    try {
        require_once __DIR__ . '/config/database.php';
        require_once __DIR__ . '/config/ErrorCodes.php';
        require_once __DIR__ . '/classes/Auth.php';
        
        echo "<h4>✅ Files loaded successfully</h4>";
        
        $database = Database::getInstance();
        $db = $database->getConnection();
        echo "<h4>✅ Database connected successfully</h4>";
        
        $auth = new Auth($db, true); // Enable debug mode
        echo "<h4>✅ Auth class initialized</h4>";
        
        // Test the login method directly
        $result = $auth->login($_POST['email'], $_POST['password']);
        
        echo "<h3>Login Result:</h3>";
        echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px; font-family: monospace;'>";
        echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
        echo "</div><br>";
        
        // Check if login was successful
        if (isset($result['success']) && $result['success']) {
            echo "<h4 style='color: green;'>✅ LOGIN SUCCESSFUL!</h4>";
            if (isset($result['data']['session_token'])) {
                echo "<p>Session Token: " . substr($result['data']['session_token'], 0, 20) . "...</p>";
            }
        } else {
            echo "<h4 style='color: red;'>❌ LOGIN FAILED!</h4>";
            if (isset($result['error'])) {
                echo "<p>Error: " . $result['error'] . "</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<h3 style='color: red;'>❌ PHP Error:</h3>";
        echo "<div style='background: #ffe6e6; padding: 15px; border-radius: 5px;'>";
        echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "<details><summary>Stack Trace</summary><pre>" . $e->getTraceAsString() . "</pre></details>";
        echo "</div>";
    }
    
    echo "<hr><a href='?'>← Test Again</a>";
    
} else {
    // Show test form
    ?>
    <form method="POST" style="background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 8px; max-width: 500px;">
        <h3>Enter Your Login Credentials</h3>
        <p>
            <label style="display: block; margin-bottom: 5px;">Email:</label>
            <input type="email" name="email" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
        </p>
        <p>
            <label style="display: block; margin-bottom: 5px;">Password:</label>
            <input type="password" name="password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
        </p>
        <p>
            <button type="submit" style="background: #007cba; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">Test Login with PHP</button>
        </p>
    </form>
    
    <div style="background: #e6f3ff; padding: 15px; border-radius: 5px; margin: 20px 0; max-width: 500px;">
        <h4>What This Test Will Show:</h4>
        <ul>
            <li>✅ File loading status</li>
            <li>✅ Database connection status</li>
            <li>✅ Auth class initialization</li>
            <li>✅ Complete login result with all data</li>
            <li>✅ Detailed error messages if login fails</li>
            <li>✅ Session token if login succeeds</li>
        </ul>
    </div>
    <?php
}
?>
