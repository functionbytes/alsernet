@extends('layouts.theme')

@section('title', 'Integraciones de Slack')

@section('page_header')
    @include('core::components.card', ['title' => 'Integraciones de Slack'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Integraciones configuradas</h5>
                        <p class="small mb-0 text-muted">Recibe notificaciones de Helpdesk directamente en tus canales de Slack</p>
                    </div>
                    <a href="{{ route('settings.helpdesk.slack-integrations.create') }}" class="btn btn-primary">
                        Nueva integración
                    </a>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                <small class="text-muted">Integraciones configuradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title  mb-2">Activas</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                                <small class="text-muted">Enviando notificaciones</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title  mb-2">Inactivas</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['inactive'] }}</h4>
                                <small class="text-muted">Pausadas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="card-body border-bottom">
                @php
                    $activeFilterCount = collect(['status'])->filter(fn($k) => request($k) !== null && request($k) !== '')->count();
                    $hasAnyFilter = $activeFilterCount > 0 || request('search');
                @endphp
                <form id="slack-integrations-filter-form" method="GET" action="{{ route('settings.helpdesk.slack-integrations.index') }}">
                    <input type="hidden" name="status" id="filter-status" value="{{ request('status') }}">

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por canal..."
                               value="{{ request('search') }}">

                        <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#slack-integrations-filter-modal" title="Filtros avanzados">
                            <i class="fas fa-filter"></i>
                            @if($activeFilterCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">{{ $activeFilterCount }}</span>
                            @endif
                        </button>

                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if($hasAnyFilter)
                                <a href="{{ route('settings.helpdesk.slack-integrations.index') }}"
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
                            @if(request('status') !== null && request('status') !== '')
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">
                                    Estado: {{ request('status') === '1' ? 'Activas' : 'Inactivas' }}
                                </span>
                            @endif
                        </div>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($integrations->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th scope="col">Canal</th>
                                    <th scope="col">Eventos configurados</th>
                                    <th scope="col" class="text-center">Estado</th>
                                    <th scope="col" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($integrations as $integration)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $integration->id }}"></td>
                                        <td>
                                            <strong>#{{ $integration->channel_name }}</strong>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach(array_slice($integration->events ?? [], 0, 3) as $event)
                                                    <span class="badge bg-secondary-subtle text-secondary">
                                                        {{ \Modules\Helpdesk\Models\SlackIntegration::AVAILABLE_EVENTS[$event] ?? $event }}
                                                    </span>
                                                @endforeach
                                                @if(count($integration->events ?? []) > 3)
                                                    <span class="badge bg-light text-muted">
                                                        +{{ count($integration->events) - 3 }} mas
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($integration->is_active)
                                                <span class="badge bg-success-subtle text-success">Activa</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.slack-integrations.edit', $integration) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item btn-toggle"
                                                            data-url="{{ route('settings.helpdesk.slack-integrations.toggle', $integration) }}"
                                                            data-active="{{ $integration->is_active ? '1' : '0' }}">
                                                            {{ $integration->is_active ? 'Desactivar' : 'Activar' }}
                                                        </button>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item delete-btn"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#delete-modal"
                                                            data-id="{{ $integration->id }}"
                                                            data-url="{{ route('settings.helpdesk.slack-integrations.destroy', $integration) }}"
                                                            data-title="Eliminar integración: #{{ $integration->channel_name }}">
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
                                <i class="fab fa-slack fs-7"></i>
                            </div>
                            @if(request()->hasAny(['search', 'status']))
                                <h6 class="mb-1">No hay integraciones disponibles</h6>
                                <p class="small mb-0 text-muted">No se encontraron resultados para los filtros aplicados</p>
                            @else
                                <h6 class="mb-1">No hay integraciones de Slack</h6>
                                <p class="text-muted mb-3">Conecta Slack para recibir alertas de SLA, sentimiento y CSAT en tus canales</p>
                                <a href="{{ route('settings.helpdesk.slack-integrations.create') }}" class="btn btn-sm btn-primary">
                                    Crear primera integración
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            @if($integrations->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong>{{ $integrations->firstItem() }}</strong> a <strong>{{ $integrations->lastItem() }}</strong>
                            de <strong>{{ $integrations->total() }}</strong> integraciones
                        </div>
                        {{ $integrations->appends(request()->input())->links() }}
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Toggle form (hidden) --}}
    <form id="toggleForm" method="POST" class="d-none">
        @csrf
        @method('POST')
    </form>

    @include('core::components.delete')

    {{-- Filter modal --}}
    <div class="modal fade" id="slack-integrations-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="modal-status" class="form-control select2-filter-modal">
                            <option value="">Todos</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Activas</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactivas</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="slack-integrations-filter-apply-btn" class="btn btn-primary w-100 mb-1">
                        Aplicar filtros
                    </button>
                    <button type="button" id="slack-integrations-filter-clear-btn" class="btn btn-secondary w-100">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none">
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
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> integracion(es)</strong>.</p>
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
    $hdSlackIntegrationsConfig = [
    'flashSuccess' => session('success'),
    'flashError' => session('error'),
    'bulkUrl' => route('settings.helpdesk.slack-integrations.bulk-action')
];
@endphp
window.HdSlackIntegrationsConfig = @json($hdSlackIntegrationsConfig);
</script>
<script>window.HdSettingsCommonSkipAutoInit = true;</script>
<script src="{{ asset('vendor/helpdesk/settings/settings-common.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/settings-common.js')) }}" defer></script>
<script src="{{ asset('vendor/helpdesk/settings/slack-integrations-index.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/slack-integrations-index.js')) }}" defer></script>
@endpush
