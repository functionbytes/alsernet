<?php

namespace Modules\HelpdeskLivechat\Services\Catalog\Drivers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\HelpdeskLivechat\Services\Catalog\CatalogProduct;
use Modules\HelpdeskLivechat\Services\Catalog\Contracts\CatalogDriver;

/**
 * Driver de catálogo basado en un product feed remoto (JSON), el mismo modelo
 * que Oct8ne usa para tiendas sin integración en vivo: se descarga el feed cada
 * X y se sirve búsqueda/recuperación desde una copia cacheada. Cubre el 80% de
 * la funcionalidad (buscar y mostrar productos) sin acoplarse a la API de cada
 * CMS; add-to-cart / stock en vivo quedan para drivers específicos.
 *
 * Formato del feed: un array JSON de objetos producto (ver CatalogProduct::fromArray).
 */
final class FeedCatalogDriver implements CatalogDriver
{
    private const CACHE_TTL_SECONDS = 3600;

    /**
     * Stopwords (ES/EN) que no deben puntuar: sin esto, "busco zapatillas para
     * correr" hace match con cualquier producto cuya descripción contenga "para"
     * y contamina las recomendaciones.
     *
     * @var array<string, true>
     */
    private const STOPWORDS = [
        'para' => true, 'por' => true, 'con' => true, 'sin' => true, 'los' => true,
        'las' => true, 'una' => true, 'unos' => true, 'unas' => true, 'del' => true,
        'que' => true, 'busco' => true, 'buscar' => true, 'quiero' => true, 'necesito' => true,
        'hola' => true, 'gustaria' => true, 'gustaría' => true, 'algun' => true, 'algún' => true,
        'alguna' => true, 'teneis' => true, 'tenéis' => true, 'hay' => true, 'ver' => true,
        'the' => true, 'for' => true, 'with' => true, 'and' => true, 'looking' => true,
        'need' => true, 'want' => true, 'some' => true, 'any' => true, 'have' => true,
    ];

    /**
     * Stopwords compartidas con otros drivers (p. ej. PrestashopCatalogDriver).
     *
     * @return array<string, true>
     */
    public static function stopwords(): array
    {
        return self::STOPWORDS;
    }

    public function __construct(
        private readonly string $feedUrl,
        private readonly int $cacheTtl = self::CACHE_TTL_SECONDS,
    ) {}

    public function search(string $query, int $limit = 6): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $terms = $this->tokenize($query);
        if ($terms === []) {
            return [];
        }

        $scored = [];
        foreach ($this->products() as $product) {
            $score = $this->score($product, $terms);
            if ($score > 0) {
                $scored[] = ['score' => $score, 'product' => $product];
            }
        }

        // Orden estable: mayor relevancia primero; a igualdad, disponibles antes.
        usort($scored, function ($a, $b) {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }

            return $b['product']->available <=> $a['product']->available;
        });

        return array_map(
            static fn (array $row): CatalogProduct => $row['product'],
            array_slice($scored, 0, max(1, $limit))
        );
    }

    public function find(string $id): ?CatalogProduct
    {
        foreach ($this->products() as $product) {
            if ($product->id === $id) {
                return $product;
            }
        }

        return null;
    }

    public function related(string $id, int $limit = 4): array
    {
        $seed = $this->find($id);
        if (! $seed) {
            return [];
        }

        // Relacionados = productos que comparten términos con el título del seed,
        // excluyendo el propio. Aproximación por feed; un driver de CMS podría
        // usar la relación "productos relacionados" nativa.
        $terms = $this->tokenize($seed->title);

        $scored = [];
        foreach ($this->products() as $product) {
            if ($product->id === $id) {
                continue;
            }
            $score = $this->score($product, $terms);
            if ($score > 0) {
                $scored[] = ['score' => $score, 'product' => $product];
            }
        }

        usort($scored, static fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_map(
            static fn (array $row): CatalogProduct => $row['product'],
            array_slice($scored, 0, max(1, $limit))
        );
    }

    /**
     * @return array<int, CatalogProduct>
     */
    private function products(): array
    {
        $raw = Cache::remember(
            'helpdesklivechat:catalog:feed:'.md5($this->feedUrl),
            $this->cacheTtl,
            function (): array {
                try {
                    $response = Http::timeout(5)->acceptJson()->get($this->feedUrl);

                    if (! $response->successful()) {
                        return [];
                    }

                    $json = $response->json();

                    return is_array($json) ? $json : [];
                } catch (\Throwable $e) {
                    Log::warning('HelpdeskLivechat catalog feed fetch failed', [
                        'feed_url' => $this->feedUrl,
                        'error' => $e->getMessage(),
                    ]);

                    return [];
                }
            }
        );

        return array_values(array_filter(array_map(
            static function ($item): ?CatalogProduct {
                if (! is_array($item)) {
                    return null;
                }
                $product = CatalogProduct::fromArray($item);

                return $product->id !== '' ? $product : null;
            },
            $raw
        )));
    }

    /**
     * @param  array<int, string>  $terms
     */
    private function score(CatalogProduct $product, array $terms): int
    {
        $title = Str::lower($product->title);
        $description = Str::lower((string) $product->description);
        $idLower = Str::lower($product->id);

        $score = 0;
        foreach ($terms as $term) {
            if ($idLower === $term) {
                $score += 10; // match exacto por id (búsqueda "por product ID")
            }
            if (str_contains($title, $term)) {
                $score += 3;
            }
            if (str_contains($description, $term)) {
                $score += 1;
            }
        }

        return $score;
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $text): array
    {
        $text = Str::lower(trim($text));
        if ($text === '') {
            return [];
        }

        $parts = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // Descarta ruido/stopwords para no puntuar por "de", "la", "para", "busco"…
        // pero conserva ids numéricos cortos (búsqueda "por product ID").
        return array_values(array_unique(array_filter(
            $parts,
            static fn (string $t): bool => (mb_strlen($t) >= 2 || ctype_digit($t))
                && ! isset(self::STOPWORDS[$t])
        )));
    }
}
