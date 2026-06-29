<?php

namespace Modules\Social\Enums;

enum PostStatus: string
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case PUBLISHING = 'publishing';
    case PUBLISHED = 'published';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::SCHEDULED => 'Programado',
            self::PUBLISHING => 'Publicando...',
            self::PUBLISHED => 'Publicado',
            self::FAILED => 'Fallido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::SCHEDULED => 'info',
            self::PUBLISHING => 'warning',
            self::PUBLISHED => 'success',
            self::FAILED => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'ti ti-file',
            self::SCHEDULED => 'ti ti-clock',
            self::PUBLISHING => 'ti ti-loader',
            self::PUBLISHED => 'ti ti-check',
            self::FAILED => 'ti ti-x',
        };
    }
}
