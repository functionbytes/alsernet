# Email Template Translation UID Implementation

## Resumen

Se implementó un sistema de **UID (Unique Identifier)** para la tabla `email_template_langs`, permitiendo acceso directo a traducciones específicas de templates de email mediante URLs del tipo:

```
/manager/settings/mailers/templates/edit/{template_uid}/{translation_uid}
```

## Arquitectura Anterior

**Antes:**
```
URL: /edit/{template_uid}?lang_id=1
Problema: Necesitaba parámetro de consulta para identificar el idioma
```

Cuando hacías click en "English", el link era:
```html
<a href="/edit/7cdbb509-1ade...?lang_id=2">English</a>
```

## Arquitectura Nueva

**Ahora:**
```
URL: /edit/{template_uid}/{translation_uid}
Ventaja: El UID de la traducción está en la URL, no en query params
```

Cuando haces click en "English", el link es:
```html
<a href="/edit/7cdbb509-1ade-4614-9557-92caba3f9377/a1b2c3d4-e5f6-7890-1234-567890abcdef">
  English
</a>
```

## Cambios Implementados

### 1. **Migration** (`database/migrations/2025_12_11_165703_...`)

Agrega la columna `uid` a la tabla `email_template_langs`:

```php
Schema::table('email_template_langs', function (Blueprint $table) {
    $table->uuid('uid')->unique()->after('id')->nullable();
});
```

**Ejecución:**
```bash
php artisan migrate
```

### 2. **Model** (`app/Models/Email/EmailTemplateTranslation.php`)

Agregó el trait `HasUid` para auto-generar UIDs:

```php
use app\Library\Traits\HasUid;

class EmailTemplateTranslation extends Model
{
    use HasUid;

    protected $fillable = [
        'uid',  // Agregado
        'email_template_id',
        'lang_id',
        // ...
    ];
}
```

### 3. **Routes** (`routes/managers.php`)

Actualizada la ruta para aceptar `translation_uid` opcional:

```php
// Antes:
Route::get('/edit/{uid}', [MailerTemplateController::class, 'edit']);

// Después:
Route::get('/edit/{uid}/{translation_uid?}', [MailerTemplateController::class, 'edit']);
```

**Compatibilidad:**
- ✅ `/edit/{uid}` - Sigue funcionando con lang_id en query params
- ✅ `/edit/{uid}/{translation_uid}` - Nuevo parámetro directo

### 4. **Controller** (`MailerTemplateController@edit`)

Actualizado para cargar por UID de traducción:

```php
public function edit(Request $request, $uid, $translation_uid = null)
{
    $template = EmailTemplate::where('uid', $uid)->firstOrFail();

    if ($translation_uid) {
        // Cargar por UID de traducción
        $translation = EmailTemplateTranslation::where('uid', $translation_uid)
            ->where('email_template_id', $template->id)
            ->firstOrFail();
        $langId = $translation->lang_id;
    } else {
        // Cargar por lang_id (comportamiento anterior)
        $langId = $request->input('lang_id', 1);
        $translation = $template->translate($langId);
    }
    // ...
}
```

### 5. **View** (`resources/views/.../templates/edit.blade.php`)

Actualizada para incluir translation_uid en los links:

```blade
@php
    $langTranslation = $template->translations()
        ->where('lang_id', $lang->id)
        ->first();
    $translationUid = $langTranslation?->uid;
@endphp

<a href="{{ route('manager.settings.mailers.templates.edit', [
    'uid' => $template->uid,
    'translation_uid' => $translationUid
]) }}">
    {{ $lang->title }}
</a>
```

## Flujo de Uso

### Antes (Antigua Arquitectura)

1. Accedes a: `/edit/7cdbb509-1ade...`
2. Sistema usa `lang_id=1` por defecto
3. Haces click en "English" → URL con `?lang_id=2`
4. Guardas → Se redirige con `->back()` → Vuelve a `lang_id=1`

### Ahora (Nueva Arquitectura)

1. Accedes a: `/edit/7cdbb509-1ade.../a1b2c3d4-e5f6...`
2. Sistema carga la traducción específica
3. Haces click en "English" → URL con translation_uid de English
4. Guardas → Se redirige manteniendo el translation_uid correcto

## Beneficios

✅ **URLs más semánticas** - El UID de la traducción está en la URL
✅ **Mejor rastreabilidad** - Cada traducción tiene su identificador único
✅ **Bookmarkeable** - Puedes guardar URLs directas a traducciones específicas
✅ **Backwards compatible** - Sigue funcionando con `lang_id` en query params
✅ **Mejor UX** - No pierdes el contexto al navegar entre idiomas

## Verificación de la Implementación

### Prueba Manual

1. Navega a un template: `/manager/settings/mailers/templates/edit/{uid}/{translation_uid}`
2. La página debe cargar con la traducción específica
3. Los links de idioma deben incluir los UIDs correspondientes

### Base de Datos

Verifica que las traducciones tengan UIDs:

```sql
SELECT id, uid, email_template_id, lang_id
FROM email_template_langs
LIMIT 5;
```

```
id | uid                                  | email_template_id | lang_id
1  | 550e8400-e29b-41d4-a716-446655440000 | 1                 | 1
2  | 550e8400-e29b-41d4-a716-446655440001 | 1                 | 2
3  | 550e8400-e29b-41d4-a716-446655440002 | 1                 | 3
```

## ★ Insight ─────────────────────────────────────

**Patrones de Diseño Implementados:**

1. **UUID para Identificadores Públicos**: Los UIDs permiten URLs limpias sin exponer IDs numéricos autoincrementales.

2. **Parámetros Opcionales en Rutas**: La ruta `/edit/{uid}/{translation_uid?}` mantiene compatibilidad bidireccional:
   - Sin translation_uid: usa lang_id (antiguo método)
   - Con translation_uid: acceso directo (nuevo método)

3. **Relaciones Anidadas en URLs**: Las URLs reflejan la jerarquía:
   - Template → Translation
   - `/templates/{template_uid}/{translation_uid}`

4. **Trait HasUid**: Automáticamente genera y persiste UIDs, manteniendo el código limpio sin lógica manual en controladores.

─────────────────────────────────────────────────────

## Próximos Pasos (Opcional)

1. **Actualizar URLs de redirección después de guardar** (ya se hizo en el commit anterior)
2. **Agregar tests** para validar carga por translation_uid
3. **Migrar datos** para poblaciones existentes con UIDs
4. **Deprecar lang_id** en futuras versiones

## Troubleshooting

**Error: "No query results for model"**
- Verifica que el translation_uid existe para el template_id
- Asegúrate de que la migración se ejecutó correctamente

**URLs con lang_id no funcionan después de actualizar**
- La ruta sigue soportando `lang_id` vía query params
- Ejemplo: `/edit/{uid}?lang_id=2` sigue siendo válido

## Commit relacionado

- Hash: `9c80c4d7` - feat: Add UID to email template translations for direct access
