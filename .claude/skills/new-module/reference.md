# Referencia Completa - nwidart/laravel-modules + Patrones del Proyecto

Read this ENTIRE file before generating any module.

---

## 1. NWIDART/LARAVEL-MODULES v12

### Crear modulo
```bash
php artisan module:make Blog           # Modulo con recursos
php artisan module:make Blog -p        # Modulo vacio (plain)
php artisan module:make Blog User Auth # Multiples
```

### Comandos generadores
```bash
php artisan module:make-controller PostController Blog
php artisan module:make-model Post Blog --fillable=title,body -m
php artisan module:make-migration create_posts_table Blog
php artisan module:make-seed PostSeeder Blog
php artisan module:make-factory PostFactory Blog
php artisan module:make-request StorePostRequest Blog
php artisan module:make-event PostCreated Blog
php artisan module:make-listener SendNotification Blog --event=PostCreated --queued
php artisan module:make-job ProcessPost Blog
php artisan module:make-mail WelcomeMail Blog
php artisan module:make-notification NewPostNotification Blog
php artisan module:make-policy PostPolicy Blog
php artisan module:make-middleware CheckPermission Blog
php artisan module:make-resource PostResource Blog --collection
php artisan module:make-test PostTest Blog
php artisan module:make-observer PostObserver Blog
php artisan module:make-service PostService Blog
php artisan module:make-provider BlogServiceProvider Blog
php artisan module:make-command DailyReport Blog
php artisan module:make-enum PostStatus Blog
php artisan module:make-trait HasSlug Blog
php artisan module:make-scope ActiveScope Blog
php artisan module:make-rule UniqueSlug Blog
php artisan module:route-provider Blog
php artisan module:make-event-provider Blog
```

### Gestion
```bash
php artisan module:list
php artisan module:enable Blog
php artisan module:disable Blog
php artisan module:delete Blog
php artisan module:migrate Blog
php artisan module:migrate-rollback Blog
php artisan module:migrate:status Blog
php artisan module:seed Blog
php artisan module:seed Blog --class=PermissionsSeeder
```

### Facade Module::
```php
use Nwidart\Modules\Facades\Module;

Module::all();                          // Todos
Module::allEnabled();                   // Habilitados
Module::allDisabled();                  // Deshabilitados
Module::find('Blog');                   // Buscar
Module::find('Blog')->enable();         // Habilitar
Module::find('Blog')->disable();        // Deshabilitar
Module::find('Blog')->isEnabled();      // Check
Module::find('Blog')->isDisabled();     // Check
Module::find('Blog')->getName();        // "Blog"
Module::find('Blog')->getLowerName();   // "blog"
Module::find('Blog')->getPath();        // /path/to/modules/Blog
Module::has('Blog');                    // Existe?
Module::count();                        // Total
module_path('Blog');                    // Helper: ruta del modulo
module_path('Blog', 'routes/web.php'); // Helper: ruta a archivo
```

### Namespaces automaticos
```php
view('blog::index');                    // Vista
view('blog::settings.categories.index');
config('blog.name');                    // Config
config('blog.settings.option');
trans('blog::messages.welcome');        // Traduccion
```

### module.json schema
```json
{
    "name": "Blog",           // PascalCase (requerido)
    "alias": "blog",          // lowercase (requerido)
    "description": "...",     // Descripcion
    "keywords": [],           // Palabras clave
    "priority": 0,            // Orden de carga (mayor = antes)
    "providers": [             // ServiceProviders (requerido)
        "Modules\\Blog\\Providers\\BlogServiceProvider"
    ],
    "aliases": {},             // Facade aliases
    "files": [],               // Archivos adicionales
    "requires": []             // Dependencias de otros modulos
}
```

---

## 2. CONFIG/MODULES.PHP DEL PROYECTO

```php
'namespace' => 'modules',                           // Namespace base
'paths.modules' => base_path('modules'),             // Directorio
'paths.assets' => public_path('modules'),            // Assets publicos
'paths.app_folder' => 'app/',                        // Carpeta app
'auto-discover.migrations' => true,                  // SI auto-descubre
'auto-discover.translations' => false,               // NO auto-descubre
'stubs.enabled' => false,                            // Stubs custom OFF
'activator' => 'file',                               // FileActivator
'activators.file.statuses-file' => 'modules_statuses.json',
```

Generators habilitados: provider, route-provider, controller, config, factory, migration, seeder, views, routes, test-feature, test-unit

---

## 3. TRES PUNTOS DE REGISTRO OBLIGATORIOS

### 3.1 modules_statuses.json
```json
"{ModuleName}": true
```

