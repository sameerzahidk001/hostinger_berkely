-- Fixed Student ID on users (BSB0000001). Safe to re-run.
SET @student_id_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'student_id'
);

SET @student_id_sql := IF(
    @student_id_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `student_id` VARCHAR(20) NULL AFTER `email`',
    'SELECT 1'
);

PREPARE student_id_stmt FROM @student_id_sql;
EXECUTE student_id_stmt;
DEALLOCATE PREPARE student_id_stmt;
