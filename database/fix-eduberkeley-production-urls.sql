-- =============================================================================
-- Fix image & content URLs after moving site to https://eduberkeley.com
-- Run in phpMyAdmin on the PRODUCTION database (eduberkeley.com).
-- IMPORTANT: Paste and run the ENTIRE file from line 1 (SET @new = ...).
-- Safe to re-run.
--
-- ALSO set in Hostinger .env:
--   APP_URL=https://eduberkeley.com
--   APP_ENV=production
--
-- Ensure document root points to /public and image files exist under:
--   public/images/library/
--   public/images/
--   public/admin/
-- =============================================================================

SET @new = 'https://eduberkeley.com';

-- Old domains to replace (add more if needed)
SET @old1 = 'https://test.berkeleyme.com';
SET @old1h = 'http://test.berkeleyme.com';
SET @old2 = 'https://testing.eduberkeley.com';
SET @old2h = 'http://testing.eduberkeley.com';
SET @old3 = 'https://berkeleyme.com';
SET @old3h = 'http://berkeleyme.com';
SET @old4 = 'https://www.berkeleyme.com';
SET @old4h = 'http://www.berkeleyme.com';

-- ---------------------------------------------------------------------------
-- 1) Menus & links
-- ---------------------------------------------------------------------------
UPDATE `menus`
SET `link` = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    `link`, @old1, @new), @old1h, @new), @old2, @new), @old2h, @new), @old3, @new), @old4, @new)
WHERE `link` LIKE '%berkeleyme.com%' OR `link` LIKE '%testing.eduberkeley.com%';

-- ---------------------------------------------------------------------------
-- 2) Site settings (logos, favicon, icons)
-- Strip full URLs → keep filename only (works with media_url helper)
-- ---------------------------------------------------------------------------
UPDATE `site_settings` SET `logo` = TRIM(BOTH '/' FROM REPLACE(`logo`, CONCAT(@old1, '/public/images/'), '')) WHERE `logo` LIKE '%berkeleyme%' OR `logo` LIKE '%eduberkeley.com/public%';
UPDATE `site_settings` SET `logo` = TRIM(BOTH '/' FROM REPLACE(`logo`, CONCAT(@old1, '/images/library/'), '')) WHERE `logo` LIKE '%berkeleyme%';
UPDATE `site_settings` SET `logo` = TRIM(BOTH '/' FROM REPLACE(`logo`, CONCAT(@new, '/public/images/'), '')) WHERE `logo` LIKE '%eduberkeley.com/public%';
UPDATE `site_settings` SET `logo` = TRIM(BOTH '/' FROM REPLACE(`logo`, CONCAT(@new, '/images/library/'), '')) WHERE `logo` LIKE '%eduberkeley.com/images%';

UPDATE `site_settings` SET `white_logo` = TRIM(BOTH '/' FROM REPLACE(`white_logo`, CONCAT(@old1, '/public/images/'), '')) WHERE `white_logo` LIKE '%berkeleyme%' OR `white_logo` LIKE '%eduberkeley.com/public%';
UPDATE `site_settings` SET `white_logo` = TRIM(BOTH '/' FROM REPLACE(`white_logo`, CONCAT(@new, '/public/images/'), '')) WHERE `white_logo` LIKE '%eduberkeley.com/public%';
UPDATE `site_settings` SET `white_logo` = TRIM(BOTH '/' FROM REPLACE(`white_logo`, CONCAT(@new, '/images/library/'), '')) WHERE `white_logo` LIKE '%eduberkeley.com/images%';

UPDATE `site_settings` SET `favicon` = TRIM(BOTH '/' FROM REPLACE(`favicon`, CONCAT(@old1, '/public/images/'), '')) WHERE `favicon` LIKE '%berkeleyme%' OR `favicon` LIKE '%eduberkeley.com/public%';
UPDATE `site_settings` SET `favicon` = TRIM(BOTH '/' FROM REPLACE(`favicon`, CONCAT(@new, '/public/images/'), '')) WHERE `favicon` LIKE '%eduberkeley.com/public%';
UPDATE `site_settings` SET `favicon` = TRIM(BOTH '/' FROM REPLACE(`favicon`, CONCAT(@new, '/images/library/'), '')) WHERE `favicon` LIKE '%eduberkeley.com/images%';

