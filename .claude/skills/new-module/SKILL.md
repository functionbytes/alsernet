---
name: new-module
description: "Create a new Laravel module following exact project conventions (nwidart/laravel-modules v12). Generates ServiceProvider with NavService, routes, config, permissions seeder, module.json, composer.json. Registers in bootstrap/providers.php, modules_statuses.json, and root composer.json autoload. Use when creating a new module from scratch."
disable-model-invocation: true
argument-hint: "[ModuleName] [description]"
---

# Generate New Module: $ARGUMENTS

Create a complete module following the EXACT conventions of this project (nwidart/laravel-modules v12).

## MANDATORY: Read References First

Before generating ANY file, read these supporting files:

### [reference.md](reference.md) - Technical Reference (570 lines)
- All nwidart artisan commands (50+ generators)
- Facade Module:: methods and helpers
- ServiceProvider complete pattern with NavService
- Route patterns (web, api, settings, public)
- Permissions seeder pattern (Spatie)
- EventServiceProvider and RouteServiceProvider patterns
- 3 registration points (providers.php + statuses.json + composer.json)
- Feature decision matrix (when to include each component)

### [existing-modules.md](existing-modules.md) - All 40 Existing Modules
- Complete inventory table with controllers, models, migrations count
- Complexity tiers (Enterprise/Full/Medium/Light)
- NavService patterns per module (mini+sidebar vs addItemsToSection)
- Order values used (10=Core, 30=User, 45=Attention, 55=Blog)
- Sub-providers pattern (which modules use Route/Event/Schedule providers)

### [templates.md](templates.md) - 15 Code Templates (real project code)
- ServiceProvider completo con NavService
- Controller base (index con vista)
- Settings Controller (index + update con Setting model)
- Model base (fillable, casts, SoftDeletes)
- Form Request (authorize + rules)
- Routes web.php (main + settings)
- Config, Permissions Seeder, Database Seeder
- Blade views (index con tabla + settings form)
- module.json, composer.json, package.json, phpunit.xml

Use the inventory to find the MOST SIMILAR existing module.
Use the templates to generate files with EXACT project patterns.
Use the reference for nwidart commands and registration points.

## Step 0: Parse Arguments

- **ModuleName** (PascalCase): first word (e.g., `Inventory`)
- **description**: remaining words (e.g., "Gestion de inventario")
- **alias**: lowercase ModuleName (e.g., `inventory`)
- **namespace**: `Modules\{ModuleName}`

## Step 1: Create Directory Structure

```
modules/{ModuleName}/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   ├── Providers/
│   │   └── {ModuleName}ServiceProvider.php
│   └── Services/
├── config/
│   └── config.php
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
│       └── {ModuleName}PermissionsSeeder.php
├── resources/
│   └── views/
├── routes/
│   └── web.php
├── tests/
│   ├── Feature/
│   └── Unit/
├── composer.json
├── module.json
└── package.json
```

## Step 2: Generate Core Files

### 2.1 module.json
```json
{
    "name": "{ModuleName}",
    "alias": "{alias}",
    "description": "{description}",
    "keywords": [],
    "priority": 0,
    "providers": [
        "Modules\\{ModuleName}\\Providers\\{ModuleName}ServiceProvider"
    ],
    "aliases": {},
    "files": [],
    "requires": []
}
```

### 2.2 composer.json (module-level)
```json
{
    "name": "modules/{alias}",
    "description": "{description}",
    "authors": [
        {
            "name": "Alsernet Development",
            "email": "dev@alsernet.com"
        }
    ],
    "extra": {
        "laravel": {
            "providers": [],
            "aliases": {}
        }
    },
    "autoload": {
        "psr-4": {
            "Modules\\{ModuleName}\\": "app/",
            "Modules\\{ModuleName}\\Database\\Factories\\": "database/factories/",
            "Modules\\{ModuleName}\\Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Modules\\{ModuleName}\\Tests\\": "tests/"
        }
    }
}
```

### 2.3 package.json
```json
{
    "private": true,
    "type": "module",
    "scripts": {
        "dev": "vite",
        "build": "vite build"
    }
}
```

### 2.4 config/config.php
```php
<?php

return [
    'name' => '{ModuleName}',
];
```

## Step 3: ServiceProvider (CRITICAL - exact project pattern)

File: `app/Providers/{ModuleName}ServiceProvider.php`

