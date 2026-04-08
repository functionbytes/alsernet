@extends('layouts.helpdesk')

@section('title', 'Tickets - Helpdesk')

@section('content')
    {{-- Modern Helpdesk Header --}}
    <div class="bg-white border-bottom sticky-top" style="z-index: 10;">
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
        {{-- Modern Sidebar with Modernize Style --}}
        <div class="left-part border-end w-20 flex-shrink-0 d-none d-lg-block">
            {{-- New Ticket Button --}}
            <div class="px-9 pt-4 pb-3">
                <a href="{{ route('manager.helpdesk.tickets.create') }}" class="btn btn-primary fw-semibold py-8 w-100">
                    <i class="fas fa-plus me-2"></i> Nuevo Ticket
                </a>
            </div>

            {{-- Sidebar Menu --}}
            <ul class="list-group mh-n100" data-simplebar>
                {{-- VISTAS Section --}}
                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    VISTAS
                    <a href="{{ route('manager.helpdesk.backups.tickets.views.create') }}" class="float-end text-primary" title="Nueva vista">
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

                {{-- CATEGORÍAS Section --}}
                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    CATEGORÍAS
                    <a href="{{ route('manager.helpdesk.backups.tickets.categories.index') }}" class="float-end text-primary" title="Gestionar">
                        <i class="fas fa-cog fs-4"></i>
                    </a>
                </li>
                <li class="list-group-item border-0 p-0 mx-9">
                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ !request('category') ? 'bg-primary-subtle' : '' }}"
                       href="{{ route('manager.helpdesk.tickets.index', array_merge(request()->except('category'), $currentView ? ['viewId' => $currentView->id] : [])) }}">
                        <i class="far fa-folder fs-5"></i>Todas las Categorías
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
                        <span class="text-muted px-3 py-2 d-block">Sin categorías configuradas</span>
                    </li>
                @endforelse

                {{-- GRUPOS Section --}}
                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    GRUPOS
                    <a href="{{ route('manager.helpdesk.backups.tickets.team.groups') }}" class="float-end text-primary" title="Gestionar">
                        <i class="fas fa-cog fs-4"></i>
                    </a>
                </li>
                <li class="list-group-item border-0 p-0 mx-9">
                    <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ !request('group') ? 'bg-primary-subtle' : '' }}"
                       href="{{ route('manager.helpdesk.tickets.index', array_merge(request()->except('group'), $currentView ? ['viewId' => $currentView->id] : [])) }}">
                        <i class="fas fa-users fs-5"></i>Todos los Grupos
                    </a>
                </li>
                @forelse($groups as $group)
                    <li class="list-group-item border-0 p-0 mx-9">
                        <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-8 mb-1 rounded-1 {{ request('group') == $group->id ? 'bg-primary-subtle' : '' }}"
                           href="{{ route('manager.helpdesk.tickets.index', array_merge(request()->except('group'), ['group' => $group->id], $currentView ? ['viewId' => $currentView->id] : [])) }}">
                            <i class="fas fa-users-group fs-5"></i>{{ $group->name }}
                        </a>
                    </li>
                @empty
                    <li class="list-group-item border-0 p-0 mx-9">
                        <span class="text-muted px-3 py-2 d-block">Sin grupos configurados</span>
                    </li>
                @endforelse

                {{-- FILTROS Section --}}
                <li class="border-bottom my-3"></li>
                <li class="fw-semibold text-dark text-uppercase mx-9 my-2 px-3 fs-2">
                    FILTROS
                    <button type="button" class="float-end btn btn-sm btn-link text-primary p-0" id="toggleFilters" title="Mostrar/Ocultar">
                        <i class="fas fa-filter fs-4"></i>
                    </button>
                </li>
                <li class="list-group-item border-0 p-0 mx-9" id="filtersPanel" style="display: none;">
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
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
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
                            <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all">Todas</option>
                                <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgente</option>
                                <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Alta</option>
                                <option value="normal" {{ request('priority') == 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Baja</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Estado SLA</label>
                            <select name="sla_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all">Todos</option>
                                <option value="breached" {{ request('sla_status') == 'breached' ? 'selected' : '' }}>Incumplido</option>
                                <option value="warning" {{ request('sla_status') == 'warning' ? 'selected' : '' }}>Próximo a vencer</option>
                                <option value="on_track" {{ request('sla_status') == 'on_track' ? 'selected' : '' }}>En tiempo</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Asignado a</label>
                            <select name="assignee" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all">Todos</option>
                                <option value="me" {{ request('assignee') == 'me' ? 'selected' : '' }}>Yo</option>
                                <option value="unassigned" {{ request('assignee') == 'unassigned' ? 'selected' : '' }}>Sin asignar</option>
                            </select>
                        </div>

                        <button type="button" class="btn btn-sm btn-light w-100" onclick="window.location.href='{{ route('manager.helpdesk.tickets.index') }}'">
                            Limpiar filtros
                        </button>
                    </form>
                </li>
            </ul>
        </div>

        {{-- Middle Section: Tickets List --}}
        <div class="w-30 flex-shrink-0 border-end">
            {{-- Bulk Actions Bar --}}
            <div id="bulk-actions-bar" class="d-none p-3 border-bottom bg-light">
                <form id="bulk-form" method="POST" action="{{ route('manager.helpdesk.tickets.bulk') }}">
                    @csrf
                    <input type="hidden" name="action" id="bulk-action-input">
                    <div id="bulk-ticket-ids"></div>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <span id="selected-count" class="text-muted fw-bold me-1">0 seleccionados</span>
                        <select name="status_id" id="bulk-status-select" class="form-select form-select-sm d-none" style="width:auto">
                            @foreach($statuses as $status)
                                <option value="{{ $status->id }}">{{ $status->name }}</option>
                            @endforeach
                        </select>
                        <select name="agent_id" id="bulk-agent-select" class="form-select form-select-sm d-none" style="width:auto">
                            @foreach($agents ?? [] as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="bulkAction('close')">Cerrar</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="bulkAction('reopen')">Reabrir</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="bulkAction('change_status')">Cambiar estado</button>
                        <button type="button" class="btn btn-sm btn-info text-white" onclick="bulkAction('assign')">Asignar</button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="bulkAction('delete')">Eliminar</button>
                        <button type="submit" id="bulk-submit-btn" class="btn btn-sm btn-success d-none">Aplicar</button>
                        <button type="button" class="btn btn-sm btn-link text-muted" onclick="clearBulkSelection()">Cancelar</button>
                    </div>
                </form>
            </div>

            {{-- Search and Count --}}
            <div class="p-3 border-bottom bg-light-subtle">
                <div class="d-flex align-items-center mb-3">
                    <form method="GET" action="{{ route('manager.helpdesk.tickets.index') }}" class="flex-grow-1">
                        @if($currentView)
                            <input type="hidden" name="viewId" value="{{ $currentView->id }}">
                        @endif
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-primary"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Buscar por #número, asunto, cliente..." value="{{ request('search') }}">
                            @if(request('search'))
                                <a href="{{ route('manager.helpdesk.tickets.index', array_merge(request()->except('search'), $currentView ? ['viewId' => $currentView->id] : [])) }}" class="input-group-text bg-white text-danger" title="Limpiar búsqueda">
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
                            <small class="text-muted">
                                Mostrando {{ $tickets->firstItem() }}-{{ $tickets->lastItem() }}
                            </small>
                        @endif
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-light-primary" onclick="location.reload()" title="Actualizar">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button type="button" class="btn btn-light-primary" id="toggleViewMode" title="Cambiar vista">
                            <i class="fas fa-th-list"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tickets List --}}
            <ul class="list-group list-group-flush" style="height: calc(100vh - 250px); overflow-y: auto;" data-simplebar>
                @forelse($tickets as $ticket)
                    <li class="list-group-item p-3 border-bottom hover-shadow transition-all d-flex align-items-start gap-2">
                        <div class="form-check mt-1 flex-shrink-0">
                            <input class="form-check-input ticket-checkbox" type="checkbox" value="{{ $ticket->id }}">
                        </div>
                        <a href="{{ route('manager.helpdesk.tickets.show', $ticket->id) }}" class="text-decoration-none d-block flex-grow-1">
                            {{-- Ticket Header --}}
                            <div class="d-flex align-items-start justify-content-between mb-2">
                                <div class="flex-grow-1 d-flex align-items-center gap-2 flex-wrap">
                                    <span class="badge" style="background-color: {{ $ticket->category->color }}; font-size: 0.75rem;">
                                        <i class="{{ $ticket->category->icon }} me-1"></i>{{ $ticket->category->name }}
                                    </span>
                                    <span class="badge bg-light text-dark border">#{{ $ticket->ticket_number }}</span>
                                    @if($ticket->sla_first_response_breached || $ticket->sla_resolution_breached)
                                        <span class="badge bg-danger-subtle text-danger border-0" title="SLA incumplido">
                                            <i class="fas fa-exclamation-circle"></i> SLA Vencido
                                        </span>
                                    @elseif($ticket->sla_first_response_due_at && $ticket->sla_first_response_due_at->diffInHours() < 2)
                                        <span class="badge bg-warning-subtle text-warning border-0" title="SLA próximo a vencer">
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
                                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-semibold" style="width: 28px; height: 28px; font-size: 12px;">
                                    {{ strtoupper(substr($ticket->customer->name, 0, 1)) }}
                                </div>
                                <small class="text-muted fw-medium">{{ Str::limit($ticket->customer->name, 30) }}</small>
                            </div>

                            {{-- Footer: Status, Priority, Assignee --}}
                            <div class="d-flex align-items-center justify-content-between mt-3 pt-2 border-top">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="badge" style="background-color: {{ $ticket->status->color }}; font-size: 0.7rem;">
                                        {{ $ticket->status->name }}
                                    </span>
                                    @php
                                        $priorityColors = [
                                            'urgent' => 'danger',
                                            'high' => 'warning',
                                            'normal' => 'info',
                                            'low' => 'secondary'
                                        ];
                                        $priorityColor = $priorityColors[$ticket->priority] ?? 'secondary';
                                        $priorityIcons = [
                                            'urgent' => 'fas fa-fire',
                                            'high' => 'fas fa-arrow-up',
                                            'normal' => 'fas fa-minus',
                                            'low' => 'fas fa-arrow-down'
                                        ];
                                        $priorityIcon = $priorityIcons[$ticket->priority] ?? 'fas fa-minus';
                                    @endphp
                                    <span class="badge bg-{{ $priorityColor }}-subtle text-{{ $priorityColor }} border-0" style="font-size: 0.7rem;">
                                        <i class="{{ $priorityIcon }}"></i> {{ ucfirst($ticket->priority) }}
                                    </span>
                                </div>
                                @if($ticket->assignee)
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 20px; height: 20px; font-size: 10px;">
                                            {{ strtoupper(substr($ticket->assignee->name, 0, 1)) }}
                                        </div>
                                        <small class="text-muted fw-medium">{{ Str::limit($ticket->assignee->name, 15) }}</small>
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
                            <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-ticket-alt text-muted" style="font-size: 2rem;"></i>
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
                                    <i class="fas fa-plus me-1"></i> Crear Primer Ticket
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

        {{-- Right Section: Empty State --}}
        <div class="flex-fill d-flex align-items-center justify-content-center">
            <div class="text-center">
                <i class="fas fa-ticket-alt display-1 text-muted mb-4"></i>
                <h5 class="text-muted">Selecciona un ticket para ver los detalles</h5>
                <p class="text-muted">O crea un nuevo ticket usando el botón de arriba</p>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .hover-shadow {
        transition: all 0.2s ease-in-out;
    }
    .hover-shadow:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        transform: translateY(-2px);
        background-color: #f8f9fa !important;
    }
    .transition-all {
        transition: all 0.2s ease-in-out;
    }
    .list-group-item-action {
        cursor: pointer;
    }
    .list-group-item-action:active {
        transform: scale(0.98);
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        // Toggle filters panel
        $('#toggleFilters').on('click', function() {
            const panel = $('#filtersPanel');
            const icon = $(this).find('i');

            panel.slideToggle(300);
            icon.toggleClass('fa-filter fa-times');
        });

        // Show success/error messages
        @if(session('success'))
            toastr.success('{{ session('success') }}', 'Éxito');
        @endif

        @if(session('error'))
            toastr.error('{{ session('error') }}', 'Error');
        @endif

        // Search form auto-submit on Enter
        $('input[name="search"]').on('keypress', function(e) {
            if (e.which === 13) {
                $(this).closest('form').submit();
            }
        });

        // Bulk: select all
        $(document).on('change', '#select-all', function() {
            $('.ticket-checkbox').prop('checked', this.checked);
            updateBulkBar();
        });

        // Bulk: individual checkbox
        $(document).on('change', '.ticket-checkbox', function() {
            const total = $('.ticket-checkbox').length;
            const checked = $('.ticket-checkbox:checked').length;
            $('#select-all').prop('indeterminate', checked > 0 && checked < total);
            $('#select-all').prop('checked', checked === total);
            updateBulkBar();
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
        const ids = $('.ticket-checkbox:checked').map(function() { return this.value; }).get();
        if (ids.length === 0) return;

        if (action === 'assign') {
            $('#bulk-agent-select').removeClass('d-none');
            $('#bulk-status-select').addClass('d-none');
            $('#bulk-submit-btn').removeClass('d-none');
            return;
        }

        if (action === 'change_status') {
            $('#bulk-status-select').removeClass('d-none');
            $('#bulk-agent-select').addClass('d-none');
            $('#bulk-submit-btn').removeClass('d-none');
            return;
        }

        if (action === 'delete' && !confirm('¿Eliminar ' + ids.length + ' ticket(s)? Esta acción no se puede deshacer.')) {
            return;
        }

        $('#bulk-agent-select, #bulk-status-select, #bulk-submit-btn').addClass('d-none');
        submitBulkForm(action, ids);
    }

    $('#bulk-submit-btn').on('click', function() {
        const action = $('#bulk-action-input').val();
        const ids = $('.ticket-checkbox:checked').map(function() { return this.value; }).get();
        submitBulkForm(action, ids);
    });

    function submitBulkForm(action, ids) {
        $('#bulk-action-input').val(action);
        $('#bulk-ticket-ids').empty();
        ids.forEach(function(id) {
            $('#bulk-ticket-ids').append('<input type="hidden" name="ticket_ids[]" value="' + id + '">');
        });
        $('#bulk-form').submit();
    }
</script>
@endpush
