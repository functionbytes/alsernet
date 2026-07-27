<?php

namespace Modules\EcommercePayment\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\EcommercePayment\Console\Commands\CleanupPendingPaymentsCommand;
use Modules\EcommercePayment\Models\Payment;
use Modules\EcommercePayment\Policies\PaymentPolicy;
use Modules\EcommercePayment\Services\BankTransferGateway;
use Modules\EcommercePayment\Services\CodGateway;
use Modules\EcommercePayment\Services\GatewayRegistry;
use Modules\EcommercePayment\Services\PaymentGatewayManager;
use Modules\EcommercePayment\Services\WompiGateway;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class EcommercePaymentServiceProvider extends ServiceProvider
{
    protected string $name = 'EcommercePayment';

    protected string $nameLower = 'ecommerce-payment';

    public function boot(): void
    {
        if (Module::find('EcommercePayment')?->isDisabled() || Module::find('Ecommerce')?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerViews();
        $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerRoutes();
        $this->app->register(EventServiceProvider::class);
        $this->registerPolicies();
        $this->registerMenus();
        $this->commands([
            CleanupPendingPaymentsCommand::class,
        ]);
    }

    /**
     * register() ya delega en Modules\EcommercePayment\Providers\RouteServiceProvider
     * via $this->app->register(RouteServiceProvider::class), pero ese sub-provider
     * dinamico nunca terminaba de bootear sus rutas dentro del ciclo normal de
     * arranque (confirmado: registrarlo a mano en un proceso ya arrancado SI
     * carga las rutas, pero durante un request real quedaban sin registrar) —
     * dejando /payment/wompi/* y el resto del modulo de pagos completamente
     * inalcanzables. Se cargan aqui directamente, igual que el resto de los
     * modulos de este proyecto.
     */
    protected function registerRoutes(): void
    {
        Route::middleware('web')
            ->namespace('Modules\EcommercePayment\Http\Controllers')
            ->group(module_path($this->name, 'routes/web.php'));

        Route::prefix('api')
            ->middleware('api')
            ->namespace('Modules\EcommercePayment\Http\Controllers\Api')
            ->group(module_path($this->name, 'routes/api.php'));
    }

    public function register(): void
    {
        if (Module::find('Ecommerce')?->isDisabled()) {
            return;
        }

        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(GatewayRegistry::class, fn () => new GatewayRegistry);

        $this->app->singleton(PaymentGatewayManager::class, function ($app) {
            $manager = new PaymentGatewayManager($app);

            // Built-in gateways — always available regardless of modules
            $manager->register('cod', CodGateway::class);
            $manager->register('bank_transfer', BankTransferGateway::class);
            $manager->register('wompi', WompiGateway::class);

            // Auto-discover gateways from installed modules
            // Any module with "type": "payment-gateway" in its module.json is registered here
            foreach ($app->make(GatewayRegistry::class)->discover() as $channel => $class) {
                $manager->register($channel, $class);
            }

            return $manager;
        });
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (! is_dir($configPath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
            $configKey = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
            $segments = explode('.', $this->nameLower.'.'.$configKey);

            $normalized = [];
            foreach ($segments as $segment) {
                if (end($normalized) !== $segment) {
                    $normalized[] = $segment;
                }
            }

            $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

            $this->publishes([$file->getPathname() => config_path($config)], 'config');
            $this->mergeConfigFrom($file->getPathname(), $key);
        }
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);
    }

    protected function registerMenus(): void
    {
        NavService::addItemsToSection('ecommerce', 'Ecommerce', [
            ['label' => 'Pagos', 'route' => 'ecommerce-payment.payments.index'],
            ['label' => 'Métodos de pago', 'route' => 'ecommerce-payment.methods.index'],
            ['label' => 'Configuracion de pagos', 'route' => 'ecommerce-payment.settings'],
        ]);
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Payment::class, PaymentPolicy::class);
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
