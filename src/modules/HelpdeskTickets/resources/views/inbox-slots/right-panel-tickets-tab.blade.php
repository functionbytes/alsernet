{{-- Tab "Tickets" del right-panel del inbox.
     Propietario: HelpdeskTickets · Renderizado por Helpdesk vía slot.
     Espera $rpTickets (Collection) provista por TicketServiceContract::getCustomerTickets().
--}}
<div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="tickets">
    @php
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
            <button class="btn btn-sm btn-primary mt-3" data-bv-modal="create-ticket">
                <i class="fas fa-plus me-1"></i> Crear primer ticket
            </button>
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
            <button class="add-btn" data-bv-modal="create-ticket" title="Nuevo ticket">
                <i class="fa-solid fa-plus"></i>
            </button>
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
                        ? trim(($tAssignee->firstname ?? '').' '.($tAssignee->lastname ?? ''))
                        : null;
                    $tFilterTags   = 'all ' . ($tIsClosed ? 'closed' : 'open') . ($ticket->priority === 'urgent' ? ' urgent' : '');
                    $tFromThisConv = isset($rpConversationId) && $rpConversationId && (int) ($ticket->conversation_id ?? 0) === (int) $rpConversationId;
                @endphp
                <button class="tk-card prio-{{ $ticket->priority }} {{ $tIsClosed ? 'is-closed' : '' }}"
                        data-bv-modal="ticket"
                        data-ticket-id="{{ $ticket->id }}"
                        data-bv-ticket-tags="{{ $tFilterTags }}">
                    <div class="head">
                        <i class="fa-solid fa-bars bv-x81"></i>
                        <span class="id">#{{ $ticket->ticket_number ?? $ticket->id }}</span>
                        @if($tFromThisConv)
                            <span class="badge bg-primary-subtle text-primary" title="Creado desde esta conversación"><i class="fas fa-link me-1"></i>De esta conversación</span>
                        @endif
                        <span class="status">{{ $tStatusName }}</span>
                    </div>
                    <div class="title">{{ \Illuminate\Support\Str::limit($tSubject, 60) }}</div>
                    <div class="foot">
                        @if($tAssigneeName)
                            <span class="seg"><i class="fa-regular fa-user"></i> {{ \Illuminate\Support\Str::limit($tAssigneeName, 14) }}</span>
                        @else
                            <span class="unassigned">Sin asignar</span>
                        @endif
                        @if($ticket->category)
                            <span class="seg"><i class="fa-regular fa-folder"></i> {{ \Illuminate\Support\Str::limit($ticket->category->name ?? '', 12) }}</span>
                        @endif
                        @if($ticket->created_at)
                            <span class="seg bv-x69"><i class="fa-regular fa-clock"></i> {{ $ticket->created_at->diffForHumans(['short' => true]) }}</span>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>
    @endif
</div>
