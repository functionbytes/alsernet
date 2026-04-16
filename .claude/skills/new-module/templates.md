# Templates Base - Codigo Real del Proyecto

Estos templates vienen de modulos REALES. Usar como base exacta al generar archivos.

---

## 1. ServiceProvider Completo

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
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRoutes();
        $this->registerMenus();
    }

    public function register(): void {}

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
                ['label' => 'Listado', 'route' => $this->moduleNameLower . '.index'],
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

## 2. Controller Base (index con vista)

```php
<?php

namespace Modules\{ModuleName}\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class {ModuleName}Controller extends Controller
{
    public function index(Request $request): View
    {
        $pageTitle = '{description}';
        $breadcrumb = '{ModuleName}';

        return view('{alias}::index', compact('pageTitle', 'breadcrumb'));
    }
}
```

---

## 3. Settings Controller (index + update)

```php
<?php

namespace Modules\{ModuleName}\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\{ModuleName}\Http\Requests\Update{ModuleName}SettingsRequest;
use Modules\Core\Models\Setting;

class {ModuleName}SettingsController extends Controller
{
    private const PREFIX = '{alias}.';

    public function __construct()
    {
        $this->middleware('can:{ModuleName}.settings.index')->only('index');
        $this->middleware('can:{ModuleName}.settings.update')->only('update');
    }

    public function index(): View
    {
        $get = fn (string $key, mixed $default = '') => Setting::get(self::PREFIX . $key, $default);

        return view('{alias}::settings.index', [
            'get' => $get,
        ]);
    }

    public function update(Update{ModuleName}SettingsRequest $request): RedirectResponse
    {
        $data = $request->safe()->all();

        foreach ($data as $key => $value) {
            Setting::set(self::PREFIX . $key, $value ?? '');
        }

        Setting::clearPrefixCache(self::PREFIX);

        return redirect()
            ->back()
            ->with('success', 'Configuracion actualizada correctamente.');
    }
}
```

---

## 4. Model Base

```php
<?php

namespace Modules\{ModuleName}\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class {EntityName} extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
```

---

## 5. Form Request

```php
<?php

namespace Modules\{ModuleName}\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Store{EntityName}Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('{alias}.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }
}
```

---

## 6. Routes web.php

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\{ModuleName}\Http\Controllers\{ModuleName}Controller;
use Modules\{ModuleName}\Http\Controllers\{ModuleName}SettingsController;

Route::middleware(['web', 'auth'])->group(function () {

    // Main routes
    Route::prefix('panel/{alias}')
        ->name('{alias}.')
        ->group(function () {
            Route::get('/', [{ModuleName}Controller::class, 'index'])->name('index');
        });

    // Settings routes
    Route::prefix('panel/settings/{alias}')
        ->name('settings.{alias}.')
        ->group(function () {
            Route::get('/', [{ModuleName}SettingsController::class, 'index'])->name('index');
            Route::patch('/', [{ModuleName}SettingsController::class, 'update'])->name('update');
        });
});
```

---

## 7. Config

```php
<?php

return [
    'name' => '{ModuleName}',
];
```

---

## 8. Permissions Seeder

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
            ['{alias}.view', 'Ver {alias}'],
            ['{alias}.create', 'Crear {alias}'],
            ['{alias}.update', 'Actualizar {alias}'],
            ['{alias}.delete', 'Eliminar {alias}'],
            ['{alias}.manage', 'Gestionar {alias} completamente'],
            ['{alias}.settings.view', 'Ver configuracion de {alias}'],
            ['{alias}.settings.update', 'Editar configuracion de {alias}'],
        ];

        foreach ($permissions as [$name, $description]) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }
    }
}
```

---

## 9. Database Seeder (main)

```php
<?php

namespace Modules\{ModuleName}\Database\Seeders;

use Illuminate\Database\Seeder;

class {ModuleName}DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            {ModuleName}PermissionsSeeder::class,
        ]);
    }
}
```

---

## 10. Blade View - Index con tabla

```blade
@extends('layouts.theme')

@section('title', $pageTitle)

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">{{ $pageTitle }}</h5>
                        <p class="small mb-0 text-muted">{description}</p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                {{-- Content here --}}
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
```

---

## 11. Blade View - Settings

```blade
@extends('layouts.theme')

@section('title', 'Configuracion de {ModuleName}')

@section('content')

    @include('core::components.alerts')

    <form method="POST" action="{{ route('settings.{alias}.update') }}">
        @csrf
        @method('PATCH')

        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3 border-bottom pb-2">Configuracion general</h6>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ $get('name') }}">
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar cambios
                </button>
            </div>
        </div>
    </form>

@endsection
```

---

## 12. module.json

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

---

## 13. composer.json (modulo)

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

---

## 14. package.json

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

---

## 15. phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="../../vendor/autoload.php"
         colors="true"
         processIsolation="false"
         stopOnFailure="false"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="{ModuleName} Module Test Suite">
            <directory>./tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory suffix=".php">./app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
    </php>
</phpunit>
```
