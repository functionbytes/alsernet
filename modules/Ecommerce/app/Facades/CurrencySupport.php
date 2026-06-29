<?php

namespace Modules\Ecommerce\Facades;

use Illuminate\Support\Facades\Facade;

class CurrencySupport extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Modules\Ecommerce\Supports\CurrencySupport::class;
    }
}
