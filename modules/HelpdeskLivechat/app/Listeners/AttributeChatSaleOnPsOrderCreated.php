<?php

namespace Modules\HelpdeskLivechat\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskLivechat\Services\Commerce\ChatSaleAttributionService;

/**
 * order.created de la tienda → venta atribuida al chat (si procede).
 * Nunca rompe el procesado del webhook.
 */
class AttributeChatSaleOnPsOrderCreated
{
    public function __construct(
        private readonly ChatSaleAttributionService $attribution,
    ) {}

    public function handle(object $event): void
    {
        try {
            $this->attribution->attribute(is_array($event->payload ?? null) ? $event->payload : []);
        } catch (\Throwable $e) {
            Log::warning('AttributeChatSaleOnPsOrderCreated failed', ['error' => $e->getMessage()]);
        }
    }
}
