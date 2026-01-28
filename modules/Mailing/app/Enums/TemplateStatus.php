<?php

namespace Modules\Mailing\Enums;

enum TemplateStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::ARCHIVED => 'Archived',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::DRAFT, self::PUBLISHED]);
    }

    public function canEdit(): bool
    {
        return $this === self::DRAFT;
    }

    public function canPublish(): bool
    {
        return $this === self::DRAFT;
    }
}
