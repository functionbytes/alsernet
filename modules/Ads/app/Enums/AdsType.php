<?php

namespace Modules\Ads\Enums;

enum AdsType: string
{
    case IMAGE = 'image';
    case GOOGLE_ADSENSE = 'google_adsense';

    public function label(): string
    {
        return match ($this) {
            self::IMAGE => 'Imagen',
            self::GOOGLE_ADSENSE => 'Google AdSense',
        };
    }
}
