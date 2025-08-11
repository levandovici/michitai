<?php
/**
 * Game Manager Class for Multiplayer API
 * Handles game creation, management, and statistics
 */

require_once __DIR__ . '/../config/ErrorCodes.php';
require_once __DIR__ . '/../config/database.php';

class GameManager {
    private $db;
    private $debug;
    
    public function __construct() {
        $this->debug = defined('DEBUG_MODE') && DEBUG_MODE;
        $this->initializeDatabase();
        
        if ($this->debug) {
            error_log("GameManager: Class initialized");
        }
    }
    
    private function initializeDatabase() {
        try {
            // Use Database class for MySQL connection with SQLite fallback
            try {
                $database = Database::getInstance();
                $this->db = $database->getConnection();
                
                if ($this->debug) {
                    error_log("GameManager: MySQL database connected successfully");
                }
            } catch (Exception $e) {
                // Fallback to SQLite for development
                if ($this->debug) {
                    error_log("GameManager: MySQL failed, using SQLite fallback - " . $e->getMessage());
                }
                
                $dbPath = __DIR__ . '/../data/multiplayer_api.db';
                $dbDir = dirname($dbPath);
                
                if (!is_dir($dbDir)) {
                    mkdir($dbDir, 0755, true);
                }

                $this->db = new PDO("sqlite:$dbPath");
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            
            // Create games table if it doesn't exist
            $this->createGamesTables();
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Database initialization failed - " . $e->getMessage());
            }
            throw new Exception("GameManager database initialization failed: " . $e->getMessage());
        }
    }
    
    private function createGamesTables() {
        try {
            // Detect database type
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            
            if ($driver === 'mysql') {
                // MySQL table creation
                $sql = "
                CREATE TABLE IF NOT EXISTS games (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NULL,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    game_type VARCHAR(100) DEFAULT 'multiplayer',
                    max_players INT DEFAULT 10,
                    status VARCHAR(50) DEFAULT 'active',
                    api_token VARCHAR(64) NOT NULL UNIQUE,
                    settings JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_status (status),
                    INDEX idx_api_token (api_token)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ";
            } else {
                // SQLite table creation
                $sql = "
                CREATE TABLE IF NOT EXISTS games (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NULL,
                    name TEXT NOT NULL,
                    description TEXT,
                    game_type TEXT DEFAULT 'multiplayer',
                    max_players INTEGER DEFAULT 10,
                    status TEXT DEFAULT 'active',
                    api_token TEXT NOT NULL UNIQUE,
                    settings TEXT,
                    created_at INTEGER DEFAULT (strftime('%s', 'now')),
                    updated_at INTEGER DEFAULT (strftime('%s', 'now'))
                );
                ";
            }

            $this->db->exec($sql);
            
            // Add migration for existing games without API tokens
            $this->migrateExistingGames();
            
            // Add migration to allow anonymous games
            $this->migrateForAnonymousGames();
            
            if ($this->debug) {
                error_log("GameManager: Games table created successfully using $driver");
            }
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Table creation failed - " . $e->getMessage());
            }
            throw $e;
        }
    }
    
