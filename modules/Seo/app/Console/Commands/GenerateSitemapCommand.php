<?php

namespace Modules\Seo\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate sitemap.xml';

    public function handle()
    {
        $sitemap = app('sitemap');

        // Clear existing items
        $sitemap->clear();

        $this->info('🚀 Generating sitemap...');

        // Add static pages
        $sitemap->add(url('/'), now()->toAtomString(), '1.0', 'daily');

        // Add models from config
        $models = config('sitemap.models', []);

        foreach ($models as $modelClass) {
            if (class_exists($modelClass)) {
                $this->info("   Adding {$modelClass}...");
                $sitemap->addModel($modelClass);
            }
        }

        // Generate the sitemap file
        $sitemap->generate();

        $itemsCount = count($sitemap->getItems());

        $this->info('✅ Sitemap generated successfully!');
        $this->info('📄 Location: '.public_path('sitemap.xml'));
        $this->info("📊 Total URLs: {$itemsCount}");

        // Clear cache
        cache()->forget('sitemap-xml');
        $this->info('🗑️  Cache cleared');

        $this->pingSitemapToSearchEngines(url('/sitemap.xml'));

        return self::SUCCESS;
    }

    protected function pingSitemapToSearchEngines(string $sitemapUrl): void
    {
        $engines = [
            'Google' => "https://www.google.com/ping?sitemap={$sitemapUrl}",
            'Bing' => "https://www.bing.com/ping?sitemap={$sitemapUrl}",
        ];

        foreach ($engines as $name => $url) {
            try {
                Http::timeout(5)->get($url);
                $this->info("Notificado: {$name}");
            } catch (\Exception) {
                $this->warn("No se pudo notificar a {$name}");
            }
        }
    }
}
