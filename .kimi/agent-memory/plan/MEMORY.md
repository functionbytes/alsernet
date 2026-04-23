# Plan Agent Memory

## Key Architecture Facts

### Settings Storage
- Global key/value pairs stored in `settings` table (`key` unique, `value` longtext)
- Use `DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $val])` pattern
- `setting($key)` helper in `modules/System/app/Helpers/SettingsHelper.php`
- `setting()` does NOT support a default parameter - check source before using

### Active Template
- Active theme slug stored in `settings` where `key = 'template'` (currently `wowy`)
- `TemplateManager::getActiveTemplateName()` reads from settings
- Themes live in `platform/themes/{slug}/`

### Module Navigation (NavService)
- `modules/Theme/app/Services/NavService.php` — static service
- `registerSidebar('settings', [...])` adds to the settings sidebar menu
- Each module's `TemplateServiceProvider::registerMenus()` hooks into this

### Template Module Structure
- Routes under `settings/templates` prefix, name `settings.templates.*`
- Controller: `modules/Template/app/Http/Controllers/TemplateController.php`
- Service: `modules/Template/app/Services/TemplateService.php`
- Views: `modules/Template/resources/views/settings/`
- JS assets published to `public/modules/template/js/`

### Frontend Theme Layout (wowy)
- Header: `platform/themes/wowy/partials/header.blade.php`
- Has `@yield('css')` inside `<head>` for injecting CSS
- Has `@yield('content')` for page content

### Testing Pattern
- Feature tests extend `Tests\TestCase`, use `DatabaseTransactions`
- Force `config(['database.default' => 'mariadb'])` for real DB tests
- `$connectionsToTransact = ['mariadb']`