UPDATE `site_settings` SET `mobile_logo` = TRIM(BOTH '/' FROM REPLACE(`mobile_logo`, CONCAT(@old1, '/public/images/'), '')) WHERE `mobile_logo` LIKE '%berkeleyme%' OR `mobile_logo` LIKE '%eduberkeley.com/public%';
UPDATE `site_settings` SET `mobile_logo` = TRIM(BOTH '/' FROM REPLACE(`mobile_logo`, CONCAT(@new, '/public/images/'), '')) WHERE `mobile_logo` LIKE '%eduberkeley.com/public%';
UPDATE `site_settings` SET `mobile_logo` = TRIM(BOTH '/' FROM REPLACE(`mobile_logo`, CONCAT(@new, '/images/library/'), '')) WHERE `mobile_logo` LIKE '%eduberkeley.com/images%';

UPDATE `site_settings` SET `header_search_image` = TRIM(BOTH '/' FROM REPLACE(`header_search_image`, CONCAT(@old1, '/public/images/'), '')) WHERE `header_search_image` LIKE '%berkeleyme%' OR `header_search_image` LIKE '%eduberkeley.com/public%';
UPDATE `site_settings` SET `header_search_image` = TRIM(BOTH '/' FROM REPLACE(`header_search_image`, CONCAT(@new, '/public/images/'), '')) WHERE `header_search_image` LIKE '%eduberkeley.com/public%';
UPDATE `site_settings` SET `header_search_image` = TRIM(BOTH '/' FROM REPLACE(`header_search_image`, CONCAT(@new, '/images/library/'), '')) WHERE `header_search_image` LIKE '%eduberkeley.com/images%';

UPDATE `site_settings` SET `facebook_icon` = TRIM(BOTH '/' FROM REPLACE(`facebook_icon`, CONCAT(@new, '/public/images/'), '')) WHERE `facebook_icon` LIKE '%/public/%';
UPDATE `site_settings` SET `twitter_icon` = TRIM(BOTH '/' FROM REPLACE(`twitter_icon`, CONCAT(@new, '/public/images/'), '')) WHERE `twitter_icon` LIKE '%/public/%';
UPDATE `site_settings` SET `instagram_icon` = TRIM(BOTH '/' FROM REPLACE(`instagram_icon`, CONCAT(@new, '/public/images/'), '')) WHERE `instagram_icon` LIKE '%/public/%';
UPDATE `site_settings` SET `youtube_icon` = TRIM(BOTH '/' FROM REPLACE(`youtube_icon`, CONCAT(@new, '/public/images/'), '')) WHERE `youtube_icon` LIKE '%/public/%';
UPDATE `site_settings` SET `linkedin_icon` = TRIM(BOTH '/' FROM REPLACE(`linkedin_icon`, CONCAT(@new, '/public/images/'), '')) WHERE `linkedin_icon` LIKE '%/public/%';
UPDATE `site_settings` SET `whatsapp_icon` = TRIM(BOTH '/' FROM REPLACE(`whatsapp_icon`, CONCAT(@new, '/public/images/'), '')) WHERE `whatsapp_icon` LIKE '%/public/%';
UPDATE `site_settings` SET `tiktok_icon` = TRIM(BOTH '/' FROM REPLACE(`tiktok_icon`, CONCAT(@new, '/public/images/'), '')) WHERE `tiktok_icon` LIKE '%/public/%';

UPDATE `site_settings` SET `header_button_url` = REPLACE(`header_button_url`, @old1, @new) WHERE `header_button_url` LIKE '%berkeleyme.com%';
UPDATE `site_settings` SET `header_button_url` = REPLACE(`header_button_url`, @old2, @new) WHERE `header_button_url` LIKE '%testing.eduberkeley.com%';
UPDATE `site_settings` SET `header_search_url` = REPLACE(`header_search_url`, @old1, @new) WHERE `header_search_url` LIKE '%berkeleyme.com%';
UPDATE `site_settings` SET `header_search_url` = REPLACE(`header_search_url`, @old2, @new) WHERE `header_search_url` LIKE '%testing.eduberkeley.com%';

