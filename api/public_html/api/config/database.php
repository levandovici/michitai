<?php
/**
 * Database Configuration for Hostinger MySQL
 * Secure database connection settings
 */

class DatabaseConfig {
    // Database connection settings for Hostinger
    private static $config = [
        'host' => 'localhost',
        'database' => 'u833544264_multiplayer_api',
        'username' => 'u833544264_api_user',
        'password' => '', // Set this in .env file for security
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    ];

    /**
     * Get database connection
     */
    public static function getConnection() {
        // Load environment variables if .env file exists
        self::loadEnvironment();
        
        // Get database credentials
        $host = $_ENV['DB_HOST'] ?? self::$config['host'];
        $database = $_ENV['DB_DATABASE'] ?? self::$config['database'];
        $username = $_ENV['DB_USERNAME'] ?? self::$config['username'];
        $password = $_ENV['DB_PASSWORD'] ?? self::$config['password'];
        
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("DatabaseConfig: Connecting to MySQL - Host: $host, Database: $database, User: $username");
        }
        
        try {
            $dsn = "mysql:host=$host;dbname=$database;charset=" . self::$config['charset'];
            $pdo = new PDO($dsn, $username, $password, self::$config['options']);
            
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("DatabaseConfig: MySQL connection successful");
            }
            
            return $pdo;
            
        } catch (PDOException $e) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("DatabaseConfig: MySQL connection failed - " . $e->getMessage());
            }
            
            // Fallback to SQLite for development/testing
            return self::getSQLiteFallback();
        }
    }
    
    /**
     * SQLite fallback for development
     */
    private static function getSQLiteFallback() {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("DatabaseConfig: Using SQLite fallback for development");
        }
        
        $dbPath = __DIR__ . '/../data/multiplayer_api.db';
        $dbDir = dirname($dbPath);
        
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return $pdo;
    }
    
    /**
     * Load environment variables from .env file
     */
    private static function loadEnvironment() {
        $envFile = __DIR__ . '/../.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
            
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("DatabaseConfig: Loaded environment variables from .env file");
            }
        }
    }
    
    /**
     * Get MySQL table creation SQL
     */
    public static function getMySQLTableSQL() {
        return "
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
            INDEX idx_api_token (api_token),
            INDEX idx_verification_token (verification_token)
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
            INDEX idx_endpoint (endpoint),
            INDEX idx_timestamp (timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
    }
    
    /**
     * Get SQLite table creation SQL
     */
    public static function getSQLiteTableSQL() {
        return "
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
}
