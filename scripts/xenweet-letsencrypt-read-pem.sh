#!/usr/bin/env bash
#
# Read a PEM from Let's Encrypt "live" storage (used by the panel when the PHP
# user cannot read root-owned /etc/letsencrypt). Invoked with sudo.
#
# Usage: xenweet-letsencrypt-read-pem <config_dir> <live_basename> <which>
#   <config_dir>  e.g. /etc/letsencrypt
#   <live_basename>  directory name under live/ (e.g. greentalk.xelenic.com)
#   <which>  privkey | fullchain
#
set -euo pipefail

CONFIG="${1:?}"
LIVE_NAME="${2:?}"
WHICH="${3:?}"
CONFIG="${CONFIG%/}"

case "$WHICH" in
  privkey) FILE="privkey.pem" ;;
  fullchain) FILE="fullchain.pem" ;;
  *) echo "ERROR: which must be privkey or fullchain" >&2; exit 1 ;;
esac

if [[ -z "$LIVE_NAME" || "$LIVE_NAME" == *..* || "$LIVE_NAME" == */* ]]; then
  echo "ERROR: invalid live name" >&2
  exit 1
fi

# Only allow a safe cert directory name
if ! [[ "$LIVE_NAME" =~ ^[a-zA-Z0-9._-]+$ ]]; then
  echo "ERROR: live name has invalid characters" >&2
  exit 1
fi

TARGET="${CONFIG}/live/${LIVE_NAME}/${FILE}"

if ! TARGET_REAL="$(readlink -f -- "$TARGET" 2>/dev/null)"; then
  echo "ERROR: path not found: $TARGET" >&2
  exit 1
fi

if ! CONFIG_REAL="$(readlink -f -- "$CONFIG" 2>/dev/null)"; then
  echo "ERROR: bad config dir: $CONFIG" >&2
  exit 1
fi

# Confine reads to the certbot config tree (e.g. /etc/letsencrypt/…)
if [[ "$TARGET_REAL" != "$CONFIG_REAL"/* ]]; then
  echo "ERROR: refusing to read outside $CONFIG_REAL" >&2
  exit 1
fi

if [[ ! -f "$TARGET_REAL" ]]; then
  echo "ERROR: not a file: $TARGET_REAL" >&2
  exit 1
fi

exec cat -- "$TARGET_REAL"
