# Patrones Avanzados

## 1. Skill con archivos de apoyo

Mantiene SKILL.md enfocado. Claude carga archivos extra solo cuando los necesita.

```
api-generator/
  SKILL.md
  reference.md          # Documentacion detallada de la API
  examples.md           # Ejemplos de endpoints
  templates/
    controller.md       # Template de controller
    resource.md         # Template de API Resource
  scripts/
    validate-routes.sh  # Script de validacion
```

En `SKILL.md`:
```markdown
---
name: api-generator
description: Generate API endpoints following project conventions
---

Generate API endpoints for $ARGUMENTS.

## References
- For API patterns and conventions, see [reference.md](reference.md)
- For endpoint examples, see [examples.md](examples.md)
- Controller template: [templates/controller.md](templates/controller.md)
- Resource template: [templates/resource.md](templates/resource.md)
```

**Tip**: Manten SKILL.md bajo 500 lineas. Mueve material de referencia a archivos separados.

## 2. Skill con restriccion de paths

Solo se activa cuando trabajas con archivos que coinciden:

```yaml
---
name: analytics-conventions
description: Analytics module coding conventions
user-invocable: false
paths:
  - "modules/Analytics/**/*.php"
  - "modules/Analytics/**/*.blade.php"
---

When working in the Analytics module:
- Use the red color palette (#90bb13, #333333, #7b0000)
- Charts use DevExpress dxChart widget
- Dashboard cards use Bootstrap grid with g-3 gap
```

## 3. Skill que genera output visual

```yaml
---
name: codebase-visualizer
description: Generate interactive HTML tree of codebase
allowed-tools: Bash(python *)
disable-model-invocation: true
---

Run the visualization script from project root:

```bash
python ${CLAUDE_SKILL_DIR}/scripts/visualize.py .
```

This creates `codebase-map.html` and opens it in your browser.
```

## 4. Skill como pipeline (multiples subagentes)

```yaml
---
name: full-review
description: Complete code review pipeline
context: fork
agent: plan
disable-model-invocation: true
---

Run a complete review pipeline for $ARGUMENTS:

## Phase 1: Parallel Analysis (use 3 subagents)
1. **security agent**: Scan for vulnerabilities
2. **performance agent**: Profile queries and identify bottlenecks
3. **review agent**: Check code quality and conventions

## Phase 2: Synthesis
Combine all findings into a single prioritized report.

## Phase 3: Fix
For each Critical finding, delegate to the appropriate agent to fix it.
```

## 5. Skill que wrappea otro skill

```yaml
---
name: safe-batch
description: Run batch with extra safety checks
disable-model-invocation: true
allowed-tools: Bash(git stash *) Bash(git status *)
---

Before running batch:
1. Stash any uncommitted changes: `git stash push -m "pre-batch"`
2. Verify clean working tree: `git status`

Now run: /batch $ARGUMENTS

After batch completes:
1. Review all generated PRs
2. Pop stash if needed: `git stash pop`
```

## 6. Skill para CI/CD (headless)

Diseñado para ejecutarse con `claude -p`:

```yaml
---
name: pr-check
description: Automated PR quality check for CI
context: fork
agent: review
disable-model-invocation: true
---

## PR Quality Check

Review the current branch changes:
- Diff: !`git diff main...HEAD`
- Changed files: !`git diff --name-only main...HEAD`
- Commit messages: !`git log --oneline main...HEAD`

## Checklist
1. Are commit messages descriptive?
2. Are there any security issues?
3. Are there any performance concerns?
4. Is there test coverage for changes?
5. Do all files follow project conventions?

Output a JSON report with pass/fail status.
```

Uso en CI:
```bash
.claude -p "/pr-check" --output-format json --allowedTools "Read,Grep,Glob"
```
