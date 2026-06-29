<?php

namespace Modules\Template\Templates\Riode\Shortcodes;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Shortcode\Compiler\ShortcodeCompiler;

/**
 * Shortcodes de marketplace y social del template Riode.
 *
 * Shortcodes registrados:
 *   [instagram-feed]   — Galería Instagram (manual/API) en carousel, grid o masonry
 *   [subcategory-card] — Tarjeta de categoría con icono + lista de subcategorías
 *   [category-column]  — Layout en columnas de categorías (parent)
 *   [category-box]     — Columna individual de categorías (child de [category-column])
 *   [vendor-card]      — Tarjeta de vendedor para marketplace multi-vendor
 *
 * NOTA: [vendor-card] requiere un módulo Marketplace/Vendor. Este proyecto NO tiene
 * dicho módulo actualmente. El shortcode se registra pero renderiza con datos del
 * atributo únicamente (sin consulta a BD).
 * TODO: Acoplar a módulo Vendor cuando esté disponible.
 */
class RiodeMarketplaceShortcodes
{
    public function __construct(private readonly ShortcodeCompiler $compiler) {}

    public function registerAll(): void
    {
        $this->registerInstagramFeed();
        $this->registerSubcategoryCard();
        $this->registerCategoryColumn();
        $this->registerCategoryBox();
        $this->registerVendorCard();
    }

    // -------------------------------------------------------------------------
    // [instagram-feed layout="grid" cols="5" limit="12" images='[...]']
    // -------------------------------------------------------------------------

    protected function registerInstagramFeed(): void
    {
        $this->compiler->register('instagram-feed', function (array $attrs, string $content): string {
            $source = $attrs['source'] ?? 'manual';
            $limit = max(1, min(30, (int) ($attrs['limit'] ?? 12)));

            $items = $source === 'api'
                ? $this->fetchInstagramItems($limit)
                : $this->parseManualImages($attrs, $limit);

            if ($items === null) {
                if ($source === 'manual') {
                    return '';
                }

                return view('riode::shortcodes.instagram-feed', [
                    'attrs' => $attrs,
                    'items' => [],
                    'placeholder' => true,
                ])->render();
            }

            if (empty($items)) {
                return '';
            }

            return view('riode::shortcodes.instagram-feed', [
                'attrs' => $attrs,
                'items' => $items,
                'placeholder' => false,
            ])->render();
        }, [
            'description' => 'Feed de Instagram en carousel, grid o masonry. Soporta imágenes manuales (JSON) o API (si configurada). Self-closing.',
            'example' => '[instagram-feed layout="grid" cols="5" limit="12" images=\'[{"url":"/img/1.jpg","link":"https://instagram.com/p/abc","alt":"Foto"}]\']',
            'attributes' => [
                'layout' => 'Disposición visual: carousel | grid | masonry (default: carousel)',
                'cols' => 'Columnas 2–6 (default: 5)',
                'source' => 'Fuente: manual | api (default: manual)',
                'images' => 'JSON array con los items: [{"url":"...","link":"...","alt":"..."}] (modo manual)',
                'limit' => 'Máximo de publicaciones 1–30 (default: 12)',
                'autoplay' => 'Solo carousel: true | false (default: true)',
                'show-info' => 'Overlay con info al hover: true | false (default: false)',
                'gutter' => 'Espaciado: none | xs | sm | default (default: default)',
            ],
            'cacheable' => false,
        ]);
    }

    /**
     * Fetch media from the Instagram Graph API, cached for 1 hour.
     *
     * Returns null when the token is missing (triggers placeholder),
     * or an empty array when the API call fails gracefully.
     *
     * @return array<int, array{url: string, link: string, alt: string}>|null
     */
    protected function fetchInstagramItems(int $limit): ?array
    {
        $token = config('services.instagram.access_token');

        if (empty($token)) {
            return null;
        }

        return Cache::remember("riode_instagram_feed:{$limit}", 3600, function () use ($token, $limit): array {
            try {
                $response = Http::timeout(8)->get('https://graph.instagram.com/me/media', [
                    'fields' => 'id,media_url,permalink,caption,media_type',
                    'access_token' => $token,
                    'limit' => $limit,
                ]);

                if (! $response->successful()) {
                    Log::warning('[instagram-feed] API returned non-2xx', ['status' => $response->status()]);

                    return [];
                }

                return collect($response->json('data', []))
                    ->filter(fn (array $item) => in_array($item['media_type'] ?? '', ['IMAGE', 'CAROUSEL_ALBUM'], true))
                    ->take($limit)
                    ->map(fn (array $item) => [
                        'url' => $item['media_url'] ?? '',
                        'link' => $item['permalink'] ?? '#',
                        'alt' => mb_substr($item['caption'] ?? 'Instagram', 0, 100),
                    ])
                    ->values()
                    ->all();
            } catch (\Throwable $e) {
                Log::warning('[instagram-feed] API failed: '.$e->getMessage());

                return [];
            }
        });
    }

    /**
     * Parse the manual JSON `images` attribute.
     *
     * Returns null when no `images` attr is provided (triggers placeholder),
     * or a sliced array of items.
     *
     * @return array<int, array<string, string>>|null
     */
    protected function parseManualImages(array $attrs, int $limit): ?array
    {
        if (empty($attrs['images'])) {
            return null;
        }

        $decoded = json_decode($attrs['images'], true);

        if (! is_array($decoded)) {
            return null;
        }

        return array_slice($decoded, 0, $limit);
    }

    // -------------------------------------------------------------------------
    // [subcategory-card name="Ventanas" href="/ventanas" icon="fas fa-window-maximize"
    //     bg-color="#4b5577" subcategories='[{"label":"PVC","url":"/pvc"}]']
    // -------------------------------------------------------------------------

