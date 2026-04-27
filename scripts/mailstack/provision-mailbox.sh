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

# Group read for Dovecot; group write for PHP when file is vmail:vmail and www-data is in vmail.
chmod 640 "$PASSWD_FILE" || true

# Ensure maildir is group-accessible (vmail user uses group perms when dirs are www-data:vmail, mode 770).
chmod -R u+rwX,g+rwX,o= "$VMAIL_DIR" 2>/dev/null || true

echo "Provisioned mailbox ${MAILBOX} at ${VMAIL_DIR}"
