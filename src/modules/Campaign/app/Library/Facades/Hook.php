<?php

namespace Modules\Campaign\Library\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Campaign\Library\HookManager;

class Hook extends Facade
{
    protected static function getFacadeAccessor()
    {
        return HookManager::class;
    }
}
