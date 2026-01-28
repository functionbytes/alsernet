<?php

namespace Modules\Mailing\Enums;

/**
 * Feedback Loop Handler Status Enum
 *
 * Represents the operational status of a feedback loop handler service.
 */
enum FeedbackLoopHandlerStatus: string
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
     * Check if the feedback loop handler is operational
     */
    public function isOperational(): bool
    {
        return $this === self::Active;
    }
}
