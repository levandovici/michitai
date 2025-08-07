<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Authentication and Authorization Class
 * Handles user registration, login, API token management, and rate limiting
 */
class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Generate UUID v4
     */
    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Register new user
     */
    public function register($email, $password) {
        // Validate input
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        if (strlen($password) < Config::get('PASSWORD_MIN_LENGTH', 8)) {
            throw new Exception("Password must be at least " . Config::get('PASSWORD_MIN_LENGTH', 8) . " characters");
        }
        
        // Check if email already exists
        $existingUser = $this->db->fetchOne(
            "SELECT user_id FROM users WHERE email = ?", 
            [$email]
        );
        
        if ($existingUser) {
            throw new Exception("Email already registered");
        }
        
        // Check system user limit
        $userCount = $this->db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
        if ($userCount >= Config::get('SYSTEM_MAX_USERS', 200)) {
            throw new Exception("System user limit reached");
        }
        
        // Hash password and generate API token
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $apiToken = $this->generateUUID();
        
        // Insert user with Free plan defaults
        $freeLimits = Config::get('PLAN_LIMITS')['Free'];
        
        $this->db->execute(
            "INSERT INTO users (email, password, api_token, plan_type, memory_limit_mb) VALUES (?, ?, ?, 'Free', ?)",
            [$email, $hashedPassword, $apiToken, $freeLimits['memory_mb']]
        );
        
        $userId = $this->db->lastInsertId();
        
        // Create initial subscription record
        $this->db->execute(
            "INSERT INTO subscriptions (user_id, plan_type, status) VALUES (?, 'Free', 'active')",
            [$userId]
        );
        
        return [
            'user_id' => $userId,
            'email' => $email,
            'api_token' => $apiToken,
            'plan_type' => 'Free'
        ];
    }
    
    /**
     * Login user
     */
    public function login($email, $password) {
        $user = $this->db->fetchOne(
            "SELECT user_id, email, password, api_token, plan_type FROM users WHERE email = ?",
            [$email]
        );
        
        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception("Invalid email or password");
        }
        
        return [
            'user_id' => $user['user_id'],
            'email' => $user['email'],
            'api_token' => $user['api_token'],
            'plan_type' => $user['plan_type']
        ];
    }
    
    /**
     * Authenticate API token
     */
    public function authenticateToken($apiToken) {
        if (empty($apiToken)) {
            throw new Exception("API token required");
        }
        
        $user = $this->db->fetchOne(
            "SELECT user_id, email, plan_type, memory_used_mb, memory_limit_mb, api_calls_today FROM users WHERE api_token = ?",
            [$apiToken]
        );
        
        if (!$user) {
            throw new Exception("Invalid API token");
        }
        
        return $user;
    }
    
    /**
     * Check API rate limit
     */
    public function checkRateLimit($userId, $planType) {
        $limits = Config::get('PLAN_LIMITS')[$planType];
        $maxCalls = $limits['max_api_calls_per_day'];
        
        $user = $this->db->fetchOne(
            "SELECT api_calls_today FROM users WHERE user_id = ?",
            [$userId]
        );
        
        if ($user['api_calls_today'] >= $maxCalls) {
            throw new Exception("API rate limit exceeded for today");
        }
        
        // Increment API call counter
        $this->db->execute(
            "UPDATE users SET api_calls_today = api_calls_today + 1 WHERE user_id = ?",
            [$userId]
        );
        
        return true;
    }
    
    /**
     * Check memory limit
     */
    public function checkMemoryLimit($userId, $additionalMemoryMB = 0) {
        $user = $this->db->fetchOne(
            "SELECT memory_used_mb, memory_limit_mb FROM users WHERE user_id = ?",
            [$userId]
        );
        
        $newMemoryUsage = $user['memory_used_mb'] + $additionalMemoryMB;
        
        if ($newMemoryUsage > $user['memory_limit_mb']) {
            throw new Exception("Memory limit exceeded. Current: {$user['memory_used_mb']}MB, Limit: {$user['memory_limit_mb']}MB");
        }
        
        return true;
    }
    
    /**
     * Update memory usage
     */
    public function updateMemoryUsage($userId, $memoryMB) {
        $this->db->execute(
            "UPDATE users SET memory_used_mb = memory_used_mb + ? WHERE user_id = ?",
            [$memoryMB, $userId]
        );
    }
    
    /**
     * Log API call for monitoring
     */
    public function logApiCall($userId, $endpoint, $method, $responseCode, $executionTimeMs) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $this->db->execute(
            "INSERT INTO api_logs (user_id, endpoint, method, ip_address, user_agent, response_code, execution_time_ms) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$userId, $endpoint, $method, $ipAddress, $userAgent, $responseCode, $executionTimeMs]
        );
    }
    
    /**
     * Get user plan limits
     */
    public function getUserLimits($planType) {
        return Config::get('PLAN_LIMITS')[$planType] ?? null;
    }
    
    /**
     * Check if user can perform action based on plan limits
     */
    public function canPerformAction($userId, $action, $count = 1) {
        $user = $this->db->fetchOne(
            "SELECT plan_type FROM users WHERE user_id = ?",
            [$userId]
        );
        
        $limits = $this->getUserLimits($user['plan_type']);
        
        switch ($action) {
            case 'create_game':
                $currentGames = $this->db->fetchOne(
                    "SELECT COUNT(*) as count FROM games WHERE user_id = ? AND is_active = 1",
                    [$userId]
                )['count'];
                return $currentGames < $limits['max_games'];
                
            case 'create_player':
                $currentPlayers = $this->db->fetchOne(
                    "SELECT COUNT(*) as count FROM players p JOIN games g ON p.game_id = g.game_id WHERE g.user_id = ?",
                    [$userId]
                )['count'];
                return $currentPlayers < $limits['max_players'];
                
            case 'create_room':
                if ($limits['max_rooms'] == -1) return true; // unlimited
                $currentRooms = $this->db->fetchOne(
                    "SELECT COUNT(*) as count FROM rooms r JOIN games g ON r.game_id = g.game_id WHERE g.user_id = ? AND r.is_active = 1",
                    [$userId]
                )['count'];
                return $currentRooms < $limits['max_rooms'];
                
            case 'create_community':
                if ($limits['max_communities'] == -1) return true; // unlimited
                if ($limits['max_communities'] == 0) return false; // not allowed
                $currentCommunities = $this->db->fetchOne(
                    "SELECT COUNT(*) as count FROM communities c JOIN games g ON c.game_id = g.game_id WHERE g.user_id = ? AND c.is_active = 1",
                    [$userId]
                )['count'];
                return $currentCommunities < $limits['max_communities'];
                
            case 'send_message':
                if ($limits['max_messages_per_day'] == -1) return true; // unlimited
                if ($limits['max_messages_per_day'] == 0) return false; // not allowed
                // This would require tracking daily message counts
                return true; // Simplified for now
                
            default:
                return false;
        }
    }
    
    /**
     * Validate data types for cross-platform compatibility
     */
    public function validateDataType($value, $expectedType) {
        $dataTypes = Config::get('DATA_TYPES');
        
        if (!isset($dataTypes[$expectedType])) {
            throw new Exception("Invalid data type: $expectedType");
        }
        
        switch ($expectedType) {
            case 'Boolean':
                return is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false']);
            case 'Char':
                return is_string($value) && strlen($value) == 1;
            case 'Byte':
                return is_int($value) && $value >= 0 && $value <= 255;
            case 'Short':
                return is_int($value) && $value >= -32768 && $value <= 32767;
            case 'Integer':
                return is_int($value) && $value >= -2147483648 && $value <= 2147483647;
            case 'Long':
                return is_int($value);
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
}
?>
