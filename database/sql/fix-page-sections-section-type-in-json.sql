-- Ensure section_type exists inside page_sections.data JSON (fixes sections missing from admin edit form)
-- Safe to re-run on live after copying sections from another page.

UPDATE `page_sections`
SET `data` = JSON_SET(CAST(`data` AS JSON), '$.section_type', `section_type`)
WHERE `section_type` IS NOT NULL
  AND `section_type` <> ''
  AND JSON_VALID(`data`)
  AND (
    JSON_EXTRACT(`data`, '$.section_type') IS NULL
    OR JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.section_type')) = ''
  );
