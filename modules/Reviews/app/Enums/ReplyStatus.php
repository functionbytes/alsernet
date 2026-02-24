<?php

namespace Modules\Reviews\Enums;

enum ReplyStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case PUBLISHED = 'published';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::APPROVED => 'Approved',
            self::PUBLISHED => 'Published',
            self::FAILED => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::APPROVED => 'warning',
            self::PUBLISHED => 'success',
            self::FAILED => 'danger',
        };
    }
}
