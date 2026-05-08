#!/usr/bin/env bash
# Chạy batch gia hạn subscription trong container ec-cube (dùng cho cron).
# Xem: README_SUBSCRIPTION_CRON.md

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="${EC_CUBE_ROOT:-$(cd "${SCRIPT_DIR}/.." && pwd)}"
cd "${PROJECT_ROOT}" || exit 1

LIMIT="${SUBSCRIPTION_RUN_LIMIT:-50}"
LOG_FILE="${SUBSCRIPTION_CRON_LOG:-/tmp/subscription-cron.log}"

# Docker Compose plugin: chỉ một lần "compose" (KHÔNG viết "docker compose compose").
log_ts() { date "+%Y-%m-%dT%H:%M:%S%z"; }

if ! command -v docker >/dev/null 2>&1; then
  echo "$(log_ts) ERROR: docker không có trong PATH" >>"${LOG_FILE}"
  exit 1
fi

# -T: bắt buộc khi không có TTY (cron).
{
  echo "=== $(log_ts) subscription:run (limit=${LIMIT}) ==="
  docker compose \
    -f docker-compose.yml \
    -f docker-compose.dev.yml \
    -f docker-compose.pgsql.yml \
    exec -T ec-cube php bin/console subscription:run --limit="${LIMIT}"
} >>"${LOG_FILE}" 2>&1
