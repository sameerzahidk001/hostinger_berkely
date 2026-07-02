#!/bin/bash
set -e

# =============================================================================
# HOSTINGER DEPLOY — always use this script (or: bash deploy.sh)
#
#   cd ~/domains/eduberkeley.com/public_html
#   bash deploy.sh
#
# Do NOT run plain `git pull` or `git clean -fd` — that can remove uploaded
# profile photos and CMS images. This script backs them up first, then restores.
# =============================================================================

ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

# shellcheck source=/dev/null
. "$ROOT/scripts/preserve-public-uploads.sh"

DEPLOY_BRANCH="${DEPLOY_BRANCH:-fix/rollback-jun19-night}"

preserve_uploads

if [ -d .git ]; then
  echo "==> Git pull ($DEPLOY_BRANCH)"
  git pull origin "$DEPLOY_BRANCH"
else
  echo "!! Not a git repo — skipped git pull"
fi

restore_uploads

echo "==> Composer install"
composer install --no-dev --optimize-autoloader

if [ ! -f .env ]; then
  echo "==> Creating .env from .env.example"
  cp .env.example .env
  php artisan key:generate --force
  echo "!! Edit .env with database + mail credentials, then run this script again."
  exit 1
fi

echo "==> Laravel setup"
php artisan storage:link --force || true
chmod -R 775 storage bootstrap/cache

echo "==> Running safe migrations (on top of production DB)"
php artisan migrate --path=database/migrations/2026_06_15_000001_create_currency_rates_table.php --force
php artisan migrate --path=database/migrations/2026_06_15_000002_add_audit_columns_to_content_tables.php --force
php artisan migrate --path=database/migrations/2026_06_16_000001_add_referrer_to_page_views_table.php --force
php artisan migrate --path=database/migrations/2026_06_16_000002_add_profile_fields_to_users_table.php --force
php artisan migrate --path=database/migrations/2026_06_16_000003_add_module_to_permissions_table.php --force
php artisan migrate --path=database/migrations/2026_06_16_000004_add_audit_columns_to_pages_seo_table.php --force
php artisan migrate --path=database/migrations/2026_06_16_000005_add_source_to_payments_table.php --force
php artisan migrate --path=database/migrations/2026_06_19_000001_create_user_activity_logs_table.php --force
php artisan migrate --path=database/migrations/2026_06_19_000002_add_image_alt_columns.php --force
php artisan berkely:ensure-image-alt-columns
php artisan berkely:ensure-seo-focus-keyword-column
php artisan migrate --path=database/migrations/2026_06_20_000001_add_status_to_pages_table.php --force
php artisan migrate --path=database/migrations/2026_06_22_000001_add_image_to_admins_table.php --force
php artisan migrate --path=database/migrations/2026_06_23_000001_add_focus_keyword_to_pages_seo_table.php --force

php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

bash "$ROOT/scripts/install-hostinger-git-hooks.sh" || true
refresh_upload_backup

echo ""
echo "==> Deploy complete."
echo "    Upload backup: $PRESERVE_ROOT"
echo "    Next time use: bash deploy.sh"
echo "    Never run: git clean -fd"
