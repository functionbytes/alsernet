<?php

namespace Modules\Queue\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Queue\Console\Commands\QueueListCommand;
use Modules\Queue\Console\Commands\QueuePurgeCommand;
use Modules\Queue\Console\Commands\QueueRetryAllFailedCommand;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;
use Nwidart\Modules\Traits\PathNamespace;

class QueueServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Queue';

    protected string $nameLower = 'queue';

    public function boot(): void
    {
        if (Module::find('Queue')?->isDisabled()) {
            return;
        }

        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerConfig();
        $this->registerViews();
        $this->registerNavigation();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerCommands(): void
    {
        $this->commands([
            QueueRetryAllFailedCommand::class,
            QueuePurgeCommand::class,
            QueueListCommand::class,
        ]);
    }

    protected function registerCommandSchedules(): void
    {
        // No scheduled jobs needed for this module
    }

    protected function registerNavigation(): void
    {
        NavService::addSidebarItems('settings', [
            ['label' => 'Gestión de colas', 'route' => 'settings.queue.dashboard'],
        ]);
    }

    public function registerConfig(): void
    {
        // Load queue_module.php under the key 'queue_module' to avoid overwriting
        // the main app config/queue.php (which would happen if we used 'queue' as key).
        $filePath = module_path($this->name, 'config/queue_module.php');

        if (! file_exists($filePath)) {
            return;
        }

        $this->publishes([$filePath => config_path('queue_module.php')], 'config');
        $this->merge_config_from($filePath, 'queue_module');
    }

    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        if (is_array($module_config)) {
            config([$key => array_replace_recursive($existing, $module_config)]);
        }
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
