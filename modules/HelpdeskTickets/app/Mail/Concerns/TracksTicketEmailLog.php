<?php

namespace Modules\HelpdeskTickets\Mail\Concerns;

use Modules\HelpdeskEmailActivity\Mail\AddsEmailLogHeaders;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * No usado por ningún Mailable real hoy (todos implementan TracksEmailLog
 * directamente — ver TicketMailable/TicketComposedMail/TicketReplyMail/
 * TicketCreatedMail) — se mantiene por si algún Mailable futuro quiere
 * componer este trait en vez de repetir los 3 métodos. getEmailLogEntityType()
 * devuelve el FQCN (Ticket::class), NO el string literal 'ticket' que tenía
 * antes: ese string desalineaba con HelpdeskEmailActivity config('entity_labels'/
 * 'entity_routes'), indexados por FQCN — mismo bug ya documentado y evitado a
 * propósito en TicketComposedMail.php.
 */
trait TracksTicketEmailLog
{
    use AddsEmailLogHeaders;

    public function getEmailLogModule(): string
    {
        return 'HelpdeskTickets';
    }

    public function getEmailLogEntityType(): string
    {
        return Ticket::class;
    }

    public function getEmailLogEntityId(): int|string
    {
        return $this->ticket->id;
    }
}
