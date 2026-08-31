<?php

namespace Modules\HelpdeskTickets\Services;

use App\Models\User;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Notifications\TicketMentionNotification;

class MentionService
{
    /**
     * Parse @mentions in a message body and send notifications.
     */
    public function notifyMentions(string $body, Ticket $ticket): void
    {
        if (! preg_match_all('/@([\w][\w\s]*?)(?=[,.\n]|$)/u', $body, $matches)) {
            return;
        }

        $currentUserId = auth()->id();
        $notified = [];

        foreach ($matches[1] as $name) {
            $name = trim($name);
            if (! $name) {
                continue;
            }

            // Mismo criterio de "quien es agente" que CatalogCacheService::agents():
            // rol helpdesk-agent + available. Antes exigia verified=1, que
            // ningun agente real tiene (es la verificacion de email de Auth),
            // asi que las menciones no encontraban a nadie del equipo.
            // Se mantiene la consulta en vez de filtrar la coleccion cacheada
            // porque el LIKE de MySQL resuelve mayusculas y acentos por
            // collation, cosa que un match en PHP no replica igual.
            $user = User::whereHas('roles', fn ($q) => $q->where('name', 'helpdesk-agent'))
                ->where('available', true)
                ->whereRaw("TRIM(CONCAT(firstname, ' ', lastname)) LIKE ?", [$name.'%'])
                ->first();

            if ($user && $user->id !== $currentUserId && ! in_array($user->id, $notified)) {
                $notified[] = $user->id;
                $user->notify(new TicketMentionNotification($ticket, auth()->user()));
            }
        }
    }
}
