<?php
/**
 * Database Configuration for Multiplayer API Web Constructor
 * Updated for Hostinger secure structure with private directory
 * Compatible with Hostinger MySQL 8.x
 */

class Database {
    private static $instance = null;
    private $connection;
    private static $config = null;
    
    private function __construct() {
        // Load environment configuration
        $config = self::loadEnv();
        
        $dsn = "mysql:host={$config['DB_HOST']};dbname={$config['DB_NAME']};charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];
        
        try {
            $this->connection = new PDO($dsn, $config['DB_USERNAME'], $config['DB_PASSWORD'], $options);
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
     * Load environment configuration from private directory
     */
    public static function loadEnv() {
        if (self::$config !== null) {
            return self::$config;
        }
        
        // Updated path for Hostinger private directory structure
        $envFile = '/home/u833544264/domains/api.michitai.com/private/api_backend/.env';
        if (!file_exists($envFile)) {
            throw new Exception('Environment file not found at: ' . $envFile);
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        self::$config = [];
        
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) continue;
            
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                self::$config[trim($parts[0])] = trim($parts[1]);
            }
        }
        
        return self::$config;
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
     * Fetch single row
     */
    public function fetchRow($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}

/**
 * Configuration Constants for Hostinger Deployment
 */
class Config {
    // System Limits (200GB storage, 200 users max)
    const SYSTEM_MAX_STORAGE_MB = 204800; // 200GB
    const SYSTEM_MAX_USERS = 200;
    
    // Plan Limits
    const PLAN_LIMITS = [
        'Free' => [
            'storage_mb' => 1024,      // 1GB
            'users' => 10,
            'games' => 3,
            'players_per_game' => 10,
            'rooms_per_game' => 5,
            'communities' => 1,
            'messages_per_day' => 100,
            'api_calls_per_day' => 1000
        ],
        'Standard' => [
            'storage_mb' => 10240,     // 10GB
            'users' => 50,
            'games' => 20,
            'players_per_game' => 50,
            'rooms_per_game' => 25,
            'communities' => 5,
            'messages_per_day' => 1000,
            'api_calls_per_day' => 10000
        ],
        'Pro' => [
            'storage_mb' => 51200,     // 50GB
            'users' => 200,
            'games' => 100,
            'players_per_game' => 200,
            'rooms_per_game' => 100,
            'communities' => 20,
            'messages_per_day' => 5000,
            'api_calls_per_day' => 50000
        ]
    ];
    
    // PayPal Configuration
    const PAYPAL_PLANS = [
        'Standard' => [
            'price' => '9.99',
            'currency' => 'USD',
            'interval' => 'month'
        ],
        'Pro' => [
            'price' => '29.99',
            'currency' => 'USD',
            'interval' => 'month'
        ]
    ];
    
    // MAIB Bank Configuration (Moldova)
    const MAIB_BANK = [
        'name' => 'Moldova Agroindbank',
        'swift' => 'AGRNMD2X',
        'country' => 'Moldova',
        'currency' => 'MDL'
    ];
    
    // SMTP Configuration for Hostinger
    const SMTP_CONFIG = [
        'host' => 'smtp.hostinger.com',
        'port' => 587,
        'encryption' => 'tls'
    ];
    
    // Slack Configuration for Pro Users
    const SLACK_CONFIG = [
        'enabled' => true,
        'timeout' => 30
    ];
    
    // Cross-platform Data Type Mappings
    const DATA_TYPES = [
        'Boolean' => ['js' => 'boolean', 'php' => 'bool', 'csharp' => 'bool'],
        'Char' => ['js' => 'string', 'php' => 'string', 'csharp' => 'char'],
        'Byte' => ['js' => 'number', 'php' => 'int', 'csharp' => 'byte'],
        'Short' => ['js' => 'number', 'php' => 'int', 'csharp' => 'short'],
        'Integer' => ['js' => 'number', 'php' => 'int', 'csharp' => 'int'],
        'Long' => ['js' => 'number', 'php' => 'int', 'csharp' => 'long'],
        'Float' => ['js' => 'number', 'php' => 'float', 'csharp' => 'float'],
        'Double' => ['js' => 'number', 'php' => 'float', 'csharp' => 'double'],
        'String' => ['js' => 'string', 'php' => 'string', 'csharp' => 'string'],
        'Array' => ['js' => 'array', 'php' => 'array', 'csharp' => 'array'],
        'Enum' => ['js' => 'string', 'php' => 'string', 'csharp' => 'enum']
    ];
    
    // Security Configuration
    const SECURITY = [
        'bcrypt_rounds' => 12,
        'token_length' => 64,
        'session_timeout' => 3600, // 1 hour
        'max_login_attempts' => 5,
        'lockout_duration' => 900 // 15 minutes
    ];
    
    // File Upload Configuration
    const UPLOAD = [
        'max_size' => 10485760, // 10MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt'],
        'upload_path' => '/home/u833544264/domains/api.michitai.com/private/uploads/'
    ];
    
    // Logging Configuration
    const LOGGING = [
        'path' => '/home/u833544264/domains/api.michitai.com/private/logs/',
        'level' => 'error', // debug, info, warning, error
        'max_file_size' => 10485760, // 10MB
        'max_files' => 10
    ];
    
    /**
     * Get configuration value from environment
     */
    public static function get($key, $default = null) {
        $config = Database::loadEnv();
        return $config[$key] ?? $default;
    }
    
    /**
     * Get plan limits for a specific plan
     */
    public static function getPlanLimits($planType) {
        return self::PLAN_LIMITS[$planType] ?? self::PLAN_LIMITS['Free'];
    }
    
    /**
     * Validate data type compatibility
     */
    public static function validateDataType($type, $value) {
        if (!isset(self::DATA_TYPES[$type])) {
            return false;
        }
        
        switch ($type) {
            case 'Boolean':
                return is_bool($value) || in_array(strtolower($value), ['true', 'false', '1', '0']);
            case 'Char':
                return is_string($value) && strlen($value) === 1;
            case 'Byte':
                return is_numeric($value) && $value >= 0 && $value <= 255;
            case 'Short':
                return is_numeric($value) && $value >= -32768 && $value <= 32767;
            case 'Integer':
                return is_numeric($value) && $value >= -2147483648 && $value <= 2147483647;
            case 'Long':
                return is_numeric($value);
            case 'Float':
            case 'Double':
                return is_numeric($value);
            case 'String':
                return is_string($value);
            case 'Array':
                return is_array($value);
            case 'Enum':
                return is_string($value);
            default:
                return false;
        }
    }
    
    /**
     * Convert value to appropriate type
     */
    public static function convertDataType($type, $value) {
        switch ($type) {
            case 'Boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'Char':
                return substr((string)$value, 0, 1);
            case 'Byte':
            case 'Short':
            case 'Integer':
                return (int)$value;
            case 'Long':
                return (int)$value;
            case 'Float':
            case 'Double':
                return (float)$value;
            case 'String':
                return (string)$value;
            case 'Array':
                return is_array($value) ? $value : [$value];
            case 'Enum':
                return (string)$value;
            default:
                return $value;
        }
    }
}

// Initialize database connection test
try {
    Database::loadEnv();
} catch (Exception $e) {
    error_log("Failed to load environment configuration: " . $e->getMessage());
}
?>
