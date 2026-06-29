<?php

namespace Modules\Ecommerce\Facades;

use Illuminate\Support\Facades\Facade;

class FlashSaleSupport extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Modules\Ecommerce\Supports\FlashSaleSupport::class;
    }
}
