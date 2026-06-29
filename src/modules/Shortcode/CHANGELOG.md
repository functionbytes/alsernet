# Changelog — Módulo Shortcode

Formato basado en [Keep a Changelog](https://keepachangelog.com/).

## [4.0.0] — 2026-04-19

### Agregado
- Shortcodes de integración con módulos del CMS:
  - `[form id="X"]` / `[form slug="X"]` — módulo Forms (iframe al form público)
  - `[page slug="X"]` — inyecta contenido de otra página (con límite anti-recursión)
  - `[menu location="header"]` — renderiza navegación del módulo Template
  - `[latest-posts count="5" category="slug"]` y `[post id="X"]` — módulo Blog
  - `[media id="X" variant="thumb"]` — módulo Media (img/video/audio)
- Shortcodes lógicos y contextuales:
  - `[if role="admin"]…[else]…[/if]` con condiciones: `role`, `permission`, `user-logged`, `user-id`
  - `[for range="1-5"]Item {i}[/for]` con placeholders `{i}` e `{index}`
  - `[user-name]`, `[user-email]`, `[user-id]` — todos `cacheable=false`
  - `[site-name]`, `[current-year]`, `[current-date format="d/m/Y"]`
- UI admin:
  - Preview interactivo en vivo (textarea + AJAX con debounce 400ms)
  - Vista `/panel/setting/shortcodes/tester` con todos los ejemplos lado a lado
  - Badge de deprecation y de alias en la tabla
- Parser extendido:
  - Atributos sin comillas: `[button url=/foo class=primary]`
  - Atributos booleanos: `[input disabled]` → `$attrs['disabled'] === 'true'`
  - Atributos posicionales: `[embed "https://…"]` → `$attrs[0]`
- Comando `shortcode:benchmark [--iterations=N] [--name=X]` — mide μs por compilación
- Job `ShortcodeWarmupJob` — precompila lotes de contenido en background
- Rate limiter custom `shortcode-api` — per-user si autenticado, per-IP si no (120/min)
- Servicio `ShortcodeUsageStats` — contador por shortcode en cache
- Listener `RecordShortcodeUsage` — incrementa stats tras cada ShortcodeCompiled
- Endpoints API: `GET /api/shortcodes/stats` y `POST /api/shortcodes/stats/reset`
- Pipeline opcional HTMLPurifier: `meta['purify'] => true` sanitiza output
- Hook `Shortcode::withCspNonce($nonce)` para shortcodes con `<script>`
- Tests: `ShortcodeCommandsTest`, `ShortcodeAttributesTest`, `DefaultShortcodesSnapshotTest`

### Cambiado
- `contact-form` redefinido como demo HTML-only; referencia al módulo Forms

## [3.0.0] — 2026-04-19

### Agregado
- Escape literal `[[shortcode]]` → `[shortcode]` (patrón WordPress)
- Skip de shortcodes dentro de comentarios HTML `<!-- [button] -->`
- Meta `raw => true`: el contenido no se re-procesa como shortcodes
- Meta `cacheable => false`: bypass del cache si el contenido lo incluye
- Meta `alias_of => "otro"`: redirige al handler del shortcode objetivo
- Meta `deprecated => "2.0"`: emite `E_USER_DEPRECATED` al usarse
- Parser stack-based: soporta nested shortcodes de la misma etiqueta `[card][card]x[/card][/card]`
- Protección ReDoS: `pcre.backtrack_limit = 100000` durante la compilación
- Métodos nuevos: `removeAll()`, `getRegex()`, `find($content)`
- Comandos: `shortcode:preview`, `shortcode:find`, `shortcode:make`

## [2.0.0] — 2026-04-19

### Agregado
- Cache persistente con Laravel Cache (reemplaza array in-memory)
- `clearCache()` funcional (version bump compatible con cualquier store)
- Método `compileWithContext($content, ?array $allowed)` — whitelist por contexto
- Constante `MAX_CONTENT_SIZE = 1 MB` previene ReDoS/OOM
- Método `atts(defaults, atts)` estilo WordPress
- Método `forgetCachedContent($content)` para invalidación puntual
- Límite `max_nesting_level` ahora se aplica (antes era decorativo)
- 4 modos `error_handling`: `silent`, `log`, `display`, `throw`
- Eventos: `ShortcodeRegistered`, `ShortcodeCompiling`, `ShortcodeCompiled`
- Excepción `ShortcodeCompilationException`
- Directiva Blade `@shortcodePicker('#selector')`
- Traducciones `lang/es/shortcode.php` y `lang/en/shortcode.php`
- Comandos artisan traducidos al español
- Helpers: `do_shortcode()`, `all_shortcodes_registered()`, `shortcode_atts()`
- Facade docblock completo

### Cambiado
- Config `default_shortcodes[name => bool]` ahora se respeta (antes era decorativo)

## [1.1.0] — 2026-04-19

### Agregado
- Form Requests: `CompileShortcodeRequest`, `StripShortcodeRequest`, `CheckShortcodeRequest`
- Permisos propios `shortcode.view` y `shortcode.manage` con seeder
- Endpoint `/api/shortcodes/registered` — metadata completa
- Rate limit `throttle:60,1` en todos los endpoints API
- Dropdown pattern en tabla admin
- Documentación explícita de política XSS en ServiceProvider

### Corregido
- Auto-close regex admite guiones: `[contact-form /]`, `[accordion-item /]`
- `parseAttributes()` admite `data-*`, `aria-*` y comillas simples
- `strip()` reordenado: self-closing primero
- Picker Blade autónomo con fallback si módulo Template no está
- Nombres de ruta API sin duplicar `api.`
- Shortcode `image` valida `ctype_digit` antes de `Media::find`

## [1.0.0] — 2026-02-08

Versión inicial: parser regex básico, 13 shortcodes default, helpers, Blade directive, 3 comandos artisan, picker modal, vistas admin.
