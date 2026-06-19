-- =============================================================================
-- Grant Analytics access to Content Writer role
-- Run on LIVE and/or testberk (u739774248_dbf3zedfyyctp / u739774248_testberk)
-- Safe to re-run.
--
-- NOTE: The Analytics sidebar tab is controlled by PHP (admin_menu_allowed).
-- You must also deploy the helpers.php change that adds 'analytics' to the
-- content writer menu list. This SQL grants the analytic-list permission only.
-- =============================================================================

-- Ensure permission exists
INSERT INTO `permissions` (`name`, `module`, `created_at`, `updated_at`)
SELECT 'analytic-list', 'analytic', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` WHERE `name` = 'analytic-list'
);

SET @cw_role_id = (
    SELECT `id` FROM `roles`
    WHERE `name` IN ('content_writer', 'Content Writer', 'librarian', 'content-writer')
       OR `description` LIKE '%Content Writer%'
    ORDER BY FIELD(`name`, 'content_writer', 'Content Writer', 'librarian', 'content-writer')
    LIMIT 1
);

SET @analytic_perm_id = (
    SELECT `id` FROM `permissions` WHERE `name` = 'analytic-list' LIMIT 1
);

INSERT INTO `role_has_permissions` (`role_id`, `permission_id`)
SELECT @cw_role_id, @analytic_perm_id
WHERE @cw_role_id IS NOT NULL
  AND @analytic_perm_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM `role_has_permissions`
      WHERE `role_id` = @cw_role_id AND `permission_id` = @analytic_perm_id
  );

-- Verify
SELECT r.name AS role, p.name AS permission
FROM `role_has_permissions` rhp
INNER JOIN `roles` r ON r.id = rhp.role_id
INNER JOIN `permissions` p ON p.id = rhp.permission_id
WHERE r.id = @cw_role_id AND p.name = 'analytic-list';
