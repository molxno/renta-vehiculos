# Renta de vehículos

## Validación local

La fuente única de verdad para las validaciones del proyecto es:

```sh
composer ci:check
```

El script ejecuta el formato y lint del frontend y Markdown (`vp check`), el
build del frontend, Pint en modo test, PHPStan con `--memory-limit=512M` y
Pest excluyendo el grupo `flow`. El workflow de GitHub Actions ejecuta
únicamente este script.

## Preparar un clon limpio

Desde una rama basada en `dev`, ejecuta:

```sh
composer install
php -r "file_exists('.env') || copy('.env.example', '.env');"
php artisan key:generate
npm install
php artisan migrate:fresh --seed
composer hooks:install
composer test
composer test:coverage
npm run build
```

Como alternativa, `composer setup` instala las dependencias, prepara `.env`,
genera la clave, migra la base de datos, instala los paquetes frontend y
construye los assets. Después de usarlo, ejecuta igualmente
`php artisan migrate:fresh --seed`, `composer test` y
`composer test:coverage`.

`composer test:coverage` ejecuta la medición cuando PHP tiene PCOV o Xdebug
activo. Sin uno de esos drivers muestra un aviso y termina correctamente; la
medición obligatoria se ejecuta en CI.

Los comandos de Pint desactivan OPcache únicamente para el proceso CLI. Esto
evita que Windows reutilice el PHAR de Pint de otro checkout cuando se prueban
clones limpios; no cambia la configuración de producción.

## Hook de pre-push

Instala los hooks versionados con:

```sh
composer hooks:install
```

El hook `.githooks/pre-push` ejecuta `composer ci:check` y bloquea el push si
falla. Funciona desde Git Bash en Windows y desde macOS/Linux. `--no-verify`
omite el hook local; por eso, el check `tests / ci` de GitHub sigue siendo la
barrera final.
