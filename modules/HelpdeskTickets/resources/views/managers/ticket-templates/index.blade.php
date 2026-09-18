@extends('layouts.theme')

@section('title', 'Plantillas de ticket')

@section('page_header')
    @include('core::components.card', ['title' => 'Plantillas de ticket'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Plantillas de ticket</h5>
                        <p class="small mb-0 text-muted">Plantillas reutilizables para crear tickets rapidamente — generales (compartidas) y personales</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('manager.helpdesk.ticket-templates.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva plantilla
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
                                <small class="text-muted">Plantillas registradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Generales</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['general']) }}</h4>
                                <small class="text-muted">Compartidas con todos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Mis plantillas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['mine']) }}</h4>
                                <small class="text-muted">Solo visibles para ti</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activas</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                <small class="text-muted">Disponibles para uso</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filtros --}}
            @php
                $ttFiltering = request()->hasAny(['search', 'category', 'priority', 'status']);
                $ttActiveFilterCount = collect(['category', 'priority', 'status'])->filter(fn ($k) => request($k))->count();
                $ttPriorityLabels = ['low' => 'Baja', 'normal' => 'Media', 'high' => 'Alta', 'urgent' => 'Urgente'];
            @endphp
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('manager.helpdesk.ticket-templates.index') }}" id="tt-filter-form">
                    <input type="hidden" name="category" id="tt-filter-category" value="{{ request('category') }}">
                    <input type="hidden" name="priority" id="tt-filter-priority" value="{{ request('priority') }}">
                    <input type="hidden" name="status" id="tt-filter-status" value="{{ request('status') }}">

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por nombre o asunto..."
                               value="{{ request('search') }}">

                        <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#tt-filter-modal" title="Filtros avanzados">
                            <i class="fas fa-filter"></i>
                            @if($ttActiveFilterCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary tt-filter-badge">
                                    {{ $ttActiveFilterCount }}
                                </span>
                            @endif
                        </button>

                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if($ttFiltering)
                                <a href="{{ route('manager.helpdesk.ticket-templates.index') }}" class="btn btn-secondary" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($ttActiveFilterCount > 0)
                        <div class="d-flex gap-2 flex-wrap mt-4">
                            <div><h6 class="mb-1">Filtrados:</h6></div>
                            @if(request('category'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    Categoría: {{ $categories->firstWhere('id', (int) request('category'))?->name ?? request('category') }}
                                </span>
                            @endif
                            @if(request('priority'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    Prioridad: {{ $ttPriorityLabels[request('priority')] ?? request('priority') }}
                                </span>
                            @endif
                            @if(request('status'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    Estado: {{ request('status') === 'active' ? 'Activas' : 'Inactivas' }}
                                </span>
                            @endif
                        </div>
                    @endif
                </form>
            </div>

            {{-- Tabs --}}
            <div class="card-body">
                <ul class="nav nav-pills user-profile-tab mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                                data-bs-toggle="pill" data-bs-target="#tpl-general"
                                type="button" role="tab" aria-controls="tpl-general" aria-selected="true">
                            Generales
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                                data-bs-toggle="pill" data-bs-target="#tpl-mine"
                                type="button" role="tab" aria-controls="tpl-mine" aria-selected="false">
                            Mis plantillas
                        </button>
                    </li>
                </ul>

                <div class="tab-content">

                    {{-- Generales --}}
                    <div class="tab-pane fade show active" id="tpl-general">
                        @if(! $canManageGeneral)
                            <p class="small text-muted mb-3">Las plantillas generales las gestiona un administrador. Aqui puedes verlas y usarlas al crear un ticket.</p>
                        @endif
                        @include('helpdesktickets::managers.ticket-templates._table', ['templates' => $general, 'canManage' => $canManageGeneral, 'group' => 'general'])
                    </div>

                    {{-- Mias --}}
                    <div class="tab-pane fade" id="tpl-mine">
                        <p class="small text-muted mb-3">Solo tu puedes ver, editar o eliminar estas plantillas.</p>
                        @include('helpdesktickets::managers.ticket-templates._table', ['templates' => $mine, 'canManage' => true, 'group' => 'mine'])
                    </div>

                </div>
            </div>

        </div>
    </div>

    {{-- Modal de filtros avanzados --}}
    <div class="modal fade" id="tt-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Categoría</label>
                        <select id="tt-modal-category" class="form-control select2-filter-modal">
                            <option value="">Todas las categorías</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((int) request('category') === $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Prioridad</label>
                        <select id="tt-modal-priority" class="form-control select2-filter-modal">
                            <option value="">Todas las prioridades</option>
                            <option value="low" @selected(request('priority') === 'low')>Baja</option>
                            <option value="normal" @selected(request('priority') === 'normal')>Media</option>
                            <option value="high" @selected(request('priority') === 'high')>Alta</option>
                            <option value="urgent" @selected(request('priority') === 'urgent')>Urgente</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="tt-modal-status" class="form-control select2-filter-modal">
                            <option value="">Activas e inactivas</option>
                            <option value="active" @selected(request('status') === 'active')>Solo activas</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Solo inactivas</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="tt-filter-apply-btn" class="btn btn-primary w-100 mb-1">Aplicar filtros</button>
                    <button type="button" id="tt-filter-clear-btn" class="btn btn-secondary w-100">Limpiar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk: una barra/modal por pestaña (bulk.js soporta grupos vía el sufijo
         del id — "bulk-toolbar-{group}" → busca "bulk-{group}-modal"), para no
         mezclar la selección de Generales con la de Mis plantillas. --}}
    @foreach(['general' => 'Generales', 'mine' => 'Mis plantillas'] as $group => $groupLabel)
        <div id="bulk-toolbar-{{ $group }}" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none tt-bulk-toolbar">
            <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-{{ $group }}-modal">
                <span data-bulk-count>0</span> seleccionada(s) — Aplicar acción
            </button>
        </div>

        <div class="modal fade" id="bulk-{{ $group }}-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Acción masiva — {{ $groupLabel }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> plantilla(s)</strong>.</p>
                        <div class="mb-0">
                            <label class="form-label fw-semibold">Acción</label>
                            <select id="bulk-{{ $group }}-action-select" class="form-select select2">
                                <option value="">Seleccionar acción...</option>
                                <option value="activate">Activar</option>
                                <option value="deactivate">Desactivar</option>
                                <option value="delete">Eliminar</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button id="bulk-{{ $group }}-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @include('core::components.delete')

@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('modules/helpdesktickets/css/helpdesktickets-ui.css') }}?v={{ @filemtime(public_path('modules/helpdesktickets/css/helpdesktickets-ui.css')) }}">
@endpush

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
{{-- Solo datos: la lógica entera vive en ticket-templates-index.js. --}}
<script>
window.hdtTicketTemplatesIndexConfig = {
    bulkActionUrl: @json(route('manager.helpdesk.ticket-templates.bulk-action')),
    successMessage: @json(session('success')),
    errorMessage: @json(session('error')),
};
</script>
<script src="{{ asset('modules/helpdesktickets/js/ticket-templates-index.js') }}"></script>
@endpush