### 3.2 bootstrap/providers.php
Agregar al array `$allProviders` en orden ALFABETICO:
```php
'Modules\\{ModuleName}\\Providers\\{ModuleName}ServiceProvider' => '{ModuleName}',
```
- Valor string = filtra por modules_statuses.json
- Valor `true` = siempre carga (SOLO criticos: Core, Auth, Role, Theme, Modules)

### 3.3 Root composer.json autoload.psr-4
```json
"Modules\\{ModuleName}\\": "modules/{ModuleName}/app/",
"Modules\\{ModuleName}\\Database\\Factories\\": "modules/{ModuleName}/database/factories/",
"Modules\\{ModuleName}\\Database\\Seeders\\": "modules/{ModuleName}/database/seeders/"
```
Luego: `composer dump-autoload`

---

## 4. SERVICEPROVIDER - PATRON COMPLETO DEL PROYECTO

```php
<?php

namespace Modules\{ModuleName}\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Facades\Module;
use Modules\Theme\Services\NavService;

class {ModuleName}ServiceProvider extends ServiceProvider
{
    protected string $moduleName = '{ModuleName}';
    protected string $moduleNameLower = '{alias}';

    public function boot(): void
    {
        // PRIMERA LINEA SIEMPRE: check disabled
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRoutes();
        $this->registerMenus();
    }

    public function register(): void
    {
        // Sub-providers opcionales:
        // $this->app->register(RouteServiceProvider::class);
        // $this->app->register(EventServiceProvider::class);
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') =>
                config_path($this->moduleNameLower . '.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower
        );
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(
            array_merge($this->getPublishableViewPaths(), [$sourcePath]),
            $this->moduleNameLower
        );
    }

    protected function registerRoutes(): void
    {
        Route::middleware('web')
            ->group(module_path($this->moduleName, 'routes/web.php'));
    }

    protected function registerMenus(): void
    {
        NavService::registerMiniItem($this->moduleNameLower, [
            'icon' => 'fas fa-{icon}',
            'tooltip' => '{description}',
            'sidebar_id' => $this->moduleNameLower,
            'order' => 50,
        ]);

        NavService::registerSidebar($this->moduleNameLower, [
            'title' => '{description}',
            'items' => [
                ['label' => 'Dashboard', 'route' => $this->moduleNameLower . '.index'],
            ],
        ]);

        NavService::registerSidebar('settings', [
            'title' => $this->moduleName,
            'items' => [
                ['label' => 'Configuracion general', 'route' => 'settings.' . $this->moduleNameLower . '.index'],
            ],
        ]);
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }

    public function provides(): array
    {
        return [];
    }
}
```

---

## 5. NAVSERVICE (Theme module)

Archivo: `modules/Theme/app/Services/NavService.php`

### registerMiniItem(moduleId, config)
Icono en el mini-nav izquierdo:
```php
NavService::registerMiniItem('blog', [
    'icon' => 'fas fa-blog',           // Font Awesome 6 ONLY
    'tooltip' => 'Blog',               // Hover tooltip
    'sidebar_id' => 'blog',            // Conecta con sidebar
    'order' => 55,                      // Posicion (10=Core, 30=User, 45=Attention, 55=Blog)
]);
```

### registerSidebar(sidebarId, config)
Menu lateral desplegable:
```php
NavService::registerSidebar('blog', [
    'title' => 'Blog',
    'items' => [
        ['label' => 'Posts', 'route' => 'blog.posts.index'],
        ['label' => 'Categorias', 'route' => 'blog.categories.index'],
    ],
]);
```

### addItemsToSection(sidebarId, sectionTitle, items)
Agregar items a seccion existente (settings):
```php
NavService::addItemsToSection('settings', 'Blog', [
    ['label' => 'Configuracion', 'route' => 'settings.blog.index'],
]);
```

### Permisos en items
```php
['label' => 'Admin', 'route' => 'blog.admin', 'permission' => 'blog.manage'],
['label' => 'Admin', 'route' => 'blog.admin', 'permission' => 'blog.manage|blog.view'],
```
NavService filtra automaticamente por `modules.view.{moduleId}`.

---

## 6. RUTAS - PATRONES DEL PROYECTO

### Web autenticadas (patron principal)
```php
Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('panel/{alias}')
        ->name('{alias}.')
        ->group(function () {
            Route::get('/', [Controller::class, 'index'])->name('index');
        });
});
```

### Settings (admin)
```php
Route::middleware(['web', 'auth'])
    ->prefix('panel/settings/{alias}')
    ->name('settings.{alias}.')
    ->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::put('/', [SettingsController::class, 'update'])->name('update');
    });
```

