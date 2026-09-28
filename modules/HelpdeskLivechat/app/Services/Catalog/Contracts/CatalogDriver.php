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
     * Busca productos con filtros adicionales (marca, categoría, rango de
     * precio, stock, orden) — la versión que usa la herramienta de búsqueda
     * de producto del bot cuando el visitante da criterios concretos ("botas
     * Chiruca por menos de 150€"). Los drivers sin motor de filtros propio
     * (feed, PrestaShop directo, null) filtran localmente el resultado de
     * search() y nunca relajan filtros (relaxed siempre vacío, engine null);
     * solo BridgeCatalogDriver delega el filtrado/relajación al bridge.
     *
     * @param  array{brand?:string,category?:string,price_min?:float,price_max?:float,in_stock?:bool,sort?:string}  $filters
     * @return array{products: array<int, CatalogProduct>, relaxed: array<int,string>, engine: ?string}
     */
    public function searchWithFilters(string $query, int $limit, array $filters = []): array;

    /**
     * Recupera un producto por su id de catálogo, o null si no existe.
     */
    public function find(string $id): ?CatalogProduct;

    /**
     * Recupera varios productos de una vez (p. ej. al compartir un carrusel de
     * hasta N ids desde el panel). Evita que el llamador tenga que hacer N
     * llamadas a find(); cada driver decide cómo resolverlo de forma eficiente
     * (índice en memoria, una única query con whereIn, etc.).
     *
     * @param  array<int, string>  $ids
     * @return array<string, CatalogProduct> indexado por id, solo los encontrados
     */
    public function findMany(array $ids): array;

    /**
     * Productos relacionados con uno dado (cross/upsell), como máximo $limit.
     *
     * @return array<int, CatalogProduct>
     */
    public function related(string $id, int $limit = 4): array;
}
