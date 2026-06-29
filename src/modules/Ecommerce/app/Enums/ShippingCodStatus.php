<?php

namespace Modules\Ecommerce\Enums;

enum ShippingCodStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
