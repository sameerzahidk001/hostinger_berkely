-- =============================================================================
-- Fix Content Writer + Accountant admin panel access (phpMyAdmin / Hostinger)
-- Safe to re-run. No php artisan migrate required.
--
-- Fixes:
--   1. Ensures Content Writer + Accountant roles exist (any name variant)
--   2. Assigns correct role to somia@ / finance@ / contentwriter@
--   3. Removes student/instructor role from panel users (stops student portal UI)
--   4. Activates + verifies panel users
--
-- AFTER running this SQL, users MUST log in at:
--   Content Writer / Accountant: https://eduberkeley.com/login
--   Admin / Superadmin only:    https://eduberkeley.com/admin/login
--
-- Default passwords (if reset below): password
-- Hash: $2y$10$Knz.8dB1.de1coPzu3znI.rUW2XSi7lGVLanS6Unpr7RF.6S4cl.q
-- =============================================================================

-- ---------------------------------------------------------------------------
-- 1) Roles — create / normalize
-- ---------------------------------------------------------------------------
INSERT INTO `roles` (`name`, `description`, `created_at`, `updated_at`)
SELECT 'content_writer', 'Content Writer — limited admin access for content management.', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `roles`
    WHERE `name` IN ('content_writer', 'Content Writer', 'librarian', 'content-writer')
       OR `description` LIKE '%Content Writer%'
);

INSERT INTO `roles` (`name`, `description`, `created_at`, `updated_at`)
SELECT 'accountant', 'Accountant — payments and currency access only.', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `roles`
    WHERE `name` IN ('accountant', 'Accountant')
       OR `description` LIKE '%Accountant%'
);

-- Rename librarian → content_writer if still present
UPDATE `roles`
SET `name` = 'content_writer',
    `description` = 'Content Writer — limited admin access for content management.',
    `updated_at` = NOW()
WHERE `name` = 'librarian';

-- Optional: standardize "Content Writer" display name to content_writer (code supports both)
-- UPDATE `roles` SET `name` = 'content_writer', `updated_at` = NOW() WHERE `name` = 'Content Writer';

SET @cw_role_id = (
    SELECT `id` FROM `roles`
    WHERE `name` IN ('content_writer', 'Content Writer', 'librarian', 'content-writer')
       OR `description` LIKE '%Content Writer%'
    ORDER BY FIELD(`name`, 'content_writer', 'Content Writer', 'librarian', 'content-writer')
    LIMIT 1
);

SET @acc_role_id = (
    SELECT `id` FROM `roles`
    WHERE `name` IN ('accountant', 'Accountant')
       OR `description` LIKE '%Accountant%'
    ORDER BY FIELD(`name`, 'accountant', 'Accountant')
    LIMIT 1
);

SET @student_role_id = (SELECT `id` FROM `roles` WHERE `name` = 'student' LIMIT 1);
SET @instructor_role_id = (SELECT `id` FROM `roles` WHERE `name` = 'instructor' LIMIT 1);

-- ---------------------------------------------------------------------------
-- 2) Specific users (from your screenshots)
-- ---------------------------------------------------------------------------

-- Somia — Content Writer
UPDATE `users`
SET `approved` = 1, `email_verified_at` = COALESCE(`email_verified_at`, NOW()), `updated_at` = NOW()
WHERE `email` = 'somia@berkeleyholding.com';

DELETE uhr FROM `user_has_roles` uhr
INNER JOIN `users` u ON u.id = uhr.user_id
WHERE u.email = 'somia@berkeleyholding.com'
  AND uhr.role_id <> @cw_role_id;

INSERT INTO `user_has_roles` (`user_id`, `role_id`, `created_at`)
SELECT u.id, @cw_role_id, NOW()
FROM `users` u
WHERE u.email = 'somia@berkeleyholding.com'
  AND @cw_role_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `user_has_roles` uhr
      WHERE uhr.user_id = u.id AND uhr.role_id = @cw_role_id
  );

