<?php

namespace Modules\Erp\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Supplier\Services\Integrations\ErpService;

class Erp extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ErpService::class;
    }
}
