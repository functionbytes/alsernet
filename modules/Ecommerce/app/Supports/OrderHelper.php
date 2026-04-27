<?php

namespace Modules\Ecommerce\Supports;

use Modules\Ecommerce\Enums\OrderStatus;
use Modules\Ecommerce\Events\OrderCancelled;
use Modules\Ecommerce\Events\OrderCompleted;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\OrderHistory;

class OrderHelper
{
    public static function createOrder(array $data): Order
    {
        $order = Order::query()->create($data);
        self::logHistory($order, 'create', 'Orden creada');

        return $order;
    }

    public static function updateOrderStatus(Order $order, OrderStatus $status, ?string $description = null): void
    {
        $order->update(['status' => $status]);
        self::logHistory($order, $status->value, $description ?? "Estado actualizado a {$status->value}");

        match ($status) {
            OrderStatus::COMPLETED => OrderCompleted::dispatch($order),
            OrderStatus::CANCELLED => OrderCancelled::dispatch($order),
            default => null,
        };
    }

    public static function logHistory(Order $order, string $action, string $description, ?int $userId = null): OrderHistory
    {
        return OrderHistory::query()->create([
            'order_id' => $order->id,
            'action' => $action,
            'description' => $description,
            'user_id' => $userId ?? auth()->id(),
        ]);
    }

    public static function generateOrderCode(): string
    {
        return 'ORD-'.strtoupper(uniqid());
    }

    public static function getCustomerAddress(Order $order, string $type = 'shipping')
    {
        return $order->addresses()->where('type', $type)->first();
    }
}
