<?php

namespace Modules\Mailing\Enums;

enum CronjobStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case RUNNING = 'running';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::RUNNING => 'Running',
            self::FAILED => 'Failed',
        };
    }

    public function isRunnable(): bool
    {
        return in_array($this, [self::ACTIVE, self::INACTIVE]);
    }

    public function canRestart(): bool
    {
        return in_array($this, [self::FAILED, self::INACTIVE]);
    }

    public function isHealthy(): bool
    {
        return in_array($this, [self::ACTIVE, self::INACTIVE]);
    }
}
