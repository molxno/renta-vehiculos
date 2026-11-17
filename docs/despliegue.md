# Despliegue en Render

El MVP de demo corre como un **Web Service Free** en Render con runtime
**Docker**, rama **`main`** y auto-deploy. Render termina TLS en su proxy;
el contenedor escucha HTTP en un puerto sin privilegios (`PORT`, por
defecto `10000`).

La imagen compila el frontend con Node 24 y sirve Laravel con FrankenPHP.
Al arrancar, el entrypoint crea o actualiza una **SQLite efímera**, corre
migraciones, siembra el catálogo y los fixtures de demo, crea el enlace de
`storage` y cachea la configuración. Los datos del disco del contenedor se
pierden al recrearlo o al dormirse el plan Free: en el siguiente arranque
se vuelve a sembrar. Para persistencia real hay que cambiar SQLite por una
base gestionada.

El plan Free duerme el servicio. El primer request tras el idle provoca un
**cold start de alrededor de un minuto** (build de contenedor ya hecho;
el costo está en migrar, sembrar e `optimize`).

## Crear el servicio

1. En Render, **New > Web Service** conectado al repositorio.
2. Runtime **Docker**. Render usa el `Dockerfile` de la raíz.
3. Rama **`main`**. Activa auto-deploy.
4. Health Check Path: `/up`.
5. Render inyecta `PORT=10000`. El `Caddyfile` usa `{$PORT}`; no hace falta
   comando de inicio: el `ENTRYPOINT` es `entrypoint`.

No hace falta (ni conviene) el workflow `deploy.yml` para esta demo: Render
construye y publica al llegar el merge a `main`.

## Variables de entorno

Configura como mínimo:

```text
APP_NAME=Renta de vehículos
APP_ENV=production
APP_KEY=base64:...         # Generada fuera del repositorio
APP_DEBUG=false
APP_URL=https://<servicio>.onrender.com
DB_CONNECTION=sqlite
DB_DATABASE=/app/database/database.sqlite
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_SECURE_COOKIE=true
LOG_CHANNEL=stderr
```

`APP_KEY` es obligatoria. El script de arranque falla si falta; no se
genera una clave nueva en cada reinicio.

Laravel confía en los proxies (`trustProxies(at: '*')`) y, en producción,
fuerza el esquema `https` en las URLs. Hace falta porque Render termina
TLS delante del contenedor.

## Build y arranque manual

Para reproducir la imagen localmente:

```sh
docker build -t renta-vehiculos .
docker run --rm -p 8000:10000 \
  -e APP_KEY="$(php artisan key:generate --show)" \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  -e APP_URL=http://localhost:8000 \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=/app/database/database.sqlite \
  -e SESSION_DRIVER=database \
  -e CACHE_STORE=database \
  -e QUEUE_CONNECTION=database \
  renta-vehiculos
```

El contenedor corre como `www-data`. El binario de FrankenPHP no lleva
file capabilities (`setcap -r`): Render rechaza ejecutar binarios con
`CAP_NET_BIND_SERVICE`. Por eso se escucha en `10000` y no en `80`.

El entrypoint, en orden:

1. `php artisan migrate --force`
2. `php artisan db:seed --force` (idempotente con `updateOrCreate`; el
   catálogo mínimo corre una sola vez por arranque)
3. `php artisan storage:link --force`
4. `php artisan optimize`
5. `frankenphp run` con el `Caddyfile`

No se deben incluir secretos en el repositorio ni en el `Dockerfile`; se
configuran en **Environment** de Render.

## Health check

Laravel expone `/up`. En el plan Free, un 502 en el primer request suele
ser el cold start: espera ~1 min y reintenta. Si `/up` no responde después
de eso, el servicio se marca no saludable.
