-- Admin profile photo (safe to re-run on MySQL 5.7+ / MariaDB)
SET @admin_image_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'admins'
      AND COLUMN_NAME = 'image'
);

SET @admin_image_sql := IF(
    @admin_image_exists = 0,
    'ALTER TABLE `admins` ADD COLUMN `image` VARCHAR(255) NULL AFTER `email`',
    'SELECT 1'
);

PREPARE admin_image_stmt FROM @admin_image_sql;
EXECUTE admin_image_stmt;
DEALLOCATE PREPARE admin_image_stmt;
