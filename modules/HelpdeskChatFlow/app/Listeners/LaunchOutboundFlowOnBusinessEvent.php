<?php

namespace Modules\HelpdeskChatFlow\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChatFlow\Services\ChatFlowBusinessEventLauncher;
use Modules\HelpdeskErp\Events\ErpOrdersReady;
use Modules\HelpdeskPrestashop\Events\PsCartAbandoned;
use Modules\HelpdeskPrestashop\Events\PsOrderStatusChanged;

/**
 * Bridges PrestaShop/ERP business events to a proactive chat flow. Handles the
 * events by FQN-based instanceof checks so it degrades gracefully when the
 * PrestaShop/ERP modules aren't installed.
 */
class LaunchOutboundFlowOnBusinessEvent implements ShouldQueue
{
    public string $queue = 'chatflow';

    public int $tries = 2;

    public int $backoff = 10;

    public int $timeout = 60;

    public function __construct(
        private readonly ChatFlowBusinessEventLauncher $launcher,
    ) {}

    public function handle(object $event): void
    {
        if ($event instanceof PsCartAbandoned) {
            $this->launcher->launch('abandoned_cart',
                ['email' => $event->email(), 'ps_id' => $event->customerId()],
                ['cart_total' => $event->total(), 'items_count' => count($event->items()), 'ps_cart_id' => $event->cartId()],
            );

            return;
        }

        if ($event instanceof PsOrderStatusChanged) {
            $this->launcher->launch('order_status',
                ['ps_id' => $event->customerId()],
                ['order_id' => $event->orderId(), 'new_status' => $event->newStatus()],
            );

            return;
        }

        if ($event instanceof ErpOrdersReady) {
            $this->launcher->launch('order_ready',
                ['email' => $event->email, 'erp_id' => $event->customerId],
                ['erp_customer_id' => $event->customerId],
            );
        }
    }

    public function failed(object $event, \Throwable $exception): void
    {
        Log::error('LaunchOutboundFlowOnBusinessEvent failed', [
            'event' => $event::class,
            'error' => $exception->getMessage(),
        ]);
    }
}
