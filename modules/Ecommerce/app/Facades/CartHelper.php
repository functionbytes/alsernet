<?php

namespace Modules\Ecommerce\Facades;

use Illuminate\Support\Facades\Facade;

class CartHelper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Modules\Ecommerce\Supports\CartHelper::class;
    }
}
