#!/bin/bash
set -e

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

php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Done. Open your site URL."
