#!/usr/bin/env bash

set -euo pipefail

SOURCE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
TARGET_BRANCH="${TARGET_BRANCH:-main}"

: "${LIVE_APP_DIR:?Set LIVE_APP_DIR to the live Laravel application directory.}"
: "${LIVE_PUBLIC_DIR:?Set LIVE_PUBLIC_DIR to the live public web root.}"

echo "[deploy] source: ${SOURCE_DIR}"
echo "[deploy] app:    ${LIVE_APP_DIR}"
echo "[deploy] public: ${LIVE_PUBLIC_DIR}"
echo "[deploy] branch: ${TARGET_BRANCH}"

git -C "${SOURCE_DIR}" pull --ff-only origin "${TARGET_BRANCH}"

mkdir -p "${LIVE_APP_DIR}" "${LIVE_PUBLIC_DIR}"

# サーバー固有の .env / storage / vendor は保持し、Git管理のアプリコードだけ同期する。
rsync -az --delete \
  --exclude='.git/' \
  --exclude='.env' \
  --exclude='node_modules/' \
  --exclude='storage/' \
  --exclude='vendor/' \
  --exclude='public/index.php' \
  "${SOURCE_DIR}/" "${LIVE_APP_DIR}/"

# アプリ内部の public/index.php は通常の Laravel フロントコントローラで揃える。
install -m 644 "${SOURCE_DIR}/public/index.php" "${LIVE_APP_DIR}/public/index.php"

# 公開ディレクトリには public 配下のアセットを同期する。
rsync -az --delete \
  --exclude='index.php' \
  "${SOURCE_DIR}/public/" "${LIVE_PUBLIC_DIR}/"

# 公開ディレクトリ用の front controller は本番パスを環境変数から埋め込んで生成する。
escaped_app_dir="$(printf '%s' "${LIVE_APP_DIR}" | sed 's/[&|]/\\&/g')"
sed "s|__LIVE_APP_DIR__|${escaped_app_dir}|g" \
  "${SOURCE_DIR}/deploy/production/public-index.php.stub" \
  > "${LIVE_PUBLIC_DIR}/index.php"
chmod 644 "${LIVE_PUBLIC_DIR}/index.php"

cd "${LIVE_APP_DIR}"
php artisan migrate --force
php artisan optimize:clear

echo "[deploy] done"
