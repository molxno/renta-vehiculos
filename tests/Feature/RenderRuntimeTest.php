<?php

declare(strict_types=1);

test('the container entrypoint seeds, links storage and honors PORT', function () {
    $entrypoint = (string) file_get_contents(base_path('docker/entrypoint.sh'));

    expect($entrypoint)
        ->toContain('export PORT="${PORT:-10000}"')
        ->toContain('php artisan migrate --force')
        ->toContain('php artisan db:seed --force')
        ->toContain('php artisan storage:link --force')
        ->toContain('php artisan optimize')
        ->toContain('exec frankenphp run --config /etc/caddy/Caddyfile --adapter caddyfile');
});

test('the runtime image drops file capabilities and listens on an unprivileged port', function () {
    $dockerfile = (string) file_get_contents(base_path('Dockerfile'));

    expect($dockerfile)
        ->toContain('setcap -r /usr/local/bin/frankenphp')
        ->toContain('ENV PORT=10000')
        ->toContain('EXPOSE 10000')
        ->toContain('USER www-data')
        ->not->toContain('ENV PORT=80');
});
