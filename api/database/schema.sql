-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Авг 10 2025 г., 09:19
-- Версия сервера: 10.11.10-MariaDB
-- Версия PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `u833544264_api`
--

DELIMITER $$
--
-- Процедуры
--
CREATE DEFINER=`u833544264_api`@`127.0.0.1` PROCEDURE `cleanup_old_events` (IN `days_to_keep` INT)   BEGIN
    -- Clean up completed events older than X days
    DELETE FROM scheduled_events 
    WHERE status IN ('completed', 'failed', 'cancelled')
    AND completed_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
    
    -- Clean up old game logs
    DELETE FROM game_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
    
    -- Clean up old notifications
    DELETE FROM user_notifications 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY)
    AND is_read = 1;
    
    -- Clean up old game events
    DELETE FROM game_events 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `api_logs`
--

CREATE TABLE `api_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `endpoint` varchar(255) NOT NULL,
  `method` varchar(10) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `response_code` int(11) DEFAULT NULL,
  `execution_time_ms` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `chats`
--

CREATE TABLE `chats` (
  `chat_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `community_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `json_messages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]' CHECK (json_valid(`json_messages`)),
  `message_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `communities`
--

CREATE TABLE `communities` (
  `community_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `json_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}' CHECK (json_valid(`json_data`)),
  `privileges` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}' CHECK (json_valid(`privileges`)),
  `member_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `games`
--

CREATE TABLE `games` (
  `game_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `json_structure` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`json_structure`)),
  `json_properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`json_properties`)),
  `json_rooms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]' CHECK (json_valid(`json_rooms`)),
  `json_communities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]' CHECK (json_valid(`json_communities`)),
  `json_chats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]' CHECK (json_valid(`json_chats`)),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `games`
--

-- --------------------------------------------------------

--
-- Структура таблицы `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('email','slack') NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}' CHECK (json_valid(`data`)),
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `players`
--

CREATE TABLE `players` (
  `player_id` varchar(36) NOT NULL,
  `game_id` int(11) NOT NULL,
  `password_guid` varchar(36) NOT NULL,
  `json_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}' CHECK (json_valid(`json_data`)),
  `is_online` tinyint(1) DEFAULT 0,
  `last_activity` timestamp NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `max_players` int(11) DEFAULT 10,
  `current_players` int(11) DEFAULT 0,
  `json_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}' CHECK (json_valid(`json_data`)),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `subscriptions`
--

