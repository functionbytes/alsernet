# Skills en Claude Code - Documentacion Completa

## Indice

1. [Introduccion](01-introduccion.md) - Que son skills, vs commands, vs subagentes, skills integrados
2. [Crear tu primer Skill](02-crear-skill.md) - Estructura, donde guardar, ejemplo basico, auto-descubrimiento
3. [Referencia del Frontmatter](03-frontmatter-referencia.md) - Todos los campos detallados con ejemplos
4. [Argumentos y Variables](04-argumentos-y-variables.md) - $ARGUMENTS, $N, ${CLAUDE_SESSION_ID}, ${CLAUDE_SKILL_DIR}
5. [Control de Invocacion](05-control-invocacion.md) - Quien invoca, restricciones, budget de descripciones
6. [Contexto Dinamico](06-contexto-dinamico.md) - Sintaxis !`command`, preprocessing, extended thinking
7. [Ejecucion en Subagente](07-ejecucion-subagente.md) - context: fork, campo agent, precargar skills en subagentes
8. [Hooks en Skills](08-hooks-en-skills.md) - Hooks limitados al ciclo de vida del skill
9. [Patrones Avanzados](09-patrones-avanzados.md) - Archivos de apoyo, paths, pipelines, CI/CD, output visual
10. [Skills en Plugins](10-skills-en-plugins.md) - Namespace, plugin.json, variables, restricciones, migracion
11. [Skills del Proyecto](11-skills-del-proyecto.md) - Los 6 skills y 4 rules de este proyecto (Alsernet)

## Referencia rapida

### Crear skill
```bash
mkdir -p .claude/skills/mi-skill
# Crear .claude/skills/mi-skill/SKILL.md con frontmatter + contenido
```

### Invocar
```
/mi-skill argumento1 argumento2
```

### Frontmatter minimo
```yaml
---
name: mi-skill
description: Que hace y cuando usarlo
---

Instrucciones aqui...
```

### Frontmatter completo
```yaml
---
name: mi-skill
description: Descripcion (max 250 chars visibles)
argument-hint: "[modulo] [entidad]"
disable-model-invocation: true
user-invocable: true
allowed-tools: Read Grep Glob Bash(php artisan *)
model: sonnet
effort: high
context: fork
agent: plan
paths: "modules/**/*.php"
shell: bash
hooks:
  PostToolUse:
    - matcher: "Edit|Write"
      hooks:
        - type: command
          command: "vendor/bin/pint --dirty"
---
```

## Fuentes
- [Documentacion oficial (ES)](https://code.claude.com/docs/es/skills)
- [Referencia de plugins](https://code.claude.com/docs/es/plugins-reference)
- [Subagentes](https://code.claude.com/docs/es/sub-agents)
