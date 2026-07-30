<?php

namespace Modules\HelpdeskLivechat\Services\Catalog;

use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskLivechat\Services\Catalog\Contracts\CatalogDriver;
use Modules\HelpdeskLivechat\Services\Catalog\Drivers\FeedCatalogDriver;
use Modules\HelpdeskLivechat\Services\Catalog\Drivers\NullCatalogDriver;
use Modules\HelpdeskLivechat\Services\Catalog\Drivers\PrestashopCatalogDriver;

/**
 * Resuelve el driver de catálogo adecuado para un canal Web.
 *
 * Estrategia actual: si el canal tiene un product feed configurado
 * (product_feed_url), se usa el FeedCatalogDriver — cubre cualquier cms_type,
 * incluido "custom". Cuando existan drivers en vivo por CMS (PrestaShop,
 * Shopify…), este manager los seleccionará por $web->cms_type antes del feed.
 * Sin feed ni driver específico → NullCatalogDriver (degradación elegante).
 */
class CatalogManager
{
    public function forWeb(?Web $web): CatalogDriver
    {
        if (! $web) {
            return new NullCatalogDriver;
        }

        // Catálogo EN VIVO por CMS: si el canal es PrestaShop y hay conexión
        // configurada (Setting livechat.catalog.prestashop, JSON), se lee el
        // catálogo real de la tienda directamente (modelo connector de Oct8ne).
        if (($web->cms_type ?? null) === 'prestashop') {
            $psConfig = $this->prestashopConfig();
            if ($psConfig !== null) {
                return new PrestashopCatalogDriver($psConfig);
            }
        }

        // Alternativa universal: product feed (JSON) para cualquier cms_type.
        $feedUrl = trim((string) ($web->product_feed_url ?? ''));
        if ($feedUrl !== '') {
            return new FeedCatalogDriver($feedUrl);
        }

        return new NullCatalogDriver;
    }

    /**
     * Config de conexión a la tienda PrestaShop, desde el Setting
     * `livechat.catalog.prestashop` (JSON). null si no está configurada.
     *
     * @return array<string, mixed>|null
     */
    private function prestashopConfig(): ?array
    {
        $raw = Setting::get('livechat.catalog.prestashop');
        if (is_string($raw) && $raw !== '') {
            $raw = json_decode($raw, true);
        }

        return is_array($raw) && ! empty($raw['database']) ? $raw : null;
    }

    /**
     * ¿El canal tiene búsqueda de producto por bot activada y catálogo utilizable?
     */
    public function botEnabledForWeb(?Web $web): bool
    {
        if (! $web || ! (bool) ($web->enable_product_search ?? false)) {
            return false;
        }

        return ! ($this->forWeb($web) instanceof NullCatalogDriver);
    }
}
