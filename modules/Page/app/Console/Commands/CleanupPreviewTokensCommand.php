<?php

namespace Modules\Page\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Page\Models\PagePreviewToken;

class CleanupPreviewTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page:cleanup-preview-tokens
                            {--days=7 : Delete tokens expired more than X days ago}
                            {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup expired page preview tokens';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $force = $this->option('force');

        $this->info('Cleaning up expired preview tokens...');

        // Find tokens expired more than X days ago
        $cutoffDate = Carbon::now()->subDays($days);

        $expiredTokens = PagePreviewToken::where('expires_at', '<=', $cutoffDate)->get();

        if ($expiredTokens->isEmpty()) {
            $this->info('No expired tokens found.');

            return Command::SUCCESS;
        }

        $count = $expiredTokens->count();
        $this->warn("Found {$count} expired token(s) to delete.");

        // Show details
        if ($this->output->isVerbose()) {
            $this->table(
                ['ID', 'Page ID', 'Expired At', 'Views', 'Days Expired'],
                $expiredTokens->map(function ($token) {
                    return [
                        $token->id,
                        $token->page_id,
                        $token->expires_at->format('Y-m-d H:i:s'),
                        $token->viewed_count,
                        $token->expires_at->diffInDays(now()),
                    ];
                })->toArray()
            );
        }

        // Confirm deletion unless force flag is set
        if (! $force && ! $this->confirm("Do you want to delete these {$count} expired token(s)?", true)) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        // Delete tokens
        $deleted = PagePreviewToken::where('expires_at', '<=', $cutoffDate)->delete();

        $this->info("Successfully deleted {$deleted} expired preview token(s).");

        // Show statistics
        $remainingActive = PagePreviewToken::active()->count();
        $remainingExpired = PagePreviewToken::expired()->count();

        $this->newLine();
        $this->info('Current Statistics:');
        $this->line("  Active tokens: {$remainingActive}");
        $this->line("  Expired tokens: {$remainingExpired}");

        return Command::SUCCESS;
    }
}
