#!/usr/bin/env bash
set -u

source "$(dirname "${BASH_SOURCE[0]}")/public-audit-inventory.sh"

max_time="${PUBLIC_AUDIT_MAX_TIME:-$PUBLIC_AUDIT_DEFAULT_MAX_TIME}"
while (($# > 0)); do
  case "$1" in
    --max-time)
      (($# >= 2)) || { echo 'Falta el valor de --max-time.' >&2; exit 2; }
      max_time="$2"
      shift 2
      ;;
    --help|-h)
      echo 'Uso: validate-public.sh [--max-time SEGUNDOS]'
      exit 0
      ;;
    *)
      echo "Argumento desconocido: $1" >&2
      exit 2
      ;;
  esac
done
[[ "$max_time" =~ ^[1-9][0-9]*$ ]] || { echo '--max-time debe ser un entero positivo.' >&2; exit 2; }

failures=0
for url in "${PUBLIC_VALIDATE_URLS[@]}"; do
  html=$(mktemp)
  trap 'rm -f "$html"' EXIT
  status=$(curl -A 'Mozilla/5.0' --max-time "$max_time" -sS -o "$html" -w '%{http_code}' "$url") || status=000
  canonical=$(sed -nE 's@.*<link rel="canonical" href="([^"]+)".*@\1@p' "$html" | head -1)
  description=$(sed -nE 's@.*<meta name="description" content="([^"]+)".*@\1@p' "$html" | head -1)
  jsonld=$(rg -c 'application/ld\+json' "$html" || true)
  local_refs=$(rg -c '192\.168\.1\.222|:8088|:8089' "$html" || true)
  local_refs=${local_refs:-0}
  title_count=$(rg -c '<title>' "$html" || true)
  if [[ "$status" != 200 || -z "$canonical" || -z "$description" || "$jsonld" -lt 1 || "$local_refs" -ne 0 || "$title_count" -ne 1 ]]; then
    failures=$((failures + 1))
  fi
  printf '%s\t%s\tjsonld=%s\tlocal=%s\ttitle=%s\tcanonical=%s\tdesc=%s\n' "$status" "$url" "$jsonld" "$local_refs" "$title_count" "$canonical" "$description"
  rm -f "$html"
  trap - EXIT
done
printf 'FAILURES=%s\n' "$failures"
exit "$failures"
