<?php

namespace Modules\Optimize\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isEnabled()
 * @method static \Modules\Optimize\Supports\Optimizer enable()
 * @method static \Modules\Optimize\Supports\Optimizer disable()
 *
 * @see \Modules\Optimize\Supports\Optimizer
 */
class Optimizer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Modules\Optimize\Supports\Optimizer::class;
    }
}
