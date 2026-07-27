<?php

namespace Modules\HelpdeskPrestashop\Listeners;

use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * A price drop or a restock changes catalog data, so the cached PrestaShop
 * categories become stale. Bumps the catalog cache version so the next read
 * refetches. Listens to PsPriceDropped and PsBackInStock.
 */
class InvalidateCatalogCache
{
    public function __construct(
        private readonly PrestashopContextService $prestashop
    ) {}

    public function handle(object $event): void
    {
        $this->prestashop->forgetCatalogCache();
    }
}
