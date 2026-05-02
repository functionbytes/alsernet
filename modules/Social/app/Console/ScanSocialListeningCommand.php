<?php

namespace Modules\Social\Console;

use Illuminate\Console\Command;
use Modules\Social\Models\SocialListeningKeyword;
use Modules\Social\Services\SocialListeningService;

class ScanSocialListeningCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'social:scan-listening
                            {--account= : Specific account ID to scan}
                            {--keyword= : Specific keyword ID to scan}
                            {--force : Force scan even if recently scanned}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan social networks for listening keywords';

    /**
     * Execute the console command.
     */
    public function handle(SocialListeningService $service): int
    {
        $this->info('Starting social listening scan...');

        // Specific keyword scan
        if ($keywordId = $this->option('keyword')) {
            return $this->scanKeyword($keywordId, $service);
        }

        // Account-specific scan
        if ($accountId = $this->option('account')) {
            return $this->scanAccount($accountId, $service);
        }

        // Scan all accounts
        return $this->scanAll($service);
    }

    /**
     * Scan a specific keyword
     */
    protected function scanKeyword(int $keywordId, SocialListeningService $service): int
    {
        $keyword = SocialListeningKeyword::find($keywordId);

        if (! $keyword) {
            $this->error("Keyword #{$keywordId} not found");

            return self::FAILURE;
        }

        if (! $keyword->is_active) {
            $this->warn("Keyword #{$keywordId} is not active. Skipping...");

            return self::SUCCESS;
        }

        $this->info("Scanning keyword: {$keyword->name}");

        try {
            $result = $service->scanKeyword($keyword);

            $this->info("✓ Found {$result['mentions_found']} mentions");

            if (! empty($result['errors'])) {
                $this->warn('Errors encountered:');
                foreach ($result['errors'] as $error) {
                    $this->error("  - {$error}");
                }
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error scanning keyword: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Scan all keywords for a specific account
     */
    protected function scanAccount(int $accountId, SocialListeningService $service): int
    {
        $this->info("Scanning keywords for account #{$accountId}");

        $query = SocialListeningKeyword::where('account_id', $accountId)
            ->active();

        if (! $this->option('force')) {
            $query->needsScanning();
        }

        $keywords = $query->get();

        if ($keywords->isEmpty()) {
            $this->warn('No keywords need scanning');

            return self::SUCCESS;
        }

        $this->info("Found {$keywords->count()} keywords to scan");

        $totalMentions = 0;
        $errors = [];

        $progressBar = $this->output->createProgressBar($keywords->count());
        $progressBar->start();

        foreach ($keywords as $keyword) {
            try {
                $result = $service->scanKeyword($keyword);
                $totalMentions += $result['mentions_found'];
                $errors = array_merge($errors, $result['errors']);
            } catch (\Exception $e) {
                $errors[] = "{$keyword->name}: {$e->getMessage()}";
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('✓ Scan complete!');
        $this->info("  Total mentions found: {$totalMentions}");

        if (! empty($errors)) {
            $this->warn('Errors encountered:');
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Scan all active keywords across all accounts
     */
    protected function scanAll(SocialListeningService $service): int
    {
        $this->info('Scanning all active keywords...');

        $query = SocialListeningKeyword::active();

        if (! $this->option('force')) {
            $query->needsScanning();
        }

        $keywords = $query->get();

        if ($keywords->isEmpty()) {
            $this->warn('No keywords need scanning');

            return self::SUCCESS;
        }

        $keywordsByAccount = $keywords->groupBy('account_id');

        $this->info("Found {$keywords->count()} keywords across {$keywordsByAccount->count()} accounts");

        $totalMentions = 0;
        $totalErrors = 0;

        foreach ($keywordsByAccount as $accountId => $accountKeywords) {
            $this->info("\nAccount #{$accountId}: {$accountKeywords->count()} keywords");

            $progressBar = $this->output->createProgressBar($accountKeywords->count());
            $progressBar->start();

            foreach ($accountKeywords as $keyword) {
                try {
                    $result = $service->scanKeyword($keyword);
                    $totalMentions += $result['mentions_found'];
                    $totalErrors += count($result['errors']);
                } catch (\Exception $e) {
                    $totalErrors++;
                }

                $progressBar->advance();
            }

            $progressBar->finish();
        }

        $this->newLine(2);
        $this->info('✓ Scan complete!');
        $this->info("  Total mentions found: {$totalMentions}");

        if ($totalErrors > 0) {
            $this->warn("  Errors encountered: {$totalErrors}");
        }

        return self::SUCCESS;
    }
}
