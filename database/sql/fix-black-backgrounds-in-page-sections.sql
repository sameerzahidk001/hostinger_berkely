-- One-time cleanup: remove accidental #000000 from section/card fields saved by the
-- transparent-checkbox bug. Does NOT touch background_color (used by banners).
-- Run on production after deploying the code fix, then clear view cache.

-- Restore black banner backgrounds where the color was cleared by the bug
UPDATE page_sections
SET data = REPLACE(data, '"background_color": ""', '"background_color": "#000000"')
WHERE section_type = 'banner' AND data LIKE '%"background_color": ""%';

UPDATE page_sections
SET data = REPLACE(data, '"background": "#000000"', '"background": ""')
WHERE data LIKE '%"background": "#000000"%';

UPDATE page_sections
SET data = REPLACE(data, '"content_background": "#000000"', '"content_background": ""')
WHERE data LIKE '%"content_background": "#000000"%';

UPDATE page_sections
SET data = REPLACE(data, '"card_background": "#000000"', '"card_background": "#ffffff"')
WHERE data LIKE '%"card_background": "#000000"%';

UPDATE page_sections
SET data = REPLACE(data, '"background": "#000"', '"background": ""')
WHERE data LIKE '%"background": "#000"%';
