#!/usr/bin/env bash
#
# Fallback: multiple "sudo -n" calls (needs many NOPASSWD rules — awkward).
#
# Preferred on production: run once on the server:
#   bash scripts/install-xenweet-nginx-sudo.sh
# That installs /usr/local/sbin/xenweet-nginx-activate; Laravel then uses a
# single "sudo -n" with one sudoers entry.
#
# Usage: hosting-vhost-nginx-activate.sh <domain> [output_dir]
# Env:   HOSTING_VHOST_OUTPUT_DIR (if second arg omitted; not set under sudo)
#
set -euo pipefail

DOMAIN="${1:?Usage: $0 <domain> [output_dir]}"
if [[ -n "${2:-}" ]]; then
  OUTPUT_DIR="$2"
elif [[ -n "${HOSTING_VHOST_OUTPUT_DIR:-}" ]]; then
  OUTPUT_DIR="$HOSTING_VHOST_OUTPUT_DIR"
else
  echo "ERROR: set HOSTING_VHOST_OUTPUT_DIR or pass output_dir as second argument" >&2
  exit 1
fi

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
