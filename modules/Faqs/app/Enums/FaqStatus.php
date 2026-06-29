<?php

namespace Modules\Faqs\Enums;

enum FaqStatus: string
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
