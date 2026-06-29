# Módulo Forms

Constructor de formularios personalizados con integración al Visual Editor de Pages.

## Características

- CRUD de formularios con categorías, plantillas y versionado
- 29 tipos de campo (texto, email, rating, NPS, signature, Likert, etc.)
- Multi-paso con navegación condicional
- Condicionales y lógica de salto
- Webhooks, emails condicionales y follow-ups programados
- Access tokens, password-protection y expiración
- Analíticas: completados, abandonos, conversión, envíos por hora
- Inbox con asignación, notas, archivos, spam/starred
- Import/export JSON, clonado, preview público con token
- Integración con Shortcode, Visual Editor, Seo, Captcha y Mailrelay

## Shortcodes

### `[form]`

Inserta un formulario por ID o slug.

```
[form id="1" display="inline" columns="2" theme="flat" show_title="true" lazy="true" /]
[form slug="contacto" display="popup" /]
```

Atributos: `id`, `slug`, `slug_{locale}`, `display` (inline|popup|slide_in), `columns` (1|2|3), `show_title`, `theme`, `lazy`, `button_text`.

### `[form-link]`

Botón que abre un formulario en modal sin renderizarlo inline.

```
[form-link id="1" text="Contactar" variant="primary" size="md" icon="fas fa-envelope" /]
```

## Eventos

- `Modules\Forms\Events\FormSubmitted` — tras cada envío exitoso. Escuchable desde otros módulos.
- `Modules\Forms\Events\NewFormSubmission` — versión broadcastable (WebSockets).

## Integración con Pages

- Caché del shortcode invalidada automáticamente al guardar Form/FormField.
- Si el driver de caché soporta tags (redis/memcached), usa tag `forms:shortcode:{id}`.
- Pages que embeben `[form]` o `[form-link]` se invalidan async vía `InvalidateFormPagesCacheJob` cuando el driver de cola lo permite.
- Listener opcional `Modules\Page\Listeners\InvalidatePagesOnFormSubmitted` reacciona a `FormSubmitted` para invalidar contadores visibles en la Page.

## Endpoints principales

| Método | Ruta | Nombre |
|--------|------|--------|
| GET | `/api/forms/{form}/preview-html` | `api.forms.preview-html` |
| GET | `/panel/forms/internal/{form}/preview-html` | `forms.internal.preview-html` |
| GET/POST | `/panel/pages/ve/preferences/{key}` | `pages.ve.preferences.*` |
| GET | `/forms/{slug}` (público) | `forms.public.show` |
| POST | `/forms/{slug}/submit` (público) | `forms.public.submit` |
| GET | `/forms/embed/{slug}` (público) | `forms.public.embed` |

## Config relevante (`config/forms.php`)

- `max_fields_per_form` — límite al builder del form.
- `throttle_submissions` — rate-limit por IP.
- `cache.shortcode_ttl_seconds` — TTL del caché `forms:active:*` (default 300).
- `cache.counters_ttl_seconds` — TTL del caché de contadores (default 60).
- `import.max_fields` — límite de campos al importar JSON (default 100).
- `import.max_file_bytes` — tamaño máximo del JSON (default 512KB).
- `permissions` — lista de permisos Spatie registrables.

## Scheduled tasks

Registrados automáticamente vía `callAfterResolving(Schedule::class, ...)`:

| Frecuencia | Command |
|------------|---------|
| cada 5 min | `forms:follow-ups:process` |
| cada hora | `forms:abandon:remind` |
| diario 03:15 | `forms:abandon:cleanup` |
| diario 03:30 | `forms:data:cleanup` |
| diario 03:45 | `forms:tokens:cleanup` |
| semanal dom 04:00 | `forms:versions:prune` |

Asume que el host ejecuta `php artisan schedule:run` cada minuto.

## Caché

| Key / Tag | TTL | Se invalida al… |
|-----------|-----|-----------------|
| `forms:active:{id}` / tag `forms:shortcode:{id}` | 300s | guardar Form o FormField |
| `forms:active:slug:{slug}` | 300s | guardar Form |
| `forms:{id}:submissions_count` | 60s | crear/actualizar/eliminar FormSubmission |
| `forms:{id}:unread_count` | 60s | cambio de `is_read` en FormSubmission |

## Permisos

Definidos en `config/forms.php`. Registrados por `FormsPermissionsSeeder`:

```
Forms.forms.{index,create,edit,delete,manage}
Forms.submissions.{index,export,delete}
Forms.categories.manage
Forms.analytics.index
Forms.settings.manage
Forms.inbox.index
Forms.field-types.manage
Forms.templates.manage
Forms.follow-ups.manage
Forms.access-tokens.manage
```
