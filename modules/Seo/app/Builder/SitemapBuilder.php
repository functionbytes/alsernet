<?php

namespace Modules\Seo\Builder;

use Illuminate\Support\Facades\Log;

class SitemapBuilder
{
    protected array $items = [];

    protected array $sitemaps = [];

    public function add(string $loc, ?string $lastmod = null, string $priority = '0.5', string $changefreq = 'weekly'): self
    {
        $this->items[] = [
            'loc' => $loc,
            'lastmod' => $lastmod ?? now()->toAtomString(),
            'priority' => $priority,
            'changefreq' => $changefreq,
        ];

        return $this;
    }

    public function addModel($model): self
    {
        if (method_exists($model, 'getSitemapItems')) {
            foreach ($model::getSitemapItems() as $item) {
                $this->add(
                    $item->url,
                    $item->updated_at?->toAtomString(),
                    $item->sitemap_priority ?? '0.5',
                    $item->sitemap_changefreq ?? 'weekly'
                );
            }
        }

        return $this;
    }

    public function addSitemap(string $loc, ?string $lastmod = null): self
    {
        $this->sitemaps[] = [
            'loc' => $loc,
            'lastmod' => $lastmod ?? now()->toAtomString(),
        ];

        return $this;
    }

    public function render(string $format = 'xml'): string
    {
        return view("Seo::settings.sitemap.formats.{$format}", [
            'items' => $this->items,
            'sitemaps' => $this->sitemaps,
        ])->render();
    }

    public function generate(): void
    {
        $this->generateToPath(public_path('sitemap.xml'));
    }

    /**
     * Generate the sitemap XML and write it to a custom path.
     *
     * @throws \RuntimeException If the file cannot be written.
     */
    public function generateToPath(string $path): void
    {
        $xml = $this->render();

        try {
            $result = file_put_contents($path, $xml);

            if ($result === false) {
                throw new \RuntimeException('file_put_contents returned false');
            }
        } catch (\Throwable $e) {
            Log::error('SitemapBuilder: No se pudo escribir el sitemap.', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                "No se pudo generar el sitemap.xml: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getSitemaps(): array
    {
        return $this->sitemaps;
    }

    public function clear(): self
    {
        $this->items = [];
        $this->sitemaps = [];

        return $this;
    }
}
