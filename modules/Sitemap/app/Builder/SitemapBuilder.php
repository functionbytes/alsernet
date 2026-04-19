<?php

namespace Modules\Sitemap\Builder;

use Modules\Sitemap\Helpers\SitemapHelper;
use OverflowException;

class SitemapBuilder
{
    protected array $items = [];

    protected array $sitemaps = [];

    protected array $alternates = [];

    private array $seenUrls = [];

    public function add(string $loc, ?string $lastmod = null, string $priority = '0.5', string $changefreq = 'weekly'): self
    {
        if (isset($this->seenUrls[$loc])) {
            return $this;
        }

        $maxItems = SitemapHelper::getItemLimit();

        if (count($this->items) >= $maxItems) {
            throw new OverflowException("Sitemap exceeded the {$maxItems} URL limit. Use a sitemap index instead.");
        }

        $this->seenUrls[$loc] = true;
        $this->items[] = [
            'loc' => $loc,
            'lastmod' => $lastmod,
            'priority' => SitemapHelper::formatPriority(
                SitemapHelper::isValidPriority($priority) ? $priority : '0.5'
            ),
            'changefreq' => SitemapHelper::isValidChangeFrequency($changefreq) ? $changefreq : 'weekly',
        ];

        return $this;
    }

    public function addModel(string $model): self
    {
        if (method_exists($model, 'chunkSitemapItems')) {
            $model::chunkSitemapItems(500, function ($item) {
                $this->add(
                    $item->url,
                    $item->updated_at?->toAtomString(),
                    $item->sitemap_priority ?? '0.8',
                    $item->sitemap_changefreq ?? 'weekly'
                );
            });
        } elseif (method_exists($model, 'getSitemapItems')) {
            foreach ($model::getSitemapItems() as $item) {
                $this->add(
                    $item->url,
                    $item->updated_at?->toAtomString(),
                    $item->sitemap_priority ?? '0.8',
                    $item->sitemap_changefreq ?? 'weekly'
                );
            }
        }

        return $this;
    }

    public function addAlternate(string $loc, string $hreflang, string $href): self
    {
        $this->alternates[$loc][] = ['hreflang' => $hreflang, 'href' => $href];

        return $this;
    }

    public function getAlternates(): array
    {
        return $this->alternates;
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
            'alternates' => $this->alternates,
        ])->render();
    }

    public function generate(): void
    {
        file_put_contents(public_path('sitemap.xml'), $this->render());
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
        $this->alternates = [];
        $this->seenUrls = [];

        return $this;
    }
}
