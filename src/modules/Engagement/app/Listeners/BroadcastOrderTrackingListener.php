<?php

namespace Modules\Engagement\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Engagement\Events\OrderTrackingUpdated;
use Modules\HelpdeskPrestashop\Events\PsOrderStatusChanged;

class BroadcastOrderTrackingListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'engagement';

    public int $tries = 3;

    public int $timeout = 30;

    public int $backoff = 5;

    public function handle(PsOrderStatusChanged $event): void
    {
        $psCustomerId = $event->customerId();
        $orderId = $event->orderId();

        if (! $psCustomerId || ! $orderId) {
            return;
        }

        // Find session tokens active in the last hour for this PS customer
        $sessionTokens = DB::connection('helpdesk')
            ->table('engagement_visitor_contexts as vc')
            ->join('engagement_visitor_sessions as vs', 'vs.session_token', '=', 'vc.session_token')
            ->where('vc.ps_customer_id', $psCustomerId)
            ->where('vs.last_activity_at', '>=', now()->subHour())
            ->pluck('vc.session_token');

        foreach ($sessionTokens as $token) {
            broadcast(new OrderTrackingUpdated(
                $token,
                $orderId,
                $event->payload['old_status'] ?? null,
                $event->payload['new_status'] ?? null,
            ));
        }
    }

    public function failed(PsOrderStatusChanged $event, \Throwable $exception): void
    {
        Log::error('Engagement: BroadcastOrderTrackingListener failed', [
            'order_id' => $event->orderId(),
            'error' => $exception->getMessage(),
        ]);
    }
}
