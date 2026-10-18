# Despliegue en Render

El proyecto se publica como un **Web Service Docker**. La imagen compila el
frontend con Node 24, ejecuta Laravel sobre FrankenPHP y crea/actualiza una
base SQLite efímera antes de iniciar el servidor. Los datos del contenedor se
pierden al recrearlo; para persistencia se deberá cambiar SQLite por una base
gestionada.

## Crear el servicio

1. En Render, crea un **New > Web Service** conectado al repositorio.
2. Selecciona el runtime **Docker** y deja que Render use el `Dockerfile` de la
   raíz.
3. Configura la rama `dev` o la rama que corresponda al despliegue.
4. Render expone automáticamente el puerto de `PORT`; el contenedor usa ese
   valor y por defecto escucha en el puerto 80.

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
LOG_CHANNEL=stack
LOG_STACK=single
```

`APP_KEY` es obligatoria. El script de arranque falla explícitamente si falta;
no se genera una clave nueva en cada reinicio.

## Build y arranque manual

Para reproducir la imagen localmente:

```sh
docker build -t renta-vehiculos .
docker run --rm -p 8000:80 \
  -e APP_KEY="$(php artisan key:generate --show)" \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=/app/database/database.sqlite \
  renta-vehiculos
```

Render ejecuta internamente el `Dockerfile`. El comando de inicio equivalente
es:

```sh
entrypoint
```

El script crea el archivo SQLite, ejecuta `php artisan migrate --force`, carga
los datos demo con `php artisan db:seed --force` y finalmente inicia
FrankenPHP/Caddy. No se deben incluir secretos en el repositorio ni en el
`Dockerfile`; se configuran en la sección **Environment** de Render.

## Health check

Laravel expone `/up` como endpoint de salud. Configúralo en Render como
**Health Check Path** para que el servicio se marque no saludable si el proceso
no responde.
