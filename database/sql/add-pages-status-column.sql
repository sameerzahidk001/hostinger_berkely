-- Page active/disabled status (safe to re-run on MySQL 5.7+ and MariaDB)
SET @pages_status_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'pages'
      AND COLUMN_NAME = 'status'
);

SET @pages_status_sql := IF(
    @pages_status_exists = 0,
    'ALTER TABLE `pages` ADD COLUMN `status` TINYINT(1) NOT NULL DEFAULT 1 AFTER `url`',
    'SELECT 1'
);

PREPARE pages_status_stmt FROM @pages_status_sql;
EXECUTE pages_status_stmt;
DEALLOCATE PREPARE pages_status_stmt;

-- Disable learner-stories CMS page if it still exists (hard route also returns 404)
UPDATE `pages` SET `status` = 0 WHERE `url` = 'learner-stories';
