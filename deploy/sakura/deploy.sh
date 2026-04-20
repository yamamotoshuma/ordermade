#!/usr/bin/env bash

set -euo pipefail

SOURCE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
LIVE_APP_DIR="${LIVE_APP_DIR:-$HOME/ordermade}"
LIVE_PUBLIC_DIR="${LIVE_PUBLIC_DIR:-$HOME/www/kanri}"
TARGET_BRANCH="${TARGET_BRANCH:-main}"

echo "[deploy] source: ${SOURCE_DIR}"
echo "[deploy] app:    ${LIVE_APP_DIR}"
echo "[deploy] public: ${LIVE_PUBLIC_DIR}"
echo "[deploy] branch: ${TARGET_BRANCH}"

git -C "${SOURCE_DIR}" pull --ff-only origin "${TARGET_BRANCH}"

mkdir -p "${LIVE_APP_DIR}" "${LIVE_PUBLIC_DIR}"

# Sync the Laravel application while preserving server-only state such as .env,
# storage data, installed vendor packages, and any local node_modules cache.
rsync -az --delete \
  --exclude='.git/' \
  --exclude='.env' \
  --exclude='node_modules/' \
  --exclude='storage/' \
  --exclude='vendor/' \
  --exclude='public/index.php' \
  "${SOURCE_DIR}/" "${LIVE_APP_DIR}/"

# Keep the internal public dir clean as well even though the Sakura web root is ~/www/kanri.
install -m 644 "${SOURCE_DIR}/public/index.php" "${LIVE_APP_DIR}/public/index.php"

# Deploy the public assets to the actual Sakura web root.
rsync -az --delete \
  --exclude='index.php' \
  "${SOURCE_DIR}/public/" "${LIVE_PUBLIC_DIR}/"

install -m 644 "${SOURCE_DIR}/deploy/sakura/kanri-index.php" "${LIVE_PUBLIC_DIR}/index.php"

cd "${LIVE_APP_DIR}"
php artisan migrate --force
php artisan optimize:clear

echo "[deploy] done"
