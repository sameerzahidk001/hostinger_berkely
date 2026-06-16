#!/bin/bash
# Run on Hostinger via SSH from Laravel project root (where artisan exists)
set -e

echo "==> Creating storage directories"
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/framework/testing
mkdir -p storage/logs
mkdir -p bootstrap/cache
chmod -R 775 storage bootstrap/cache

if [ ! -f .env ]; then
  echo "==> Creating .env"
  cp .env.example .env
  php artisan key:generate --force
  echo "!! Edit .env: DB_*, APP_URL=https://test.berkeleyme.com, then run this script again."
  exit 1
fi

echo "==> Composer"
if [ ! -d vendor ]; then
  composer install --no-dev --optimize-autoloader
fi

echo "==> Laravel"
php artisan storage:link --force || true
php artisan config:clear
php artisan cache:clear
php artisan view:clear

if [ -f database/fix-test-urls.sql ]; then
  echo "==> Tip: run database/fix-test-urls.sql in phpMyAdmin to replace eduberkeley.com menu links"
fi

php artisan migrate --path=database/migrations/2026_06_15_000001_create_currency_rates_table.php --force
php artisan migrate --path=database/migrations/2026_06_15_000002_add_audit_columns_to_content_tables.php --force
php artisan migrate --path=database/migrations/2026_06_16_000001_add_referrer_to_page_views_table.php --force
php artisan migrate --path=database/migrations/2026_06_16_000002_add_profile_fields_to_users_table.php --force
php artisan migrate --path=database/migrations/2026_06_16_000003_add_module_to_permissions_table.php --force
php artisan migrate --path=database/migrations/2026_06_16_000004_add_audit_columns_to_pages_seo_table.php --force
php artisan migrate --path=database/migrations/2026_06_16_000005_add_source_to_payments_table.php --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Done. Check https://test.berkeleyme.com"
