# Skills en Claude Code - Introduccion

## Que son los Skills

Los skills extienden lo que Claude puede hacer. Creas un archivo `SKILL.md` con instrucciones y Claude lo agrega a su kit de herramientas. Claude usa skills cuando es relevante, o puedes invocar uno directamente con `/nombre-del-skill`.

Los skills siguen el estandar abierto [Agent Skills](https://agentskills.io). Claude Code lo extiende con:
- Control de invocacion (quien puede invocar)
- Ejecucion en subagente aislado
- Inyeccion de contexto dinamico

## Skills vs Commands (Legacy)

Un archivo en `.claude/commands/deploy.md` y un skill en `.claude/skills/deploy/SKILL.md` ambos crean `/deploy` y funcionan igual. Los commands siguen funcionando, pero los skills agregan:

- Directorio para archivos de apoyo (templates, scripts, ejemplos)
- Frontmatter YAML para controlar invocacion
- Auto-carga cuando Claude detecta que es relevante
- Si un skill y un command comparten nombre, **el skill tiene prioridad**

## Skills vs Subagentes

| Caracteristica | Skill | Subagente |
|---|---|---|
| Invocacion | `/nombre` o auto por Claude | Auto-delegado por Claude |
| Contexto | Se inyecta en conversacion actual (o `fork`) | Siempre en contexto aislado |
| Herramientas | Hereda + `allowed-tools` opcional | Definidas en frontmatter |
| Modelo | Hereda o override con `model` | Definido en frontmatter |
| Memoria | No tiene persistente | `memory: project/user/local` |
| Proposito | Instrucciones/workflows | Roles especializados |

## Skills Integrados (Bundled)

Vienen con Claude Code y estan disponibles siempre:

| Skill | Uso |
|---|---|
| `/batch <instruccion>` | Cambios masivos en paralelo. Divide en 5-30 unidades, cada una en worktree aislado con su PR |
| `/claude-api` | Referencia de la API de Claude para tu lenguaje |
| `/debug [descripcion]` | Habilita logs de debug y troubleshooting |
| `/loop [intervalo] <prompt>` | Ejecuta prompt repetidamente en un intervalo |
| `/simplify [foco]` | Revisa archivos modificados con 3 agentes paralelos y aplica fixes |
