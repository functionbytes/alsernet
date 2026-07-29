<?php

namespace Modules\Supplier\DTOs;

class ValidationResult
{
    public function __construct(
        public bool $valid,
        public array $errors = [],
        public array $warnings = []
    ) {}

    public static function success(): self
    {
        return new self(valid: true);
    }

    public static function failure(array $errors): self
    {
        return new self(valid: false, errors: $errors);
    }

    public static function warning(array $warnings): self
    {
        return new self(valid: true, warnings: $warnings);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function isInvalid(): bool
    {
        return ! $this->valid;
    }

    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    public function hasWarnings(): bool
    {
        return ! empty($this->warnings);
    }

    public function addError(string $error): self
    {
        $this->errors[] = $error;
        $this->valid = false;

        return $this;
    }

    public function addWarning(string $warning): self
    {
        $this->warnings[] = $warning;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
