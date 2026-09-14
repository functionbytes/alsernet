{{-- Tab "Tickets" del right-panel del inbox.
     Propietario: HelpdeskTickets · Renderizado por Helpdesk vía slot.
     Espera $rpTickets (Collection) provista por TicketServiceContract::getCustomerTickets().
--}}
<div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="tickets">
    @php
        $canCreateTickets = app(\Modules\Helpdesk\Contracts\TicketServiceContract::class)->canCreateTickets();

        // Las claves SON el vocabulario del módulo (low|normal|high|urgent).
        // Cualquier otra cosa cae al fallback 'normal' de abajo, pero la clase
        // CSS prio-{valor} de la tarjeta no existiría: por eso el modal de
        // escalado ya no puede mandar prioridades fuera de esta lista.
        $ticketPriorityMap = [
            'low'    => ['label' => 'Baja',    'color' => '#10b981', 'bg' => 'rgba(16, 185, 129, 0.1)',  'icon' => 'fa-arrow-down'],
            'normal' => ['label' => 'Normal',  'color' => '#3b82f6', 'bg' => 'rgba(59, 130, 246, 0.1)',  'icon' => 'fa-equals'],
            'high'   => ['label' => 'Alta',    'color' => '#f59e0b', 'bg' => 'rgba(245, 158, 11, 0.1)',  'icon' => 'fa-arrow-up'],
            'urgent' => ['label' => 'Urgente', 'color' => '#ef4444', 'bg' => 'rgba(239, 68, 68, 0.1)',   'icon' => 'fa-fire'],
        ];

        // Stats — solo si hay tickets
        $tCounts = [
            'total'  => $rpTickets->count(),
            'open'   => $rpTickets->filter(fn ($t) => ! ($t->status?->is_closed ?? in_array(strtolower($t->status?->name ?? ''), ['cerrado','closed','resolved','resuelto']))) ->count(),
            'closed' => $rpTickets->filter(fn ($t) => ($t->status?->is_closed ?? in_array(strtolower($t->status?->name ?? ''), ['cerrado','closed','resolved','resuelto'])))->count(),
            'urgent' => $rpTickets->where('priority', 'urgent')->count(),
        ];
    @endphp

    @if($rpTickets->isEmpty())
        <div class="bv-tab-empty">
            <i class="far fa-ticket"></i>
            <div class="bv-tab-empty-title">Sin tickets relacionados</div>
            <div class="bv-tab-empty-sub">No hay tickets asociados a este cliente</div>
            @if($canCreateTickets)
                <button class="btn btn-sm btn-primary mt-3" data-bv-modal="create-ticket">
                    Crear primer ticket
                </button>
            @endif
        </div>
    @else
        {{-- Cabecera con contador --}}
        <div class="tk-panel-head">
            <span class="num">{{ $tCounts['total'] }}</span>
            <div class="meta">
                <span class="lbl">Tickets</span>
                <span class="sub">
                    {{ $tCounts['open'] }} abierto{{ $tCounts['open'] === 1 ? '' : 's' }}
                    @if($tCounts['urgent'] > 0)
                        · <span class="text-danger fw-semibold">{{ $tCounts['urgent'] }} urgente{{ $tCounts['urgent'] === 1 ? '' : 's' }}</span>
                    @endif
                </span>
            </div>
            @if($canCreateTickets)
                <button class="add-btn" data-bv-modal="create-ticket" title="Nuevo ticket" aria-label="Nuevo ticket">
                    <i class="fa-solid fa-plus"></i>
                </button>
            @endif
        </div>

        {{-- Filtros --}}
        <div class="tk-filter-row">
            <button class="media-pill on" data-bv-tickets-filter="all">
                Todos <span class="c">{{ $tCounts['total'] }}</span>
            </button>
            @if($tCounts['open'] > 0)
                <button class="media-pill" data-bv-tickets-filter="open">
                    <span class="bv-x80"></span>
                    Abiertos <span class="c">{{ $tCounts['open'] }}</span>
                </button>
            @endif
            @if($tCounts['closed'] > 0)
                <button class="media-pill" data-bv-tickets-filter="closed">
                    Cerrados <span class="c">{{ $tCounts['closed'] }}</span>
                </button>
            @endif
            @if($tCounts['urgent'] > 0)
                <button class="media-pill" data-bv-tickets-filter="urgent">
                    <i class="fas fa-fire bv-x27"></i>
                    Urgentes <span class="c">{{ $tCounts['urgent'] }}</span>
                </button>
            @endif
        </div>

        {{-- Lista de tickets --}}
        <div class="tk-list" id="bv-tickets-list">
            @foreach($rpTickets as $ticket)
                @php
                    $tStatusName   = $ticket->status?->name ?? 'Abierto';
                    $tIsClosed     = $ticket->status?->is_closed ?? in_array(strtolower($tStatusName), ['cerrado','closed','resolved','resuelto']);
                    $tPrio         = $ticketPriorityMap[$ticket->priority] ?? $ticketPriorityMap['normal'];
                    $tSubject      = $ticket->subject ?? $ticket->title ?? 'Sin título';
                    $tAssignee     = $ticket->assignee ?? null;
                    $tAssigneeName = $tAssignee
                        ? $tAssignee->fullName()
                        : null;
                    $tFilterTags   = 'all ' . ($tIsClosed ? 'closed' : 'open') . ($ticket->priority === 'urgent' ? ' urgent' : '');
                    $tFromThisConv = isset($rpConversationId) && $rpConversationId && (int) ($ticket->conversation_id ?? 0) === (int) $rpConversationId;
                @endphp
                {{-- Rediseño sep-2026 (dirección "A · Asunto primero").

                     El badge "De esta conversación" era una etiqueta de texto
                     completo en la misma fila que el chip de estado: en 340 px
                     se solapaban y el estado quedaba tapado. Y como casi todos
                     los tickets del panel nacen de la conversación abierta, el
                     badge se repetía en todas las tarjetas sin distinguir nada.
                     Ahora es el eslabón que precede al número, con su título.

                     El asunto sube a la primera línea porque es lo que
                     identifica el ticket; el número, que antes ocupaba el sitio
                     de honor, baja al renglón de metadatos.

                     Las clases .id y .title se conservan: el modal de detalle
                     las lee para pintar su estado de carga. --}}
                <button class="tk-card prio-{{ array_key_exists($ticket->priority, $ticketPriorityMap) ? $ticket->priority : 'normal' }} {{ $tIsClosed ? 'is-closed' : '' }}"
                        data-bv-modal="ticket"
                        data-ticket-id="{{ $ticket->id }}"
                        data-bv-ticket-tags="{{ $tFilterTags }}">
                    <div class="head">
                        <span class="title">{{ \Illuminate\Support\Str::limit($tSubject, 90) }}</span>
                        <span class="status">{{ $tStatusName }}</span>
                    </div>
                    <div class="meta">
                        @if($tFromThisConv)
                            <span class="link" title="Creado desde esta conversación"><i class="fas fa-link" aria-hidden="true"></i></span>
                        @endif
                        <span class="id">#{{ $ticket->ticket_number ?? $ticket->id }}</span>
                        @if($ticket->created_at)
                            <span class="dot" aria-hidden="true"></span>
                            <span class="age">{{ $ticket->created_at->diffForHumans(['short' => true]) }}</span>
                        @endif
                    </div>
                    <div class="foot">
                        @if($tAssigneeName)
                            <span class="seg"><i class="fa-regular fa-user"></i> {{ \Illuminate\Support\Str::limit($tAssigneeName, 16) }}</span>
                        @else
                            <span class="seg unassigned"><i class="fa-regular fa-user"></i> Sin asignar</span>
                        @endif
                        @if($ticket->category)
                            <span class="seg"><i class="fa-regular fa-folder"></i> {{ \Illuminate\Support\Str::limit($ticket->category->name ?? '', 16) }}</span>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>
    @endif
</div>
