<?php

namespace Modules\Ecommerce\Services;

class HandleRemoveCouponService
{
    public function execute(): array
    {
        return [
            'success' => true,
            'amount' => 0,
            'message' => 'Cupón removido',
        ];
    }
}
