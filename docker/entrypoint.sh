#!/bin/sh

set -eu

: "${APP_KEY:?APP_KEY must be provided}"

export PORT="${PORT:-80}"
export DB_CONNECTION="${DB_CONNECTION:-sqlite}"
export DB_DATABASE="${DB_DATABASE:-/app/database/database.sqlite}"

mkdir -p \
    "$(dirname "$DB_DATABASE")" \
    /app/storage/framework/cache/data \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/storage/logs \
    /app/bootstrap/cache

touch "$DB_DATABASE"

php artisan migrate --force
php artisan db:seed --force

exec frankenphp run --config /etc/caddy/Caddyfile --adapter caddyfile
