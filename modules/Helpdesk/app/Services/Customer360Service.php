<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\DB;
use Modules\Engagement\Models\Event;
use Modules\Engagement\Models\RecommendationProfile;
use Modules\Engagement\Models\Segment;
use Modules\Engagement\Models\VisitorScore;
use Modules\Engagement\Services\CustomerDataOrchestrator;
use Modules\Helpdesk\Contracts\TicketServiceContract;
use Modules\Helpdesk\Models\CsatRating;
use Modules\Helpdesk\Models\Customer;
use Nwidart\Modules\Facades\Module;

/**
 * Aggregates Customer 360 data from Helpdesk, Engagement, and external platforms.
 *
 * The `platforms` section requires an inbox_id so the CustomerDataOrchestrator
 * knows which integrations are active. When inbox_id is absent or Engagement
 * is disabled, those sections return null / empty arrays.
 */
class Customer360Service
{
    /**
     * @return array{customer: array, engagement: array|null, platforms: array, helpdesk: array}
     */
    public function aggregate(Customer $customer, ?int $inboxId, bool $force = false): array
    {
        return [
            'customer' => $this->customerData($customer),
            'engagement' => $this->engagementData($customer, $inboxId),
            'platforms' => $this->platformsData($customer, $inboxId, $force),
            'helpdesk' => $this->helpdeskData($customer),
        ];
    }

    // ── Customer ──────────────────────────────────────────────────────────────

    /**
     * @return array{id: int, name: string, email: string|null, phone: string|null, avatar_url: string, created_at: string}
     */
    private function customerData(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'avatar_url' => $customer->getAvatarUrl(),
            'created_at' => $customer->created_at->toIso8601String(),
        ];
    }

    // ── Engagement ────────────────────────────────────────────────────────────

    /**
     * Returns engagement data if the Engagement module is active; null otherwise.
     *
     * @return array{score: int|null, segment: string|null, segment_names: array, last_activity_at: string|null, top_events: array, recommendations: array}|null
     */
    private function engagementData(Customer $customer, ?int $inboxId): ?array
    {
        if (! $this->engagementEnabled()) {
            return null;
        }

        $visitorScore = VisitorScore::query()
            ->where('customer_id', $customer->id)
            ->when($inboxId, fn ($q) => $q->where('inbox_id', $inboxId))
            ->orderByDesc('last_recalc_at')
            ->first();

        $segmentNames = Segment::query()
            ->whereHas('customers', fn ($q) => $q->where('helpdesk_customers.id', $customer->id))
            ->when($inboxId, fn ($q) => $q->where('inbox_id', $inboxId))
            ->active()
            ->pluck('name')
            ->values()
            ->all();

        $topEvents = Event::query()
            ->where('customer_id', $customer->id)
            ->when($inboxId, fn ($q) => $q->where('inbox_id', $inboxId))
            ->selectRaw('event_name, COUNT(*) as occurrences, MAX(occurred_at) as last_at')
            ->groupBy('event_name')
            ->orderByDesc('occurrences')
            ->limit(5)
            ->get()
            ->map(fn ($e) => [
                'event_name' => $e->event_name,
                'occurrences' => (int) $e->occurrences,
                'last_at' => $e->last_at,
            ])
            ->all();

        $profile = RecommendationProfile::query()
            ->where('customer_id', $customer->id)
            ->first();

        $recommendations = [];
        if ($profile) {
            $viewed = $profile->viewed_products ?? [];
            usort($viewed, fn ($a, $b) => ($b['count'] ?? 0) <=> ($a['count'] ?? 0));
            $recommendations = array_slice($viewed, 0, 5);
        }

        return [
            'score' => $visitorScore?->score,
            'segment' => $visitorScore?->segment,
            'segment_names' => $segmentNames,
            'last_activity_at' => $visitorScore?->last_event_at?->toIso8601String(),
            'top_events' => $topEvents,
            'recommendations' => $recommendations,
        ];
    }

    // ── Platforms (ERP / PrestaShop) ──────────────────────────────────────────

    /**
     * Fetches cross-platform data via CustomerDataOrchestrator.
     * Returns empty array when Engagement is disabled or inbox_id is absent.
     *
     * @return array<int, array{ok: bool, data: array|null, error: string|null, cached_at: string|null, platform: string}>
     */
    private function platformsData(Customer $customer, ?int $inboxId, bool $force): array
    {
        if (! $this->engagementEnabled() || ! $inboxId) {
            return [];
        }

        /** @var CustomerDataOrchestrator $orchestrator */
        $orchestrator = app(CustomerDataOrchestrator::class);

        $lookup = array_filter([
            'email' => $customer->email,
            'external_id' => $customer->externalIds->first()?->external_id,
        ], fn ($v) => $v !== null && $v !== '');

        if (empty($lookup)) {
            return [];
        }

        $results = [];

        foreach (['profile', 'orders', 'balance', 'loyaltyPoints'] as $action) {
            $actionResults = $orchestrator->fetchAcrossInbox($inboxId, $action, $lookup, $force);
            foreach ($actionResults as $integrationId => $result) {
                $results[$integrationId][$action] = $result;
                // Store platform name once
                if (! isset($results[$integrationId]['platform'])) {
                    $results[$integrationId]['platform'] = $result['platform'];
                }
            }
        }

        return $results;
    }

    // ── Helpdesk ──────────────────────────────────────────────────────────────

    /**
     * @return array{total_conversations: int, open_conversations: int, avg_csat: float|null, recent_tickets: array}
     */
    private function helpdeskData(Customer $customer): array
    {
        $conversationStats = DB::connection('helpdesk')
            ->table('helpdesk_conversations')
            ->where('customer_id', $customer->id)
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN closed_at IS NULL THEN 1 ELSE 0 END) as open_count')
            ->first();

        $avgCsat = CsatRating::query()
            ->where('customer_id', $customer->id)
            ->whereNotNull('answered_at')
            ->avg('rating');

        $recentTickets = $this->recentTickets($customer);

        return [
            'total_conversations' => (int) ($conversationStats->total ?? 0),
            'open_conversations' => (int) ($conversationStats->open_count ?? 0),
            'avg_csat' => $avgCsat !== null ? round((float) $avgCsat, 2) : null,
            'recent_tickets' => $recentTickets,
        ];
    }

    /**
     * Loads recent tickets if the HelpdeskTickets integration is enabled.
     * Goes through TicketServiceContract — no direct dependency on the
     * HelpdeskTickets module at the import level.
     *
     * @return array<int, array{id: int, ticket_number: string, subject: string, status: string, priority: string, created_at: string}>
     */
    private function recentTickets(Customer $customer): array
    {
        if (! helpdesk_tickets_enabled()) {
            return [];
        }

        return app(TicketServiceContract::class)
            ->getCustomerTickets($customer, 5)
            ->map(fn ($t) => [
                'id' => $t->id,
                'ticket_number' => $t->ticket_number,
                'subject' => $t->subject,
                'status' => $t->status?->name ?? '—',
                'status_color' => $t->status?->color ?? 'secondary',
                'priority' => $t->priority,
                'created_at' => $t->created_at->toIso8601String(),
                'created_at_human' => $t->created_at->diffForHumans(),
            ])
            ->all();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function engagementEnabled(): bool
    {
        return Module::find('Engagement')?->isEnabled() === true;
    }
}
