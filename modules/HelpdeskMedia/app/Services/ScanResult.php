<?php

namespace Modules\HelpdeskMedia\Services;

final class ScanResult
{
    public const CLEAN = 'clean';

    public const INFECTED = 'infected';

    public const UNAVAILABLE = 'unavailable';

    /** Antivirus apagado por configuración: no se ha intentado escanear. */
    public const SKIPPED = 'skipped';

    public function __construct(
        public readonly string $status,
        public readonly ?string $signature = null,
        public readonly ?string $reason = null,
    ) {}

    public function isInfected(): bool
    {
        return $this->status === self::INFECTED;
    }

    public function isUnavailable(): bool
    {
        return $this->status === self::UNAVAILABLE;
    }
}
