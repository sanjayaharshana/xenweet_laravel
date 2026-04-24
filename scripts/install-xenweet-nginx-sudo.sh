#!/usr/bin/env bash
#
# Run once on the server (as a user with sudo):
#   bash scripts/install-xenweet-nginx-sudo.sh
#
# Installs root-owned helpers under /usr/local/sbin/ and sudoers so the PHP
# user (www-data) can run ONLY these commands without a password.
#
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PHP_USER="${1:-www-data}"

echo "Installing xenweet nginx helpers from ${ROOT} (sudo user: ${PHP_USER})..."

sudo install -m 755 -o root -g root "${ROOT}/scripts/xenweet-nginx-activate" /usr/local/sbin/xenweet-nginx-activate
sudo install -m 755 -o root -g root "${ROOT}/scripts/xenweet-nginx-deactivate" /usr/local/sbin/xenweet-nginx-deactivate
sudo install -m 755 -o root -g root "${ROOT}/scripts/xenweet-nginx-install-ssl" /usr/local/sbin/xenweet-nginx-install-ssl

TMP="$(mktemp)"
{
  echo "Defaults:${PHP_USER} !requiretty"
  echo "${PHP_USER} ALL=(root) NOPASSWD: /usr/local/sbin/xenweet-nginx-activate, /usr/local/sbin/xenweet-nginx-deactivate, /usr/local/sbin/xenweet-nginx-install-ssl"
} > "$TMP"

sudo install -m 440 -o root -g root "$TMP" /etc/sudoers.d/xenweet-nginx
rm -f "$TMP"

sudo visudo -cf /etc/sudoers.d/xenweet-nginx

echo "Done. Verify (should list the two NOPASSWD lines): sudo -u ${PHP_USER} sudo -n -l"
echo ""
echo "IMPORTANT: The helper must accept <domain> <output_dir> (sudo drops env)."
echo "After git pull, always re-run this script so /usr/local/sbin/xenweet-nginx-activate stays current:"
echo "  bash ${ROOT}/scripts/install-xenweet-nginx-sudo.sh ${PHP_USER}"
echo "Then retry provisioning from the panel."
