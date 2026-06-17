-- Invoice footer columns for site_settings (admin editable)
SET @db = DATABASE();

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='site_settings' AND COLUMN_NAME='invoice_footer_usa')=0,
  'ALTER TABLE `site_settings` ADD COLUMN `invoice_footer_usa` TEXT NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='site_settings' AND COLUMN_NAME='invoice_footer_uk')=0,
  'ALTER TABLE `site_settings` ADD COLUMN `invoice_footer_uk` TEXT NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='site_settings' AND COLUMN_NAME='invoice_footer_middle_east')=0,
  'ALTER TABLE `site_settings` ADD COLUMN `invoice_footer_middle_east` TEXT NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='site_settings' AND COLUMN_NAME='invoice_footer_email')=0,
  'ALTER TABLE `site_settings` ADD COLUMN `invoice_footer_email` VARCHAR(255) NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='site_settings' AND COLUMN_NAME='invoice_footer_website')=0,
  'ALTER TABLE `site_settings` ADD COLUMN `invoice_footer_website` VARCHAR(255) NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='site_settings' AND COLUMN_NAME='invoice_footer_presence')=0,
  'ALTER TABLE `site_settings` ADD COLUMN `invoice_footer_presence` VARCHAR(500) NULL', 'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE `site_settings` SET
  `invoice_footer_usa` = '<strong>USA &amp; Canada:</strong> 2001 Addison Street, Suite 300, Berkeley, CA, 94704. &nbsp; | &nbsp; <strong>T:</strong> +1 (407) 371 9886',
  `invoice_footer_uk` = '<strong>UK &amp; Europe:</strong> 124 City Road, London EC1V 2NX, United Kingdom. &nbsp; | &nbsp; <strong>T:</strong> +44 7 306 279 111',
  `invoice_footer_middle_east` = '<strong>Middle East:</strong> Floor 25, Sheikh Rashid Tower, Dubai World Trade Centre, Dubai, UAE. &nbsp; | &nbsp; <strong>T:</strong> +971 585 55 56 57',
  `invoice_footer_email` = 'Finance@berkeleyme.com',
  `invoice_footer_website` = 'www.eduberkeley.com',
  `invoice_footer_presence` = 'USA | Canada | UK | UAE | KSA | China | Africa'
WHERE `invoice_footer_usa` IS NULL OR `invoice_footer_usa` = '';

INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
('2026_06_17_000001_add_invoice_footer_to_site_settings_table', 999);