CREATE TABLE `subscriptions` (
  `subscription_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `paypal_subscription_id` varchar(255) DEFAULT NULL,
  `plan_type` enum('Free','Standard','Pro') NOT NULL,
  `status` enum('active','canceled','past_due','pending') DEFAULT 'pending',
  `amount` decimal(10,2) DEFAULT 0.00,
  `currency` varchar(3) DEFAULT 'USD',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `system_stats`
--

CREATE TABLE `system_stats` (
  `stat_id` int(11) NOT NULL,
  `total_users` int(11) DEFAULT 0,
  `total_memory_mb` decimal(10,2) DEFAULT 0.00,
  `total_api_calls_today` int(11) DEFAULT 0,
  `active_games` int(11) DEFAULT 0,
  `active_players` int(11) DEFAULT 0,
  `recorded_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `system_stats`
--

INSERT INTO `system_stats` (`stat_id`, `total_users`, `total_memory_mb`, `total_api_calls_today`, `active_games`, `active_players`, `recorded_at`) VALUES
(1, 0, 0.00, 0, 0, 0, '2025-08-09 09:03:59');

-- --------------------------------------------------------

--
-- Структура таблицы `timers`
--

CREATE TABLE `timers` (
  `timer_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `player_id` varchar(36) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `value` float DEFAULT 0,
  `initial_value` float DEFAULT 0,
  `multiplier` float DEFAULT 1,
  `trigger_id` int(11) DEFAULT NULL,
  `is_running` tinyint(1) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `triggers`
--

CREATE TABLE `triggers` (
  `trigger_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '{}' CHECK (json_valid(`parameters`)),
  `action_type` enum('timer','event','condition','function') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `api_token` varchar(36) NOT NULL,
  `plan_type` enum('Free','Standard','Pro') DEFAULT 'Free',
  `memory_used_mb` decimal(10,2) DEFAULT 0.00,
  `memory_limit_mb` decimal(10,2) DEFAULT 250.00,
  `api_calls_today` int(11) DEFAULT 0,
  `paypal_customer_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verification_token` varchar(64) DEFAULT NULL COMMENT 'Email verification token for account confirmation',
  `token_expires` int(11) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0 COMMENT 'Boolean flag indicating if email is verified (0=false, 1=true)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `api_logs`
--
ALTER TABLE `api_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_endpoint` (`endpoint`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Индексы таблицы `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`chat_id`),
  ADD KEY `idx_game_id` (`game_id`),
  ADD KEY `idx_room_id` (`room_id`),
  ADD KEY `idx_community_id` (`community_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Индексы таблицы `communities`
--
ALTER TABLE `communities`
  ADD PRIMARY KEY (`community_id`),
  ADD KEY `idx_game_id` (`game_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Индексы таблицы `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`game_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_games_user_active` (`user_id`,`is_active`);

--
-- Индексы таблицы `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`type`);

--
-- Индексы таблицы `players`
--
ALTER TABLE `players`
  ADD PRIMARY KEY (`player_id`),
  ADD KEY `idx_game_id` (`game_id`),
  ADD KEY `idx_online` (`is_online`),
  ADD KEY `idx_last_activity` (`last_activity`),
  ADD KEY `idx_players_game_online` (`game_id`,`is_online`);

--
-- Индексы таблицы `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `idx_game_id` (`game_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Индексы таблицы `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`subscription_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_paypal_subscription` (`paypal_subscription_id`),
  ADD KEY `idx_status` (`status`);

--
-- Индексы таблицы `system_stats`
--
ALTER TABLE `system_stats`
  ADD PRIMARY KEY (`stat_id`),
  ADD KEY `idx_recorded_at` (`recorded_at`);

--
-- Индексы таблицы `timers`
--
ALTER TABLE `timers`
  ADD PRIMARY KEY (`timer_id`),
  ADD KEY `idx_game_id` (`game_id`),
  ADD KEY `idx_player_id` (`player_id`),
  ADD KEY `idx_running` (`is_running`),
  ADD KEY `idx_trigger_id` (`trigger_id`),
  ADD KEY `idx_timers_game_running` (`game_id`,`is_running`);

--
-- Индексы таблицы `triggers`
--
ALTER TABLE `triggers`
  ADD PRIMARY KEY (`trigger_id`),
  ADD KEY `idx_game_id` (`game_id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_active` (`is_active`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `api_token` (`api_token`),
  ADD KEY `idx_api_token` (`api_token`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_users_plan_memory` (`plan_type`,`memory_used_mb`),
  ADD KEY `idx_users_verification_token` (`verification_token`),
  ADD KEY `idx_users_email_verified` (`email_verified`),
  ADD KEY `idx_token_expires` (`token_expires`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `api_logs`
--
ALTER TABLE `api_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `chats`
--
ALTER TABLE `chats`
  MODIFY `chat_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `communities`
--
ALTER TABLE `communities`
  MODIFY `community_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `games`
--
ALTER TABLE `games`
  MODIFY `game_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT для таблицы `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `subscription_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `system_stats`
--
ALTER TABLE `system_stats`
  MODIFY `stat_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `timers`
--
ALTER TABLE `timers`
  MODIFY `timer_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `triggers`
--
ALTER TABLE `triggers`
  MODIFY `trigger_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `api_logs`
--
ALTER TABLE `api_logs`
  ADD CONSTRAINT `api_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `chats`
--
ALTER TABLE `chats`
  ADD CONSTRAINT `chats_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chats_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `chats_ibfk_3` FOREIGN KEY (`community_id`) REFERENCES `communities` (`community_id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `communities`
--
ALTER TABLE `communities`
  ADD CONSTRAINT `communities_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `players`
--
ALTER TABLE `players`
  ADD CONSTRAINT `players_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `timers`
--
ALTER TABLE `timers`
  ADD CONSTRAINT `timers_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timers_ibfk_2` FOREIGN KEY (`player_id`) REFERENCES `players` (`player_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `timers_ibfk_3` FOREIGN KEY (`trigger_id`) REFERENCES `triggers` (`trigger_id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `triggers`
--
ALTER TABLE `triggers`
  ADD CONSTRAINT `triggers_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE;

DELIMITER $$
--
-- События
--
CREATE DEFINER=`u833544264_api`@`127.0.0.1` EVENT `daily_cleanup` ON SCHEDULE EVERY 1 DAY STARTS '2025-08-06 03:00:00' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    -- Keep data for 30 days by default
    CALL cleanup_old_events(30);
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
