<?php

namespace Modules\HelpdeskTickets\Services\TicketDetail;

use Illuminate\Support\Collection;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketNote;
use Modules\HelpdeskTickets\Services\MentionService;

/**
 * Pestaña Notas del panel de detalle. Extraído de TicketDetailDataService
 * (30-sep-2026) al trocear ese servicio por sección de panel.
 */
class NotesBuilder
{
    public function __construct(
        private readonly MentionService $mentionService,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function build(Ticket $ticket): Collection
    {
        return TicketNote::where('ticket_id', $ticket->id)
            ->with('user')
            ->orderByDesc('is_pinned')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (TicketNote $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'color' => $n->color,
                'is_pinned' => (bool) $n->is_pinned,
                'author_name' => $n->user ? trim($n->user->firstname.' '.$n->user->lastname) : 'Agente',
                // Bloque "Menciones" del panel de notas. La mención no se
                // guarda en ninguna columna: MentionService la deduce del
                // texto al notificar, así que aquí se usa ese mismo servicio
                // para que panel y notificación no puedan discrepar.
                'mentions' => $this->mentionService->resolveMentions((string) $n->body)
                    ->map(fn ($u) => [
                        'id' => $u->id,
                        'name' => trim($u->firstname.' '.$u->lastname) ?: (string) $u->email,
                    ])->values()->all(),
                'created_at' => $n->created_at?->toIso8601String(),
                'created_at_human' => $n->created_at?->diffForHumans(),
            ])->values();
    }
}
