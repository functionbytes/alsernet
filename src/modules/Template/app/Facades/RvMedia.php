<?php

namespace Modules\Template\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getImageUrl(?string $url, ?string $size = null, bool $hasDefaultImage = false, ?string $default = null)
 *
 * @see \Modules\Template\Helpers\RvMedia
 */
class RvMedia extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Modules\Template\Helpers\RvMedia::class;
    }
}
