#!/usr/bin/env bash
set -euo pipefail

# Args: domain local_part mail_password_hash state_root
DOMAIN="${1:-}"
LOCAL="${2:-}"
PASS_HASH="${3:-}"
STATE_ROOT="${4:-}"

if [[ -z "$DOMAIN" || -z "$LOCAL" || -z "$PASS_HASH" || -z "$STATE_ROOT" ]]; then
  echo "usage: provision-mailbox.sh <domain> <local_part> <mail_password_hash> <state_root>" >&2
  exit 2
fi

MAILBOX="${LOCAL}@${DOMAIN}"
PASSWD_DIR="${STATE_ROOT}/dovecot"
PASSWD_FILE="${PASSWD_DIR}/users.passwd"
VMAIL_DIR="${STATE_ROOT}/vmail/${DOMAIN}/${LOCAL}"

mkdir -p "$PASSWD_DIR"
mkdir -p "$VMAIL_DIR/Maildir"/{cur,new,tmp}

touch "$PASSWD_FILE"

# Replace existing row for mailbox
TMP_FILE="${PASSWD_FILE}.tmp"
awk -F: -v mailbox="$MAILBOX" '$1 != mailbox { print }' "$PASSWD_FILE" > "$TMP_FILE" || true
printf '%s:%s\n' "$MAILBOX" "$PASS_HASH" >> "$TMP_FILE"
mv "$TMP_FILE" "$PASSWD_FILE"

chmod 600 "$PASSWD_FILE" || true

echo "Provisioned mailbox ${MAILBOX} at ${VMAIL_DIR}"
