# Inyeccion de Contexto Dinamico

## Sintaxis !`command`

La sintaxis `` !`<command>` `` ejecuta comandos de shell **ANTES** de que el contenido se envie a Claude. La salida reemplaza el placeholder. Claude solo ve el resultado, no el comando.

### Como funciona

1. Cada `` !`<command>` `` se ejecuta inmediatamente (antes de que Claude vea nada)
2. La salida reemplaza el placeholder en el contenido del skill
3. Claude recibe el prompt completamente renderizado con datos reales

### Ejemplo: resumen de PR

```yaml
---
name: pr-summary
description: Summarize changes in a pull request
context: fork
agent: Explore
allowed-tools: Bash(gh *)
---

## Pull request context
- PR diff: !`gh pr diff`
- PR comments: !`gh pr view --comments`
- Changed files: !`gh pr diff --name-only`

## Your task
Summarize this pull request. Focus on:
1. What changed and why
2. Potential risks
3. Testing suggestions
```

### Ejemplo: contexto de git

```yaml
---
name: commit-review
description: Review staged changes before committing
disable-model-invocation: true
---

## Current state
- Branch: !`git branch --show-current`
- Staged changes: !`git diff --cached --stat`
- Full diff: !`git diff --cached`

## Review checklist
1. Are all changes intentional?
2. Are there any debugging artifacts?
3. Is there test coverage?
4. Does the commit message accurately describe the changes?
```

### Ejemplo: datos de base de datos

```yaml
---
name: table-report
description: Generate a report of a database table
disable-model-invocation: true
---

## Table info
- Structure: !`php artisan tinker --execute="Schema::getColumnListing('$0')"`
- Row count: !`php artisan tinker --execute="DB::table('$0')->count()"`

## Task
Analyze the table $0 and generate a health report.
```

## Extended Thinking

Para habilitar extended thinking (pensamiento profundo) en un skill, incluye la palabra `ultrathink` en cualquier parte del contenido:

```yaml
---
name: deep-analysis
description: Deep analysis of complex code
---

Perform an ultrathink analysis of $ARGUMENTS.

Take your time to thoroughly understand every aspect...
```
