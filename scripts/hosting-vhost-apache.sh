#!/usr/bin/env bash
#
# Write an Apache 2.4+ VirtualHost for a hosted domain.
# Usage: hosting-vhost-apache.sh <domain> <document_root>
#
# Required env:
#   HOSTING_VHOST_OUTPUT_DIR  Directory to write the .conf file.
#
# Optional env:
#   APACHE_LOG_PREFIX   Directory for CustomLog/ErrorLog (default: <host_root>/logs)
#
set -euo pipefail

DOMAIN="${1:?Usage: $0 <domain> <document_root>}"
WEB_ROOT="${2:?Usage: $0 <domain> <document_root>}"
OUTPUT_DIR="${HOSTING_VHOST_OUTPUT_DIR:?Set HOSTING_VHOST_OUTPUT_DIR to a writable directory}"

if [[ ! -d "$OUTPUT_DIR" ]]; then
  echo "ERROR: HOSTING_VHOST_OUTPUT_DIR is not a directory: $OUTPUT_DIR" >&2
  exit 1
fi

if [[ ! -d "$WEB_ROOT" ]]; then
  echo "ERROR: document root does not exist: $WEB_ROOT" >&2
  exit 1
fi

SAFE_NAME="${DOMAIN//[^a-zA-Z0-9._-]/_}"
CONF_PATH="${OUTPUT_DIR}/${SAFE_NAME}-apache.conf"

LOG_DIR="${APACHE_LOG_PREFIX:-$(dirname "$WEB_ROOT")/logs}"
mkdir -p "$LOG_DIR" 2>/dev/null || true

cat > "$CONF_PATH" <<EOF
# Xenweet panel — generated for ${DOMAIN}
# Install: sudo cp ${CONF_PATH} /etc/apache2/sites-available/${SAFE_NAME}.conf
#          sudo a2ensite ${SAFE_NAME}
#          sudo systemctl reload apache2

<VirtualHost *:80>
    ServerName ${DOMAIN}
    ServerAdmin webmaster@${DOMAIN}
    DocumentRoot ${WEB_ROOT}

    <Directory ${WEB_ROOT}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${LOG_DIR}/apache-error.log
    CustomLog ${LOG_DIR}/apache-access.log combined
</VirtualHost>
EOF

echo "OK: wrote ${CONF_PATH}"
echo "Next (as root): sudo cp ${CONF_PATH} /etc/apache2/sites-available/${SAFE_NAME}.conf"
echo "     sudo a2ensite ${SAFE_NAME} && sudo systemctl reload apache2"

exit 0
