<?php

namespace Modules\HelpdeskTickets\Exceptions;

use RuntimeException;

class StaleTicketException extends RuntimeException
{
    public function __construct(
        public readonly ?string $expectedUpdatedAt,
        public readonly ?string $actualUpdatedAt,
    ) {
        parent::__construct('El ticket fue actualizado por otro usuario.');
    }
}
