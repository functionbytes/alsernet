# Hooks en Skills

## Hooks limitados al ciclo de vida del skill

Puedes definir hooks directamente en el frontmatter del skill. Estos hooks solo se ejecutan mientras el skill esta activo y se limpian cuando termina.

```yaml
---
name: safe-deploy
description: Deploy with safety checks
disable-model-invocation: true
hooks:
  PreToolUse:
    - matcher: "Bash"
      hooks:
        - type: command
          command: "./scripts/validate-deploy-command.sh"
  Stop:
    - hooks:
        - type: prompt
          prompt: "Verify the deployment completed successfully. Check logs for errors."
---

Deploy $ARGUMENTS to production...
```

## Eventos soportados

Todos los eventos de hook de Claude Code estan soportados en skills:

| Evento | Uso comun en skills |
|---|---|
| `PreToolUse` | Validar comandos antes de ejecutar |
| `PostToolUse` | Formatear/verificar despues de editar |
| `Stop` | Verificar que el skill completo se ejecuto correctamente |
| `SubagentStart` | Configurar contexto para subagentes que el skill genera |
| `SubagentStop` | Validar output de subagentes |

## Ejemplo: skill de solo lectura con validacion

```yaml
---
name: db-reader
description: Execute read-only database queries
allowed-tools: Bash
hooks:
  PreToolUse:
    - matcher: "Bash"
      hooks:
        - type: command
          command: "${CLAUDE_SKILL_DIR}/scripts/validate-readonly-query.sh"
---

Execute the following query safely (read-only):

$ARGUMENTS
```

Script `scripts/validate-readonly-query.sh`:
```bash
#!/bin/bash
INPUT=$(cat)
COMMAND=$(echo "$INPUT" | jq -r '.tool_input.command // empty')

# Block SQL write operations
if echo "$COMMAND" | grep -iE '\b(INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|TRUNCATE)\b' > /dev/null; then
  echo "Blocked: Only SELECT queries are allowed" >&2
  exit 2
fi

exit 0
```

## Ejemplo: auto-format despues de cambios

```yaml
---
name: quick-fix
description: Quick code fix with auto-formatting
hooks:
  PostToolUse:
    - matcher: "Edit|Write"
      hooks:
        - type: command
          command: "jq -r '.tool_input.file_path' | xargs -I{} sh -c 'case \"{}\" in *.php) vendor/bin/pint \"{}\" ;; esac'"
---

Fix the following issue quickly:

$ARGUMENTS
```
