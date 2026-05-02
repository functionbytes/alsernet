<?php

namespace Modules\Remarketing\Connectors;

use Modules\Remarketing\Connectors\PrestaShop\PrestaShopConnector;
use Modules\Remarketing\Connectors\Shopify\ShopifyConnector;
use Modules\Remarketing\Connectors\WooCommerce\WooCommerceConnector;
use Modules\Remarketing\Contracts\EcommerceConnector;
use Modules\Remarketing\Models\Store;

class ConnectorRegistry
{
    /** @var array<string, class-string<EcommerceConnector>> */
    protected array $connectors = [
        'shopify' => ShopifyConnector::class,
        'woocommerce' => WooCommerceConnector::class,
        'prestashop' => PrestaShopConnector::class,
    ];

    public function for(Store $store): EcommerceConnector
    {
        $class = $this->connectors[$store->platform] ?? null;

        if (! $class) {
            throw new \InvalidArgumentException("No connector for platform: {$store->platform}");
        }

        return app($class)->bind($store);
    }
}
