#!/usr/bin/env sh
# Helper for SSL/TLS module: private key and CSR generation via local openssl(1).
# Optional env: OPENSSL=/path/to/openssl (defaults to openssl on PATH)
# Usage: ssltls-openssl.sh genkey rsa2048|ec256
#        ssltls-openssl.sh gencsr <keyfile> <subj>   (subj e.g. /C=US/O=MyOrg/CN=example.com)
set -eu
OPENSSL="${OPENSSL:-openssl}"

cmd="${1:-}"
case "$cmd" in
  genkey)
    kt="${2:-}"
    case "$kt" in
      rsa2048)
        exec "$OPENSSL" genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:2048
        ;;
      ec256)
        exec "$OPENSSL" genpkey -algorithm EC \
          -pkeyopt ec_paramgen_curve:prime256v1 -pkeyopt ec_param_enc:named_curve
        ;;
      *)
        echo "ssltls-openssl: unknown key type (use rsa2048 or ec256)" >&2
        exit 1
        ;;
    esac
    ;;
  gencsr)
    keyfile="${2:-}"
    subj="${3:-}"
    if [ -z "$keyfile" ] || [ ! -r "$keyfile" ]; then
      echo "ssltls-openssl: private key file missing or not readable" >&2
      exit 1
    fi
    if [ -z "$subj" ]; then
      echo "ssltls-openssl: subject (DN) is required" >&2
      exit 1
    fi
    exec "$OPENSSL" req -new -sha256 -key "$keyfile" -subj "$subj"
    ;;
  *)
    echo "ssltls-openssl: usage: $0 genkey rsa2048|ec256" >&2
    echo "              or:  $0 gencsr <keyfile> <subj>" >&2
    exit 1
    ;;
esac
