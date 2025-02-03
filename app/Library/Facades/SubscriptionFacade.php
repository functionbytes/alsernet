<?php

namespace App\Library\Facades;

use Illuminate\Support\Facades\Facade;
use App\Library\SubscriptionManager;

class SubscriptionFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SubscriptionManager::class;
    }
}
