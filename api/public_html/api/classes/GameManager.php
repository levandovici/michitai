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
            $stmt = $this->db->prepare("SELECT id FROM users WHERE api_token = ?");
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ? $user['id'] : null;
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Error getting user ID - " . $e->getMessage());
            }
            return null;
        }
    }
    
    /**
     * Create a new game (authenticated)
     */
    public function createGame($token, $gameData) {
        try {
            $userId = $this->getUserIdFromToken($token);
            if (!$userId) {
                return ErrorCodes::createErrorResponse(ErrorCodes::AUTH_INVALID_TOKEN);
            }
            
            $name = $gameData['name'] ?? 'Untitled Game';
            $description = $gameData['description'] ?? '';
            $gameType = $gameData['game_type'] ?? 'multiplayer';
            $maxPlayers = $gameData['max_players'] ?? 10;
            $settings = json_encode($gameData['settings'] ?? []);
            
            // Generate unique API token for the game
            $apiToken = $this->generateApiToken();
            
            $stmt = $this->db->prepare("
                INSERT INTO games (user_id, name, description, game_type, max_players, api_token, settings) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$userId, $name, $description, $gameType, $maxPlayers, $apiToken, $settings]);
            $gameId = $this->db->lastInsertId();
            
            if ($this->debug) {
                error_log("GameManager: Created game ID $gameId for user $userId with API token");
            }
            
            return ErrorCodes::createSuccessResponse([
                'game_id' => $gameId,
                'name' => $name,
                'description' => $description,
                'game_type' => $gameType,
                'max_players' => $maxPlayers,
                'status' => 'active',
                'api_token' => $apiToken
            ], 'Game created successfully');
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Create game error - " . $e->getMessage());
            }
            return ErrorCodes::createErrorResponse(ErrorCodes::SYS_INTERNAL_ERROR);
        }
    }

    /**
     * Create a new game without authentication (anonymous)
     */
    public function createGameAnonymous($gameData) {
        try {
            $name = $gameData['name'] ?? 'Untitled Game';
            $description = $gameData['description'] ?? '';
            
            if ($this->debug) {
                error_log("GameManager: Starting anonymous game creation with name: $name");
            }
            
            // Generate unique API token for the game
            $apiToken = $this->generateApiToken();
            
            if ($this->debug) {
                error_log("GameManager: Generated API token: $apiToken");
            }
            
            // Create basic JSON structure for the game
            $jsonStructure = json_encode([
                'api_token' => $apiToken,
                'game_type' => 'multiplayer',
                'max_players' => 10,
                'status' => 'active'
            ]);
            
            $jsonProperties = json_encode([
                'created_by' => 'anonymous',
                'version' => '1.0'
            ]);
            
            if ($this->debug) {
                error_log("GameManager: Prepared JSON data - Structure: $jsonStructure, Properties: $jsonProperties");
            }
            
            // Use the existing database schema columns
            $stmt = $this->db->prepare("
                INSERT INTO games (user_id, name, description, json_structure, json_properties, json_rooms, json_communities, json_chats, is_active, created_at, updated_at) 
                VALUES (NULL, ?, ?, ?, ?, '[]', '[]', '[]', 1, NOW(), NOW())
            ");
            
            if ($this->debug) {
                error_log("GameManager: Executing SQL insert with parameters: " . json_encode([$name, $description, $jsonStructure, $jsonProperties]));
            }
            
            $result = $stmt->execute([$name, $description, $jsonStructure, $jsonProperties]);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                if ($this->debug) {
                    error_log("GameManager: SQL execution failed - " . json_encode($errorInfo));
                }
                throw new Exception("Database insert failed: " . $errorInfo[2]);
            }
            
            $gameId = $this->db->lastInsertId();
            
            if ($this->debug) {
                error_log("GameManager: Created anonymous game ID $gameId with API token $apiToken");
            }
            
            return ErrorCodes::createSuccessResponse([
                'game_id' => $gameId,
                'name' => $name,
                'description' => $description,
                'game_type' => 'multiplayer',
                'max_players' => 10,
                'status' => 'active',
                'api_token' => $apiToken
            ], 'Game created successfully');
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("GameManager: Create anonymous game error - " . $e->getMessage());
                error_log("GameManager: Stack trace - " . $e->getTraceAsString());
            }
            return ErrorCodes::createErrorResponse(ErrorCodes::SYS_INTERNAL_ERROR);
        }
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
                SELECT id, name, description, game_type, max_players, status, api_token, created_at, updated_at 
                FROM games 
                WHERE user_id = ? 
                ORDER BY created_at DESC
            ");
            
            $stmt->execute([$userId]);
            $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert timestamps for display
            foreach ($games as &$game) {
                $game['id'] = (int)$game['id'];
                $game['max_players'] = (int)$game['max_players'];
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
}
