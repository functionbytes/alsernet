<?php

namespace Modules\Core\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Monolog processor that redacts Bearer tokens and API keys
 * from log messages and context before they are written.
 */
class SensitiveDataMasker implements ProcessorInterface
{
    /** @var array<array{pattern: string, replacement: string}> */
    private array $patterns = [
        [
            'pattern' => '/Bearer\s+[A-Za-z0-9._\-]+/i',
            'replacement' => 'Bearer [REDACTED]',
        ],
        [
            'pattern' => '/"api_key":\s*"[^"]+"/i',
            'replacement' => '"api_key":"[REDACTED]"',
        ],
        [
            'pattern' => '/api[_-]?key[=:]\s*["\']?[A-Za-z0-9._\-]{8,}/i',
            'replacement' => 'api_key=[REDACTED]',
        ],
    ];

    /**
     * Context keys whose values must always be redacted regardless of content.
     *
     * @var array<int, string>
     */
    private array $sensitiveKeys = [
        'api_key',
        'apikey',
        'api-key',
        'token',
        'access_token',
        'refresh_token',
        'secret',
        'client_secret',
        'password',
        'authorization',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: $this->mask($record->message),
            context: $this->maskArray($record->context),
        );
    }

    private function mask(string $value): string
    {
        foreach ($this->patterns as ['pattern' => $pattern, 'replacement' => $replacement]) {
            $value = (string) preg_replace($pattern, $replacement, $value);
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    private function maskArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isSensitiveKey($key) && is_scalar($value)) {
                $data[$key] = '[REDACTED]';

                continue;
            }

            $data[$key] = match (true) {
                is_string($value) => $this->mask($value),
                is_array($value) => $this->maskArray($value),
                default => $value,
            };
        }

        return $data;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        return in_array($normalized, $this->sensitiveKeys, true);
    }
}
