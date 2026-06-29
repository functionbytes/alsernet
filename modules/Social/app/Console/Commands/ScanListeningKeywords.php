<?php

namespace Modules\Social\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Social\Models\SocialListeningKeyword;
use Modules\Social\Services\SocialListeningService;

class ScanListeningKeywords extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'social:scan-listening
                            {--all : Scan all keywords regardless of last_scanned_at}
                            {--keyword= : Only scan specific keyword ID}
                            {--limit=50 : Maximum number of keywords to process}';

    /**
     * The console command description.
     */
    protected $description = 'Scan social networks for listening keywords and mentions';

    /**
     * Execute the console command.
     */
    public function handle(SocialListeningService $listeningService): int
    {
        $scanAll = $this->option('all');
        $keywordId = $this->option('keyword');
        $limit = (int) $this->option('limit');

        $this->info('🔍 Scanning social networks for listening keywords...');

        // Build query
        $query = SocialListeningKeyword::with('account');

        if ($keywordId) {
            // Scan specific keyword
            $query->where('id', $keywordId);
            $this->line("Scanning keyword ID: {$keywordId}");
        } elseif ($scanAll) {
            // Scan all active keywords
            $query->where('is_active', true);
            $this->line('Scanning all active keywords');
        } else {
            // Scan only keywords that need scanning (default behavior)
            $query->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('last_scanned_at')
                        ->orWhere('last_scanned_at', '<', now()->subMinutes(15));
                });
            $this->line('Scanning keywords due for update (>15 min since last scan)');
        }

        $keywords = $query->orderBy('last_scanned_at', 'asc')
            ->limit($limit)
            ->get();

        if ($keywords->isEmpty()) {
            $this->info('✅ No keywords found that need scanning.');

            return Command::SUCCESS;
        }

        $this->info("📋 Found {$keywords->count()} keyword(s) to scan.");
        $this->newLine();

        $totalMentions = 0;
        $successCount = 0;
        $failedCount = 0;

        foreach ($keywords as $keyword) {
            $formattedName = match ($keyword->type) {
                'hashtag' => '#'.$keyword->name,
                'mention' => '@'.$keyword->name,
                default => $keyword->name,
            };

            $networksText = implode(', ', array_map('ucfirst', $keyword->networks));

            $this->line("  • {$formattedName} ({$keyword->type})");
            $this->line("    Networks: {$networksText}");

            if ($keyword->last_scanned_at) {
                $lastScan = $keyword->last_scanned_at->diffForHumans();
                $this->line("    Last scan: {$lastScan}");
            } else {
                $this->line('    Last scan: Never');
            }

            try {
                // Scan keyword using service
                $result = $listeningService->scanKeyword($keyword);

                $mentionsFound = $result['mentions_found'] ?? 0;
                $totalMentions += $mentionsFound;
                $successCount++;

                if ($mentionsFound > 0) {
                    $this->line("    <fg=green>✅ Found {$mentionsFound} new mention(s)</>");
                } else {
                    $this->line('    <fg=yellow>○ No new mentions found</>');
                }

                Log::info("Scanned listening keyword {$keyword->id}", [
                    'keyword_id' => $keyword->id,
                    'keyword_name' => $keyword->name,
                    'mentions_found' => $mentionsFound,
                ]);
            } catch (Exception $e) {
                $failedCount++;
                $this->line('    <fg=red>❌ Failed: '.$e->getMessage().'</>');

                Log::error("Failed to scan listening keyword {$keyword->id}", [
                    'keyword_id' => $keyword->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->newLine();
        }

        // Summary table
        $this->info('📊 Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Keywords Scanned', $keywords->count()],
                ['Successful', $successCount],
                ['Failed', $failedCount],
                ['Total Mentions Found', $totalMentions],
            ]
        );

        return $failedCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
