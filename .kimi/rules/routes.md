# Rule: routes

> **Applies to:** "modules/*/routes/**/*.php,routes/**/*.php"


# Route Files Rules

## Archivos de rutas por modulo

```
modules/{Module}/routes/
├── web.php       # Rutas web (navegacion, forms, vistas)
├── api.php       # Rutas API REST (Sanctum, JSON)
└── settings.php  # OPCIONAL - solo algunos modulos separan rutas de admin
```

## Patron `routes/web.php` (estandar del proyecto)

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\{ModuleName}\Http\Controllers\{ModuleName}Controller;
use Modules\{ModuleName}\Http\Controllers\{ModuleName}SettingsController;

Route::middleware(['web', 'auth'])->group(function () {

    // Rutas principales del modulo
    Route::prefix('panel/{alias}')
        ->name('{alias}.')
        ->group(function () {
            Route::get('/', [{ModuleName}Controller::class, 'index'])->name('index');
            Route::get('/create', [{ModuleName}Controller::class, 'create'])->name('create');
            Route::post('/', [{ModuleName}Controller::class, 'store'])->name('store');
            Route::get('/{id}/edit', [{ModuleName}Controller::class, 'edit'])->name('edit');
            Route::put('/{id}', [{ModuleName}Controller::class, 'update'])->name('update');
            Route::delete('/{id}', [{ModuleName}Controller::class, 'destroy'])->name('destroy');
            Route::post('/bulk-action', [{ModuleName}Controller::class, 'bulkAction'])->name('bulk-action');
        });

    // Rutas de settings/admin (prefix+name diferentes)
    Route::prefix('panel/settings/{alias}')
        ->name('settings.{alias}.')
        ->group(function () {
            Route::get('/', [{ModuleName}SettingsController::class, 'index'])->name('index');
            Route::patch('/', [{ModuleName}SettingsController::class, 'update'])->name('update');
        });
});
```

## Patron `routes/api.php` (REST API)

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\{ModuleName}\Http\Controllers\Api\{EntityName}ApiController;

// Publicas con throttle
Route::middleware(['api', 'throttle:60,1'])
    ->prefix('{alias}/public')
    ->name('api.{alias}.public.')
    ->group(function () {
        Route::post('/submit', [PublicController::class, 'submit'])->name('submit');
    });

// Autenticadas con Sanctum
Route::middleware(['api', 'auth:sanctum'])
    ->prefix('{alias}')
    ->name('api.{alias}.')
    ->group(function () {
        Route::apiResource('{entity-kebab}', {EntityName}ApiController::class);
    });
```

## Patron de rutas publicas (sin auth, con throttle)

```php
Route::middleware(['web', 'throttle:30,1'])
    ->prefix('ruta-publica')
    ->name('{alias}.public.')
    ->group(function () {
        Route::get('/', [PublicController::class, 'form'])->name('form');
        Route::post('/', [PublicController::class, 'submit'])->name('submit')->middleware('throttle:10,1');
    });
```

## Reglas criticas

- **Prefix main**: SIEMPRE `panel/{alias}`
- **Prefix settings**: SIEMPRE `panel/settings/{alias}`
- **Prefix API**: SIEMPRE `api/{alias}` con `auth:sanctum`
- **Name main**: SIEMPRE `{alias}.` (ej: `attention.index`)
- **Name settings**: SIEMPRE `settings.{alias}.` (ej: `settings.attention.index`)
- **Name API**: SIEMPRE `api.{alias}.` (ej: `api.attention.index`)
- **Middleware main**: `['web', 'auth']`
- **Middleware settings**: `['web', 'auth']` + opcional `settings` middleware
- **Middleware public**: `['web', 'throttle:30,1']` (rate limit obligatorio)
- **Middleware API publica**: `['api', 'throttle:60,1']`
- **Middleware API privada**: `['api', 'auth:sanctum']`

## HTTP methods correctos

| Accion | Method | Nombre ruta |
|--------|--------|-------------|
| Listar | `GET` | `index` |
| Formulario crear | `GET` | `create` |
| Guardar nuevo | `POST` | `store` |
| Ver detalle | `GET` | `show` |
| Formulario editar | `GET` | `edit` |
| Actualizar | `PUT` | `update` |
| Actualizar parcial (settings) | `PATCH` | `update` |
| Eliminar | `DELETE` | `destroy` |
| Bulk action | `POST` | `bulk-action` |
| Exportar | `GET` | `export` |

## Nested resources pattern

```php
Route::prefix('{parent}/{parent_id}/{child}')->name('{parent}.{child}.')->group(function () {
    Route::get('/', [ChildController::class, 'index'])->name('index');
    Route::post('/', [ChildController::class, 'store'])->name('store');
    Route::put('/{id}', [ChildController::class, 'update'])->name('update');
});
```

## Bulk action route

SIEMPRE incluir ruta `bulk-action` en listados:
```php
Route::post('/bulk-action', [Controller::class, 'bulkAction'])->name('bulk-action');
```

Espera JSON payload:
```json
{ "action": "delete|activate|...", "ids": [1, 2, 3] }
```

## NO usar

- `Route::resource()` para rutas web (usa rutas explicitas)
- `Route::apiResource()` solo en api.php
- Closures en routes (siempre usar controller)
- Rutas fuera de grupos (siempre con middleware explicito)
- `name()` sin prefijo de modulo

## Ver tambien

- [rules/controllers.md] para patron de controller
- [rules/api-controllers.md] para API controllers
- [rules/laravel-cache-commands.md] `php artisan route:clear` despues de cambios
