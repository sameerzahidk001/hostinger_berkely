-- =============================================================================
-- FIX ALL BLANK PAGES — ONE CLICK
-- =============================================================================
--
-- !!! RUN ON LIVE DATABASE ONLY !!!
--   LIVE:  u739774248_dbf3zedfyyctp   → eduberkeley.com
--   WRONG: u739774248_testberk         → sublive only (your screenshot)
--
-- phpMyAdmin: click LIVE database name on LEFT sidebar FIRST, then SQL tab.
-- BACKUP FIRST → Export
-- =============================================================================

-- OPTION A: Auto-pick template (page with most sections)
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

-- OPTION B: If above is NULL, uncomment ONE line and set a known good page id:
-- SET @template_id = 1967;

SELECT @template_id AS template_page_id;

-- STOP if template_page_id is NULL — do not run below until it shows a number!
-- On LIVE it should show e.g. 1967. On testberk it is often NULL (wrong database).

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
WHERE @template_id IS NOT NULL
  AND p.`category_id` IS NOT NULL
  AND p.`id` <> @template_id
  AND NOT EXISTS (
      SELECT 1 FROM `page_sections` ex WHERE ex.`page_id` = p.`id`
  );

UPDATE `page_sections`
SET `data` = JSON_SET(`data`, '$.section_type', `section_type`)
WHERE @template_id IS NOT NULL
  AND `section_type` IS NOT NULL
  AND `section_type` <> ''
  AND `data` IS NOT NULL
  AND `data` <> ''
  AND JSON_VALID(`data`)
  AND (
    JSON_EXTRACT(`data`, '$.section_type') IS NULL
    OR JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.section_type')) = ''
  );

-- Should return 0 rows when done:
SELECT p.`id`, p.`page_name`, COUNT(ps.`id`) AS sections
FROM `pages` p
LEFT JOIN `page_sections` ps ON ps.`page_id` = p.`id`
WHERE p.`category_id` IS NOT NULL
GROUP BY p.`id`, p.`page_name`
HAVING sections = 0;
