<?php

namespace Modules\Ecommerce\Enums;

enum DiscountType: string
{
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';
    case FREE_SHIPPING = 'free_shipping';
}
