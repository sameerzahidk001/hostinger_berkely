#!/bin/bash
# Use this instead of plain `git pull` on Hostinger if you cannot run deploy-hostinger.sh.
set -e

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

# shellcheck source=/dev/null
. "$ROOT/scripts/preserve-public-uploads.sh"

BRANCH="${1:-${DEPLOY_BRANCH:-fix/rollback-jun19-night}}"

preserve_uploads
git pull origin "$BRANCH"
restore_uploads
refresh_upload_backup

echo "==> Safe pull complete. Prefer: bash deploy.sh"
