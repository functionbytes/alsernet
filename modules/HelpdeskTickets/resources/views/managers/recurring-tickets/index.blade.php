@extends('layouts.theme')

@section('title', 'Tickets recurrentes')

@section('page_header')
    @include('core::components.card', ['title' => 'Tickets recurrentes'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Tickets recurrentes</h5>
                        <p class="small mb-0 text-muted">Tickets generados automaticamente segun la programacion configurada</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('manager.helpdesk.recurring-tickets.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo ticket recurrente
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">Schedules registrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                <small class="text-muted">En ejecucion</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Inactivos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['inactive']) }}</h4>
                                <small class="text-muted">Pausados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Proximos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['upcoming']) }}</h4>
                                <small class="text-muted">Con próxima ejecución futura</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Busqueda y filtros --}}
            <div class="card-body border-bottom">
                @php
                    $rtActiveFilterCount = collect(['frequency', 'category_id', 'status'])->filter(fn ($k) => request()->filled($k))->count();
                    $rtHasAnyFilter = $rtActiveFilterCount > 0 || request()->filled('search');
                    $rtFrequencyLabels = ['daily' => 'Diario', 'weekly' => 'Semanal', 'monthly' => 'Mensual', 'custom' => 'Personalizado'];
                @endphp

                <form method="GET" action="{{ route('manager.helpdesk.recurring-tickets.index') }}" id="rt-filter-form">
                    <input type="hidden" name="frequency" id="rt-filter-frequency" value="{{ request('frequency') }}">
                    <input type="hidden" name="category_id" id="rt-filter-category" value="{{ request('category_id') }}">
                    <input type="hidden" name="status" id="rt-filter-status" value="{{ request('status') }}">

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por nombre o asunto..."
                               value="{{ request('search') }}">

                        <x-filter-button target="rt-filter-modal" :count="$rtActiveFilterCount" />

                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if($rtHasAnyFilter)
                                <a href="{{ route('manager.helpdesk.recurring-tickets.index') }}" class="btn btn-secondary" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($rtActiveFilterCount > 0)
                        <div class="d-flex gap-2 flex-wrap mt-4 align-items-center">
                            <h6 class="mb-0">Filtrados:</h6>
                            @if(request('frequency'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">Frecuencia: {{ $rtFrequencyLabels[request('frequency')] ?? request('frequency') }}</span>
                            @endif
                            @if(request('category_id'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">Categoría: {{ $categories->firstWhere('id', (int) request('category_id'))?->name ?? request('category_id') }}</span>
                            @endif
                            @if(request('status'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">Estado: {{ request('status') === 'active' ? 'Activos' : 'Inactivos' }}</span>
                            @endif
                        </div>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($recurringTickets->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre</th>
                                    <th>Asunto</th>
                                    <th>Frecuencia</th>
                                    <th>Próxima ejecución</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recurringTickets as $recurring)
                                    @php
                                        $frequencyMap = [
                                            'daily'   => ['label' => 'Diario',        'class' => 'bg-primary-subtle text-primary'],
                                            'weekly'  => ['label' => 'Semanal',        'class' => 'bg-info-subtle text-info'],
                                            'monthly' => ['label' => 'Mensual',        'class' => 'bg-warning-subtle text-warning'],
                                            'custom'  => ['label' => 'Personalizado',  'class' => 'bg-secondary-subtle text-secondary'],
                                        ];
                                        $freq = $frequencyMap[$recurring->frequency] ?? ['label' => $recurring->frequency, 'class' => 'bg-secondary-subtle text-secondary'];
                                    @endphp
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $recurring->id }}"></td>
                                        <td>
                                            <div class="fw-semibold">{{ $recurring->name }}</div>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $recurring->subject }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $freq['class'] }}">{{ $freq['label'] }}</span>
                                            @if($recurring->frequency === 'custom' && $recurring->cron_expression)
                                                <br><small class="text-muted font-monospace">{{ $recurring->cron_expression }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($recurring->next_run_at)
                                                <span title="{{ $recurring->next_run_at->format('Y-m-d H:i') }}">
                                                    {{ $recurring->next_run_at->diffForHumans() }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($recurring->is_active)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('manager.helpdesk.recurring-tickets.edit', $recurring->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('manager.helpdesk.recurring-tickets.toggle', $recurring->id) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">
                                                                {{ $recurring->is_active ? 'Desactivar' : 'Activar' }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('manager.helpdesk.recurring-tickets.destroy', $recurring->id) }}"
                                                           data-title="Eliminar ticket recurrente: {{ $recurring->name }}">
                                                            Eliminar
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-clock fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">No hay tickets recurrentes configurados</h5>
                        <p class="text-muted mb-4">Crea tu primer schedule para generar tickets automaticamente</p>
                        <a href="{{ route('manager.helpdesk.recurring-tickets.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo ticket recurrente
                        </a>
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($recurringTickets->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $recurringTickets->firstItem() }} - {{ $recurringTickets->lastItem() }} de {{ $recurringTickets->total() }}
                        </div>
                        <div>
                            {{ $recurringTickets->appends(request()->input())->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    @include('core::components.delete')

    {{-- Filtros avanzados --}}
    <x-filter-shell id="rt-filter-modal"
                    :count="$rtActiveFilterCount"
                    apply-id="rt-filter-apply-btn"
                    clear-id="rt-filter-clear-btn">
        <div class="fs-field">
            <label class="form-label fw-semibold">Frecuencia</label>
            <select id="modal-frequency" class="form-control select2-filter-modal">
                <option value="">Todas las frecuencias</option>
                <option value="daily" @selected(request('frequency') === 'daily')>Diario</option>
                <option value="weekly" @selected(request('frequency') === 'weekly')>Semanal</option>
                <option value="monthly" @selected(request('frequency') === 'monthly')>Mensual</option>
                <option value="custom" @selected(request('frequency') === 'custom')>Personalizado</option>
            </select>
        </div>
        <div class="fs-field">
            <label class="form-label fw-semibold">Categoría</label>
            <select id="modal-category" class="form-control select2-filter-modal">
                <option value="">Todas las categorías</option>
                @foreach($categories as $rtCategory)
                    <option value="{{ $rtCategory->id }}" @selected(request('category_id') == $rtCategory->id)>{{ $rtCategory->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="fs-field">
            <label class="form-label fw-semibold">Estado</label>
            <select id="modal-status" class="form-control select2-filter-modal">
                <option value="">Activos e inactivos</option>
                <option value="active" @selected(request('status') === 'active')>Solo activos</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Solo inactivos</option>
            </select>
        </div>
    </x-filter-shell>

    {{-- Barra flotante de seleccion --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none rt-bulk-toolbar">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar accion
        </button>
    </div>

    {{-- Accion masiva --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Accion masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicara sobre <strong><span data-bulk-count>0</span> ticket(s) recurrente(s)</strong>.</p>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Accion</label>
                        <select id="bulk-action-select" class="form-select select2-bulk">
                            <option value="">Seleccionar accion...</option>
                            <option value="activate">Activar</option>
                            <option value="deactivate">Desactivar</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdesktickets/css/helpdesktickets-ui.css') }}?v={{ @filemtime(public_path('modules/helpdesktickets/css/helpdesktickets-ui.css')) }}">
@endpush

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
{{-- Solo datos: la lógica entera vive en recurring-tickets-index.js. --}}
<script>
window.hdtRecurringTicketsIndexConfig = {
    bulkActionUrl: @json(route('manager.helpdesk.recurring-tickets.bulk-action')),
    successMessage: @json(session('success')),
    errorMessage: @json(session('error')),
};
</script>
<script src="{{ asset('modules/helpdesktickets/js/recurring-tickets-index.js') }}"></script>
@endpush
