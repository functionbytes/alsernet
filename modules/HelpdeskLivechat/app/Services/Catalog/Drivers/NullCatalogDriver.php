<?php

namespace Modules\HelpdeskLivechat\Services\Catalog\Drivers;

use Modules\HelpdeskLivechat\Services\Catalog\CatalogProduct;
use Modules\HelpdeskLivechat\Services\Catalog\Contracts\CatalogDriver;

/**
 * Driver nulo para canales sin catálogo configurado (cms_type=custom y sin feed).
 * Nunca lanza: devuelve resultados vacíos para que el bot y el agente degraden
 * con elegancia (sin recomendaciones) en vez de romper la conversación.
 */
final class NullCatalogDriver implements CatalogDriver
{
    public function search(string $query, int $limit = 6): array
    {
        return [];
    }

    public function find(string $id): ?CatalogProduct
    {
        return null;
    }

    public function related(string $id, int $limit = 4): array
    {
        return [];
    }
}
