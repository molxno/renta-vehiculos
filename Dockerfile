FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

FROM node:24-bookworm-slim AS node

FROM dunglas/frankenphp:php8.5-bookworm AS frontend

WORKDIR /app

RUN install-php-extensions pdo_sqlite

COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules

RUN printf '#!/bin/sh\nexec node /usr/local/lib/node_modules/npm/bin/npm-cli.js "$@"\n' > /usr/local/bin/npm \
    && chmod +x /usr/local/bin/npm

COPY package.json package-lock.json ./
RUN npm ci

COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN cp .env.example .env \
    && php artisan key:generate --force \
    && php artisan wayfinder:generate --with-form -vvv \
    && npm run build

FROM dunglas/frankenphp:php8.5-bookworm

WORKDIR /app

RUN install-php-extensions pdo_sqlite

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=frontend /app/public/build ./public/build
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN chmod +x /usr/local/bin/entrypoint \
    && mkdir -p database storage/framework/cache/data storage/framework/sessions \
        storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data database storage bootstrap/cache

ENV PORT=80

EXPOSE 80

ENTRYPOINT ["entrypoint"]
