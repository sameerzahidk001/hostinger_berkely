-- Run on production if migrate is unavailable.
ALTER TABLE `pages_s_e_o_s`
    ADD COLUMN `focus_keyword` VARCHAR(80) NULL AFTER `meta_description`;
