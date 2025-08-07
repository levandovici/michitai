-- Multiplayer API Web Constructor Database Schema
-- Database: multiplayer_api
-- Compatible with MySQL 8.x

CREATE DATABASE IF NOT EXISTS multiplayer_api;
USE multiplayer_api;

-- Users table with plan management and API tracking
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- bcrypt hashed
    api_token VARCHAR(36) UNIQUE NOT NULL, -- UUIDv4
    plan_type ENUM('Free', 'Standard', 'Pro') DEFAULT 'Free',
    memory_used_mb DECIMAL(10,2) DEFAULT 0.00,
    memory_limit_mb DECIMAL(10,2) DEFAULT 250.00, -- Free plan default
    api_calls_today INT DEFAULT 0,
    paypal_customer_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_api_token (api_token),
    INDEX idx_email (email)
);

-- Subscriptions table for payment tracking
CREATE TABLE subscriptions (
    subscription_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    paypal_subscription_id VARCHAR(255) NULL,
    plan_type ENUM('Free', 'Standard', 'Pro') NOT NULL,
    status ENUM('active', 'canceled', 'past_due', 'pending') DEFAULT 'pending',
    amount DECIMAL(10,2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'USD',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_paypal_subscription (paypal_subscription_id),
    INDEX idx_status (status)
);

-- Games table with JSON structure for flexibility
CREATE TABLE games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    json_structure JSON NOT NULL, -- Game structure definition
    json_properties JSON NOT NULL, -- Game properties and settings
    json_rooms JSON DEFAULT '[]', -- Room configurations
    json_communities JSON DEFAULT '[]', -- Community settings
    json_chats JSON DEFAULT '[]', -- Chat configurations
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_active (is_active)
);

-- Players table with GUID identifiers
CREATE TABLE players (
    player_id VARCHAR(36) PRIMARY KEY, -- GUID
    game_id INT NOT NULL,
    password_guid VARCHAR(36) NOT NULL, -- GUID for player authentication
    json_data JSON DEFAULT '{}', -- Player progress and data
    is_online BOOLEAN DEFAULT FALSE,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    INDEX idx_game_id (game_id),
    INDEX idx_online (is_online),
    INDEX idx_last_activity (last_activity)
);

-- Rooms table for game instances
CREATE TABLE rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    max_players INT DEFAULT 10,
    current_players INT DEFAULT 0,
    json_data JSON DEFAULT '{}', -- Room state and configuration
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    INDEX idx_game_id (game_id),
    INDEX idx_active (is_active)
);

-- Communities table for player groups
CREATE TABLE communities (
    community_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    json_data JSON DEFAULT '{}', -- Community settings and data
    privileges JSON DEFAULT '{}', -- Member privileges and roles
    member_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    INDEX idx_game_id (game_id),
    INDEX idx_active (is_active)
);

-- Chats table for messaging
CREATE TABLE chats (
    chat_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    room_id INT NULL, -- NULL for global chat
    community_id INT NULL, -- NULL for non-community chat
    name VARCHAR(255) NOT NULL,
    json_messages JSON DEFAULT '[]', -- Message history
    message_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id) ON DELETE SET NULL,
    FOREIGN KEY (community_id) REFERENCES communities(community_id) ON DELETE SET NULL,
    INDEX idx_game_id (game_id),
    INDEX idx_room_id (room_id),
    INDEX idx_community_id (community_id),
    INDEX idx_active (is_active)
);

-- Triggers table for game logic
CREATE TABLE triggers (
    trigger_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    parameters JSON DEFAULT '{}', -- Trigger parameters and conditions
    action_type ENUM('timer', 'event', 'condition', 'function') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    INDEX idx_game_id (game_id),
    INDEX idx_name (name),
    INDEX idx_active (is_active)
);

-- Timers table for game timing logic
CREATE TABLE timers (
    timer_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    player_id VARCHAR(36) NULL, -- NULL for global timers
    name VARCHAR(255) NOT NULL,
    value FLOAT DEFAULT 0.0, -- Current timer value
    initial_value FLOAT DEFAULT 0.0, -- Starting value
    multiplier FLOAT DEFAULT 1.0, -- Timer speed multiplier
    trigger_id INT NULL, -- Associated trigger
    is_running BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(player_id) ON DELETE CASCADE,
    FOREIGN KEY (trigger_id) REFERENCES triggers(trigger_id) ON DELETE SET NULL,
    INDEX idx_game_id (game_id),
    INDEX idx_player_id (player_id),
    INDEX idx_running (is_running),
    INDEX idx_trigger_id (trigger_id)
);

-- Notifications queue table
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('email', 'slack') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON DEFAULT '{}', -- Additional notification data
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_type (type)
);

-- API call logs for monitoring and rate limiting
CREATE TABLE api_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    response_code INT,
    execution_time_ms INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_endpoint (endpoint),
    INDEX idx_created_at (created_at)
);

-- System monitoring table
CREATE TABLE system_stats (
    stat_id INT AUTO_INCREMENT PRIMARY KEY,
    total_users INT DEFAULT 0,
    total_memory_mb DECIMAL(10,2) DEFAULT 0.00,
    total_api_calls_today INT DEFAULT 0,
    active_games INT DEFAULT 0,
    active_players INT DEFAULT 0,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recorded_at (recorded_at)
);

-- Insert default system stats
INSERT INTO system_stats (total_users, total_memory_mb, total_api_calls_today, active_games, active_players) 
VALUES (0, 0.00, 0, 0, 0);

-- Create indexes for performance
CREATE INDEX idx_users_plan_memory ON users(plan_type, memory_used_mb);
CREATE INDEX idx_games_user_active ON games(user_id, is_active);
CREATE INDEX idx_players_game_online ON players(game_id, is_online);
CREATE INDEX idx_timers_game_running ON timers(game_id, is_running);
