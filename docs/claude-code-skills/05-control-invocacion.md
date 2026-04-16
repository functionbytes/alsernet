# Control de Invocacion

## Quien puede invocar un skill

Por defecto, tanto tu como Claude pueden invocar cualquier skill.

### Tabla de comportamiento

| Frontmatter | Tu puedes invocar | Claude puede invocar | Cuando se carga en contexto |
|---|---|---|---|
| (default) | Si | Si | Descripcion siempre en contexto; skill completo al invocar |
| `disable-model-invocation: true` | Si | No | Descripcion NO en contexto; skill completo cuando tu lo invocas |
| `user-invocable: false` | No | Si | Descripcion siempre en contexto; skill completo al invocar |

### Solo usuario (disable-model-invocation)

Usa para workflows con side-effects que quieres controlar:

```yaml
---
name: deploy
description: Deploy the application to production
disable-model-invocation: true
---

Deploy $ARGUMENTS to production:
1. Run the test suite
2. Build the application
3. Push to the deployment target
4. Verify the deployment succeeded
```

Claude NUNCA ejecutara `/deploy` por si solo. Solo tu puedes hacerlo.

### Solo Claude (user-invocable: false)

Usa para conocimiento de fondo que no es un comando:

```yaml
---
name: legacy-system-context
description: How the old auth system works. Use when debugging auth issues.
user-invocable: false
---

The legacy auth system uses JWT tokens stored in httpOnly cookies...
```

No aparece en el menu `/`, pero Claude lo carga automaticamente cuando es relevante.

## Restringir acceso a skills

### Deshabilitar TODOS los skills

En `/permissions`, agrega a deny rules:
```
Skill
```

### Permitir/denegar skills especificos

```
# Permitir solo skills especificos
Skill(commit)
Skill(review-pr *)

# Denegar skills especificos
Skill(deploy *)
```

Sintaxis: `Skill(name)` para match exacto, `Skill(name *)` para match de prefijo.

## Budget de descripciones

Las descripciones se cargan en contexto para que Claude sepa que hay disponible. Si tienes muchos skills, las descripciones se acortan para caber en el presupuesto.

- Budget escala al 1% de la ventana de contexto (fallback: 8,000 caracteres)
- Cada entrada limitada a 250 caracteres independiente del budget
- Para aumentar: `SLASH_COMMAND_TOOL_CHAR_BUDGET=16000`
- Pon las palabras clave al principio de la descripcion
