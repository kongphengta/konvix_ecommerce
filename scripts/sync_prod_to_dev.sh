#!/usr/bin/env bash
set -euo pipefail

# Synchronize production data to local development:
# - dumps prod database
# - downloads and imports into local DB
# - syncs public/uploads
#
# Usage:
#   cp .sync.env.example .sync.env
#   # edit .sync.env with your values
#   chmod +x scripts/sync_prod_to_dev.sh
#   ./scripts/sync_prod_to_dev.sh
#
# Optional:
#   ./scripts/sync_prod_to_dev.sh --no-db
#   ./scripts/sync_prod_to_dev.sh --no-uploads
#   ./scripts/sync_prod_to_dev.sh --no-local-backup

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CONFIG_FILE="${SYNC_CONFIG_FILE:-$ROOT_DIR/.sync.env}"

DO_DB=1
DO_UPLOADS=1
DO_LOCAL_BACKUP=1

for arg in "$@"; do
  case "$arg" in
    --no-db) DO_DB=0 ;;
    --no-uploads) DO_UPLOADS=0 ;;
    --no-local-backup) DO_LOCAL_BACKUP=0 ;;
    -h|--help)
      sed -n '1,40p' "$0"
      exit 0
      ;;
    *)
      echo "Unknown option: $arg" >&2
      exit 1
      ;;
  esac
done

if [[ ! -f "$CONFIG_FILE" ]]; then
  echo "Missing config file: $CONFIG_FILE" >&2
  echo "Create it from .sync.env.example first." >&2
  exit 1
fi

# shellcheck source=/dev/null
source "$CONFIG_FILE"

required_vars=(
  REMOTE_SSH
  REMOTE_PROJECT_PATH
  REMOTE_DB_DUMP_CMD
  LOCAL_DB_IMPORT_CMD
  LOCAL_UPLOADS_PATH
)

for name in "${required_vars[@]}"; do
  if [[ -z "${!name:-}" ]]; then
    echo "Missing required variable in $CONFIG_FILE: $name" >&2
    exit 1
  fi
done

if ! command -v ssh >/dev/null 2>&1; then
  echo "ssh command not found" >&2
  exit 1
fi

if ! command -v rsync >/dev/null 2>&1; then
  echo "rsync command not found" >&2
  exit 1
fi

timestamp="$(date +%Y%m%d_%H%M%S)"
work_dir="${LOCAL_SYNC_WORK_DIR:-$ROOT_DIR/tmp/sync-db}"
mkdir -p "$work_dir"

remote_dump_name="prod_${timestamp}.sql"
remote_dump_path="${REMOTE_TMP_DIR:-/tmp}/${remote_dump_name}"
local_dump_path="$work_dir/$remote_dump_name"

echo "==> Starting prod -> dev synchronization"

if [[ "$DO_DB" -eq 1 ]]; then
  echo "==> Creating database dump on production server"
  remote_dump_cmd="$REMOTE_DB_DUMP_CMD > \"$remote_dump_path\""
  ssh "$REMOTE_SSH" "cd '$REMOTE_PROJECT_PATH' && bash -lc $(printf '%q' "$remote_dump_cmd")"

  echo "==> Downloading production dump"
  scp "$REMOTE_SSH:$remote_dump_path" "$local_dump_path"

  if [[ "$DO_LOCAL_BACKUP" -eq 1 ]]; then
    if [[ -n "${LOCAL_DB_BACKUP_CMD:-}" ]]; then
      local_backup_path="$work_dir/local_backup_${timestamp}.sql"
      echo "==> Backing up local DB before import"
      bash -lc "$LOCAL_DB_BACKUP_CMD > '$local_backup_path'"
    else
      echo "==> Skipping local backup (LOCAL_DB_BACKUP_CMD not set)"
    fi
  fi

  echo "==> Importing dump into local development DB"
  bash -lc "$LOCAL_DB_IMPORT_CMD < '$local_dump_path'"

  echo "==> Cleaning remote dump"
  ssh "$REMOTE_SSH" "rm -f '$remote_dump_path'"
fi

if [[ "$DO_UPLOADS" -eq 1 ]]; then
  echo "==> Synchronizing uploads directory"
  mkdir -p "$LOCAL_UPLOADS_PATH"
  rsync -az --delete "$REMOTE_SSH:$REMOTE_PROJECT_PATH/public/uploads/" "$LOCAL_UPLOADS_PATH/"
fi

echo "==> Synchronization completed"
