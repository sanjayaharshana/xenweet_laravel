#!/usr/bin/env bash
set -euo pipefail

# Args: domain local_part mail_password_hash mail_root
DOMAIN="${1:-}"
LOCAL="${2:-}"
PASS_HASH="${3:-}"
MAIL_ROOT="${4:-}"

if [[ -z "$DOMAIN" || -z "$LOCAL" || -z "$PASS_HASH" || -z "$MAIL_ROOT" ]]; then
  echo "usage: provision-mailbox.sh <domain> <local_part> <mail_password_hash> <mail_root>" >&2
  exit 2
fi

MAILBOX="${LOCAL}@${DOMAIN}"
PASSWD_DIR="${MAIL_ROOT}/dovecot"
PASSWD_FILE="${PASSWD_DIR}/users.passwd"
DOMAIN_VMAIL_DIR="${MAIL_ROOT}/vmail/${DOMAIN}"
VMAIL_DIR="${MAIL_ROOT}/vmail/${DOMAIN}/${LOCAL}"

mkdir -p "$PASSWD_DIR"

# Domain dir often pre-exists as vmail:vmail mode 0700 (created by delivery/Dovecot) — www-data then cannot add mailboxes.
if [[ -d "$DOMAIN_VMAIL_DIR" && ! -w "$DOMAIN_VMAIL_DIR" ]]; then
  echo "Error: $DOMAIN_VMAIL_DIR is not writable by this process (often mode 0700 for vmail only). " >&2
  echo "As root, fix the tree so the panel can create \`${LOCAL}/\` and delivery still works, e.g.:" >&2
  echo "  chown vmail:vmail $MAIL_ROOT/vmail $DOMAIN_VMAIL_DIR && chmod 2770 $MAIL_ROOT/vmail $DOMAIN_VMAIL_DIR" >&2
  echo "  # or: find $MAIL_ROOT/vmail -type d -exec chmod 2770 {} \;" >&2
  exit 1
fi

mkdir -p "$VMAIL_DIR/Maildir"/{cur,new,tmp}
# Setgid on dirs we own so new paths stay group vmail
chmod 2770 "$DOMAIN_VMAIL_DIR" 2>/dev/null || true
chmod 2770 "$VMAIL_DIR" 2>/dev/null || true

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
