<?php
/**
 * Email Sending Test Script
 * Tests Hostinger SMTP configuration and email sending functionality
 */

// Enable error reporting and debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set debug mode
define('DEBUG_MODE', true);

echo "<!DOCTYPE html><html><head><title>Email Test - Multiplayer API</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;}";
echo ".container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}";
echo ".success{color:#28a745;background:#d4edda;padding:10px;border-radius:4px;margin:10px 0;}";
echo ".error{color:#dc3545;background:#f8d7da;padding:10px;border-radius:4px;margin:10px 0;}";
echo ".info{color:#0c5460;background:#d1ecf1;padding:10px;border-radius:4px;margin:10px 0;}";
echo ".log{background:#f8f9fa;padding:10px;border-left:4px solid #007bff;margin:10px 0;font-family:monospace;white-space:pre-wrap;}";
echo "</style></head><body>";

echo "<div class='container'>";
echo "<h1>📧 Email Sending Test - Multiplayer API</h1>";
echo "<p>Testing Hostinger SMTP configuration and email sending functionality...</p>";

try {
    // Include required files
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/ErrorCodes.php';
    
    echo "<div class='success'>✅ Required files loaded successfully</div>";
    
    // Load environment configuration
    $config = Database::loadEnv();
    
    echo "<div class='info'>📋 Environment Configuration Loaded:</div>";
    echo "<div class='log'>";
    echo "SMTP_HOST: " . ($config['SMTP_HOST'] ?? 'NOT SET') . "\n";
    echo "SMTP_PORT: " . ($config['SMTP_PORT'] ?? 'NOT SET') . "\n";
    echo "SMTP_USERNAME: " . ($config['SMTP_USERNAME'] ?? 'NOT SET') . "\n";
    echo "SMTP_PASSWORD: " . (isset($config['SMTP_PASSWORD']) && !empty($config['SMTP_PASSWORD']) ? '[SET]' : '[NOT SET]') . "\n";
    echo "SMTP_FROM_EMAIL: " . ($config['SMTP_FROM_EMAIL'] ?? 'NOT SET') . "\n";
    echo "SMTP_FROM_NAME: " . ($config['SMTP_FROM_NAME'] ?? 'NOT SET') . "\n";
    echo "</div>";
    
    // Check if SMTP is configured
    if (empty($config['SMTP_USERNAME']) || empty($config['SMTP_PASSWORD'])) {
        echo "<div class='error'>❌ SMTP credentials not configured in .env file</div>";
        echo "<p>Please configure your SMTP settings in <code>public_html/api/.env</code></p>";
        exit;
    }
    
    // Get test email address
    $testEmail = $_GET['email'] ?? $config['SMTP_USERNAME'] ?? 'test@example.com';
    
    echo "<div class='info'>🎯 Test Email Address: $testEmail</div>";
    
    if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        echo "<div class='error'>❌ Invalid email address format</div>";
        exit;
    }
    
    // Important note about email account
    echo "<div class='info'>⚠️ <strong>Important:</strong> Make sure the email account <code>" . htmlspecialchars($config['SMTP_USERNAME']) . "</code> exists in your Hostinger email panel and the password is correct.</div>";
    
    // Test email content
    $subject = "Test Email from Multiplayer API - " . date('Y-m-d H:i:s');
    $message = "Hello!\n\n";
    $message .= "This is a test email from your Multiplayer API system.\n\n";
    $message .= "If you received this email, your SMTP configuration is working correctly!\n\n";
    $message .= "Test Details:\n";
    $message .= "- Sent at: " . date('Y-m-d H:i:s T') . "\n";
    $message .= "- SMTP Host: " . $config['SMTP_HOST'] . "\n";
    $message .= "- SMTP Port: " . $config['SMTP_PORT'] . "\n";
    $message .= "- From: " . $config['SMTP_FROM_EMAIL'] . "\n\n";
    $message .= "Best regards,\n";
    $message .= "Multiplayer API System";
    
    echo "<div class='info'>📝 Email Content:</div>";
    echo "<div class='log'>";
    echo "Subject: $subject\n";
    echo "To: $testEmail\n";
    echo "From: " . $config['SMTP_FROM_NAME'] . " <" . $config['SMTP_FROM_EMAIL'] . ">\n\n";
    echo "Message:\n" . $message;
    echo "</div>";
    
    // Test SMTP connection and sending
    echo "<div class='info'>🔄 Testing SMTP Connection...</div>";
    
    $result = testSMTPEmail($testEmail, $subject, $message, $config);
    
    if ($result['success']) {
        echo "<div class='success'>✅ Email sent successfully!</div>";
        echo "<div class='info'>Check your inbox at: $testEmail</div>";
    } else {
        echo "<div class='error'>❌ Email sending failed: " . $result['error'] . "</div>";
    }
    
    // Show debug logs
    if (!empty($result['logs'])) {
        echo "<div class='info'>🔍 Debug Logs:</div>";
        echo "<div class='log'>" . implode("\n", $result['logs']) . "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='log'>Stack trace:\n" . htmlspecialchars($e->getTraceAsString()) . "</div>";
}

