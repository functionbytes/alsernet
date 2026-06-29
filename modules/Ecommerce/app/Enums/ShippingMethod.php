<?php

namespace Modules\Ecommerce\Enums;

enum ShippingMethod: string
{
    case DEFAULT = 'default';
    case FREE = 'free';
    case FLAT_RATE = 'flat_rate';
    case PICKUP = 'pickup';
}
