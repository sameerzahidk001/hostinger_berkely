-- Run in phpMyAdmin on test.berkeleyme.com if migrations were not applied.
-- Safe to re-run: each block checks information_schema before ALTER.

-- ---------------------------------------------------------------------------
-- Audit columns (created_by, updated_by) — fixes course/page save errors
-- ---------------------------------------------------------------------------
SET @db = DATABASE();

-- Helper: run once per table (courses)
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'created_by');
SET @sql = IF(@exists = 0, 'ALTER TABLE `courses` ADD COLUMN `created_by` BIGINT UNSIGNED NULL AFTER `updated_at`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'updated_by');
SET @sql = IF(@exists = 0, 'ALTER TABLE `courses` ADD COLUMN `updated_by` BIGINT UNSIGNED NULL AFTER `created_by`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- pages
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pages' AND COLUMN_NAME = 'created_by');
SET @sql = IF(@exists = 0, 'ALTER TABLE `pages` ADD COLUMN `created_by` BIGINT UNSIGNED NULL AFTER `updated_at`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pages' AND COLUMN_NAME = 'updated_by');
SET @sql = IF(@exists = 0, 'ALTER TABLE `pages` ADD COLUMN `updated_by` BIGINT UNSIGNED NULL AFTER `created_by`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- categories
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'created_by');
SET @sql = IF(@exists = 0, 'ALTER TABLE `categories` ADD COLUMN `created_by` BIGINT UNSIGNED NULL AFTER `updated_at`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'updated_by');
SET @sql = IF(@exists = 0, 'ALTER TABLE `categories` ADD COLUMN `updated_by` BIGINT UNSIGNED NULL AFTER `created_by`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- faqs
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'faqs' AND COLUMN_NAME = 'created_by');
SET @sql = IF(@exists = 0, 'ALTER TABLE `faqs` ADD COLUMN `created_by` BIGINT UNSIGNED NULL AFTER `updated_at`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'faqs' AND COLUMN_NAME = 'updated_by');
SET @sql = IF(@exists = 0, 'ALTER TABLE `faqs` ADD COLUMN `updated_by` BIGINT UNSIGNED NULL AFTER `created_by`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- clients
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'created_by');
SET @sql = IF(@exists = 0, 'ALTER TABLE `clients` ADD COLUMN `created_by` BIGINT UNSIGNED NULL AFTER `updated_at`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'clients' AND COLUMN_NAME = 'updated_by');
SET @sql = IF(@exists = 0, 'ALTER TABLE `clients` ADD COLUMN `updated_by` BIGINT UNSIGNED NULL AFTER `created_by`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- course_agendas
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'course_agendas' AND COLUMN_NAME = 'created_by');
SET @sql = IF(@exists = 0, 'ALTER TABLE `course_agendas` ADD COLUMN `created_by` BIGINT UNSIGNED NULL AFTER `updated_at`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'course_agendas' AND COLUMN_NAME = 'updated_by');
SET @sql = IF(@exists = 0, 'ALTER TABLE `course_agendas` ADD COLUMN `updated_by` BIGINT UNSIGNED NULL AFTER `created_by`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- pages_s_e_o_s (SEO table)
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pages_s_e_o_s' AND COLUMN_NAME = 'created_by');
SET @sql = IF(@exists = 0, 'ALTER TABLE `pages_s_e_o_s` ADD COLUMN `created_by` BIGINT UNSIGNED NULL AFTER `updated_at`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'pages_s_e_o_s' AND COLUMN_NAME = 'updated_by');
SET @sql = IF(@exists = 0, 'ALTER TABLE `pages_s_e_o_s` ADD COLUMN `updated_by` BIGINT UNSIGNED NULL AFTER `created_by`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- payments.source
-- ---------------------------------------------------------------------------
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'source');
SET @sql = IF(@exists = 0, 'ALTER TABLE `payments` ADD COLUMN `source` VARCHAR(20) NULL AFTER `status`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- page_views.referrer
-- ---------------------------------------------------------------------------
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'page_views' AND COLUMN_NAME = 'referrer');
SET @sql = IF(@exists = 0, 'ALTER TABLE `page_views` ADD COLUMN `referrer` VARCHAR(500) NULL AFTER `url`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- permissions.module
-- ---------------------------------------------------------------------------
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'permissions' AND COLUMN_NAME = 'module');
SET @sql = IF(@exists = 0, 'ALTER TABLE `permissions` ADD COLUMN `module` VARCHAR(100) NULL AFTER `name`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- currency_rates table
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `currency_rates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `currency_id` BIGINT UNSIGNED NOT NULL,
  `rate_to_aed` DECIMAL(12,4) NOT NULL DEFAULT 1.0000,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `currency_rates_currency_id_unique` (`currency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- site_settings invoice footer columns
-- ---------------------------------------------------------------------------
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'site_settings' AND COLUMN_NAME = 'invoice_footer_usa');
SET @sql = IF(@exists = 0, 'ALTER TABLE `site_settings` ADD COLUMN `invoice_footer_usa` TEXT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'site_settings' AND COLUMN_NAME = 'invoice_footer_uk');
SET @sql = IF(@exists = 0, 'ALTER TABLE `site_settings` ADD COLUMN `invoice_footer_uk` TEXT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'site_settings' AND COLUMN_NAME = 'invoice_footer_middle_east');
SET @sql = IF(@exists = 0, 'ALTER TABLE `site_settings` ADD COLUMN `invoice_footer_middle_east` TEXT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'site_settings' AND COLUMN_NAME = 'invoice_footer_email');
SET @sql = IF(@exists = 0, 'ALTER TABLE `site_settings` ADD COLUMN `invoice_footer_email` VARCHAR(255) NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'site_settings' AND COLUMN_NAME = 'invoice_footer_website');
SET @sql = IF(@exists = 0, 'ALTER TABLE `site_settings` ADD COLUMN `invoice_footer_website` VARCHAR(255) NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'site_settings' AND COLUMN_NAME = 'invoice_footer_presence');
SET @sql = IF(@exists = 0, 'ALTER TABLE `site_settings` ADD COLUMN `invoice_footer_presence` VARCHAR(500) NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'site_settings' AND COLUMN_NAME = 'invoice_header_email');
SET @sql = IF(@exists = 0, 'ALTER TABLE `site_settings` ADD COLUMN `invoice_header_email` VARCHAR(255) NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
