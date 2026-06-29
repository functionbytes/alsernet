# Referencia completa del Frontmatter

Todos los campos son opcionales. Solo `description` es recomendado.

```yaml
---
name: mi-skill
description: Que hace y cuando usarlo
argument-hint: "[issue-number]"
disable-model-invocation: true
user-invocable: false
allowed-tools: Read Grep Glob
model: sonnet
effort: medium
context: fork
agent: Explore
hooks:
  PreToolUse:
    - matcher: "Bash"
      hooks:
        - type: command
          command: "./scripts/validate.sh"
paths: "src/**/*.ts,tests/**"
shell: bash
---
```

## Campos detallados

### name
- **Tipo**: string
- **Default**: nombre del directorio
- **Restricciones**: solo letras minusculas, numeros y guiones (max 64 caracteres)
- **Se convierte en**: el `/slash-command`

### description
- **Tipo**: string
- **Importancia**: Claude usa esto para decidir cuando aplicar el skill
- **Limite**: primeros 250 caracteres se muestran en la lista de skills (se trunca)
- **Tip**: pon las palabras clave de uso al principio
- Si se omite, usa el primer parrafo del contenido markdown

### argument-hint
- **Tipo**: string
- **Proposito**: sugerencia mostrada durante autocompletado
- **Ejemplo**: `[issue-number]` o `[filename] [format]`

### disable-model-invocation
- **Tipo**: boolean
- **Default**: `false`
- **Cuando usar**: para workflows con side-effects que quieres controlar manualmente
- **Ejemplo**: `/deploy`, `/commit`, `/send-slack-message`
- Cuando es `true`, la descripcion NO se carga en contexto

### user-invocable
- **Tipo**: boolean
- **Default**: `true`
- **Cuando usar**: para conocimiento de fondo que no es accionable como comando
- **Ejemplo**: un skill `legacy-system-context` que explica como funciona un sistema antiguo
- Solo controla visibilidad del menu, NO bloquea invocacion programatica

### allowed-tools
- **Tipo**: string separado por espacios o lista YAML
- **Proposito**: herramientas que Claude puede usar sin pedir permiso cuando el skill esta activo
- **Ejemplo**: `Read Grep Glob` o `Bash(npm test) Edit Write`
- Los permisos de la sesion siguen gobernando el baseline

### model
- **Tipo**: string
- **Opciones**: `sonnet`, `opus`, `haiku`, o ID completo como `claude-opus-4-6`
- **Proposito**: modelo a usar cuando el skill esta activo

### effort
- **Tipo**: string
- **Opciones**: `low`, `medium`, `high`, `max` (solo Opus 4.6)
- **Proposito**: override del nivel de esfuerzo de la sesion

### context
- **Tipo**: string
- **Unico valor**: `fork`
- **Proposito**: ejecutar en un subagente aislado (sin acceso al historial de conversacion)
- Solo tiene sentido para skills con instrucciones explicitas, no para guidelines generales

### agent
- **Tipo**: string
- **Opciones**: `Explore`, `Plan`, `general-purpose`, o nombre de subagente custom de `.claude/agents/`
- **Default**: `general-purpose` si se omite
- **Requisito**: solo funciona cuando `context: fork` esta establecido

### hooks
- **Tipo**: objeto de hooks
- **Proposito**: hooks limitados al ciclo de vida de este skill
- **Formato**: mismo formato que hooks en settings.json

### paths
- **Tipo**: string separado por comas o lista YAML
- **Proposito**: patrones glob que limitan cuando se activa el skill
- **Ejemplo**: `"modules/Analytics/**/*.php,modules/Analytics/**/*.blade.php"`
- Cuando se establece, Claude auto-carga el skill solo cuando trabaja con archivos que coinciden

### shell
- **Tipo**: string
- **Opciones**: `bash` (default) o `powershell`
- **Proposito**: shell para bloques `` !`command` `` en este skill
- `powershell` requiere `CLAUDE_CODE_USE_POWERSHELL_TOOL=1`
