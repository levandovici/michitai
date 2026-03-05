-- Player Updates Queue and Matchmaking Database Schema
-- Add these tables to your existing database

-- Table for player update queues (host-to-player communication)
CREATE TABLE IF NOT EXISTS player_updates (
    update_id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    room_id VARCHAR(36) NOT NULL,
    from_player_id VARCHAR(36) NOT NULL,
    target_player_id VARCHAR(36) NOT NULL,
    type VARCHAR(50) NOT NULL COMMENT 'play_animation, spawn_effect, sync_state, etc.',
    data_json JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivered_at TIMESTAMP NULL COMMENT 'When the update was delivered to the target player',
    status ENUM('pending', 'delivered', 'read') DEFAULT 'pending',
    
    INDEX idx_target_player_updates (target_player_id, status, created_at),
    INDEX idx_room_updates (room_id, created_at),
    INDEX idx_from_player (from_player_id, created_at),
    
    FOREIGN KEY (room_id) REFERENCES game_rooms(room_id) ON DELETE CASCADE,
    FOREIGN KEY (from_player_id) REFERENCES room_players(player_id) ON DELETE CASCADE,
    FOREIGN KEY (target_player_id) REFERENCES room_players(player_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for matchmaking lobbies
CREATE TABLE IF NOT EXISTS matchmaking (
    matchmaking_id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    host_player_id VARCHAR(36) NOT NULL,
    max_players INT NOT NULL DEFAULT 4 CHECK (max_players BETWEEN 2 AND 16),
    strict_full BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Game can start only when full',
    extra_json_string JSON NULL COMMENT 'Host-defined criteria (rank, level, etc.)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_heartbeat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_started BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Game has been started from this lobby',
    started_at TIMESTAMP NULL,
    
    INDEX idx_host_lobby (host_player_id, is_started),
    INDEX idx_active_lobbies (is_started, created_at),
    INDEX idx_heartbeat (last_heartbeat),
    
    FOREIGN KEY (host_player_id) REFERENCES room_players(player_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for matchmaking players (junction table)
CREATE TABLE IF NOT EXISTS matchmaking_players (
    matchmaking_id VARCHAR(36) NOT NULL,
    player_id VARCHAR(36) NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_heartbeat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'disconnected') DEFAULT 'active',
    
    PRIMARY KEY (matchmaking_id, player_id),
    INDEX idx_player_lobbies (player_id, status),
    INDEX idx_lobby_players (matchmaking_id, status),
    
    FOREIGN KEY (matchmaking_id) REFERENCES matchmaking(matchmaking_id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES room_players(player_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for matchmaking join requests (optional - for host approval system)
CREATE TABLE IF NOT EXISTS matchmaking_requests (
    request_id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    matchmaking_id VARCHAR(36) NOT NULL,
    player_id VARCHAR(36) NOT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    responded_at TIMESTAMP NULL,
    responded_by VARCHAR(36) NULL COMMENT 'Host player who approved/rejected',
    
    INDEX idx_lobby_requests (matchmaking_id, status),
    INDEX idx_player_requests (player_id, status),
    
    FOREIGN KEY (matchmaking_id) REFERENCES matchmaking(matchmaking_id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES room_players(player_id) ON DELETE CASCADE,
    FOREIGN KEY (responded_by) REFERENCES room_players(player_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance if they don't exist
ALTER TABLE game_rooms ADD INDEX IF NOT EXISTS idx_host_player (host_player_id);
ALTER TABLE room_players ADD INDEX IF NOT EXISTS idx_player_active (player_id, last_heartbeat);
