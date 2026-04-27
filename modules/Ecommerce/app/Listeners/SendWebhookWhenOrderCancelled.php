<?php

namespace Modules\Ecommerce\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Ecommerce\Events\OrderCancelled;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Services\WebhookService;

class SendWebhookWhenOrderCancelled implements ShouldQueue
{
    public string $queue = 'webhooks';

    public function __construct(private readonly WebhookService $webhookService) {}

    public function handle(OrderCancelled $event): void
    {
        $this->webhookService->dispatch('order.cancelled', $this->payload($event->order));
    }

    private function payload(Order $order): array
    {
        return [
            'id' => $order->id,
            'code' => $order->code,
            'status' => is_object($order->status) ? $order->status->value : $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'total' => (float) $order->total,
            'sub_total' => (float) $order->sub_total,
            'tax_amount' => (float) $order->tax_amount,
            'shipping_amount' => (float) $order->shipping_amount,
            'discount_amount' => (float) $order->discount_amount,
            'customer' => $order->customer ? [
                'id' => $order->customer->id,
                'name' => $order->customer->name,
                'email' => $order->customer->email,
            ] : null,
            'items_count' => $order->items()->count(),
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }
}
