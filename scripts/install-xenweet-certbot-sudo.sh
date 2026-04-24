#!/usr/bin/env bash
#
# Run once on the server (as root or a user with sudo to write /etc/sudoers.d):
#   sudo bash scripts/install-xenweet-certbot-sudo.sh
#   sudo bash scripts/install-xenweet-certbot-sudo.sh www-data
#   sudo bash scripts/install-xenweet-certbot-sudo.sh www-data /usr/bin/certbot
#
# Grants the PHP / FPM user passwordless sudo for:
#   - certbot (issues/renews in /etc/letsencrypt, root-owned)
#   - xenweet-letsencrypt-read-pem (reads privkey.pem / fullchain.pem for the panel;
#     those files are often mode 600 and not readable by www-data)
#
# Same idea as scripts/install-xenweet-nginx-sudo.sh. Required for Auto SSL in the
# panel when the PHP user is unprivileged.
#
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
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

READ_HELPER="/usr/local/sbin/xenweet-letsencrypt-read-pem"
install -m 755 -o root -g root "${ROOT}/scripts/xenweet-letsencrypt-read-pem.sh" "$READ_HELPER"

echo "Installing sudoers for user ${PHP_USER} -> NOPASSWD ${CERTBOT_BIN}, ${READ_HELPER}"

TMP="$(mktemp)"
{
  echo "Defaults:${PHP_USER} !requiretty"
  echo "${PHP_USER} ALL=(root) NOPASSWD: ${CERTBOT_BIN}, ${READ_HELPER}"
} > "$TMP"

install -m 440 -o root -g root "$TMP" /etc/sudoers.d/xenweet-certbot
rm -f "$TMP"

visudo -cf /etc/sudoers.d/xenweet-certbot

echo ""
echo "Done. Verify certbot (should print version without a password prompt):"
echo "  sudo -u ${PHP_USER} sudo -n \"${CERTBOT_BIN}\" --version"
echo "Verify read helper (if you already have a cert under live/):"
echo "  sudo -u ${PHP_USER} sudo -n ${READ_HELPER} /etc/letsencrypt example.com fullchain  | head -1"
echo "  (use a real live/ directory name; output should be -----BEGIN CERTIFICATE----- or similar)"
echo ""
echo "If the panel uses a different PHP user, re-run with that user as the first argument."
