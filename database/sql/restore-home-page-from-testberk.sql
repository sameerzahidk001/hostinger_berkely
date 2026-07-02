-- =============================================================================
-- RESTORE HOME PAGE (id 31) FROM testberk BACKUP
-- =============================================================================
--
-- Use when live Home page sections were wiped after an admin save.
-- testberk may still have a copy if sync-pages-live-to-testberk.sql was run
-- BEFORE the bad save.
--
-- !!! RUN ON LIVE DATABASE: u739774248_dbf3zedfyyctp !!!
-- BACKUP FIRST → Export
-- =============================================================================

SET @home_page_id = 31;

-- 1) Preview what exists now on LIVE vs testberk
SELECT 'LIVE now' AS source, COUNT(*) AS sections
FROM `page_sections` WHERE `page_id` = @home_page_id
UNION ALL
SELECT 'testberk copy', COUNT(*)
FROM `u739774248_testberk`.`page_sections` WHERE `page_id` = @home_page_id;

SELECT id, section_type, `order`, CHAR_LENGTH(data) AS data_bytes
FROM `page_sections`
WHERE `page_id` = @home_page_id
ORDER BY `order`;

-- 2) STOP if testberk has 0 sections — use Hostinger daily backup instead.
-- 3) Uncomment below ONLY when testberk shows more sections than LIVE.

/*
DELETE FROM `page_sections` WHERE `page_id` = @home_page_id;

INSERT INTO `page_sections` (`id`, `page_id`, `section_type`, `order`, `data`, `created_at`, `updated_at`)
SELECT `id`, `page_id`, `section_type`, `order`, `data`, `created_at`, `updated_at`
FROM `u739774248_testberk`.`page_sections`
WHERE `page_id` = @home_page_id;

-- Fix section_type inside JSON if missing
UPDATE `page_sections`
SET `data` = JSON_SET(`data`, '$.section_type', `section_type`)
WHERE `page_id` = @home_page_id
  AND `section_type` IS NOT NULL
  AND `section_type` <> ''
  AND `data` IS NOT NULL
  AND `data` <> ''
  AND JSON_VALID(`data`)
  AND (
    JSON_EXTRACT(`data`, '$.section_type') IS NULL
    OR JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.section_type')) = ''
  );
*/
