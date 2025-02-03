<?php

namespace App\Library\Facades;

use Illuminate\Support\Facades\Facade;
use App\Library\BillingManager;

class Billing extends Facade
{
    protected static function getFacadeAccessor()
    {
        return BillingManager::class;
    }
}
