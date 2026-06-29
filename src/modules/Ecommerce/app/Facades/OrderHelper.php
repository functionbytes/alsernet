<?php

namespace Modules\Ecommerce\Facades;

use Illuminate\Support\Facades\Facade;

class OrderHelper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Modules\Ecommerce\Supports\OrderHelper::class;
    }
}
