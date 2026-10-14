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

Las ramas `dev` y `qa` deben exigir el check exacto `tests / ci` antes de
permitir la integración. La configuración de los rulesets se realiza
manualmente cuando el check esté estable; este documento no modifica la
configuración de GitHub.

## Cobertura

El job **Pruebas y cobertura** ejecuta `vendor/bin/pest --coverage` en CI con
PCOV y publica el porcentaje por archivo en los logs. El hook local ejecuta
`composer test:coverage` solo cuando detecta PCOV o Xdebug; sin driver muestra
un aviso y no bloquea. La medición canónica se hace en CI.

El alcance de `phpunit.xml` incluye `app/`. Solo se excluyen `app/Contracts`,
porque contiene interfaces sin implementación ejecutable, y
`app/Http/Controllers/Controller.php`, porque es el controlador base vacío del
framework. No se excluyen modelos, enums, requests, fakes, providers ni
controladores concretos: contienen comportamiento o reglas verificables.
