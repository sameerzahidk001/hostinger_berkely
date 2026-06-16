-- Run on test/staging after importing production DB.
-- Usage (phpMyAdmin or SSH):
--   mysql -u USER -p DATABASE < database/fix-test-urls.sql
-- Or replace the domain below before running.

SET @old = 'https://eduberkeley.com';
SET @old_http = 'http://eduberkeley.com';
SET @new = 'https://test.berkeleyme.com';

UPDATE menus
SET link = REPLACE(REPLACE(link, @old, @new), @old_http, @new)
WHERE link LIKE '%eduberkeley.com%';

UPDATE page_sections
SET data = REPLACE(REPLACE(data, @old, @new), @old_http, @new)
WHERE data LIKE '%eduberkeley.com%';

UPDATE site_settings
SET
  logo = REPLACE(REPLACE(logo, CONCAT(@old, '/public/images/'), ''), CONCAT(@old_http, '/public/images/'), ''),
  white_logo = REPLACE(REPLACE(white_logo, CONCAT(@old, '/public/images/'), ''), CONCAT(@old_http, '/public/images/'), ''),
  mobile_logo = REPLACE(REPLACE(mobile_logo, CONCAT(@old, '/public/images/'), ''), CONCAT(@old_http, '/public/images/'), ''),
  favicon = REPLACE(REPLACE(favicon, CONCAT(@old, '/public/images/'), ''), CONCAT(@old_http, '/public/images/'), ''),
  header_button_url = REPLACE(REPLACE(header_button_url, @old, @new), @old_http, @new),
  header_search_url = REPLACE(REPLACE(header_search_url, @old, @new), @old_http, @new),
  whatsapp_url = REPLACE(REPLACE(whatsapp_url, @old, @new), @old_http, @new),
  facebook_url = REPLACE(REPLACE(facebook_url, @old, @new), @old_http, @new),
  twitter_url = REPLACE(REPLACE(twitter_url, @old, @new), @old_http, @new),
  instagram_url = REPLACE(REPLACE(instagram_url, @old, @new), @old_http, @new),
  linkedin_url = REPLACE(REPLACE(linkedin_url, @old, @new), @old_http, @new),
  youtube_url = REPLACE(REPLACE(youtube_url, @old, @new), @old_http, @new),
  tiktok_url = REPLACE(REPLACE(tiktok_url, @old, @new), @old_http, @new)
WHERE id = 1;

UPDATE courses
SET
  description = REPLACE(REPLACE(description, @old, @new), @old_http, @new),
  short_description = REPLACE(REPLACE(short_description, @old, @new), @old_http, @new),
  exam_information = REPLACE(REPLACE(exam_information, @old, @new), @old_http, @new)
WHERE description LIKE '%eduberkeley.com%'
   OR short_description LIKE '%eduberkeley.com%'
   OR exam_information LIKE '%eduberkeley.com%';

UPDATE pages
SET description = REPLACE(REPLACE(description, @old, @new), @old_http, @new)
WHERE description LIKE '%eduberkeley.com%';
