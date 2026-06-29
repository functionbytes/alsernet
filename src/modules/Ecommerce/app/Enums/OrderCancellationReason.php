<?php

namespace Modules\Ecommerce\Enums;

enum OrderCancellationReason: string
{
    case CUSTOMER = 'customer';
    case OUT_OF_STOCK = 'out_of_stock';
    case OTHER = 'other';
}
