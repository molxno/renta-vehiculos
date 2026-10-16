# Flujo de trabajo

Este flujo es obligatorio para cualquier historia iniciada con el disparador
definido en `AGENTS.md`.

## 0. Preparar

1. Ejecuta `git fetch`.
2. Cambia a `dev` con `git switch dev`.
3. Actualiza con `git pull --ff-only`.
4. Verifica que el árbol de trabajo esté limpio.
5. Verifica que Jira responde mediante el MCP de Atlassian.
6. Ejecuta `composer ci:check` como línea base y exige que quede en verde.

## 1. Leer el ticket

Lee en Jira la descripción, los criterios de aceptación, la historia padre,
los bloqueos y las etiquetas `mock`, `figma-pendiente`, `fase-0` y `deploy`.
Pasa el ticket a **En curso** sin cambiar el asignado. Si lo bloquea un ticket
sin terminar, trabaja contra sus contratos y fixtures y avisa esa dependencia.

## 2. Crear la rama

Crea `feature/SCRUM-N-descripcion-corta` desde `dev`. Usa `fix/` para una
corrección y `chore/` para una tarea técnica sin funcionalidad de negocio.

## 3. Plan

Escribe un plan de 5 a 10 líneas con los archivos, las capas y los tests
afectados. No esperes aprobación para ejecutarlo.

## 4. UI

Lee `docs/design/` o los frames estrictamente necesarios de Figma, respetando
el límite de llamadas. Usa el tema definido por las variables de Tailwind y
mantén los textos de UI en español.

## 5. Implementar

Implementa con TDD en ciclo rojo, verde y refactor, siguiendo
`docs/estandar-codigo.md`. No introduzcas código en `app/` para una tarea que
solo requiere documentación o configuración.

## 6. Verificar

Ejecuta, hasta que estén verdes:

```text
composer ci:check
composer test:coverage
```

Si el mismo fallo ocurre dos veces, detente y reporta el error, el comando y
la salida relevante.

## 7. HANDOFF para prueba local

Entrega este bloque con todos los campos y después PAUSA hasta recibir
`probado OK`:

```text
HANDOFF — prueba local
Comandos:
- migrate:fresh --seed:
- fixture específico:
- composer dev:
Usuario y contraseña de prueba por rol:
- Rol:
Ruta o URL:
Pasos:
1.
2.
3.
Resultado esperado:
Flujos alternos a probar:
Qué está simulado y cómo cambia:
Tests y cobertura obtenidos:
Riesgos:
```

Si el humano reporta fallos, vuelve al paso 5. No avances al commit sin el
handoff y `probado OK`.

## 8. Commit y push

Usa el mensaje `SCRUM-N: descripción en imperativo` y haz push de la rama. El
hook ejecuta las validaciones.

## 9. Abrir el PR

Abre un único PR hacia `dev` con:

```text
gh pr create --base dev --body-file archivo-temporal
```

Rellena `.github/pull_request_template.md`, comenta el enlace en Jira y pasa
el ticket a **En revisión**. No cambies otros estados durante este paso.

## 10. Revisión

El code owner aprueba y hace merge con **squash**. El agente no mergea.
Atiende los comentarios con commits nuevos en la misma rama.

## 11. Promoción a QA

Con el PR integrado en `dev` y el CI de `dev` verde, abre o actualiza un único
PR `dev -> qa`, titulado `Promoción a QA: SCRUM-N[, SCRUM-M]`. Ese PR usa
**merge commit**, no squash. Después del merge a `qa` y del CI verde, pasa el
ticket a **Finalizado** y añade un comentario con la evidencia.

## 12. Promoción a main

La promoción `qa -> main` solo la hace Santiago a petición suya. Los agentes
no abren PR a `main` sin una petición explícita.

## 13. Cierre

Usa este resumen fijo:

```text
Cierre
- Ticket:
- PR:
- Commits:
- Checks:
- Cobertura:
- Pendientes:
```

## Si algo falla

- **CI rojo:** lee la primera causa, corrige con un commit nuevo y repite los
  checks obligatorios. Tras dos fallos iguales, detente y reporta.
- **Conflicto:** no fuerces la historia; actualiza desde la rama destino,
  resuelve de forma explícita, ejecuta las validaciones y documenta el cambio.
- **MCP caído:** no inventes respuestas. Usa información local verificable,
  registra qué MCP falló y pausa si necesitas Jira, Figma o una decisión que no
  pueda verificarse.

Los checks obligatorios son `npm run check`, `npm run build`,
`pint --parallel --test`, `phpstan analyse --memory-limit=512M`,
`php artisan config:clear --ansi` y `php artisan test --exclude-group=flow`,
todos incluidos en `composer ci:check`. Para cobertura usa además
`composer test:coverage` cuando exista en el entorno; si no existe, reporta la
limitación sin inventar un resultado.