-- ---------------------------------------------------------------------------
-- 3) Page builder JSON (most broken images live here)
-- Run each block below (one UPDATE per line is safer in phpMyAdmin).
-- ---------------------------------------------------------------------------
UPDATE `page_sections` SET `data` = REPLACE(`data`, CONCAT(@old1, '/public/'), CONCAT(@new, '/')) WHERE `data` LIKE '%test.berkeleyme.com/public/%';
UPDATE `page_sections` SET `data` = REPLACE(`data`, CONCAT(@old1h, '/public/'), CONCAT(@new, '/')) WHERE `data` LIKE '%test.berkeleyme.com/public/%';
UPDATE `page_sections` SET `data` = REPLACE(`data`, CONCAT(@old2, '/public/'), CONCAT(@new, '/')) WHERE `data` LIKE '%testing.eduberkeley.com/public/%';
UPDATE `page_sections` SET `data` = REPLACE(`data`, CONCAT(@old2h, '/public/'), CONCAT(@new, '/')) WHERE `data` LIKE '%testing.eduberkeley.com/public/%';
UPDATE `page_sections` SET `data` = REPLACE(`data`, CONCAT(@new, '/public/'), CONCAT(@new, '/')) WHERE `data` LIKE '%eduberkeley.com/public/%';
UPDATE `page_sections` SET `data` = REPLACE(`data`, @old1, @new) WHERE `data` LIKE '%test.berkeleyme.com%';
UPDATE `page_sections` SET `data` = REPLACE(`data`, @old1h, @new) WHERE `data` LIKE '%test.berkeleyme.com%';
UPDATE `page_sections` SET `data` = REPLACE(`data`, @old2, @new) WHERE `data` LIKE '%testing.eduberkeley.com%';
UPDATE `page_sections` SET `data` = REPLACE(`data`, @old2h, @new) WHERE `data` LIKE '%testing.eduberkeley.com%';
UPDATE `page_sections` SET `data` = REPLACE(`data`, @old3, @new) WHERE `data` LIKE '%berkeleyme.com%';
UPDATE `page_sections` SET `data` = REPLACE(`data`, @old3h, @new) WHERE `data` LIKE '%berkeleyme.com%';

-- ---------------------------------------------------------------------------
-- 4) SEO thumbnails, course images, clients, testimonials
-- (simple one-column UPDATEs — safe for phpMyAdmin)
-- ---------------------------------------------------------------------------
UPDATE `pages_s_e_o_s` SET `thumbnail` = REPLACE(`thumbnail`, CONCAT(@new, '/public/'), CONCAT(@new, '/')) WHERE `thumbnail` LIKE '%/public/%';
UPDATE `pages_s_e_o_s` SET `thumbnail` = REPLACE(`thumbnail`, CONCAT(@old1, '/public/'), '') WHERE `thumbnail` LIKE '%test.berkeleyme.com%';
UPDATE `pages_s_e_o_s` SET `thumbnail` = REPLACE(`thumbnail`, CONCAT(@old2, '/public/'), '') WHERE `thumbnail` LIKE '%testing.eduberkeley.com%';
UPDATE `pages_s_e_o_s` SET `thumbnail` = REPLACE(`thumbnail`, @old1, '') WHERE `thumbnail` LIKE '%test.berkeleyme.com%';
UPDATE `pages_s_e_o_s` SET `thumbnail` = REPLACE(`thumbnail`, @old2, '') WHERE `thumbnail` LIKE '%testing.eduberkeley.com%';

UPDATE `courses` SET `thumbnail` = REPLACE(`thumbnail`, CONCAT(@new, '/public/'), CONCAT(@new, '/')) WHERE `thumbnail` LIKE '%/public/%';
UPDATE `courses` SET `thumbnail` = REPLACE(`thumbnail`, CONCAT(@old1, '/public/'), '') WHERE `thumbnail` LIKE '%berkeleyme%';
UPDATE `courses` SET `thumbnail` = REPLACE(`thumbnail`, @old1, @new) WHERE `thumbnail` LIKE '%berkeleyme%';
UPDATE `courses` SET `thumbnail` = REPLACE(`thumbnail`, @old2, @new) WHERE `thumbnail` LIKE '%testing.eduberkeley.com%';

UPDATE `courses` SET `overview_img` = REPLACE(`overview_img`, CONCAT(@new, '/public/'), CONCAT(@new, '/')) WHERE `overview_img` LIKE '%/public/%';
UPDATE `courses` SET `overview_img` = REPLACE(`overview_img`, @old1, @new) WHERE `overview_img` LIKE '%berkeleyme%';
UPDATE `courses` SET `overview_img` = REPLACE(`overview_img`, @old2, @new) WHERE `overview_img` LIKE '%testing.eduberkeley.com%';

UPDATE `courses` SET `post_image` = REPLACE(`post_image`, CONCAT(@new, '/public/'), CONCAT(@new, '/')) WHERE `post_image` LIKE '%/public/%';
UPDATE `courses` SET `post_image` = REPLACE(`post_image`, @old1, @new) WHERE `post_image` LIKE '%berkeleyme%';
UPDATE `courses` SET `post_image` = REPLACE(`post_image`, @old2, @new) WHERE `post_image` LIKE '%testing.eduberkeley.com%';

