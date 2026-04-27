<?php

namespace Modules\Ecommerce\Enums;

enum ShippingRuleType: string
{
    case BASED_ON_PRICE = 'based_on_price';
    case BASED_ON_WEIGHT = 'based_on_weight';
    case BASED_ON_ZIPCODE = 'based_on_zipcode';
    case BASED_ON_LOCATION = 'based_on_location';
}
