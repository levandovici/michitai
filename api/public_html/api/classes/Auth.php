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
    private $isMySQL;

    public function __construct() {
        $this->debug = defined('DEBUG_MODE') && DEBUG_MODE;
        $this->initializeDatabase();
    }

    private function initializeDatabase() {
        try {
            // Use the DatabaseConfig class to get connection
            $this->db = DatabaseConfig::getConnection();
            
            // Detect if we're using MySQL or SQLite
            $this->isMySQL = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
            
            // Create tables if they don't exist
            $this->createTables();
            
            if ($this->debug) {
                $dbType = $this->isMySQL ? 'MySQL' : 'SQLite';
                error_log("Auth: Database initialized successfully using $dbType");
            }
        } catch (Exception $e) {
            ErrorCodes::logError(ErrorCodes::DB_CONNECTION_FAILED, ['error' => $e->getMessage()], $e);
            throw new Exception("Database initialization failed: " . $e->getMessage());
        }
    }

    private function createTables() {
        try {
            if ($this->isMySQL) {
                $sql = DatabaseConfig::getMySQLTableSQL();
            } else {
                $sql = DatabaseConfig::getSQLiteTableSQL();
            }
            
            // Execute each statement separately for MySQL compatibility
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $this->db->exec($statement);
                }
            }
            
            if ($this->debug) {
                $dbType = $this->isMySQL ? 'MySQL' : 'SQLite';
                error_log("Auth: Tables created successfully in $dbType");
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

            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
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
                INSERT INTO users (email, password_hash, api_token, verification_token, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $timestamp = time();
            $result = $stmt->execute([
                $email, 
                $passwordHash, 
                $apiToken, 
                $verificationToken, 
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

            // Debug point 4: Send verification email (simulated for development)
            if ($this->debug) {
                error_log("Auth::register - Sending verification email to: $email");
                error_log("Auth::register - Verification token: $verificationToken");
            }

            // In development, we'll simulate email sending
            $emailSent = $this->sendVerificationEmail($email, $verificationToken);
            
            if (!$emailSent && $this->debug) {
                error_log("Auth::register - Warning: Verification email failed for: $email");
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

            if (!password_verify($password, $user['password_hash'])) {
                if ($this->debug) {
                    error_log("Auth::login - Invalid password for: $email");
                }
                return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_INVALID_CREDENTIALS);
            }

            // Generate new API token
            $apiToken = $this->generateApiToken();
            $stmt = $this->db->prepare("UPDATE users SET api_token = ?, updated_at = ? WHERE id = ?");
            $stmt->execute([$apiToken, time(), $user['id']]);

            if ($this->debug) {
                error_log("Auth::login - Login successful for: $email");
            }

            return ErrorCodes::createSuccessResponse([
                'user_id' => $user['id'],
                'email' => $user['email'],
                'api_token' => $apiToken,
                'email_verified' => (bool)$user['email_verified'],
                'plan_type' => $user['plan_type']
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
            $stmt = $this->db->prepare("SELECT id, email FROM users WHERE api_token = ?");
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
     * Send verification email (simulated for development)
     */
    private function sendVerificationEmail($email, $token) {
        if ($this->debug) {
            error_log("Auth::sendVerificationEmail - Simulating email send to: $email");
            error_log("Auth::sendVerificationEmail - Verification URL: /confirm-email.html?token=$token");
        }
        
        // In development, always return true
        // In production, implement actual email sending
        return true;
    }
}
