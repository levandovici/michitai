<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Auth.php';

/**
 * Player Management Class
 * Handles player creation, authentication, data management, and progress tracking
 */
class PlayerManager {
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
     * Validate player data for cross-platform compatibility
     */
    private function validatePlayerData($jsonData) {
        if (!is_array($jsonData)) {
            throw new Exception("Player data must be a valid JSON object");
        }
        
        // Validate data types for C# compatibility
        foreach ($jsonData as $key => $value) {
            if (isset($value['type']) && isset($value['value'])) {
                $dataType = $value['type'];
                $dataValue = $value['value'];
                
                if (!$this->auth->validateDataType($dataValue, $dataType)) {
                    throw new Exception("Invalid data type for field '$key': expected $dataType");
                }
            }
        }
        
        return true;
    }
    
    /**
     * Create new player
     */
    public function createPlayer($userId, $gameId) {
        // Check if user can create players
        if (!$this->auth->canPerformAction($userId, 'create_player')) {
            throw new Exception("Player creation limit reached for your plan");
        }
        
        // Verify game exists and belongs to user
        $game = $this->db->fetchOne(
            "SELECT user_id FROM games WHERE game_id = ? AND is_active = 1",
            [$gameId]
        );
        
        if (!$game || $game['user_id'] != $userId) {
            throw new Exception("Game not found or access denied");
        }
        
        // Generate GUIDs
        $playerId = $this->generateGUID();
        $passwordGuid = $this->generateGUID();
        
        // Create player with default data
        $defaultData = [
            'level' => ['type' => 'Integer', 'value' => 1],
            'score' => ['type' => 'Long', 'value' => 0],
            'health' => ['type' => 'Float', 'value' => 100.0],
            'position' => ['type' => 'Array', 'value' => [0, 0, 0]],
            'inventory' => ['type' => 'Array', 'value' => []],
            'status' => ['type' => 'Enum', 'value' => 'active'],
            'created_at' => ['type' => 'String', 'value' => date('Y-m-d H:i:s')]
        ];
        
        $this->db->execute(
            "INSERT INTO players (player_id, game_id, password_guid, json_data) VALUES (?, ?, ?, ?)",
            [$playerId, $gameId, $passwordGuid, json_encode($defaultData)]
        );
        
        // Calculate and update memory usage
        $memoryUsage = $this->calculateMemoryUsage($defaultData);
        $this->auth->updateMemoryUsage($userId, $memoryUsage);
        
        return [
            'player_id' => $playerId,
            'password_guid' => $passwordGuid,
            'game_id' => $gameId,
            'json_data' => $defaultData,
            'memory_used_mb' => $memoryUsage
        ];
    }
    
