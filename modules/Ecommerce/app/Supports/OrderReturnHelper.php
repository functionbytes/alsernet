<?php

namespace Modules\Ecommerce\Supports;

use Modules\Ecommerce\Models\OrderReturn;
use Modules\Ecommerce\Models\OrderReturnHistory;

class OrderReturnHelper
{
    public static function logHistory(OrderReturn $return, string $action, ?string $description = null): OrderReturnHistory
    {
        return OrderReturnHistory::query()->create([
            'order_return_id' => $return->id,
            'action' => $action,
            'description' => $description,
            'user_id' => auth()->id(),
        ]);
    }
}
