#!/usr/bin/env bash
set -euo pipefail

USERNAME="${1:?usage: $0 <username> <password> <host_root> <web_root> [public_key_b64]}"
PASSWORD="${2:?usage: $0 <username> <password> <host_root> <web_root> [public_key_b64]}"
HOST_ROOT="${3:?usage: $0 <username> <password> <host_root> <web_root> [public_key_b64]}"
WEB_ROOT="${4:?usage: $0 <username> <password> <host_root> <web_root> [public_key_b64]}"
PUBKEY_B64="${5:-}"

if ! [[ "$USERNAME" =~ ^[a-z_][a-z0-9_-]{2,31}$ ]]; then
  echo "ERROR: invalid username (lowercase Linux username required, 3-32 chars)." >&2
  exit 1
fi

if [[ "${#PASSWORD}" -lt 8 ]]; then
  echo "ERROR: password must be at least 8 characters." >&2
  exit 1
fi

if [[ ! -d "$HOST_ROOT" ]]; then
  echo "ERROR: host root directory missing: $HOST_ROOT" >&2
  exit 1
fi

HOME_DIR="$HOST_ROOT/ssh-users/$USERNAME"

if id -u "$USERNAME" >/dev/null 2>&1; then
  echo "ERROR: user already exists: $USERNAME" >&2
  exit 1
fi

mkdir -p "$HOME_DIR"
useradd -m -d "$HOME_DIR" -s /bin/rbash "$USERNAME"
echo "$USERNAME:$PASSWORD" | chpasswd

mkdir -p "$HOME_DIR/.ssh"
chmod 700 "$HOME_DIR/.ssh"

if [[ -n "$PUBKEY_B64" ]]; then
  echo "$PUBKEY_B64" | base64 --decode > "$HOME_DIR/.ssh/authorized_keys"
  chmod 600 "$HOME_DIR/.ssh/authorized_keys"
fi

ln -sfn "$WEB_ROOT" "$HOME_DIR/public_html" || true
chown -R "$USERNAME":"$USERNAME" "$HOME_DIR"

echo "OK: jailed SSH account created: $USERNAME"
echo "home=$HOME_DIR"
echo "shell=/bin/rbash"
