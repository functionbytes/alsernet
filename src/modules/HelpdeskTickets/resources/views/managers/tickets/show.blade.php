@extends('layouts.theme')

@section('title', 'Ticket #' . $ticket->ticket_number . ' - Helpdesk')

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
                        <a href="{{ route('manager.helpdesk.tickets.show', $t->id) }}" class="text-decoration-none d-block">
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
                                            <div class="mt-2">
                                                @foreach($item->attachment_urls as $attachment)
                                                    <a href="{{ $attachment }}" target="_blank" class="btn btn-sm btn-light me-2 mb-1">
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
                            <a href="{{ route('manager.helpdesk.tickets.show', $link->linkedTicket) }}" class="fw-semibold">
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
                            <select name="link_type" class="form-select form-select-sm">
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
                <select class="form-select form-select-sm mb-2" id="macro-select">
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

@endsection

@push('styles')
<style>
    .ticket-slim-scroll { height: calc(100vh - 140px); overflow-y: auto; }
    .ticket-slim-subject { font-size: 11px; }
    .ticket-main-col { width: 40%; }
    .ticket-messages-scroll { height: calc(100vh - 340px); overflow-y: auto; }
    .ticket-message-bubble { max-width: 70%; }
    .ticket-info-col { width: 20%; overflow-y: auto; height: calc(100vh - 70px); }
    .time-entries-scroll { max-height: 200px; overflow-y: auto; }
    .avatar-md { width: 32px; height: 32px; font-size: 14px; flex-shrink: 0; }
    .avatar-xs { width: 24px; height: 24px; font-size: 11px; flex-shrink: 0; }
</style>
@endpush

