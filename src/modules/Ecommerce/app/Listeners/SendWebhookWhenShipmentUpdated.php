<?php

namespace Modules\Ecommerce\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Ecommerce\Events\ShippingStatusChanged;
use Modules\Ecommerce\Models\Shipment;
use Modules\Ecommerce\Services\WebhookService;

class SendWebhookWhenShipmentUpdated implements ShouldQueue
{
    public string $queue = 'webhooks';

    public function __construct(private readonly WebhookService $webhookService) {}

    public function handle(ShippingStatusChanged $event): void
    {
        $this->webhookService->dispatch('order.shipped', $this->payload($event->shipment, $event->oldStatus, $event->newStatus));
    }

    private function payload(Shipment $shipment, string $oldStatus, string $newStatus): array
    {
        return [
            'shipment_id' => $shipment->id,
            'order_id' => $shipment->order_id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'tracking_id' => $shipment->tracking_id ?? null,
            'updated_at' => $shipment->updated_at?->toIso8601String(),
        ];
    }
}
