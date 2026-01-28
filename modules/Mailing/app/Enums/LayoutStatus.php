<?php

namespace Modules\Mailing\Enums;

enum LayoutStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::ARCHIVED => 'Archived',
        };
    }

    public function isUsable(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canActivate(): bool
    {
        return in_array($this, [self::INACTIVE, self::ARCHIVED]);
    }

    public function canDeactivate(): bool
    {
        return $this === self::ACTIVE;
    }
}
