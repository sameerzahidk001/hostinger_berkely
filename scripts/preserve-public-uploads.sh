#!/bin/bash
# Shared backup/restore for Hostinger user uploads (profile photos, library images, etc.)

PRESERVE_ROOT="${PRESERVE_ROOT:-storage/app/preserved-public-uploads}"

UPLOAD_DIRS=(
  "public/images/library"
  "public/images/clients"
  "public/images/profiles"
  "public/images"
  "public/admin/courses"
)

preserve_uploads() {
  echo "==> Archiving user uploads to $PRESERVE_ROOT"
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
  if [ ! -d "$PRESERVE_ROOT" ]; then
    return 0
  fi

  echo "==> Restoring user uploads from $PRESERVE_ROOT"
  for dir in "${UPLOAD_DIRS[@]}"; do
    if [ -d "$PRESERVE_ROOT/$dir" ]; then
      mkdir -p "$dir"
      cp -a "$PRESERVE_ROOT/$dir/." "$dir/" 2>/dev/null || true
    fi
  done
}

refresh_upload_backup() {
  preserve_uploads
}
