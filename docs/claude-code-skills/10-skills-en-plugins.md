# Skills en Plugins

## Estructura en un plugin

```
mi-plugin/
  .claude-plugin/
    plugin.json
  skills/
    code-review/
      SKILL.md
      reference.md
    deploy/
      SKILL.md
      scripts/
        deploy.sh
  commands/           # Legacy, tambien funciona
    status.md
```

## Namespace

Los skills de plugin usan namespace automatico: `plugin-name:skill-name`.

Si el plugin se llama `mi-plugin` y el skill `deploy`, se invoca como:
```
/mi-plugin:deploy
```

## plugin.json

```json
{
  "name": "mi-plugin",
  "description": "My custom plugin",
  "version": "1.0.0",
  "skills": "./custom/skills/"
}
```

El campo `skills` acepta string o array. Las rutas custom complementan los directorios default (no los reemplazan).

## Variables de entorno en skills de plugin

| Variable | Descripcion |
|---|---|
| `${CLAUDE_PLUGIN_ROOT}` | Ruta absoluta al directorio de instalacion del plugin |
| `${CLAUDE_PLUGIN_DATA}` | Directorio persistente para estado del plugin (sobrevive updates) |
| `${user_config.KEY}` | Valores no-sensibles de userConfig del plugin |

## Restricciones de seguridad

Los agents de plugin que referencian skills soportan el campo `skills` en frontmatter, pero los agents de plugin NO soportan:
- `hooks`
- `mcpServers`
- `permissionMode`

Si los necesitas, copia el archivo del agent a `.claude/agents/` o `~/.claude/agents/`.

## Probar plugin localmente

```bash
.claude --plugin-dir ./mi-plugin
```

Para recargar sin reiniciar:
```
/reload-plugins
```

Probar skills del plugin:
```
/mi-plugin:skill-name
```

## Convertir skills existentes a plugin

1. Crear estructura:
```bash
mkdir -p mi-plugin/..claude-plugin
mkdir -p mi-plugin/skills
```

2. Crear manifiesto `mi-plugin/.claude-plugin/plugin.json`:
```json
{
  "name": "mi-plugin",
  "description": "Migrated from standalone config",
  "version": "1.0.0"
}
```

3. Copiar skills:
```bash
cp -r ..claude/skills/* mi-plugin/skills/
```

4. Probar:
```bash
.claude --plugin-dir ./mi-plugin
```
