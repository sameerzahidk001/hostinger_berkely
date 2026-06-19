-- Image alt text columns for SEO
-- Run each statement; skip any that report "duplicate column"

ALTER TABLE `courses` ADD COLUMN `image_alts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL;
ALTER TABLE `course_dynamic_labels` ADD COLUMN `image_alts` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL;
ALTER TABLE `clients` ADD COLUMN `image_alt` varchar(255) DEFAULT NULL;
ALTER TABLE `learner_stories` ADD COLUMN `image_alt` varchar(255) DEFAULT NULL;
ALTER TABLE `subjects` ADD COLUMN `image_alt` varchar(255) DEFAULT NULL;
ALTER TABLE `homepage_sections` ADD COLUMN `image_alt` varchar(255) DEFAULT NULL;
ALTER TABLE `course_testimonials` ADD COLUMN `image_alt` varchar(255) DEFAULT NULL;
ALTER TABLE `pages_s_e_o_s` ADD COLUMN `thumbnail_alt` varchar(255) DEFAULT NULL;
ALTER TABLE `schools` ADD COLUMN `image_alt` varchar(255) DEFAULT NULL;
ALTER TABLE `schools` ADD COLUMN `icon_alt` varchar(255) DEFAULT NULL;
