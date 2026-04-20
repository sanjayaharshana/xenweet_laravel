#!/usr/bin/env bash
#
# Install generated nginx snippet into sites-available + sites-enabled and reload nginx.
# Requires passwordless sudo for the PHP user, e.g. in /etc/sudoers.d/xenweet-nginx:
#
#   www-data ALL=(root) NOPASSWD: /bin/cp, /bin/ln, /usr/sbin/nginx, /bin/systemctl
#
# Or a single wrapper path — adjust to match your OS (nginx binary path).
#
# Usage: hosting-vhost-nginx-activate.sh <domain>
# Env:   HOSTING_VHOST_OUTPUT_DIR (same as vhost generator)
#
set -euo pipefail

DOMAIN="${1:?Usage: $0 <domain>}"
OUTPUT_DIR="${HOSTING_VHOST_OUTPUT_DIR:?Set HOSTING_VHOST_OUTPUT_DIR}"

SAFE_NAME="${DOMAIN//[^a-zA-Z0-9._-]/_}"
SRC="${OUTPUT_DIR}/${SAFE_NAME}.conf"
DEST="/etc/nginx/sites-available/${SAFE_NAME}.conf"
ENABLED="/etc/nginx/sites-enabled/${SAFE_NAME}.conf"

if [[ ! -f "$SRC" ]]; then
  echo "ERROR: missing generated file: $SRC" >&2
  exit 1
fi

# -n = non-interactive (fails fast if sudo needs a password)
sudo -n cp "$SRC" "$DEST"
sudo -n ln -sf "$DEST" "$ENABLED"
sudo -n nginx -t
sudo -n systemctl reload nginx

echo "OK: nginx activated (${DEST}, ${ENABLED})"
