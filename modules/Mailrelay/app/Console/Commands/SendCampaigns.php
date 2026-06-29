<?php

namespace Modules\Mailrelay\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Entities\MailrelayGroup;
use Modules\Mailrelay\Enums\CampaignStatus;
use Modules\Mailrelay\Jobs\SendCampaignJob;

/**
 * Process and dispatch scheduled campaigns for sending.
 *
 * Queries campaigns with SCHEDULED status whose scheduled_at time has passed,
 * resolves active subscribers from each campaign's list, and dispatches
 * SendCampaignJob to the campaigns queue.
 *
 * Usage:
 *   php artisan mailrelay:send-campaigns
 *   php artisan mailrelay:send-campaigns --dry-run
 */
class SendCampaigns extends Command
{
    protected $signature = 'mailrelay:send-campaigns
                            {--dry-run : Preview campaigns that would be sent without dispatching jobs}';

    protected $description = 'Process scheduled campaigns and dispatch send jobs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No jobs will be dispatched');
        }

        $this->info('Querying scheduled campaigns...');

        $campaigns = Campaign::query()
            ->where('status', CampaignStatus::SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('No scheduled campaigns ready to send.');

            return Command::SUCCESS;
        }

        $this->info("Found {$campaigns->count()} campaign(s) ready to send.");

        $dispatched = 0;
        $failed = 0;

        foreach ($campaigns as $campaign) {
            try {
                $this->processCampaign($campaign, $isDryRun);
                $dispatched++;
            } catch (\Throwable $e) {
                $failed++;
                $this->error("Failed to process campaign #{$campaign->id} ({$campaign->name}): {$e->getMessage()}");

                Log::error('SendCampaigns: failed to process campaign', [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Campaigns found', $campaigns->count()],
                ['Dispatched', $dispatched],
                ['Failed', $failed],
            ]
        );

        if ($failed > 0) {
            $this->warn("{$failed} campaign(s) failed to process. Check logs for details.");
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Process a single campaign: resolve subscribers and dispatch the send job.
     */
    protected function processCampaign(Campaign $campaign, bool $isDryRun): void
    {
        $this->info("Processing campaign #{$campaign->id}: {$campaign->name}");

        $group = MailrelayGroup::query()->find($campaign->list_id);

        if (! $group) {
            throw new \RuntimeException("List (group) ID {$campaign->list_id} not found for campaign #{$campaign->id}");
        }

        $recipientCount = $group->subscribers()->active()->count();

        if ($recipientCount === 0) {
            $this->warn("  No active subscribers in list '{$group->name}'. Skipping campaign.");

            Log::warning('SendCampaigns: no active subscribers for campaign', [
                'campaign_id' => $campaign->id,
                'list_id' => $campaign->list_id,
            ]);

            return;
        }

        $this->info('  Found '.$recipientCount." active subscriber(s) in list '{$group->name}'.");

        $recipients = $group->subscribers()
            ->active()
            ->limit(5000)
            ->get()
            ->map(fn ($subscriber) => [
                'email' => $subscriber->email,
                'name' => $subscriber->name,
            ])
            ->all();

        if ($isDryRun) {
            $this->line('  [DRY RUN] Would dispatch SendCampaignJob and mark campaign as SENDING.');

            return;
        }

        $campaign->markAsSending();

        SendCampaignJob::dispatch($campaign, $recipients)->onQueue('campaigns');

        Log::info('SendCampaigns: dispatched SendCampaignJob', [
            'campaign_id' => $campaign->id,
            'recipients_count' => count($recipients),
        ]);

        $this->info('  Dispatched to queue. Recipients: '.count($recipients));
    }
}
