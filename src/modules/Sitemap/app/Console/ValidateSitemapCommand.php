<?php

namespace Modules\Sitemap\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class ValidateSitemapCommand extends Command
{
    protected $signature = 'sitemap:validate
                            {--check-urls : Verify HTTP status of sampled URLs}
                            {--sample=10 : Number of URLs to check (default 10)}';

    protected $description = 'Validate the generated sitemap.xml for XML errors and optionally check URL accessibility';

    public function handle(): int
    {
        $path = public_path('sitemap.xml');

        if (! file_exists($path)) {
            $this->error('sitemap.xml not found. Run sitemap:generate first.');

            return self::FAILURE;
        }

        // XML validation
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($path);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        if ($xml === false || ! empty($errors)) {
            $this->error('sitemap.xml contains XML errors:');
            foreach ($errors as $error) {
                $this->error(sprintf('  Line %d: %s', $error->line, trim($error->message)));
            }

            return self::FAILURE;
        }

        $urlCount = count($xml->url ?? []);
        $this->info("XML structure: OK ({$urlCount} URLs)");

        if ($urlCount === 0) {
            $this->warn('Sitemap has no URLs.');
        }

        if ($this->option('check-urls') && $urlCount > 0) {
            $sample = min((int) $this->option('sample'), $urlCount);
            $broken = 0;

            $this->info("Checking {$sample} sampled URLs...");

            foreach (collect($xml->url)->take($sample) as $url) {
                $loc = (string) $url->loc;
                try {
                    $response = Http::timeout(5)->withoutRedirecting()->head($loc);
                    $status = $response->status();
                    $ok = $status < 400;
                    $symbol = $ok ? '✓' : '✗';
                    $this->line("  {$symbol} [{$status}] {$loc}");
                    if (! $ok) {
                        $broken++;
                    }
                } catch (Throwable $e) {
                    $this->warn("  ? [ERR] {$loc}: {$e->getMessage()}");
                    $broken++;
                }
            }

            if ($broken > 0) {
                $this->warn("{$broken}/{$sample} URLs returned errors.");

                return self::FAILURE;
            }

            $this->info("All {$sample} sampled URLs are accessible.");
        }

        return self::SUCCESS;
    }
}
