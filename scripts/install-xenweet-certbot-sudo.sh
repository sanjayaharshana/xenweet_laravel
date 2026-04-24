#!/usr/bin/env bash
#
# Run once on the server (as root or a user with sudo to write /etc/sudoers.d):
#   sudo bash scripts/install-xenweet-certbot-sudo.sh
#   sudo bash scripts/install-xenweet-certbot-sudo.sh www-data
#   sudo bash scripts/install-xenweet-certbot-sudo.sh www-data /usr/bin/certbot
#
# Grants the PHP / FPM user passwordless sudo for certbot only (same idea as
# scripts/install-xenweet-nginx-sudo.sh). Required when the panel runs certbot as
# `sudo -n certbot ...` and would otherwise see: "sudo: a password is required".
#
set -euo pipefail

PHP_USER="${1:-www-data}"
CERTBOT_ARG="${2:-}"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "ERROR: run as root, e.g. sudo bash $0 $*" >&2
  exit 1
fi

resolve_certbot() {
  if [[ -n "$CERTBOT_ARG" ]]; then
    echo "$CERTBOT_ARG"
    return
  fi
  if command -v certbot &>/dev/null; then
    command -v certbot
    return
  fi
  if [[ -x /usr/bin/certbot ]]; then
    echo /usr/bin/certbot
    return
  fi
  if [[ -x /usr/local/bin/certbot ]]; then
    echo /usr/local/bin/certbot
    return
  fi
  echo ""
}

CERTBOT_BIN="$(resolve_certbot)"
if [[ -z "$CERTBOT_BIN" || ! -x "$CERTBOT_BIN" ]]; then
  echo "ERROR: certbot not found. Install it first, e.g. apt install certbot" >&2
  exit 1
fi

echo "Installing sudoers for user ${PHP_USER} -> NOPASSWD ${CERTBOT_BIN}"

TMP="$(mktemp)"
{
  echo "Defaults:${PHP_USER} !requiretty"
  echo "${PHP_USER} ALL=(root) NOPASSWD: ${CERTBOT_BIN}"
} > "$TMP"

install -m 440 -o root -g root "$TMP" /etc/sudoers.d/xenweet-certbot
rm -f "$TMP"

visudo -cf /etc/sudoers.d/xenweet-certbot

echo ""
echo "Done. Verify (should print certbot version without a password prompt):"
echo "  sudo -u ${PHP_USER} sudo -n \"${CERTBOT_BIN}\" --version"
echo ""
echo "If the panel uses a different PHP user, re-run with that user as the first argument."
