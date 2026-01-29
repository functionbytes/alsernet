<?php

namespace Modules\Mailing\Library\Facades;

use Modules\Mailing\Library\SubscriptionManager;
use Illuminate\Support\Facades\Facade;

class SubscriptionFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SubscriptionManager::class;
    }
}
