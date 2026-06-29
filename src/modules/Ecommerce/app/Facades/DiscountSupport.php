<?php

namespace Modules\Ecommerce\Facades;

use Illuminate\Support\Facades\Facade;

class DiscountSupport extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Modules\Ecommerce\Supports\DiscountSupport::class;
    }
}