echo "<hr>";
echo "<h3>📋 Test Options</h3>";
echo "<p>Test with different email address:</p>";
echo "<form method='GET'>";
echo "<input type='email' name='email' placeholder='test@example.com' value='" . htmlspecialchars($testEmail) . "' style='padding:8px;width:300px;'>";
echo "<button type='submit' style='padding:8px 16px;background:#007bff;color:white;border:none;border-radius:4px;cursor:pointer;'>Send Test Email</button>";
echo "</form>";

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>If email sending works, your registration emails should work too</li>";
echo "<li>If it fails, check your .env SMTP configuration</li>";
echo "<li>Make sure your Hostinger email account exists and password is correct</li>";
echo "<li>Check spam/junk folder if you don't see the email</li>";
echo "</ul>";

echo "</div></body></html>";

/**
 * Test SMTP email sending function
 */
function testSMTPEmail($to, $subject, $message, $config) {
    $logs = [];
    $logs[] = "Starting SMTP test...";
    
    try {
        $smtpHost = $config['SMTP_HOST'];
        $smtpPort = (int)$config['SMTP_PORT'];
        $smtpUsername = $config['SMTP_USERNAME'];
        $smtpPassword = $config['SMTP_PASSWORD'];
        $fromEmail = $config['SMTP_FROM_EMAIL'];
        $fromName = $config['SMTP_FROM_NAME'];
        
        $logs[] = "SMTP Config: $smtpHost:$smtpPort, User: $smtpUsername";
        
        // Determine encryption type
        $useSSL = ($smtpPort == 465);
        $useTLS = ($smtpPort == 587);
        
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
            $socket = stream_socket_client("ssl://$smtpHost:$smtpPort", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        } else {
            $socket = fsockopen($smtpHost, $smtpPort, $errno, $errstr, 30);
        }
        
        if (!$socket) {
            return ['success' => false, 'error' => "Connection failed: $errstr ($errno)", 'logs' => $logs];
        }
        
        $logs[] = "✅ Connected to SMTP server";
        
        // Read greeting
        $response = fgets($socket, 512);
        $logs[] = "Server greeting: " . trim($response);
        
        // EHLO and read all capabilities
        fputs($socket, "EHLO $smtpHost\r\n");
        $response = fgets($socket, 512);
        $logs[] = "EHLO response: " . trim($response);
        
        // Read all EHLO capabilities
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
        
        // Check if AUTH is supported
        $authSupported = false;
        $supportedAuthMethods = [];
        foreach ($capabilities as $cap) {
            if (strpos($cap, 'AUTH') !== false) {
                $authSupported = true;
                $supportedAuthMethods[] = $cap;
            }
        }
        
        if (!$authSupported) {
            fclose($socket);
            return ['success' => false, 'error' => 'Server does not support authentication', 'logs' => $logs];
        }
        
        $logs[] = "Authentication methods: " . implode(', ', $supportedAuthMethods);
        
        // STARTTLS if needed
        if ($useTLS && !$useSSL) {
            fputs($socket, "STARTTLS\r\n");
            $response = fgets($socket, 512);
            $logs[] = "STARTTLS response: " . trim($response);
            
            if (strpos($response, '220') === 0) {
                $cryptoResult = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if (!$cryptoResult) {
                    fclose($socket);
                    return ['success' => false, 'error' => 'TLS encryption failed', 'logs' => $logs];
                }
                $logs[] = "✅ TLS encryption enabled";
                
                // EHLO again after TLS
                fputs($socket, "EHLO $smtpHost\r\n");
                $response = fgets($socket, 512);
                $logs[] = "EHLO after TLS: " . trim($response);
            }
        }
        
        // Authentication - try AUTH LOGIN first
        fputs($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 512);
        $logs[] = "AUTH LOGIN response: " . trim($response);
        
        // Check if server supports AUTH LOGIN (should respond with 334)
        if (strpos($response, '334') === 0) {
            // Server supports AUTH LOGIN, proceed with base64 credentials
            fputs($socket, base64_encode($smtpUsername) . "\r\n");
            $response = fgets($socket, 512);
            $logs[] = "Username response: " . trim($response);
            
            fputs($socket, base64_encode($smtpPassword) . "\r\n");
            $response = fgets($socket, 512);
            $logs[] = "Password response: " . trim($response);
            
            if (strpos($response, '235') !== 0) {
                fclose($socket);
                return ['success' => false, 'error' => 'Authentication failed: ' . trim($response), 'logs' => $logs];
            }
        } else {
            // Try AUTH PLAIN instead
            $logs[] = "AUTH LOGIN not supported, trying AUTH PLAIN...";
            $authString = base64_encode("\0" . $smtpUsername . "\0" . $smtpPassword);
            fputs($socket, "AUTH PLAIN $authString\r\n");
            $response = fgets($socket, 512);
            $logs[] = "AUTH PLAIN response: " . trim($response);
            
            if (strpos($response, '235') !== 0) {
                fclose($socket);
                return ['success' => false, 'error' => 'Authentication failed with both LOGIN and PLAIN: ' . trim($response), 'logs' => $logs];
            }
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
        
        // Email content
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
        
        // Quit
        fputs($socket, "QUIT\r\n");
        $response = fgets($socket, 512);
        $logs[] = "QUIT response: " . trim($response);
        
        fclose($socket);
        
        $logs[] = "✅ Email sent successfully!";
        
        return ['success' => true, 'logs' => $logs];
        
    } catch (Exception $e) {
        $logs[] = "❌ Exception: " . $e->getMessage();
        return ['success' => false, 'error' => $e->getMessage(), 'logs' => $logs];
    }
}
?>
