<?php

namespace Modules\Ecommerce\Facades;

use Illuminate\Support\Facades\Facade;

class InvoiceHelper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Modules\Ecommerce\Supports\InvoiceHelper::class;
    }
}
