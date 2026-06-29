<?php

namespace Modules\Social\Enums;

enum AccountStatus: int
{
    case DISABLED = 0;
    case ACTIVE = 1;
    case PAUSED = 2;
    case ERROR = 3;

    public function label(): string
    {
        return match ($this) {
            self::DISABLED => 'Deshabilitada',
            self::ACTIVE => 'Activa',
            self::PAUSED => 'Pausada',
            self::ERROR => 'Error',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DISABLED => 'secondary',
            self::ACTIVE => 'success',
            self::PAUSED => 'warning',
            self::ERROR => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DISABLED => 'ti ti-circle-off',
            self::ACTIVE => 'ti ti-circle-check',
            self::PAUSED => 'ti ti-player-pause',
            self::ERROR => 'ti ti-alert-circle',
        };
    }
}
