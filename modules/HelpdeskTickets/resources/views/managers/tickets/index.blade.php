@extends('layouts.theme')

@section('title', 'Tickets - Helpdesk')

@section('content')
    {{-- Helpdesk header --}}
    <div class="bg-white border-bottom sticky-top z-10">
        <div class="d-flex align-items-center px-4 py-3">
            <h4 class="mb-0 fw-bold">
                <i class="fas fa-ticket-alt me-2 text-primary"></i>
                {{ $currentView ? $currentView->name : 'Tickets' }}
            </h4>
            @if($currentView && $currentView->description)
                <span class="text-muted mx-2">•</span>
                <small class="text-muted">{{ $currentView->description }}</small>
            @endif
        </div>
    </div>

    <div class="d-flex">
        {{-- Left sidebar: views, categories, groups, filters --}}
        <div class="left-part border-end w-20 flex-shrink-0 d-none d-lg-block">
            <div class="px-9 pt-4 pb-3 d-flex gap-2">
                <a href="{{ route('manager.helpdesk.tickets.create') }}" class="btn btn-primary fw-semibold py-8 flex-grow-1">
                    <i class="fas fa-plus me-2"></i> Nuevo ticket
                </a>
                <div class="dropdown">
                    <button type="button" class="btn btn-light py-8 dropdown-toggle" data-bs-toggle="dropdown" title="Exportar">
                        <i class="fas fa-download"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('manager.helpdesk.tickets.export', ['format' => 'csv'] + request()->only(['search', 'status', 'category'])) }}">
                                CSV
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('manager.helpdesk.tickets.export', ['format' => 'pdf'] + request()->only(['search', 'status', 'category'])) }}">
                                PDF
                            </a>
                        </li>
                    </ul>
                </div>
                <button type="button" class="btn btn-light py-8"
                        data-bs-toggle="modal" data-bs-target="#shortcuts-modal"
                        title="Atajos (?)">
                    <i class="fas fa-keyboard"></i>
                </button>
            </div>

            <ul class="list-group mh-n100" data-simplebar>
                {{-- Vistas --}}
                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    Vistas
                    <a href="{{ route('manager.helpdesk.settings.ticket-views.create') }}" class="float-end text-primary" title="Nueva vista">
                        <i class="fas fa-plus fs-4"></i>
                    </a>
                </li>
                @forelse($views as $view)
                    <li class="list-group-item border-0 p-0 mx-9">
                        <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ $currentView && $currentView->id == $view->id && !request('group') && !request('category') ? 'bg-primary-subtle' : '' }}"
                           href="{{ route('manager.helpdesk.tickets.index', ['viewId' => $view->id]) }}">
                            <i class="{{ $view->icon ?? 'fas fa-eye' }} fs-5" style="color: {{ $view->color ?? '#5D87FF' }}"></i>{{ $view->name }}
                        </a>
                    </li>
                @empty
                    <li class="list-group-item border-0 p-0 mx-9">
                        <span class="text-muted px-3 py-2 d-block">Sin vistas configuradas</span>
                    </li>
                @endforelse

                {{-- Categorias --}}
                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    Categorias
                    <a href="{{ route('manager.helpdesk.settings.ticket-categories.index') }}" class="float-end text-primary" title="Gestionar">
                        <i class="fas fa-cog fs-4"></i>
                    </a>
                </li>
                <li class="list-group-item border-0 p-0 mx-9">
                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ !request('category') ? 'bg-primary-subtle' : '' }}"
                       href="{{ route('manager.helpdesk.tickets.index', array_merge(request()->except('category'), $currentView ? ['viewId' => $currentView->id] : [])) }}">
                        <i class="far fa-folder fs-5"></i>Todas las categorias
                    </a>
                </li>
                @forelse($categories as $category)
                    <li class="list-group-item border-0 p-0 mx-9">
                        <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ request('category') == $category->id ? 'bg-primary-subtle' : '' }}"
                           href="{{ route('manager.helpdesk.tickets.index', array_merge(request()->except('category'), ['category' => $category->id], $currentView ? ['viewId' => $currentView->id] : [])) }}">
                            <i class="{{ $category->icon ?? 'fas fa-tag' }} fs-5" style="color: {{ $category->color ?? '#90bb13' }}"></i>{{ $category->name }}
                        </a>
                    </li>
                @empty
                    <li class="list-group-item border-0 p-0 mx-9">
                        <span class="text-muted px-3 py-2 d-block">Sin categorias configuradas</span>
                    </li>
                @endforelse

                {{-- Grupos --}}
                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    Grupos
                    <a href="{{ route('manager.helpdesk.settings.ticket-groups.index') }}" class="float-end text-primary" title="Gestionar">
                        <i class="fas fa-cog fs-4"></i>
                    </a>
                </li>
                <li class="list-group-item border-0 p-0 mx-9">
                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ !request('group') ? 'bg-primary-subtle' : '' }}"
                       href="{{ route('manager.helpdesk.tickets.index', array_merge(request()->except('group'), $currentView ? ['viewId' => $currentView->id] : [])) }}">
                        <i class="fas fa-users fs-5"></i>Todos los grupos
                    </a>
                </li>
                @forelse($groups as $group)
                    <li class="list-group-item border-0 p-0 mx-9">
                        <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ request('group') == $group->id ? 'bg-primary-subtle' : '' }}"
                           href="{{ route('manager.helpdesk.tickets.index', array_merge(request()->except('group'), ['group' => $group->id], $currentView ? ['viewId' => $currentView->id] : [])) }}">
                            <i class="fas fa-user-group fs-5"></i>{{ $group->name }}
                        </a>
                    </li>
                @empty
                    <li class="list-group-item border-0 p-0 mx-9">
                        <span class="text-muted px-3 py-2 d-block">Sin grupos configurados</span>
                    </li>
                @endforelse

                {{-- Filtros --}}
                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    Filtros
                    <button type="button" class="float-end btn btn-sm btn-link text-primary p-0" id="toggleFilters" title="Mostrar/Ocultar">
                        <i class="fas fa-filter fs-4"></i>
                    </button>
                </li>
                <li class="list-group-item border-0 p-0 mx-9 d-none" id="filtersPanel">
                    <form method="GET" action="{{ route('manager.helpdesk.tickets.index') }}" id="filtersForm">
                        @if($currentView)
                            <input type="hidden" name="viewId" value="{{ $currentView->id }}">
                        @endif
                        @if(request('group'))
                            <input type="hidden" name="group" value="{{ request('group') }}">
                        @endif
                        @if(request('category'))
                            <input type="hidden" name="category" value="{{ request('category') }}">
                        @endif

                        <div class="mb-3">
                            <label class="form-label small">Estado</label>
                            <select name="status" class="form-select form-select-sm js-auto-submit">
                                <option value="all">Todos</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status->id }}" {{ request('status') == $status->id ? 'selected' : '' }}>
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Prioridad</label>
                            <select name="priority" class="form-select form-select-sm js-auto-submit">
                                <option value="all">Todas</option>
                                <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgente</option>
                                <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Alta</option>
                                <option value="normal" {{ request('priority') == 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Baja</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Estado SLA</label>
                            <select name="sla_status" class="form-select form-select-sm js-auto-submit">
                                <option value="all">Todos</option>
                                <option value="breached" {{ request('sla_status') == 'breached' ? 'selected' : '' }}>Incumplido</option>
                                <option value="warning" {{ request('sla_status') == 'warning' ? 'selected' : '' }}>Proximo a vencer</option>
                                <option value="on_track" {{ request('sla_status') == 'on_track' ? 'selected' : '' }}>En tiempo</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Asignado a</label>
                            <select name="assignee" class="form-select form-select-sm js-auto-submit">
                                <option value="all">Todos</option>
                                <option value="me" {{ request('assignee') == 'me' ? 'selected' : '' }}>Yo</option>
                                <option value="unassigned" {{ request('assignee') == 'unassigned' ? 'selected' : '' }}>Sin asignar</option>
                            </select>
                        </div>

                        <a href="{{ route('manager.helpdesk.tickets.index') }}" class="btn btn-sm btn-light w-100">Limpiar filtros</a>
                    </form>
                </li>
            </ul>
        </div>

        {{-- Middle: ticket list --}}
        <div class="w-30 flex-shrink-0 border-end">
            {{-- Bulk actions bar --}}
            <div id="bulk-actions-bar" class="d-none p-3 border-bottom bg-light">
                <form id="bulk-form" method="POST" action="{{ route('manager.helpdesk.tickets.bulk') }}">
                    @csrf
                    <input type="hidden" name="action" id="bulk-action-input">
                    <div id="bulk-ticket-ids"></div>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <span id="selected-count" class="text-muted fw-bold me-1">0 seleccionados</span>
                        <select name="status_id" id="bulk-status-select" class="form-select form-select-sm d-none w-auto">
                            @foreach($statuses as $status)
                                <option value="{{ $status->id }}">{{ $status->name }}</option>
                            @endforeach
                        </select>
                        <select name="agent_id" id="bulk-agent-select" class="form-select form-select-sm d-none w-auto">
                            @foreach($agents ?? [] as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="bulkAction('close')">Cerrar</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="bulkAction('reopen')">Reabrir</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="bulkAction('change_status')">Cambiar estado</button>
                        <button type="button" class="btn btn-sm btn-info text-white" onclick="bulkAction('assign')">Asignar</button>
                        <button type="button" class="btn btn-sm btn-success" id="bulk-reply-btn">Responder</button>
                        <button type="button" class="btn btn-sm btn-danger" id="bulk-delete-btn">Eliminar</button>
                        <button type="submit" id="bulk-submit-btn" class="btn btn-sm btn-success d-none">Aplicar</button>
                        <button type="button" class="btn btn-sm btn-link text-muted" onclick="clearBulkSelection()">Cancelar</button>
                    </div>
                </form>
            </div>

            {{-- Search and count --}}
            <div class="p-3 border-bottom bg-light-subtle">
                <div class="d-flex align-items-center mb-3">
                    <form method="GET" action="{{ route('manager.helpdesk.tickets.index') }}" class="flex-grow-1">
                        @if($currentView)
                            <input type="hidden" name="viewId" value="{{ $currentView->id }}">
                        @endif
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-1">
                                <i class="fas fa-search text-primary"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0"
                                   placeholder="Buscar por #numero, asunto, cliente..." value="{{ request('search') }}">
                            @if(request('search'))
                                <a href="{{ route('manager.helpdesk.tickets.index', array_merge(request()->except('search'), $currentView ? ['viewId' => $currentView->id] : [])) }}"
                                   class="input-group-text bg-white text-muted" title="Limpiar busqueda">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="select-all" title="Seleccionar todos">
                        </div>
                        <span class="badge bg-primary-subtle text-primary px-2 py-1">
                            <i class="fas fa-list-check me-1"></i>
                            {{ $tickets->total() }} {{ $tickets->total() == 1 ? 'ticket' : 'tickets' }}
                        </span>
                        @if($tickets->firstItem())
                            <small class="text-muted">Mostrando {{ $tickets->firstItem() }}-{{ $tickets->lastItem() }}</small>
                        @endif
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-light-primary" onclick="location.reload()" title="Actualizar">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Ticket list --}}
            <ul class="list-group list-group-flush ticket-list-scroll" data-simplebar>
                @forelse($tickets as $ticket)
                    <li class="list-group-item p-3 border-bottom ticket-list-item d-flex align-items-start gap-2">
                        <div class="form-check mt-1 flex-shrink-0">
                            <input class="form-check-input ticket-checkbox" type="checkbox" value="{{ $ticket->id }}">
                        </div>
                        <a href="{{ route('manager.helpdesk.tickets.show', $ticket->id) }}" class="text-decoration-none d-block flex-grow-1">
                            {{-- Ticket header --}}
                            <div class="d-flex align-items-start justify-content-between mb-2">
                                <div class="flex-grow-1 d-flex align-items-center gap-2 flex-wrap">
                                    <span class="badge" style="background-color: {{ $ticket->category?->color ?? "#90bb13" }}; font-size: 0.75rem;">
                                        <i class="{{ $ticket->category?->icon ?? "fas fa-tag" }} me-1"></i>{{ $ticket->category?->name ?? "Sin categoria" }}
                                    </span>
                                    <span class="badge bg-light text-dark border">#{{ $ticket->ticket_number }}</span>
                                    @if($ticket->sla_first_response_breached || $ticket->sla_resolution_breached)
                                        <span class="badge bg-danger-subtle text-danger" title="SLA incumplido">
                                            <i class="fas fa-exclamation-circle"></i> SLA Vencido
                                        </span>
                                    @elseif($ticket->sla_first_response_due_at && $ticket->sla_first_response_due_at->diffInHours() < 2)
                                        <span class="badge bg-warning-subtle text-warning" title="SLA proximo a vencer">
                                            <i class="far fa-clock"></i> SLA Urgente
                                        </span>
                                    @endif
                                </div>
                                <small class="text-muted fw-medium">{{ $ticket->created_at->diffForHumans() }}</small>
                            </div>

                            {{-- Subject --}}
                            <h6 class="mb-2 fw-semibold text-dark lh-base">{{ Str::limit($ticket->subject, 60) }}</h6>

                            {{-- Customer --}}
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="avatar-sm rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-semibold">
                                    {{ strtoupper(substr($ticket->customer?->name ?? "Sin cliente", 0, 1)) }}
                                </div>
                                <small class="text-muted fw-medium">{{ Str::limit($ticket->customer?->name ?? "Sin cliente", 30) }}</small>
                            </div>

                            {{-- Footer: status, priority, assignee --}}
                            <div class="d-flex align-items-center justify-content-between mt-3 pt-2 border-top">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="badge" style="background-color: {{ $ticket->status?->color ?? "#6c757d" }}; font-size: 0.7rem;">
                                        {{ $ticket->status?->name ?? "Sin estado" }}
                                    </span>
                                    @php
                                        $priorityMap = [
                                            'urgent' => ['color' => 'danger', 'icon' => 'fas fa-fire'],
                                            'high'   => ['color' => 'warning', 'icon' => 'fas fa-arrow-up'],
                                            'normal' => ['color' => 'info',    'icon' => 'fas fa-minus'],
                                            'low'    => ['color' => 'secondary','icon' => 'fas fa-arrow-down'],
                                        ];
                                        $prio = $priorityMap[$ticket->priority] ?? ['color' => 'secondary', 'icon' => 'fas fa-minus'];
                                    @endphp
                                    <span class="badge bg-{{ $prio['color'] }}-subtle text-{{ $prio['color'] }}" style="font-size: 0.7rem;">
                                        <i class="{{ $prio['icon'] }}"></i> {{ ucfirst($ticket->priority) }}
                                    </span>
                                </div>
                                @if($ticket->assignee)
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="avatar-xs rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center">
                                            {{ strtoupper(substr($ticket->assignee?->name ?? "Sin asignar", 0, 1)) }}
                                        </div>
                                        <small class="text-muted fw-medium">{{ Str::limit($ticket->assignee?->name ?? "Sin asignar", 15) }}</small>
                                    </div>
                                @else
                                    <small class="text-muted">
                                        <i class="fas fa-user-slash me-1"></i> Sin asignar
                                    </small>
                                @endif
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="list-group-item text-center py-5">
                        <div class="py-4">
                            <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3 p-4">
                                <i class="fas fa-ticket-alt fa-2x text-muted opacity-50"></i>
                            </div>
                            <h6 class="text-muted fw-semibold mb-1">No se encontraron tickets</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No hay resultados para "{{ request('search') }}"
                                @else
                                    Crea un nuevo ticket para comenzar
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('manager.helpdesk.tickets.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i> Crear primer ticket
                                </a>
                            @endif
                        </div>
                    </li>
                @endforelse
            </ul>

            {{-- Pagination --}}
            @if($tickets->hasPages())
                <div class="p-3 border-top">
                    {{ $tickets->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>

        {{-- Right: empty state when no ticket selected --}}
        <div class="flex-fill d-flex align-items-center justify-content-center">
            <div class="text-center">
                <i class="fas fa-ticket-alt display-1 text-muted mb-4"></i>
                <h5 class="text-muted">Selecciona un ticket para ver los detalles</h5>
                <p class="text-muted">O crea un nuevo ticket usando el boton de arriba</p>
            </div>
        </div>
    </div>

    {{-- Bulk reply modal --}}
    <div class="modal fade" id="bulk-reply-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-reply me-2"></i>Responder a <span id="bulk-reply-count">0</span> tickets
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Esta respuesta se enviara a todos los tickets seleccionados.</p>
                    <div class="mb-3">
                        <label class="form-label">Mensaje</label>
                        <textarea class="form-control" id="bulk-reply-body" rows="6" placeholder="Escribe tu respuesta..."></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="bulk-reply-internal">
                        <label class="form-check-label" for="bulk-reply-internal">
                            <i class="fas fa-lock me-1"></i> Nota interna (no visible para el cliente)
                        </label>
                    </div>
                </div>
                <div class="modal-footer flex-column">
                    <button type="button" class="btn btn-primary w-100 mb-2" id="bulk-reply-send">Enviar respuesta</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk delete confirmation modal --}}
    <div class="modal fade" id="bulk-delete-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Eliminar tickets seleccionados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">¿Deseas eliminar los tickets seleccionados? Esta accion no se puede deshacer.</p>
                </div>
                <div class="modal-footer d-flex flex-column gap-2">
                    <button type="button" class="btn btn-danger w-100" id="bulk-delete-confirm">Eliminar</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .ticket-list-scroll {
        height: calc(100vh - 250px);
        overflow-y: auto;
    }
    .ticket-list-item {
        transition: background-color 0.15s ease;
    }
    .ticket-list-item:hover {
        background-color: #f8f9fa;
    }
    .avatar-sm {
        width: 28px;
        height: 28px;
        font-size: 12px;
        flex-shrink: 0;
    }
    .avatar-xs {
        width: 20px;
        height: 20px;
        font-size: 10px;
        flex-shrink: 0;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    // Toggle filters panel
    $('#toggleFilters').on('click', function () {
        const panel = $('#filtersPanel');
        const icon = $(this).find('i');
        panel.toggleClass('d-none');
        icon.toggleClass('fa-filter fa-times');
    });

    // Auto-submit filter selects
    $(document).on('change', '.js-auto-submit', function () {
        $(this).closest('form').submit();
    });

    // Bulk: select all
    $(document).on('change', '#select-all', function () {
        $('.ticket-checkbox').prop('checked', this.checked);
        updateBulkBar();
    });

    // Bulk: individual checkbox
    $(document).on('change', '.ticket-checkbox', function () {
        const total = $('.ticket-checkbox').length;
        const checked = $('.ticket-checkbox:checked').length;
        $('#select-all').prop('indeterminate', checked > 0 && checked < total);
        $('#select-all').prop('checked', checked === total);
        updateBulkBar();
    });

    // Bulk reply button shows modal
    $('#bulk-reply-btn').on('click', function () {
        const count = $('.ticket-checkbox:checked').length;
        if (count === 0) return;
        $('#bulk-reply-count').text(count);
        $('#bulk-reply-modal').modal('show');
    });

    // Send bulk reply
    $('#bulk-reply-send').on('click', function () {
        const selected = $('.ticket-checkbox:checked').map(function () { return this.value; }).get();
        if (selected.length === 0) { toastr.warning('Selecciona al menos un ticket'); return; }

        const body = $('#bulk-reply-body').val().trim();
        if (!body) { toastr.warning('Escribe un mensaje'); return; }

        const $btn = $(this).prop('disabled', true).text('Enviando...');

        $.ajax({
            url: '{{ route("manager.helpdesk.tickets.bulk-reply") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: {
                ticket_ids: selected,
                body: body,
                is_internal: $('#bulk-reply-internal').is(':checked') ? 1 : 0,
            },
        }).done(function (res) {
            toastr.success(res.message);
            $('#bulk-reply-modal').modal('hide');
            $('#bulk-reply-body').val('');
            $('#bulk-reply-internal').prop('checked', false);
            setTimeout(function () { location.reload(); }, 800);
        }).fail(function () {
            toastr.error('Error al enviar la respuesta');
        }).always(function () {
            $btn.prop('disabled', false).text('Enviar respuesta');
        });
    });

    // Bulk delete button shows modal
    $('#bulk-delete-btn').on('click', function () {
        const count = $('.ticket-checkbox:checked').length;
        if (count === 0) return;
        $('#bulk-delete-modal').modal('show');
    });

    // Confirm bulk delete
    $('#bulk-delete-confirm').on('click', function () {
        $('#bulk-delete-modal').modal('hide');
        const ids = $('.ticket-checkbox:checked').map(function () { return this.value; }).get();
        submitBulkForm('delete', ids);
    });
});

function updateBulkBar() {
    const count = $('.ticket-checkbox:checked').length;
    if (count > 0) {
        $('#bulk-actions-bar').removeClass('d-none');
        $('#selected-count').text(count + ' seleccionados');
    } else {
        $('#bulk-actions-bar').addClass('d-none');
        $('#bulk-agent-select, #bulk-status-select, #bulk-submit-btn').addClass('d-none');
    }
}

function clearBulkSelection() {
    $('.ticket-checkbox, #select-all').prop('checked', false);
    $('#select-all').prop('indeterminate', false);
    $('#bulk-actions-bar').addClass('d-none');
    $('#bulk-agent-select, #bulk-status-select, #bulk-submit-btn').addClass('d-none');
}

function bulkAction(action) {
    const ids = $('.ticket-checkbox:checked').map(function () { return this.value; }).get();
    if (ids.length === 0) return;

    if (action === 'assign') {
        $('#bulk-agent-select').removeClass('d-none');
        $('#bulk-status-select').addClass('d-none');
        $('#bulk-submit-btn').removeClass('d-none');
        $('#bulk-action-input').val(action);
        return;
    }

    if (action === 'change_status') {
        $('#bulk-status-select').removeClass('d-none');
        $('#bulk-agent-select').addClass('d-none');
        $('#bulk-submit-btn').removeClass('d-none');
        $('#bulk-action-input').val(action);
        return;
    }

    $('#bulk-agent-select, #bulk-status-select, #bulk-submit-btn').addClass('d-none');
    submitBulkForm(action, ids);
}

$('#bulk-submit-btn').on('click', function () {
    const action = $('#bulk-action-input').val();
    const ids = $('.ticket-checkbox:checked').map(function () { return this.value; }).get();
    submitBulkForm(action, ids);
});

function submitBulkForm(action, ids) {
    $('#bulk-action-input').val(action);
    $('#bulk-ticket-ids').empty();
    ids.forEach(function (id) {
        $('#bulk-ticket-ids').append('<input type="hidden" name="ticket_ids[]" value="' + id + '">');
    });
    $('#bulk-form').submit();
}

// ── Keyboard shortcuts ─────────────────────────────────────
(function () {
    let currentIdx = -1;

    function ticketItems() {
        return $('.ticket-list-item a[href*="/tickets/"]');
    }

    function highlight(idx) {
        const $items = ticketItems();
        $items.closest('.ticket-list-item').removeClass('bg-primary-subtle');
        if (idx < 0 || idx >= $items.length) return;
        $items.eq(idx).closest('.ticket-list-item').addClass('bg-primary-subtle')[0]
            .scrollIntoView({ block: 'nearest' });
        currentIdx = idx;
    }

    $(document).on('keydown', function (e) {
        if ($(e.target).is('input, textarea, select, [contenteditable]')) return;

        const key = e.key;
        const $items = ticketItems();

        if (key === 'j' || key === 'ArrowDown') {
            e.preventDefault();
            highlight(Math.min(currentIdx + 1, $items.length - 1));
        } else if (key === 'k' || key === 'ArrowUp') {
            e.preventDefault();
            highlight(Math.max(0, currentIdx - 1));
        } else if ((key === 'Enter' || key === 'o') && currentIdx >= 0) {
            const href = $items.eq(currentIdx).attr('href');
            if (href) { window.location.href = href; }
        } else if (key === 'c') {
            e.preventDefault();
            $('input[name="search"]').first().focus();
        } else if (key === 'n') {
            window.location.href = '{{ route("manager.helpdesk.tickets.create") }}';
        } else if (key === '?') {
            e.preventDefault();
            $('#shortcuts-modal').modal('show');
        }
    });
}());
</script>

<div class="modal fade" id="shortcuts-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-keyboard me-2"></i>Atajos de teclado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><td><kbd>J</kbd> / <kbd>↓</kbd></td><td>Siguiente ticket</td></tr>
                        <tr><td><kbd>K</kbd> / <kbd>↑</kbd></td><td>Ticket anterior</td></tr>
                        <tr><td><kbd>Enter</kbd> / <kbd>O</kbd></td><td>Abrir ticket seleccionado</td></tr>
                        <tr><td><kbd>C</kbd></td><td>Buscar tickets</td></tr>
                        <tr><td><kbd>N</kbd></td><td>Nuevo ticket</td></tr>
                        <tr><td><kbd>?</kbd></td><td>Mostrar esta ayuda</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endpush
