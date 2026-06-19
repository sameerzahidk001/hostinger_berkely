-- Fix section_type inside page_sections.data JSON (MariaDB / phpMyAdmin safe)
-- Run on LIVE or SUBLIVE. No CAST(... AS JSON) — works on Hostinger MariaDB.

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
