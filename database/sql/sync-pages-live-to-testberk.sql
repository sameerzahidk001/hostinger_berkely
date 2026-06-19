-- =============================================================================
-- Sync PAGES content: LIVE → SUBLIVE (phpMyAdmin on Hostinger)
--
--   FROM: u739774248_dbf3zedfyyctp  (eduberkeley.com LIVE)
--   TO:   u739774248_testberk        (sublive / test)
--
-- Run each block while **testberk** is selected in the left sidebar.
-- BACKUP testberk first (Export).
--
-- Does NOT touch: users, payments, admins on testberk.
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- 1) pages
-- ---------------------------------------------------------------------------
INSERT INTO `u739774248_testberk`.`pages` (
    `id`, `page_name`, `url`, `parent_id`, `category_id`,
    `deleted_at`, `created_at`, `updated_at`, `created_by`, `updated_by`
)
SELECT
    `id`, `page_name`, `url`, `parent_id`, `category_id`,
    `deleted_at`, `created_at`, `updated_at`, `created_by`, `updated_by`
FROM `u739774248_dbf3zedfyyctp`.`pages`
ON DUPLICATE KEY UPDATE
    `page_name`   = VALUES(`page_name`),
    `url`         = VALUES(`url`),
    `parent_id`   = VALUES(`parent_id`),
    `category_id` = VALUES(`category_id`),
    `deleted_at`  = VALUES(`deleted_at`),
    `updated_at`  = VALUES(`updated_at`),
    `created_by`  = VALUES(`created_by`),
    `updated_by`  = VALUES(`updated_by`);

-- If testberk pages table has fewer columns, use this simpler version instead:
-- INSERT INTO `u739774248_testberk`.`pages` (`id`, `page_name`, `url`, `parent_id`, `category_id`, `deleted_at`, `created_at`, `updated_at`)
-- SELECT `id`, `page_name`, `url`, `parent_id`, `category_id`, `deleted_at`, `created_at`, `updated_at`
-- FROM `u739774248_dbf3zedfyyctp`.`pages`
-- ON DUPLICATE KEY UPDATE
--     `page_name` = VALUES(`page_name`), `url` = VALUES(`url`),
--     `parent_id` = VALUES(`parent_id`), `category_id` = VALUES(`category_id`),
--     `deleted_at` = VALUES(`deleted_at`), `updated_at` = VALUES(`updated_at`);

-- ---------------------------------------------------------------------------
-- 2) page_sections — replace all sections for pages that exist on live
-- ---------------------------------------------------------------------------
DELETE FROM `u739774248_testberk`.`page_sections`
WHERE `page_id` IN (
    SELECT `id` FROM `u739774248_dbf3zedfyyctp`.`pages`
);

INSERT INTO `u739774248_testberk`.`page_sections` (
    `id`, `page_id`, `section_type`, `order`, `data`, `created_at`, `updated_at`
)
SELECT
    `id`, `page_id`, `section_type`, `order`, `data`, `created_at`, `updated_at`
FROM `u739774248_dbf3zedfyyctp`.`page_sections`
WHERE `page_id` IN (
    SELECT `id` FROM `u739774248_dbf3zedfyyctp`.`pages`
);

-- ---------------------------------------------------------------------------
-- 3) pages SEO (page_id rows only, not course SEO)
-- ---------------------------------------------------------------------------
INSERT INTO `u739774248_testberk`.`pages_s_e_o_s` (
    `id`, `page_id`, `course_id`, `title`, `meta_description`,
    `keywords`, `additional_keywords`, `thumbnail`,
    `created_at`, `updated_at`, `created_by`, `updated_by`
)
SELECT
    `id`, `page_id`, `course_id`, `title`, `meta_description`,
    `keywords`, `additional_keywords`, `thumbnail`,
    `created_at`, `updated_at`, `created_by`, `updated_by`
FROM `u739774248_dbf3zedfyyctp`.`pages_s_e_o_s`
WHERE `page_id` IS NOT NULL
ON DUPLICATE KEY UPDATE
    `title`              = VALUES(`title`),
    `meta_description`   = VALUES(`meta_description`),
    `keywords`           = VALUES(`keywords`),
    `additional_keywords`= VALUES(`additional_keywords`),
    `thumbnail`          = VALUES(`thumbnail`),
    `updated_at`         = VALUES(`updated_at`);

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- 4) Verify on testberk
-- ---------------------------------------------------------------------------
SELECT p.id, p.page_name, COUNT(ps.id) AS sections
FROM `u739774248_testberk`.`pages` p
LEFT JOIN `u739774248_testberk`.`page_sections` ps ON ps.page_id = p.id
WHERE p.category_id IS NOT NULL
GROUP BY p.id, p.page_name
ORDER BY sections ASC, p.id DESC
LIMIT 20;
