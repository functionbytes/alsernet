<?php

namespace Modules\HelpdeskMedia\Services;

final class TranscriptResult
{
    public function __construct(
        public readonly ?string $text,
        public readonly ?string $language = null,
        public readonly ?string $model = null,
        public readonly ?string $error = null,
    ) {}

    public function ok(): bool
    {
        return $this->text !== null && $this->text !== '';
    }
}
