<?php

namespace Modules\Remarketing\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Mailer\Services\MailerTemplateRendererService;
use Modules\Remarketing\Jobs\SendEmailJob;
use Modules\Remarketing\Models\Campaign;
use Modules\Remarketing\Models\CampaignVariant;
use Modules\Remarketing\Models\ConsentEvent;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Message;
use Modules\Remarketing\Models\Suppression;

class CampaignService
{
    public function __construct(
        private readonly ConsentService $consentService,
        private readonly SegmentService $segmentService,
        private readonly WebhookService $webhookService,
    ) {}

    /**
     * Schedule a campaign for sending at a specific time.
     */
    public function schedule(Campaign $campaign, Carbon $scheduledAt): void
    {
        $campaign->update([
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
        ]);
    }

    /**
     * Begin sending a campaign to all eligible segment members.
     * Returns the number of messages dispatched.
     */
    public function send(Campaign $campaign): int
    {
        if ($campaign->mailer_template_id) {
            $validation = MailerTemplateRendererService::validateTemplate($campaign->mailerTemplate);
            if (! $validation['valid']) {
                $missingNames = array_column($validation['missing'], 'name');
                Log::warning('Campaign send blocked: missing variables', [
                    'campaign_id' => $campaign->id,
                    'missing' => $missingNames,
                ]);
                $campaign->update(['status' => 'draft']);

                throw new \RuntimeException(
                    'No se puede enviar la campaña: faltan variables requeridas en la plantilla: '.implode(', ', $missingNames)
                );
            }
        }

        $campaign->update([
            'status' => 'sending',
            'started_at' => now(),
        ]);

        $store = $campaign->store;
        $dispatched = 0;
        $holdoutCount = 0;

        $holdoutPct = $this->resolveHoldoutPct($campaign);
        $sendTimeOpt = (bool) data_get($campaign->settings, 'send_time_optimization', false);
        $variants = $campaign->variants()->get();

        // Pre-load all suppressions for this store into memory (O(1) lookup per customer)
        $suppressedEmails = Suppression::query()
            ->where('store_id', $store->id)
            ->pluck('email')
            ->mapWithKeys(fn (string $email) => [strtolower(trim($email)) => true])
            ->all();

        $query = $campaign->segment_id
            ? $this->segmentService->getMembers($campaign->segment)
            : Customer::query()
                ->where('store_id', $campaign->store_id)
                ->withEmailMarketingConsent();

        $query->chunk(500, function ($customers) use ($campaign, $holdoutPct, $sendTimeOpt, $variants, &$dispatched, &$holdoutCount, $suppressedEmails): void {
            foreach ($customers as $customer) {
                if (! $customer->email) {
                    continue;
                }

                if (isset($suppressedEmails[strtolower(trim($customer->email))])) {
                    continue;
                }

                $isHoldout = $this->shouldHoldOut($customer->email, $campaign->id, $holdoutPct);

                $variant = $variants->isNotEmpty()
                    ? $this->pickVariant($customer->email, $campaign->id, $variants)
                    : null;

                $subject = $variant?->subject ?? $campaign->subject;

                $message = Message::create([
                    'store_id' => $campaign->store_id,
                    'customer_id' => $customer->id,
                    'campaign_id' => $campaign->id,
                    'variant_id' => $variant?->id,
                    'email' => $customer->email,
                    'subject' => $subject,
                    'status' => $isHoldout ? 'suppressed' : 'queued',
                    'is_holdout' => $isHoldout,
                    'open_token' => Str::random(64),
                    'click_token' => Str::random(64),
                ]);

                if ($isHoldout) {
                    $holdoutCount++;

                    continue;
                }

                $delay = $sendTimeOpt
                    ? $this->computeSendDelay($customer->preferred_send_hour)
                    : null;

                $job = SendEmailJob::dispatch($message)->onQueue('remarketing');

                if ($delay !== null) {
                    $job->delay($delay);
                }

                $dispatched++;
            }
        });

        $campaign->update([
            'recipients_total' => $dispatched + $holdoutCount,
            'settings' => array_merge($campaign->settings ?? [], [
                'holdout_count' => $holdoutCount,
                'holdout_pct_applied' => $holdoutPct,
            ]),
        ]);

        $this->webhookService->fire('campaign.sent', $campaign->store_id, [
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->name,
            'dispatched' => $dispatched,
        ]);

        return $dispatched;
    }

    /**
     * Deterministic variant assignment: same email always gets same variant.
     * Respects variant weights (should sum to 100; tolerant if they don't).
     */
    protected function pickVariant(string $email, int $campaignId, Collection $variants): ?CampaignVariant
    {
        $totalWeight = $variants->sum('weight');

        if ($totalWeight <= 0) {
            return $variants->first();
        }

        $bucket = abs(crc32(strtolower(trim($email)).'|ab|'.$campaignId)) % $totalWeight;
        $cumulative = 0;

        foreach ($variants as $variant) {
            $cumulative += $variant->weight;
            if ($bucket < $cumulative) {
                return $variant;
            }
        }

        return $variants->last();
    }

