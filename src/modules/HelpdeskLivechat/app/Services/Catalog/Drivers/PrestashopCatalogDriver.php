<?php

namespace Modules\HelpdeskLivechat\Services\Catalog\Drivers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\HelpdeskLivechat\Services\Catalog\Contracts\CatalogDriver;
use Modules\HelpdeskLivechat\Services\Catalog\CatalogProduct;

/**
 * Driver de catálogo EN VIVO contra la base de datos de una tienda PrestaShop
 * (el modelo "connector" de Oct8ne, pero leyendo directamente el catálogo). Hace
 * búsqueda por nombre sobre product_lang y devuelve nombre, precio, imagen de
 * portada y enlace a la ficha reales, siempre frescos (sin feed intermedio).
 *
 * Config esperada (array):
 *  host, port, database, username, password, prefix, base_url, id_lang, id_shop
 */
final class PrestashopCatalogDriver implements CatalogDriver
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config) {}

    public function search(string $query, int $limit = 6): array
    {
        $terms = $this->tokenize($query);
        if ($terms === []) {
            return [];
        }

        try {
            $q = $this->baseQuery()
                ->where('ps.active', 1);

            // Todos los términos deben aparecer en el nombre (AND) — evita ruido.
            foreach ($terms as $term) {
                $q->where('pl.name', 'like', '%'.$term.'%');
            }

            $rows = $q->orderByDesc('ps.price')->limit(max(1, $limit))->get();

            return $this->mapRows($rows);
        } catch (\Throwable $e) {
            Log::warning('PrestashopCatalogDriver search failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function find(string $id): ?CatalogProduct
    {
        try {
            $row = $this->baseQuery()->where('ps.id_product', (int) $id)->first();

            return $row ? $this->mapRows(collect([$row]))[0] ?? null : null;
        } catch (\Throwable $e) {
            Log::warning('PrestashopCatalogDriver find failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function related(string $id, int $limit = 4): array
    {
        try {
            $prefix = $this->prefix();
            $shop = (int) ($this->config['id_shop'] ?? 1);

            $categoryId = DB::connection($this->connectionName())
                ->table($prefix.'product_shop')
                ->where('id_product', (int) $id)
                ->where('id_shop', $shop)
                ->value('id_category_default');

            if (! $categoryId) {
                return [];
            }

            $rows = $this->baseQuery()
                ->join($prefix.'category_product as cp', 'cp.id_product', '=', 'ps.id_product')
                ->where('cp.id_category', (int) $categoryId)
                ->where('ps.active', 1)
                ->where('ps.id_product', '!=', (int) $id)
                ->orderByDesc('ps.price')
                ->limit(max(1, $limit))
                ->get();

            return $this->mapRows($rows);
        } catch (\Throwable $e) {
            Log::warning('PrestashopCatalogDriver related failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Consulta base: product_shop + nombre + imagen de portada, por idioma/tienda.
     */
    private function baseQuery(): \Illuminate\Database\Query\Builder
    {
        $prefix = $this->prefix();
        $lang = (int) ($this->config['id_lang'] ?? 1);
        $shop = (int) ($this->config['id_shop'] ?? 1);

        return DB::connection($this->connectionName())
            ->table($prefix.'product_shop as ps')
            ->join($prefix.'product_lang as pl', function ($join) use ($lang) {
                $join->on('pl.id_product', '=', 'ps.id_product')
                    ->on('pl.id_shop', '=', 'ps.id_shop')
                    ->where('pl.id_lang', '=', $lang);
            })
            ->leftJoin($prefix.'image as img', function ($join) {
                $join->on('img.id_product', '=', 'ps.id_product')
                    ->where('img.cover', '=', 1);
            })
            ->where('ps.id_shop', $shop)
            ->select([
                'ps.id_product',
                'pl.name',
                'pl.link_rewrite',
                'ps.price',
                'img.id_image',
            ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return array<int, CatalogProduct>
     */
    private function mapRows($rows): array
    {
        $base = rtrim((string) ($this->config['base_url'] ?? ''), '/');

        return $rows->map(function ($r) use ($base): CatalogProduct {
            $image = null;
            if ($base !== '' && ! empty($r->id_image)) {
                // URL friendly de imagen de PrestaShop: {base}/{id_image}-home_default/{link_rewrite}.jpg
                $image = $base.'/'.$r->id_image.'-home_default/'.$r->link_rewrite.'.jpg';
            }

            $url = $base !== ''
                ? $base.'/index.php?controller=product&id_product='.(int) $r->id_product
                : null;

            $price = round((float) $r->price, 2);

            return new CatalogProduct(
                id: (string) $r->id_product,
                title: (string) $r->name,
                imageUrl: $image,
                url: $url,
                // El catálogo dev tiene muchos precios a 0 (importación parcial):
                // se omite el precio en ese caso en vez de mostrar "0,00 €".
                price: $price > 0 ? $price : null,
                currency: 'EUR',
                available: true,
            );
        })->all();
    }

    private function prefix(): string
    {
        return (string) ($this->config['prefix'] ?? 'ps_');
    }

    /**
     * Registra (idempotente) una conexión de solo lectura hacia la BD de la
     * tienda y devuelve su nombre. Se aísla del resto de conexiones del sistema.
     */
    private function connectionName(): string
    {
        $name = 'ps_catalog_'.substr(md5((string) ($this->config['database'] ?? '').($this->config['host'] ?? '')), 0, 8);

        if (! Config::has('database.connections.'.$name)) {
            Config::set('database.connections.'.$name, [
                'driver' => 'mysql',
                'host' => $this->config['host'] ?? '127.0.0.1',
                'port' => $this->config['port'] ?? 3306,
                'database' => $this->config['database'] ?? '',
                'username' => $this->config['username'] ?? '',
                'password' => $this->config['password'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
            ]);
        }

        return $name;
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $text): array
    {
        $text = Str::lower(trim($text));
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $stop = FeedCatalogDriver::stopwords();

        return array_values(array_unique(array_filter(
            $parts,
            static fn (string $t): bool => (mb_strlen($t) >= 3 || ctype_digit($t)) && ! isset($stop[$t])
        )));
    }
}
