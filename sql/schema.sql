-- Analytics Service Database Schema (MySQL)
-- Compatible with MySQL 5.7+

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================
-- USERS TABLE (Admin & Clients)
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) NOT NULL DEFAULT 'User',
  `role` ENUM('admin', 'client') NOT NULL DEFAULT 'client',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PROJECTS TABLE (Websites being tracked)
-- ============================================
CREATE TABLE IF NOT EXISTS `projects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `domain` VARCHAR(255) NOT NULL,
  `tracking_code` VARCHAR(64) NOT NULL UNIQUE,
  `yandex_metrika_id` VARCHAR(20) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `settings` JSON DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_domain` (`domain`),
  KEY `idx_tracking_code` (`tracking_code`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_projects_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- GOALS TABLE (Conversion Goals)
-- ============================================
CREATE TABLE IF NOT EXISTS `goals` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `goal_type` ENUM('click', 'scroll', 'time_on_page', 'form_submit', 'page_view', 'custom_event') NOT NULL,
  `target_name` VARCHAR(100) NOT NULL COMMENT 'Yandex Metrika goal name',
  `conditions` JSON NOT NULL COMMENT 'Trigger conditions',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `conversions_count` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_goal_type` (`goal_type`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_goals_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- EVENTS TABLE (Raw Event Data)
-- Stores all tracked events
-- ============================================
CREATE TABLE IF NOT EXISTS `events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `session_id` VARCHAR(64) NOT NULL,
  `event_type` VARCHAR(50) NOT NULL,
  `event_name` VARCHAR(100) DEFAULT NULL,
  `goal_id` INT UNSIGNED DEFAULT NULL,
  `user_id_hash` VARCHAR(64) NOT NULL COMMENT 'Anonymized user identifier',
  `page_url` VARCHAR(2048) NOT NULL,
  `page_title` VARCHAR(500) DEFAULT NULL,
  `referrer` VARCHAR(2048) DEFAULT NULL,
  `device_type` ENUM('desktop', 'tablet', 'mobile') DEFAULT NULL,
  `os` VARCHAR(50) DEFAULT NULL,
  `browser` VARCHAR(50) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IPv4 or IPv6',
  `country` VARCHAR(50) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `utm_source` VARCHAR(100) DEFAULT NULL,
  `utm_medium` VARCHAR(100) DEFAULT NULL,
  `utm_campaign` VARCHAR(200) DEFAULT NULL,
  `utm_term` VARCHAR(200) DEFAULT NULL,
  `utm_content` VARCHAR(200) DEFAULT NULL,
  `event_data` JSON DEFAULT NULL COMMENT 'Additional event metadata',
  `timestamp` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `idx_project_timestamp` (`project_id`, `timestamp`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_goal_id` (`goal_id`),
  KEY `idx_user_hash` (`user_id_hash`),
  KEY `idx_timestamp` (`timestamp`),
  CONSTRAINT `fk_events_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_events_goal` FOREIGN KEY (`goal_id`) REFERENCES `goals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
PARTITION BY RANGE (YEAR(timestamp)) (
  PARTITION p2024 VALUES LESS THAN (2025),
  PARTITION p2025 VALUES LESS THAN (2026),
  PARTITION p2026 VALUES LESS THAN (2027),
  PARTITION p_max VALUES LESS THAN MAXVALUE
);

-- ============================================
-- SESSIONS TABLE (User Sessions)
-- ============================================
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` VARCHAR(64) NOT NULL,
  `project_id` INT UNSIGNED NOT NULL,
  `user_id_hash` VARCHAR(64) NOT NULL,
  `page_views` INT UNSIGNED NOT NULL DEFAULT 0,
  `total_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Seconds',
  `active_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Active seconds',
  `bounce` TINYINT(1) NOT NULL DEFAULT 0,
  `converted` TINYINT(1) NOT NULL DEFAULT 0,
  `goal_ids` JSON DEFAULT NULL COMMENT 'Array of achieved goal IDs',
  `entry_page` VARCHAR(2048) NOT NULL,
  `exit_page` VARCHAR(2048) DEFAULT NULL,
  `referrer` VARCHAR(2048) DEFAULT NULL,
  `device_type` ENUM('desktop', 'tablet', 'mobile') DEFAULT NULL,
  `os` VARCHAR(50) DEFAULT NULL,
  `browser` VARCHAR(50) DEFAULT NULL,
  `country` VARCHAR(50) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `started_at` DATETIME(3) NOT NULL,
  `ended_at` DATETIME(3) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_session` (`session_id`),
  KEY `idx_project_started` (`project_id`, `started_at`),
  KEY `idx_user_hash` (`user_id_hash`),
  KEY `idx_converted` (`converted`),
  KEY `idx_bounce` (`bounce`),
  KEY `idx_started_at` (`started_at`),
  CONSTRAINT `fk_sessions_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
PARTITION BY RANGE (YEAR(started_at)) (
  PARTITION p2024 VALUES LESS THAN (2025),
  PARTITION p2025 VALUES LESS THAN (2026),
  PARTITION p2026 VALUES LESS THAN (2027),
  PARTITION p_max VALUES LESS THAN MAXVALUE
);

-- ============================================
-- DAILY STATISTICS TABLE (Aggregated Data)
-- For fast dashboard queries
-- ============================================
CREATE TABLE IF NOT EXISTS `daily_stats` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `stat_date` DATE NOT NULL,
  `page_views` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `unique_visitors` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `sessions` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `bounce_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `avg_session_duration` INT UNSIGNED NOT NULL DEFAULT 0,
  `conversions` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `conversion_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `top_pages` JSON DEFAULT NULL COMMENT 'Top 10 pages by views',
  `top_referrers` JSON DEFAULT NULL COMMENT 'Top 10 referrers',
  `devices` JSON DEFAULT NULL COMMENT 'Device breakdown',
  `countries` JSON DEFAULT NULL COMMENT 'Country breakdown',
  `hourly_distribution` JSON DEFAULT NULL COMMENT 'Views per hour',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_project_date` (`project_id`, `stat_date`),
  KEY `idx_stat_date` (`stat_date`),
  KEY `idx_project_date` (`project_id`, `stat_date`),
  CONSTRAINT `fk_daily_stats_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- FUNNELS TABLE (Conversion Funnels)
-- ============================================
CREATE TABLE IF NOT EXISTS `funnels` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `steps` JSON NOT NULL COMMENT 'Ordered array of steps',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  CONSTRAINT `fk_funnels_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- FUNNEL RESULTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `funnel_results` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `funnel_id` INT UNSIGNED NOT NULL,
  `project_id` INT UNSIGNED NOT NULL,
  `stat_date` DATE NOT NULL,
  `step_number` TINYINT UNSIGNED NOT NULL,
  `step_name` VARCHAR(255) NOT NULL,
  `visitors_count` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `conversion_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `drop_off_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_funnel_date` (`funnel_id`, `stat_date`),
  KEY `idx_project_date` (`project_id`, `stat_date`),
  CONSTRAINT `fk_funnel_results_funnel` FOREIGN KEY (`funnel_id`) REFERENCES `funnels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_funnel_results_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- API KEYS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `key_hash` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) DEFAULT NULL,
  `permissions` JSON DEFAULT NULL COMMENT 'API permissions',
  `expires_at` DATETIME DEFAULT NULL,
  `last_used_at` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_key_hash` (`key_hash`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_api_keys_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ACTIVITY LOG TABLE (Audit Trail)
-- ============================================
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50) DEFAULT NULL,
  `entity_id` INT UNSIGNED DEFAULT NULL,
  `old_values` JSON DEFAULT NULL,
  `new_values` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_timestamp` (`user_id`, `timestamp`),
  KEY `idx_action` (`action`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  CONSTRAINT `fk_activity_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT DEFAULT ADMIN USER
-- Password: admin123 (hashed with PASSWORD_DEFAULT in PHP)
-- ============================================
INSERT INTO `users` (`email`, `password_hash`, `name`, `role`) VALUES
('admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin') ON DUPLICATE KEY UPDATE email=email;

COMMIT;
