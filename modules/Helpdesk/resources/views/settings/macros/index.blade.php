@extends('layouts.theme')

@section('title', 'Macros')

@push('styles')
<style>
.hd-filter-badge { font-size: .6rem; }
.hd-bulk-toolbar { z-index: 1050; }
</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Macros'])
@endsection

@section('content')

    @include('core::components.alerts')

    <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Macros configurados</h5>
                        <p class="small mb-0 text-muted">Conjuntos de acciones predefinidas que los agentes pueden ejecutar con un clic</p>
                    </div>
                    <a href="{{ route('settings.helpdesk.macros.create') }}" class="btn btn-primary">
                        Nuevo macro
                    </a>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                <small class="text-muted">Macros configurados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title  mb-2">Globales</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['shared'] }}</h4>
                                <small class="text-muted">Disponibles para todos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title  mb-2">Personales</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['personal'] }}</h4>
                                <small class="text-muted">Solo del agente creador</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title  mb-2">Ejecutados</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total_runs']) }}</h4>
                                <small class="text-muted">Total de ejecuciones</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                @php
                    $activeFilterCount = collect(['visibility', 'status'])->filter(fn($k) => request($k))->count();
                    $hasAnyFilter = $activeFilterCount > 0 || request('search');
                @endphp
                <form id="macros-filter-form" method="GET" action="{{ route('settings.helpdesk.macros.index') }}">
                    <input type="hidden" name="visibility" id="filter-visibility" value="{{ request('visibility') }}">
                    <input type="hidden" name="status"     id="filter-status"     value="{{ request('status') }}">

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por nombre..."
                               value="{{ request('search') }}">

                        <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#macros-filter-modal" title="Filtros avanzados">
                            <i class="fas fa-filter"></i>
                            @if($activeFilterCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary hd-filter-badge">{{ $activeFilterCount }}</span>
                            @endif
                        </button>

                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if($hasAnyFilter)
                                <a href="{{ route('settings.helpdesk.macros.index') }}"
                                   class="btn btn-secondary" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($activeFilterCount > 0)
                        <div class="d-flex gap-2 flex-wrap mt-4">
                            <div>
                                <h6 class="mb-1">Filtrados:</h6>
                            </div>
                            @if(request('visibility'))
                                <span class="badge bg-primary-subtle text-primary  py-1 px-2">
                                    Visibilidad: {{ request('visibility') === 'shared' ? 'Global' : 'Personal' }}
                                </span>
                            @endif
                            @if(request('status'))
                                <span class="badge bg-primary-subtle text-primary  py-1 px-2">
                                    Estado: {{ request('status') === 'active' ? 'Activo' : 'Inactivo' }}
                                </span>
                            @endif
                        </div>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($macros->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Descripcion</th>
                                    <th scope="col" class="text-center">Acciones</th>
                                    <th scope="col" class="text-center">Visibilidad</th>
                                    <th scope="col" class="text-center">Estado</th>
                                    <th scope="col" class="text-center">Ejecutados</th>
                                    <th scope="col" class="text-center">Opciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($macros as $macro)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $macro->id }}"></td>
                                        <td>
                                            <strong>{{ $macro->name }}</strong>
                                        </td>
                                        <td>
                                            <span class="text-muted small">
                                                {{ $macro->description ? Str::limit($macro->description, 60) : '—' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                {{ count($macro->actions ?? []) }}
                                                {{ Str::plural('accion', count($macro->actions ?? [])) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($macro->is_shared)
                                                <span class="badge bg-primary-subtle text-primary">Global</span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning">Personal</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($macro->is_active)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            {{ number_format($macro->usage_count ?? 0) }}
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.macros.edit', $macro) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item delete-btn"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#delete-modal"
                                                            data-id="{{ $macro->id }}"
                                                            data-url="{{ route('settings.helpdesk.macros.destroy', $macro) }}"
                                                            data-title="Eliminar macro: {{ $macro->name }}">
                                                            Eliminar
                                                        </button>
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
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-bolt fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay macros configurados</h6>
                            <p class="text-muted mb-3">
                                @if(request('search') || request('visibility') || request('status'))
                                    No se encontraron resultados para los filtros aplicados
                                @else
                                    Crea tu primer macro para agilizar las acciones de tus agentes
                                @endif
                            </p>
                            @unless(request('search') || request('visibility') || request('status'))
                                <a href="{{ route('settings.helpdesk.macros.create') }}" class="btn btn-sm btn-primary">
                                    Crear primer macro
                                </a>
                            @endunless
                        </div>
                    </div>
                @endif
            </div>

            @if($macros->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong>{{ $macros->firstItem() }}</strong> a <strong>{{ $macros->lastItem() }}</strong>
                            de <strong>{{ $macros->total() }}</strong> macros
                        </div>
                        {{ $macros->appends(request()->input())->links() }}
                    </div>
                </div>
            @endif

    </div>

    @include('core::components.delete')

    {{-- Filter modal --}}
    <div class="modal fade" id="macros-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Visibilidad</label>
                        <select id="modal-visibility" class="form-control select2-filter-modal">
                            <option value="">Todas las visibilidades</option>
                            <option value="shared" {{ request('visibility') === 'shared' ? 'selected' : '' }}>Global</option>
                            <option value="personal" {{ request('visibility') === 'personal' ? 'selected' : '' }}>Personal</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="modal-status" class="form-control select2-filter-modal">
                            <option value="">Todos</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activo</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="macros-filter-apply-btn" class="btn btn-primary w-100 mb-1">
                        Aplicar filtros
                    </button>
                    <button type="button" id="macros-filter-clear-btn" class="btn btn-secondary w-100">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none hd-bulk-toolbar">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> macro(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
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

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
<script>
@php
    $hdMacrosConfig = [
    'flashSuccess' => session('success'),
    'flashError' => session('error'),
    'bulkUrl' => route('settings.helpdesk.macros.bulk-action')
];
@endphp
window.HdMacrosConfig = @json($hdMacrosConfig);
</script>
<script>window.HdSettingsCommonSkipAutoInit = true;</script>
<script src="{{ asset('vendor/helpdesk/settings/settings-common.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/settings-common.js')) }}" defer></script>
<script src="{{ asset('vendor/helpdesk/settings/macros-index.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/macros-index.js')) }}" defer></script>
@endpush
