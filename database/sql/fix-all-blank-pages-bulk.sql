-- =============================================================================
-- FIX ALL BLANK CMS PAGES (bulk) — MariaDB / phpMyAdmin on Hostinger
-- BACKUP DATABASE FIRST (Export in phpMyAdmin)
--
-- Also deploy branch: fix/seo-limits-disabled-courses-delete-ui-20260618
-- (route fix: /category/... must not hit course listing controller)
-- =============================================================================

-- -----------------------------------------------------------------------------
-- STEP 1 — SEE ALL BLANK PAGES (0 sections = blank on frontend)
-- -----------------------------------------------------------------------------
SELECT
    p.id,
    p.page_name,
    p.url,
    p.category_id,
    p.created_at,
    COUNT(ps.id) AS sections
FROM `pages` p
LEFT JOIN `page_sections` ps ON ps.page_id = p.id
WHERE p.category_id IS NOT NULL
GROUP BY p.id, p.page_name, p.url, p.category_id, p.created_at
HAVING sections = 0
ORDER BY p.created_at DESC;


-- -----------------------------------------------------------------------------
-- STEP 2 — PICK TEMPLATE PAGE (page that HAS sections + banner)
-- Run this to find the best template automatically:
-- -----------------------------------------------------------------------------
SELECT
    p.id,
    p.page_name,
    COUNT(ps.id) AS sections,
    SUM(ps.section_type IN ('hero-banner', 'banner')) AS has_banner
FROM `pages` p
INNER JOIN `page_sections` ps ON ps.page_id = p.id
WHERE p.category_id IS NOT NULL
GROUP BY p.id, p.page_name
HAVING sections >= 5 AND has_banner >= 1
ORDER BY sections DESC
LIMIT 10;

-- Set template ID from STEP 2 (example: CIMA San Jose = 1967, or your best CAIA page)
SET @template_id = 1967;


-- -----------------------------------------------------------------------------
-- STEP 3 — COPY SECTIONS TO **ALL** EMPTY CATEGORY PAGES (one shot)
-- Skips pages that already have sections. Copies banner + all blocks.
-- -----------------------------------------------------------------------------
INSERT INTO `page_sections` (`page_id`, `section_type`, `order`, `data`, `created_at`, `updated_at`)
SELECT
    p.id,
    t.section_type,
    t.`order`,
    t.data,
    NOW(),
    NOW()
FROM `pages` p
INNER JOIN `page_sections` t ON t.page_id = @template_id
LEFT JOIN (
    SELECT `page_id`, COUNT(*) AS cnt
    FROM `page_sections`
    GROUP BY `page_id`
) sc ON sc.page_id = p.id
WHERE p.category_id IS NOT NULL
  AND p.id <> @template_id
  AND (sc.cnt IS NULL OR sc.cnt = 0);


-- -----------------------------------------------------------------------------
-- STEP 4 — FIX section_type inside JSON (MariaDB safe)
-- -----------------------------------------------------------------------------
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


-- -----------------------------------------------------------------------------
-- STEP 5 — RESTORE BANNER IMAGES from template where missing
-- -----------------------------------------------------------------------------
UPDATE `page_sections` ps
INNER JOIN `page_sections` tmpl
    ON tmpl.page_id = @template_id
    AND tmpl.section_type = ps.section_type
    AND tmpl.`order` = ps.`order`
    AND tmpl.section_type IN ('hero-banner', 'banner')
INNER JOIN `pages` p ON p.id = ps.page_id
SET ps.data = JSON_SET(
    ps.data,
    '$.image', JSON_UNQUOTE(JSON_EXTRACT(tmpl.data, '$.image')),
    '$.section_type', ps.section_type
)
WHERE p.category_id IS NOT NULL
  AND p.id <> @template_id
  AND JSON_VALID(ps.data)
  AND JSON_VALID(tmpl.data)
  AND (
    JSON_EXTRACT(ps.data, '$.image') IS NULL
    OR JSON_UNQUOTE(JSON_EXTRACT(ps.data, '$.image')) = ''
  )
  AND JSON_EXTRACT(tmpl.data, '$.image') IS NOT NULL
  AND JSON_UNQUOTE(JSON_EXTRACT(tmpl.data, '$.image')) <> '';


-- -----------------------------------------------------------------------------
-- STEP 6 — VERIFY (should return 0 rows if all fixed)
-- -----------------------------------------------------------------------------
SELECT
    p.id,
    p.page_name,
    p.url,
    COUNT(ps.id) AS sections
FROM `pages` p
LEFT JOIN `page_sections` ps ON ps.page_id = p.id
WHERE p.category_id IS NOT NULL
GROUP BY p.id, p.page_name, p.url
HAVING sections = 0
ORDER BY p.id;


-- -----------------------------------------------------------------------------
-- STEP 7 — CHECK BANNERS (pages still missing banner image in data)
-- -----------------------------------------------------------------------------
SELECT
    p.id,
    p.page_name,
    ps.section_type,
    JSON_UNQUOTE(JSON_EXTRACT(ps.data, '$.image')) AS banner_image
FROM `pages` p
INNER JOIN `page_sections` ps ON ps.page_id = p.id
WHERE p.category_id IS NOT NULL
  AND ps.section_type IN ('hero-banner', 'banner')
  AND (
    JSON_EXTRACT(ps.data, '$.image') IS NULL
    OR JSON_UNQUOTE(JSON_EXTRACT(ps.data, '$.image')) = ''
  )
ORDER BY p.id;

-- If STEP 7 shows rows: open each page in Admin → Edit → set banner image → Save
-- (After code deploy, save will NOT wipe images anymore)