-- Finance / Accountant
UPDATE `users`
SET `approved` = 1, `email_verified_at` = COALESCE(`email_verified_at`, NOW()), `updated_at` = NOW()
WHERE `email` IN ('finance@berkeleyme.com', 'finance@eduberkeley.com');

DELETE uhr FROM `user_has_roles` uhr
INNER JOIN `users` u ON u.id = uhr.user_id
WHERE u.email IN ('finance@berkeleyme.com', 'finance@eduberkeley.com')
  AND uhr.role_id <> @acc_role_id;

INSERT INTO `user_has_roles` (`user_id`, `role_id`, `created_at`)
SELECT u.id, @acc_role_id, NOW()
FROM `users` u
WHERE u.email IN ('finance@berkeleyme.com', 'finance@eduberkeley.com')
  AND @acc_role_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `user_has_roles` uhr
      WHERE uhr.user_id = u.id AND uhr.role_id = @acc_role_id
  );

-- Default content writer account (if exists)
UPDATE `users`
SET `approved` = 1, `email_verified_at` = COALESCE(`email_verified_at`, NOW()), `updated_at` = NOW()
WHERE `email` = 'contentwriter@berkeleyme.com';

DELETE uhr FROM `user_has_roles` uhr
INNER JOIN `users` u ON u.id = uhr.user_id
WHERE u.email = 'contentwriter@berkeleyme.com'
  AND uhr.role_id <> @cw_role_id;

INSERT INTO `user_has_roles` (`user_id`, `role_id`, `created_at`)
SELECT u.id, @cw_role_id, NOW()
FROM `users` u
WHERE u.email = 'contentwriter@berkeleyme.com'
  AND @cw_role_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `user_has_roles` uhr
      WHERE uhr.user_id = u.id AND uhr.role_id = @cw_role_id
  );

-- ---------------------------------------------------------------------------
-- 3) ALL content writers — remove student/instructor role (wrong portal)
-- ---------------------------------------------------------------------------
DELETE uhr FROM `user_has_roles` uhr
WHERE @cw_role_id IS NOT NULL
  AND uhr.user_id IN (SELECT user_id FROM (SELECT user_id FROM `user_has_roles` WHERE role_id = @cw_role_id) AS cw_users)
  AND uhr.role_id IN (@student_role_id, @instructor_role_id);

-- ALL accountants — remove student/instructor role
DELETE uhr FROM `user_has_roles` uhr
WHERE @acc_role_id IS NOT NULL
  AND uhr.user_id IN (SELECT user_id FROM (SELECT user_id FROM `user_has_roles` WHERE role_id = @acc_role_id) AS acc_users)
  AND uhr.role_id IN (@student_role_id, @instructor_role_id);

-- ---------------------------------------------------------------------------
-- 4) Optional password reset to: password
-- Uncomment if login fails after role fix
-- ---------------------------------------------------------------------------
-- UPDATE `users` SET `password` = '$2y$10$Knz.8dB1.de1coPzu3znI.rUW2XSi7lGVLanS6Unpr7RF.6S4cl.q', `updated_at` = NOW()
-- WHERE `email` IN (
--   'somia@berkeleyholding.com',
--   'finance@berkeleyme.com',
--   'contentwriter@berkeleyme.com'
-- );

-- ---------------------------------------------------------------------------
-- 5) Verify — run separately to check results
-- ---------------------------------------------------------------------------
-- SELECT u.id, u.name, u.email, u.approved, GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ', ') AS roles
-- FROM `users` u
-- LEFT JOIN `user_has_roles` uhr ON uhr.user_id = u.id
-- LEFT JOIN `roles` r ON r.id = uhr.role_id
-- WHERE u.email IN (
--   'somia@berkeleyholding.com',
--   'finance@berkeleyme.com',
--   'contentwriter@berkeleyme.com'
-- )
-- GROUP BY u.id, u.name, u.email, u.approved;
