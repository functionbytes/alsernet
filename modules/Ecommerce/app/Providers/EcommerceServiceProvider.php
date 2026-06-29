<?php

namespace Modules\Ecommerce\Providers;

use App\Http\Api\V1\Manifest\MobileModuleRegistry;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Ecommerce\Console\Commands\CancelExpiredDeletionRequests;
use Modules\Ecommerce\Console\Commands\GenerateInvoiceForOrders;
use Modules\Ecommerce\Console\Commands\NotifyPriceDropsCommand;
use Modules\Ecommerce\Console\Commands\NotifySavedSearchesCommand;
use Modules\Ecommerce\Console\Commands\ProcessExistingProductImagesCommand;
use Modules\Ecommerce\Console\Commands\ProcessSubscriptionRenewalsCommand;
use Modules\Ecommerce\Console\Commands\RefreshSitemapCommand;
use Modules\Ecommerce\Console\Commands\SendAbandonedCartsEmailCommand;
use Modules\Ecommerce\Console\Commands\SendRestockAlertsCommand;
use Modules\Ecommerce\Console\Commands\UpdateFlashSaleStatus;
use Modules\Ecommerce\Mobile\EcommerceMobileManifest;
use Modules\Ecommerce\Models\Brand;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Models\CustomerAddress;
use Modules\Ecommerce\Models\Discount;
use Modules\Ecommerce\Models\FlashSale;
use Modules\Ecommerce\Models\GlobalOption;
use Modules\Ecommerce\Models\Invoice;
use Modules\Ecommerce\Models\NewsletterSubscriber;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductAttributeSet;
use Modules\Ecommerce\Models\ProductCategory;
use Modules\Ecommerce\Models\ProductCollection;
use Modules\Ecommerce\Models\ProductLabel;
use Modules\Ecommerce\Models\ProductTag;
use Modules\Ecommerce\Models\Review;
use Modules\Ecommerce\Models\Shipment;
use Modules\Ecommerce\Models\Shipping;
use Modules\Ecommerce\Models\SpecificationAttribute;
use Modules\Ecommerce\Models\SpecificationGroup;
use Modules\Ecommerce\Models\SpecificationTable;
use Modules\Ecommerce\Models\StoreLocator;
use Modules\Ecommerce\Models\Tax;
use Modules\Ecommerce\Observers\BrandObserver;
use Modules\Ecommerce\Observers\FlashSaleObserver;
use Modules\Ecommerce\Observers\ProductCategoryObserver;
use Modules\Ecommerce\Observers\ProductImageObserver;
use Modules\Ecommerce\Observers\ProductObserver;
use Modules\Ecommerce\Policies\BrandPolicy;
use Modules\Ecommerce\Policies\CustomerAddressPolicy;
use Modules\Ecommerce\Policies\CustomerPolicy;
use Modules\Ecommerce\Policies\DiscountPolicy;
use Modules\Ecommerce\Policies\FlashSalePolicy;
use Modules\Ecommerce\Policies\GlobalOptionPolicy;
use Modules\Ecommerce\Policies\InvoicePolicy;
use Modules\Ecommerce\Policies\NewsletterSubscriberPolicy;
use Modules\Ecommerce\Policies\OrderPolicy;
use Modules\Ecommerce\Policies\ProductAttributeSetPolicy;
use Modules\Ecommerce\Policies\ProductCategoryPolicy;
use Modules\Ecommerce\Policies\ProductCollectionPolicy;
use Modules\Ecommerce\Policies\ProductLabelPolicy;
use Modules\Ecommerce\Policies\ProductPolicy;
use Modules\Ecommerce\Policies\ProductTagPolicy;
use Modules\Ecommerce\Policies\ReviewPolicy;
use Modules\Ecommerce\Policies\ShipmentPolicy;
use Modules\Ecommerce\Policies\ShippingPolicy;
use Modules\Ecommerce\Policies\SpecificationAttributePolicy;
use Modules\Ecommerce\Policies\SpecificationGroupPolicy;
use Modules\Ecommerce\Policies\SpecificationTablePolicy;
use Modules\Ecommerce\Policies\StoreLocatorPolicy;
use Modules\Ecommerce\Policies\TaxPolicy;
use Modules\Ecommerce\Services\CartService;
use Modules\Ecommerce\Services\CheckoutService;
use Modules\Ecommerce\Services\CustomerService;
use Modules\Ecommerce\Services\DiscountService;
use Modules\Ecommerce\Services\FlashSaleService;
use Modules\Ecommerce\Services\InvoiceService;
use Modules\Ecommerce\Services\OrderService;
use Modules\Ecommerce\Services\OrderStockService;
use Modules\Ecommerce\Services\ProductService;
use Modules\Ecommerce\Services\ReviewService;
use Modules\Ecommerce\Services\ShippingService;
use Modules\Ecommerce\Services\TaxService;
use Modules\Ecommerce\Services\WishlistService;
use Modules\Ecommerce\Supports\CartHelper;
use Modules\Theme\Services\NavService;
use Nwidart\Modules\Facades\Module;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class EcommerceServiceProvider extends ServiceProvider
{
    protected string $name = 'Ecommerce';

    protected string $nameLower = 'ecommerce';

    public function boot(): void
    {
        if (Module::find('Ecommerce')?->isDisabled()) {
            return;
        }

        $this->registerConfig();
        $this->registerViews();
        $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerPolicies();
        $this->registerObservers();
        $this->registerMenus();
        $this->registerMobileManifest();
        $this->app->register(EventServiceProvider::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                CancelExpiredDeletionRequests::class,
                GenerateInvoiceForOrders::class,
                NotifyPriceDropsCommand::class,
                ProcessExistingProductImagesCommand::class,
                ProcessSubscriptionRenewalsCommand::class,
                RefreshSitemapCommand::class,
                SendAbandonedCartsEmailCommand::class,
                SendRestockAlertsCommand::class,
                NotifySavedSearchesCommand::class,
                UpdateFlashSaleStatus::class,
            ]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('ecommerce:send-restock-alerts')->everyFiveMinutes();
            $schedule->command('ecommerce:notify-price-drops')->daily();
            $schedule->command('ecommerce:notify-saved-searches')->daily();
            $schedule->command('ecommerce:process-subscription-renewals')->dailyAt('03:00');
        });
    }

    public function register(): void
    {
        if (Module::find('Ecommerce')?->isDisabled()) {
            return;
        }

        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(CartHelper::class);
        $this->app->singleton(CartService::class);
        $this->app->singleton(ProductService::class);
        $this->app->singleton(OrderService::class);
        $this->app->singleton(CustomerService::class);
        $this->app->singleton(ShippingService::class);
        $this->app->singleton(TaxService::class);
        $this->app->singleton(DiscountService::class);
        $this->app->singleton(InvoiceService::class);
        $this->app->singleton(ReviewService::class);
        $this->app->singleton(FlashSaleService::class);
        $this->app->singleton(WishlistService::class);
        $this->app->singleton(CheckoutService::class);
        $this->app->singleton(OrderStockService::class);

        $helperFile = __DIR__.'/../Helpers/EcommerceHelper.php';
        if (file_exists($helperFile)) {
            require_once $helperFile;
        }
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

    protected function registerObservers(): void
    {
        ProductCategory::observe(ProductCategoryObserver::class);
        Brand::observe(BrandObserver::class);
        FlashSale::observe(FlashSaleObserver::class);
        Product::observe(ProductObserver::class);
        Product::observe(ProductImageObserver::class);
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(ProductCategory::class, ProductCategoryPolicy::class);
        Gate::policy(Brand::class, BrandPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(CustomerAddress::class, CustomerAddressPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Discount::class, DiscountPolicy::class);
        Gate::policy(FlashSale::class, FlashSalePolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(Shipment::class, ShipmentPolicy::class);
        Gate::policy(Shipping::class, ShippingPolicy::class);
        Gate::policy(StoreLocator::class, StoreLocatorPolicy::class);
        Gate::policy(Tax::class, TaxPolicy::class);
        Gate::policy(ProductCollection::class, ProductCollectionPolicy::class);
        Gate::policy(ProductTag::class, ProductTagPolicy::class);
        Gate::policy(ProductLabel::class, ProductLabelPolicy::class);
        Gate::policy(GlobalOption::class, GlobalOptionPolicy::class);
        Gate::policy(ProductAttributeSet::class, ProductAttributeSetPolicy::class);
        Gate::policy(SpecificationGroup::class, SpecificationGroupPolicy::class);
        Gate::policy(SpecificationAttribute::class, SpecificationAttributePolicy::class);
        Gate::policy(SpecificationTable::class, SpecificationTablePolicy::class);
        Gate::policy(NewsletterSubscriber::class, NewsletterSubscriberPolicy::class);
    }

    protected function registerMenus(): void
    {
        NavService::registerMiniItem('ecommerce', [
            'icon' => 'fa-duotone fa-thin fa-store',
            'tooltip' => 'Ecommerce',
            'sidebar_id' => 'ecommerce',
            'order' => 45,
        ]);

        NavService::registerSidebar('ecommerce', [
            'title' => 'Ecommerce',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'ecommerce.dashboard'],
                ['label' => 'Reportes', 'route' => 'ecommerce.reports.index'],
                ['label' => 'Ordenes', 'route' => 'ecommerce.orders.index'],
                ['label' => 'Ordenes incompletas', 'route' => 'ecommerce.incomplete-orders.index'],
                ['label' => 'Devoluciones', 'route' => 'ecommerce.returns.index'],
                ['label' => 'Envios', 'route' => 'ecommerce.shipments.index'],
                ['label' => 'Facturas', 'route' => 'ecommerce.invoices.index'],
                ['label' => 'Clientes', 'route' => 'ecommerce.customers.index'],
                ['label' => 'Productos', 'route' => 'ecommerce.products.index'],
                ['label' => 'Inventario', 'route' => 'ecommerce.inventory.index'],
                ['label' => 'Precios', 'route' => 'ecommerce.product-prices.index'],
                ['label' => 'Categorias', 'route' => 'ecommerce.categories.index'],
                ['label' => 'Marcas', 'route' => 'ecommerce.brands.index'],
                ['label' => 'Etiquetas', 'route' => 'ecommerce.tags.index'],
                ['label' => 'Etiquetas de producto', 'route' => 'ecommerce.product-labels.index'],
                ['label' => 'Colecciones', 'route' => 'ecommerce.collections.index'],
                ['label' => 'Descuentos', 'route' => 'ecommerce.discounts.index'],
                ['label' => 'Ventas Flash', 'route' => 'ecommerce.flash-sales.index'],
                ['label' => 'Resenas', 'route' => 'ecommerce.reviews.index'],
                ['label' => 'Newsletter', 'route' => 'ecommerce.newsletter.index'],
                ['label' => 'Impuestos', 'route' => 'ecommerce.taxes.index'],
                ['label' => 'Metodos de envio', 'route' => 'ecommerce.shipping.index'],
                ['label' => 'Tiendas', 'route' => 'ecommerce.store-locators.index'],
                ['label' => 'Opciones globales', 'route' => 'ecommerce.global-options.index'],
                ['label' => 'Conjuntos de atributos', 'route' => 'ecommerce.product-attribute-sets.index'],
                ['label' => 'Opciones de producto', 'route' => 'ecommerce.product-options.index'],
                ['label' => 'Grupos de especificacion', 'route' => 'ecommerce.specification-groups.index'],
                ['label' => 'Atributos de especificacion', 'route' => 'ecommerce.specification-attributes.index'],
                ['label' => 'Tablas de especificacion', 'route' => 'ecommerce.specification-tables.index'],
            ],
        ]);

        NavService::registerSidebar('settings', [
            'title' => 'Ecommerce',
            'items' => [
                ['label' => 'General', 'route' => 'settings.ecommerce.index'],
                ['label' => 'Monedas', 'route' => 'settings.ecommerce.currencies.index'],
                ['label' => 'Productos', 'route' => 'settings.ecommerce.products.index'],
                ['label' => 'Busqueda de productos', 'route' => 'settings.ecommerce.product-search.index'],
                ['label' => 'Productos digitales', 'route' => 'settings.ecommerce.digital-products.index'],
                ['label' => 'Resenas de productos', 'route' => 'settings.ecommerce.product-reviews.index'],
                ['label' => 'Compras', 'route' => 'settings.ecommerce.shopping.index'],
                ['label' => 'Checkout', 'route' => 'settings.ecommerce.checkout.index'],
                ['label' => 'Devoluciones', 'route' => 'settings.ecommerce.return.index'],
                ['label' => 'Facturas', 'route' => 'settings.ecommerce.invoices.index'],
                ['label' => 'Plantilla de factura', 'route' => 'settings.ecommerce.invoice-template.index'],
                ['label' => 'Plantilla etiqueta envio', 'route' => 'settings.ecommerce.shipping-label-template.index'],
                ['label' => 'Impuestos', 'route' => 'settings.ecommerce.taxes.index'],
                ['label' => 'Clientes', 'route' => 'settings.ecommerce.customers.index'],
                ['label' => 'Envio', 'route' => 'settings.ecommerce.shipping.index'],
                ['label' => 'Webhook', 'route' => 'settings.ecommerce.webhook.index'],
                ['label' => 'Seguimiento y analitica', 'route' => 'settings.ecommerce.tracking.index'],
                ['label' => 'Estandar y formato', 'route' => 'settings.ecommerce.standard-and-format.index'],
                ['label' => 'Venta flash', 'route' => 'settings.ecommerce.flash-sale.index'],
                ['label' => 'Carrito abandonado', 'route' => 'settings.ecommerce.abandoned-cart.index'],
                ['label' => 'Localizadores de tiendas', 'route' => 'settings.ecommerce.store-locators.index'],
            ],
        ]);
    }

    protected function registerMobileManifest(): void
    {
        $this->app->make(MobileModuleRegistry::class)
            ->register(new EcommerceMobileManifest);
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
