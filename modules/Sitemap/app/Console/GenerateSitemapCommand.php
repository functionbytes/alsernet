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

    public function handle(): int
    {
        $start = hrtime(true);
        $source = $this->option('source');

        try {
            $builder = new SitemapBuilder;

            $this->info('Generating sitemap...');

            $builder->add(url('/'), null, '1.0', 'daily');

            foreach (config('sitemap.static_urls', []) as $entry) {
                $builder->add(
                    url($entry['loc']),
                    null,
                    $entry['priority'] ?? '0.5',
                    $entry['changefreq'] ?? 'weekly'
                );
            }

            foreach (config('sitemap.models', []) as $modelClass) {
                if (! class_exists($modelClass)) {
                    $this->warn("  Skipping {$modelClass} (class not found)");

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
