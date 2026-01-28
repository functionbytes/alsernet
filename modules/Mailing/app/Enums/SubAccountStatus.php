<?php

namespace Modules\Mailing\Enums;

/**
 * Sub Account Status Enum
 *
 * Represents the operational status of a sub-account.
 */
enum SubAccountStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Inactive = 'inactive';

    /**
     * Get the human-readable label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::Suspended => 'Suspendido',
            self::Inactive => 'Inactivo',
        };
    }

    /**
     * Check if the sub-account is active and can be used
     */
    public function isUsable(): bool
    {
        return $this === self::Active;
    }

    /**
     * Check if the sub-account is suspended
     */
    public function isSuspended(): bool
    {
        return $this === self::Suspended;
    }
}
