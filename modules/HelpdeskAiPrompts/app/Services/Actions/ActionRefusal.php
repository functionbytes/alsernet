<?php

namespace Modules\HelpdeskAiPrompts\Services\Actions;

use RuntimeException;

/**
 * Corte controlado de una acción. `publicMessage` es lo único que puede ver la
 * IA; `reason` es un código interno para el log/registro de ejecuciones.
 */
class ActionRefusal extends RuntimeException
{
    public const DENIED = 'denied';

    public const ERROR = 'error';

    public function __construct(
        public readonly string $status,
        public readonly string $publicMessage,
        public readonly string $reason,
    ) {
        parent::__construct($reason);
    }

    public static function denied(string $publicMessage, string $reason): self
    {
        return new self(self::DENIED, $publicMessage, $reason);
    }

    public static function error(string $reason, string $publicMessage = ActionExecutor::UNAVAILABLE): self
    {
        return new self(self::ERROR, $publicMessage, $reason);
    }
}
