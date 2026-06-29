# ServiceProvider - Patrones del Proyecto

## Ciclo de vida

1. `register()` - Se ejecuta PRIMERO. Bindings, singletons, sub-providers
2. `boot()` - Se ejecuta DESPUES de todos los register(). Carga recursos

## Patron estandar de este proyecto

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
        // CRITICO: Verificar si el modulo esta deshabilitado
        if (Module::find($this->moduleName)?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerRoutes();
        $this->registerMenus();
        // Opcionales:
        // $this->registerPolicies();
        // $this->registerCommands();
        // $this->registerSchedule();
    }

    public function register(): void
    {
        // Opcional: registrar sub-providers
        // $this->app->register(RouteServiceProvider::class);
        // $this->app->register(EventServiceProvider::class);
    }

    protected function registerConfig(): void { /* ver abajo */ }
    protected function registerViews(): void { /* ver abajo */ }
    protected function registerRoutes(): void { /* ver abajo */ }
    protected function registerMenus(): void { /* ver abajo */ }
}
```

## registerConfig() - Patron simple
```php
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
```

## registerViews() - Patron con publishable paths
```php
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
```

## registerRoutes() - Patron inline
```php
protected function registerRoutes(): void
{
    Route::middleware('web')
        ->group(module_path($this->moduleName, 'routes/web.php'));

    // Solo si tiene API routes:
    Route::middleware('api')
        ->prefix('api')
        ->group(module_path($this->moduleName, 'routes/api.php'));
}
```

## registerMenus() - NavService
```php
protected function registerMenus(): void
{
    NavService::registerMiniItem('{alias}', [
        'icon' => 'fas fa-box',
        'tooltip' => '{description}',
        'sidebar_id' => '{alias}',
        'order' => 50,
    ]);

    NavService::registerSidebar('{alias}', [
        'title' => '{description}',
        'items' => [
            ['label' => 'Dashboard', 'route' => '{alias}.index'],
        ],
    ]);

    NavService::registerSidebar('settings', [
        'title' => '{ModuleName}',
        'items' => [
            ['label' => 'Configuracion', 'route' => 'settings.{alias}.index'],
        ],
    ]);
}
```

## Metodos opcionales

### registerPolicies()
```php
protected function registerPolicies(): void
{
    Gate::policy(Model::class, ModelPolicy::class);
}
```

### registerCommands()
```php
protected function registerCommands(): void
{
    if ($this->app->runningInConsole()) {
        $this->commands([
            MyCommand::class,
        ]);
    }
}
```

### registerSchedule()
```php
protected function registerSchedule(): void
{
    $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
        $schedule->command('module:daily-task')
            ->daily()
            ->withoutOverlapping()
            ->onOneServer();
    });
}
```

## Variaciones encontradas en el proyecto

| Aspecto | Patron A (simple) | Patron B (avanzado) |
|---------|-------------------|---------------------|
| Rutas | Inline en boot() | RouteServiceProvider separado |
| Eventos | Event::listen() inline | EventServiceProvider separado |
| Config | Un solo config.php | Directorio recursivo |
| Modelos | Observer en boot() | ObserverServiceProvider |
| Middleware | No registra | pushMiddlewareToGroup() |
| Facades | No | $this->app->singleton() + alias |

**Recomendacion**: Usar Patron A para modulos simples. Patron B cuando el modulo tiene api.php, eventos, y complejidad alta.
