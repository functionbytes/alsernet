<?php

namespace Modules\Ecommerce\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Ecommerce\Enums\OrderStatus;
use Modules\Ecommerce\Events\OrderPlaced;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Supports\OrderHelper;

class OrderService
{
    public function createOrder(array $data, array $items): Order
    {
        $order = OrderHelper::createOrder($data);

        foreach ($items as $item) {
            $order->items()->create($item);
        }

        OrderPlaced::dispatch($order);

        return $order;
    }

    public function getOrders(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Order::query()
            ->with(['customer', 'items'])
            ->when($filters['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate($perPage);
    }

    public function updateStatus(Order $order, string $status): void
    {
        OrderHelper::updateOrderStatus($order, OrderStatus::from($status));
    }
}
