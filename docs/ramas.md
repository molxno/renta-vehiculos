# Ramas y promoción

## Mapa

- `feature/SCRUM-N-descripcion-corta`: nace desde `dev` y contiene una sola
  tarea. Usa `fix/` para correcciones y `chore/` para tareas técnicas.
- `dev`: integración de trabajo revisado. Los code owners mergean PR con
  **squash**.
- `qa`: validación integrada. La promoción `dev -> qa` usa **merge commit**.
- `main`: producción. La promoción `qa -> main` usa **merge commit** y solo la
  realiza Santiago a petición suya.

## Promoción

1. El agente abre el PR de la rama de historia hacia `dev`; nunca lo mergea.
2. Con `dev` y su CI en verde, se abre o actualiza un único PR titulado
   `Promoción a QA: SCRUM-N[, SCRUM-M]`.
3. Tras el merge commit a `qa` y el CI verde, el ticket puede pasar a
   **Finalizado** con comentario de evidencia.
4. Nadie abre PR a `main` sin la petición explícita de Santiago.

Las ramas `dev` y `qa` deben exigir los checks exactos documentados en la tabla
de esta página antes de permitir la integración.
La configuración de los rulesets se realiza
manualmente cuando el check esté estable; este documento no modifica la
configuración de GitHub.

## Flujo CI/CD

Hay dos workflows: **CI** (`.github/workflows/ci.yml`) y **CD**
(`.github/workflows/cd.yml`).

```text
PR → CI → merge a main → CI en main → CD (deploy hook de Render) → smoke test /up
```

```mermaid
flowchart LR
  PR[PR] --> CI[CI]
  CI --> MergeMain[merge a main]
  MergeMain --> CIMain[CI en main]
  CIMain --> CD[CD]
  CD --> Hook[deploy hook de Render]
  Hook --> Smoke[smoke test /up]
```

CI corre en cada `pull_request` y en cada `push` a `dev`, `qa` y `main`.
Tiene cuatro jobs en paralelo: **Calidad**, **Pruebas**, **Seguridad** y
**Docker**. En un PR, un push nuevo cancela el run anterior de esa rama.

CD solo arranca cuando CI termina en verde en `main` (o con
`workflow_dispatch` para un redeploy manual). Dispara el deploy hook de
Render y espera a que `https://rent-go-co67.onrender.com/up` responda 200.
El Auto-Deploy de Render debe quedar en **Off**: el despliegue lo dispara
CD, no el push a `main`.

El hook `.githooks/pre-push` sigue ejecutando `composer ci:check` en la
máquina que hace push, como puerta local.

## Checks obligatorios por rama

El ruleset de `main` debe exigir los cuatro checks de CI: **Calidad**,
**Pruebas**, **Seguridad** y **Docker**. En GitHub aparecen como
`CI / Calidad`, `CI / Pruebas`, `CI / Seguridad` y `CI / Docker`.

| Rama                    | Checks obligatorios                                                                                            |
| ----------------------- | -------------------------------------------------------------------------------------------------------------- |
| `feature/*` hacia `dev` | `composer ci:check` (hook local). GitHub: `CI / Calidad`, `CI / Pruebas`, `CI / Seguridad`, `CI / Docker`      |
| `dev`                   | Igual que `feature/*` hacia `dev`                                                                              |
| `qa`                    | Igual que `feature/*` hacia `dev`                                                                              |
| `main`                  | `CI / Calidad`, `CI / Pruebas`, `CI / Seguridad`, `CI / Docker`. Tras CI verde, CD despliega y hace smoke test |

## Cobertura

El hook local ejecuta `composer test:coverage` solo cuando detecta PCOV o
Xdebug; sin driver muestra un aviso y no bloquea. La medición canónica es
`vendor/bin/pest --coverage --min=100` con PCOV, en local o en el job
**Pruebas** de CI.
El umbral actual es **100 %**: la tercera medición alcanzó 100 % en todos
los archivos incluidos, por lo que `composer test:coverage` conserva
`--min=100`. Las iteraciones anteriores alcanzaron 56.6 % y 96.8 %; se
completó la cobertura pendiente de `AppServiceProvider` con pruebas de sus
defaults de producción.

El alcance de `phpunit.xml` incluye `app/`. Solo se excluyen `app/Contracts`,
porque contiene interfaces sin implementación ejecutable, y
`app/Http/Controllers/Controller.php`, porque es el controlador base vacío del
framework. No se excluyen modelos, enums, requests, fakes, providers ni
controladores concretos: contienen comportamiento o reglas verificables.
