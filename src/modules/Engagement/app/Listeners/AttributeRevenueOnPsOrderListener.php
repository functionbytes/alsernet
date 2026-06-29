<?php

namespace Modules\Engagement\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskPrestashop\Events\PsOrderCreated;

class AttributeRevenueOnPsOrderListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'engagement';

    public int $tries = 2;

    public int $timeout = 30;

    public int $backoff = 10;

    public function handle(PsOrderCreated $event): void
    {
        $customerId = $event->customerId();
        $orderId = $event->orderId();
        $total = (float) ($event->payload['total'] ?? 0);

        if (! $customerId || ! $orderId || $total <= 0) {
            return;
        }

        // Find the most recent active session for this PS customer
        $sessionToken = $this->resolveSessionToken($customerId, $event->email());

        if (! $sessionToken) {
            return;
        }

        // Find AB test variant impressions stored in event properties
        $variantIds = $this->findVariantImpressionsForSession($sessionToken);

        if ($variantIds->isEmpty()) {
            return;
        }

        $currency = $event->payload['currency'] ?? 'EUR';
        $orderPayload = json_encode($event->payload);

        foreach ($variantIds as $variantId) {
            $abTestId = DB::connection('helpdesk')
                ->table('engagement_ab_test_variants')
                ->where('id', $variantId)
                ->value('ab_test_id');

            if (! $abTestId) {
                continue;
            }

            try {
                DB::connection('helpdesk')
                    ->table('engagement_ab_test_attributions')
                    ->insertOrIgnore([
                        'ab_test_id' => $abTestId,
                        'ab_test_variant_id' => $variantId,
                        'session_token' => $sessionToken,
                        'external_order_id' => $orderId,
                        'external_customer_id' => $customerId,
                        'revenue' => $total,
                        'currency' => $currency,
                        'order_payload' => $orderPayload,
                        'attributed_at' => now(),
                    ]);
            } catch (\Throwable $e) {
                Log::warning('Engagement: AB attribution insert failed', [
                    'variant_id' => $variantId,
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function failed(PsOrderCreated $event, \Throwable $exception): void
    {
        Log::error('Engagement: AttributeRevenueOnPsOrderListener failed', [
            'order_id' => $event->orderId(),
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Resolve session token by PS customer id or email via visitor_contexts.ps_customer_id.
     */
    private function resolveSessionToken(int $psCustomerId, ?string $email): ?string
    {
        // Primary: look up by ps_customer_id set during enrichment
        $token = DB::connection('helpdesk')
            ->table('engagement_visitor_contexts')
            ->where('ps_customer_id', $psCustomerId)
            ->orderByDesc('updated_at')
            ->value('session_token');

        if ($token) {
            return $token;
        }

        // Fallback: match by email in context JSON (stored during identify flow)
        if ($email) {
            $token = DB::connection('helpdesk')
                ->table('engagement_visitor_contexts')
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(context, '$.email')) = ?", [$email])
                ->orderByDesc('updated_at')
                ->value('session_token');
        }

        return $token ?: null;
    }

    /**
     * Find variant IDs from AB test impression events logged in the session.
     * Events with event_name='ab_test_impression' store variant_id in properties JSON.
     */
    private function resolveSessionTokenByEmail(string $email): ?string
    {
        return DB::connection('helpdesk')
            ->table('engagement_visitor_contexts')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(context, '$.email')) = ?", [$email])
            ->orderByDesc('updated_at')
            ->value('session_token');
    }

    private function findVariantImpressionsForSession(string $sessionToken): Collection
    {
        return DB::connection('helpdesk')
            ->table('engagement_events')
            ->where('session_token', $sessionToken)
            ->where('event_name', 'ab_test_impression')
            ->where('occurred_at', '>=', now()->subDays(30))
            ->whereRaw("JSON_EXTRACT(properties, '$.ab_test_variant_id') IS NOT NULL")
            ->pluck(DB::raw("CAST(JSON_UNQUOTE(JSON_EXTRACT(properties, '$.ab_test_variant_id')) AS UNSIGNED)"))
            ->unique()
            ->values();
    }
}
