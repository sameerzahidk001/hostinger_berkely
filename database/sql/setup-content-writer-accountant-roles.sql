-- =============================================================================
-- Berkeley admin: Content Writer + Accountant roles (phpMyAdmin / Hostinger)
-- Safe to re-run. Does NOT require php artisan migrate.
--
-- What this does:
--   1. Creates "content_writer" role (renames old "librarian" if present)
--   2. Ensures "accountant" role exists
--   3. Creates ONE Content Writer login IF none exists yet
--
-- Default Content Writer login (change password after first login):
--   URL:   https://YOUR-SITE.com/login
--   Email: contentwriter@berkeleyme.com
--   Pass:  password
--
-- Admin / Superadmin only:
--   URL:   https://YOUR-SITE.com/admin/login
-- =============================================================================

-- 1) Content Writer role
INSERT INTO `roles` (`name`, `description`, `created_at`, `updated_at`)
SELECT
    'content_writer',
    'Content Writer — limited admin access for content management.',
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `roles` WHERE `name` IN ('content_writer', 'librarian')
);

UPDATE `roles`
SET
    `name` = 'content_writer',
    `description` = 'Content Writer — limited admin access for content management.',
    `updated_at` = NOW()
WHERE `name` = 'librarian';

-- 2) Accountant role (your existing accountant user is kept as-is)
INSERT INTO `roles` (`name`, `description`, `created_at`, `updated_at`)
SELECT
    'accountant',
    'Accountant — payments and currency access only.',
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `roles` WHERE `name` = 'accountant'
);

-- 3) Content Writer user — only if no content writer / librarian user exists
INSERT INTO `users` (`name`, `email`, `password`, `approved`, `image`, `created_at`, `updated_at`)
SELECT
    'Content Writer',
    'contentwriter@berkeleyme.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    1,
    '/images/profiles/user.png',
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1
    FROM `user_has_roles` uhr
    INNER JOIN `roles` r ON r.id = uhr.role_id
    WHERE r.name IN ('content_writer', 'librarian')
)
AND NOT EXISTS (
    SELECT 1 FROM `users` WHERE `email` = 'contentwriter@berkeleyme.com'
);

-- 4) Assign Content Writer role to that user
INSERT INTO `user_has_roles` (`user_id`, `role_id`)
SELECT u.id, r.id
FROM `users` u
CROSS JOIN `roles` r
WHERE u.email = 'contentwriter@berkeleyme.com'
  AND r.name = 'content_writer'
  AND NOT EXISTS (
      SELECT 1 FROM `user_has_roles` uhr
      WHERE uhr.user_id = u.id AND uhr.role_id = r.id
  );

-- 5) Verify (optional — run separately to check)
-- SELECT u.id, u.name, u.email, r.name AS role
-- FROM users u
-- JOIN user_has_roles uhr ON uhr.user_id = u.id
-- JOIN roles r ON r.id = uhr.role_id
-- WHERE r.name IN ('content_writer', 'accountant');
