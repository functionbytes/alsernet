<?php

namespace Modules\Ecommerce\Enums;

enum ShippingStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case DELIVERING = 'delivering';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}
