#!/bin/bash
set -e

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
HOOK_SRC="$ROOT/scripts/git-hooks/post-merge"
HOOK_DST="$ROOT/.git/hooks/post-merge"

if [ ! -d "$ROOT/.git" ]; then
  echo "Not a git repository — skipped hook install."
  exit 0
fi

cp "$HOOK_SRC" "$HOOK_DST"
chmod +x "$HOOK_DST"
echo "Installed post-merge hook: uploads restore after every git pull."
