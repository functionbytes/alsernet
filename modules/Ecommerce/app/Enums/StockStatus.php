<?php

namespace Modules\Ecommerce\Enums;

enum StockStatus: string
{
    case IN_STOCK = 'in_stock';
    case OUT_OF_STOCK = 'out_of_stock';
    case ON_BACKORDER = 'on_backorder';
}
