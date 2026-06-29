<?php

namespace Modules\Ads\Enums;

enum AdsStatus: string
{
    case PUBLISHED = 'published';
    case DRAFT = 'draft';

    public function label(): string
    {
        return match ($this) {
            self::PUBLISHED => 'Publicado',
            self::DRAFT => 'Borrador',
        };
    }
}
