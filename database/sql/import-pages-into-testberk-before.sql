-- =============================================================================
-- BEFORE importing pages-testberk-import.sql into u739774248_testberk
-- Run this small file first (SQL tab), then use Import for the big file.
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- Optional: clear only page content tables before full replace import
-- (Skip if you only want to update existing rows via REPLACE INTO in import file)
-- TRUNCATE TABLE `page_sections`;
-- TRUNCATE TABLE `pages_s_e_o_s` WHERE page_id IS NOT NULL;  -- not valid in MySQL

-- Safer: delete sections for category CMS pages only
DELETE ps FROM `page_sections` ps
INNER JOIN `pages` p ON p.id = ps.page_id
WHERE p.category_id IS NOT NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- NEXT: phpMyAdmin → Import → choose pages-testberk-import.sql (from converter script)
