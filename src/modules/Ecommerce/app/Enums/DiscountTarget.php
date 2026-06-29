<?php

namespace Modules\Ecommerce\Enums;

enum DiscountTarget: string
{
    case ALL_ORDERS = 'all-orders';
    case SPECIFIC_PRODUCT = 'specific-product';
    case PRODUCT_COLLECTION = 'product-collection';
    case CUSTOMERS = 'customers';
}
