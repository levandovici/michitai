<?php
/**
 * Database Configuration for Multiplayer API Web Constructor
 * Compatible with Hostinger MySQL 8.x
 */

class Database {
    private static $instance = null;
    private $connection;
    
    // Database configuration - Update these for Hostinger deployment
    private $host = 'localhost'; // Hostinger MySQL host
    private $database = 'multiplayer_api';
    private $username = 'root'; // Update with Hostinger MySQL username
    private $password = ''; // Update with Hostinger MySQL password
    private $charset = 'utf8mb4';
    
    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];
        
        try {
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute a prepared statement with parameters
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query failed: " . $e->getMessage());
            throw new Exception("Database query failed");
        }
    }
    
    /**
     * Get single row from database
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Get multiple rows from database
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get last inserted ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * Check if we're in a transaction
     */
    public function inTransaction() {
        return $this->connection->inTransaction();
    }
}

/**
 * Environment Configuration
 */
class Config {
    private static $config = [
        // System limits
        'SYSTEM_MAX_MEMORY_MB' => 204800, // 200 GB
        'SYSTEM_MAX_USERS' => 200,
        
        // Plan limits
        'PLAN_LIMITS' => [
            'Free' => [
                'memory_mb' => 250,
                'max_users' => 80,
                'max_games' => 1,
                'max_players' => 100,
                'max_rooms' => 5,
                'max_communities' => 0,
                'max_messages_per_day' => 0,
                'max_api_calls_per_day' => 1000,
                'price' => 0
            ],
            'Standard' => [
                'memory_mb' => 1024,
                'max_users' => 80,
                'max_games' => 1,
                'max_players' => 1000,
                'max_rooms' => 50,
                'max_communities' => 5,
                'max_messages_per_day' => 1000,
                'max_api_calls_per_day' => 10000,
                'price' => 50
            ],
            'Pro' => [
                'memory_mb' => 2560,
                'max_users' => 40,
                'max_games' => 3,
                'max_players' => 2500,
                'max_rooms' => -1, // unlimited
                'max_communities' => -1, // unlimited
                'max_messages_per_day' => -1, // unlimited
                'max_api_calls_per_day' => 1000000,
                'price' => 200
            ]
        ],
        
        // PayPal configuration
        'PAYPAL_MODE' => 'sandbox', // Change to 'live' for production
        'PAYPAL_CLIENT_ID' => '', // Set in .env file
        'PAYPAL_CLIENT_SECRET' => '', // Set in .env file
        'PAYPAL_WEBHOOK_ID' => '', // Set in .env file
        
        // MAIB Bank details for payouts
        'MAIB_SWIFT' => 'AGRNMD2X',
        'MAIB_BANK_NAME' => 'Moldova Agroindbank',
        'MAIB_COUNTRY' => 'Moldova',
        
        // Notification settings
        'SMTP_HOST' => '', // Set in .env file
        'SMTP_PORT' => 587,
        'SMTP_USERNAME' => '', // Set in .env file
        'SMTP_PASSWORD' => '', // Set in .env file
        'SMTP_FROM_EMAIL' => '', // Set in .env file
        'SMTP_FROM_NAME' => 'Multiplayer API Constructor',
        
        'SLACK_WEBHOOK_URL' => '', // Set in .env file for Pro users
        
        // Security settings
        'JWT_SECRET' => '', // Set in .env file
        'API_RATE_LIMIT_WINDOW' => 3600, // 1 hour in seconds
        'PASSWORD_MIN_LENGTH' => 8,
        
        // Data type mappings for cross-platform compatibility
        'DATA_TYPES' => [
            'Boolean' => ['php' => 'bool', 'js' => 'boolean', 'csharp' => 'bool'],
            'Char' => ['php' => 'string', 'js' => 'string', 'csharp' => 'char'],
            'Byte' => ['php' => 'int', 'js' => 'number', 'csharp' => 'byte'],
            'Short' => ['php' => 'int', 'js' => 'number', 'csharp' => 'short'],
            'Integer' => ['php' => 'int', 'js' => 'number', 'csharp' => 'int'],
            'Long' => ['php' => 'int', 'js' => 'number', 'csharp' => 'long'],
            'Float' => ['php' => 'float', 'js' => 'number', 'csharp' => 'float'],
            'Double' => ['php' => 'float', 'js' => 'number', 'csharp' => 'double'],
            'String' => ['php' => 'string', 'js' => 'string', 'csharp' => 'string'],
            'Array' => ['php' => 'array', 'js' => 'array', 'csharp' => 'array'],
            'Enum' => ['php' => 'string', 'js' => 'string', 'csharp' => 'enum']
        ]
    ];
    
    public static function get($key, $default = null) {
        return self::$config[$key] ?? $default;
    }
    
    public static function getPlanLimit($plan, $key) {
        return self::$config['PLAN_LIMITS'][$plan][$key] ?? null;
    }
    
    public static function loadEnv() {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }
    }
}

// Load environment variables
Config::loadEnv();
?>
