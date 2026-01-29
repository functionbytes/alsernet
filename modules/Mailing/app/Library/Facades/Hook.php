<?php

namespace Modules\Mailing\Library\Facades;

use Modules\Mailing\Library\HookManager;
use Illuminate\Support\Facades\Facade;

class Hook extends Facade
{
    protected static function getFacadeAccessor()
    {
        return HookManager::class;
    }
}
