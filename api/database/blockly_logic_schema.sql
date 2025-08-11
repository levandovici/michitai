-- Blockly Puzzle Logic Constructor Schema Extension
-- Add logic storage capabilities to existing games table

-- Add logic_json column to games table for storing Blockly workspace JSON
ALTER TABLE games ADD COLUMN IF NOT EXISTS logic_json LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}' CHECK (json_valid(logic_json));

-- Add logic metadata columns
ALTER TABLE games ADD COLUMN IF NOT EXISTS logic_size_bytes INT DEFAULT 0;
ALTER TABLE games ADD COLUMN IF NOT EXISTS logic_version INT DEFAULT 1;
ALTER TABLE games ADD COLUMN IF NOT EXISTS logic_updated_at TIMESTAMP NULL DEFAULT NULL;

-- Create game_logic_versions table for versioning (optional)
CREATE TABLE IF NOT EXISTS game_logic_versions (
    version_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    user_id INT NOT NULL,
    logic_json LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
    logic_size_bytes INT DEFAULT 0,
    version_number INT NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_game_id (game_id),
    INDEX idx_user_id (user_id),
    INDEX idx_version (version_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create game_logic_simulations table for test runs
CREATE TABLE IF NOT EXISTS game_logic_simulations (
    simulation_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    user_id INT NOT NULL,
    logic_json LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
    simulation_result LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}',
    status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
    execution_time_ms INT DEFAULT 0,
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_game_id (game_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance
ALTER TABLE games ADD INDEX IF NOT EXISTS idx_logic_size (logic_size_bytes);
ALTER TABLE games ADD INDEX IF NOT EXISTS idx_logic_updated (logic_updated_at);