### API (Sanctum)
```php
Route::middleware(['api', 'auth:sanctum'])
    ->prefix('api/{alias}')
    ->name('api.{alias}.')
    ->group(function () {
        Route::apiResource('posts', PostController::class);
    });
```

### Publicas (sin auth, con throttle)
```php
Route::middleware(['web', 'throttle:30,1'])
    ->prefix('public-path')
    ->name('{alias}.public.')
    ->group(function () {
        Route::get('/', [PublicController::class, 'form'])->name('form');
    });
```

### Role-based
```php
Route::middleware(['web', 'auth', 'role:super-admin'])->group(...);
```

---

## 7. PERMISOS - PATRON SEEDER

```php
<?php

namespace Modules\{ModuleName}\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class {ModuleName}PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            [
                'name' => '{alias}.view',
                'description' => 'Ver {alias}',
                'guard_name' => 'web',
            ],
            [
                'name' => '{alias}.create',
                'description' => 'Crear {alias}',
                'guard_name' => 'web',
            ],
            [
                'name' => '{alias}.update',
                'description' => 'Actualizar {alias}',
                'guard_name' => 'web',
            ],
            [
                'name' => '{alias}.delete',
                'description' => 'Eliminar {alias}',
                'guard_name' => 'web',
            ],
            [
                'name' => '{alias}.manage',
                'description' => 'Gestionar {alias} completamente',
                'guard_name' => 'web',
            ],
            [
                'name' => '{alias}.settings.view',
                'description' => 'Ver configuracion de {alias}',
                'guard_name' => 'web',
            ],
            [
                'name' => '{alias}.settings.update',
                'description' => 'Editar configuracion de {alias}',
                'guard_name' => 'web',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                $permission
            );
        }
    }
}
```

Convencion: `{alias}.action` o `{alias}.scope.action`

---

## 8. EVENTSERVICEPROVIDER (OPCIONAL)

Solo si el modulo tiene eventos:

```php
<?php

namespace Modules\{ModuleName}\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Nwidart\Modules\Facades\Module;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \Modules\{ModuleName}\Events\EntityCreated::class => [
            \Modules\{ModuleName}\Listeners\SendNotification::class,
        ],
    ];

    public function boot(): void
    {
        if (Module::find('{ModuleName}')?->isDisabled()) {
            return;
        }
        parent::boot();
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
```

Registrar en ServiceProvider principal:
```php
public function register(): void
{
    $this->app->register(EventServiceProvider::class);
}
```

---

## 9. ROUTESERVICEPROVIDER (OPCIONAL)

Solo si el modulo tiene web + api routes:

```php
<?php

namespace Modules\{ModuleName}\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function map(): void
    {
        $this->mapWebRoutes();
        $this->mapApiRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->group(module_path('{ModuleName}', 'routes/web.php'));
    }

    protected function mapApiRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(module_path('{ModuleName}', 'routes/api.php'));
    }
}
```

---

## 10. MODULOS MIDDLEWARE

### EnsureModuleIsActive (global, automatico)
- Se ejecuta en TODA request web
- Extrae nombre de modulo de la ruta
- Retorna 404 si modulo esta deshabilitado
- NO necesita registro manual para nuevos modulos

### module:ModuleName (por ruta, opcional)
```php
Route::middleware(['web', 'module:Blog'])->group(...);
```

---

## 11. COMPOSER.JSON DEL MODULO

```json
{
    "name": "modules/{alias}",
    "description": "{description}",
    "authors": [
        {"name": "Alsernet Development", "email": "dev@alsernet.com"}
    ],
    "extra": {
        "laravel": {"providers": [], "aliases": {}}
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

---

## 12. FEATURE DECISION MATRIX

| Necesita el modulo... | Incluir |
|---|---|
| Web routes solamente | registerRoutes() inline en ServiceProvider |
| Web + API routes | RouteServiceProvider separado con map() |
| Eventos/Listeners | EventServiceProvider separado |
| Side-effects en models | Model Observers en boot() |
| Tareas programadas | registerSchedule() con callAfterResolving(Schedule) |
| Blade components | registerBladeComponents() |
| Custom middleware | registerMiddleware() con Router::aliasMiddleware() |
| Facade | $this->app->singleton() + AliasLoader en register() |
| Roles especificos | Middleware 'role:super-admin' en rutas |
| Permisos | PermissionsSeeder (casi siempre) |
| Tests | tests/Feature/ + tests/Unit/ + phpunit.xml |
