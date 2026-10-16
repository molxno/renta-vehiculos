# Guía de agentes de IA

Esta guía sirve para cualquier agente o modelo que trabaje en el repositorio.
La fuente única de instrucciones del proyecto es [`AGENTS.md`](../AGENTS.md).
No la reemplazan las instrucciones del editor, el prompt del agente ni un
archivo generado.

## Qué debe leer cada herramienta

| Herramienta        | Archivo automático esperado                                             | Cómo apuntar a `AGENTS.md`                                                              | Estado                                                                                       |
| ------------------ | ----------------------------------------------------------------------- | --------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------- |
| Copilot en VS Code | `AGENTS.md` y reglas de Copilot del repositorio                         | Abre la raíz del repositorio como workspace y conserva `AGENTS.md` en la raíz           | Verificado en el repositorio; el comportamiento exacto depende de la versión de la extensión |
| Copilot CLI        | `AGENTS.md`; `scripts/agente.ps1` o `scripts/agente.sh` aplica límites  | Ejecuta el lanzador desde la raíz o indica explícitamente `@AGENTS.md` en el prompt     | Lanzador verificado; lectura automática exacta del CLI no se comprobó                        |
| Claude Code        | `AGENTS.md` si la búsqueda de instrucciones está habilitada             | Inicia Claude en la raíz y menciona `Lee AGENTS.md`; si no lo detecta, usa `@AGENTS.md` | No verificado en este entorno                                                                |
| Cursor             | Reglas del proyecto y archivos de instrucciones que la versión habilite | Añade `AGENTS.md` a las reglas del proyecto o referencia `@AGENTS.md`                   | No verificado en este entorno                                                                |
| Codex              | Archivo de instrucciones del proyecto que la versión habilite           | Inicia desde la raíz y referencia `AGENTS.md` explícitamente                            | No verificado en este entorno                                                                |
| Gemini CLI         | Archivo de instrucciones del proyecto que la versión habilite           | Inicia desde la raíz y referencia `AGENTS.md` explícitamente                            | No verificado en este entorno                                                                |

Si una herramienta no lee `AGENTS.md` por sí sola, no copies sus reglas a otro
archivo: apunta el contexto al archivo original. Así se evita que dos agentes
trabajen con políticas distintas.

## Servidores MCP

La configuración comprobada del repositorio está en `.vscode/mcp.json`:

```json
{
    "servers": {
        "laravel-boost": {
            "command": "php",
            "args": ["artisan", "boost:mcp"]
        },
        "atlassian": {
            "type": "http",
            "url": "https://mcp.atlassian.com/v1/mcp/authv2"
        },
        "figma": {
            "type": "http",
            "url": "https://mcp.figma.com/mcp"
        }
    }
}
```

- **Atlassian:** usa `https://mcp.atlassian.com/v1/mcp/authv2` para Jira y
  Confluence.
- **Figma:** usa `https://mcp.figma.com/mcp`. Respeta el límite de llamadas
  indicado por la respuesta del MCP; no repitas llamadas para explorar “por si
  acaso”.
- **Laravel Boost:** arranca localmente con `php artisan boost:mcp`.

La autenticación de los servidores HTTP es OAuth y se completa en el cliente;
no se deben pegar tokens en `AGENTS.md`, `.vscode/mcp.json`, commits ni
variables versionadas. El proceso local de Laravel Boost usa el entorno local.

### Copilot CLI

Usa el lanzador versionado:

```text
scripts\agente.ps1
```

o:

```text
./scripts/agente.sh
```

En la configuración del cliente establece **Defer Tools = Never** para que el
agente pueda consultar el MCP que necesita en el momento correcto. La
ubicación exacta de esa opción cambia entre versiones de Copilot CLI y no se
pudo comprobar aquí; el lanzador sí está versionado y comprobado.

### Claude Code

Registra los dos servidores HTTP con sus URL anteriores y el servidor local
con el comando `php artisan boost:mcp`, completa OAuth en Claude Code y
verifica que el estado del MCP sea conectado. El nombre de la clave puede
variar por versión de Claude Code; esa interfaz exacta no se comprobó en este
entorno. No guardes el token generado por OAuth en el repositorio.

## Lanzar un requerimiento

Para una historia, usa uno de estos disparadores:

```text
Toma SCRUM-N
/requerimiento SCRUM-N
```

El agente debe leer el ticket en Jira, comprobar dependencias y seguir
`docs/flujo-de-trabajo.md`. En esta versión el flujo exige un handoff de prueba
local antes del commit y un único PR hacia `dev`.

Para iniciar Copilot con las barreras del proyecto:

```text
scripts\agente.ps1
```

En macOS/Linux:

```text
./scripts/agente.sh
```

Si un MCP no está disponible, no inventes la respuesta. Registra qué servidor
falló, usa solo información local verificable y pausa si necesitas una
decisión, un dato de Jira o una lectura de Figma que no pueda comprobarse.

## Rulesets y límites

Los checks y reglas de ramas están descritos en
[docs/ramas.md](ramas.md); el agente no modifica rulesets ni su configuración.
Los límites duros son:

- no hacer merge, force push ni push directo a `dev`, `qa` o `main`;
- no borrar ramas remotas ni usar `--no-verify`;
- no escribir secretos ni `.env` versionado;
- no pasar un ticket a **Finalizado** antes de que el cambio esté en `qa`;
- no editar tickets de otras personas;
- no crear un PR a `main` sin petición explícita de Santiago;
- no saltarse los checks ni documentar resultados no ejecutados.

El agente sí puede editar código y documentación dentro del alcance del ticket,
crear tests, ejecutar los checks, abrir un PR hacia `dev`, comentar la
evidencia en Jira cuando corresponde y atender revisiones con commits nuevos.
Debe mantener las capas, los tipos, la cobertura y los textos de negocio del
proyecto.

## Laravel Boost

No edites bloques generados por Laravel Boost. Si Boost regenera un archivo,
cambia la fuente que lo produce o documenta la limitación; no modifiques
manualmente el bloque generado para ocultar una diferencia.
