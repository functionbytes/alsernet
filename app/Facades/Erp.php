<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\Services\ErpService;

class Erp extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ErpService::class;
    }

}
