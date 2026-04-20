#!/usr/bin/env bash
#
# Write an Nginx server block for a hosted domain.
# Usage: hosting-vhost-nginx.sh <domain> <document_root>
#
# Required env:
#   HOSTING_VHOST_OUTPUT_DIR  Directory to write the .conf file (must exist and be writable).
#
# After this runs, Laravel can run scripts/hosting-vhost-nginx-activate.sh when
# HOSTING_VHOST_NGINX_ACTIVATE=true (copies into /etc/nginx and reloads; needs sudo).
#
# Optional env:
#   PHP_FPM_SOCKET            e.g. /var/run/php/php8.3-fpm.sock
#   NGINX_EXTRA_SERVER_NAMES  Space-separated extra server_name entries (e.g. "www.example.com")
#   NGINX_CLIENT_MAX_BODY     Default 64m
#
set -euo pipefail

DOMAIN="${1:?Usage: $0 <domain> <document_root>}"
WEB_ROOT="${2:?Usage: $0 <domain> <document_root>}"
OUTPUT_DIR="${HOSTING_VHOST_OUTPUT_DIR:?Set HOSTING_VHOST_OUTPUT_DIR to a writable directory}"

PHP_SOCK="${PHP_FPM_SOCKET:-/var/run/php/php8.3-fpm.sock}"
CLIENT_MAX="${NGINX_CLIENT_MAX_BODY:-64m}"
EXTRA_NAMES="${NGINX_EXTRA_SERVER_NAMES:-}"

if [[ ! -d "$OUTPUT_DIR" ]]; then
  echo "ERROR: HOSTING_VHOST_OUTPUT_DIR is not a directory: $OUTPUT_DIR" >&2
  exit 1
fi

if [[ ! -d "$WEB_ROOT" ]]; then
  echo "ERROR: document root does not exist: $WEB_ROOT" >&2
  exit 1
fi

# Safe filename for config file
SAFE_NAME="${DOMAIN//[^a-zA-Z0-9._-]/_}"
CONF_PATH="${OUTPUT_DIR}/${SAFE_NAME}.conf"

SERVER_NAMES="$DOMAIN"
if [[ -n "$EXTRA_NAMES" ]]; then
  SERVER_NAMES="$DOMAIN $EXTRA_NAMES"
fi

LOG_DIR="$(dirname "$WEB_ROOT")/logs"
mkdir -p "$LOG_DIR" 2>/dev/null || true

cat > "$CONF_PATH" <<EOF
# Xenweet panel — generated for ${DOMAIN}
# Install: sudo cp ${CONF_PATH} /etc/nginx/sites-available/${SAFE_NAME}.conf
#          sudo ln -sf /etc/nginx/sites-available/${SAFE_NAME}.conf /etc/nginx/sites-enabled/
#          sudo nginx -t && sudo systemctl reload nginx

server {
    listen 80;
    listen [::]:80;
    server_name ${SERVER_NAMES};

    root ${WEB_ROOT};
    index index.php index.html index.htm;

    client_max_body_size ${CLIENT_MAX};

    access_log ${LOG_DIR}/nginx-access.log;
    error_log  ${LOG_DIR}/nginx-error.log;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include fastcgi_params;
        fastcgi_pass unix:${PHP_SOCK};
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

echo "OK: wrote ${CONF_PATH}"
echo "Next (as root): sudo cp ${CONF_PATH} /etc/nginx/sites-available/${SAFE_NAME}.conf"
echo "     sudo ln -sf /etc/nginx/sites-available/${SAFE_NAME}.conf /etc/nginx/sites-enabled/"
echo "     sudo nginx -t && sudo systemctl reload nginx"

exit 0
