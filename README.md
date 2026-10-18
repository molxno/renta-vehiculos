# Renta de vehículos

Aplicación web para gestionar el ciclo de renta de vehículos: catálogo,
reservas, entregas, devoluciones, facturación y pagos. Es un monolito Laravel
con frontend Inertia/Vue. El estado actual es **fase 0 / gate mínimo**: el
catálogo, los usuarios demo, los seeders, los contratos y los checks de CI
están disponibles; el trabajo funcional continúa por historias en Jira.

## Checks del repositorio

| Check     | Insignia                                                                                                                                                                     |
| --------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| CI        | [![CI](https://github.com/molxno/renta-vehiculos/actions/workflows/ci.yml/badge.svg)](https://github.com/molxno/renta-vehiculos/actions/workflows/ci.yml)                    |
| Seguridad | [![Seguridad](https://github.com/molxno/renta-vehiculos/actions/workflows/security.yml/badge.svg)](https://github.com/molxno/renta-vehiculos/actions/workflows/security.yml) |
| Deploy    | [![Deploy](https://github.com/molxno/renta-vehiculos/actions/workflows/deploy.yml/badge.svg)](https://github.com/molxno/renta-vehiculos/actions/workflows/deploy.yml)        |

La integración se hace hacia `dev`. Los checks obligatorios son `CI /
Calidad`, `CI / Pruebas y cobertura`, `Seguridad / Dependencias`, `Seguridad /
Secretos` y `Deploy / Build Docker`.

## Stack

Las restricciones se leen de `composer.json` y `package.json`; las versiones
instaladas se leen de los archivos lock y se comprobaron en el entorno del
proyecto.

| Capa     | Dependencia     | Versión declarada            | Versión instalada comprobada |
| -------- | --------------- | ---------------------------- | ---------------------------- |
| Backend  | PHP             | `^8.3`                       | 8.5.0                        |
| Backend  | Laravel         | `^13.17`                     | 13.35.0                      |
| Backend  | Inertia Laravel | `^3.0`                       | 3.5.1                        |
| Backend  | Laravel Boost   | `^2.2`                       | 2.10.3                       |
| Backend  | Pest            | `^5.3`                       | 5.3.1                        |
| Backend  | Larastan        | `^3.9`                       | 3.13.0                       |
| Backend  | Pint            | `^1.27`                      | 1.32.1                       |
| Frontend | Node.js         | usado por CI: 22; Docker: 24 | 24.18.0 local                |
| Frontend | Vue             | `^3.5.13`                    | 3.5.43                       |
| Frontend | Inertia Vue     | `^3.0.0`                     | 3.8.0                        |
| Frontend | Vite            | `^8.0.0`                     | 8.3.4                        |
| Frontend | TypeScript      | `^5.2.2`                     | 5.9.3                        |
| Frontend | Tailwind CSS    | `^4.1.1`                     | 4.3.3                        |

## Requisitos previos

Necesitas PHP 8.5 con SQLite, Composer 2, Node.js 22 o 24 con npm, Git y
GitHub CLI (`gh`). PCOV o Xdebug son opcionales y solo hacen falta para medir
cobertura local; Docker es opcional para reproducir el despliegue.

### Windows

La opción recomendada es [Laravel Herd para
Windows](https://herd.laravel.com/windows), que instala PHP y facilita el
entorno Laravel. Instala también [Node.js LTS](https://nodejs.org/en/download),
[Git for Windows](https://git-scm.com/download/win) y [GitHub CLI para
Windows](https://cli.github.com/). Docker Desktop y PCOV/Xdebug son opcionales.
Comprueba las versiones con los comandos de la sección _Diagnóstico rápido_.

### macOS

La opción recomendada es [Laravel Herd para
macOS](https://herd.laravel.com/mac), que incluye PHP y Composer. Instala
[Node.js LTS](https://nodejs.org/en/download), [Git](https://git-scm.com/) y
[GitHub CLI](https://cli.github.com/). Docker Desktop y PCOV/Xdebug son
opcionales.

### Linux

Instala PHP 8.5 y las extensiones `sqlite3`, `mbstring`, `xml`, `curl` y
`zip` desde el gestor de paquetes de tu distribución; después instala
[Composer](https://getcomposer.org/download/), [Node.js LTS](https://nodejs.org/)
y [GitHub CLI](https://cli.github.com/). Docker Engine y PCOV/Xdebug son
opcionales. En Linux no hay una edición de Herd soportada por este proyecto,
por lo que se recomienda seguir la documentación de la distribución y de
Laravel.

Estas instrucciones de instalación son enlaces a los instaladores oficiales;
no sustituyen la comprobación de los paquetes del sistema operativo.

### Diagnóstico rápido

```text
php --version
composer --version
node --version
npm --version
git --version
gh --version
```

## Instalación

Ejecuta los pasos desde PowerShell, Git Bash o una terminal macOS/Linux. El
flujo siguiente fue comprobado en un clon limpio del repositorio.

1. Clona el repositorio y entra en la carpeta:

    ```text
    git clone https://github.com/molxno/renta-vehiculos.git
    cd renta-vehiculos
    ```

2. Ejecuta la preparación oficial del proyecto. Instala dependencias, crea
   `.env` si falta, genera la clave, migra la base, instala npm y compila:

    ```text
    composer setup
    ```

3. Si necesitas recrear el entorno desde cero, confirma el archivo `.env` y
   genera una clave local:

    ```text
    Copy-Item .env.example .env
    php artisan key:generate
    ```

    En macOS/Linux, la copia equivalente es `cp .env.example .env`. Usa una
    sola de las dos formas según tu shell.

4. Carga el catálogo y los fixtures demo:

    ```text
    php artisan migrate:fresh --seed
    ```

5. Activa los hooks versionados:

    ```text
    composer hooks:install
    ```

6. Inicia el entorno de desarrollo:

    ```text
    composer dev
    ```

    El comando mantiene abiertos Laravel, Vite y los procesos auxiliares.
    Deténlo con `Ctrl+C`.

### Preparar un clon limpio sin el atajo

Si necesitas ver cada comando por separado, este es el flujo equivalente:

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

`composer test:coverage` ejecuta la medición cuando PHP tiene PCOV o Xdebug
activo. Sin uno de esos drivers muestra un aviso y termina correctamente; la
medición obligatoria se ejecuta en CI. Los comandos de Pint desactivan OPcache
únicamente para el proceso CLI, para evitar que Windows reutilice el PHAR de
otro checkout; no cambia la configuración de producción.

## Hook de pre-push

El hook `.githooks/pre-push` ejecuta `composer ci:check` y bloquea el push si
falla. Funciona desde Git Bash en Windows y desde macOS/Linux. Instálalo con:

```text
composer hooks:install
```

`--no-verify` omite el hook local; el check de GitHub sigue siendo la barrera
final.

## Usuarios y fixtures de prueba

`php artisan migrate:fresh --seed` ejecuta `DatabaseSeeder`, que carga
`MinimumCatalogSeeder` y los escenarios `ReservaConfirmadaSeeder`,
`AlquilerEntregadoSeeder`, `DevolucionFacturadaSeeder` y
`PagoRechazadoSeeder`. Todos los usuarios demo usan la contraseña
`password`; son datos locales, nunca credenciales de producción.

| Rol           | Correo                     | Contraseña | Fixture adicional                                         |
| ------------- | -------------------------- | ---------- | --------------------------------------------------------- |
| Cliente       | `cliente@renta.test`       | `password` | Reservas confirmada, entregada, devuelta y pago rechazado |
| Coordinador   | `coordinador@renta.test`   | `password` | Catálogo mínimo                                           |
| Cajero        | `cajero@renta.test`        | `password` | Catálogo mínimo                                           |
| Administrador | `administrador@renta.test` | `password` | Catálogo mínimo                                           |
| Gerente       | `gerente@renta.test`       | `password` | Catálogo mínimo                                           |

Para cargar todo:

```text
php artisan migrate:fresh --seed
```

Para cargar un escenario aislado después de migrar:

```text
php artisan db:seed --class=MinimumCatalogSeeder
php artisan db:seed --class=ReservaConfirmadaSeeder
php artisan db:seed --class=AlquilerEntregadoSeeder
php artisan db:seed --class=DevolucionFacturadaSeeder
php artisan db:seed --class=PagoRechazadoSeeder
```

## Comandos del día a día

| Necesidad                | Comando comprobado                  |
| ------------------------ | ----------------------------------- |
| Check completo local     | `composer ci:check`                 |
| Pruebas y lint           | `composer test`                     |
| Cobertura local al 100 % | `composer test:coverage`            |
| Formato PHP              | `composer lint`                     |
| Comprobar formato PHP    | `composer lint:check`               |
| Análisis estático        | `composer types:check`              |
| Check frontend           | `npm run check`                     |
| Build frontend           | `npm run build`                     |
| Desarrollo               | `composer dev`                      |
| Docker                   | `docker build -t renta-vehiculos .` |

`composer ci:check` ejecuta el check frontend, el build, Pint, PHPStan,
limpieza de configuración y Pest. La cobertura local requiere PCOV o Xdebug;
sin un driver el hook informa que la medición queda para CI. CI usa PCOV y
exige 100 % de cobertura de `app/`.

## Estructura por capas

```text
app/
├── Http/Controllers/   entrada HTTP delgada
├── Actions/            casos de uso y transacciones
├── Models/             relaciones, casts y scopes Eloquent
├── Contracts/          interfaces de dependencias intercambiables
├── Enums/              vocabularios y estados cerrados
├── Http/Requests/      validación de entrada
└── Services/           lógica compartida cuando existe una variación real
database/
├── migrations/         esquema reversible
├── seeders/            catálogo y escenarios demo
└── factories/          datos para pruebas
resources/js/           páginas y componentes Vue/Inertia
routes/                 rutas nombradas
tests/                  pruebas Feature, Unit y arquitectura
docs/                   flujo, ramas, despliegue y agentes
```

La dirección normal es `Ruta -> Controller -> FormRequest -> Action -> Modelo`.
Los controladores no contienen reglas de negocio.

## Flujo de trabajo

1. Lee `AGENTS.md`, el ticket y [docs/flujo-de-trabajo.md](docs/flujo-de-trabajo.md).
2. Trabaja en `feature/SCRUM-N-descripcion-corta` desde `dev`; una historia por PR.
3. Implementa con las capas de [docs/estandar-codigo.md](docs/estandar-codigo.md) y textos de dominio en español.
4. Ejecuta `composer ci:check` y `composer test:coverage` cuando haya driver local.
5. Abre un único PR hacia `dev`; el code owner hace squash y el agente no hace merge.

Antes de integrar, la rama debe tener verdes estos checks:

| Workflow  | Checks                                             |
| --------- | -------------------------------------------------- |
| CI        | `CI / Calidad`, `CI / Pruebas y cobertura`         |
| Seguridad | `Seguridad / Dependencias`, `Seguridad / Secretos` |
| Deploy    | `Deploy / Build Docker`                            |

## Agentes de IA y despliegue

- Lee [docs/agentes.md](docs/agentes.md) antes de usar un agente o modelo.
- El despliegue reproducible está documentado en
  [docs/despliegue.md](docs/despliegue.md) y usa Docker/Render.
- El flujo completo de historias está en
  [docs/flujo-de-trabajo.md](docs/flujo-de-trabajo.md).

## Problemas comunes

- **PHPStan sin memoria:** usa el límite comprobado por CI:
  `phpstan analyse --memory-limit=512M`.
- **SQLite:** el entorno local usa SQLite y `.env.example` apunta a ella.
  Comprueba que PHP tenga la extensión SQLite habilitada.
- **Hook en Windows:** instala con `composer hooks:install` y ejecuta desde
  Git Bash si PowerShell no resuelve el hook; el hook también detecta
  `composer.bat`.
- **`migrate` frente a `migrate:fresh`:** `migrate` conserva datos y es el
  flujo de preparación; `migrate:fresh --seed` elimina y recrea todo para una
  prueba limpia.
- **Versión de Node:** CI usa Node 22 y Docker compila con Node 24. Si Vite
  falla con otra versión, usa una de esas versiones soportadas.
- **Puerto ocupado:** `composer dev` necesita sus puertos libres. Detén otro
  servidor local antes de iniciarlo.

## Equipo

El repositorio es `molxno/renta-vehiculos`. La coordinación técnica ocurre en
Jira (`SCRUM-*`), el código en GitHub y los contratos compartidos en `docs/`.
Los code owners revisan y hacen merge; los agentes no hacen merge ni modifican
rulesets.