    protected function registerSubcategoryCard(): void
    {
        $this->compiler->register('subcategory-card', function (array $attrs, string $content): string {
            if (empty($attrs['name']) || empty($attrs['href']) || empty($attrs['icon'])) {
                return '';
            }

            $subsRaw = $attrs['subcategories'] ?? '[]';
            $subcategories = json_decode($subsRaw, true);
            $subcategories = is_array($subcategories) ? array_slice($subcategories, 0, 8) : [];

            return view('riode::shortcodes.subcategory-card', array_merge(
                compact('attrs', 'subcategories')
            ))->render();
        }, [
            'description' => 'Tarjeta de categoría con fondo de color, icono Font Awesome 6 y lista de subcategorías hijas. Self-closing.',
            'example' => '[subcategory-card name="Ventanas" href="/catalogo/ventanas" icon="fas fa-window-maximize" bg-color="#4b5577" subcategories=\'[{"label":"PVC","url":"/pvc"},{"label":"Aluminio","url":"/aluminio"}]\']',
            'attributes' => [
                'name' => 'Nombre de la categoría — requerido',
                'href' => 'URL del enlace principal — requerido',
                'icon' => 'Clase Font Awesome 6 (ej: fas fa-window-maximize) — requerido',
                'bg-color' => 'Color de fondo hex (default: #4b5577)',
                'text-color' => 'Color del texto: light | dark (default: light)',
                'subcategories' => 'JSON array de hijos: [{"label":"Nombre","url":"/ruta"}] (máx. 8)',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [category-column cols="4" gap="default"]...[category-box ...][/category-column]
    // -------------------------------------------------------------------------

    protected function registerCategoryColumn(): void
    {
        $this->compiler->register('category-column', function (array $attrs, string $content): string {
            if (trim($content) === '') {
                return '';
            }

            $content = $this->compiler->compile($content);

            return view('riode::shortcodes.category-column', compact('attrs', 'content'))->render();
        }, [
            'description' => 'Contenedor en columnas para [category-box]. Layout de directorio de categorías tipo footer rico.',
            'example' => '[category-column cols="4"][category-box title="Ventanas:" links=\'[{"label":"PVC","url":"/pvc"}]\'][/category-box][/category-column]',
            'attributes' => [
                'cols' => 'Columnas: 1–6 o auto (default: auto)',
                'gap' => 'Separación: none | sm | default | lg (default: default)',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [category-box title="Ventanas:" links='[{"label":"PVC","url":"/pvc"}]']
    // -------------------------------------------------------------------------

    protected function registerCategoryBox(): void
    {
        $this->compiler->register('category-box', function (array $attrs, string $content): string {
            if (empty($attrs['title']) || empty($attrs['links'])) {
                return '';
            }

            $links = json_decode($attrs['links'], true);
            $links = is_array($links) ? $links : [];

            $limit = isset($attrs['limit']) && (int) $attrs['limit'] > 0
                ? (int) $attrs['limit']
                : 0;

            if ($limit > 0) {
                $links = array_slice($links, 0, $limit);
            }

            if (empty($links)) {
                return '';
            }

            return view('riode::shortcodes.category-box', compact('attrs', 'links'))->render();
        }, [
            'description' => 'Columna individual de categorías para usar dentro de [category-column]. Encabezado + enlaces planos.',
            'example' => '[category-box title="Ventanas:" links=\'[{"label":"PVC","url":"/ventanas/pvc"},{"label":"Aluminio","url":"/ventanas/aluminio"}]\']',
            'attributes' => [
                'title' => 'Encabezado h6 de la columna (requerido)',
                'links' => 'JSON array: [{"label":"Nombre","url":"/ruta"}] (requerido)',
                'limit' => 'Truncar lista a N enlaces (0 = sin límite, default: 0)',
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // [vendor-card name="Carpintería López" href="/marketplace/lopez" logo="/logo.png"
    //     products-count="42" rating="90" layout="default" product-images='[...]']
    //
    // TODO: Acoplar a módulo Vendor cuando esté disponible en el proyecto.
    // Este proyecto no tiene módulo Marketplace/Multi-vendor. El shortcode renderiza
    // únicamente con los datos pasados como atributos.
    // -------------------------------------------------------------------------

    protected function registerVendorCard(): void
    {
        $this->compiler->register('vendor-card', function (array $attrs, string $content): string {
            if (empty($attrs['name']) || empty($attrs['href']) || empty($attrs['logo'])) {
                return '';
            }

            $imagesRaw = $attrs['product-images'] ?? '[]';
            $productImages = json_decode($imagesRaw, true);
            $productImages = is_array($productImages) ? array_slice($productImages, 0, 3) : [];

            return view('riode::shortcodes.vendor-card', array_merge(
                compact('attrs', 'productImages')
            ))->render();
        }, [
            'description' => 'Tarjeta de vendedor para marketplace multi-vendor. Logo, nombre, rating, recuento de productos e imágenes destacadas. Self-closing.',
            'example' => '[vendor-card name="Carpintería López" href="/marketplace/lopez" logo="/img/logo.png" products-count="42" rating="90" layout="default"]',
            'attributes' => [
                'name' => 'Nombre del vendedor (requerido)',
                'href' => 'URL a la página del vendedor (requerido)',
                'logo' => 'URL del logo 70×70 px (requerido)',
                'layout' => 'Variante visual: default | style2 | style3 (default: default)',
                'products-count' => 'Número de productos del vendedor',
                'rating' => 'Valoración 0–100 como porcentaje de barra de estrellas (default: oculto si no se pasa)',
                'product-images' => 'JSON array: [{"url":"...","href":"...","alt":"..."}] (máx. 3)',
            ],
        ]);
    }
}
