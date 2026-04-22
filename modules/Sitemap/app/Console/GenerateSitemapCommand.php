<?php

namespace Modules\Sitemap\Console;

use Illuminate\Console\Command;
use Modules\Sitemap\Builder\SitemapBuilder;
use Modules\Sitemap\Models\SitemapGeneration;
use Throwable;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'sitemap:generate
                            {--ping : Ping search engines after generating}
                            {--source=command : Source identifier stored in history (command/schedule)}';

    protected $description = 'Generate sitemap.xml and optionally ping search engines';

    /**
     * Add all multilingual page URLs using SeoMeta canonical URLs with hreflang alternates.
     * Uses the production canonical_url field rather than url() helper (APP_URL) to ensure
     * the correct domain is always used regardless of the environment.
     */
    protected function addMultilingualPages(SitemapBuilder $builder): void
    {
        $seoMetaClass = 'Modules\\Seo\\Models\\SeoMeta';
        $pageClass = 'Modules\\Page\\Models\\Page';

        if (! class_exists($seoMetaClass) || ! class_exists($pageClass)) {
            return;
        }

        $this->info('  Adding multilingual pages via SeoMeta...');

        $metas = $seoMetaClass::where('seoable_type', $pageClass)
            ->whereNotNull('canonical_url')
            ->where('robots', 'not like', '%noindex%')
            ->select(['seoable_id', 'locale', 'canonical_url', 'updated_at'])
            ->orderBy('seoable_id')
            ->orderBy('locale')
            ->get();

        $grouped = $metas->groupBy('seoable_id');
        $defaultLocale = config('app.locale', 'es');

        foreach ($grouped as $pageMetas) {
            $alternatesMap = $pageMetas->pluck('canonical_url', 'locale')->all();

            if (empty($alternatesMap)) {
                continue;
            }

            foreach ($pageMetas as $meta) {
                $lastmod = $meta->updated_at?->toAtomString();
                $priority = $meta->locale === $defaultLocale ? '0.9' : '0.8';
                $builder->add($meta->canonical_url, $lastmod, $priority, 'monthly');

                foreach ($alternatesMap as $lang => $href) {
                    $builder->addAlternate($meta->canonical_url, $lang, $href);
                }
                if (isset($alternatesMap[$defaultLocale])) {
                    $builder->addAlternate($meta->canonical_url, 'x-default', $alternatesMap[$defaultLocale]);
                }
            }
        }
    }

    public function handle(): int
    {
        $start = hrtime(true);
        $source = $this->option('source');

        try {
            $builder = new SitemapBuilder;

            $this->info('Generating sitemap...');

            foreach (config('sitemap.static_urls', []) as $entry) {
                $builder->add(
                    url($entry['loc']),
                    null,
                    $entry['priority'] ?? '0.5',
                    $entry['changefreq'] ?? 'weekly'
                );
            }

            // Add multilingual pages using SeoMeta canonical URLs (correct production domain)
            $this->addMultilingualPages($builder);

            // Add non-Page models (Blog, etc.) using the model trait
            foreach (config('sitemap.models', []) as $modelClass) {
                if (! class_exists($modelClass)) {
                    $this->warn("  Skipping {$modelClass} (class not found)");

                    continue;
                }

                // Pages are handled by addMultilingualPages() above
                if ($modelClass === 'Modules\\Page\\Models\\Page') {
                    continue;
                }

                try {
                    $this->info("  Adding {$modelClass}...");
                    $builder->addModel($modelClass);
                } catch (Throwable $e) {
                    $this->error("  Failed {$modelClass}: {$e->getMessage()}");
                }
            }

            foreach (config('sitemap.post_callbacks', []) as $callback) {
                if (is_callable($callback)) {
                    $callback($builder);
                }
            }

            $builder->generate();
            cache()->forget('sitemap-xml');

            $urlCount = count($builder->getItems());
            $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);

            SitemapGeneration::create([
                'status' => 'success',
                'url_count' => $urlCount,
                'duration_ms' => $durationMs,
                'source' => $source,
            ]);

            $this->info('Generated: '.public_path('sitemap.xml'));
            $this->info("Total URLs: {$urlCount} ({$durationMs}ms)");

            if ($this->option('ping')) {
                $this->call('sitemap:ping');
            }

            try {
                if ($this->getApplication()?->has('seo:indexnow-submit')) {
                    $this->call('seo:indexnow-submit', ['--recent' => '1']);
                }
            } catch (Throwable) {
                // IndexNow is optional — never block sitemap generation
            }

            return self::SUCCESS;

        } catch (Throwable $e) {
            $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);

            SitemapGeneration::create([
                'status' => 'failed',
                'url_count' => 0,
                'duration_ms' => $durationMs,
                'error_message' => $e->getMessage(),
                'source' => $source,
            ]);

            $this->error("Sitemap generation failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
