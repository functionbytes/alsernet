<?php

namespace Modules\Core\Helpers;

use Modules\Core\Models\Setting;

class SiteHelper
{
    public static function getSiteName(): string
    {
        return Setting::get('site_name', config('app.name', 'Alsernet'));
    }

    public static function getSiteKeyword(): string
    {
        return Setting::get('site_keyword', 'E-Learning');
    }

    public static function getSiteTitle(): string
    {
        return self::getSiteName().' - '.self::getSiteKeyword();
    }
}
