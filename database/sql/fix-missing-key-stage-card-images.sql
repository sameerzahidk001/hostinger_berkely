-- Restore Key Stage 4 & 5 homepage card images (files were 404 on server).
-- After deploy, also ensure these files exist under public/images/library/:
--   key-stage-4-6a3f0e7e5d3e1.png  (copy from public/frontend/images/jpg/GCSE.jpg)
--   key-stage-5-6a3f0e7e5ca66.png  (copy from public/frontend/images/jpg/six-form.jpg)
--
-- Or update CMS data to use frontend paths directly:

UPDATE page_sections
SET data = REPLACE(data, 'key-stage-4-6a3f0e7e5d3e1.png', 'frontend/images/jpg/GCSE.jpg')
WHERE data LIKE '%key-stage-4-6a3f0e7e5d3e1.png%';

UPDATE page_sections
SET data = REPLACE(data, 'key-stage-5-6a3f0e7e5ca66.png', 'frontend/images/jpg/six-form.jpg')
WHERE data LIKE '%key-stage-5-6a3f0e7e5ca66.png%';
