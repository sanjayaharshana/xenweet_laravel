#!/usr/bin/env bash
#
# Remove generated vhost snippet files for a domain (same basename as create scripts).
# Usage: hosting-vhost-remove.sh <domain>
#
# Required env:
#   HOSTING_VHOST_OUTPUT_DIR
#
set -euo pipefail

DOMAIN="${1:?Usage: $0 <domain>}"
OUTPUT_DIR="${HOSTING_VHOST_OUTPUT_DIR:?Set HOSTING_VHOST_OUTPUT_DIR}"

SAFE_NAME="${DOMAIN//[^a-zA-Z0-9._-]/_}"
REMOVED=0

for f in "${OUTPUT_DIR}/${SAFE_NAME}.conf" "${OUTPUT_DIR}/${SAFE_NAME}-apache.conf"; do
  if [[ -f "$f" ]]; then
    rm -f "$f"
    echo "Removed $f"
    REMOVED=1
  fi
done

if [[ "$REMOVED" -eq 0 ]]; then
  echo "No matching vhost files in ${OUTPUT_DIR} for ${DOMAIN}"
fi

exit 0
