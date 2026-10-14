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

## Hook de pre-push

Instala los hooks versionados con:

```sh
composer hooks:install
```

El hook `.githooks/pre-push` ejecuta `composer ci:check` y bloquea el push si
falla. Funciona desde Git Bash en Windows y desde macOS/Linux. `--no-verify`
omite el hook local; por eso, el check `tests / ci` de GitHub sigue siendo la
barrera final.
