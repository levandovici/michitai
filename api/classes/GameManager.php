<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Auth.php';

/**
 * Game Management Class
 * Handles game creation, configuration, rooms, communities, chats, and puzzle logic
 */
class GameManager {
    private $db;
    private $auth;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
    }
    
    /**
     * Generate GUID for players
     */
    private function generateGUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Calculate JSON memory usage in MB
     */
    private function calculateMemoryUsage($jsonData) {
        return strlen(json_encode($jsonData)) / (1024 * 1024);
    }
    
    /**
     * Validate puzzle logic structure
     */
    private function validatePuzzleLogic($structure) {
        $allowedElements = [
            'triggers', 'timers', 'logic', 'operators', 'functions', 'data_types'
        ];
        
        $allowedLogic = ['If', 'If-Else', 'For', 'Switch', 'While', 'Do-While'];
        $allowedOperators = ['+', '-', '*', '/', '%', '<', '>', '<=', '>=', '==', '!=', '!'];
        $allowedFunctions = ['Power', 'Sqrt', 'Qrt', 'Random'];
        $allowedDataTypes = ['Boolean', 'Char', 'Byte', 'Short', 'Integer', 'Long', 'Float', 'Double', 'String', 'Array', 'Enum'];
        
        // Validate structure contains required elements
        if (!is_array($structure)) {
            throw new Exception("Puzzle logic structure must be an array");
        }
        
        // Validate logic elements if present
        if (isset($structure['logic'])) {
            foreach ($structure['logic'] as $logic) {
                if (!in_array($logic['type'], $allowedLogic)) {
                    throw new Exception("Invalid logic type: " . $logic['type']);
                }
            }
        }
        
        // Validate data types
        if (isset($structure['data_types'])) {
            foreach ($structure['data_types'] as $dataType) {
                if (!in_array($dataType, $allowedDataTypes)) {
                    throw new Exception("Invalid data type: " . $dataType);
                }
            }
        }
        
        return true;
    }
    
    /**
     * Create new game
     */
    public function createGame($userId, $input) {
        // Check if user can create games
        if (!$this->auth->canPerformAction($userId, 'create_game')) {
            throw new Exception("Game creation limit reached for your plan");
        }
        
        $name = $input['name'] ?? '';
        $description = $input['description'] ?? '';
        $jsonStructure = $input['json_structure'] ?? [];
        $jsonProperties = $input['json_properties'] ?? [];
        
        if (empty($name) || empty($jsonStructure)) {
            throw new Exception("Game name and structure are required");
        }
        
        // Validate puzzle logic structure
        $this->validatePuzzleLogic($jsonStructure);
        
        // Calculate memory usage
        $memoryUsage = $this->calculateMemoryUsage([
            'structure' => $jsonStructure,
            'properties' => $jsonProperties
        ]);
        
        // Check memory limit
        $this->auth->checkMemoryLimit($userId, $memoryUsage);
        
        // Create game
        $this->db->execute(
            "INSERT INTO games (user_id, name, description, json_structure, json_properties) VALUES (?, ?, ?, ?, ?)",
            [$userId, $name, $description, json_encode($jsonStructure), json_encode($jsonProperties)]
        );
        
        $gameId = $this->db->lastInsertId();
        
        // Update memory usage
        $this->auth->updateMemoryUsage($userId, $memoryUsage);
        
        return [
            'game_id' => $gameId,
            'name' => $name,
            'description' => $description,
            'memory_used_mb' => $memoryUsage
        ];
    }
    
    /**
     * Get user's games
     */
    public function getUserGames($userId) {
        return $this->db->fetchAll(
            "SELECT game_id, name, description, is_active, created_at FROM games WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
    }
    
    /**
     * Get specific game
     */
    public function getGame($userId, $gameId) {
        $game = $this->db->fetchOne(
            "SELECT * FROM games WHERE game_id = ? AND user_id = ?",
            [$gameId, $userId]
        );
        
        if (!$game) {
            throw new Exception("Game not found");
        }
        
        // Decode JSON fields
        $game['json_structure'] = json_decode($game['json_structure'], true);
        $game['json_properties'] = json_decode($game['json_properties'], true);
        $game['json_rooms'] = json_decode($game['json_rooms'], true);
        $game['json_communities'] = json_decode($game['json_communities'], true);
        $game['json_chats'] = json_decode($game['json_chats'], true);
        
        return $game;
    }
    
    /**
     * Update game
     */
    public function updateGame($userId, $gameId, $input) {
        // Verify ownership
        $game = $this->db->fetchOne(
            "SELECT user_id FROM games WHERE game_id = ?",
            [$gameId]
        );
        
        if (!$game || $game['user_id'] != $userId) {
            throw new Exception("Game not found or access denied");
        }
        
        $updates = [];
        $params = [];
        
        if (isset($input['name'])) {
            $updates[] = "name = ?";
            $params[] = $input['name'];
        }
        
        if (isset($input['description'])) {
            $updates[] = "description = ?";
            $params[] = $input['description'];
        }
        
        if (isset($input['json_structure'])) {
            $this->validatePuzzleLogic($input['json_structure']);
            $updates[] = "json_structure = ?";
            $params[] = json_encode($input['json_structure']);
        }
        
        if (isset($input['json_properties'])) {
            $updates[] = "json_properties = ?";
            $params[] = json_encode($input['json_properties']);
        }
        
        if (empty($updates)) {
            throw new Exception("No valid fields to update");
        }
        
        $params[] = $gameId;
        
        $this->db->execute(
            "UPDATE games SET " . implode(', ', $updates) . " WHERE game_id = ?",
            $params
        );
        
        return ['success' => true, 'message' => 'Game updated successfully'];
    }
    
    /**
     * Delete game
     */
    public function deleteGame($userId, $gameId) {
        // Verify ownership
        $game = $this->db->fetchOne(
            "SELECT user_id FROM games WHERE game_id = ?",
            [$gameId]
        );
        
        if (!$game || $game['user_id'] != $userId) {
            throw new Exception("Game not found or access denied");
        }
        
        $this->db->execute(
            "UPDATE games SET is_active = 0 WHERE game_id = ?",
            [$gameId]
        );
        
        return ['success' => true, 'message' => 'Game deleted successfully'];
    }
    
    /**
     * Create room
     */
    public function createRoom($userId, $input) {
        if (!$this->auth->canPerformAction($userId, 'create_room')) {
            throw new Exception("Room creation limit reached for your plan");
        }
        
        $gameId = $input['game_id'] ?? '';
        $name = $input['name'] ?? '';
        $maxPlayers = $input['max_players'] ?? 10;
        $jsonData = $input['json_data'] ?? [];
        
        // Verify game ownership
        $game = $this->db->fetchOne(
            "SELECT user_id FROM games WHERE game_id = ? AND is_active = 1",
            [$gameId]
        );
        
        if (!$game || $game['user_id'] != $userId) {
            throw new Exception("Game not found or access denied");
        }
        
        $this->db->execute(
            "INSERT INTO rooms (game_id, name, max_players, json_data) VALUES (?, ?, ?, ?)",
            [$gameId, $name, $maxPlayers, json_encode($jsonData)]
        );
        
        return [
            'room_id' => $this->db->lastInsertId(),
            'name' => $name,
            'max_players' => $maxPlayers
        ];
    }
    
    /**
     * Join room
     */
    public function joinRoom($input) {
        $roomId = $input['room_id'] ?? '';
        $playerId = $input['player_id'] ?? '';
        
        $room = $this->db->fetchOne(
            "SELECT * FROM rooms WHERE room_id = ? AND is_active = 1",
            [$roomId]
        );
        
        if (!$room) {
            throw new Exception("Room not found");
        }
        
        if ($room['current_players'] >= $room['max_players']) {
            throw new Exception("Room is full");
        }
        
        // Update room player count
        $this->db->execute(
            "UPDATE rooms SET current_players = current_players + 1 WHERE room_id = ?",
            [$roomId]
        );
        
        return ['success' => true, 'message' => 'Joined room successfully'];
    }
    
    /**
     * Leave room
     */
    public function leaveRoom($input) {
        $roomId = $input['room_id'] ?? '';
        
        $this->db->execute(
            "UPDATE rooms SET current_players = GREATEST(0, current_players - 1) WHERE room_id = ?",
            [$roomId]
        );
        
        return ['success' => true, 'message' => 'Left room successfully'];
    }
    
    /**
     * Get room data
     */
    public function getRoomData($input) {
        $roomId = $input['room_id'] ?? '';
        
        $room = $this->db->fetchOne(
            "SELECT * FROM rooms WHERE room_id = ? AND is_active = 1",
            [$roomId]
        );
        
        if (!$room) {
            throw new Exception("Room not found");
        }
        
        $room['json_data'] = json_decode($room['json_data'], true);
        return $room;
    }
    
    /**
     * Remove room
     */
    public function removeRoom($userId, $input) {
        $roomId = $input['room_id'] ?? '';
        
        // Verify ownership through game
        $room = $this->db->fetchOne(
            "SELECT r.*, g.user_id FROM rooms r JOIN games g ON r.game_id = g.game_id WHERE r.room_id = ?",
            [$roomId]
        );
        
        if (!$room || $room['user_id'] != $userId) {
            throw new Exception("Room not found or access denied");
        }
        
        $this->db->execute(
            "UPDATE rooms SET is_active = 0 WHERE room_id = ?",
            [$roomId]
        );
        
        return ['success' => true, 'message' => 'Room removed successfully'];
    }
    
    /**
     * Create community
     */
    public function createCommunity($userId, $input) {
        if (!$this->auth->canPerformAction($userId, 'create_community')) {
            throw new Exception("Community creation not allowed for your plan");
        }
        
        $gameId = $input['game_id'] ?? '';
        $name = $input['name'] ?? '';
        $description = $input['description'] ?? '';
        $jsonData = $input['json_data'] ?? [];
        $privileges = $input['privileges'] ?? [];
        
        // Verify game ownership
        $game = $this->db->fetchOne(
            "SELECT user_id FROM games WHERE game_id = ? AND is_active = 1",
            [$gameId]
        );
        
        if (!$game || $game['user_id'] != $userId) {
            throw new Exception("Game not found or access denied");
        }
        
        $this->db->execute(
            "INSERT INTO communities (game_id, name, description, json_data, privileges) VALUES (?, ?, ?, ?, ?)",
            [$gameId, $name, $description, json_encode($jsonData), json_encode($privileges)]
        );
        
        return [
            'community_id' => $this->db->lastInsertId(),
            'name' => $name,
            'description' => $description
        ];
    }
    
    /**
     * Create trigger
     */
    public function createTrigger($userId, $gameId, $name, $parameters) {
        // Verify game ownership
        $game = $this->db->fetchOne(
            "SELECT user_id FROM games WHERE game_id = ? AND is_active = 1",
            [$gameId]
        );
        
        if (!$game || $game['user_id'] != $userId) {
            throw new Exception("Game not found or access denied");
        }
        
        // Validate trigger parameters
        if (!is_array($parameters)) {
            throw new Exception("Trigger parameters must be an array");
        }
        
        $actionType = $parameters['action_type'] ?? 'event';
        $allowedTypes = ['timer', 'event', 'condition', 'function'];
        
        if (!in_array($actionType, $allowedTypes)) {
            throw new Exception("Invalid trigger action type");
        }
        
        $this->db->execute(
            "INSERT INTO triggers (game_id, name, parameters, action_type) VALUES (?, ?, ?, ?)",
            [$gameId, $name, json_encode($parameters), $actionType]
        );
        
        return [
            'trigger_id' => $this->db->lastInsertId(),
            'name' => $name,
            'action_type' => $actionType
        ];
    }
    
    /**
     * Set timer
     */
    public function setTimer($input) {
        $gameId = $input['game_id'] ?? '';
        $playerId = $input['player_id'] ?? null;
        $name = $input['name'] ?? '';
        $value = $input['value'] ?? 0.0;
        $multiplier = $input['multiplier'] ?? 1.0;
        $triggerId = $input['trigger_id'] ?? null;
        
        $this->db->execute(
            "INSERT INTO timers (game_id, player_id, name, value, initial_value, multiplier, trigger_id, is_running) VALUES (?, ?, ?, ?, ?, ?, ?, 1)",
            [$gameId, $playerId, $name, $value, $value, $multiplier, $triggerId]
        );
        
        return [
            'timer_id' => $this->db->lastInsertId(),
            'name' => $name,
            'value' => $value,
            'is_running' => true
        ];
    }
    
    /**
     * Perform timer math operations
     */
    public function performTimerMath($input) {
        $timerId = $input['timer_id'] ?? '';
        $operation = $input['operation'] ?? '';
        $operand = $input['operand'] ?? 0;
        
        $allowedOperations = ['+', '-', '*', '/', '%'];
        
        if (!in_array($operation, $allowedOperations)) {
            throw new Exception("Invalid math operation");
        }
        
        $timer = $this->db->fetchOne(
            "SELECT value FROM timers WHERE timer_id = ?",
            [$timerId]
        );
        
        if (!$timer) {
            throw new Exception("Timer not found");
        }
        
        $currentValue = $timer['value'];
        $newValue = $currentValue;
        
        switch ($operation) {
            case '+':
                $newValue = $currentValue + $operand;
                break;
            case '-':
                $newValue = $currentValue - $operand;
                break;
            case '*':
                $newValue = $currentValue * $operand;
                break;
            case '/':
                if ($operand == 0) throw new Exception("Division by zero");
                $newValue = $currentValue / $operand;
                break;
            case '%':
                if ($operand == 0) throw new Exception("Modulo by zero");
                $newValue = $currentValue % $operand;
                break;
        }
        
        $this->db->execute(
            "UPDATE timers SET value = ? WHERE timer_id = ?",
            [$newValue, $timerId]
        );
        
        return [
            'timer_id' => $timerId,
            'old_value' => $currentValue,
            'new_value' => $newValue,
            'operation' => $operation,
            'operand' => $operand
        ];
    }
    
    /**
     * Get user monitoring data
     */
    public function getUserMonitorData($userId) {
        $user = $this->db->fetchOne(
            "SELECT memory_used_mb, memory_limit_mb, api_calls_today, plan_type FROM users WHERE user_id = ?",
            [$userId]
        );
        
        if (!$user) {
            throw new Exception("User not found");
        }
        
        $limits = $this->auth->getUserLimits($user['plan_type']);
        
        return [
            'user_id' => $userId,
            'memory_used_mb' => $user['memory_used_mb'],
            'memory_limit_mb' => $user['memory_limit_mb'],
            'memory_usage_percent' => ($user['memory_used_mb'] / $user['memory_limit_mb']) * 100,
            'api_calls_today' => $user['api_calls_today'],
            'api_calls_limit' => $limits['max_api_calls_per_day'],
            'api_usage_percent' => ($user['api_calls_today'] / $limits['max_api_calls_per_day']) * 100,
            'plan_type' => $user['plan_type'],
            'plan_limits' => $limits
        ];
    }
    
    /**
     * Get system monitoring data
     */
    public function getSystemMonitorData() {
        $stats = $this->db->fetchOne(
            "SELECT * FROM system_stats ORDER BY recorded_at DESC LIMIT 1"
        );
        
        $totalMemory = $this->db->fetchOne(
            "SELECT SUM(memory_used_mb) as total FROM users"
        )['total'] ?? 0;
        
        $totalUsers = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM users"
        )['count'] ?? 0;
        
        $activeGames = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM games WHERE is_active = 1"
        )['count'] ?? 0;
        
        $activePlayers = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM players WHERE is_online = 1"
        )['count'] ?? 0;
        
        return [
            'total_memory_mb' => $totalMemory,
            'memory_limit_mb' => Config::get('SYSTEM_MAX_MEMORY_MB'),
            'memory_usage_percent' => ($totalMemory / Config::get('SYSTEM_MAX_MEMORY_MB')) * 100,
            'total_users' => $totalUsers,
            'user_limit' => Config::get('SYSTEM_MAX_USERS'),
            'user_usage_percent' => ($totalUsers / Config::get('SYSTEM_MAX_USERS')) * 100,
            'active_games' => $activeGames,
            'active_players' => $activePlayers,
            'last_updated' => $stats['recorded_at'] ?? null
        ];
    }
}
?>