Must include:
1. `Module::find()->isDisabled()` check in boot() as FIRST line
2. `registerConfig()` - publishes + merges config
3. `registerViews()` - loads from module path with namespace `{alias}`
4. `loadMigrationsFrom()` - auto-discovers migrations
5. `registerRoutes()` - loads web.php (and api.php if needed)
6. `registerMenus()` - NavService mini item + sidebar + settings sidebar
7. `getPublishableViewPaths()` - helper for view loading

Use these imports:
```php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Facades\Module;
use Modules\Theme\Services\NavService;
```

NavService registration pattern:
```php
// Mini-nav icon (left sidebar)
NavService::registerMiniItem('{alias}', [
    'icon' => 'fas fa-{appropriate-icon}',
    'tooltip' => '{description}',
    'sidebar_id' => '{alias}',
    'order' => 50,
]);

// Main sidebar
NavService::registerSidebar('{alias}', [
    'title' => '{description}',
    'items' => [
        ['label' => 'Dashboard', 'route' => '{alias}.index'],
    ],
]);

// Settings sidebar section
NavService::registerSidebar('settings', [
    'title' => '{ModuleName}',
    'items' => [
        ['label' => 'Configuracion general', 'route' => 'settings.{alias}.index'],
    ],
]);
```

## Step 4: Routes

File: `routes/web.php`

```php
<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {

    // Main module routes
    Route::prefix('panel/{alias}')
        ->name('{alias}.')
        ->group(function () {
            // Add routes here
        });

    // Settings routes (admin)
    Route::prefix('panel/settings/{alias}')
        ->name('settings.{alias}.')
        ->group(function () {
            // Add settings routes here
        });
});
```

## Step 5: Permissions Seeder

File: `database/seeders/{ModuleName}PermissionsSeeder.php`

Convention: `{alias}.action` (e.g., `inventory.view`, `inventory.create`)

Standard permissions to create:
- `{alias}.view` - Ver
- `{alias}.create` - Crear
- `{alias}.update` - Actualizar
- `{alias}.delete` - Eliminar
- `{alias}.manage` - Gestionar completamente

Use `Permission::firstOrCreate()` pattern with `guard_name => 'web'`.
Reset permission cache with `app()[PermissionRegistrar::class]->forgetCachedPermissions()`.

## Step 6: Register Module in 3 Places (CRITICAL)

### 6.1 bootstrap/providers.php

Read the file. Find the `$allProviders` array. Add this line in ALPHABETICAL order among the other module entries:

```php
'Modules\\{ModuleName}\\Providers\\{ModuleName}ServiceProvider' => '{ModuleName}',
```

The value `'{ModuleName}'` maps to the key in modules_statuses.json. If the value is `true` instead of a string, it means "always load" (only for critical modules like Core, Auth, Role, Theme).

### 6.2 modules_statuses.json

Read the file. Add in ALPHABETICAL order:
```json
"{ModuleName}": true
```

### 6.3 Root composer.json autoload

Read `composer.json` in project root. Find the `autoload.psr-4` section. Add in ALPHABETICAL order among the other `Modules\\*` entries:

```json
"Modules\\{ModuleName}\\": "modules/{ModuleName}/app/",
"Modules\\{ModuleName}\\Database\\Factories\\": "modules/{ModuleName}/database/factories/",
"Modules\\{ModuleName}\\Database\\Seeders\\": "modules/{ModuleName}/database/seeders/"
```

### 6.4 Run composer dump-autoload

```bash
composer dump-autoload
```

## Step 7: Verify

1. `php artisan module:list` - module appears and is enabled
2. `php artisan route:list --name={alias}` - routes registered
3. `vendor/bin/pint --dirty` - format all new PHP files
4. Check that NavService menu items appear (may need page refresh)

## CRITICAL Rules

- **Namespace**: `Modules\{ModuleName}\...` (NOT `App\...`)
- **Views namespace**: `{alias}::view.name` in controllers (`return view('{alias}::index')`)
- **Module check**: ALWAYS `Module::find('{ModuleName}')?->isDisabled()` as first line of boot()
- **Route prefix**: `panel/{alias}` for main, `panel/settings/{alias}` for settings
- **Route names**: `{alias}.action` for main, `settings.{alias}.action` for settings
- **Permissions**: `{alias}.action` naming (inventory.view, inventory.create...)
- **NavService permissions**: Mini items filtered by `modules.view.{alias}` permission
- **Icons**: Font Awesome 6 ONLY (fas/far/fab fa-*)
- **Config access**: `config('{alias}.key')`, NEVER `env()` outside config files
- **3 registration points**: bootstrap/providers.php + modules_statuses.json + root composer.json
- **Migrations**: Auto-discovered via `loadMigrationsFrom()` in ServiceProvider
