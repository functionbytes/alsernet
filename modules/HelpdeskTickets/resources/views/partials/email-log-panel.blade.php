{{--
    Fragmento inyectado por TicketEmailLogPanelRenderer en el detalle de un
    email de HelpdeskEmailActivity (vía EntityPanelRegistry). NO extiende ningún
    layout — se embebe dentro de OTRA página de OTRO módulo, así que el
    estilo se mantiene neutro y mínimo (small, text-muted, sin iconos
    decorativos) en vez de arrastrar el CSS propio de tickets.

    Variables recibidas (ver TicketEmailLogPanelRenderer::render()):
    - $ticket: Modules\HelpdeskTickets\Models\Ticket, el ticket dueño del email.
    - $relatedTickets: Illuminate\Support\Collection<Ticket> — otros tickets
      del mismo cliente (ya excluye a $ticket, límite 10).
--}}
<div class="small">
    <div class="text-muted mb-2">
        Tickets relacionados de este mismo cliente
    </div>

    @if ($relatedTickets->isEmpty())
        <div class="text-muted">Sin otros tickets de este cliente.</div>
    @else
        <ul class="list-unstyled mb-0">
            @foreach ($relatedTickets as $related)
                <li class="mb-2">
                    <a href="{{ route('manager.helpdesk.tickets.show', $related->id) }}">
                        {{ $related->ticket_number ?? ('#'.$related->id) }}
                    </a>
                    — {{ $related->subject }}
                    <div class="text-muted">
                        {{ $related->status?->name ?? 'Sin estado' }}
                        · {{ $related->created_at?->format('Y-m-d H:i') }}
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
