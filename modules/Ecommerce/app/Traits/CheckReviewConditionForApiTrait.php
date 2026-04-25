<?php

namespace Modules\Ecommerce\Traits;

use Modules\Ecommerce\Models\Order;

trait CheckReviewConditionForApiTrait
{
    public function canReviewProductForApi(int $productId, int $customerId): bool
    {
        return Order::query()
            ->where('customer_id', $customerId)
            ->whereHas('items', function ($q) use ($productId) {
                $q->where('product_id', $productId);
            })
            ->where('status', 'completed')
            ->exists();
    }
}
