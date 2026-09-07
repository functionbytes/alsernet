<?php

namespace Modules\HelpdeskTickets\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Notifications\TicketMentionNotification;

class MentionService
{
    /**
     * Parse @mentions in a message body and send notifications.
     */
    public function notifyMentions(string $body, Ticket $ticket): void
    {
        $currentUserId = auth()->id();

        foreach ($this->resolveMentions($body) as $user) {
            if ($user->id === $currentUserId) {
                continue;
            }

            $user->notify(new TicketMentionNotification($ticket, auth()->user()));
        }
    }

    /**
     * Agentes mencionados con @Nombre dentro de un texto, sin repetir.
     *
     * Se extrajo de notifyMentions() para que el panel de notas pueda pintar
     * a quién se mencionó: la mención no se guarda en ninguna columna, se
     * deduce del propio texto, así que el panel y la notificación tienen que
     * usar exactamente el mismo criterio o mostrarían cosas distintas.
     *
     * @return Collection<int, User>
     */
    public function resolveMentions(string $body): Collection
    {
        if (! preg_match_all('/@([\w][\w\s]*?)(?=[,.\n]|$)/u', $body, $matches)) {
            return collect();
        }

        $found = collect();

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

            if ($user && ! $found->contains('id', $user->id)) {
                $found->push($user);
            }
        }

        return $found;
    }
}
