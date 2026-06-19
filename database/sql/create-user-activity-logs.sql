-- User activity logs (login, logout, payments, sessions)
-- Safe to re-run on Hostinger / phpMyAdmin

CREATE TABLE IF NOT EXISTS `user_activity_logs` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) UNSIGNED DEFAULT NULL,
    `admin_id` bigint(20) UNSIGNED DEFAULT NULL,
    `audience` varchar(20) NOT NULL,
    `action` varchar(255) NOT NULL,
    `item` text DEFAULT NULL,
    `url` varchar(500) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `session_id` varchar(120) DEFAULT NULL,
    `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `user_activity_logs_user_id_index` (`user_id`),
    KEY `user_activity_logs_admin_id_index` (`admin_id`),
    KEY `user_activity_logs_audience_index` (`audience`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
