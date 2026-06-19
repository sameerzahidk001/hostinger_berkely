-- =============================================================================
-- Fix empty category pages (created after 2026-06-16 on live)
-- Run on LIVE or SUBLIVE in phpMyAdmin — backup first!
-- =============================================================================

-- 1) DIAGNOSE: pages with no sections (these show empty on frontend)
SELECT
    p.id,
    p.page_name,
    p.url,
    p.category_id,
    p.created_at,
    COUNT(ps.id) AS section_count
FROM `pages` p
LEFT JOIN `page_sections` ps ON ps.page_id = p.id
WHERE p.created_at >= '2026-06-16 16:47:00'
GROUP BY p.id, p.page_name, p.url, p.category_id, p.created_at
ORDER BY p.created_at;

-- 2) DIAGNOSE: duplicate URLs (multiple pages sharing same slug = wrong page loads)
SELECT `url`, COUNT(*) AS cnt, GROUP_CONCAT(id ORDER BY id) AS page_ids
FROM `pages`
WHERE `parent_id` IS NULL
GROUP BY `url`
HAVING cnt > 1;

-- 3) FIX duplicate URLs — set unique slug from page name for affected rows
--    (Run only if step 2 shows duplicates; review output before uncommenting)
-- UPDATE `pages` p
-- JOIN (
--     SELECT id, LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
--         TRIM(`page_name`), ' ', '-'), '''', ''), ',', ''), '.', ''), '(', ''), ')', '')) AS new_slug
--     FROM `pages`
--     WHERE created_at >= '2026-06-16 16:47:00'
--       AND category_id IS NOT NULL
-- ) AS s ON s.id = p.id
-- SET p.url = s.new_slug, p.updated_at = NOW()
-- WHERE p.url IN (
--     SELECT bad_url FROM (
--         SELECT `url` AS bad_url FROM `pages` WHERE parent_id IS NULL GROUP BY `url` HAVING COUNT(*) > 1
--     ) AS dups
-- );

-- 4) COPY sections from a working template page to empty pages
--    Replace @source_id with a page that HAS content (e.g. San Jose page id)
--    Replace @target_id with each empty page id (run once per empty page)
--
-- SET @source_id = 46;
-- SET @target_id = 41;
--
-- DELETE FROM `page_sections` WHERE `page_id` = @target_id;
--
-- INSERT INTO `page_sections` (`page_id`, `section_type`, `order`, `data`, `created_at`, `updated_at`)
-- SELECT @target_id, `section_type`, `order`, `data`, NOW(), NOW()
-- FROM `page_sections`
-- WHERE `page_id` = @source_id;
