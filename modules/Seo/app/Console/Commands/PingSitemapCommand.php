<?php

namespace Modules\Seo\Console\Commands;

use Illuminate\Console\Command;
use Modules\Seo\Helpers\SitemapHelper;

class PingSitemapCommand extends Command
{
    protected $signature = 'sitemap:ping {--url= : Custom sitemap URL}';

    protected $description = 'Ping search engines about sitemap updates';

    public function handle()
    {
        $sitemapUrl = $this->option('url') ?? url('/sitemap.xml');

        $this->info('🌐 Pinging search engines...');
        $this->info("📄 Sitemap URL: {$sitemapUrl}");

        $results = SitemapHelper::pingSearchEngines($sitemapUrl);

        $this->newLine();

        foreach ($results as $engine => $success) {
            $status = $success ? '✅' : '❌';
            $message = $success ? 'Success' : 'Failed';

            $this->line("{$status} {$engine}: {$message}");
        }

        $this->newLine();

        $successCount = count(array_filter($results));
        $totalCount = count($results);

        if ($successCount === $totalCount) {
            $this->info('🎉 All search engines pinged successfully!');

            return self::SUCCESS;
        } elseif ($successCount > 0) {
            $this->warn('⚠️  Some search engines could not be reached.');

            return self::SUCCESS;
        } else {
            $this->error('❌ Failed to ping any search engines.');

            return self::FAILURE;
        }
    }
}
