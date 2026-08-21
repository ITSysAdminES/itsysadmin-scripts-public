#!/usr/bin/env bash
set -euo pipefail

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
      echo 'Uso: public-production-audit.sh [--max-time SEGUNDOS]'
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
for url in "${PUBLIC_AUDIT_URLS[@]}"; do
  html="$(curl --fail --silent --show-error --location --max-time "$max_time" "$url")" || {
    printf 'FAIL\t%s\tunreachable\n' "$url"
    failures=$((failures + 1))
    continue
  }
  headers="$(curl --silent --show-error --head --max-time "$max_time" "$url")"
  code="$(awk 'toupper($1) ~ /^HTTP\// {c=$2} END {print c}' <<<"$headers")"
  canonical="$(grep -oiE '<link[^>]+rel="canonical"[^>]*>' <<<"$html" | head -1 | sed -n 's/.*href="\([^"]*\)".*/\1/p' || true)"
  expected="${url%%\?*}"
  noindex=0; grep -qiE '<meta[^>]+robots[^>]+noindex' <<<"$html" && noindex=1
  schema=0; grep -qi 'application/ld+json' <<<"$html" && schema=1
  localrefs=0; grep -qiE '192\.168\.|:808[89]' <<<"$html" && localrefs=1
  if [[ "$code" != 200 || "$canonical" != "$expected" || "$noindex" != 0 || "$schema" != 1 || "$localrefs" != 0 ]]; then
    failures=$((failures + 1))
  fi
  printf 'PAGE\t%s\t%s\tcanonical=%s\tnoindex=%s\tschema=%s\tlocalrefs=%s\n' \
    "$code" "$url" "$canonical" "$noindex" "$schema" "$localrefs"
done

for endpoint in "${PUBLIC_AUDIT_ENDPOINTS[@]}"; do
  meta="$(curl --silent --show-error --max-time "$max_time" --output /dev/null --write-out '%{http_code}\t%{content_type}\t%{size_download}' "$endpoint")"
  printf 'ENDPOINT\t%s\t%s\n' "$endpoint" "$meta"
  [[ "$meta" == 200$'\t'* ]] || failures=$((failures + 1))
done

for domain in "${PUBLIC_AUDIT_DOMAINS[@]}"; do
  redirect="$(curl --silent --show-error --max-time "$max_time" --output /dev/null --write-out '%{http_code}\t%{redirect_url}' "http://${domain}/prueba-seo/?q=1")"
  www_redirect="$(curl --silent --show-error --max-time "$max_time" --output /dev/null --write-out '%{http_code}\t%{redirect_url}' "https://www.${domain}/prueba-seo/?q=1")"
  printf 'REDIRECT\thttp://%s\t%s\n' "$domain" "$redirect"
  printf 'REDIRECT\thttps://www.%s\t%s\n' "$domain" "$www_redirect"
done

echo "FAILURES ${failures}"
exit "$failures"
