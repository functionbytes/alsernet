# Shortcode — Health Checklist

Checklist para `/module-doctor Shortcode` y diagnóstico manual.

## Registro del módulo

- [ ] `modules_statuses.json` contiene `"Shortcode": true`
- [ ] `composer.json` autoload: `"Modules\\Shortcode\\": "modules/Shortcode/app/"`
- [ ] `modules/Shortcode/module.json` declara `ShortcodeServiceProvider` en `providers`
- [ ] `ShortcodeServiceProvider::boot()` inicia con `Module::find('Shortcode')?->isDisabled()` guard

## Providers secundarios

- [ ] `EventServiceProvider` registra `ShortcodeCompiled → RecordShortcodeUsage`
- [ ] `RouteServiceProvider` carga `routes/web.php` y `routes/api.php`

## Rutas web esperadas

- [ ] `setting.shortcode.index` → `GET /panel/setting/shortcodes`
- [ ] `setting.shortcode.reference` → `GET /panel/setting/shortcodes/reference`
- [ ] `setting.shortcode.tester` → `GET /panel/setting/shortcodes/tester`

## Rutas API esperadas

Prefix `/api/shortcodes`, name `api.shortcode.*`, middleware `auth:sanctum` + `throttle:shortcode-api`:

- [ ] `api.shortcode.compile` (POST)
- [ ] `api.shortcode.strip` (POST)
- [ ] `api.shortcode.list` (GET)
- [ ] `api.shortcode.registered` (GET)
- [ ] `api.shortcode.check` (POST)
- [ ] `api.shortcode.clear-cache` (POST)
- [ ] `api.shortcode.stats` (GET)
- [ ] `api.shortcode.stats.reset` (POST)

## Comandos artisan

- [ ] `shortcode:list`, `shortcode:clear`, `shortcode:compile`
- [ ] `shortcode:preview <name>`, `shortcode:find <path>`, `shortcode:make <name>`
- [ ] `shortcode:benchmark`, `shortcode:validate`

## Permisos Spatie

- [ ] `shortcode.view` creado (guard `web`)
- [ ] `shortcode.manage` creado (guard `web`)
- [ ] Asignados al rol `admin` por `ShortcodePermissionsSeeder`

## Config

- [ ] `config('shortcode.enabled')` accesible y truthy por defecto
- [ ] `config('shortcode.cache_duration')` numérico (>0)
- [ ] `config('shortcode.max_nesting_level')` >= 1
- [ ] `config('shortcode.error_handling')` ∈ [silent, log, display, throw]

## Singleton y facade

- [ ] `app('shortcode')` resuelve a `ShortcodeCompiler`
- [ ] Facade `Shortcode::` tiene acceso a los 17 métodos documentados
- [ ] Helpers globales `shortcode()`, `strip_shortcodes()`, `register_shortcode()`, `has_shortcode()`, `all_shortcodes()`, `all_shortcodes_registered()`, `shortcode_atts()`, `do_shortcode()`

## NavService

- [ ] Módulo registra `settings` sidebar con 3 items (Listado, Referencia, Tester)

## Seeders

- [ ] `ShortcodeDatabaseSeeder` llama a `ShortcodePermissionsSeeder`
- [ ] `php artisan module:seed Shortcode` completa sin errores

## Rápido de verificar

```bash
# Rutas
php artisan route:list --name=shortcode
php artisan route:list --name=api.shortcode

# Comandos
php artisan list | grep shortcode

# Tests
./vendor/bin/phpunit modules/Shortcode/tests/Unit

# Config
php artisan tinker --execute="dump(config('shortcode'));"

# Smoke test
php artisan shortcode:preview button
php artisan shortcode:compile '[alert]Hola[/alert]'
```

## Problemas conocidos

- **Feature tests con `RefreshDatabase`** fallan por bug preexistente del proyecto (migration del módulo Menu). Afecta a todos los módulos con `RefreshDatabase`, no es específico de Shortcode.
- **Shortcodes del módulo Template** (alert, button, etc. almacenados en BD) pueden sobrescribir los defaults de este módulo si se registran después. Comportamiento esperado: último en registrar gana.
