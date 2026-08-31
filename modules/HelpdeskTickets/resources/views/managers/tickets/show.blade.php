@extends('layouts.theme')

@section('title', 'Ticket #' . $ticket->ticket_number . ' - Helpdesk')

@section('page_header')
    @include('core::components.card', ['title' => 'Ticket #' . $ticket->ticket_number . ' - Helpdesk'])
@endsection

@section('content')
    {{-- Helpdesk header --}}
    <div class="bg-white border-bottom sticky-top z-10">
        <div class="d-flex align-items-center justify-content-between px-4 py-3">
            <div class="d-flex align-items-center">
                <a href="{{ route('manager.helpdesk.tickets.index', request()->only(['viewId', 'group', 'category'])) }}"
                   class="btn btn-light btn-sm me-3">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h5 class="mb-0 fw-bold">
                    <span class="badge badge-sm" style="background-color: {{ $ticket->category?->color ?? "#6c757d" }}">
                        <i class="{{ $ticket->category?->icon ?? "fas fa-tag" }} me-1"></i>{{ $ticket->category?->name ?? "Sin categoria" }}
                    </span>
                    <span class="text-muted mx-2">#{{ $ticket->ticket_number }}</span>
                    {{ Str::limit($ticket->subject, 50) }}
                </h5>
            </div>
            <div class="btn-group">
                @can('update', $ticket)
                    <a href="{{ route('manager.helpdesk.tickets.edit', $ticket->id) }}" class="btn btn-light btn-sm">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                @endcan
                @can('close', $ticket)
                    @if(!$ticket->isClosed())
                        <button type="button" class="btn btn-light btn-sm"
                                data-bs-toggle="modal" data-bs-target="#close-ticket-modal">
                            <i class="fas fa-times-circle"></i> Cerrar
                        </button>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    <div class="d-flex">
        {{-- Left sidebar: navigation --}}
        @include('helpdesktickets::managers.tickets.partials.sidebar')

        {{-- Tickets list (slim) --}}
        <div class="w-20 flex-shrink-0 border-end">
            <div class="p-2 border-bottom bg-light">
                <small class="text-muted fw-semibold">
                    <i class="fas fa-ticket-alt fs-5 me-1"></i> {{ $tickets->total() }} tickets
                </small>
            </div>
            <ul class="list-group list-group-flush ticket-slim-scroll" data-simplebar>
                @foreach($tickets as $t)
                    <li class="list-group-item list-group-item-action p-2 border-bottom {{ $t->id == $ticket->id ? 'active' : '' }}">
                        <a href="{{ route('manager.helpdesk.tickets.show-full', $t->id) }}" class="text-decoration-none d-block">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <small class="fw-semibold {{ $t->id == $ticket->id ? 'text-white' : 'text-dark' }}">
                                    #{{ $t->ticket_number }}
                                </small>
                                <small class="text-muted">{{ $t->created_at->diffForHumans(null, true) }}</small>
                            </div>
                            <small class="{{ $t->id == $ticket->id ? 'text-white-50' : 'text-muted' }} ticket-slim-subject">
                                {{ Str::limit($t->subject, 30) }}
                            </small>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Main: conversation --}}
        <div class="flex-fill border-end ticket-main-col">
            {{-- Ticket info bar --}}
            <div class="p-3 border-bottom bg-light">
                <h5 class="mb-2">{{ $ticket->subject }}</h5>
                <div class="d-flex align-items-center gap-3 text-muted flex-wrap">
                    <span><i class="fas fa-user me-1"></i>{{ $ticket->customer?->name ?? "Sin cliente" }}</span>
                    <span><i class="far fa-envelope me-1"></i>{{ $ticket->customer?->email ?? "" }}</span>
                    <span><i class="far fa-calendar me-1"></i>{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>

            {{-- Aviso de colisión de agentes (otro agente viendo/respondiendo) --}}
            <div id="collision-banner"
                class="alert alert-warning py-2 px-3 mb-0 border-0 border-bottom rounded-0 d-none small"
                data-heartbeat-url="{{ route('manager.helpdesk.tickets.presence.heartbeat', $ticket->id) }}"
                data-leave-url="{{ route('manager.helpdesk.tickets.presence.leave', $ticket->id) }}">
                <i class="fas fa-users me-1"></i><span id="collision-text"></span>
            </div>

            {{-- Messages thread --}}
            <div class="p-3 ticket-messages-scroll" data-simplebar id="messagesContainer">
                @forelse($ticket->items as $item)
                    @if($item->type == 'message' || $item->type == 'internal_note')
                        <div class="mb-3 {{ $item->isFromAgent() ? 'text-end' : '' }}">
                            <div class="d-inline-block text-start ticket-message-bubble">
                                {{-- Message header --}}
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    @if($item->isFromCustomer())
                                        <div class="avatar-md rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-semibold">
                                            {{ strtoupper(substr($item->sender_name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <strong class="d-block">{{ $item->sender_name }}</strong>
                                        <small class="text-muted">{{ $item->created_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                    @if($item->isFromAgent())
                                        <div class="avatar-md rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-semibold">
                                            {{ strtoupper(substr($item->sender_name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>

                                {{-- Message body --}}
                                <div class="card {{ $item->isFromAgent() ? 'bg-primary-subtle' : 'bg-light' }} {{ $item->type == 'internal_note' ? 'border-warning border-2' : '' }}">
                                    <div class="card-body p-3">
                                        @if($item->type == 'internal_note')
                                            <div class="badge bg-warning text-dark mb-2">
                                                <i class="fas fa-lock me-1"></i> Nota interna
                                            </div>
                                        @endif
                                        <div>{!! nl2br(e($item->body)) !!}</div>

                                        @if($item->attachment_urls)
                                            {{-- attachment_urls guarda rutas del disco PRIVADO, no
                                                 URLs públicas (cambió al mover los adjuntos fuera de
                                                 public/). Volcar la ruta cruda en el href daba 404;
                                                 hay que pasar por la ruta de descarga autorizada,
                                                 igual que hace TicketDetailDataController. --}}
                                            <div class="mt-2">
                                                @foreach($item->attachment_urls as $index => $attachment)
                                                    <a href="{{ route('manager.helpdesk.tickets.attachments.download', [$ticket, $item->id, $index]) }}" class="btn btn-sm btn-light me-2 mb-1">
                                                        <i class="fas fa-paperclip"></i> {{ basename($attachment) }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- System event --}}
                        <div class="text-center my-3">
                            <span class="badge bg-light text-dark border">
                                <i class="fas fa-info-circle me-1"></i>
                                {{ $item->event_label }}: {{ $item->body }}
                            </span>
                        </div>
                    @endif
                @empty
                    <div class="text-center py-5">
                        <i class="far fa-comment-slash fs-1 text-muted mb-3 d-block"></i>
                        <p class="text-muted">No hay mensajes en este ticket</p>
                    </div>
                @endforelse
            </div>

            {{-- Reply form --}}
            @can('update', $ticket)
                <div class="p-3 border-top bg-light" id="reply-form-wrapper">
                    <form action="{{ route('manager.helpdesk.tickets.messages.store', $ticket->id) }}" method="POST" enctype="multipart/form-data" id="reply-form">
                        @csrf

                        {{-- Toolbar --}}
                        <div class="d-flex align-items-center gap-2 mb-2">
                            {{-- Nota interna toggle --}}
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="is_internal" id="isInternal" value="1">
                                <label class="form-check-label small" for="isInternal">
                                    <i class="fas fa-lock me-1"></i>Nota interna
                                </label>
                            </div>

                            {{-- Canned replies dropdown --}}
                            @if($cannedReplies->isNotEmpty())
                                <div class="dropdown ms-auto">
                                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="cannedRepliesBtn" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-bolt me-1"></i>Respuestas rápidas
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm htk-dropdown-scroll" aria-labelledby="cannedRepliesBtn">
                                        @foreach($cannedReplies as $cr)
                                            <li>
                                                <button type="button" class="dropdown-item canned-reply-item py-2" data-content="{{ e($cr->content) }}">
                                                    <div class="fw-semibold small">{{ $cr->title }}</div>
                                                    @if($cr->short_code)
                                                        <small class="text-muted">/{{ $cr->short_code }}</small>
                                                    @endif
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if(helpdesk_agents_enabled())
                                <button type="button"
                                    @class(['btn', 'btn-light', 'btn-sm', 'ms-auto' => $cannedReplies->isEmpty()])
                                    id="ai-suggest-reply-btn"
                                    data-url="{{ route('manager.helpdesk.tickets.ai.suggest-reply', $ticket->id) }}">
                                    <i class="fas fa-wand-magic-sparkles me-1"></i>Sugerir respuesta
                                </button>
                            @endif
                        </div>

                        {{-- Internal note indicator --}}
                        <div id="internal-indicator" class="alert alert-warning py-2 mb-2 d-none small">
                            <i class="fas fa-lock me-1"></i> Esta nota es interna — el cliente no la verá
                        </div>

                        @if(helpdesk_helpcenter_enabled())
                            <div class="mb-2 border rounded" id="suggested-articles-box"
                                data-url="{{ route('manager.helpdesk.tickets.suggested-articles', $ticket->id) }}">
                                <button type="button" class="btn btn-link btn-sm text-decoration-none px-2 py-1"
                                    id="suggested-articles-toggle">
                                    <i class="fas fa-lightbulb me-1"></i>Artículos sugeridos
                                </button>
                                <div id="suggested-articles-list" class="px-2 pb-2 d-none"></div>
                            </div>
                        @endif

                        @if(helpdesk_agents_enabled())
                            {{-- Procedencia del borrador de IA: que plantilla se uso y que datos
                                 se consultaron de verdad, para que el agente pueda verificarlo
                                 antes de enviar. Se rellena desde JS. --}}
                            <div id="ai-reply-meta" class="alert alert-light border py-2 mb-2 d-none small"></div>
                        @endif

                        <div class="mb-2">
                            <textarea name="body" id="reply-body" class="form-control" rows="4" placeholder="Escribe tu respuesta..." required></textarea>
                        </div>

                        {{-- Attachments --}}
                        <div class="mb-2">
                            <input type="file" name="attachments[]" id="attachments" multiple class="d-none">
                            <label for="attachments" class="btn btn-light btn-sm">
                                <i class="fas fa-paperclip me-1"></i>Adjuntar archivo
                            </label>
                            @if(helpdesk_translate_enabled())
                                <button type="button" class="btn btn-light btn-sm" id="translate-reply-btn"
                                    data-url="{{ route('manager.helpdesk.tickets.translate', $ticket->id) }}">
                                    <i class="fas fa-language me-1"></i>Traducir borrador
                                </button>
                            @endif
                            <div id="attachment-preview" class="d-flex flex-wrap gap-2 mt-2"></div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light btn-sm" onclick="document.getElementById('reply-body').value=''">
                                Limpiar
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="far fa-paper-plane me-1"></i>Enviar respuesta
                            </button>
                        </div>
                    </form>
                </div>
            @endcan
        </div>

        {{-- Right sidebar: details, actions, time, rating, activity --}}
        <div class="flex-shrink-0 ticket-info-col">

            {{-- SLA status widget --}}
            @if($ticket->slaPolicy)
                <div class="p-3 border-bottom">
                    <h6 class="fw-bold mb-3"><i class="fas fa-stopwatch me-1"></i> SLA</h6>

                    @php
                        $now = now();
                        $slaItems = [
                            [
                                'label'    => 'Primera respuesta',
                                'due'      => $ticket->sla_first_response_due_at,
                                'breached' => $ticket->sla_first_response_breached,
                                'done'     => $ticket->first_response_at,
                            ],
                            [
                                'label'    => 'Proxima respuesta',
                                'due'      => $ticket->sla_next_response_due_at,
                                'breached' => $ticket->sla_next_response_breached,
                                'done'     => null,
                            ],
                            [
                                'label'    => 'Resolucion',
                                'due'      => $ticket->sla_resolution_due_at,
                                'breached' => $ticket->sla_resolution_breached,
                                'done'     => $ticket->resolved_at,
                            ],
                        ];
                    @endphp

                    @foreach($slaItems as $item)
                        @continue(!$item['due'])
                        @php
                            $dueAt    = $item['due'];
                            $remaining = $now->diffInSeconds($dueAt, false);
                            $total    = $ticket->created_at->diffInSeconds($dueAt);
                            $pct      = $item['done'] ? 100 : ($total > 0 ? max(0, min(100, ($remaining / $total) * 100)) : 0);
                            $barClass = match(true) {
                                (bool) $item['breached'] => 'bg-danger',
                                $pct < 10               => 'bg-danger',
                                $pct < 50               => 'bg-warning',
                                default                 => 'bg-success',
                            };
                        @endphp

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="fw-semibold">{{ $item['label'] }}</small>
                                @if($item['breached'])
                                    <span class="badge bg-danger">Incumplido</span>
                                @elseif($item['done'])
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Cumplido</span>
                                @elseif($remaining < 0)
                                    <span class="badge bg-danger">Vencido</span>
                                @else
                                    <small class="text-muted" data-sla-countdown="{{ $dueAt->toIso8601String() }}">
                                        {{ $dueAt->diffForHumans() }}
                                    </small>
                                @endif
                            </div>
                            <div class="progress htk-progress-xs">
                                <div class="progress-bar {{ $barClass }}" role="progressbar"
                                     style="width: {{ $pct }}%" aria-valuenow="{{ $pct }}"
                                     aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    @endforeach

                    @if($ticket->sla_paused_at)
                        <div class="alert alert-warning py-2 px-3 mb-0">
                            <small><i class="fas fa-pause me-1"></i> SLA pausado</small>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Ticket links widget --}}
            <div class="p-3 border-bottom">
                <h6 class="fw-bold mb-3"><i class="fas fa-link me-1"></i> Tickets relacionados</h6>
                @php $allLinks = $ticket->links()->with('linkedTicket')->get(); @endphp
                @forelse($allLinks as $link)
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1 me-2">
                            <span class="badge bg-secondary-subtle text-secondary me-1">{{ $link->link_type }}</span>
                            <a href="{{ route('manager.helpdesk.tickets.show-full', $link->linkedTicket) }}" class="fw-semibold">
                                #{{ $link->linkedTicket->ticket_number }}
                            </a>
                            <small class="text-muted d-block">{{ Str::limit($link->linkedTicket->subject, 40) }}</small>
                        </div>
                        <form action="{{ route('manager.helpdesk.tickets.unlink', [$ticket->id, $link->id]) }}" method="POST" class="flex-shrink-0">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Eliminar enlace">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-muted small mb-3">Sin tickets enlazados.</p>
                @endforelse

                <form action="{{ route('manager.helpdesk.tickets.link', $ticket->id) }}" method="POST" class="mt-2">
                    @csrf
                    <div class="row g-1">
                        <div class="col-5">
                            <input type="number" class="form-control form-control-sm" name="linked_ticket_id"
                                   placeholder="ID del ticket..." min="1" required>
                        </div>
                        <div class="col-4">
                            <select name="link_type" class="form-select form-select-sm select2">
                                <option value="related">Relacionado</option>
                                <option value="duplicate_of">Duplicado</option>
                                <option value="blocks">Bloquea</option>
                                <option value="blocked_by">Bloqueado por</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <button type="submit" class="btn btn-sm btn-primary w-100" title="Enlazar">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    @error('linked_ticket_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </form>
            </div>

            {{-- Merge widget --}}
            @can('manager.helpdesk.tickets.update')
            <div class="p-3 border-bottom">
                <h6 class="fw-bold mb-2"><i class="fas fa-object-group me-1"></i> Fusionar ticket</h6>
                <p class="text-muted small mb-2">Fusionar este ticket en otro existente. Los mensajes se moverán y este ticket se cerrará.</p>
                <form action="{{ route('manager.helpdesk.tickets.merge', $ticket) }}" method="POST"
                      id="merge-ticket-form">
                    @csrf
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">#</span>
                        <input type="number" name="merge_into_id" class="form-control"
                               placeholder="ID del ticket destino" min="1" required>
                        <button type="submit" class="btn btn-warning" title="Fusionar">
                            <i class="fas fa-object-group"></i>
                        </button>
                    </div>
                    @error('merge_into_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </form>
            </div>
            @endcan

            {{-- Ticket details --}}
            <div class="p-3 border-bottom">
                <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-1"></i> Detalles</h6>

                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Estado</small>
                    <span class="badge" style="background-color: {{ $ticket->status?->color ?? "#6c757d" }}">
                        {{ $ticket->status?->name ?? "Sin estado" }}
                    </span>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Prioridad</small>
                    <span class="badge bg-{{ $ticket->priority_color }}">{{ ucfirst($ticket->priority) }}</span>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Categoria</small>
                    <span class="badge" style="background-color: {{ $ticket->category?->color ?? "#6c757d" }}">
                        <i class="{{ $ticket->category?->icon ?? "fas fa-tag" }} me-1"></i>{{ $ticket->category?->name ?? "Sin categoria" }}
                    </span>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Asignado a</small>
                    @if($ticket->assignee)
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-xs rounded-circle bg-success text-white d-flex align-items-center justify-content-center">
                                {{ strtoupper(substr($ticket->assignee?->full_name ?? "Sin asignar", 0, 1)) }}
                            </div>
                            <span>{{ $ticket->assignee?->full_name ?? "Sin asignar" }}</span>
                        </div>
                    @else
                        <span class="text-muted"><i class="fas fa-user-times me-1"></i> Sin asignar</span>
                    @endif
                </div>

                @if($ticket->group)
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">Grupo</small>
                        <span><i class="fas fa-user-group me-1"></i>{{ $ticket->group?->name ?? "Sin grupo" }}</span>
                    </div>
                @endif

                <div class="mb-3">
                    <small class="text-muted d-block mb-1">Origen</small>
                    @php
                        $sourceIcons = [
                            'manager'      => 'fas fa-desktop',
                            'widget'       => 'fas fa-comment',
                            'portal'       => 'fas fa-globe',
                            'api'          => 'fas fa-code',
                            'email'        => 'fas fa-envelope',
                            'conversation' => 'fas fa-comments',
                            'chatflow'     => 'fas fa-robot',
                            'social'       => 'fas fa-share-nodes',
                            'contacts'     => 'fas fa-address-book',
                            'web_form'     => 'fas fa-file-lines',
                            'formulario'   => 'fas fa-file-lines',
                        ];
                        $sourceIcon = $sourceIcons[$ticket->source] ?? 'fas fa-question-circle';
                    @endphp
                    <span><i class="{{ $sourceIcon }} me-1"></i>{{ ucfirst($ticket->source) }}</span>
                </div>

                @if($ticket->conversation)
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">Conversación de origen</small>
                        <a href="{{ route('manager.helpdesk.conversations.show', $ticket->conversation) }}"
                            class="text-decoration-none">
                            <i class="fas fa-comments me-1"></i>{{ $ticket->conversation->subject ?? 'Conversación #'.$ticket->conversation->id }}
                        </a>
                    </div>
                @endif

                @if($qualityReview ?? null)
                    {{-- Revisión de calidad. Se muestra al agente evaluado, no solo al
                         gestor: una nota sobre tu trabajo que no puedes ver ni discutir
                         no es una métrica. --}}
                    <div class="mb-3 p-2 border rounded bg-body-tertiary" id="ai-review-box"
                        data-url="{{ route('manager.helpdesk.tickets.ai.dispute-review', $ticket->id) }}">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <small class="text-muted fw-bold"><i class="fas fa-clipboard-check me-1"></i>Revisión de calidad</small>
                            <span class="badge bg-secondary-subtle text-secondary">{{ $qualityReview->score }}/5</span>
                        </div>

                        @if($qualityReview->summary)
                            <div class="small">{{ $qualityReview->summary }}</div>
                        @endif

                        @if($qualityReview->issues)
                            <ul class="small text-muted mt-2 mb-0 ps-3">
                                @foreach($qualityReview->issues as $issue)
                                    <li>{{ $issue }}</li>
                                @endforeach
                            </ul>
                        @endif

                        @if($qualityReview->disputed)
                            <div class="small text-muted mt-2">Disputada — no cuenta para las medias.</div>
                        @else
                            <button type="button" class="btn btn-sm btn-light w-100 mt-2" id="ai-review-dispute">
                                No estoy de acuerdo
                            </button>
                        @endif
                    </div>
                @endif

                @if(helpdesk_agents_enabled() && config('helpdeskagents.ticket_similarity.enabled'))
                    {{-- Posibles duplicados. Sugiere, no fusiona: el enlace lleva al
                         ticket para que el agente lo compruebe. Se carga en diferido
                         para no retrasar la ficha. --}}
                    <div class="mb-3 p-2 border rounded bg-body-tertiary d-none" id="ai-duplicates-box"
                        data-url="{{ route('manager.helpdesk.tickets.ai.duplicates', $ticket->id) }}">
                        <small class="text-muted d-block mb-2 fw-bold"><i class="fas fa-clone me-1"></i>Posibles duplicados</small>
                        <div id="ai-duplicates-list" class="small"></div>
                    </div>
                @endif

                @if(helpdesk_agents_enabled())
                    {{-- Resumen del caso bajo demanda. Reusa
                         manager.helpdesk.tickets.summary, que ya existia enrutado
                         y cacheado 10 minutos pero sin consumidor en esta vista. --}}
                    <div class="mb-3 p-2 border rounded bg-body-tertiary" id="ai-summary-box"
                        data-url="{{ route('manager.helpdesk.tickets.summary', $ticket->id) }}">
                        <div class="d-flex align-items-center justify-content-between">
                            <small class="text-muted fw-bold"><i class="fas fa-align-left me-1"></i>Resumen del caso</small>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" id="ai-summary-btn">Generar</button>
                        </div>
                        <div id="ai-summary-text" class="small mt-2 d-none"></div>
                    </div>
                @endif

                @if($ticket->aiSuggestedCategory || $ticket->ai_suggested_priority)
                    <div class="mb-3 p-2 border rounded bg-body-tertiary" id="ai-suggestions"
                        data-url="{{ route('manager.helpdesk.tickets.apply-ai-suggestion', $ticket->id) }}">
                        <small class="text-muted d-block mb-2 fw-bold"><i class="fas fa-wand-magic-sparkles me-1"></i>Sugerencias de IA</small>
                        @if($ticket->aiSuggestedCategory)
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small">Categoría: <span class="fw-semibold">{{ $ticket->aiSuggestedCategory->name }}</span></span>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 apply-ai-suggestion" data-field="category">Aplicar</button>
                            </div>
                        @endif
                        @if($ticket->ai_suggested_priority)
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="small">Prioridad: <span class="fw-semibold">{{ ucfirst($ticket->ai_suggested_priority) }}</span></span>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 apply-ai-suggestion" data-field="priority">Aplicar</button>
                            </div>
                        @endif
                    </div>
                @endif

                @can('update', $ticket)
                    <div class="mb-3 p-2 border rounded" id="followups-box"
                        data-store-url="{{ route('manager.helpdesk.tickets.followups.store', $ticket->id) }}"
                        data-base-url="{{ url('panel/helpdesk/tickets/'.$ticket->id.'/followups') }}">
                        <small class="text-muted d-block mb-2 fw-bold"><i class="fas fa-bell me-1"></i>Seguimientos</small>

                        <div id="followups-list">
                            @forelse($ticket->followups as $followup)
                                <div class="d-flex align-items-start justify-content-between gap-2 py-1 border-bottom" data-followup-id="{{ $followup->id }}">
                                    <div class="small">
                                        <span class="fw-semibold">{{ $followup->scheduled_at?->format('d/m/Y H:i') }}</span>
                                        @if($followup->note)<div class="text-muted">{{ $followup->note }}</div>@endif
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light py-0 px-1 cancel-followup" title="Cancelar"><i class="fas fa-xmark"></i></button>
                                </div>
                            @empty
                                <div class="text-muted small py-1" id="followups-empty">Sin seguimientos programados.</div>
                            @endforelse
                        </div>

                        <div class="mt-2">
                            <input type="datetime-local" class="form-control form-control-sm mb-1" id="followup-when">
                            <input type="text" class="form-control form-control-sm mb-1" id="followup-note" maxlength="1000" placeholder="Nota (opcional)">
                            <button type="button" class="btn btn-sm btn-outline-primary w-100" id="add-followup">
                                <i class="fas fa-plus me-1"></i>Programar seguimiento
                            </button>
                        </div>
                    </div>

                    {{-- Respuestas programadas (send later) --}}
                    <div class="mb-3 p-2 border rounded" id="scheduled-box"
                        data-store-url="{{ route('manager.helpdesk.tickets.scheduled-replies.store', $ticket->id) }}"
                        data-index-url="{{ route('manager.helpdesk.tickets.scheduled-replies.index', $ticket->id) }}"
                        data-base-url="{{ url('panel/helpdesk/tickets/'.$ticket->id.'/scheduled-replies') }}">
                        <small class="text-muted d-block mb-2 fw-bold"><i class="fas fa-clock me-1"></i>Respuestas programadas</small>
                        <div id="scheduled-list">
                            <div class="text-muted small py-1" id="scheduled-empty">Sin respuestas programadas.</div>
                        </div>
                        <div class="mt-2">
                            <textarea class="form-control form-control-sm mb-1" id="scheduled-body" rows="2" maxlength="20000" placeholder="Respuesta a enviar..."></textarea>
                            <input type="datetime-local" class="form-control form-control-sm mb-1" id="scheduled-when">
                            <button type="button" class="btn btn-sm btn-outline-primary w-100" id="add-scheduled">
                                <i class="fas fa-paper-plane me-1"></i>Programar envío
                            </button>
                        </div>
                    </div>

                    {{-- Conversaciones laterales (side conversations) --}}
                    <div class="mb-3 p-2 border rounded" id="side-box"
                        data-index-url="{{ route('manager.helpdesk.tickets.side-conversations.index', $ticket->id) }}"
                        data-store-url="{{ route('manager.helpdesk.tickets.side-conversations.store', $ticket->id) }}"
                        data-base-url="{{ url('panel/helpdesk/tickets/'.$ticket->id.'/side-conversations') }}">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <small class="text-muted fw-bold"><i class="fas fa-comments me-1"></i>Conversaciones laterales</small>
                            <button type="button" class="btn btn-sm btn-light py-0 px-1" data-bs-toggle="modal" data-bs-target="#sideConversationModal" title="Nueva">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div id="side-list">
                            <div class="text-muted small py-1" id="side-empty">Sin conversaciones laterales.</div>
                        </div>
                    </div>
                @endcan

                @if($ticket->custom_fields)
                    <div class="mb-3">
                        <small class="text-muted d-block mb-2 fw-bold">Campos personalizados</small>
                        @foreach($ticket->custom_fields as $key => $value)
                            <div class="mb-2">
                                <small class="text-muted d-block">{{ ucfirst(str_replace('_', ' ', $key)) }}</small>
                                <span class="small">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Macros --}}
            <div class="p-3 border-bottom">
                <h6 class="fw-bold mb-2"><i class="fas fa-bolt me-1 text-warning"></i> Macros</h6>
                <select class="form-select form-select-sm select2 mb-2" id="macro-select">
                    <option value="">Selecciona macro...</option>
                </select>
                <button class="btn btn-sm btn-primary w-100" id="macro-apply-btn" disabled>
                    <i class="fas fa-play me-1"></i> Aplicar
                </button>
            </div>

            {{-- Actions --}}
            <div class="p-3 border-bottom">
                <h6 class="fw-bold mb-3"><i class="fas fa-bolt me-1"></i> Acciones</h6>

                @can('resolve', $ticket)
                    @if(!$ticket->isResolved() && !$ticket->isClosed())
                        <form action="{{ route('manager.helpdesk.tickets.resolve', $ticket->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="far fa-check-circle me-1"></i> Marcar como resuelto
                            </button>
                        </form>
                    @endif
                @endcan

                @can('close', $ticket)
                    @if(!$ticket->isClosed())
                        <button type="button" class="btn btn-secondary btn-sm w-100 mb-2"
                                data-bs-toggle="modal" data-bs-target="#close-ticket-modal">
                            <i class="fas fa-times-circle me-1"></i> Cerrar ticket
                        </button>
                    @endif
                @endcan

                @can('reopen', $ticket)
                    @if($ticket->isClosed())
                        <form action="{{ route('manager.helpdesk.tickets.reopen', $ticket->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-sync-alt me-1"></i> Reabrir ticket
                            </button>
                        </form>
                    @endif
                @endcan

                @can('update', $ticket)
                    <div id="snooze-actions"
                        data-snooze-url="{{ route('manager.helpdesk.tickets.snooze', $ticket->id) }}"
                        data-unsnooze-url="{{ route('manager.helpdesk.tickets.unsnooze', $ticket->id) }}">
                        @if($ticket->isSnoozed())
                            <button type="button" class="btn btn-outline-warning btn-sm w-100 mb-2" id="unsnooze-btn">
                                <i class="fas fa-bell me-1"></i> Reactivar (pospuesto hasta {{ $ticket->snoozed_until?->format('d/m H:i') }})
                            </button>
                        @elseif(!$ticket->isClosed())
                            <button type="button" class="btn btn-light btn-sm w-100 mb-2"
                                    data-bs-toggle="modal" data-bs-target="#snooze-ticket-modal">
                                <i class="fas fa-clock me-1"></i> Posponer
                            </button>
                        @endif
                    </div>
                @endcan

                @can('archive', $ticket)
                    @if($ticket->isClosed() && !$ticket->is_archived)
                        <button type="button" class="btn btn-light btn-sm w-100 mb-2"
                                data-bs-toggle="modal" data-bs-target="#archive-ticket-modal">
                            <i class="fas fa-archive me-1"></i> Archivar
                        </button>
                    @endif
                @endcan

                @can('delete', $ticket)
                    <button type="button" class="btn btn-outline-danger btn-sm w-100 mb-2"
                            data-bs-toggle="modal" data-bs-target="#delete-ticket-modal">
                        <i class="fas fa-trash me-1"></i> Eliminar ticket
                    </button>
                @endcan

                @can('helpdesk.tickets.settings')
                    @if($ticket->customer?->email)
                        @if($blacklistMatch ?? null)
                            <div class="alert alert-warning py-2 px-3 small mb-2">
                                <i class="fas fa-ban me-1"></i> Remitente en lista negra
                                (regla: {{ $blacklistMatch->value }})
                            </div>
                        @else
                            <button type="button" class="btn btn-outline-danger btn-sm w-100 mb-2"
                                    data-bs-toggle="modal" data-bs-target="#block-sender-modal">
                                <i class="fas fa-ban me-1"></i> Bloquear remitente
                            </button>
                        @endif
                    @endif
                @endcan
            </div>

            {{-- Time tracking --}}
            <div class="card mb-0 border-0 border-top rounded-0">
                <div class="card-header d-flex justify-content-between align-items-center px-3 py-2 bg-white border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-clock me-2"></i>Time tracking</h6>
                    <span id="total-time-badge" class="badge bg-primary">...</span>
                </div>
                <div class="card-body p-0">
                    <div id="time-entries-list" class="list-group list-group-flush time-entries-scroll"></div>
                    <form id="log-time-form" class="p-2 border-top">
                        @csrf
                        <div class="row g-1 mb-1">
                            <div class="col-5">
                                <input type="number" id="time-minutes" class="form-control form-control-sm"
                                       placeholder="Minutos (1-480)" min="1" max="480">
                            </div>
                            <div class="col-7">
                                <input type="text" id="time-description" class="form-control form-control-sm"
                                       placeholder="Descripcion (opcional)">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="fas fa-plus me-1"></i> Registrar tiempo
                        </button>
                    </form>
                </div>
            </div>

            {{-- Customer rating --}}
            @if($ticket->rated_at)
                <div class="card mb-0 border-0 border-top rounded-0">
                    <div class="card-header px-3 py-2 bg-white border-bottom">
                        <h6 class="mb-0"><i class="fas fa-star me-2 text-warning"></i>Valoracion del cliente</h6>
                    </div>
                    <div class="card-body px-3 py-2">
                        <div class="d-flex align-items-center mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star {{ $i <= $ticket->rating ? 'text-warning' : 'text-muted' }} me-1"></i>
                            @endfor
                            <span class="ms-2 fw-bold">{{ $ticket->rating }}/5</span>
                        </div>
                        @if($ticket->rating_comment)
                            <p class="text-muted mb-1">"{{ $ticket->rating_comment }}"</p>
                        @endif
                        <small class="text-muted">Valorado {{ $ticket->rated_at->diffForHumans() }}</small>
                    </div>
                </div>
            @endif

            {{-- Activity timeline --}}
            <div class="p-3 border-top">
                <h6 class="fw-bold mb-3"><i class="fas fa-stream me-1"></i> Actividad</h6>
                @if($history->isNotEmpty())
                    <div class="timeline-sm">
                        @foreach($history as $entry)
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <span class="badge bg-{{ $entry->action_color }}-subtle text-{{ $entry->action_color }} border border-{{ $entry->action_color }}-subtle mt-1 flex-shrink-0 htk-badge-xs">
                                    {{ $entry->action_label }}
                                </span>
                                <div>
                                    <small class="d-block">{{ $entry->description }}</small>
                                    <small class="text-muted">{{ $entry->created_at->format('d/m/Y H:i') }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted small mb-0">Sin registros de actividad.</p>
                @endif
            </div>

            {{-- Email history --}}
            @if($ticketMails->isNotEmpty())
                <div class="p-3 border-top">
                    <h6 class="fw-bold mb-3"><i class="fas fa-envelope me-1"></i> Emails enviados</h6>
                    @foreach($ticketMails as $mail)
                        <div class="mb-3 border rounded p-2 bg-white">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge bg-{{ $mail->status_color }}-subtle text-{{ $mail->status_color }} border border-{{ $mail->status_color }}-subtle small">
                                    {{ $mail->status_label }}
                                </span>
                                <small class="text-muted">{{ $mail->created_at->format('d/m/Y H:i') }}</small>
                            </div>
                            <div class="small fw-semibold text-truncate">{{ $mail->subject }}</div>
                            <div class="small text-muted">
                                <span class="me-2"><i class="fas fa-arrow-right me-1 text-success"></i>{{ $mail->to }}</span>
                            </div>
                            @if($mail->body_text)
                                <div class="small text-muted mt-1 text-truncate htk-email-preview">
                                    {{ Str::limit($mail->body_text, 120) }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Close ticket modal --}}
    @can('close', $ticket)
        @if(!$ticket->isClosed())
            <div class="modal fade" id="close-ticket-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Cerrar ticket</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0">¿Deseas cerrar este ticket? El cliente sera notificado.</p>
                        </div>
                        <div class="modal-footer d-flex flex-column gap-2">
                            <form action="{{ route('manager.helpdesk.tickets.close', $ticket->id) }}" method="POST" class="w-100">
                                @csrf
                                <button type="submit" class="btn btn-secondary w-100">Cerrar ticket</button>
                            </form>
                            <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endcan

    {{-- Archive ticket modal --}}
    @can('archive', $ticket)
        @if($ticket->isClosed() && !$ticket->is_archived)
            <div class="modal fade" id="archive-ticket-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Archivar ticket</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0">¿Deseas archivar este ticket?</p>
                        </div>
                        <div class="modal-footer d-flex flex-column gap-2">
                            <form action="{{ route('manager.helpdesk.tickets.archive', $ticket->id) }}" method="POST" class="w-100">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100">Archivar</button>
                            </form>
                            <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endcan

    {{-- Delete ticket modal --}}
    @can('delete', $ticket)
        <div class="modal fade" id="delete-ticket-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Eliminar ticket</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">¿Deseas eliminar el ticket <strong>#{{ $ticket->ticket_number }}</strong>? Esta accion no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer d-flex flex-column gap-2">
                        <form action="{{ route('manager.helpdesk.tickets.destroy', $ticket->id) }}" method="POST" class="w-100">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100">Eliminar</button>
                        </form>
                        <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    {{-- Block sender modal --}}
    @can('helpdesk.tickets.settings')
        @if($ticket->customer?->email && ! ($blacklistMatch ?? null))
            @php $customerDomain = \Illuminate\Support\Str::afterLast($ticket->customer->email, '@'); @endphp
            <div class="modal fade" id="block-sender-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form action="{{ route('manager.helpdesk.settings.ticket-blacklist.store') }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Bloquear remitente</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <label class="form-label small">Qué bloquear</label>
                                <select name="type" id="block-sender-type" class="form-select mb-2">
                                    <option value="email" selected>Solo este email — {{ $ticket->customer->email }}</option>
                                    <option value="domain">Todo el dominio — {{ '@'.$customerDomain }} (incluye subdominios)</option>
                                </select>
                                <p class="mb-3 small" id="block-sender-description">
                                    Los próximos correos de <strong>{{ $ticket->customer->email }}</strong> se descartarán
                                    automáticamente, sin crear tickets nuevos ni de respuesta.
                                </p>
                                <input type="hidden" name="value" id="block-sender-value" value="{{ $ticket->customer->email }}">
                                <input type="hidden" name="redirect_ticket_id" value="{{ $ticket->id }}">
                                <label class="form-label small">Motivo (opcional)</label>
                                <textarea name="reason" class="form-control" rows="2"
                                          placeholder="Bloqueado desde el ticket #{{ $ticket->ticket_number }}"></textarea>
                            </div>
                            <div class="modal-footer d-flex flex-column gap-2">
                                <button type="submit" class="btn btn-danger w-100">Bloquear</button>
                                <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endcan

    @if($ticket->customer?->email && ! ($blacklistMatch ?? null))
    @endif

    @can('update', $ticket)
        {{-- Modal: posponer ticket --}}
        <div class="modal fade" id="snooze-ticket-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Posponer ticket</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label small">Reactivar el ticket el</label>
                        <input type="datetime-local" class="form-control" id="snooze-until">
                        <div class="form-text">El ticket se ocultará del listado hasta esa fecha.</div>
                    </div>
                    <div class="modal-footer d-flex flex-column gap-2">
                        <button type="button" class="btn btn-primary w-100" id="snooze-confirm-btn">Posponer</button>
                        <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal: nueva conversación lateral --}}
        <div class="modal fade" id="sideConversationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nueva conversación lateral</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small">Asunto</label>
                            <input type="text" class="form-control" id="side-subject" maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Participante</label>
                            <select class="form-select select2" id="side-participant-type">
                                <option value="team">Compañero de equipo</option>
                                <option value="external_email">Contacto externo (email)</option>
                            </select>
                        </div>
                        <div class="mb-3" id="side-team-wrap">
                            <label class="form-label small">Compañero</label>
                            <select class="form-select select2" id="side-participant-user">
                                <option value="">Selecciona...</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}">{{ $agent->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3 d-none" id="side-email-wrap">
                            <label class="form-label small">Email del contacto</label>
                            <input type="email" class="form-control" id="side-participant-email" maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Mensaje</label>
                            <textarea class="form-control" id="side-message" rows="4" maxlength="20000"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer d-flex flex-column gap-2">
                        <button type="button" class="btn btn-primary w-100" id="side-create-btn">Crear conversación</button>
                        <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan


{{-- Avisos de la guardia de salida. Nunca bloquea el envío: el botón
     principal manda la respuesta igualmente. Ver ReplyGuardService. --}}
@can('update', $ticket)
    <div class="modal fade" id="replyGuardModal" tabindex="-1" aria-labelledby="replyGuardModalLabel" aria-hidden="true"
        data-url="{{ route('manager.helpdesk.tickets.ai.check-reply', $ticket->id) }}">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="replyGuardModalLabel">Antes de enviar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        Esto es lo que no he podido verificar en el hilo ni en las plantillas.
                        Compruébalo tú: puede estar bien.
                    </p>
                    <div id="reply-guard-warnings"></div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" id="reply-guard-review">Volver y revisar</button>
                    <button type="button" class="btn btn-light w-100" id="reply-guard-send">Enviar de todos modos</button>
                </div>
            </div>
        </div>
    </div>
@endcan

@endsection

@push('scripts')
{{-- Los valores que el JS necesitaba interpolados de Blade viajan aquí; el
     resto (unas 750 líneas) vive en ticket-detail.js, cacheable y versionado,
     igual que el listado con tickets-app.js. --}}
<script>
    window.TicketDetailConfig = {
        ticketId: {{ $ticket->id }},
        currentUserId: {{ auth()->id() }},
        broadcastingEnabled: @json(config('broadcasting.default') !== 'null'),
        senderEmail: @json($ticket->customer?->email),
        senderDomain: @json($customerDomain ?? null),
        mentionableUsers: @json($mentionableUsers ?? []),
        timeEntriesUrl: @json(route('manager.helpdesk.tickets.time-entries.index', $ticket)),
        macrosListUrl: @json(route('manager.helpdesk.macros.list')),
        macroApplyUrlBase: @json(url('panel/helpdesk/tickets/'.$ticket->id.'/macros')),
        typingUrl: @json(route('manager.helpdesk.tickets.typing', $ticket->id)),
    };
</script>
<script src="{{ asset('modules/helpdesktickets/js/ticket-detail.js') }}?v={{ @filemtime(public_path('modules/helpdesktickets/js/ticket-detail.js')) }}"></script>
@endpush
