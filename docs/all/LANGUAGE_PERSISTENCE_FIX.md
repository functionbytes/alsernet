# Language Persistence Fix for Email Templates

## Problema Identificado

Cuando editabas un template de email en otro idioma (ej: English - lang_id=2) y hacías click en "Guardar", el sistema siempre regresaba a español (lang_id=1) en lugar de mantener el idioma que estabas editando.

## Causa

En el controlador `MailerTemplateController@update`, el método de redirección después de guardar cambios era:

```php
return redirect()
    ->back()
    ->with('success', 'Template actualizado exitosamente');
```

El problema es que `->back()` regresa a la página anterior sin mantener los parámetros de consulta (query parameters). Cuando haces click en "Guardar" desde English (lang_id=2), el navegador guarda el historial de navegación pero el `lang_id=2` se pierde.

## Solución Implementada

Se cambió el redirect para mantener explícitamente el `lang_id` seleccionado:

```php
return redirect()
    ->route('manager.backups.mailers.templates.edit', [
        'uid' => $template->uid,
        'lang_id' => $validated['lang_id'],
    ])
    ->with('success', 'Template actualizado exitosamente');
```

Ahora:
1. Cuando editas English (lang_id=2) y guardas → Redirige a edit con lang_id=2 ✅
2. Cuando editas Español (lang_id=1) y guardas → Redirige a edit con lang_id=1 ✅
3. El idioma seleccionado siempre persiste después de guardar cambios ✅

## Archivo Modificado

**`app/Http/Controllers/Managers/Settings/Mailers/MailerTemplateController.php`**
- Líneas 284-289: Se cambió el `redirect()->back()` por `redirect()->route()` manteniendo el `lang_id`

## Verificación del Fix

### Flujo de Prueba:
1. Acceder a: `/manager/settings/mailers/templates/{uid}/edit?lang_id=2`
2. Ver el contenido en English
3. Hacer cambios al template
4. Clickear "Guardar"
5. **Resultado esperado**: Redirige a `/manager/settings/mailers/templates/{uid}/edit?lang_id=2` con success message
6. **Antes del fix**: Redirigía a `/manager/settings/mailers/templates/{uid}/edit` (sin lang_id, defaulteando a Spanish)

## Prueba Automática

Se creó un archivo de prueba: `tests/Feature/MailerTemplateLanguagePersistenceTest.php`

Este archivo contiene dos tests:
1. `test_language_id_persists_after_saving_template` - Valida que el idioma persiste después de guardar
2. `test_switching_languages_maintains_selection` - Valida el flujo completo de cambiar idiomas

Para ejecutar:
```bash
php artisan test tests/Feature/MailerTemplateLanguagePersistenceTest.php
```

## Impacto

✅ **Positivo**: El usuario mantiene el idioma seleccionado al editar y guardar templates
✅ **No-invasivo**: Solo afecta el flujo de redirección después de guardar
✅ **Compatibilidad**: No afecta ningún otro controlador o vista

## ★ Insight ─────────────────────────────────────

**Key Learning Points:**

1. **URL Query Parameters in Redirects**: Cuando usas `redirect()->back()`, pierdes los parámetros de consulta. Para mantener contexto (como lang_id), siempre usa `redirect()->route()` con los parámetros explícitos.

2. **Stateful UI Context**: Los filtros, búsquedas y selecciones de idioma son parte del "estado" de la UI. Deben ser preservados en los query parameters, no en sesión, para que sean bookmarkeable y compartibles.

3. **Test Pattern for Language Features**: Este tipo de tests (cambiar contexto y verificar que persiste) es crítico para cualquier aplicación multi-idioma. Previene regressions donde el usuario pierde su contexto.

─────────────────────────────────────────────────────
