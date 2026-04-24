#!/usr/bin/env bash
#
# Configure an nginx site for HTTPS and reload nginx.
# Usage: hosting-vhost-nginx-install-ssl.sh <domain> <web_root> <key_pem_path> <fullchain_pem_path>
#
# Requires sudo -n permissions for cp/nginx/systemctl when not running as root.
#
set -euo pipefail

DOMAIN="${1:?Usage: $0 <domain> <web_root> <key_pem_path> <fullchain_pem_path>}"
WEB_ROOT="${2:?Usage: $0 <domain> <web_root> <key_pem_path> <fullchain_pem_path>}"
KEY_PATH="${3:?Usage: $0 <domain> <web_root> <key_pem_path> <fullchain_pem_path>}"
FULLCHAIN_PATH="${4:?Usage: $0 <domain> <web_root> <key_pem_path> <fullchain_pem_path>}"
PHP_SOCK="${PHP_FPM_SOCKET:-/var/run/php/php8.3-fpm.sock}"

SAFE_NAME="${DOMAIN//[^a-zA-Z0-9._-]/_}"
DEST="/etc/nginx/sites-available/${SAFE_NAME}.conf"
ENABLED="/etc/nginx/sites-enabled/${SAFE_NAME}.conf"

if [[ ! -f "$KEY_PATH" ]]; then
  echo "ERROR: key PEM not found: $KEY_PATH" >&2
  exit 1
fi
if [[ ! -f "$FULLCHAIN_PATH" ]]; then
  echo "ERROR: fullchain PEM not found: $FULLCHAIN_PATH" >&2
  exit 1
fi

TMP_CONF="$(mktemp)"
cat > "$TMP_CONF" <<EOF
# Xenweet panel — SSL enabled for ${DOMAIN}
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${DOMAIN};

    root ${WEB_ROOT};
    index index.php index.html index.htm;

    ssl_certificate ${FULLCHAIN_PATH};
    ssl_certificate_key ${KEY_PATH};
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:${PHP_SOCK};
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

sudo -n cp "$TMP_CONF" "$DEST"
sudo -n ln -sf "$DEST" "$ENABLED"
sudo -n nginx -t
sudo -n systemctl reload nginx
rm -f "$TMP_CONF"

echo "OK: nginx SSL installed (${DEST})"
