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
export PRESERVE_ROOT="${PRESERVE_ROOT:-$ROOT/../persistent-uploads}"
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

echo "==> Ensure Noon live keys exist in .env (fill missing/empty only)"
if [ -f .env ]; then
  upsert_env() {
    key="$1"
    val="$2"
    if grep -q "^${key}=" .env; then
      current="$(grep "^${key}=" .env | head -n1 | cut -d= -f2-)"
      if [ -z "$current" ]; then
        sed -i "s|^${key}=.*|${key}=${val}|" .env
      fi
    else
      echo "${key}=${val}" >> .env
    fi
  }
  upsert_env NOON_BUSINESS_ID berkeley
  upsert_env NOON_APP_ID BerkeleyWeb
  upsert_env NOON_APP_KEY 706e59eb4057482b9a8172880f327351
  upsert_env NOON_AUTH_SCHEME Key
  upsert_env NOON_MODE live
  upsert_env NOON_API_URL https://api.noonpayments.com/payment/v1
  upsert_env NOON_ORDER_CATEGORY pay
  upsert_env NOON_CHANNEL web
  upsert_env NOON_CURRENCY AED
  upsert_env NOON_PAYMENT_ACTION SALE
  upsert_env NOON_WEBHOOK_SECRET d3aa6de3-2653-4c6f-851e-51794d1dc32b

  # Zoho Meeting Lab + WorkDrive + Calendar (bdm@berkeleyme.com OAuth)
  # Force-write so a blank/stale token on Hostinger is replaced.
  force_env() {
    key="$1"
    val="$2"
    if grep -q "^${key}=" .env; then
      sed -i "s|^${key}=.*|${key}=${val}|" .env
    else
      echo "${key}=${val}" >> .env
    fi
  }
  force_env ZOHO_ACCOUNT_EMAIL bdm@berkeleyme.com
  force_env ZOHO_ACCOUNTS_URL https://accounts.zoho.com
  force_env ZOHO_MEETING_URL https://meeting.zoho.com
  force_env ZOHO_CLIENT_ID 1000.WW01EMQ73P97FDWYKHPPPL46PLCG4F
  force_env ZOHO_CLIENT_SECRET df268c77655674d5c29009939053e5a8f929ab1890
  force_env ZOHO_REFRESH_TOKEN 1000.621d0b7d565917edf2fa28a26ca07c9c.fd6eeb269a175580907d41c10ead466e
  force_env ZOHO_ORG_ID 667841096
  force_env ZOHO_PRESENTER_ZUID 813723220
  force_env ZOHO_WORKDRIVE_FOLDER_ID spuntc5376892cd29487691e0c9d20e212f62
  force_env ZOHO_CALENDAR_UID a911f7d47515486dadb3ef2e6a0bcb34
  force_env ZOHO_TIMEZONE Asia/Dubai
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
php artisan migrate --path=database/migrations/2026_09_16_000001_add_head_of_faculty_to_class_schedules.php --force

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
