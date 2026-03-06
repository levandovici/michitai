CREATE DATABASE levandovici_api;

USE levandovici_api;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_name VARCHAR(100) NOT NULL,
    api_key VARCHAR(36) UNIQUE NOT NULL,
    api_private_key VARCHAR(36) UNIQUE NOT NULL,
    game_data JSON DEFAULT (JSON_OBJECT()),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_api_key (api_key),
    INDEX idx_api_private_key (api_private_key)
);

CREATE TABLE game_players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    player_name VARCHAR(100) NOT NULL,
    private_key VARCHAR(36) UNIQUE NOT NULL,
    player_data JSON DEFAULT (JSON_OBJECT()),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES api_keys(id) ON DELETE CASCADE,
    INDEX idx_private_key (private_key),
    INDEX idx_game_private (game_id, private_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE verification_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(36) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS game_rooms (
    room_id VARCHAR(36) PRIMARY KEY,
    room_name VARCHAR(255) NOT NULL,
    host_player_id VARCHAR(36) NULL,
    password VARCHAR(255) NULL,
    max_players INT DEFAULT 6,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS room_players (
    player_id VARCHAR(36) PRIMARY KEY,
    room_id VARCHAR(36) NOT NULL,
    player_name VARCHAR(100) NOT NULL,
    is_host BOOLEAN DEFAULT FALSE,
    is_online BOOLEAN DEFAULT TRUE,
    last_heartbeat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES game_rooms(room_id) ON DELETE CASCADE,
    INDEX idx_room_id (room_id),
    INDEX idx_last_heartbeat (last_heartbeat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS action_queue (
    action_id VARCHAR(36) PRIMARY KEY,
    room_id VARCHAR(36) NOT NULL,
    player_id VARCHAR(36) NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    request_data JSON,
    response_data JSON,
    status ENUM('pending','processing','completed','failed','read') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (room_id) REFERENCES game_rooms(room_id) ON DELETE CASCADE,
    INDEX idx_room_status (room_id, status),
    INDEX idx_player_status (player_id, status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE game_rooms
    ADD CONSTRAINT fk_host_player
    FOREIGN KEY (host_player_id) REFERENCES room_players(player_id)
    ON DELETE SET NULL;


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
    host_player_id INT NOT NULL,
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
    
    FOREIGN KEY (host_player_id) REFERENCES game_players(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for matchmaking players (junction table)
CREATE TABLE IF NOT EXISTS matchmaking_players (
    matchmaking_id VARCHAR(36) NOT NULL,
    player_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_heartbeat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'disconnected') DEFAULT 'active',
    
    PRIMARY KEY (matchmaking_id, player_id),
    INDEX idx_player_lobbies (player_id, status),
    INDEX idx_lobby_players (matchmaking_id, status),
    
    FOREIGN KEY (matchmaking_id) REFERENCES matchmaking(matchmaking_id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES game_players(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for matchmaking join requests (optional - for host approval system)
CREATE TABLE IF NOT EXISTS matchmaking_requests (
    request_id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    matchmaking_id VARCHAR(36) NOT NULL,
    player_id INT NOT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    responded_at TIMESTAMP NULL,
    responded_by INT NULL COMMENT 'Host player who approved/rejected',
    
    INDEX idx_lobby_requests (matchmaking_id, status),
    INDEX idx_player_requests (player_id, status),
    
    FOREIGN KEY (matchmaking_id) REFERENCES matchmaking(matchmaking_id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES game_players(id) ON DELETE CASCADE,
    FOREIGN KEY (responded_by) REFERENCES game_players(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance if they don't exist
ALTER TABLE game_rooms ADD INDEX IF NOT EXISTS idx_host_player (host_player_id);
ALTER TABLE room_players ADD INDEX IF NOT EXISTS idx_player_active (player_id, last_heartbeat);
