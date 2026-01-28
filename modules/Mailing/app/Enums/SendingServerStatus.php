<?php

namespace Modules\Mailing\Enums;

/**
 * Sending Server Status Enum
 *
 * Represents the operational status of a sending server.
 */
enum SendingServerStatus: string
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
     * Check if the server is operational
     */
    public function isOperational(): bool
    {
        return $this === self::Active;
    }
}
