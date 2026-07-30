<?php

namespace Modules\HelpdeskTickets\Mail\Concerns;

use Modules\HelpdeskEmailLog\Mail\AddsEmailLogHeaders;

trait TracksTicketEmailLog
{
    use AddsEmailLogHeaders;

    public function getEmailLogModule(): string
    {
        return 'HelpdeskTickets';
    }

    public function getEmailLogEntityType(): string
    {
        return 'ticket';
    }

    public function getEmailLogEntityId(): int|string
    {
        return $this->ticket->id;
    }
}
