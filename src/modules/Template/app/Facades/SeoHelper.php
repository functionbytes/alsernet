<?php

namespace Modules\Template\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Modules\Template\Helpers\SeoHelper setTitle(string $title)
 * @method static string getTitle()
 * @method static \Modules\Template\Helpers\SeoHelper setDescription(string $description)
 * @method static string getDescription()
 *
 * @see \Modules\Template\Helpers\SeoHelper
 */
class SeoHelper extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Modules\Template\Helpers\SeoHelper::class;
    }
}
