# Plan Agent Memory

## Key Architecture Notes

### Module Registration
- `bootstrap/providers.php` — controls which module service providers are loaded based on `modules_statuses.json`
- `modules_statuses.json` — key: module name, value: bool (true=active, false=inactive)
- New modules MUST be added to both files manually or via artisan

### Settings Pattern (Core)
- Central settings table: `settings` (key/value, unique key)
- Model: `Modules\Core\Models\Setting` — use `Setting::get(prefix.key, $default)`, `Setting::set(prefix.key, value)`, `Setting::clearPrefixCache('prefix.')`
- Settings are cached 10 minutes per key. Always call `clearPrefixCache` after bulk update.
- Prefix convention: `module_name.field_name` e.g. `cookie.enabled`, `cache.admin_menu`
- NO dedicated migration needed — all modules write to the central `settings` table
- The `cookie_settings` table is a remnant/parallel approach — NOT the standard; the standard is `settings`

### Module File Structure (minimal settings module pattern)
```
modules/ModuleName/
  app/
    Http/Controllers/Settings/ModuleNameController.php  ← extends Controller
    Http/Requests/Settings/ModuleNameRequest.php        ← FormRequest
    Providers/ModuleNameServiceProvider.php
    Providers/RouteServiceProvider.php
  config/
    general.php
    permissions.php
  resources/views/settings/index.blade.php
  routes/
    web.php
    api.php
  module.json
  composer.json
```

### Route Registration Pattern
- Settings routes use prefix `settings/module-name`, name prefix `settings.module-name.`
- Middleware: `['web', 'auth']`
- Route registration done in ServiceProvider `registerRoutes()`, NOT in RouteServiceProvider

### NavService Registration
```php
NavService::registerSidebar('settings', [
    'title' => 'Section Title',
    'items' => [
        ['label' => 'Label', 'route' => 'settings.module-name.index'],
    ],
]);
```
- `sidebar_id = 'settings'` groups it under the admin settings sidebar

### View Pattern
- Extend: `@extends('layouts.theme')`
- Include alerts: `@include('core::components.alerts')`
- Include card header: `@include('core::components.card', ['title' => 'Title'])`
- Checkboxes: Bootstrap `form-check form-switch`, value=1, PHP: `$request->has('field') ? '1' : '0'`
- Save button: `<button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Guardar cambios</button>`

### Permission Pattern
```php
// config/permissions.php
[
    'name' => 'Human Name',
    'flag' => 'ModuleName.index',
    'parent_flag' => 'core.index',
    'is_feature' => true,
],
```

### Module JSON
```json
{"name":"ModuleName","alias":"ModuleName","description":"...","keywords":[],"priority":0,"active":1,"providers":["Modules\\ModuleName\\Providers\\ModuleNameServiceProvider"],"files":[]}
```

## Links to Detailed Notes
- See `patterns.md` for full code templates
