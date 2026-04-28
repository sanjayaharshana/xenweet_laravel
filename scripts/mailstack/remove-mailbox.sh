#!/usr/bin/env bash
set -euo pipefail

# Args: domain local_part mail_root
DOMAIN="${1:-}"
LOCAL="${2:-}"
MAIL_ROOT="${3:-}"

if [[ -z "$DOMAIN" || -z "$LOCAL" || -z "$MAIL_ROOT" ]]; then
  echo "usage: remove-mailbox.sh <domain> <local_part> <mail_root>" >&2
  exit 2
fi

MAILBOX="${LOCAL}@${DOMAIN}"
PASSWD_FILE="${MAIL_ROOT}/dovecot/users.passwd"
VMAIL_DIR="${MAIL_ROOT}/vmail/${DOMAIN}/${LOCAL}"

if [[ -f "$PASSWD_FILE" ]]; then
  TMP_FILE="${PASSWD_FILE}.tmp"
  awk -F: -v mailbox="$MAILBOX" '$1 != mailbox { print }' "$PASSWD_FILE" > "$TMP_FILE" || true
  mv "$TMP_FILE" "$PASSWD_FILE"
fi

rm -rf "$VMAIL_DIR" || true

echo "Removed mailbox ${MAILBOX}"
