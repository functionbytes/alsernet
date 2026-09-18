<?php

namespace Modules\HelpdeskEmailActivity\Exceptions;

use RuntimeException;

/**
 * Una redirección que apuntaba a un destino que OutboundUrlGuard no admite.
 *
 * Existe como excepción propia y no como RuntimeException a secas para poder
 * distinguirla en el pool: un enlace cortado aquí no es «no se pudo conectar»,
 * es «no se ha querido ir ahí», y se le enseña al agente como bloqueado con el
 * mismo código que un enlace que no pasa el guard de entrada.
 */
class BlockedRedirectException extends RuntimeException
{
    public function __construct(public readonly string $blockedUrl)
    {
        parent::__construct('Redirección bloqueada hacia un destino no permitido: '.$blockedUrl);
    }
}
