# Crear tu primer Skill

## Estructura de archivos

Cada skill es un directorio con `SKILL.md` como punto de entrada:

```
.claude/skills/
  mi-skill/
    SKILL.md           # Instrucciones principales (obligatorio)
    template.md        # Template para que Claude complete (opcional)
    examples/
      sample.md        # Ejemplo de output esperado (opcional)
    scripts/
      validate.sh      # Script que Claude puede ejecutar (opcional)
```

## Donde guardar skills

| Ubicacion | Ruta | Aplica a |
|---|---|---|
| **Enterprise** | Configuracion gestionada | Todos los usuarios de la org |
| **Personal** | `~/.claude/skills/<nombre>/SKILL.md` | Todos tus proyectos |
| **Proyecto** | `.claude/skills/<nombre>/SKILL.md` | Solo este proyecto |
| **Plugin** | `<plugin>/skills/<nombre>/SKILL.md` | Donde el plugin este habilitado |

Prioridad cuando hay conflicto de nombres: enterprise > personal > proyecto.
Los skills de plugin usan namespace `plugin-name:skill-name`, sin conflictos posibles.

## Ejemplo basico

Crear el directorio:
```bash
mkdir -p .claude/skills/explain-code
```

Crear `.claude/skills/explain-code/SKILL.md`:
```yaml
---
name: explain-code
description: Explains code with visual diagrams and analogies. Use when explaining how code works.
---

When explaining code, always include:

1. **Start with an analogy**: Compare the code to something from everyday life
2. **Draw a diagram**: Use ASCII art to show the flow
3. **Walk through the code**: Explain step-by-step
4. **Highlight a gotcha**: Common mistake or misconception

Keep explanations conversational.
```

## Probar el skill

**Automaticamente** (Claude lo detecta por la descripcion):
```
How does this code work?
```

**Manualmente** (invocacion directa):
```
/explain-code src/auth/login.ts
```

## Auto-descubrimiento en monorepos

Claude Code descubre skills desde directorios `.claude/skills/` anidados. Si estas editando un archivo en `packages/frontend/`, tambien busca en `packages/frontend/.claude/skills/`.

Los directorios agregados con `--add-dir` tambien cargan skills automaticamente con deteccion de cambios en vivo (no necesitas reiniciar).
