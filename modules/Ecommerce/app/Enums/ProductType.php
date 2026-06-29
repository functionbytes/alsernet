<?php

namespace Modules\Ecommerce\Enums;

enum ProductType: string
{
    case PHYSICAL = 'physical';
    case DIGITAL = 'digital';
    case GROUPED = 'grouped';
    case VARIABLE = 'variable';
}