    /**
     * Migrate existing games to add API tokens
     */
    private function migrateExistingGames() {
        try {
            // Check if there are games without API tokens
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM games WHERE api_token IS NULL OR api_token = ''");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                // Update games without API tokens
                $stmt = $this->db->prepare("SELECT id FROM games WHERE api_token IS NULL OR api_token = ''");
                $stmt->execute();
                $games = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($games as $gameId) {
                    $apiToken = $this->generateApiToken();
                    $updateStmt = $this->db->prepare("UPDATE games SET api_token = ? WHERE id = ?");
                    $updateStmt->execute([$apiToken, $gameId]);
                }
                
                if ($this->debug) {
                    error_log("GameManager: Migrated $count games with API tokens");
                }
            }
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Migration failed - " . $e->getMessage());
            }
        }
    }

    private function migrateForAnonymousGames() {
        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            
            if ($driver === 'mysql') {
                // Check if user_id column allows NULL
                $stmt = $this->db->prepare("SHOW COLUMNS FROM games LIKE 'user_id'");
                $stmt->execute();
                $column = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($column && $column['Null'] === 'NO') {
                    // Modify column to allow NULL
                    $this->db->exec("ALTER TABLE games MODIFY COLUMN user_id INT NULL");
                    
                    // Drop foreign key constraint if it exists
                    try {
                        $this->db->exec("ALTER TABLE games DROP FOREIGN KEY games_ibfk_1");
                    } catch (Exception $e) {
                        // Foreign key might not exist, ignore error
                    }
                    
                    if ($this->debug) {
                        error_log("GameManager: Updated user_id column to allow NULL for anonymous games");
                    }
                }
            } else {
                // SQLite doesn't support ALTER COLUMN, so we need to recreate the table
                // But since we're using CREATE TABLE IF NOT EXISTS, it should be fine
                if ($this->debug) {
                    error_log("GameManager: SQLite schema updated for anonymous games");
                }
            }
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Anonymous games migration failed - " . $e->getMessage());
            }
        }
    }
    
    /**
     * Generate a unique API token for a game
     */
    private function generateApiToken() {
        do {
            // Generate a secure random token
            $token = 'game_' . bin2hex(random_bytes(24)); // 48 character token with prefix
            
            // Check if token already exists in json_structure field
            $stmt = $this->db->prepare("SELECT game_id FROM games WHERE json_structure LIKE ?");
            $stmt->execute(['%"api_token":"' . $token . '"%']);
            $exists = $stmt->fetch();
            
        } while ($exists); // Keep generating until we get a unique token
        
        return $token;
    }
    
    /**
     * Get user ID from token
     */
    private function getUserIdFromToken($token) {
        try {
            $stmt = $this->db->prepare("SELECT user_id FROM users WHERE api_token = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ? $user['user_id'] : null;
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Error getting user ID - " . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Create a new game
     * 
     * @param string|null $token User authentication token (null for anonymous creation if allowed)
     * @param array $gameData Game data including name, description, etc.
     * @return array API response with created game data or error
     */
    public function createGame($token, $gameData) {
        try {
            $userId = null;
            $isAnonymous = false;
            
            // If token is provided, validate it and get user ID
            if ($token !== null) {
                $userId = $this->getUserIdFromToken($token);
                if (!$userId) {
                    return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_INVALID_TOKEN);
                }
            } else {
                // Check if anonymous creation is allowed
                $allowAnonymous = defined('ALLOW_ANONYMOUS_GAME_CREATION') && ALLOW_ANONYMOUS_GAME_CREATION;
                if (!$allowAnonymous) {
                    return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_REQUIRED, 'Authentication is required to create games');
                }
                $isAnonymous = true;
            }
            
            $name = $gameData['name'] ?? 'Untitled Game';
            $description = $gameData['description'] ?? '';
            $gameType = $gameData['game_type'] ?? 'multiplayer';
            $maxPlayers = min(100, max(1, (int)($gameData['max_players'] ?? 10))); // Ensure reasonable limits
            
            // Generate unique API token for the game
            $apiToken = $this->generateApiToken();
            
            // Prepare game data for storage - match actual database schema
            $jsonStructure = json_encode([
                'api_token' => $apiToken,
                'game_type' => $gameType,
                'max_players' => $maxPlayers,
                'status' => 'active',
                'created_by' => $isAnonymous ? 'anonymous' : 'user:' . $userId,
                'version' => '1.0',
                'created_at' => date('c')
            ]);
            
            $jsonProperties = json_encode([
                'settings' => $gameData['settings'] ?? [],
                'game_type' => $gameType,
                'max_players' => $maxPlayers,
                'status' => 'active',
                'api_token' => $apiToken
            ]);
            
            // Use prepared statement matching actual database schema
            $stmt = $this->db->prepare("
                INSERT INTO games (
                    user_id, name, description, 
                    json_structure, json_properties,
                    json_rooms, json_communities, json_chats,
                    is_active
                ) VALUES (?, ?, ?, ?, ?, '[]', '[]', '[]', 1)
            ");
            
            $result = $stmt->execute([
                $userId, // Can be null for anonymous games
                $name,
                $description,
                $jsonStructure,
                $jsonProperties
            ]);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Database insert failed: " . ($errorInfo[2] ?? 'Unknown error'));
            }
            
            $gameId = $this->db->lastInsertId();
            
            if ($this->debug) {
                $logMessage = $isAnonymous 
                    ? "Created anonymous game ID $gameId"
                    : "Created game ID $gameId for user $userId";
                error_log("GameManager: $logMessage with API token: $apiToken");
            }
            
            return ErrorCodes::createSuccessResponse([
                'game_id' => (int)$gameId,
                'name' => $name,
                'description' => $description,
                'game_type' => $gameType,
                'max_players' => $maxPlayers,
                'status' => 'active',
                'api_token' => $apiToken,
                'is_anonymous' => $isAnonymous,
                'created_at' => date('c')
            ], 'Game created successfully');
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Create game error - " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
            }
            return ErrorCodes::createErrorResponse(
                ErrorCodes::SYS_INTERNAL_ERROR,
                'Failed to create game: ' . ($this->debug ? $e->getMessage() : 'Internal server error')
            );
        }
    }

    /**
     * @deprecated Use createGame() instead. This method is kept for backward compatibility.
     */
    public function createGameAnonymous($gameData) {
        // For backward compatibility, route to createGame with null token
        return $this->createGame(null, $gameData);
    }
    
    /**
     * Get a specific game by ID
     */
    public function getGame($token, $gameId) {
        try {
            $userId = $this->getUserIdFromToken($token);
            if (!$userId) {
                return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_INVALID_TOKEN);
            }
            
            $stmt = $this->db->prepare("
                SELECT id, name, description, game_type, max_players, status, api_token, created_at, updated_at 
                FROM games 
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([$gameId, $userId]);
            $game = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$game) {
                return ErrorCodes::createErrorResponse(ErrorCodes::GAME_NOT_FOUND);
            }
            
            // Convert data types
            $game['id'] = (int)$game['id'];
            $game['max_players'] = (int)$game['max_players'];
            
            if ($this->debug) {
                error_log("GameManager: Retrieved game $gameId for user $userId");
            }
            
            return ErrorCodes::createSuccessResponse($game, 'Game retrieved successfully');
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Get game error - " . $e->getMessage());
            }
            return ErrorCodes::createErrorResponse(ErrorCodes::SYS_INTERNAL_ERROR);
        }
    }
    
    /**
     * Get user's games
     */
    public function getGames($token) {
        try {
            $userId = $this->getUserIdFromToken($token);
            if (!$userId) {
                return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_INVALID_TOKEN);
            }
            
            $stmt = $this->db->prepare("
                SELECT game_id, user_id, name, description, json_structure, is_active, created_at, updated_at 
                FROM games 
                WHERE user_id = ? 
                ORDER BY created_at DESC
            ");
            
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $games = [];
            foreach ($rows as $row) {
                $json = [];
                if (!empty($row['json_structure'])) {
                    $decoded = json_decode($row['json_structure'], true);
                    if (is_array($decoded)) {
                        $json = $decoded;
                    }
                }

                $games[] = [
                    'game_id' => (int)$row['game_id'],
                    'user_id' => $row['user_id'] !== null ? (int)$row['user_id'] : null,
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'game_type' => $json['game_type'] ?? 'multiplayer',
                    'max_players' => (int)($json['max_players'] ?? 10),
                    'status' => $json['status'] ?? 'active',
                    'api_token' => $json['api_token'] ?? 'No token',
                    'is_active' => (bool)$row['is_active'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                    'created_by' => 'User ' . $userId
                ];
            }

            if ($this->debug) {
                error_log("GameManager: Retrieved " . count($games) . " games for user $userId");
            }

            return ErrorCodes::createSuccessResponse([
                'games' => $games,
                'total' => count($games)
            ], 'Games retrieved successfully');
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Get games error - " . $e->getMessage());
            }
            return ErrorCodes::createErrorResponse(ErrorCodes::SYS_INTERNAL_ERROR);
        }
    }

    /**
     * @deprecated This method is no longer supported. Use getGames() instead.
     */
    public function getAllGames() {
        if ($this->debug) {
            error_log("GameManager: Deprecated method getAllGames() called");
        }
        return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_REQUIRED, 'This endpoint is no longer available. Authentication is required.');
    }
    
    /**
     * Get user statistics
     */
    public function getUserStats($token) {
        try {
            $userId = $this->getUserIdFromToken($token);
            if (!$userId) {
                return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_INVALID_TOKEN);
            }
            
            // Get game count
            $stmt = $this->db->prepare("SELECT COUNT(*) as game_count FROM games WHERE user_id = ?");
            $stmt->execute([$userId]);
            $gameCount = $stmt->fetch(PDO::FETCH_ASSOC)['game_count'];
            
            // Get API usage
            $stmt = $this->db->prepare("SELECT api_calls_used, api_calls_limit, plan_type FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userStats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($this->debug) {
                error_log("GameManager: Retrieved stats for user $userId");
            }
            
            return ErrorCodes::createSuccessResponse([
                'games_created' => (int)$gameCount,
                'api_calls_used' => (int)$userStats['api_calls_used'],
                'api_calls_limit' => (int)$userStats['api_calls_limit'],
                'plan_type' => $userStats['plan_type'],
                'usage_percentage' => round(($userStats['api_calls_used'] / $userStats['api_calls_limit']) * 100, 2)
            ], 'User statistics retrieved successfully');
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Get user stats error - " . $e->getMessage());
            }
            return ErrorCodes::createErrorResponse(ErrorCodes::SYS_INTERNAL_ERROR);
        }
    }
    
    /**
     * Update game
     */
    public function updateGame($token, $gameId, $gameData) {
        try {
            $userId = $this->getUserIdFromToken($token);
            if (!$userId) {
                return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_INVALID_TOKEN);
            }
            
            // Verify game ownership
            $stmt = $this->db->prepare("SELECT id FROM games WHERE id = ? AND user_id = ?");
            $stmt->execute([$gameId, $userId]);
            if (!$stmt->fetch()) {
                return ErrorCodes::createErrorResponse(ErrorCodes::GAME_NOT_FOUND);
            }
            
            // Update game
            $updates = [];
            $params = [];
            
            if (isset($gameData['name'])) {
                $updates[] = "name = ?";
                $params[] = $gameData['name'];
            }
            
            if (isset($gameData['description'])) {
                $updates[] = "description = ?";
                $params[] = $gameData['description'];
            }
            
            if (isset($gameData['max_players'])) {
                $updates[] = "max_players = ?";
                $params[] = $gameData['max_players'];
            }
            
            if (isset($gameData['status'])) {
                $updates[] = "status = ?";
                $params[] = $gameData['status'];
            }
            
            if (empty($updates)) {
                return ErrorCodes::createErrorResponse(ErrorCodes::API_INVALID_INPUT, 'No valid fields to update');
            }
            
            $params[] = $gameId;
            $sql = "UPDATE games SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return ErrorCodes::createSuccessResponse(['game_id' => $gameId], 'Game updated successfully');
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Update game error - " . $e->getMessage());
            }
            return ErrorCodes::createErrorResponse(ErrorCodes::SYS_INTERNAL_ERROR);
        }
    }
    
    /**
     * Delete game
     */
    public function deleteGame($token, $gameId) {
        try {
            $userId = $this->getUserIdFromToken($token);
            if (!$userId) {
                return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_INVALID_TOKEN);
            }
            
            $stmt = $this->db->prepare("DELETE FROM games WHERE id = ? AND user_id = ?");
            $stmt->execute([$gameId, $userId]);
            
            if ($stmt->rowCount() === 0) {
                return ErrorCodes::createErrorResponse(ErrorCodes::GAME_NOT_FOUND);
            }
            
            return ErrorCodes::createSuccessResponse(['game_id' => $gameId], 'Game deleted successfully');
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Delete game error - " . $e->getMessage());
            }
            return ErrorCodes::createErrorResponse(ErrorCodes::SYS_INTERNAL_ERROR);
        }
    }
    
    // Stub methods for advanced features
    public function getSystemStats($token) {
        return ErrorCodes::createSuccessResponse([
            'total_games' => 0,
            'active_players' => 0,
            'server_status' => 'online'
        ], 'System stats retrieved');
    }
    
    public function findMatch($token, $matchData) {
        return ErrorCodes::createErrorResponse(ErrorCodes::API_NOT_IMPLEMENTED, 'Matchmaking not yet implemented');
    }
    
    public function createTrigger($token, $triggerData) {
        return ErrorCodes::createErrorResponse(ErrorCodes::API_NOT_IMPLEMENTED, 'Triggers not yet implemented');
    }
    
    public function createTimer($token, $timerData) {
        return ErrorCodes::createErrorResponse(ErrorCodes::API_NOT_IMPLEMENTED, 'Timers not yet implemented');
    }

    /**
     * Save Blockly puzzle logic for a game
     * @param string $token User authentication token
     * @param int $gameId Game ID
     * @param array $logicData Logic data containing JSON workspace
     * @return array API response
     */
    public function saveLogic($token, $gameId, $logicData) {
        try {
            $userId = $this->getUserIdFromToken($token);
            if (!$userId) {
                return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_INVALID_TOKEN);
            }

            // Verify game ownership
            $stmt = $this->db->prepare("SELECT id FROM games WHERE id = ? AND user_id = ?");
            $stmt->execute([$gameId, $userId]);
            if (!$stmt->fetch()) {
                return ErrorCodes::createErrorResponse(ErrorCodes::GAME_NOT_FOUND);
            }

            // Validate logic JSON
            if (!isset($logicData['logic']) || !is_array($logicData['logic'])) {
                return ErrorCodes::createErrorResponse(ErrorCodes::API_INVALID_INPUT, 'Invalid logic JSON format');
            }

            $logicJson = json_encode($logicData['logic']);
            if ($logicJson === false) {
                return ErrorCodes::createErrorResponse(ErrorCodes::API_INVALID_INPUT, 'Invalid JSON data');
            }

            // Check size limits (max 10MB for logic)
            $sizeBytes = strlen($logicJson);
            if ($sizeBytes > 10 * 1024 * 1024) {
                return ErrorCodes::createErrorResponse(ErrorCodes::API_INVALID_INPUT, 'Logic JSON too large (max 10MB)');
            }

            // Update game with logic
            $stmt = $this->db->prepare("
                UPDATE games 
                SET logic_json = ?, 
                    logic_size_bytes = ?, 
                    logic_updated_at = CURRENT_TIMESTAMP,
                    logic_version = COALESCE(logic_version, 0) + 1
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([$logicJson, $sizeBytes, $gameId, $userId]);

            // Save version if versioning is enabled
            if (isset($logicData['save_version']) && $logicData['save_version']) {
                $this->saveLogicVersion($gameId, $userId, $logicJson, $sizeBytes, $logicData['version_description'] ?? null);
            }

            return ErrorCodes::createSuccessResponse([
                'game_id' => $gameId,
                'logic_size_bytes' => $sizeBytes,
                'saved_at' => date('Y-m-d H:i:s')
            ], 'Logic saved successfully');

        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Save logic error - " . $e->getMessage());
            }
            return ErrorCodes::createErrorResponse(ErrorCodes::SYS_INTERNAL_ERROR);
        }
    }

    /**
     * Get Blockly puzzle logic for a game
     * @param string $token User authentication token
     * @param int $gameId Game ID
     * @return array API response with logic JSON
     */
    public function getLogic($token, $gameId) {
        try {
            $userId = $this->getUserIdFromToken($token);
            if (!$userId) {
                return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_INVALID_TOKEN);
            }

            // Get game logic
            $stmt = $this->db->prepare("
                SELECT logic_json, logic_size_bytes, logic_version, logic_updated_at 
                FROM games 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$gameId, $userId]);
            $game = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$game) {
                return ErrorCodes::createErrorResponse(ErrorCodes::GAME_NOT_FOUND);
            }

            $logicJson = $game['logic_json'] ?? '{}';
            $logic = json_decode($logicJson, true);

            return ErrorCodes::createSuccessResponse([
                'game_id' => $gameId,
                'logic' => $logic,
                'logic_size_bytes' => $game['logic_size_bytes'] ?? 0,
                'logic_version' => $game['logic_version'] ?? 1,
                'logic_updated_at' => $game['logic_updated_at']
            ], 'Logic retrieved successfully');

        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Get logic error - " . $e->getMessage());
            }
            return ErrorCodes::createErrorResponse(ErrorCodes::SYS_INTERNAL_ERROR);
        }
    }

    /**
     * Export logic as downloadable JSON file
     * @param string $token User authentication token
     * @param int $gameId Game ID
     * @return array API response with file data
     */
    public function exportLogic($token, $gameId) {
        try {
            $logicResponse = $this->getLogic($token, $gameId);
            if (!$logicResponse['success']) {
                return $logicResponse;
            }

            $logic = $logicResponse['data']['logic'];
            $gameName = $this->getGameName($gameId);
            
            $exportData = [
                'game_id' => $gameId,
                'game_name' => $gameName,
                'logic' => $logic,
                'exported_at' => date('Y-m-d H:i:s'),
                'version' => $logicResponse['data']['logic_version']
            ];

            return ErrorCodes::createSuccessResponse([
                'filename' => "game_{$gameId}_logic.json",
                'content' => json_encode($exportData, JSON_PRETTY_PRINT),
                'content_type' => 'application/json'
            ], 'Logic exported successfully');

        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Export logic error - " . $e->getMessage());
            }
            return ErrorCodes::createErrorResponse(ErrorCodes::SYS_INTERNAL_ERROR);
        }
    }

    /**
     * Import logic from JSON file
     * @param string $token User authentication token
     * @param int $gameId Game ID
     * @param array $importData Import data with logic JSON
     * @return array API response
     */
    public function importLogic($token, $gameId, $importData) {
        try {
            $userId = $this->getUserIdFromToken($token);
            if (!$userId) {
                return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_INVALID_TOKEN);
            }

            // Validate import data
            if (!isset($importData['logic']) || !is_array($importData['logic'])) {
                return ErrorCodes::createErrorResponse(ErrorCodes::API_INVALID_INPUT, 'Invalid import data format');
            }

            // Save the imported logic
            return $this->saveLogic($token, $gameId, [
                'logic' => $importData['logic'],
                'save_version' => true,
                'version_description' => 'Imported from file'
            ]);

        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Import logic error - " . $e->getMessage());
            }
            return ErrorCodes::createErrorResponse(ErrorCodes::SYS_INTERNAL_ERROR);
        }
    }

    /**
     * Simulate/validate logic without saving
     * @param string $token User authentication token
     * @param int $gameId Game ID
     * @param array $logicData Logic data to simulate
     * @return array API response with simulation results
     */
    public function simulateLogic($token, $gameId, $logicData) {
        try {
            $userId = $this->getUserIdFromToken($token);
            if (!$userId) {
                return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_INVALID_TOKEN);
            }

            // Verify game ownership
            $stmt = $this->db->prepare("SELECT id FROM games WHERE id = ? AND user_id = ?");
            $stmt->execute([$gameId, $userId]);
            if (!$stmt->fetch()) {
                return ErrorCodes::createErrorResponse(ErrorCodes::GAME_NOT_FOUND);
            }

            // Validate logic JSON
            if (!isset($logicData['logic']) || !is_array($logicData['logic'])) {
                return ErrorCodes::createErrorResponse(ErrorCodes::API_INVALID_INPUT, 'Invalid logic JSON format');
            }

            $startTime = microtime(true);
            $simulationResult = $this->performLogicSimulation($logicData['logic']);
            $executionTime = round((microtime(true) - $startTime) * 1000);

            // Save simulation record
            $this->saveSimulationRecord($gameId, $userId, $logicData['logic'], $simulationResult, $executionTime);

            return ErrorCodes::createSuccessResponse([
                'game_id' => $gameId,
                'simulation_result' => $simulationResult,
                'execution_time_ms' => $executionTime,
                'validated_at' => date('Y-m-d H:i:s')
            ], 'Logic simulation completed');

        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Simulate logic error - " . $e->getMessage());
            }
            return ErrorCodes::createErrorResponse(ErrorCodes::SYS_INTERNAL_ERROR);
        }
    }

    /**
     * Save logic version for versioning
     * @param int $gameId Game ID
     * @param int $userId User ID
     * @param string $logicJson Logic JSON string
     * @param int $sizeBytes Size in bytes
     * @param string|null $description Version description
     */
    private function saveLogicVersion($gameId, $userId, $logicJson, $sizeBytes, $description = null) {
        try {
            // Get current version number
            $stmt = $this->db->prepare("SELECT MAX(version_number) as max_version FROM game_logic_versions WHERE game_id = ?");
            $stmt->execute([$gameId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextVersion = ($result['max_version'] ?? 0) + 1;

            // Save version
            $stmt = $this->db->prepare("
                INSERT INTO game_logic_versions (game_id, user_id, logic_json, logic_size_bytes, version_number, description)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$gameId, $userId, $logicJson, $sizeBytes, $nextVersion, $description]);

        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Save logic version error - " . $e->getMessage());
            }
        }
    }

    /**
     * Perform basic logic simulation/validation
     * @param array $logic Logic blocks to simulate
     * @return array Simulation results
     */
    private function performLogicSimulation($logic) {
        $result = [
            'valid' => true,
            'blocks_count' => 0,
            'categories_used' => [],
            'warnings' => [],
            'errors' => []
        ];

        try {
            // Basic validation of Blockly workspace structure
            if (isset($logic['blocks']) && is_array($logic['blocks'])) {
                $result['blocks_count'] = count($logic['blocks']);
                
                foreach ($logic['blocks'] as $block) {
                    if (isset($block['type'])) {
                        $category = $this->getBlockCategory($block['type']);
                        if (!in_array($category, $result['categories_used'])) {
                            $result['categories_used'][] = $category;
                        }
                    }
                }
            }

            // Add simulation warnings/suggestions
            if ($result['blocks_count'] === 0) {
                $result['warnings'][] = 'No blocks found in workspace';
            }

            if ($result['blocks_count'] > 100) {
                $result['warnings'][] = 'Large number of blocks may impact performance';
            }

        } catch (Exception $e) {
            $result['valid'] = false;
            $result['errors'][] = 'Simulation error: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Get block category from block type
     * @param string $blockType Block type identifier
     * @return string Category name
     */
    private function getBlockCategory($blockType) {
        $categoryMap = [
            'trigger_' => 'Main Puzzles',
            'timer_' => 'Main Puzzles',
            'data_' => 'Data Puzzles',
            'logic_' => 'Logic',
            'math_' => 'Operators',
            'function_' => 'Functions',
            'notification_' => 'Notifications',
            'matchmaking_' => 'Matchmaking',
            'room_' => 'Room',
            'chat_' => 'Chat'
        ];

        foreach ($categoryMap as $prefix => $category) {
            if (strpos($blockType, $prefix) === 0) {
                return $category;
            }
        }

        return 'Unknown';
    }

    /**
     * Save simulation record
     * @param int $gameId Game ID
     * @param int $userId User ID
     * @param array $logic Logic data
     * @param array $result Simulation result
     * @param int $executionTime Execution time in ms
     */
    private function saveSimulationRecord($gameId, $userId, $logic, $result, $executionTime) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO game_logic_simulations (game_id, user_id, logic_json, simulation_result, status, execution_time_ms, completed_at)
                VALUES (?, ?, ?, ?, 'completed', ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                $gameId, 
                $userId, 
                json_encode($logic), 
                json_encode($result), 
                $executionTime
            ]);
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Save simulation record error - " . $e->getMessage());
            }
        }
    }

    /**
     * Get game name for export
     * @param int $gameId Game ID
     * @return string Game name
     */
    private function getGameName($gameId) {
        try {
            $stmt = $this->db->prepare("SELECT name FROM games WHERE id = ?");
            $stmt->execute([$gameId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['name'] ?? "Game_{$gameId}";
        } catch (Exception $e) {
            return "Game_{$gameId}";
        }
    }
}
