<?php

namespace Modules\Ecommerce\Services;

use Modules\Ecommerce\Models\Order;

class CreatePaymentForOrderService
{
    public function execute(Order $order, string $paymentMethod, array $data = []): array
    {
        $order->update([
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
        ]);

        return [
            'success' => true,
            'order' => $order,
            'payment_method' => $paymentMethod,
        ];
    }
}
