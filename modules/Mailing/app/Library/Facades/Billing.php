<?php

namespace Modules\Mailing\Library\Facades;

use Modules\Mailing\Library\BillingManager;
use Illuminate\Support\Facades\Facade;

class Billing extends Facade
{
    protected static function getFacadeAccessor()
    {
        return BillingManager::class;
    }
}
