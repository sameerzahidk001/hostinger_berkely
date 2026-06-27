#!/bin/bash
set -e

# User uploads live under public/ but are usually NOT committed to git.
# A plain `git pull` + `git clean` on Hostinger deletes them. This script
# archives uploads to storage/ (gitignored), pulls code, then restores files.

PRESERVE_ROOT="storage/app/preserved-public-uploads"
DEPLOY_BRANCH="${DEPLOY_BRANCH:-fix/rollback-jun19-night}"
UPLOAD_DIRS=(
  "public/images/library"
  "public/images/clients"
  "public/images/profiles"
  "public/images"
  "public/admin/courses"
)

preserve_uploads() {
  echo "==> Archiving user uploads (before code update)"
  for dir in "${UPLOAD_DIRS[@]}"; do
    if [ -d "$dir" ]; then
      mkdir -p "$PRESERVE_ROOT/$dir"
      cp -a "$dir/." "$PRESERVE_ROOT/$dir/" 2>/dev/null || true
    else
      mkdir -p "$dir"
    fi
  done
}

restore_uploads() {
  echo "==> Restoring user uploads (after code update)"
  for dir in "${UPLOAD_DIRS[@]}"; do
    if [ -d "$PRESERVE_ROOT/$dir" ]; then
      mkdir -p "$dir"
      cp -a "$PRESERVE_ROOT/$dir/." "$dir/" 2>/dev/null || true
    fi
  done
}

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

echo "==> Done. User uploads preserved in $PRESERVE_ROOT"
echo "!! Never run: git clean -fd  (it deletes uploaded images)"
echo "!! Use only: bash deploy-hostinger.sh"
