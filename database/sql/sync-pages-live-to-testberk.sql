-- =============================================================================
-- COPY ALL PAGES FROM LIVE → TESTBERK
--
--   SOURCE (read from):  u739774248_dbf3zedfyyctp
--   TARGET (write to):   u739774248_testberk
--
-- HOW TO RUN:
--   1. phpMyAdmin → click u739774248_testberk on the LEFT (target must be selected)
--   2. Backup testberk first (Export)
--   3. SQL tab → paste this ENTIRE file → Go
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- 1) Copy all pages rows from LIVE into testberk
INSERT INTO `u739774248_testberk`.`pages`
SELECT * FROM `u739774248_dbf3zedfyyctp`.`pages`
ON DUPLICATE KEY UPDATE
    `page_name`   = VALUES(`page_name`),
    `url`         = VALUES(`url`),
    `parent_id`   = VALUES(`parent_id`),
    `category_id` = VALUES(`category_id`),
    `deleted_at`  = VALUES(`deleted_at`),
    `updated_at`  = VALUES(`updated_at`);

-- 2) Remove old sections on testberk for pages that exist on LIVE, then copy fresh from LIVE
DELETE ps FROM `u739774248_testberk`.`page_sections` ps
INNER JOIN `u739774248_dbf3zedfyyctp`.`pages` p ON p.`id` = ps.`page_id`;

INSERT INTO `u739774248_testberk`.`page_sections`
SELECT * FROM `u739774248_dbf3zedfyyctp`.`page_sections` ps
WHERE ps.`page_id` IN (SELECT `id` FROM `u739774248_dbf3zedfyyctp`.`pages`);

-- 3) Copy page SEO from LIVE (not course SEO)
INSERT INTO `u739774248_testberk`.`pages_s_e_o_s`
SELECT * FROM `u739774248_dbf3zedfyyctp`.`pages_s_e_o_s`
WHERE `page_id` IS NOT NULL
ON DUPLICATE KEY UPDATE
    `title`               = VALUES(`title`),
    `meta_description`    = VALUES(`meta_description`),
    `keywords`            = VALUES(`keywords`),
    `additional_keywords` = VALUES(`additional_keywords`),
    `thumbnail`           = VALUES(`thumbnail`),
    `updated_at`          = VALUES(`updated_at`);

SET FOREIGN_KEY_CHECKS = 1;

-- 4) Check result on testberk (sections should be > 0 for pages that have content on LIVE)
SELECT
    p.`id`,
    p.`page_name`,
    COUNT(ps.`id`) AS sections
FROM `u739774248_testberk`.`pages` p
LEFT JOIN `u739774248_testberk`.`page_sections` ps ON ps.`page_id` = p.`id`
WHERE p.`category_id` IS NOT NULL
GROUP BY p.`id`, p.`page_name`
ORDER BY sections DESC
LIMIT 30;
