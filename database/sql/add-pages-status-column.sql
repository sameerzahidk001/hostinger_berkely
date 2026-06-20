-- Page active/disabled status (safe to re-run)
ALTER TABLE `pages`
    ADD COLUMN IF NOT EXISTS `status` TINYINT(1) NOT NULL DEFAULT 1 AFTER `url`;

-- Disable learner-stories CMS page if it still exists (hard route also returns 404)
UPDATE `pages` SET `status` = 0 WHERE `url` = 'learner-stories';
