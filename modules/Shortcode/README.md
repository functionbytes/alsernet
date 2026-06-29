# Módulo Shortcode

Sistema de shortcodes estilo WordPress para Laravel, integrado con el ecosistema Alsernet (Forms, Page, Menu, Blog, Media).

## Características

- Parser stack-based (admite shortcodes anidados de la misma etiqueta)
- Cache persistente con Laravel Cache e invalidación por versión
- Eventos, whitelist por contexto, deprecaciones, aliases
- Integración directa con Forms / Page / Menu / Blog / Media
- Shortcodes lógicos: `[if]`, `[for]`, `[user-name]`, `[current-year]`
- Protección ReDoS con `pcre.backtrack_limit` y tamaño máximo 1 MB
- Escape literal `[[shortcode]]` compatible con WordPress
- API REST + vistas admin + 7 comandos artisan
- Pipeline opcional HTMLPurifier y hook CSP nonce

## Uso básico

```php
// En una vista Blade:
@shortcode($post->content)

// En PHP:
$html = shortcode('[button url="/contacto"]Contacto[/button]');

// Strip sin compilar:
$plain = strip_shortcodes($content);
```

## Registrar un shortcode custom

```php
use Modules\Shortcode\Facades\Shortcode;

Shortcode::register('highlight', function (array $attrs, string $content) {
    $attrs = shortcode_atts(['color' => 'yellow'], $attrs);

    return sprintf(
        '<mark style="background:%s">%s</mark>',
        htmlspecialchars($attrs['color']),
        $content
    );
}, [
    'description' => 'Resalta texto con color de fondo',
    'example' => '[highlight color="yellow"]texto[/highlight]',
    'attributes' => ['color' => 'Color de fondo CSS'],
    // Opcionales:
    // 'raw'        => true,   // el content no se re-procesa
    // 'cacheable'  => false,  // bypass del cache (útil para datos dinámicos)
    // 'alias_of'   => 'mark', // redirige al handler de otro
    // 'deprecated' => '3.0',  // emite E_USER_DEPRECATED al usarse
    // 'purify'     => true,   // pasa output por HTMLPurifier (si está instalado)
]);
```

## Shortcodes default disponibles

### Visuales (14)
`[button]` `[alert]` `[columns]` `[column]` `[youtube]` `[image]` `[icon]` `[badge]` `[card]` `[accordion]` `[accordion-item]` `[quote]` `[contact-form]` (demo)

### Integración con módulos
- `[form id="X"]` / `[form slug="X"]` — módulo Forms
- `[page slug="about"]` — módulo Page
- `[menu location="header"]` — módulo Template
- `[latest-posts count="5" category="news"]` — módulo Blog
- `[post id="X"]` / `[post slug="X"]` — módulo Blog
- `[media id="X" variant="thumb"]` — módulo Media

### Lógicos y contextuales
- `[if role="admin"]…[else]…[/if]` (condiciones: `role`, `permission`, `user-logged`, `user-id`)
- `[for range="1-5"]Item {i}[/for]`
- `[user-name]` `[user-email]` `[user-id]` `[site-name]` `[current-year]` `[current-date format="d/m/Y"]`

## Sintaxis de atributos

```
[name="valor"]       comillas dobles
[name='valor']       comillas simples
[name=valor]         sin comillas (hasta espacio)
[name]               booleano → $attrs['name'] === 'true'
[shortcode "valor"]  posicional → $attrs[0]
[data-id="42"]       guiones y dos puntos en nombres
```

## Escapar shortcodes literales

Para mostrar `[button]` como texto sin compilar:
```
[[button]]
```

## API REST

```
POST /api/shortcodes/compile      {"content": "[alert]x[/alert]"}
POST /api/shortcodes/strip        {"content": "..."}
POST /api/shortcodes/check        {"name": "button"}
GET  /api/shortcodes/list         → ["button", "alert", ...]
GET  /api/shortcodes/registered   → [{name, description, example, attributes, ...}]
GET  /api/shortcodes/stats        → {"button": 120, "alert": 34, ...}
POST /api/shortcodes/clear-cache
POST /api/shortcodes/stats/reset
```

Rate limit: 120 req/min por-usuario (si auth) o per-IP.

## Comandos artisan

```bash
php artisan shortcode:list                     # listar registrados
php artisan shortcode:compile "[alert]x[/alert]"
php artisan shortcode:compile "..." --strip    # eliminar shortcodes
php artisan shortcode:preview button           # renderiza el example
php artisan shortcode:find resources/views     # escanea archivos
php artisan shortcode:make my-widget           # genera stub
php artisan shortcode:benchmark --iterations=1000
php artisan shortcode:clear                    # invalida cache
```

## Vistas admin

- `/panel/setting/shortcodes` — lista + stats cards + preview interactivo en vivo
- `/panel/setting/shortcodes/reference` — documentación detallada
- `/panel/setting/shortcodes/tester` — todos los ejemplos renderizados lado a lado

## Configuración

Archivo `config/shortcode.php`:

```php
'enabled' => true,
'auto_register' => true,
'cache' => true,
'cache_duration' => 3600,
'max_nesting_level' => 10,
'error_handling' => 'log',          // silent | log | display | throw
'track_usage' => true,
'purify_enabled' => true,
'default_shortcodes' => [
    'button' => true,
    'contact-form' => true,
    // poner false para deshabilitar
],
```

## Eventos

```php
use Modules\Shortcode\Events\{ShortcodeRegistered, ShortcodeCompiling, ShortcodeCompiled};

// En un Listener:
public function handle(ShortcodeCompiled $event): void
{
    $event->original;  // string
    $event->compiled;  // string
    $event->passes;    // int
    $event->fromCache; // bool
}
```

## Pre-warmup con jobs

```php
use Modules\Shortcode\Jobs\ShortcodeWarmupJob;

// Tras un clearCache masivo, recalienta cache con los contenidos más leídos:
ShortcodeWarmupJob::dispatch(
    Page::published()->orderByDesc('views')->limit(50)->pluck('content')->all()
);
```

## Seguridad

- Atributos se escapan con `htmlspecialchars()`
- Contenido (entre tags) NO se escapa — admite HTML y shortcodes anidados (comportamiento WordPress)
- **Por tanto**: Solo compilar contenido de **administradores confiables**
- Límite de tamaño: `ShortcodeCompiler::MAX_CONTENT_SIZE = 1 MB`
- Límite de nesting: `config('shortcode.max_nesting_level', 10)`
- Backtrack limit de PCRE ajustado durante compile
- Rate limit per-user en API

Para user-generated content, habilitar `purify` en el shortcode (requiere paquete `ezyang/htmlpurifier`).

## Permisos

```
shortcode.view    → ver listado, referencia, tester, stats, compile API
shortcode.manage  → clear cache, reset stats
```

Seedear con:
```bash
php artisan module:seed Shortcode --class=ShortcodePermissionsSeeder
```

## Tests

```bash
./vendor/bin/phpunit modules/Shortcode/tests
```

Suites: unit (parser, attrs, advanced, hyphen, features), feature (defaults, helpers, commands, snapshots, controller).

## Licencia

MIT
