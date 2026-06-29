<?php

namespace Modules\Seo\Console\Commands;

use Illuminate\Console\Command;
use Modules\Seo\Services\KeywordRankingService;

/**
 * Updates SERP rankings for all target_keyword meta records via the configured
 * provider (SerpAPI / ValueSERP / DataForSEO). Writes to seo_metas.gsc_position.
 *
 * Usage:
 *   php artisan seo:update-rankings
 *   php artisan seo:update-rankings --limit=200
 */
class SeoUpdateRankingsCommand extends Command
{
    protected $signature = 'seo:update-rankings {--limit=50 : Max metas to process}';

    protected $description = 'Fetch current SERP ranking for each meta with a target_keyword';

    public function handle(KeywordRankingService $service): int
    {
        $provider = (string) seo_setting('serp.provider', config('Seo.serp.provider', ''));

        if ($provider === '') {
            $this->warn('No SERP provider configured. Set SEO_SERP_PROVIDER and SEO_SERP_API_KEY (or edit in /panel/setting/seo/settings).');

            return self::SUCCESS;
        }

        $this->info("Updating rankings via {$provider}...");
        $updated = $service->updateAll((int) $this->option('limit'));
        $this->info("Posiciones actualizadas: {$updated}");

        return self::SUCCESS;
    }
}
