-- Speeds up /admin/analytics so nginx does not 504.
ALTER TABLE page_views ADD INDEX page_views_created_at_index (created_at);
ALTER TABLE page_views ADD INDEX page_views_updated_at_index (updated_at);
