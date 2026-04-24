#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PHP_USER="${1:-www-data}"

echo "Installing SSH jailed helper for ${PHP_USER}..."

sudo install -m 755 -o root -g root "${ROOT}/scripts/hosting-ssh-create-jailed.sh" /usr/local/sbin/hosting-ssh-create-jailed.sh
sudo install -m 755 -o root -g root "${ROOT}/scripts/xenweet-ssh-create-jailed" /usr/local/sbin/xenweet-ssh-create-jailed

TMP="$(mktemp)"
{
  echo "Defaults:${PHP_USER} !requiretty"
  echo "${PHP_USER} ALL=(root) NOPASSWD: /usr/local/sbin/xenweet-ssh-create-jailed"
} > "$TMP"

sudo install -m 440 -o root -g root "$TMP" /etc/sudoers.d/xenweet-ssh-access
rm -f "$TMP"

sudo visudo -cf /etc/sudoers.d/xenweet-ssh-access

echo "Done. Verify:"
echo "  sudo -u ${PHP_USER} sudo -n /usr/local/sbin/xenweet-ssh-create-jailed testuser TestPass123 /tmp /tmp"
