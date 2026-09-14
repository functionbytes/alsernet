<?php

namespace Modules\Supplier\DTOs;

class ConnectionTestResult
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?int $responseTime = null,
        public array $metadata = []
    ) {}

    public static function success(string $message, ?int $responseTime = null, array $metadata = []): self
    {
        return new self(
            success: true,
            message: $message,
            responseTime: $responseTime,
            metadata: $metadata
        );
    }

    public static function failure(string $message, array $metadata = []): self
    {
        return new self(
            success: false,
            message: $message,
            responseTime: null,
            metadata: $metadata
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'response_time' => $this->responseTime,
            'metadata' => $this->metadata,
        ];
    }

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function isFailed(): bool
    {
        return ! $this->success;
    }

    public function hasMetadata(string $key): bool
    {
        return isset($this->metadata[$key]);
    }

    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }
}
