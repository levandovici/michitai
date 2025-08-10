<?php
/**
 * Authentication Class with Professional Debugging
 * Handles user registration, login, and token management
 */

require_once __DIR__ . '/../config/ErrorCodes.php';
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    private $debug;

    public function __construct() {
        $this->debug = defined('DEBUG_MODE') && DEBUG_MODE;
        $this->initializeDatabase();
    }

    private function initializeDatabase() {
        try {
            // Use Database class for MySQL connection with SQLite fallback
            try {
                $database = Database::getInstance();
                $this->db = $database->getConnection();
                
                if ($this->debug) {
                    error_log("Auth: MySQL database connected successfully");
                }
            } catch (Exception $e) {
                // Fallback to SQLite for development
                if ($this->debug) {
                    error_log("Auth: MySQL failed, using SQLite fallback - " . $e->getMessage());
                }
                
                $dbPath = __DIR__ . '/../data/multiplayer_api.db';
                $dbDir = dirname($dbPath);
                
                if (!is_dir($dbDir)) {
                    mkdir($dbDir, 0755, true);
                }

                $this->db = new PDO("sqlite:$dbPath");
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            
            // Create tables if they don't exist
            $this->createTables();
            
            if ($this->debug) {
                error_log("Auth: Database initialized successfully");
            }
        } catch (Exception $e) {
            ErrorCodes::logError(ErrorCodes::DB_CONNECTION_FAILED, ['error' => $e->getMessage()], $e);
            throw new Exception("Database initialization failed: " . $e->getMessage());
        }
    }

    private function createTables() {
        try {
            // Detect database type
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            
            if ($driver === 'mysql') {
                // MySQL table creation
                $sql = "
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    api_token VARCHAR(255) UNIQUE,
                    email_verified TINYINT(1) DEFAULT 0,
                    verification_token VARCHAR(255),
                    reset_token VARCHAR(255),
                    reset_token_expires INT,
                    plan_type VARCHAR(50) DEFAULT 'free',
                    api_calls_used INT DEFAULT 0,
                    api_calls_limit INT DEFAULT 1000,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_api_token (api_token)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS api_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    endpoint VARCHAR(255),
                    method VARCHAR(10),
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    response_code INT,
                    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                    INDEX idx_user_id (user_id),
                    INDEX idx_endpoint (endpoint)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ";
            } else {
                // SQLite table creation
                $sql = "
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    email TEXT UNIQUE NOT NULL,
                    password_hash TEXT NOT NULL,
                    api_token TEXT UNIQUE,
                    email_verified INTEGER DEFAULT 0,
                    verification_token TEXT,
                    reset_token TEXT,
                    reset_token_expires INTEGER,
                    plan_type TEXT DEFAULT 'free',
                    api_calls_used INTEGER DEFAULT 0,
                    api_calls_limit INTEGER DEFAULT 1000,
                    created_at INTEGER DEFAULT (strftime('%s', 'now')),
                    updated_at INTEGER DEFAULT (strftime('%s', 'now'))
                );

                CREATE TABLE IF NOT EXISTS api_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER,
                    endpoint TEXT,
                    method TEXT,
                    ip_address TEXT,
                    user_agent TEXT,
                    response_code INTEGER,
                    timestamp INTEGER DEFAULT (strftime('%s', 'now')),
                    FOREIGN KEY (user_id) REFERENCES users(id)
                );
                ";
            }

            // Execute each statement separately
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $this->db->exec($statement);
                }
            }
            
            if ($this->debug) {
                error_log("Auth: Tables created successfully using $driver");
            }
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("Auth: Table creation failed - " . $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Register a new user with comprehensive validation and debugging
     */
    public function register($email, $password, $newsletter = false) {
        try {
            // Debug point 1: Input validation
            if ($this->debug) {
                error_log("Auth::register - Starting registration for email: $email");
            }

            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                if ($this->debug) {
                    error_log("Auth::register - Invalid email format: $email");
                }
                return ErrorCodes::createErrorResponse(ErrorCodes::REG_INVALID_EMAIL);
            }

            // Validate password strength
            if (!$this->isPasswordStrong($password)) {
                if ($this->debug) {
                    error_log("Auth::register - Password too weak for email: $email");
                }
                return ErrorCodes::createErrorResponse(ErrorCodes::REG_PASSWORD_REQUIREMENTS);
            }

            // Debug point 2: Check if email exists
            if ($this->debug) {
                error_log("Auth::register - Checking if email exists: $email");
            }

            $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                if ($this->debug) {
                    error_log("Auth::register - Email already exists: $email");
                }
                return ErrorCodes::createErrorResponse(ErrorCodes::REG_EMAIL_EXISTS);
            }

            // Debug point 3: Create user account
            if ($this->debug) {
                error_log("Auth::register - Creating new user account for: $email");
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $apiToken = $this->generateApiToken();
            $verificationToken = $this->generateVerificationToken();

            $stmt = $this->db->prepare("
                INSERT INTO users (email, password, api_token, verification_token, token_expires, email_verified, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
        
            $timestamp = time();
            $tokenExpires = $timestamp + (24 * 60 * 60); // 24 hours from now
            $result = $stmt->execute([
                $email, 
                $passwordHash, 
                $apiToken, 
                $verificationToken,
                $tokenExpires,
                0, // email_verified = false
                $timestamp, 
                $timestamp
            ]);

            if (!$result) {
                if ($this->debug) {
                    error_log("Auth::register - Database insert failed for: $email");
                }
                return ErrorCodes::createErrorResponse(ErrorCodes::REG_DATABASE_ERROR);
            }

            $userId = $this->db->lastInsertId();

            // Debug point 4: Send verification email (production implementation)
            if ($this->debug) {
                error_log("Auth::register - Sending verification email to: $email");
                error_log("Auth::register - Verification token: $verificationToken");
            }

            // Send real verification email
            $emailSent = $this->sendVerificationEmail($email, $verificationToken);
            
            if (!$emailSent) {
                if ($this->debug) {
                    error_log("Auth::register - ERROR: Verification email failed for: $email");
                }
                // Don't fail registration if email fails, but log the error
                ErrorCodes::logError(ErrorCodes::SYS_EMAIL_SEND_FAILED, [
                    'email' => $email,
                    'function' => 'register',
                    'token' => $verificationToken
                ]);
            } else {
                if ($this->debug) {
                    error_log("Auth::register - SUCCESS: Verification email sent to: $email");
                }
            }

            // Debug point 5: Success response
            if ($this->debug) {
                error_log("Auth::register - Registration successful for: $email, User ID: $userId");
            }

            return ErrorCodes::createSuccessResponse([
                'user_id' => $userId,
                'email' => $email,
                'api_token' => $apiToken,
                'email_verified' => false,
                'verification_required' => true
            ], 'Registration successful. Please check your email to verify your account.');

        } catch (Exception $e) {
            ErrorCodes::logError(ErrorCodes::SYS_INTERNAL_ERROR, [
                'email' => $email,
                'function' => 'register'
            ], $e);

            if ($this->debug) {
                error_log("Auth::register - Exception: " . $e->getMessage());
            }

            return ErrorCodes::createErrorResponse(ErrorCodes::SYS_INTERNAL_ERROR, null, 
                $this->debug ? $e->getMessage() : null);
        }
    }

    /**
     * Login user with debugging
     */
    public function login($email, $password) {
        try {
            if ($this->debug) {
                error_log("Auth::login - Login attempt for email: $email");
            }

            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                if ($this->debug) {
                    error_log("Auth::login - User not found: $email");
                }
                return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_INVALID_CREDENTIALS);
            }

            if (!password_verify($password, $user['password'])) {
                if ($this->debug) {
                    error_log("Auth::login - Invalid password for: $email");
                }
                return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_INVALID_CREDENTIALS);
            }

            // Generate session token for frontend (not API token)
            $sessionToken = $this->generateApiToken();
            
            // Update last login time only
            $stmt = $this->db->prepare("UPDATE users SET updated_at = ? WHERE user_id = ?");
            $stmt->execute([time(), $user['user_id']]);

            if ($this->debug) {
                error_log("Auth::login - Login successful for: $email");
            }

            return ErrorCodes::createSuccessResponse([
                'user_id' => $user['user_id'],
                'email' => $user['email'],
                'session_token' => $sessionToken, // Session token for frontend
                'api_token' => $user['api_token'], // API token for game operations
                'email_verified' => isset($user['email_verified']) ? (bool)$user['email_verified'] : false,
                'plan_type' => $user['plan_type'] ?? 'free'
            ], 'Login successful');

        } catch (Exception $e) {
            ErrorCodes::logError(ErrorCodes::SYS_INTERNAL_ERROR, [
                'email' => $email,
                'function' => 'login'
            ], $e);

            return ErrorCodes::createErrorResponse(ErrorCodes::SYS_INTERNAL_ERROR);
        }
    }

    /**
     * Validate API token
     */
    public function validateToken($token) {
        if (!$token) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("SELECT user_id, email FROM users WHERE api_token = ?");
            $stmt->execute([$token]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("Auth::validateToken - Error: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Log API call for rate limiting and analytics
     */
    public function logApiCall($token, $endpoint) {
        try {
            $user = $this->validateToken($token);
            $userId = $user ? $user['id'] : null;

            $stmt = $this->db->prepare("
                INSERT INTO api_logs (user_id, endpoint, method, ip_address, user_agent, timestamp) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $endpoint,
                $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                time()
            ]);

            if ($this->debug) {
                error_log("Auth::logApiCall - Logged API call: $endpoint for user: " . ($userId ?? 'anonymous'));
            }

        } catch (Exception $e) {
            if ($this->debug) {
                error_log("Auth::logApiCall - Error: " . $e->getMessage());
            }
        }
    }

    /**
     * Check password strength
     */
    private function isPasswordStrong($password) {
        return strlen($password) >= 8 && 
               preg_match('/[A-Z]/', $password) && 
               preg_match('/[a-z]/', $password) && 
               preg_match('/[0-9]/', $password);
    }

    /**
     * Generate secure API token
     */
    private function generateApiToken() {
        return 'mapi_' . bin2hex(random_bytes(32));
    }

    /**
     * Generate verification token
     */
    private function generateVerificationToken() {
        return bin2hex(random_bytes(32));
    }

/**
 * Get user profile by token
 */
public function getUserProfile($token) {
    try {
        $user = $this->validateToken($token);
        if (!$user) {
            return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_INVALID_TOKEN);
        }

        // Get full user data
        $stmt = $this->db->prepare("SELECT user_id, email, plan_type, api_calls_today, created_at FROM users WHERE api_token = ?");
        $stmt->execute([$token]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($this->debug) {
            error_log("Auth::getUserProfile - Retrieved profile for: " . $userData['email']);
        }

        return ErrorCodes::createSuccessResponse([
            'user_id' => $userData['user_id'],
            'email' => $userData['email'],
            'email_verified' => false, // Not available in current schema
            'plan_type' => $userData['plan_type'],
            'api_calls_used' => (int)$userData['api_calls_today'],
            'api_calls_limit' => 1000, // Default limit since not in schema
            'created_at' => $userData['created_at']
        ], 'User profile retrieved successfully');

    } catch (Exception $e) {
        ErrorCodes::logError(ErrorCodes::SYS_INTERNAL_ERROR, [
            'function' => 'getUserProfile'
        ], $e);

        return ErrorCodes::createErrorResponse(ErrorCodes::SYS_INTERNAL_ERROR);
    }
}


/**
 * Send verification email (production implementation)
 */
private function sendVerificationEmail($email, $token) {
    if ($this->debug) {
        error_log("Auth::sendVerificationEmail - Sending verification email to: $email");
        error_log("Auth::sendVerificationEmail - Verification token: $token");
    }
    
    // Use the real email sending method
    return $this->sendConfirmationEmail($email, $token);
}

/**
 * Send confirmation email
 */
private function sendConfirmationEmail($email, $token) {
    $subject = "Confirm Your Multiplayer API Account";
    $confirmUrl = "https://api.michitai.com/confirm-email.html?token=" . urlencode($token);
    
    $message = "
    Welcome to Multiplayer API!
    
    Please confirm your email address by clicking the link below:
    $confirmUrl
    
    If you didn't create this account, please ignore this email.
    
    Best regards,
    Multiplayer API Team
    ";

    return $this->sendEmail($email, $subject, $message);
}

/**
 * Send email using SMTP (production implementation)
 */
private function sendEmail($to, $subject, $message) {
    if ($this->debug) {
        error_log("Auth: Attempting to send email to $to - Subject: $subject");
    }
    
    try {
        // Get SMTP configuration from environment using Database config loader
        require_once __DIR__ . '/../config/database.php';
        $config = Database::loadEnv();
        
        $smtpHost = $config['SMTP_HOST'] ?? 'localhost';
        $smtpPort = $config['SMTP_PORT'] ?? 465; // Hostinger default SSL port
        $smtpUsername = $config['SMTP_USERNAME'] ?? '';
        $smtpPassword = $config['SMTP_PASSWORD'] ?? '';
        $fromEmail = $config['SMTP_FROM_EMAIL'] ?? 'noreply@michitai.com';
        $fromName = $config['SMTP_FROM_NAME'] ?? 'Multiplayer API';
        
        if ($this->debug) {
            error_log("Auth: SMTP Config - Host: $smtpHost, Port: $smtpPort, Username: $smtpUsername, From: $fromEmail");
        }
        
        // If SMTP credentials are not configured, fall back to PHP mail()
        if (empty($smtpUsername) || empty($smtpPassword)) {
            if ($this->debug) {
                error_log("Auth: SMTP not configured, using PHP mail() function");
            }
            return $this->sendEmailWithPHPMail($to, $subject, $message, $fromEmail, $fromName);
        }
        
        // Use SMTP with authentication
        return $this->sendEmailWithSMTP($to, $subject, $message, $smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $fromEmail, $fromName);
        
    } catch (Exception $e) {
        if ($this->debug) {
            error_log("Auth: Email sending failed - " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Send email using PHP's built-in mail() function
 */
private function sendEmailWithPHPMail($to, $subject, $message, $fromEmail, $fromName) {
    try {
        // Set headers
        $headers = [
            'From' => "$fromName <$fromEmail>",
            'Reply-To' => $fromEmail,
            'X-Mailer' => 'PHP/' . phpversion(),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit'
        ];
        
        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= "$key: $value\r\n";
        }
        
        // Send email
        $result = mail($to, $subject, $message, $headerString);
        
        if ($this->debug) {
            error_log("Auth: PHP mail() result: " . ($result ? 'success' : 'failed'));
        }
        
        return $result;
        
    } catch (Exception $e) {
        if ($this->debug) {
            error_log("Auth: PHP mail() exception - " . $e->getMessage());
        }
        return false;
    }
}

/**
 * Send email using SMTP with authentication (Hostinger compatible)
 */
private function sendEmailWithSMTP($to, $subject, $message, $host, $port, $username, $password, $fromEmail, $fromName) {
    try {
        // Determine if we should use SSL or TLS based on port
        $useSSL = ($port == 465);
        $useTLS = ($port == 587);
        
        if ($this->debug) {
            error_log("Auth: SMTP connecting to $host:$port (SSL: " . ($useSSL ? 'yes' : 'no') . ", TLS: " . ($useTLS ? 'yes' : 'no') . ")");
        }
        
        // Create socket connection - use SSL context if port 465
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
            if ($this->debug) {
                error_log("Auth: SMTP connection failed - $errstr ($errno)");
            }
            return false;
        }
        
        // Read server greeting
        $response = fgets($socket, 512);
        if ($this->debug) {
            error_log("Auth: SMTP greeting - " . trim($response));
        }
        
        // Send EHLO command
        fputs($socket, "EHLO $host\r\n");
        $response = fgets($socket, 512);
        if ($this->debug) {
            error_log("Auth: EHLO response - " . trim($response));
        }
        
        // Start TLS if port 587 and not already using SSL
        if ($useTLS && !$useSSL) {
            fputs($socket, "STARTTLS\r\n");
            $response = fgets($socket, 512);
            if ($this->debug) {
                error_log("Auth: STARTTLS response - " . trim($response));
            }
            
            if (strpos($response, '220') === 0) {
                // Enable crypto
                $cryptoResult = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if (!$cryptoResult) {
                    if ($this->debug) {
                        error_log("Auth: TLS encryption failed");
                    }
                    fclose($socket);
                    return false;
                }
                
                // Send EHLO again after TLS
                fputs($socket, "EHLO $host\r\n");
                $response = fgets($socket, 512);
                if ($this->debug) {
                    error_log("Auth: EHLO after TLS response - " . trim($response));
                }
            }
        }
        
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
        
        if ($this->debug) {
            error_log("Auth: Server capabilities - " . implode(', ', $capabilities));
        }
        
        // Check if AUTH is supported and what methods
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
            if ($this->debug) {
                error_log("Auth: Server does not support authentication");
            }
            fclose($socket);
            return false;
        }
        
        if ($this->debug) {
            error_log("Auth: Supported auth methods - " . implode(', ', $supportedMethods));
        }
        
        // Try authentication methods in order of preference
        $authenticated = false;
        
        // Try LOGIN first if supported
        if (in_array('LOGIN', $supportedMethods)) {
            fputs($socket, "AUTH LOGIN\r\n");
            $response = fgets($socket, 512);
            if ($this->debug) {
                error_log("Auth: AUTH LOGIN response - " . trim($response));
            }
            
            if (strpos($response, '334') === 0) {
                fputs($socket, base64_encode($username) . "\r\n");
                $response = fgets($socket, 512);
                if ($this->debug) {
                    error_log("Auth: Username response - " . trim($response));
                }
                
                fputs($socket, base64_encode($password) . "\r\n");
                $response = fgets($socket, 512);
                if ($this->debug) {
                    error_log("Auth: Password response - " . trim($response));
                }
                
                if (strpos($response, '235') === 0) {
                    $authenticated = true;
                }
            }
        }
        
        // Try PLAIN if LOGIN failed or not supported
        if (!$authenticated && in_array('PLAIN', $supportedMethods)) {
            if ($this->debug) {
                error_log("Auth: Trying AUTH PLAIN...");
            }
            $authString = base64_encode("\0" . $username . "\0" . $password);
            fputs($socket, "AUTH PLAIN $authString\r\n");
            $response = fgets($socket, 512);
            if ($this->debug) {
                error_log("Auth: AUTH PLAIN response - " . trim($response));
            }
            
            if (strpos($response, '235') === 0) {
                $authenticated = true;
            }
        }
        
        if (!$authenticated) {
            if ($this->debug) {
                error_log("Auth: All authentication methods failed");
            }
            fclose($socket);
            return false;
        }
        
        // Send email
        fputs($socket, "MAIL FROM: <$fromEmail>\r\n");
        $response = fgets($socket, 512);
        
        fputs($socket, "RCPT TO: <$to>\r\n");
        $response = fgets($socket, 512);
        
        fputs($socket, "DATA\r\n");
        $response = fgets($socket, 512);
        
        // Email headers and body
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
        
        // Quit
        fputs($socket, "QUIT\r\n");
        $response = fgets($socket, 512);
        
        fclose($socket);
        
        if ($this->debug) {
            error_log("Auth: SMTP email sent successfully to $to");
        }
        
        return true;
        
    } catch (Exception $e) {
        if ($this->debug) {
            error_log("Auth: SMTP sending failed - " . $e->getMessage());
        }
        
        if (isset($socket) && $socket) {
            fclose($socket);
        }
        
        return false;
    }
}

    /**
     * Confirm email address using verification token
     */
    public function confirmEmail($token) {
        try {
            if ($this->debug) {
                error_log("Auth::confirmEmail - Confirming email with token: $token");
            }

            if (empty($token)) {
                if ($this->debug) {
                    error_log("Auth::confirmEmail - No token provided");
                }
                return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_MISSING_PARAMETERS, null, 'No confirmation token provided');
            }

            // Find user by verification token
            $stmt = $this->db->prepare("SELECT user_id, email, email_verified, token_expires FROM users WHERE verification_token = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                if ($this->debug) {
                    error_log("Auth::confirmEmail - Invalid token: $token");
                }
                return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_INVALID_TOKEN, null, 'Invalid or expired confirmation token');
            }

            // Check if token has expired (24 hours)
            $currentTime = time();
            if ($user['token_expires'] && $currentTime > $user['token_expires']) {
                if ($this->debug) {
                    error_log("Auth::confirmEmail - Token expired for user: " . $user['email'] . ", expired at: " . date('Y-m-d H:i:s', $user['token_expires']));
                }
                return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_INVALID_TOKEN, null, 'The confirmation link has expired (valid for 24 hours). Please request a new confirmation email.');
            }

            if ($user['email_verified']) {
                if ($this->debug) {
                    error_log("Auth::confirmEmail - Email already verified for user: " . $user['email']);
                }
                return ErrorCodes::createErrorResponse(ErrorCodes::REG_EMAIL_ALREADY_VERIFIED, null, 'Email is already verified');
            }

            // Mark email as verified and clear verification token
            $stmt = $this->db->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, token_expires = NULL, updated_at = ? WHERE user_id = ?");
            $result = $stmt->execute([time(), $user['user_id']]);

            if (!$result) {
                if ($this->debug) {
                    error_log("Auth::confirmEmail - Database update failed for user: " . $user['email']);
                }
                return ErrorCodes::createErrorResponse(ErrorCodes::SYS_DATABASE_ERROR, null, 'Failed to update verification status');
            }

            if ($this->debug) {
                error_log("Auth::confirmEmail - Email verified successfully for: " . $user['email']);
            }

            return ErrorCodes::createSuccessResponse([
                'email' => $user['email'],
                'verified' => true
            ], 'Email verified successfully');

        } catch (Exception $e) {
            ErrorCodes::logError(ErrorCodes::SYS_INTERNAL_ERROR, [
                'token' => $token,
                'function' => 'confirmEmail'
            ], $e);

            if ($this->debug) {
                error_log("Auth::confirmEmail - Exception: " . $e->getMessage());
            }

            return ErrorCodes::createErrorResponse(ErrorCodes::SYS_INTERNAL_ERROR, null, 
                $this->debug ? $e->getMessage() : null);
        }
    }
    /**
     * Resend confirmation email
     */
    public function resendConfirmationEmail($email) {
        try {
            if ($this->debug) {
                error_log("Auth::resendConfirmationEmail - Resending confirmation for: $email");
            }

            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ErrorCodes::createErrorResponse(ErrorCodes::REG_INVALID_EMAIL, null, 'Invalid email address');
            }

            // Check if user exists and get current verification status
            $stmt = $this->db->prepare("SELECT user_id, email_verified FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_USER_NOT_FOUND, null, 'User not found');
            }

            // Check if email is already verified
            if ($user['email_verified']) {
                if ($this->debug) {
                    error_log("Auth::resendConfirmationEmail - Email already verified for: $email");
                }
                return ErrorCodes::createErrorResponse(ErrorCodes::REG_EMAIL_ALREADY_VERIFIED, null, 'Email address is already verified');
            }

            // Generate new verification token
            $verificationToken = $this->generateVerificationToken();
            $tokenExpires = time() + (24 * 60 * 60); // 24 hours from now

            // Update verification token in database
            $stmt = $this->db->prepare("UPDATE users SET verification_token = ?, token_expires = ?, updated_at = ? WHERE user_id = ?");
            $result = $stmt->execute([$verificationToken, $tokenExpires, time(), $user['user_id']]);

            if (!$result) {
                if ($this->debug) {
                    error_log("Auth::resendConfirmationEmail - Database update failed for: $email");
                }
                return ErrorCodes::createErrorResponse(ErrorCodes::REG_DATABASE_ERROR, null, 'Failed to generate new verification token');
            }

            // Send confirmation email
            $emailSent = $this->sendConfirmationEmail($email, $verificationToken);

            if (!$emailSent) {
                if ($this->debug) {
                    error_log("Auth::resendConfirmationEmail - Email sending failed for: $email");
                }
                return ErrorCodes::createErrorResponse(ErrorCodes::SYS_EMAIL_SEND_FAILED, null, 'Failed to send confirmation email');
            }

            if ($this->debug) {
                error_log("Auth::resendConfirmationEmail - Confirmation email sent to: $email with token: $verificationToken");
            }

            return ErrorCodes::createSuccessResponse([
                'email_sent' => true,
                'message' => 'Confirmation email sent successfully'
            ], 'Confirmation email sent');

        } catch (Exception $e) {
            if ($this->debug) {
                error_log("Auth::resendConfirmationEmail - Exception: " . $e->getMessage());
            }

            ErrorCodes::logError(ErrorCodes::SYS_INTERNAL_ERROR, [
                'function' => 'resendConfirmationEmail',
                'email' => $email
            ], $e);

            return ErrorCodes::createErrorResponse(ErrorCodes::SYS_INTERNAL_ERROR);
        }
    }
}
