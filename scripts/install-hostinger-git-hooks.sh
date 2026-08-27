#!/bin/bash
set -e

ROOT="$(cd "$(dirname "$0")/.." && pwd)"

if [ ! -d "$ROOT/.git" ]; then
  echo "Not a git repository — skipped hook install."
  exit 0
fi

mkdir -p "$ROOT/.git/hooks"

for hook in post-merge post-checkout; do
  cat > "$ROOT/.git/hooks/$hook" <<'EOF'
#!/bin/bash
ROOT="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
HOOK_SCRIPT="$ROOT/scripts/preserve-public-uploads.sh"
if [ -f "$HOOK_SCRIPT" ]; then
  # shellcheck source=/dev/null
  . "$HOOK_SCRIPT"
  restore_uploads
fi
EOF
  chmod +x "$ROOT/.git/hooks/$hook"
done

echo "Installed post-merge and post-checkout hooks: restore uploads after git deploy."
