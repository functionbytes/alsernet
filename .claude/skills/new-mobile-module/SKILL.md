---
name: new-mobile-module
description: Add Mobile API support to an existing module following the F0+ Mobile API conventions. Creates the manifest, audience middleware, controllers/resources/requests structure, routes file (api-mobile.php), and registers the module in MobileModuleRegistry.
---

# Skill: new-mobile-module

Use this skill to expose an EXISTING module to the Flutter mobile API following the same pattern as the Ecommerce module.

## Cuándo usar

- Cuando el usuario pide añadir endpoints móviles a un módulo (ej: "expón el módulo Helpdesk a la app móvil para los técnicos")
- Cuando hay que crear una nueva audiencia (cliente, técnico, repartidor) para un módulo

## Prerrequisitos

- F0 ya implementado (`app/Http/Api/V1/*` y la capa común existe)
- El módulo target ya existe en `modules/{ModuleName}/`
- El usuario ha confirmado:
  - Nombre del módulo
  - Audiencia (`customer`, `technician`, `driver`, etc.)
  - Modelo Authenticatable que representa esa audiencia (ej. `Customer`, `User`, `Technician`)
  - Lista de endpoints a crear

## Convenciones obligatorias

1. **Estructura de carpetas**:
   ```
   modules/{Module}/app/Http/Controllers/Api/V1/{Audience}/
       SomeController.php
   modules/{Module}/app/Http/Resources/Api/V1/
       SomeResource.php
   modules/{Module}/app/Http/Requests/Api/V1/{Group}/
       StoreSomeRequest.php
   modules/{Module}/app/Mobile/
       {Module}MobileManifest.php
   modules/{Module}/routes/api-mobile.php
   ```

2. **Routes**:
   - Prefix: `api/v1/{module-alias}/...`
   - Name pattern: `api.v1.{alias}.{action}`
   - Cargado desde `RouteServiceProvider::mapApiMobileRoutes()` con `prefix('api/v1')` + `middleware('api')`
   - Audience middleware: `Route::middleware(['auth:sanctum', '{audience}'])->group(...)` donde `{audience}` es un alias registrado en `bootstrap/app.php`

3. **Controllers**: extends `App\Http\Api\V1\BaseApiController`, usar `$this->ok()`, `$this->paginated()`, `$this->errorResponse()`.

4. **Resources**: extends `App\Http\Api\V1\BaseResource`, usar `$this->iso($date)`, `$this->mediaUrl($path)`, camelCase keys.

5. **Form Requests**: extends `App\Http\Api\V1\BaseApiRequest`, `authorize()` obligatorio (puede ser `return $this->user() !== null`).

6. **Manifest**: implementa `App\Http\Api\V1\Manifest\MobileModuleManifest`. Registrarlo en `{Module}ServiceProvider::boot()`:
   ```php
   $this->app->make(MobileModuleRegistry::class)->register(new {Module}MobileManifest());
   ```

## Pasos

1. **Lee el plan**: `docs/plans/mobile-api-architecture.md` para contexto.

2. **Lee la docs**: `docs/mobile-api/README.md` para entender el patrón.

3. **Verifica modelo de audiencia**: el modelo (`Customer`, `User`, etc.) debe usar `HasApiTokens` y un guard registrado en `config/auth.php` y `modules/Auth/config/sanctum.php`.

4. **Crea archivos en orden**:
   - Manifest en `app/Mobile/{Module}MobileManifest.php`
   - Resources en `app/Http/Resources/Api/V1/`
   - Form Requests en `app/Http/Requests/Api/V1/{Group}/`
   - Controllers en `app/Http/Controllers/Api/V1/{Audience}/`
   - Audience middleware si la audiencia es nueva (`app/Http/Middleware/EnsureIs{Audience}.php` y registrar alias en `bootstrap/app.php`)
   - Routes en `routes/api-mobile.php`

5. **Modifica `RouteServiceProvider`**: añade `mapApiMobileRoutes()` que carga `routes/api-mobile.php`:
   ```php
   protected function mapApiMobileRoutes(): void
   {
       Route::prefix('api/v1')
           ->middleware('api')
           ->group(module_path('{Module}', 'routes/api-mobile.php'));
   }
   ```
   Y llamarlo desde `map()`.

6. **Modifica `{Module}ServiceProvider::boot()`**: registra el manifest:
   ```php
   $this->registerMobileManifest();
   ```
   Y el método:
   ```php
   protected function registerMobileManifest(): void
   {
       $this->app->make(\App\Http\Api\V1\Manifest\MobileModuleRegistry::class)
           ->register(new \Modules\{Module}\Mobile\{Module}MobileManifest);
   }
   ```

7. **Crea tests** en `modules/{Module}/tests/Feature/Api/V1/{Audience}/` siguiendo el patrón de Ecommerce:
   - RegisterDB con `RefreshDatabase`
   - `Sanctum::actingAs($user, ['*'])` para auth
   - Validar formato `{success, message, data}`
   - Cubrir IDOR si el endpoint accede a recursos del usuario

8. **Ejecuta**:
   ```bash
   composer dump-autoload
   php artisan optimize:clear
   vendor/bin/pint --dirty
   php artisan test --compact modules/{Module}/tests/Feature/Api/V1/
   ```

## Stub: Manifest

```php
<?php

namespace Modules\{Module}\Mobile;

use App\Http\Api\V1\Manifest\MobileModuleManifest;

class {Module}MobileManifest implements MobileModuleManifest
{
    public function alias(): string
    {
        return '{module-alias}';
    }

    public function name(): string
    {
        return '{Display Name}';
    }

    public function version(): string
    {
        return 'v1';
    }

    public function audiences(): array
    {
        return ['{audience}']; // customer | technician | driver | etc.
    }

    public function endpoints(): array
    {
        return [
            'index' => '/api/v1/{alias}/items',
            // ...
        ];
    }

    public function requiresAbilities(): array
    {
        return [];
    }

    public function featureFlags(): array
    {
        return [];
    }
}
```

## Stub: Audience middleware (si la audiencia es nueva)

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIs{Audience}
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof \Modules\{Module}\Models\{AudienceModel}) {
            return response()->json([
                'success' => false,
                'message' => 'Token no autorizado para esta API.',
                'code' => 'AUDIENCE_MISMATCH',
            ], 403);
        }

        return $next($request);
    }
}
```

Registrar alias en `bootstrap/app.php`:
```php
$middleware->alias([
    // ...
    '{audience}' => EnsureIs{Audience}::class,
]);
```

## Stub: routes/api-mobile.php

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\{Module}\Http\Controllers\Api\V1\{Audience}\SomeController;

Route::middleware(['accept-language', 'throttle:api-mobile'])
    ->prefix('{alias}')
    ->name('api.v1.{alias}.')
    ->group(function () {
        // Public
        Route::get('public-stuff', [SomeController::class, 'publicAction'])->name('public-stuff');
    });

Route::middleware(['accept-language', 'auth:sanctum', '{audience}', 'throttle:api-mobile'])
    ->prefix('{alias}')
    ->name('api.v1.{alias}.')
    ->group(function () {
        // Authenticated
        Route::apiResource('items', SomeController::class);
    });
```

## NO hacer

- No usar `DB::` directamente — `Model::query()` siempre.
- No saltar `authorize()` en Form Requests.
- No exponer columnas sensibles (passwords, tokens, secret_keys) en Resources.
- No duplicar lógica de negocio — reutilizar Services existentes del módulo.
- No mezclar audiencias en el mismo grupo de rutas (un grupo por audiencia).
- No olvidar el middleware de audiencia — sin él, cualquier token entra.
