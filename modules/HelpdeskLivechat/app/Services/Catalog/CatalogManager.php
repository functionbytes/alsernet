<?php

namespace Modules\HelpdeskLivechat\Services\Catalog;

use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskLivechat\Models\Channels\Web;
use Modules\HelpdeskLivechat\Services\Catalog\Contracts\CatalogDriver;
use Modules\HelpdeskLivechat\Services\Catalog\Drivers\BridgeCatalogDriver;
use Modules\HelpdeskLivechat\Services\Catalog\Drivers\FeedCatalogDriver;
use Modules\HelpdeskLivechat\Services\Catalog\Drivers\NullCatalogDriver;
use Modules\HelpdeskLivechat\Services\Catalog\Drivers\PrestashopCatalogDriver;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

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
    public function forWeb(?Web $web, ?string $lang = null): CatalogDriver
    {
        if (! $web) {
            return new NullCatalogDriver;
        }

        // Catálogo EN VIVO por CMS: si el canal es PrestaShop y hay conexión
        // configurada (Setting livechat.catalog.prestashop, JSON), se lee el
        // catálogo real de la tienda directamente (modelo connector de Oct8ne).
        if (($web->cms_type ?? null) === 'prestashop') {
            // Preferido: la API firmada del bridge (búsqueda y visibilidad de
            // la propia tienda). La BD directa queda como alternativa.
            if ($this->bridgeConfigured()) {
                $lang = $lang !== null ? strtolower(substr($lang, 0, 2)) : null;

                return new BridgeCatalogDriver(app(PrestashopContextService::class), 'EUR', $lang ?: null);
            }

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

    private function bridgeConfigured(): bool
    {
        return class_exists(PrestashopContextService::class)
            && (string) config('helpdeskprestashop.api_url', '') !== ''
            && (string) config('helpdeskprestashop.webhook_secret', '') !== '';
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
