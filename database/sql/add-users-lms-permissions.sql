-- Instructor LMS action permissions (safe to re-run)
SET @lms_permissions_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'lms_permissions'
);

SET @lms_permissions_sql := IF(
    @lms_permissions_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `lms_permissions` TEXT NULL',
    'SELECT 1'
);

PREPARE lms_permissions_stmt FROM @lms_permissions_sql;
EXECUTE lms_permissions_stmt;
DEALLOCATE PREPARE lms_permissions_stmt;
