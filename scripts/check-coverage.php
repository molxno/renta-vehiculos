<?php

declare(strict_types=1);

$coverageDrivers = ['pcov', 'xdebug'];

if (count(array_intersect($coverageDrivers, get_loaded_extensions())) === 0) {
    fwrite(STDOUT, "Cobertura omitida: no se detectó PCOV ni Xdebug; se medirá en CI.\n");

    exit(0);
}

$command = sprintf(
    '%s artisan test --coverage --min=100',
    escapeshellarg(PHP_BINARY),
);

passthru($command, $exitCode);

exit($exitCode);