    /**
     * Recalculate per-variant stats from messages table. Also picks a winner.
     */
    public function recalculateVariantStats(Campaign $campaign): void
    {
        $variants = $campaign->variants()->get();

        if ($variants->isEmpty()) {
            return;
        }

        $winnerMetric = $campaign->ab_winner_metric ?? 'open_rate';

        foreach ($variants as $variant) {
            $stats = Message::query()
                ->where('variant_id', $variant->id)
                ->where('is_holdout', false)
                ->selectRaw('
                    COUNT(*) as sent,
                    SUM(status IN ("opened","clicked")) as opened,
                    SUM(status = "clicked") as clicked,
                    SUM(revenue) as revenue
                ')
                ->first();

            $variant->update([
                'sent' => (int) ($stats->sent ?? 0),
                'opened' => (int) ($stats->opened ?? 0),
                'clicked' => (int) ($stats->clicked ?? 0),
                'revenue' => (float) ($stats->revenue ?? 0),
            ]);
        }

        // Refresh after updates
        $variants = $campaign->variants()->get();

        $winner = $variants->sortByDesc(fn (CampaignVariant $v) => match ($winnerMetric) {
            'ctr' => $v->ctr(),
            'revenue' => (float) $v->revenue,
            default => $v->openRate(),
        })->first();

        if ($winner) {
            $variants->each(fn (CampaignVariant $v) => $v->update(['is_winner' => $v->id === $winner->id]));
        }
    }

    /**
     * Return the number of seconds to delay a message so it arrives at the customer's
     * preferred hour. Returns null when no preference is set.
     * Max delay capped at 23 h 59 m so a message is never pushed to the next day's cycle.
     */
    protected function computeSendDelay(?int $preferredHour): ?int
    {
        if ($preferredHour === null) {
            return null;
        }

        $now = now()->utc();
        $target = $now->copy()->setTime($preferredHour, 0, 0);

        if ($target->lte($now)) {
            $target->addDay();
        }

        $seconds = (int) $now->diffInSeconds($target);

        return min($seconds, 86340); // cap at 23h59m
    }

    /**
     * Read holdout_pct from campaign settings, normalize to 0-50 range.
     */
    protected function resolveHoldoutPct(Campaign $campaign): int
    {
        $pct = (int) (data_get($campaign->settings, 'holdout_pct', 0));

        return max(0, min(50, $pct));
    }

    /**
     * Deterministic holdout decision: same email always falls in same bucket per campaign.
     * Returns true if the customer should be excluded from send (kept in control group).
     */
    protected function shouldHoldOut(string $email, int $campaignId, int $holdoutPct): bool
    {
        if ($holdoutPct <= 0) {
            return false;
        }

        $bucket = crc32(strtolower(trim($email)).'|'.$campaignId) % 100;

        return $bucket < $holdoutPct;
    }

    /**
     * Cancel a campaign that hasn't finished sending.
     */
    public function cancel(Campaign $campaign): void
    {
        $campaign->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);
    }

    /**
     * Recalculate campaign stats from the messages table.
     */
    public function recalculateStats(Campaign $campaign): void
    {
        $stats = Message::query()
            ->where('campaign_id', $campaign->id)
            ->where('is_holdout', false)
            ->selectRaw('
                COUNT(*) as total,
                SUM(status = "sent") as sent,
                SUM(status = "delivered") as delivered,
                SUM(status = "bounced") as bounced,
                SUM(status IN ("opened", "clicked")) as opened,
                SUM(status = "clicked") as clicked,
                SUM(status = "complained") as complained,
                SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as unique_opens,
                SUM(revenue) as revenue
            ')
            ->first();

        $holdoutStats = Message::query()
            ->where('campaign_id', $campaign->id)
            ->where('is_holdout', true)
            ->selectRaw('COUNT(*) as total, SUM(revenue) as revenue')
            ->first();

        $holdoutTotal = (int) ($holdoutStats->total ?? 0);
        $holdoutRevenue = (float) ($holdoutStats->revenue ?? 0);
        $sentTotal = (int) ($stats->sent ?? 0);
        $sentRevenue = (float) ($stats->revenue ?? 0);

        $incrementalRevenue = 0.0;

        if ($holdoutTotal > 0 && $sentTotal > 0) {
            $revPerSent = $sentRevenue / $sentTotal;
            $revPerHoldout = $holdoutRevenue / $holdoutTotal;
            $incrementalRevenue = ($revPerSent - $revPerHoldout) * $sentTotal;
        }

        // Count unsubscribes separately from consent events
        $unsubscribed = ConsentEvent::query()
            ->where('store_id', $campaign->store_id)
            ->where('event_type', 'withdrawn')
            ->whereIn('customer_id', function ($sub) use ($campaign): void {
                $sub->select('customer_id')
                    ->from('remarketing_messages')
                    ->where('campaign_id', $campaign->id);
            })
            ->count();

        $campaign->update([
            'sent' => (int) ($stats->sent ?? 0),
            'delivered' => (int) ($stats->delivered ?? 0),
            'bounced' => (int) ($stats->bounced ?? 0),
            'opened' => (int) ($stats->opened ?? 0),
            'clicked' => (int) ($stats->clicked ?? 0),
            'unsubscribed' => $unsubscribed,
            'complained' => (int) ($stats->complained ?? 0),
            'revenue' => (float) ($stats->revenue ?? 0),
            'settings' => array_merge($campaign->settings ?? [], [
                'holdout_total' => $holdoutTotal,
                'holdout_revenue' => round($holdoutRevenue, 2),
                'incremental_revenue' => round($incrementalRevenue, 2),
            ]),
        ]);

        $this->webhookService->fire('campaign.completed', $campaign->store_id, [
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->name,
            'sent' => (int) ($stats->sent ?? 0),
            'opened' => (int) ($stats->opened ?? 0),
            'clicked' => (int) ($stats->clicked ?? 0),
            'revenue' => (float) ($stats->revenue ?? 0),
        ]);
    }
}
