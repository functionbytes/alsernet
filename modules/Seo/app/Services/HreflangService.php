<?php

namespace Modules\Seo\Services;

use Modules\Locales\Models\Locale;
use Modules\Seo\Models\SeoMeta;

/**
 * Genera los `<link rel="alternate" hreflang="...">` para una URL
 * cruzando las traducciones en SeoMeta con los locales activos en el
 * módulo Locales (fuente de verdad).
 *
 * Docs: https://developers.google.com/search/docs/specialized/international/localized-versions
 */
class HreflangService
{
    /**
     * Construye los tags hreflang para la URL canónica indicada.
     *
     * Lógica:
     *  1. Localiza la SeoMeta por canonical_url.
     *  2. Resuelve el grupo de traducciones (misma seoable_id+type).
     *  3. Para cada traducción con locale activo en `locales`, emite un
     *     link con su `canonical_url` propio.
     *  4. Añade `x-default` apuntando al locale default si existe.
     *
     * @return array<int, array{hreflang: string, href: string}>
     */
    public function buildFor(string $canonicalUrl): array
    {
        $meta = SeoMeta::query()->where('canonical_url', $canonicalUrl)->first();

        if (! $meta || ! $meta->seoable_id || ! $meta->seoable_type) {
            return [];
        }

        $activeCodes = Locale::active()->pluck('code')->all();

        if (empty($activeCodes)) {
            return [];
        }

        $siblings = SeoMeta::query()
            ->where('seoable_id', $meta->seoable_id)
            ->where('seoable_type', $meta->seoable_type)
            ->whereNotNull('canonical_url')
            ->whereNotNull('locale')
            ->whereIn('locale', $activeCodes)
            ->get(['locale', 'canonical_url']);

        if ($siblings->isEmpty()) {
            return [];
        }

        $tags = [];
        $seen = [];

        foreach ($siblings as $sibling) {
            $locale = (string) $sibling->locale;
            if ($locale === '' || isset($seen[$locale])) {
                continue;
            }

            $tags[] = [
                'hreflang' => $locale,
                'href' => (string) $sibling->canonical_url,
            ];
            $seen[$locale] = true;
        }

        $default = Locale::active()->firstWhere('is_default', true);
        if ($default && isset($seen[$default->code])) {
            $tags[] = [
                'hreflang' => 'x-default',
                'href' => (string) $siblings->firstWhere('locale', $default->code)->canonical_url,
            ];
        }

        return $tags;
    }

    /**
     * Renderiza directamente las etiquetas HTML listas para incrustar en <head>.
     */
    public function render(string $canonicalUrl): string
    {
        $lines = [];

        foreach ($this->buildFor($canonicalUrl) as $tag) {
            $lines[] = sprintf(
                '<link rel="alternate" hreflang="%s" href="%s">',
                e($tag['hreflang']),
                e($tag['href'])
            );
        }

        return implode("\n", $lines);
    }
}
