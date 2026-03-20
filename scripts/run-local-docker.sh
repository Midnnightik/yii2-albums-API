#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

COMPOSE_CMD="${COMPOSE_CMD:-docker compose}"

SEED_PASSWORD_FILE="config/seed_password_local.php"
if [[ ! -f "$SEED_PASSWORD_FILE" ]]; then
  echo "Missing seed password file: $SEED_PASSWORD_FILE" >&2
  echo "Copy it first:" >&2
  echo "  cp config/seed_password_local.php.example config/seed_password_local.php" >&2
  exit 1
fi

WITH_TESTS="0"
if [[ "${1:-}" == "--with-tests" ]]; then
  WITH_TESTS="1"
fi

MODE="all"
for arg in "$@"; do
  case "$arg" in
    --seed-users) MODE="users" ;;
    --seed-albums) MODE="albums" ;;
    --seed-photos) MODE="photos" ;;
    --seed-all) MODE="all" ;;
  esac
done

echo "Starting containers..."
$COMPOSE_CMD up -d --build

if ! $COMPOSE_CMD exec -T php sh -lc 'test -f vendor/autoload.php'; then
  echo "Installing composer dependencies (vendor/autoload.php missing in container)..."
  $COMPOSE_CMD exec -T php sh -lc 'git config --global --add safe.directory /var/www/html || true; composer install --no-interaction'
fi

echo "Migrating main DB..."
$COMPOSE_CMD exec -T php php yii migrate --interactive=0

SEED_USERS_PREFIX="${SEED_USERS_PREFIX:-user_}"
SEED_USERS_COUNT="${SEED_USERS_COUNT:-10}"
SEED_ALBUMS_PER_USER="${SEED_ALBUMS_PER_USER:-10}"
SEED_PHOTOS_PER_ALBUM="${SEED_PHOTOS_PER_ALBUM:-10}"

case "$MODE" in
  users)
    echo "Seeding users: ${SEED_USERS_PREFIX}1..${SEED_USERS_PREFIX}${SEED_USERS_COUNT}"
    $COMPOSE_CMD exec -T php php yii seed/users --prefix="$SEED_USERS_PREFIX" --count="$SEED_USERS_COUNT"
    ;;
  albums)
    echo "Seeding albums: ${SEED_ALBUMS_PER_USER} per user (expects user_1..user_10)"
    $COMPOSE_CMD exec -T php php yii seed/albums --per-user="$SEED_ALBUMS_PER_USER"
    ;;
  photos)
    echo "Seeding photos: ${SEED_PHOTOS_PER_ALBUM} per album"
    $COMPOSE_CMD exec -T php php yii seed/photos --per-album="$SEED_PHOTOS_PER_ALBUM"
    ;;
  all)
    echo "Seeding demo data (10 users, 100 albums, 1000 photos)..."
    $COMPOSE_CMD exec -T php php yii seed/all
    ;;
  *)
    echo "Unknown mode: $MODE" >&2
    exit 1
    ;;
esac

if [[ "$MODE" == "all" || "$WITH_TESTS" == "1" ]]; then
  echo "Migrating test DB (for Codeception)..."
  $COMPOSE_CMD exec -T -e MYSQL_DATABASE=yii2basic_test php php yii migrate --interactive=0
fi

if [[ "$WITH_TESTS" == "1" ]]; then
  echo "Running unit tests..."
  $COMPOSE_CMD exec -T php vendor/bin/codecept run unit
  echo "Running functional API tests..."
  $COMPOSE_CMD exec -T php vendor/bin/codecept run functional ApiCest.php
fi

echo "Done."

