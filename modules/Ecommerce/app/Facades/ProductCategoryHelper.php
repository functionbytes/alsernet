<?php

namespace Modules\Ecommerce\Facades;

use Illuminate\Support\Facades\Facade;

class ProductCategoryHelper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Modules\Ecommerce\Supports\ProductCategoryHelper::class;
    }
}
