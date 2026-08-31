<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationMarkedAsSpam;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;

/**
 * Cierra el circulo entre los 2 mecanismos de bloqueo que hasta ahora vivian
 * separados: marcar una conversación como spam en el inbox (núcleo Helpdesk,
 * banea al cliente) ahora también añade su email a la lista negra de tickets
 * (HelpdeskTickets) — así un mismo spammer no hay que bloquearlo dos veces en
 * dos paneles distintos. Best-effort: sin email, con regla ya existente, o
 * con error de escritura, nunca revierte el marcado como spam en si.
 */
class AddSpamSenderToBlacklist
{
    public function handle(ConversationMarkedAsSpam $event): void
    {
        if (! helpdesk_tickets_enabled()) {
            return;
        }

        $email = $event->conversation->customer?->email;

        if (! $email) {
            return;
        }

        if (TicketEmailBlacklist::matches($email)) {
            return;
        }

        try {
            TicketEmailBlacklist::create([
                'type' => 'email',
                'value' => strtolower($email),
                'reason' => "Marcado como spam desde la conversación #{$event->conversation->id}",
                'is_active' => true,
                'added_by' => $event->markedByUserId,
            ]);
        } catch (\Throwable $e) {
            Log::warning('AddSpamSenderToBlacklist: no se pudo crear la regla de lista negra.', [
                'conversation_id' => $event->conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
