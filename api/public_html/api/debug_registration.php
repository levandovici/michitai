<?php
/**
 * Debug Registration and Email Sending
 * This script tests the complete registration flow including email sending
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set debug mode
define('DEBUG_MODE', true);

// Include required files
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/ErrorCodes.php';
require_once __DIR__ . '/classes/Auth.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Debug - Multiplayer API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .timestamp { font-family: monospace; background: #e9ecef; padding: 2px 4px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Registration & Email Debug</h1>
        
        <?php
        // Test 1: Timestamp Calculation
        echo '<div class="debug-section info">';
        echo '<h3>📅 Timestamp Calculation Test</h3>';
        
        $currentTime = time();
        $tokenExpires = $currentTime + (24 * 60 * 60); // 24 hours from now
        
        echo "<p><strong>Current time():</strong> <span class='timestamp'>$currentTime</span></p>";
        echo "<p><strong>Current date:</strong> " . date('Y-m-d H:i:s', $currentTime) . " UTC</p>";
        echo "<p><strong>Token expires timestamp:</strong> <span class='timestamp'>$tokenExpires</span></p>";
        echo "<p><strong>Token expires date:</strong> " . date('Y-m-d H:i:s', $tokenExpires) . " UTC</p>";
        
        // Check your database value
        $yourDbValue = 1754719276;
        echo "<p><strong>Your DB value:</strong> <span class='timestamp'>$yourDbValue</span></p>";
        echo "<p><strong>Your DB date:</strong> " . date('Y-m-d H:i:s', $yourDbValue) . " UTC</p>";
        
        // Check if it's expired
        $isExpired = $currentTime > $yourDbValue;
        echo "<p><strong>Is expired?</strong> " . ($isExpired ? '<span style="color: red;">YES</span>' : '<span style="color: green;">NO</span>') . "</p>";
        
        // Calculate difference
        $diff = $yourDbValue - $currentTime;
        $hours = round($diff / 3600, 2);
        echo "<p><strong>Hours until expiration:</strong> $hours</p>";
        
        echo '</div>';
        
        // Test 2: SMTP Configuration
        echo '<div class="debug-section info">';
        echo '<h3>📧 SMTP Configuration Test</h3>';
        
        try {
            $config = Database::loadEnv();
            echo "<p><strong>SMTP_HOST:</strong> " . ($config['SMTP_HOST'] ?? 'NOT SET') . "</p>";
            echo "<p><strong>SMTP_PORT:</strong> " . ($config['SMTP_PORT'] ?? 'NOT SET') . "</p>";
            echo "<p><strong>SMTP_USERNAME:</strong> " . ($config['SMTP_USERNAME'] ?? 'NOT SET') . "</p>";
            echo "<p><strong>SMTP_PASSWORD:</strong> " . (isset($config['SMTP_PASSWORD']) && !empty($config['SMTP_PASSWORD']) ? '[SET]' : 'NOT SET') . "</p>";
            echo "<p><strong>SMTP_FROM_EMAIL:</strong> " . ($config['SMTP_FROM_EMAIL'] ?? 'NOT SET') . "</p>";
            echo "<p><strong>SMTP_FROM_NAME:</strong> " . ($config['SMTP_FROM_NAME'] ?? 'NOT SET') . "</p>";
        } catch (Exception $e) {
            echo "<p class='error'>Error loading SMTP config: " . $e->getMessage() . "</p>";
        }
        
        echo '</div>';
        
        // Test 3: Test Registration Process
        if (isset($_POST['test_registration'])) {
            echo '<div class="debug-section">';
            echo '<h3>🧪 Registration Test Results</h3>';
            
            $testEmail = $_POST['test_email'] ?? 'test@example.com';
            $testPassword = $_POST['test_password'] ?? 'TestPass123';
            
            try {
                $auth = new Auth();
                
                echo "<p><strong>Testing registration for:</strong> $testEmail</p>";
                
                // Capture error logs
                ob_start();
                $result = $auth->register($testEmail, $testPassword);
                $output = ob_get_clean();
                
                if ($result['success']) {
                    echo '<div class="success">';
                    echo '<h4>✅ Registration Successful</h4>';
                    echo '<pre>' . json_encode($result, JSON_PRETTY_PRINT) . '</pre>';
                    echo '</div>';
                } else {
                    echo '<div class="error">';
                    echo '<h4>❌ Registration Failed</h4>';
                    echo '<pre>' . json_encode($result, JSON_PRETTY_PRINT) . '</pre>';
                    echo '</div>';
                }
                
                if ($output) {
                    echo '<div class="info">';
                    echo '<h4>📝 Debug Output</h4>';
                    echo '<pre>' . htmlspecialchars($output) . '</pre>';
                    echo '</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<h4>💥 Exception Occurred</h4>';
                echo '<p>' . $e->getMessage() . '</p>';
                echo '<pre>' . $e->getTraceAsString() . '</pre>';
                echo '</div>';
            }
            
            echo '</div>';
        }
        
        // Test 4: Direct Email Test
        if (isset($_POST['test_email_direct'])) {
            echo '<div class="debug-section">';
            echo '<h3>📨 Direct Email Test Results</h3>';
            
            $testEmail = $_POST['direct_email'] ?? 'support@michitai.com';
            
            try {
                $auth = new Auth();
                
                // Use reflection to access private method
                $reflection = new ReflectionClass($auth);
                $method = $reflection->getMethod('sendConfirmationEmail');
                $method->setAccessible(true);
                
                $testToken = bin2hex(random_bytes(32));
                echo "<p><strong>Testing direct email to:</strong> $testEmail</p>";
                echo "<p><strong>Test token:</strong> $testToken</p>";
                
                $result = $method->invoke($auth, $testEmail, $testToken);
                
                if ($result) {
                    echo '<div class="success">';
                    echo '<h4>✅ Email Sent Successfully</h4>';
                    echo '</div>';
                } else {
                    echo '<div class="error">';
                    echo '<h4>❌ Email Sending Failed</h4>';
                    echo '</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<h4>💥 Exception Occurred</h4>';
                echo '<p>' . $e->getMessage() . '</p>';
                echo '<pre>' . $e->getTraceAsString() . '</pre>';
                echo '</div>';
            }
            
            echo '</div>';
        }
        ?>
        
        <!-- Test Forms -->
        <div class="debug-section">
            <h3>🧪 Run Tests</h3>
            
            <form method="POST" style="margin-bottom: 20px;">
                <h4>Test Registration Process</h4>
                <p>
                    <label>Email: <input type="email" name="test_email" value="test<?php echo rand(1000,9999); ?>@example.com" style="width: 200px;"></label>
                </p>
                <p>
                    <label>Password: <input type="text" name="test_password" value="TestPass123" style="width: 200px;"></label>
                </p>
                <button type="submit" name="test_registration" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px;">Test Registration</button>
            </form>
            
            <form method="POST">
                <h4>Test Direct Email Sending</h4>
                <p>
                    <label>Email: <input type="email" name="direct_email" value="support@michitai.com" style="width: 200px;"></label>
                </p>
                <button type="submit" name="test_email_direct" style="padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 4px;">Test Direct Email</button>
            </form>
        </div>
        
        <div class="debug-section warning">
            <h3>⚠️ Important Notes</h3>
            <ul>
                <li>This script is for debugging only - remove from production</li>
                <li>Check your server error logs for additional debug information</li>
                <li>Verify your .env file has correct SMTP credentials</li>
                <li>Make sure the token_expires column exists in your database</li>
            </ul>
        </div>
    </div>
</body>
</html>
