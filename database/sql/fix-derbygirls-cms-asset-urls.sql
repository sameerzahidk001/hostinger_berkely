-- Fix CMS HTML still pointing at derbygirls.co.uk (IGCSE timetable tick icons, etc.)
-- Run on production after deploy. Safe to re-run.

SET @new = 'https://eduberkeley.com';
SET @old = 'https://derbygirls.co.uk';
SET @oldh = 'http://derbygirls.co.uk';

UPDATE `page_sections`
SET `data` = REPLACE(REPLACE(REPLACE(REPLACE(
    `data`, CONCAT(@old, '/public/'), CONCAT(@new, '/')), CONCAT(@oldh, '/public/'), CONCAT(@new, '/')),
    @old, @new), @oldh, @new)
WHERE `data` LIKE '%derbygirls.co.uk%';

UPDATE `page_sections`
SET `data` = REPLACE(`data`, CONCAT(@new, '/public/'), CONCAT(@new, '/'))
WHERE `data` LIKE '%eduberkeley.com/public/%';

UPDATE `courses`
SET `description` = REPLACE(REPLACE(REPLACE(REPLACE(
    `description`, CONCAT(@old, '/public/'), CONCAT(@new, '/')), CONCAT(@oldh, '/public/'), CONCAT(@new, '/')),
    @old, @new), @oldh, @new)
WHERE `description` LIKE '%derbygirls.co.uk%';

UPDATE `courses`
SET `short_description` = REPLACE(REPLACE(REPLACE(REPLACE(
    `short_description`, CONCAT(@old, '/public/'), CONCAT(@new, '/')), CONCAT(@oldh, '/public/'), CONCAT(@new, '/')),
    @old, @new), @oldh, @new)
WHERE `short_description` LIKE '%derbygirls.co.uk%';

UPDATE `widgets`
SET `description` = REPLACE(REPLACE(REPLACE(REPLACE(
    `description`, CONCAT(@old, '/public/'), CONCAT(@new, '/')), CONCAT(@oldh, '/public/'), CONCAT(@new, '/')),
    @old, @new), @oldh, @new)
WHERE `description` LIKE '%derbygirls.co.uk%';