    /**
     * Authenticate player
     */
    public function authenticatePlayer($playerId, $passwordGuid) {
        $player = $this->db->fetchOne(
            "SELECT * FROM players WHERE player_id = ?",
            [$playerId]
        );
        
        if (!$player) {
            throw new Exception("Player not found");
        }
        
        if ($player['password_guid'] !== $passwordGuid) {
            throw new Exception("Invalid player credentials");
        }
        
        // Update last activity and online status
        $this->db->execute(
            "UPDATE players SET is_online = 1, last_activity = CURRENT_TIMESTAMP WHERE player_id = ?",
            [$playerId]
        );
        
        // Decode JSON data
        $player['json_data'] = json_decode($player['json_data'], true);
        
        return [
            'player_id' => $player['player_id'],
            'game_id' => $player['game_id'],
            'json_data' => $player['json_data'],
            'is_online' => true,
            'last_activity' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get player data
     */
    public function getPlayer($playerId) {
        $player = $this->db->fetchOne(
            "SELECT * FROM players WHERE player_id = ?",
            [$playerId]
        );
        
        if (!$player) {
            throw new Exception("Player not found");
        }
        
        // Decode JSON data
        $player['json_data'] = json_decode($player['json_data'], true);
        
        return $player;
    }
    
    /**
     * Update player data
     */
    public function updatePlayer($playerId, $jsonData) {
        // Validate player exists
        $player = $this->db->fetchOne(
            "SELECT game_id, json_data FROM players WHERE player_id = ?",
            [$playerId]
        );
        
        if (!$player) {
            throw new Exception("Player not found");
        }
        
        // Validate new data
        $this->validatePlayerData($jsonData);
        
        // Get game owner for memory limit checking
        $game = $this->db->fetchOne(
            "SELECT user_id FROM games WHERE game_id = ?",
            [$player['game_id']]
        );
        
        // Calculate memory difference
        $oldData = json_decode($player['json_data'], true);
        $oldMemory = $this->calculateMemoryUsage($oldData);
        $newMemory = $this->calculateMemoryUsage($jsonData);
        $memoryDiff = $newMemory - $oldMemory;
        
        // Check memory limit if increasing
        if ($memoryDiff > 0) {
            $this->auth->checkMemoryLimit($game['user_id'], $memoryDiff);
        }
        
        // Update player data
        $this->db->execute(
            "UPDATE players SET json_data = ?, updated_at = CURRENT_TIMESTAMP WHERE player_id = ?",
            [json_encode($jsonData), $playerId]
        );
        
        // Update memory usage
        if ($memoryDiff != 0) {
            $this->auth->updateMemoryUsage($game['user_id'], $memoryDiff);
        }
        
        return [
            'player_id' => $playerId,
            'json_data' => $jsonData,
            'memory_diff_mb' => $memoryDiff,
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Set player offline
     */
    public function setPlayerOffline($playerId) {
        $this->db->execute(
            "UPDATE players SET is_online = 0, last_activity = CURRENT_TIMESTAMP WHERE player_id = ?",
            [$playerId]
        );
        
        return ['success' => true, 'message' => 'Player set offline'];
    }
    
    /**
     * Get players by game
     */
    public function getPlayersByGame($gameId, $onlineOnly = false) {
        $sql = "SELECT player_id, is_online, last_activity, created_at FROM players WHERE game_id = ?";
        $params = [$gameId];
        
        if ($onlineOnly) {
            $sql .= " AND is_online = 1";
        }
        
        $sql .= " ORDER BY last_activity DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get player statistics
     */
    public function getPlayerStats($playerId) {
        $player = $this->db->fetchOne(
            "SELECT * FROM players WHERE player_id = ?",
            [$playerId]
        );
        
        if (!$player) {
            throw new Exception("Player not found");
        }
        
        $jsonData = json_decode($player['json_data'], true);
        
        // Extract common statistics
        $stats = [
            'player_id' => $playerId,
            'game_id' => $player['game_id'],
            'is_online' => $player['is_online'],
            'last_activity' => $player['last_activity'],
            'created_at' => $player['created_at'],
            'data_size_mb' => $this->calculateMemoryUsage($jsonData)
        ];
        
        // Extract game-specific stats if available
        if (isset($jsonData['level'])) {
            $stats['level'] = $jsonData['level']['value'] ?? 1;
        }
        
        if (isset($jsonData['score'])) {
            $stats['score'] = $jsonData['score']['value'] ?? 0;
        }
        
        if (isset($jsonData['health'])) {
            $stats['health'] = $jsonData['health']['value'] ?? 100;
        }
        
        if (isset($jsonData['status'])) {
            $stats['status'] = $jsonData['status']['value'] ?? 'unknown';
        }
        
        return $stats;
    }
    
    /**
     * Bulk update players (for game events)
     */
    public function bulkUpdatePlayers($gameId, $updates) {
        // Verify game exists
        $game = $this->db->fetchOne(
            "SELECT user_id FROM games WHERE game_id = ? AND is_active = 1",
            [$gameId]
        );
        
        if (!$game) {
            throw new Exception("Game not found");
        }
        
        $updatedPlayers = [];
        
        foreach ($updates as $update) {
            $playerId = $update['player_id'] ?? '';
            $jsonData = $update['json_data'] ?? [];
            
            if (empty($playerId) || empty($jsonData)) {
                continue;
            }
            
            try {
                $result = $this->updatePlayer($playerId, $jsonData);
                $updatedPlayers[] = $result;
            } catch (Exception $e) {
                // Log error but continue with other players
                error_log("Failed to update player $playerId: " . $e->getMessage());
            }
        }
        
        return [
            'updated_count' => count($updatedPlayers),
            'updated_players' => $updatedPlayers
        ];
    }
    
    /**
     * Clean up inactive players
     */
    public function cleanupInactivePlayers($inactiveHours = 24) {
        $cutoffTime = date('Y-m-d H:i:s', time() - ($inactiveHours * 3600));
        
        // Set players offline if inactive
        $this->db->execute(
            "UPDATE players SET is_online = 0 WHERE last_activity < ? AND is_online = 1",
            [$cutoffTime]
        );
        
        $affectedRows = $this->db->getConnection()->rowCount();
        
        return [
            'cleaned_players' => $affectedRows,
            'cutoff_time' => $cutoffTime
        ];
    }
    
    /**
     * Export player data (for backup or migration)
     */
    public function exportPlayerData($gameId, $format = 'json') {
        // Verify game exists
        $game = $this->db->fetchOne(
            "SELECT name FROM games WHERE game_id = ? AND is_active = 1",
            [$gameId]
        );
        
        if (!$game) {
            throw new Exception("Game not found");
        }
        
        $players = $this->db->fetchAll(
            "SELECT * FROM players WHERE game_id = ?",
            [$gameId]
        );
        
        // Decode JSON data for each player
        foreach ($players as &$player) {
            $player['json_data'] = json_decode($player['json_data'], true);
        }
        
        $exportData = [
            'game_id' => $gameId,
            'game_name' => $game['name'],
            'export_date' => date('Y-m-d H:i:s'),
            'player_count' => count($players),
            'players' => $players
        ];
        
        switch ($format) {
            case 'json':
                return json_encode($exportData, JSON_PRETTY_PRINT);
            case 'csv':
                // Simplified CSV export
                $csv = "player_id,game_id,is_online,last_activity,created_at\n";
                foreach ($players as $player) {
                    $csv .= "{$player['player_id']},{$player['game_id']},{$player['is_online']},{$player['last_activity']},{$player['created_at']}\n";
                }
                return $csv;
            default:
                throw new Exception("Unsupported export format");
        }
    }
    
    /**
     * Validate player data structure for specific game
     */
    public function validatePlayerDataStructure($gameId, $jsonData) {
        // Get game structure to validate against
        $game = $this->db->fetchOne(
            "SELECT json_structure FROM games WHERE game_id = ? AND is_active = 1",
            [$gameId]
        );
        
        if (!$game) {
            throw new Exception("Game not found");
        }
        
        $gameStructure = json_decode($game['json_structure'], true);
        
        // Validate required fields if defined in game structure
        if (isset($gameStructure['required_player_fields'])) {
            foreach ($gameStructure['required_player_fields'] as $field) {
                if (!isset($jsonData[$field])) {
                    throw new Exception("Required player field missing: $field");
                }
            }
        }
        
        // Validate field types if defined
        if (isset($gameStructure['player_field_types'])) {
            foreach ($gameStructure['player_field_types'] as $field => $expectedType) {
                if (isset($jsonData[$field])) {
                    $value = $jsonData[$field]['value'] ?? $jsonData[$field];
                    if (!$this->auth->validateDataType($value, $expectedType)) {
                        throw new Exception("Invalid data type for field '$field': expected $expectedType");
                    }
                }
            }
        }
        
        return true;
    }
}
?>
