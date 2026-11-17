#!/bin/sh

set -eu

: "${APP_KEY:?APP_KEY must be provided}"

export PORT="${PORT:-10000}"
export DB_CONNECTION="${DB_CONNECTION:-sqlite}"
export DB_DATABASE="${DB_DATABASE:-/app/database/database.sqlite}"

mkdir -p \
    "$(dirname "$DB_DATABASE")" \
    /app/storage/framework/cache/data \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/storage/logs \
    /app/bootstrap/cache \
    /app/storage/app/public

touch "$DB_DATABASE"

php artisan migrate --force
php artisan db:seed --force
php artisan storage:link --force
php artisan optimize

exec frankenphp run --config /etc/caddy/Caddyfile --adapter caddyfile
