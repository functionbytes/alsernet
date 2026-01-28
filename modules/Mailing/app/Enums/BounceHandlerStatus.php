<?php

namespace Modules\Mailing\Enums;

/**
 * Bounce Handler Status Enum
 *
 * Represents the operational status of a bounce handler service.
 */
enum BounceHandlerStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Error = 'error';

    /**
     * Get the human-readable label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::Inactive => 'Inactivo',
            self::Error => 'Error',
        };
    }

    /**
     * Check if the bounce handler is operational
     */
    public function isOperational(): bool
    {
        return $this === self::Active;
    }
}
