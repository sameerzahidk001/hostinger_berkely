-- =============================================================================
-- ONE-CLICK FIX ALL BLANK PAGES — run on LIVE database (eduberkeley.com)
-- NOT on u739774248_testberk (that is sublive only)
--
-- LIVE DB name is usually: u739774248_dbf3zedfyyctp
-- BACKUP FIRST → phpMyAdmin → Export
-- =============================================================================

-- Auto-pick template = category page with the MOST sections (usually has banner)
SET @template_id = (
    SELECT `page_id`
    FROM (
        SELECT ps.`page_id`, COUNT(*) AS cnt
        FROM `page_sections` ps
        INNER JOIN `pages` p ON p.`id` = ps.`page_id`
        WHERE p.`category_id` IS NOT NULL
        GROUP BY ps.`page_id`
        ORDER BY cnt DESC
        LIMIT 1
    ) AS pick
);

SELECT @template_id AS template_page_id;

-- Copy all sections to EVERY category page that has ZERO sections
INSERT INTO `page_sections` (`page_id`, `section_type`, `order`, `data`, `created_at`, `updated_at`)
SELECT
    p.`id`,
    t.`section_type`,
    t.`order`,
    t.`data`,
    NOW(),
    NOW()
FROM `pages` p
INNER JOIN `page_sections` t ON t.`page_id` = @template_id
WHERE p.`category_id` IS NOT NULL
  AND p.`id` <> @template_id
  AND NOT EXISTS (
      SELECT 1 FROM `page_sections` ex WHERE ex.`page_id` = p.`id`
  );

-- Fix section_type inside JSON (MariaDB)
UPDATE `page_sections`
SET `data` = JSON_SET(`data`, '$.section_type', `section_type`)
WHERE `section_type` IS NOT NULL
  AND `section_type` <> ''
  AND `data` IS NOT NULL
  AND `data` <> ''
  AND JSON_VALID(`data`)
  AND (
    JSON_EXTRACT(`data`, '$.section_type') IS NULL
    OR JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.section_type')) = ''
  );

-- Verify: should return 0 rows
SELECT p.`id`, p.`page_name`, p.`url`, COUNT(ps.`id`) AS sections
FROM `pages` p
LEFT JOIN `page_sections` ps ON ps.`page_id` = p.`id`
WHERE p.`category_id` IS NOT NULL
GROUP BY p.`id`, p.`page_name`, p.`url`
HAVING sections = 0;
