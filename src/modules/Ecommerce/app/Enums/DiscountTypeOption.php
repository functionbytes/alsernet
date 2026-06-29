<?php

namespace Modules\Ecommerce\Enums;

enum DiscountTypeOption: string
{
    case AMOUNT = 'amount';
    case PERCENTAGE = 'percentage';
    case SHIPPING = 'shipping';
    case SAME_PRICE = 'same-price';
}
