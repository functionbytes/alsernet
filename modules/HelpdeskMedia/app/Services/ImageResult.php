<?php

namespace Modules\HelpdeskMedia\Services;

final class ImageResult
{
    public const OPTIMIZED = 'optimized';

    public const ANIMATED = 'animated';

    public const UNSUPPORTED = 'unsupported';

    public const FAILED = 'failed';

    public function __construct(
        public readonly string $status,
        public readonly ?string $tempPath = null,
        public readonly ?string $mime = null,
        public readonly ?string $extension = null,
        public readonly ?int $width = null,
        public readonly ?int $height = null,
        public readonly ?int $bytes = null,
        public readonly bool $sourceIncompatible = false,
        public readonly bool $sourceHadGps = false,
        public readonly ?string $reason = null,
    ) {}

    public function isOptimized(): bool
    {
        return $this->status === self::OPTIMIZED && $this->tempPath !== null;
    }

    public function discard(): void
    {
        if ($this->tempPath !== null && is_file($this->tempPath)) {
            @unlink($this->tempPath);
        }
    }
}
