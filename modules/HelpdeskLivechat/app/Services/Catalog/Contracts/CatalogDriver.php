<?php

namespace Modules\HelpdeskLivechat\Services\Catalog\Contracts;

use Modules\HelpdeskLivechat\Services\Catalog\CatalogProduct;

/**
 * Contrato de acceso al catálogo de una tienda, resuelto por CMS
 * (cms_type del canal Web) — el equivalente al "connector" de Oct8ne, pero
 * detrás de la API autenticada del módulo en vez de por JSONP.
 *
 * Cada driver (feed, PrestaShop, Shopify, WooCommerce…) implementa la búsqueda
 * y recuperación de productos. Los drivers son stateless respecto a la petición
 * y deben cachear internamente el acceso al origen (feed remoto, API externa)
 * para no penalizar cada búsqueda del bot / agente.
 */
interface CatalogDriver
{
    /**
     * Busca productos por texto libre (la "pregunta" del visitante o la query
     * del agente). Devuelve como máximo $limit resultados, ya normalizados.
     *
     * @return array<int, CatalogProduct>
     */
    public function search(string $query, int $limit = 6): array;

    /**
     * Recupera un producto por su id de catálogo, o null si no existe.
     */
    public function find(string $id): ?CatalogProduct;

    /**
     * Productos relacionados con uno dado (cross/upsell), como máximo $limit.
     *
     * @return array<int, CatalogProduct>
     */
    public function related(string $id, int $limit = 4): array;
}
