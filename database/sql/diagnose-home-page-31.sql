-- Quick diagnosis for blank Home page (page id 31)
-- Run on LIVE: u739774248_dbf3zedfyyctp

SELECT home FROM site_settings LIMIT 1;

SELECT COUNT(*) AS section_count FROM page_sections WHERE page_id = 31;

SELECT id, section_type, `order`, CHAR_LENGTH(data) AS data_bytes,
       LEFT(JSON_UNQUOTE(JSON_EXTRACT(data, '$.title')), 60) AS title_preview
FROM page_sections
WHERE page_id = 31
ORDER BY `order`;

-- Rows with human-readable section types (won't render until normalized)
SELECT id, section_type
FROM page_sections
WHERE page_id = 31
  AND section_type NOT IN (
    'hero-banner','banner','filter-courses','course-agendas','testimonials',
    'category','school-category','programmes','title-section','media-section',
    'clients','grid-cards','cards','list','separator','content','certificate',
    'contactus','career','search-bar','instructors'
  );
