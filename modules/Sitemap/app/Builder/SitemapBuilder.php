<?php

namespace Modules\Sitemap\Builder;

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
        return view("sitemap::formats.{$format}", [
            'items' => $this->items,
            'sitemaps' => $this->sitemaps,
        ])->render();
    }

    public function generate(): void
    {
        $xml = $this->render();
        file_put_contents(public_path('sitemap.xml'), $xml);
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
