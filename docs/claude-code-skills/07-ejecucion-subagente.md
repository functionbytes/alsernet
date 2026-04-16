# Ejecutar Skills en un Subagente (context: fork)

## Cuando usar context: fork

Agrega `context: fork` cuando quieras que un skill se ejecute en aislamiento. El contenido del skill se convierte en el prompt del subagente. NO tiene acceso al historial de conversacion.

**IMPORTANTE**: `context: fork` solo tiene sentido para skills con instrucciones explicitas. Si tu skill solo contiene guidelines sin una tarea, el subagente recibe las guidelines pero sin prompt accionable y retorna sin output significativo.

## Ejemplo basico

```yaml
---
name: deep-research
description: Research a topic thoroughly
context: fork
agent: Explore
---

Research $ARGUMENTS thoroughly:

1. Find relevant files using Glob and Grep
2. Read and analyze the code
3. Summarize findings with specific file references
```

## Flujo de ejecucion

1. Se crea un nuevo contexto aislado
2. El subagente recibe el contenido del skill como su prompt
3. El campo `agent` determina el entorno (modelo, herramientas, permisos)
4. Los resultados se resumen y retornan a la conversacion principal

## Campo agent

Especifica que configuracion de subagente usar:

| Valor | Descripcion |
|---|---|
| `Explore` | Solo lectura, rapido, optimizado para busqueda (usa Haiku) |
| `Plan` | Solo lectura para planificacion |
| `general-purpose` | Todas las herramientas (default si se omite) |
| `backend` | Tu subagente custom de `.claude/agents/backend.md` |
| `frontend` | Tu subagente custom de `.claude/agents/frontend.md` |
| Cualquier nombre | Cualquier subagente de `.claude/agents/` |

## Skill vs Subagente: dos direcciones

| Enfoque | System prompt | Tarea | Tambien carga |
|---|---|---|---|
| **Skill con `context: fork`** | Del tipo de agent (Explore, Plan, etc.) | Contenido de SKILL.md | CLAUDE.md |
| **Subagente con campo `skills`** | Body markdown del subagente | Mensaje de delegacion de Claude | Skills precargados + CLAUDE.md |

## Precargar skills en subagentes

Usa el campo `skills` en el frontmatter del subagente para inyectar contenido de skills al inicio:

```yaml
# .claude/agents/api-developer.md
---
name: api-developer
description: Implement API endpoints following team conventions
skills:
  - api-conventions
  - error-handling-patterns
---

Implement API endpoints. Follow the conventions and patterns from the preloaded skills.
```

**Diferencias clave**:
- El contenido COMPLETO de cada skill se inyecta en el contexto del subagente
- Los subagentes NO heredan skills de la conversacion principal
- Debes listarlos explicitamente

## Ejemplo: audit con fork

```yaml
---
name: security-scan
description: Run a security scan on a module
context: fork
agent: security
allowed-tools: Read Grep Glob Bash(composer audit)
---

## Security Audit for: $ARGUMENTS

Perform a comprehensive security audit:

1. Check for SQL injection (search whereRaw, DB::raw)
2. Check for XSS (search {!! in Blade views)
3. Check mass assignment ($guarded = [])
4. Check routes without auth middleware
5. Run composer audit

Report findings with severity ratings.
```
