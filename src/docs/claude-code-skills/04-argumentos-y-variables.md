# Argumentos y Variables de Sustitucion

## Variables disponibles

| Variable | Descripcion |
|---|---|
| `$ARGUMENTS` | Todos los argumentos pasados al invocar. Si no esta en el contenido, se agrega como `ARGUMENTS: <valor>` |
| `$ARGUMENTS[N]` | Argumento especifico por indice (base 0). `$ARGUMENTS[0]` = primero |
| `$N` | Atajo para `$ARGUMENTS[N]`. `$0` = primero, `$1` = segundo |
| `${CLAUDE_SESSION_ID}` | ID de sesion actual. Para logging o archivos por sesion |
| `${CLAUDE_SKILL_DIR}` | Directorio que contiene SKILL.md. Para referenciar scripts incluidos |

## Ejemplo: todos los argumentos

```yaml
---
name: fix-issue
description: Fix a GitHub issue
disable-model-invocation: true
---

Fix GitHub issue $ARGUMENTS following our coding standards.

1. Read the issue description
2. Implement the fix
3. Write tests
4. Create a commit
```

Uso: `/fix-issue 123` -> Claude recibe "Fix GitHub issue 123 following..."

## Ejemplo: argumentos posicionales

```yaml
---
name: migrate-component
description: Migrate a component from one framework to another
---

Migrate the $0 component from $1 to $2.
Preserve all existing behavior and tests.
```

Uso: `/migrate-component SearchBar React Vue`
- `$0` = `SearchBar`
- `$1` = `React`
- `$2` = `Vue`

## Ejemplo: session ID para logging

```yaml
---
name: session-logger
description: Log activity for this session
---

Log the following to logs/${CLAUDE_SESSION_ID}.log:

$ARGUMENTS
```

## Ejemplo: referenciar scripts del skill

```yaml
---
name: codebase-visualizer
description: Generate interactive HTML tree of codebase
allowed-tools: Bash(python *)
---

Run the visualization script:

```bash
python ${CLAUDE_SKILL_DIR}/scripts/visualize.py .
```

This creates `codebase-map.html` and opens it in your browser.
```

## Comportamiento sin $ARGUMENTS

Si invocas un skill con argumentos pero el skill NO incluye `$ARGUMENTS` en su contenido, Claude Code agrega automaticamente `ARGUMENTS: <tu input>` al final del contenido.
