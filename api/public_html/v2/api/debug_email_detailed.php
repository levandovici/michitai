<?php
/**
 * Detailed Email Debug - Compare working test vs Auth class
 */

// Enable error reporting and debug mode
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
    <title>Detailed Email Debug - Multiplayer API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        .side-by-side { display: flex; gap: 20px; }
        .side-by-side > div { flex: 1; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Detailed Email Debug Comparison</h1>
        
        <?php
        if (isset($_POST['test_both'])) {
            $testEmail = $_POST['test_email'] ?? 'nichitalnc@gmail.com';
            $testToken = bin2hex(random_bytes(32));
            
            echo '<div class="debug-section">';
            echo '<h3>📧 Side-by-Side Email Test Comparison</h3>';
            echo "<p><strong>Testing email to:</strong> $testEmail</p>";
            echo "<p><strong>Test token:</strong> $testToken</p>";
            
            echo '<div class="side-by-side">';
            
            // Test 1: Working test script method
            echo '<div>';
            echo '<h4>✅ Working Test Script Method</h4>';
            
            try {
                // Load config like the working test script
                $config = Database::loadEnv();
                
                $smtpHost = $config['SMTP_HOST'] ?? 'smtp.hostinger.com';
                $smtpPort = $config['SMTP_PORT'] ?? 465;
                $smtpUsername = $config['SMTP_USERNAME'] ?? '';
                $smtpPassword = $config['SMTP_PASSWORD'] ?? '';
                $fromEmail = $config['SMTP_FROM_EMAIL'] ?? 'support@michitai.com';
                $fromName = $config['SMTP_FROM_NAME'] ?? 'Multiplayer API Support';
                
                echo "<p><strong>Config loaded:</strong></p>";
                echo "<pre>";
                echo "Host: $smtpHost\n";
                echo "Port: $smtpPort\n";
                echo "Username: $smtpUsername\n";
                echo "Password: " . (empty($smtpPassword) ? 'EMPTY' : '[SET]') . "\n";
                echo "From: $fromEmail\n";
                echo "Name: $fromName\n";
                echo "</pre>";
                
                // Test SMTP connection like the working script
                $subject = "Test Email from Debug Script - " . date('Y-m-d H:i:s');
                $message = "This is a test email from the debug script.\n\nToken: $testToken\n\nIf you receive this, the working method is functioning.";
                
                $result = testSMTPSending($testEmail, $subject, $message, $smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $fromEmail, $fromName);
                
                if ($result['success']) {
                    echo '<div class="success">✅ Working method succeeded!</div>';
                } else {
                    echo '<div class="error">❌ Working method failed: ' . $result['error'] . '</div>';
                }
                
                if (!empty($result['logs'])) {
                    echo '<h5>Debug Logs:</h5>';
                    echo '<pre>' . implode("\n", $result['logs']) . '</pre>';
                }
                
            } catch (Exception $e) {
                echo '<div class="error">Exception: ' . $e->getMessage() . '</div>';
            }
            
            echo '</div>';
            
            // Test 2: Auth class method
            echo '<div>';
            echo '<h4>❌ Auth Class Method</h4>';
            
            try {
                $auth = new Auth();
                
                // Capture all output and errors
                ob_start();
                error_reporting(E_ALL);
                
                // Use reflection to access private sendConfirmationEmail method
                $reflection = new ReflectionClass($auth);
                $method = $reflection->getMethod('sendConfirmationEmail');
                $method->setAccessible(true);
                
                echo "<p><strong>Calling Auth->sendConfirmationEmail()...</strong></p>";
                
                $result = $method->invoke($auth, $testEmail, $testToken);
                
                $output = ob_get_clean();
                
                if ($result) {
                    echo '<div class="success">✅ Auth method succeeded!</div>';
                } else {
                    echo '<div class="error">❌ Auth method failed</div>';
                }
                
                if ($output) {
                    echo '<h5>Debug Output:</h5>';
                    echo '<pre>' . htmlspecialchars($output) . '</pre>';
                }
                
                // Also test the sendEmail method directly
                $sendEmailMethod = $reflection->getMethod('sendEmail');
                $sendEmailMethod->setAccessible(true);
                
                echo "<p><strong>Testing Auth->sendEmail() directly...</strong></p>";
                
                ob_start();
                $directResult = $sendEmailMethod->invoke($auth, $testEmail, "Direct Test", "Direct email test message");
                $directOutput = ob_get_clean();
                
                if ($directResult) {
                    echo '<div class="success">✅ Direct sendEmail succeeded!</div>';
                } else {
                    echo '<div class="error">❌ Direct sendEmail failed</div>';
                }
                
                if ($directOutput) {
                    echo '<h5>Direct sendEmail Output:</h5>';
                    echo '<pre>' . htmlspecialchars($directOutput) . '</pre>';
                }
                
            } catch (Exception $e) {
                echo '<div class="error">Exception: ' . $e->getMessage() . '</div>';
                echo '<pre>' . $e->getTraceAsString() . '</pre>';
            }
            
            echo '</div>';
            echo '</div>'; // End side-by-side
            echo '</div>'; // End debug section
        }
        
        // Helper function - same as working test script
        function testSMTPSending($to, $subject, $message, $host, $port, $username, $password, $fromEmail, $fromName) {
            $logs = [];
            
            try {
                $logs[] = "Starting SMTP test...";
                $logs[] = "SMTP Config: $host:$port, User: $username";
                
                // Determine SSL/TLS
                $useSSL = ($port == 465);
                $useTLS = ($port == 587);
                $logs[] = "Encryption: SSL=" . ($useSSL ? 'yes' : 'no') . ", TLS=" . ($useTLS ? 'yes' : 'no');
                
                // Create socket connection
                if ($useSSL) {
                    $context = stream_context_create([
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        ]
                    ]);
                    $socket = stream_socket_client("ssl://$host:$port", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
                } else {
                    $socket = fsockopen($host, $port, $errno, $errstr, 30);
                }
                
                if (!$socket) {
                    return ['success' => false, 'error' => "Connection failed: $errstr ($errno)", 'logs' => $logs];
                }
                
                $logs[] = "✅ Connected to SMTP server";
                
                // Server greeting
                $response = fgets($socket, 512);
                $logs[] = "Server greeting: " . trim($response);
                
                // EHLO
                fputs($socket, "EHLO $host\r\n");
                $response = fgets($socket, 512);
                $logs[] = "EHLO response: " . trim($response);
                
                // Read all EHLO capabilities first
                $capabilities = [];
                while (true) {
                    $line = fgets($socket, 512);
                    if (!$line || strpos($line, '250 ') === 0) {
                        $capabilities[] = trim($line);
                        break;
                    }
                    if (strpos($line, '250-') === 0) {
                        $capabilities[] = trim($line);
                    } else {
                        break;
                    }
                }
                
                $logs[] = "Server capabilities: " . implode(', ', $capabilities);
                
                // Check if AUTH is supported and extract methods
                $authSupported = false;
                $supportedMethods = [];
                foreach ($capabilities as $cap) {
                    if (strpos($cap, 'AUTH') !== false) {
                        $authSupported = true;
                        // Extract supported auth methods
                        if (preg_match('/AUTH\s+(.+)/', $cap, $matches)) {
                            $supportedMethods = explode(' ', trim($matches[1]));
                        }
                    }
                }
                
                if (!$authSupported) {
                    fclose($socket);
                    return ['success' => false, 'error' => 'Server does not support authentication', 'logs' => $logs];
                }
                
                $logs[] = "Supported auth methods: " . implode(', ', $supportedMethods);
                
                // Try authentication methods in order of preference
                $authenticated = false;
                
                // Try LOGIN first if supported
                if (in_array('LOGIN', $supportedMethods)) {
                    fputs($socket, "AUTH LOGIN\r\n");
                    $response = fgets($socket, 512);
                    $logs[] = "AUTH LOGIN response: " . trim($response);
                    
                    if (strpos($response, '334') === 0) {
                        fputs($socket, base64_encode($username) . "\r\n");
                        $response = fgets($socket, 512);
                        $logs[] = "Username response: " . trim($response);
                        
                        fputs($socket, base64_encode($password) . "\r\n");
                        $response = fgets($socket, 512);
                        $logs[] = "Password response: " . trim($response);
                        
                        if (strpos($response, '235') === 0) {
                            $authenticated = true;
                        }
                    }
                }
                
                // Try PLAIN if LOGIN failed or not supported
                if (!$authenticated && in_array('PLAIN', $supportedMethods)) {
                    $logs[] = "Trying AUTH PLAIN...";
                    $authString = base64_encode("\0" . $username . "\0" . $password);
                    fputs($socket, "AUTH PLAIN $authString\r\n");
                    $response = fgets($socket, 512);
                    $logs[] = "AUTH PLAIN response: " . trim($response);
                    
                    if (strpos($response, '235') === 0) {
                        $authenticated = true;
                    }
                }
                
                if (!$authenticated) {
                    fclose($socket);
                    return ['success' => false, 'error' => 'All authentication methods failed', 'logs' => $logs];
                }
                
                $logs[] = "✅ Authentication successful";
                
                // Send email
                fputs($socket, "MAIL FROM: <$fromEmail>\r\n");
                $response = fgets($socket, 512);
                $logs[] = "MAIL FROM response: " . trim($response);
                
                fputs($socket, "RCPT TO: <$to>\r\n");
                $response = fgets($socket, 512);
                $logs[] = "RCPT TO response: " . trim($response);
                
                fputs($socket, "DATA\r\n");
                $response = fgets($socket, 512);
                $logs[] = "DATA response: " . trim($response);
                
                $emailData = "From: $fromName <$fromEmail>\r\n";
                $emailData .= "To: $to\r\n";
                $emailData .= "Subject: $subject\r\n";
                $emailData .= "MIME-Version: 1.0\r\n";
                $emailData .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $emailData .= "\r\n";
                $emailData .= $message;
                $emailData .= "\r\n.\r\n";
                
                fputs($socket, $emailData);
                $response = fgets($socket, 512);
                $logs[] = "Email data response: " . trim($response);
                
                fputs($socket, "QUIT\r\n");
                $response = fgets($socket, 512);
                $logs[] = "QUIT response: " . trim($response);
                
                fclose($socket);
                
                $logs[] = "✅ Email sent successfully!";
                return ['success' => true, 'logs' => $logs];
                
            } catch (Exception $e) {
                if (isset($socket) && $socket) {
                    fclose($socket);
                }
                return ['success' => false, 'error' => $e->getMessage(), 'logs' => $logs];
            }
        }
        ?>
        
        <!-- Test Form -->
        <div class="debug-section">
            <h3>🧪 Run Detailed Comparison Test</h3>
            <form method="POST">
                <p>
                    <label>Email: <input type="email" name="test_email" value="nichitalnc@gmail.com" style="width: 300px;"></label>
                </p>
                <button type="submit" name="test_both" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px;">Test Both Methods Side-by-Side</button>
            </form>
        </div>
        
        <div class="debug-section warning">
            <h3>🎯 What This Test Will Show</h3>
            <ul>
                <li><strong>Working Method:</strong> Uses the same SMTP code as the successful test_email.php</li>
                <li><strong>Auth Class Method:</strong> Uses the Auth class sendConfirmationEmail method</li>
                <li><strong>Comparison:</strong> Shows exactly where the difference is</li>
                <li><strong>Debug Logs:</strong> Complete SMTP conversation for both methods</li>
            </ul>
        </div>
    </div>
</body>
</html>