@push('scripts')
<script>
    // Auto-scroll to bottom of messages
    const messagesContainer = document.getElementById('messagesContainer');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    // Real-time via Laravel Echo
    @if(config('broadcasting.default') !== 'null')
        Echo.channel('ticket.{{ $ticket->id }}')
            .listen('TicketMessageReceived', () => location.reload());
    @endif

    // ── Time Tracking ──────────────────────────────────────────────
    const timeEntriesUrl = "{{ route('manager.helpdesk.tickets.time-entries.index', $ticket) }}";
    const currentUserId = {{ auth()->id() }};

    function formatLoggedAt(dateStr) {
        if (!dateStr) return '';
        return new Date(dateStr).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function renderEntries(data) {
        const $list = $('#time-entries-list');
        $list.empty();
        $('#total-time-badge').text(data.formatted_total || '0m');

        if (!data.entries || data.entries.length === 0) {
            $list.html('<div class="list-group-item text-muted text-center py-3">Sin registros de tiempo</div>');
            return;
        }

        data.entries.forEach(function (entry) {
            const userName = entry.user ? entry.user.name : 'Desconocido';

            const $info = $('<div class="small">').append(
                $('<span class="fw-semibold">').text(entry.formatted_duration),
                $('<span class="text-muted ms-1">').text('· ' + userName)
            );

            if (entry.description) {
                $info.append($('<div class="text-muted">').css('font-size', '11px').text(entry.description));
            }

            $info.append($('<div class="text-muted">').css('font-size', '10px').text(formatLoggedAt(entry.logged_at)));

            const $actions = $('<div class="flex-shrink-0">');
            if (entry.user_id === currentUserId) {
                $actions.append(
                    $('<button class="btn btn-link btn-sm text-danger p-0 ms-1 delete-time-entry">')
                        .attr({ 'data-id': entry.id, title: 'Eliminar' })
                        .append($('<i class="fas fa-trash-alt">'))
                );
            }

            $list.append(
                $('<div class="list-group-item px-2 py-1 d-flex justify-content-between align-items-start">').append($info, $actions)
            );
        });
    }

    function loadTimeEntries() {
        $.get(timeEntriesUrl)
            .done(renderEntries)
            .fail(function () {
                $('#time-entries-list').html('<div class="list-group-item text-danger small">Error al cargar registros</div>');
            });
    }

    $(document).on('click', '.delete-time-entry', function () {
        const id = $(this).data('id');
        $.ajax({
            method: 'DELETE',
            url: timeEntriesUrl + '/' + id,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
        .done(loadTimeEntries)
        .fail(function (xhr) {
            toastr.error(xhr.responseJSON ? xhr.responseJSON.message : 'Error al eliminar');
        });
    });

    $('#log-time-form').on('submit', function (e) {
        e.preventDefault();

        const minutes = parseInt($('#time-minutes').val(), 10);
        const description = $('#time-description').val().trim();

        if (!minutes || minutes < 1 || minutes > 480) {
            toastr.warning('Los minutos deben estar entre 1 y 480.');
            $('#time-minutes').focus();
            return;
        }

        $.ajax({
            method: 'POST',
            url: timeEntriesUrl,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { minutes, description },
        })
        .done(function () {
            $('#time-minutes').val('');
            $('#time-description').val('');
            toastr.success('Tiempo registrado correctamente.');
            loadTimeEntries();
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                toastr.error(Object.values(xhr.responseJSON.errors).flat().join(' '));
            } else {
                toastr.error('Error al registrar tiempo.');
            }
        });
    });

    loadTimeEntries();

    // ── SLA Countdown ──────────────────────────────────────────────
    function updateSlaCountdowns() {
        $('[data-sla-countdown]').each(function () {
            const due  = new Date($(this).data('sla-countdown'));
            const mins = Math.round((due - new Date()) / 60000);

            if (mins < 0) {
                $(this).text('Vencido hace ' + Math.abs(mins) + ' min');
            } else if (mins < 60) {
                $(this).text('En ' + mins + ' min');
            } else {
                $(this).text('En ' + Math.round(mins / 60) + ' h');
            }
        });
    }

    updateSlaCountdowns();
    setInterval(updateSlaCountdowns, 30000);

    // ── @Mentions ──────────────────────────────────────────────────
    (function () {
        const users = @json($mentionableUsers);
        const $textarea = $('textarea[name="body"]').first();
        if (!$textarea.length) return;

        const $dropdown = $('<div class="dropdown-menu shadow" style="max-height:200px;overflow-y:auto;display:none;position:fixed;z-index:9999"></div>')
            .appendTo('body');

        function getAtContext(text, cursor) {
            const lastAt = text.lastIndexOf('@', cursor - 1);
            if (lastAt < 0) return null;
            if (lastAt > 0 && !/\s/.test(text[lastAt - 1])) return null;
            const query = text.substring(lastAt + 1, cursor);
            if (/\s/.test(query)) return null;
            return { lastAt, query };
        }

        $textarea.on('input keyup', function () {
            const ctx = getAtContext(this.value, this.selectionStart);
            if (!ctx) { $dropdown.hide(); return; }

            const matches = users.filter(u => u.name.toLowerCase().includes(ctx.query.toLowerCase())).slice(0, 8);
            if (!matches.length) { $dropdown.hide(); return; }

            $dropdown.empty();
            matches.forEach(function (u) {
                const rect = $textarea[0].getBoundingClientRect();
                $dropdown.css({ top: rect.bottom + 5, left: rect.left, minWidth: 260 });

                $('<a class="dropdown-item d-flex align-items-center gap-2 py-2"></a>')
                    .html('<span class="fw-semibold">' + $('<span>').text(u.name).html() + '</span><small class="text-muted">' + $('<span>').text(u.email).html() + '</small>')
                    .on('mousedown', function (ev) {
                        ev.preventDefault();
                        const val = $textarea.val();
                        const cursor = $textarea[0].selectionStart;
                        $textarea.val(val.substring(0, ctx.lastAt) + '@' + u.name + ' ' + val.substring(cursor));
                        $dropdown.hide();
                        $textarea.focus();
                    })
                    .appendTo($dropdown);
            });

            const rect = $textarea[0].getBoundingClientRect();
            $dropdown.css({ top: rect.bottom + 5, left: rect.left, minWidth: 260 }).show();
        });

        $textarea.on('blur', function () {
            setTimeout(function () { $dropdown.hide(); }, 200);
        });
    }());
</script>
@endpush

@push('scripts')
<script>
$(function () {
    const $sel = $('#macro-select');
    const $btn = $('#macro-apply-btn');

    $.get('{{ route("manager.helpdesk.macros.list") }}').done(function (data) {
        $.each(data.macros, function (i, m) {
            $sel.append($('<option>', { value: m.id, text: m.name }));
        });
    });

    $sel.on('change', function () {
        $btn.prop('disabled', !this.value);
    });

    $btn.on('click', function () {
        const macroId = $sel.val();
        if (!macroId) return;

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Aplicando...');

        $.ajax({
            url: '{{ url("panel/helpdesk/tickets/'.$ticket->id.'/macros") }}/' + macroId + '/apply',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function (res) {
            toastr.success(res.message);
            setTimeout(function () { location.reload(); }, 800);
        }).fail(function () {
            toastr.error('Error al aplicar la macro.');
            $btn.prop('disabled', false).html('<i class="fas fa-play me-1"></i> Aplicar');
        });
    });

    // Merge confirmation
    $('#merge-ticket-form').on('submit', function (e) {
        const targetId = $(this).find('[name="merge_into_id"]').val();
        if (!targetId) return;
        e.preventDefault();
        const form = this;
        window.__confirm('¿Fusionar este ticket en el ticket #' + targetId + '? Esta acción no se puede deshacer.', function () {
            form.submit();
        });
    });

    // ── Canned replies ──────────────────────────────────────────────
    $(document).on('click', '.canned-reply-item', function () {
        const content = $(this).data('content');
        const $body = $('#reply-body');
        const current = $body.val().trim();
        $body.val(current ? current + '\n\n' + content : content);
        $body.focus();
    });

    // ── Internal note toggle ────────────────────────────────────────
    $('#isInternal').on('change', function () {
        const isInternal = $(this).is(':checked');
        $('#internal-indicator').toggleClass('d-none', !isInternal);
        $('#reply-form-wrapper').toggleClass('bg-warning-subtle', isInternal).toggleClass('bg-light', !isInternal);
    });

    // ── Attachment preview ──────────────────────────────────────────
    $('#attachments').on('change', function () {
        const $preview = $('#attachment-preview');
        $preview.empty();
        $.each(this.files, function (i, file) {
            const ext = file.name.split('.').pop().toLowerCase();
            const iconMap = { pdf: 'fa-file-pdf', doc: 'fa-file-word', docx: 'fa-file-word', xls: 'fa-file-excel', xlsx: 'fa-file-excel', jpg: 'fa-file-image', jpeg: 'fa-file-image', png: 'fa-file-image', gif: 'fa-file-image', zip: 'fa-file-archive', rar: 'fa-file-archive' };
            const icon = iconMap[ext] || 'fa-file';
            const size = file.size > 1048576 ? (file.size / 1048576).toFixed(1) + ' MB' : (file.size / 1024).toFixed(0) + ' KB';
            $preview.append(
                '<div class="d-flex align-items-center gap-1 border rounded px-2 py-1 bg-white small">' +
                '<i class="fas ' + icon + ' text-muted"></i>' +
                '<span class="text-truncate" style="max-width:120px">' + $('<span>').text(file.name).html() + '</span>' +
                '<span class="text-muted">(' + size + ')</span>' +
                '</div>'
            );
        });
    });
});
</script>
@endpush

@auth
@push('scripts')
<script>
$(function () {
    if (typeof window.Echo === 'undefined') {
        console.warn('Echo not available — skipping real-time features');
        return;
    }

    const ticketId = {{ $ticket->id }};
    const meId     = {{ auth()->id() }};

    const $banner = $('<div class="alert alert-warning py-2 mb-3 d-none" id="collision-banner">' +
        '<i class="fas fa-users me-1"></i><span id="collision-msg"></span>' +
        '</div>');

    // Insert banner before the messages container
    $('#messagesContainer').before($banner);

    const $typing = $('<div class="small text-muted fst-italic mt-1 d-none" id="typing-indicator"></div>');
    $('textarea[name="body"]').closest('form').before($typing);

    let otherUsers = [];

    function updateCollisionBanner() {
        if (!otherUsers.length) {
            $('#collision-banner').addClass('d-none');
            return;
        }

        const names = otherUsers.map(function (u) { return u.name; }).join(', ');
        const verb  = otherUsers.length > 1 ? 'estan' : 'esta';
        $('#collision-msg').text(names + ' ' + verb + ' viendo este ticket');
        $('#collision-banner').removeClass('d-none');
    }

    window.Echo.join('ticket.' + ticketId)
        .here(function (users) {
            otherUsers = users.filter(function (u) { return u.id !== meId; });
            updateCollisionBanner();
        })
        .joining(function (user) {
            if (user.id !== meId) {
                otherUsers.push(user);
                updateCollisionBanner();
            }
        })
        .leaving(function (user) {
            otherUsers = otherUsers.filter(function (u) { return u.id !== user.id; });
            updateCollisionBanner();
        })
        .listen('.typing', function (e) {
            if (e.userId === meId) { return; }

            if (e.isTyping) {
                $('#typing-indicator').text(e.userName + ' esta escribiendo...').removeClass('d-none');
            } else {
                $('#typing-indicator').addClass('d-none');
            }
        });

    // Emit typing indicator while agent types in the reply textarea
    let typingTimer;
    $('textarea[name="body"]').on('input', function () {
        clearTimeout(typingTimer);

        $.post('{{ route("manager.helpdesk.tickets.typing", $ticket->id) }}', {
            _token:    $('meta[name="csrf-token"]').attr('content'),
            is_typing: 1,
        });

        typingTimer = setTimeout(function () {
            $.post('{{ route("manager.helpdesk.tickets.typing", $ticket->id) }}', {
                _token:    $('meta[name="csrf-token"]').attr('content'),
                is_typing: 0,
            });
        }, 2500);
    });
});
</script>
@endpush

@push('scripts')
<script>
$(function () {
    const $box = $('#suggested-articles-box');
    if (!$box.length) return;

    const $list = $('#suggested-articles-list');
    let loaded = false;

    function esc(s) {
        return $('<div>').text(s == null ? '' : s).html();
    }

    function load() {
        $list.html('<div class="text-muted small py-1">Buscando…</div>');
        $.ajax({
            url: $box.data('url'),
            method: 'GET',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function (r) {
            const items = (r && r.data) || [];
            if (!items.length) {
                $list.html('<div class="text-muted small py-1">No hay artículos relacionados.</div>');
                return;
            }
            const html = items.map(function (a) {
                return '<div class="d-flex align-items-start justify-content-between gap-2 py-1 border-bottom">'
                    + '<a href="' + esc(a.url) + '" target="_blank" class="small text-decoration-none">'
                    + '<i class="fas fa-file-lines me-1"></i>' + esc(a.title) + '</a>'
                    + '<button type="button" class="btn btn-sm btn-light py-0 px-1 insert-article-link" '
                    + 'data-url="' + esc(a.url) + '" data-title="' + esc(a.title) + '" title="Insertar enlace">'
                    + '<i class="fas fa-plus"></i></button></div>';
            }).join('');
            $list.html(html);
        }).fail(function () {
            $list.html('<div class="text-danger small py-1">No se pudieron cargar las sugerencias.</div>');
        });
    }

    $('#suggested-articles-toggle').on('click', function () {
        $list.toggleClass('d-none');
        if (!loaded && !$list.hasClass('d-none')) {
            loaded = true;
            load();
        }
    });

    $(document).on('click', '.insert-article-link', function () {
        const $t = $('#reply-body');
        const link = $(this).data('title') + ': ' + $(this).data('url');
        $t.val(($t.val() ? $t.val() + '\n' : '') + link).trigger('input').focus();
        if (window.toastr) { toastr.success('Enlace del artículo insertado'); }
    });

    // ── Aplicar sugerencias de IA (categoría / prioridad) ──
    $(document).on('click', '.apply-ai-suggestion', function () {
        const $btn = $(this);
        const $box = $('#ai-suggestions');
        $btn.prop('disabled', true);
        $.ajax({
            url: $box.data('url'),
            method: 'POST',
            dataType: 'json',
            data: { field: $btn.data('field') },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function () {
            if (window.toastr) { toastr.success('Sugerencia aplicada'); }
            setTimeout(function () { window.location.reload(); }, 500);
        }).fail(function () {
            $btn.prop('disabled', false);
            if (window.toastr) { toastr.error('No se pudo aplicar la sugerencia'); }
        });
    });

    // ── Seguimientos programados ──
    const $fBox = $('#followups-box');
    function escF(s) { return $('<div>').text(s == null ? '' : s).html(); }

    $('#add-followup').on('click', function () {
        const when = $('#followup-when').val();
        if (!when) { if (window.toastr) { toastr.info('Elige fecha y hora'); } return; }
        const note = $('#followup-note').val();
        const $btn = $(this).prop('disabled', true);
        $.ajax({
            url: $fBox.data('store-url'),
            method: 'POST',
            dataType: 'json',
            data: { scheduled_at: when, note: note },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function (r) {
            const f = (r && r.data) || {};
            const dt = new Date(f.scheduled_at).toLocaleString();
            $('#followups-empty').remove();
            $('#followups-list').append(
                '<div class="d-flex align-items-start justify-content-between gap-2 py-1 border-bottom" data-followup-id="' + f.id + '">'
                + '<div class="small"><span class="fw-semibold">' + escF(dt) + '</span>'
                + (f.note ? '<div class="text-muted">' + escF(f.note) + '</div>' : '') + '</div>'
                + '<button type="button" class="btn btn-sm btn-light py-0 px-1 cancel-followup" title="Cancelar"><i class="fas fa-xmark"></i></button></div>');
            $('#followup-when').val(''); $('#followup-note').val('');
            if (window.toastr) { toastr.success('Seguimiento programado'); }
        }).fail(function (xhr) {
            const msg = xhr.responseJSON && xhr.responseJSON.errors
                ? Object.values(xhr.responseJSON.errors)[0][0] : 'No se pudo programar';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () { $btn.prop('disabled', false); });
    });

    $(document).on('click', '.cancel-followup', function () {
        const $row = $(this).closest('[data-followup-id]');
        $.ajax({
            url: $fBox.data('base-url') + '/' + $row.data('followup-id'),
            method: 'DELETE',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function () {
            $row.remove();
            if (!$('#followups-list').children().length) {
                $('#followups-list').html('<div class="text-muted small py-1" id="followups-empty">Sin seguimientos programados.</div>');
            }
            if (window.toastr) { toastr.success('Seguimiento cancelado'); }
        }).fail(function () { if (window.toastr) { toastr.error('No se pudo cancelar'); } });
    });

    // ── Traducir el borrador de respuesta ──
    $('#translate-reply-btn').on('click', function () {
        const $btn = $(this);
        const $t = $('#reply-body');
        const text = ($t.val() || '').trim();
        if (!text) { if (window.toastr) { toastr.info('Escribe algo para traducir'); } return; }
        $btn.prop('disabled', true);
        $.ajax({
            url: $btn.data('url'),
            method: 'POST',
            dataType: 'json',
            data: { text: text, target_lang: (document.documentElement.lang || 'es') },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function (r) {
            if (r && r.text) { $t.val(r.text).trigger('input'); }
            if (window.toastr) {
                r && r.translated ? toastr.success('Borrador traducido') : toastr.info('Traducción no disponible');
            }
        }).fail(function () {
            if (window.toastr) { toastr.error('No se pudo traducir'); }
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
});
</script>
@endpush
@endauth
