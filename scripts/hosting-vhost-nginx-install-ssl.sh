#!/usr/bin/env bash
#
# Configure an nginx site for HTTPS and reload nginx.
# Usage: hosting-vhost-nginx-install-ssl.sh <domain> <web_root> <key_pem_path> <fullchain_pem_path> [php_fpm_socket] [extra_server_names]
#
# 5th arg: PHP-FPM unix socket (required when invoked via "sudo" helpers — env PHP_FPM_SOCKET is not forwarded).
# 6th arg: extra server_name values (space-separated; optional). sudo helpers do not forward
#   NGINX_EXTRA_SERVER_NAMES, so the panel should pass 6th argv. Env NGINX_EXTRA_SERVER_NAMES is
#   used if 6th is empty.
# Optional env: PHP_FPM_SOCKET (used when 5th arg is omitted; default php8.3)
#
# Requires sudo -n permissions for cp/nginx/systemctl when not running as root.
#
set -euo pipefail

DOMAIN="${1:?Usage: $0 <domain> <web_root> <key_pem_path> <fullchain_pem_path> [php_fpm_socket]}"
WEB_ROOT="${2:?Usage: $0 <domain> <web_root> <key_pem_path> <fullchain_pem_path> [php_fpm_socket]}"
KEY_PATH="${3:?Usage: $0 <domain> <web_root> <key_pem_path> <fullchain_pem_path> [php_fpm_socket]}"
FULLCHAIN_PATH="${4:?Usage: $0 <domain> <web_root> <key_pem_path> <fullchain_pem_path> [php_fpm_socket]}"
PHP_SOCK="${5:-${PHP_FPM_SOCKET:-/var/run/php/php8.3-fpm.sock}}"
EXTRA_NAMES="${6:-${NGINX_EXTRA_SERVER_NAMES:-}}"
SERVER_NAMES="$DOMAIN"
if [[ -n "$EXTRA_NAMES" ]]; then
  SERVER_NAMES="$DOMAIN $EXTRA_NAMES"
fi

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
    server_name ${SERVER_NAMES};

    # Let's Encrypt HTTP-01 (certbot) — must stay on plain HTTP
    location ^~ /.well-known/acme-challenge/ {
        root ${WEB_ROOT};
        default_type "text/plain";
    }

    location / {
        return 301 https://\$host\$request_uri;
    }
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${SERVER_NAMES};

    root ${WEB_ROOT};
    index index.php index.html index.htm;

    client_max_body_size 64m;

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

    location ~ \.php\$ {
        include fastcgi_params;
        fastcgi_index index.php;
        fastcgi_param HTTPS on;
        fastcgi_param REQUEST_SCHEME \$scheme;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_pass unix:${PHP_SOCK};
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
