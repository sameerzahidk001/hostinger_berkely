#!/bin/bash
# Shared backup/restore for Hostinger user uploads (profile photos, library images, etc.)
# Store OUTSIDE the git working tree so Hostinger auto-deploy cannot delete them.

SCRIPT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PRESERVE_ROOT="${PRESERVE_ROOT:-$SCRIPT_ROOT/../persistent-uploads}"

UPLOAD_DIRS=(
  "public/admin/courses"
  "public/images/library"
  "public/images/clients"
  "public/images/profiles"
  "public/images"
)

preserve_uploads() {
  echo "==> Archiving user uploads to $PRESERVE_ROOT"
  mkdir -p "$PRESERVE_ROOT"
  for dir in "${UPLOAD_DIRS[@]}"; do
    if [ -d "$SCRIPT_ROOT/$dir" ]; then
      mkdir -p "$PRESERVE_ROOT/$dir"
      cp -a "$SCRIPT_ROOT/$dir/." "$PRESERVE_ROOT/$dir/" 2>/dev/null || true
    else
      mkdir -p "$SCRIPT_ROOT/$dir"
    fi
  done
}

restore_uploads() {
  if [ ! -d "$PRESERVE_ROOT" ]; then
    return 0
  fi

  echo "==> Restoring user uploads from $PRESERVE_ROOT"
  for dir in "${UPLOAD_DIRS[@]}"; do
    if [ -d "$PRESERVE_ROOT/$dir" ]; then
      mkdir -p "$SCRIPT_ROOT/$dir"
      cp -a "$PRESERVE_ROOT/$dir/." "$SCRIPT_ROOT/$dir/" 2>/dev/null || true
    fi
  done
}

refresh_upload_backup() {
  preserve_uploads
}
