# Ramas y promoción

## Mapa

- `feature/SCRUM-N-descripcion-corta`: nace desde `dev` y contiene una sola
  tarea. Usa `fix/` para correcciones y `chore/` para tareas técnicas.
- `dev`: integración de trabajo revisado. Los code owners mergean PR con
  **merge commit**.
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

## Checks obligatorios por rama

La puerta de calidad vigente es el hook `.githooks/pre-push`, que ejecuta
`composer ci:check` en la máquina que hace push. GitHub Actions quedó en
`workflow_dispatch` porque la cuenta de GitHub está bloqueada por facturación
y los runners alojados no arrancan. Eso no es un fallo de código. Cuando
Actions vuelva a estar disponible, se restauran los disparos `push` y
`pull_request` en `.github/workflows/` y, si el check ya es estable, se
vuelven a exigir en el ruleset.

| Rama                    | Checks obligatorios                                                                                                                                                                                |
| ----------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `feature/*` hacia `dev` | `composer ci:check` (hook local). GitHub: `CI / Calidad`, `CI / Pruebas y cobertura`, `Seguridad / Dependencias`, `Seguridad / Secretos`, `Deploy / Build Docker` cuando Actions esté desbloqueado |
| `dev`                   | Igual que `feature/*` hacia `dev`                                                                                                                                                                  |
| `qa`                    | Igual que `feature/*` hacia `dev`                                                                                                                                                                  |
| `main`                  | Igual que `feature/*` hacia `dev`                                                                                                                                                                  |

## Cobertura

El hook local ejecuta `composer test:coverage` solo cuando detecta PCOV o
Xdebug; sin driver muestra un aviso y no bloquea. La medición canónica es
`vendor/bin/pest --coverage --min=100` con PCOV, en local o en GitHub Actions
cuando los runners vuelvan a estar disponibles.
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