UPDATE `courses` SET `course_brochure` = REPLACE(`course_brochure`, CONCAT(@new, '/public/'), CONCAT(@new, '/')) WHERE `course_brochure` LIKE '%/public/%';
UPDATE `courses` SET `course_brochure` = REPLACE(`course_brochure`, @old1, @new) WHERE `course_brochure` LIKE '%berkeleyme%';
UPDATE `courses` SET `course_brochure` = REPLACE(`course_brochure`, @old2, @new) WHERE `course_brochure` LIKE '%testing.eduberkeley.com%';

UPDATE `courses` SET `description` = REPLACE(`description`, CONCAT(@new, '/public/'), CONCAT(@new, '/')) WHERE `description` LIKE '%/public/%';
UPDATE `courses` SET `description` = REPLACE(`description`, @old1, @new) WHERE `description` LIKE '%berkeleyme%';
UPDATE `courses` SET `description` = REPLACE(`description`, @old1h, @new) WHERE `description` LIKE '%berkeleyme%';
UPDATE `courses` SET `description` = REPLACE(`description`, @old2, @new) WHERE `description` LIKE '%testing.eduberkeley.com%';
UPDATE `courses` SET `description` = REPLACE(`description`, @old2h, @new) WHERE `description` LIKE '%testing.eduberkeley.com%';

UPDATE `courses` SET `short_description` = REPLACE(`short_description`, CONCAT(@new, '/public/'), CONCAT(@new, '/')) WHERE `short_description` LIKE '%/public/%';
UPDATE `courses` SET `short_description` = REPLACE(`short_description`, @old1, @new) WHERE `short_description` LIKE '%berkeleyme%';
UPDATE `courses` SET `short_description` = REPLACE(`short_description`, @old2, @new) WHERE `short_description` LIKE '%testing.eduberkeley.com%';

UPDATE `courses` SET `exam_information` = REPLACE(`exam_information`, CONCAT(@new, '/public/'), CONCAT(@new, '/')) WHERE `exam_information` LIKE '%/public/%';
UPDATE `courses` SET `exam_information` = REPLACE(`exam_information`, @old1, @new) WHERE `exam_information` LIKE '%berkeleyme%';
UPDATE `courses` SET `exam_information` = REPLACE(`exam_information`, @old2, @new) WHERE `exam_information` LIKE '%testing.eduberkeley.com%';

UPDATE `clients` SET `image` = REPLACE(`image`, CONCAT(@new, '/public/'), CONCAT(@new, '/')) WHERE `image` LIKE '%/public/%';
UPDATE `clients` SET `image` = REPLACE(`image`, @old1, @new) WHERE `image` LIKE '%berkeleyme%';
UPDATE `clients` SET `image` = REPLACE(`image`, @old2, @new) WHERE `image` LIKE '%testing.eduberkeley.com%';

UPDATE `course_testimonials` SET `image` = REPLACE(`image`, CONCAT(@new, '/public/'), CONCAT(@new, '/')) WHERE `image` LIKE '%/public/%';
UPDATE `course_testimonials` SET `image` = REPLACE(`image`, @old1, @new) WHERE `image` LIKE '%berkeleyme%';
UPDATE `course_testimonials` SET `image` = REPLACE(`image`, @old2, @new) WHERE `image` LIKE '%testing.eduberkeley.com%';

UPDATE `users` SET `image` = REPLACE(`image`, CONCAT(@new, '/public/'), CONCAT(@new, '/')) WHERE `image` LIKE '%/public/%';
UPDATE `users` SET `image` = REPLACE(`image`, @old1, @new) WHERE `image` LIKE '%berkeleyme%';
UPDATE `users` SET `image` = REPLACE(`image`, @old2, @new) WHERE `image` LIKE '%testing.eduberkeley.com%';

-- ---------------------------------------------------------------------------
-- 5) Widgets (if any HTML/images stored)
-- ---------------------------------------------------------------------------
UPDATE `widgets` SET `description` = REPLACE(`description`, CONCAT(@new, '/public/'), CONCAT(@new, '/')) WHERE `description` LIKE '%/public/%';
UPDATE `widgets` SET `description` = REPLACE(`description`, @old1, @new) WHERE `description` LIKE '%berkeleyme.com%';
UPDATE `widgets` SET `description` = REPLACE(`description`, @old1h, @new) WHERE `description` LIKE '%berkeleyme.com%';
UPDATE `widgets` SET `description` = REPLACE(`description`, @old2, @new) WHERE `description` LIKE '%testing.eduberkeley.com%';
UPDATE `widgets` SET `description` = REPLACE(`description`, @old2h, @new) WHERE `description` LIKE '%testing.eduberkeley.com%';

-- ---------------------------------------------------------------------------
-- Done — clear Laravel cache on server after this:
--   php artisan config:clear
--   php artisan cache:clear
--   php artisan view:clear
-- =============================================================================
