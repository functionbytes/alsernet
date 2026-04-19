<?php

namespace Modules\Helpdesk\Exceptions;

use RuntimeException;

class LlmRateLimitException extends RuntimeException
{
    public function __construct(
        private readonly string $limitType,
        private readonly int $retryAfterSeconds = 60,
        string $message = ''
    ) {
        parent::__construct($message ?: $this->buildMessage());
    }

    public function getLimitType(): string
    {
        return $this->limitType;
    }

    public function getRetryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }

    private function buildMessage(): string
    {
        return match ($this->limitType) {
            'per_minute' => 'Has alcanzado el límite de solicitudes por minuto al asistente. Intenta de nuevo en un momento.',
            'per_5min' => 'Has enviado demasiados mensajes en los últimos 5 minutos. Espera antes de continuar.',
            'per_day' => 'Has alcanzado el límite diario de solicitudes al asistente.',
            default => 'Límite de solicitudes alcanzado. Intenta de nuevo más tarde.',
        };
    }
}
