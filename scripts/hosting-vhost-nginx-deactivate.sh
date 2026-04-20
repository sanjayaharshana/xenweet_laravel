#!/usr/bin/env bash
#
# Remove nginx site for this domain from sites-enabled / sites-available and reload.
# Same sudo requirements as hosting-vhost-nginx-activate.sh
#
# Usage: hosting-vhost-nginx-deactivate.sh <domain>
#
set -euo pipefail

DOMAIN="${1:?Usage: $0 <domain>}"

SAFE_NAME="${DOMAIN//[^a-zA-Z0-9._-]/_}"
ENABLED="/etc/nginx/sites-enabled/${SAFE_NAME}.conf"
AVAIL="/etc/nginx/sites-available/${SAFE_NAME}.conf"

sudo -n rm -f "$ENABLED"
sudo -n rm -f "$AVAIL"
sudo -n nginx -t
sudo -n systemctl reload nginx

echo "OK: nginx site removed for ${DOMAIN}"
