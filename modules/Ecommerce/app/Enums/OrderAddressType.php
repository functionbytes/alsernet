<?php

namespace Modules\Ecommerce\Enums;

enum OrderAddressType: string
{
    case SHIPPING = 'shipping';
    case BILLING = 'billing';
}
