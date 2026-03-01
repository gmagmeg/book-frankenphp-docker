#!/usr/bin/env sh
set -eu

cd /app

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist
fi

exec php artisan octane:frankenphp \
  --host=0.0.0.0 \
  --port="${APP_PORT:-8000}" \
  --workers="${OCTANE_WORKERS:-4}" \
  --max-requests="${OCTANE_MAX_REQUESTS:-500}"
