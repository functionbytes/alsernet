<?php

namespace Modules\Sitemap\Console;

use Illuminate\Console\Command;
use Modules\Sitemap\Helpers\SitemapHelper;

class PingSitemapCommand extends Command
{
    protected $signature = 'sitemap:ping {--url= : Custom sitemap URL}';

    protected $description = 'Ping search engines about sitemap updates';

    public function handle(): int
    {
        $sitemapUrl = $this->option('url') ?? url('/sitemap.xml');

        $this->info("Pinging search engines for: {$sitemapUrl}");

        $results = SitemapHelper::pingSearchEngines($sitemapUrl);
        $successCount = 0;

        foreach ($results as $engine => $result) {
            $label = strtoupper($engine);

            if ($result['ok']) {
                $this->info("  {$label}: OK [{$result['status']}]");
                $successCount++;
            } elseif ($result['error']) {
                $this->warn("  {$label}: Failed (connection error: {$result['error']})");
            } else {
                $this->warn("  {$label}: Failed [{$result['status']}]");
            }
        }

        if ($successCount === 0) {
            $this->error('Failed to ping any search engines.');

            return self::FAILURE;
        }

        if ($successCount < count($results)) {
            $this->warn('Some search engines could not be reached.');
        } else {
            $this->info('All search engines pinged successfully.');
        }

        return self::SUCCESS;
    }
}
