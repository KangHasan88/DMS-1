#!/usr/bin/env bash
set -euo pipefail

check_url() {
  local label="$1"
  local url="$2"
  local expected="$3"
  local insecure="${4:-false}"
  local follow_redirects="${5:-false}"
  local curl_args=(-sS -o /dev/null -w "%{http_code}")

  if [[ "$insecure" == "true" ]]; then
    curl_args=(-k "${curl_args[@]}")
  fi

  if [[ "$follow_redirects" == "true" ]]; then
    curl_args=(-L --max-redirs 10 "${curl_args[@]}")
  fi

  local status
  status="$(curl "${curl_args[@]}" "$url")"

  if [[ "$status" != "$expected" ]]; then
    printf 'FAIL %-18s expected=%s actual=%s url=%s\n' "$label" "$expected" "$status" "$url" >&2
    return 1
  fi

  printf 'OK   %-18s %s\n' "$label" "$status"
}

check_url "central" "https://31.97.106.123/central" "200" "true" "true"
check_url "bmp_auth" "https://31.97.106.123/dev/bmp/bmp_report/Auth" "200" "true"
check_url "dms_login" "https://dms.kurmigo.id/login" "200"
check_url "dms_health" "https://dms.kurmigo.id/health" "200"
