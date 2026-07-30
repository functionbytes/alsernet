@extends('layouts.theme')

@section('title', 'Automatizacion de Proveedores')

@section('page_header')
    @include('core::components.card', ['title' => 'Automatizacion de Proveedores'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Panel de automatizacion</h5>
                        <p class="small mb-0 text-muted">Gestiona workflows, ejecuciones y disparadores automaticos</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.suppliers.automation.workflows.create') }}" class="btn btn-primary btn-sm">
                            Nuevo workflow
                        </a>
                        <div class="dropdown">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-ellipsis-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a href="#" class="dropdown-item" id="runAllBtn">Ejecutar todos los workflows</a></li>
                                <li><a href="#" class="dropdown-item" id="refreshStatsBtn">Actualizar estadisticas</a></li>
                                <li><a href="{{ route('settings.suppliers.automation.logs') }}" class="dropdown-item">Ver logs del sistema</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a href="#" class="dropdown-item text-danger" id="clearFailedBtn">Limpiar ejecuciones fallidas</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Workflows activos</h6>
                                <h2 class="fw-bold mb-1" id="activeWorkflows">{{ $stats['active_workflows'] ?? 0 }}</h2>
                                <small class="text-muted">En funcionamiento</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Pendientes</h6>
                                <h2 class="fw-bold mb-1" id="pendingExecutions">{{ $stats['pending_executions'] ?? 0 }}</h2>
                                <small class="text-muted">Ejecuciones en cola</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Fallidas hoy</h6>
                                <h2 class="fw-bold mb-1 text-danger" id="failedExecutions">{{ $stats['failed_executions_today'] ?? 0 }}</h2>
                                <small class="text-muted">Ejecuciones con error</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total hoy</h6>
                                <h2 class="fw-bold mb-1">{{ $stats['total_executions_today'] ?? 0 }}</h2>
                                <small class="text-muted">Ejecuciones totales</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <ul class="nav nav-pills user-profile-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $tab === 'workflows' ? 'active' : '' }}"
                            data-bs-toggle="pill" data-bs-target="#workflows" type="button" role="tab">
                        <span class="d-none d-md-block">Workflows</span>
                        <span class="badge bg-info ms-2">{{ $workflows->total() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $tab === 'executions' ? 'active' : '' }}"
                            data-bs-toggle="pill" data-bs-target="#executions" type="button" role="tab">
                        <span class="d-none d-md-block">Ejecuciones</span>
                        <span class="badge bg-info ms-2">{{ $executions->total() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $tab === 'triggers' ? 'active' : '' }}"
                            data-bs-toggle="pill" data-bs-target="#triggers" type="button" role="tab">
                        <span class="d-none d-md-block">Disparadores</span>
                        <span class="badge bg-info ms-2">{{ $triggers->total() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $tab === 'alerts' ? 'active' : '' }}"
                            data-bs-toggle="pill" data-bs-target="#alerts" type="button" role="tab">
                        <span class="d-none d-md-block">Alertas</span>
                        <span class="badge bg-info ms-2">{{ $alerts->total() }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content">

                {{-- ════════════════════ WORKFLOWS ════════════════════ --}}
                <div class="tab-pane fade {{ $tab === 'workflows' ? 'show active' : '' }}" id="workflows" role="tabpanel">

                    {{-- Filtros --}}
                    <div class="card-body border-bottom">
                        @php
                            $wfActiveFilters = collect([$wfType, $wfStatus])->filter(fn ($v) => $v !== null && $v !== '')->count();
                        @endphp
                        <form id="wf-filter-form" method="GET" action="{{ route('settings.suppliers.automation.index') }}">
                            <input type="hidden" name="tab" value="workflows">
                            <input type="hidden" name="wf_type"   id="wf-filter-type"   value="{{ $wfType }}">
                            <input type="hidden" name="wf_status" id="wf-filter-status" value="{{ $wfStatus }}">
                            <div class="d-flex align-items-center gap-2">
                                <input type="search" name="wf_search" class="form-control flex-grow-1"
                                       placeholder="Buscar por nombre o descripcion..." value="{{ $wfSearch }}">
                                <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                        data-bs-toggle="modal" data-bs-target="#wf-filter-modal" title="Filtros avanzados">
                                    <i class="fas fa-filter"></i>
                                    @if($wfActiveFilters > 0)
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary" style="font-size:0.6rem;">{{ $wfActiveFilters }}</span>
                                    @endif
                                </button>
                                <div class="d-flex gap-1 flex-shrink-0">
                                    <button type="submit" class="btn btn-primary" title="Buscar"><i class="fas fa-magnifying-glass"></i></button>
                                    @if($wfSearch || $wfActiveFilters > 0)
                                        <a href="{{ route('settings.suppliers.automation.index') }}?tab=workflows" class="btn btn-secondary" title="Limpiar filtros"><i class="fas fa-xmark"></i></a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Ejecuciones</th>
                                        <th class="text-center">Tasa exito</th>
                                        <th class="text-center">Prioridad</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($workflows as $workflow)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $workflow->name }}</div>
                                                @if ($workflow->description)
                                                    <small class="text-muted">{{ Str::limit($workflow->description, 60) }}</small>
                                                @endif
                                            </td>
                                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $workflow->workflow_type }}</span></td>
                                            <td class="text-center">
                                                @if ($workflow->is_active)
                                                    <span class="badge bg-success-subtle text-success">Activo</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $workflow->total_executions }}</td>
                                            <td class="text-center">{{ $workflow->success_rate }}%</td>
                                            <td class="text-center">{{ $workflow->priority }}</td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-ellipsis-vertical"></i></a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a href="#" class="dropdown-item run-workflow" data-uid="{{ $workflow->uid }}"><i class="fas fa-play me-2 text-success"></i>Ejecutar</a></li>
                                                        <li><a href="{{ route('settings.suppliers.automation.workflows.edit', $workflow->uid) }}" class="dropdown-item"><i class="fas fa-pen me-2 text-primary"></i>Editar</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a href="#" class="dropdown-item text-danger delete-workflow" data-uid="{{ $workflow->uid }}" data-name="{{ $workflow->name }}"><i class="fas fa-trash me-2"></i>Eliminar</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted py-5">No hay workflows con los filtros aplicados</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($workflows->hasPages())
                        <div class="card-footer bg-white border-top">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <span class="text-muted small">Mostrando {{ $workflows->firstItem() }}–{{ $workflows->lastItem() }} de {{ $workflows->total() }}</span>
                                <nav>{{ $workflows->appends(request()->except('wpage'))->links('pagination::bootstrap-5') }}</nav>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ════════════════════ EJECUCIONES ════════════════════ --}}
                <div class="tab-pane fade {{ $tab === 'executions' ? 'show active' : '' }}" id="executions" role="tabpanel">

                    {{-- Filtros --}}
                    <div class="card-body border-bottom">
                        @php
                            $exActiveFilters = collect([$exStatus, $exTrigger])->filter(fn ($v) => $v !== null && $v !== '')->count();
                        @endphp
                        <form id="ex-filter-form" method="GET" action="{{ route('settings.suppliers.automation.index') }}">
                            <input type="hidden" name="tab" value="executions">
                            <input type="hidden" name="ex_status"  id="ex-filter-status"  value="{{ $exStatus }}">
                            <input type="hidden" name="ex_trigger" id="ex-filter-trigger" value="{{ $exTrigger }}">
                            <div class="d-flex align-items-center gap-2">
                                <input type="search" name="ex_search" class="form-control flex-grow-1"
                                       placeholder="Buscar por workflow o proveedor..." value="{{ $exSearch }}">
                                <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                        data-bs-toggle="modal" data-bs-target="#ex-filter-modal" title="Filtros avanzados">
                                    <i class="fas fa-filter"></i>
                                    @if($exActiveFilters > 0)
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary" style="font-size:0.6rem;">{{ $exActiveFilters }}</span>
                                    @endif
                                </button>
                                <div class="d-flex gap-1 flex-shrink-0">
                                    <button type="submit" class="btn btn-primary" title="Buscar"><i class="fas fa-magnifying-glass"></i></button>
                                    @if($exSearch || $exActiveFilters > 0)
                                        <a href="{{ route('settings.suppliers.automation.index') }}?tab=executions" class="btn btn-secondary" title="Limpiar filtros"><i class="fas fa-xmark"></i></a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Workflow</th>
                                        <th>Proveedor</th>
                                        <th class="text-center">Estado</th>
                                        <th>Disparador</th>
                                        <th>Inicio / Fin</th>
                                        <th class="text-center">Duracion</th>
                                        <th class="text-center">Reintentos</th>
                                        <th>Resultado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($executions as $execution)
                                        @php
                                            $exColors = [
                                                'pending' => 'warning', 'queued' => 'info', 'running' => 'primary',
                                                'completed' => 'success', 'failed' => 'danger', 'timeout' => 'dark', 'cancelled' => 'secondary',
                                            ];
                                            $exLabels = [
                                                'pending' => 'Pendiente', 'queued' => 'En cola', 'running' => 'Ejecutando',
                                                'completed' => 'Completado', 'failed' => 'Fallido', 'timeout' => 'Timeout', 'cancelled' => 'Cancelado',
                                            ];
                                            $exColor = $exColors[$execution->status] ?? 'secondary';
                                        @endphp
                                        <tr>
                                            <td>{{ $execution->workflow?->name ?? 'N/A' }}</td>
                                            <td>{{ $execution->supplier?->label ?? 'N/A' }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $exColor }}-subtle text-{{ $exColor }}">{{ $exLabels[$execution->status] ?? $execution->status }}</span>
                                            </td>
                                            <td><span class="text-muted">{{ $execution->trigger_type }}</span></td>
                                            <td>
                                                <small>
                                                    {{ $execution->started_at?->format('d/m/Y H:i') ?? '-' }}<br>
                                                    {{ $execution->completed_at?->format('d/m/Y H:i') ?? '-' }}
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                @if ($execution->duration_ms)<small>{{ round($execution->duration_ms / 1000, 2) }}s</small>@else - @endif
                                            </td>
                                            <td class="text-center">{{ $execution->retry_count }}</td>
                                            <td>
                                                @if ($execution->items_processed)
                                                    <small>{{ $execution->items_processed }} proc</small>
                                                @elseif ($execution->error_details && is_array($execution->error_details))
                                                    <small class="text-danger" title="{{ $execution->error_details['error'] ?? $execution->error_details['message'] ?? '' }}">
                                                        {{ Str::limit($execution->error_details['error'] ?? $execution->error_details['message'] ?? 'Error', 30) }}
                                                    </small>
                                                @else - @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-ellipsis-vertical"></i></a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a href="#" class="dropdown-item view-execution" data-uid="{{ $execution->uid }}"><i class="fas fa-eye me-2 text-info"></i>Ver detalles</a></li>
                                                        @if ($execution->status === 'failed')
                                                            <li><a href="#" class="dropdown-item retry-execution" data-uid="{{ $execution->uid }}"><i class="fas fa-rotate-right me-2 text-warning"></i>Reintentar</a></li>
                                                        @endif
                                                        @if (in_array($execution->status, ['pending', 'running', 'queued']))
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li><a href="#" class="dropdown-item text-danger cancel-execution" data-uid="{{ $execution->uid }}"><i class="fas fa-xmark me-2"></i>Cancelar</a></li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="9" class="text-center text-muted py-5">No hay ejecuciones con los filtros aplicados</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($executions->hasPages())
                        <div class="card-footer bg-white border-top">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <span class="text-muted small">Mostrando {{ $executions->firstItem() }}–{{ $executions->lastItem() }} de {{ $executions->total() }}</span>
                                <nav>{{ $executions->appends(request()->except('epage'))->links('pagination::bootstrap-5') }}</nav>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ════════════════════ DISPARADORES ════════════════════ --}}
                <div class="tab-pane fade {{ $tab === 'triggers' ? 'show active' : '' }}" id="triggers" role="tabpanel">

                    {{-- Filtros --}}
                    <div class="card-body border-bottom">
                        @php
                            $trActiveFilters = collect([$trType, $trStatus])->filter(fn ($v) => $v !== null && $v !== '')->count();
                        @endphp
                        <form id="tr-filter-form" method="GET" action="{{ route('settings.suppliers.automation.index') }}">
                            <input type="hidden" name="tab" value="triggers">
                            <input type="hidden" name="tr_type"   id="tr-filter-type"   value="{{ $trType }}">
                            <input type="hidden" name="tr_status" id="tr-filter-status" value="{{ $trStatus }}">
                            <div class="d-flex align-items-center gap-2">
                                <input type="search" name="tr_search" class="form-control flex-grow-1"
                                       placeholder="Buscar por nombre o descripcion..." value="{{ $trSearch }}">
                                <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                        data-bs-toggle="modal" data-bs-target="#tr-filter-modal" title="Filtros avanzados">
                                    <i class="fas fa-filter"></i>
                                    @if($trActiveFilters > 0)
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary" style="font-size:0.6rem;">{{ $trActiveFilters }}</span>
                                    @endif
                                </button>
                                <div class="d-flex gap-1 flex-shrink-0">
                                    <button type="submit" class="btn btn-primary" title="Buscar"><i class="fas fa-magnifying-glass"></i></button>
                                    @if($trSearch || $trActiveFilters > 0)
                                        <a href="{{ route('settings.suppliers.automation.index') }}?tab=triggers" class="btn btn-secondary" title="Limpiar filtros"><i class="fas fa-xmark"></i></a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th class="text-center">Estado</th>
                                        <th>Workflow</th>
                                        <th class="text-center">Total disparos</th>
                                        <th>Ultimo disparo</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($triggers as $trigger)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $trigger->name }}</div>
                                                @if ($trigger->description)
                                                    <small class="text-muted">{{ Str::limit($trigger->description, 60) }}</small>
                                                @endif
                                            </td>
                                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $trigger->trigger_type }}</span></td>
                                            <td class="text-center">
                                                @if ($trigger->is_enabled)
                                                    <span class="badge bg-success-subtle text-success">Activo</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                                @endif
                                            </td>
                                            <td>{{ $trigger->workflow?->name ?? 'N/A' }}</td>
                                            <td class="text-center">{{ $trigger->total_triggers }}</td>
                                            <td>
                                                @if ($trigger->last_triggered_at)
                                                    <small>{{ $trigger->last_triggered_at->format('d/m/Y H:i') }}</small>
                                                @else
                                                    <small class="text-muted">Nunca</small>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-ellipsis-vertical"></i></a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a href="#" class="dropdown-item"><i class="fas fa-pen me-2 text-primary"></i>Editar</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted py-5">No hay disparadores con los filtros aplicados</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($triggers->hasPages())
                        <div class="card-footer bg-white border-top">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <span class="text-muted small">Mostrando {{ $triggers->firstItem() }}–{{ $triggers->lastItem() }} de {{ $triggers->total() }}</span>
                                <nav>{{ $triggers->appends(request()->except('tpage'))->links('pagination::bootstrap-5') }}</nav>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ════════════════════ ALERTAS ════════════════════ --}}
                <div class="tab-pane fade {{ $tab === 'alerts' ? 'show active' : '' }}" id="alerts" role="tabpanel">

                    {{-- Filtros --}}
                    <div class="card-body border-bottom">
                        @php
                            $alActiveFilters = collect([$alSeverity, $alType, $alAck])->filter(fn ($v) => $v !== null && $v !== '')->count();
                        @endphp
                        <form id="al-filter-form" method="GET" action="{{ route('settings.suppliers.automation.index') }}">
                            <input type="hidden" name="tab" value="alerts">
                            <input type="hidden" name="al_severity" id="al-filter-severity" value="{{ $alSeverity }}">
                            <input type="hidden" name="al_type"     id="al-filter-type"     value="{{ $alType }}">
                            <input type="hidden" name="al_ack"      id="al-filter-ack"      value="{{ $alAck }}">
                            <div class="d-flex align-items-center gap-2">
                                <input type="search" name="al_search" class="form-control flex-grow-1"
                                       placeholder="Buscar por titulo o mensaje..." value="{{ $alSearch }}">
                                <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                        data-bs-toggle="modal" data-bs-target="#al-filter-modal" title="Filtros avanzados">
                                    <i class="fas fa-filter"></i>
                                    @if($alActiveFilters > 0)
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary" style="font-size:0.6rem;">{{ $alActiveFilters }}</span>
                                    @endif
                                </button>
                                <div class="d-flex gap-1 flex-shrink-0">
                                    <button type="submit" class="btn btn-primary" title="Buscar"><i class="fas fa-magnifying-glass"></i></button>
                                    @if($alSearch || $alActiveFilters > 0)
                                        <a href="{{ route('settings.suppliers.automation.index') }}?tab=alerts" class="btn btn-secondary" title="Limpiar filtros"><i class="fas fa-xmark"></i></a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tipo</th>
                                        <th class="text-center">Severidad</th>
                                        <th>Titulo</th>
                                        <th>Mensaje</th>
                                        <th>Fecha</th>
                                        <th class="text-center">Reconocida</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($alerts as $alert)
                                        @php
                                            $sevColors = ['critical' => 'danger', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
                                            $sevLabels = ['critical' => 'Critica', 'error' => 'Error', 'warning' => 'Advertencia', 'info' => 'Info'];
                                            $sevColor = $sevColors[$alert->severity] ?? 'secondary';
                                        @endphp
                                        <tr>
                                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $alert->alert_type }}</span></td>
                                            <td class="text-center"><span class="badge bg-{{ $sevColor }}-subtle text-{{ $sevColor }}">{{ $sevLabels[$alert->severity] ?? $alert->severity }}</span></td>
                                            <td>{{ $alert->title }}</td>
                                            <td><small class="text-muted">{{ Str::limit($alert->message, 60) }}</small></td>
                                            <td><small>{{ $alert->created_at->format('d/m/Y H:i') }}</small></td>
                                            <td class="text-center">
                                                @if ($alert->acknowledged_at)
                                                    <span class="badge bg-success-subtle text-success">Si</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">No</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-ellipsis-vertical"></i></a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a href="#" class="dropdown-item"><i class="fas fa-eye me-2 text-info"></i>Ver detalles</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted py-5">No hay alertas con los filtros aplicados</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($alerts->hasPages())
                        <div class="card-footer bg-white border-top">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <span class="text-muted small">Mostrando {{ $alerts->firstItem() }}–{{ $alerts->lastItem() }} de {{ $alerts->total() }}</span>
                                <nav>{{ $alerts->appends(request()->except('apage'))->links('pagination::bootstrap-5') }}</nav>
                            </div>
                        </div>
                    @endif
                </div>

            </div>{{-- tab-content --}}

        </div>{{-- card --}}
    </div>{{-- widget-content --}}

    {{-- ════════════════════ MODALES DE FILTROS ════════════════════ --}}

    {{-- Workflows --}}
    <div class="modal fade" id="wf-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Filtros avanzados — Workflows</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo</label>
                        <select id="wf-modal-type" class="form-select select2">
                            <option value="">Todos los tipos</option>
                            @foreach($workflowTypes as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="wf-modal-status" class="form-select select2">
                            <option value="">Todos</option>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" onclick="applyWfFilters()">Aplicar filtros</button>
                    <button type="button" class="btn btn-secondary w-100" onclick="clearWfFilters()">Limpiar filtros</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Ejecuciones --}}
    <div class="modal fade" id="ex-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Filtros avanzados — Ejecuciones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="ex-modal-status" class="form-select select2">
                            <option value="">Todos</option>
                            <option value="pending">Pendiente</option>
                            <option value="queued">En cola</option>
                            <option value="running">Ejecutando</option>
                            <option value="completed">Completado</option>
                            <option value="failed">Fallido</option>
                            <option value="timeout">Timeout</option>
                            <option value="cancelled">Cancelado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Disparador</label>
                        <select id="ex-modal-trigger" class="form-select select2">
                            <option value="">Todos</option>
                            @foreach($execTriggerTypes as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" onclick="applyExFilters()">Aplicar filtros</button>
                    <button type="button" class="btn btn-secondary w-100" onclick="clearExFilters()">Limpiar filtros</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Disparadores --}}
    <div class="modal fade" id="tr-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Filtros avanzados — Disparadores</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo</label>
                        <select id="tr-modal-type" class="form-select select2">
                            <option value="">Todos los tipos</option>
                            @foreach($triggerTypes as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="tr-modal-status" class="form-select select2">
                            <option value="">Todos</option>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" onclick="applyTrFilters()">Aplicar filtros</button>
                    <button type="button" class="btn btn-secondary w-100" onclick="clearTrFilters()">Limpiar filtros</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Alertas --}}
    <div class="modal fade" id="al-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Filtros avanzados — Alertas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Severidad</label>
                        <select id="al-modal-severity" class="form-select select2">
                            <option value="">Todas</option>
                            <option value="critical">Critica</option>
                            <option value="error">Error</option>
                            <option value="warning">Advertencia</option>
                            <option value="info">Info</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo</label>
                        <select id="al-modal-type" class="form-select select2">
                            <option value="">Todos los tipos</option>
                            @foreach($alertTypes as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reconocida</label>
                        <select id="al-modal-ack" class="form-select select2">
                            <option value="">Todas</option>
                            <option value="1">Si</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" onclick="applyAlFilters()">Aplicar filtros</button>
                    <button type="button" class="btn btn-secondary w-100" onclick="clearAlFilters()">Limpiar filtros</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Ejecutar todos los workflows -->
    <div class="modal fade" id="runAllModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ejecutar todos los workflows</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body"><p>Estas seguro de que deseas ejecutar todos los workflows activos?</p></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirmRunAllBtn">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Limpiar ejecuciones fallidas -->
    <div class="modal fade" id="clearFailedModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Limpiar ejecuciones fallidas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body"><p>Estas seguro de que deseas eliminar todas las ejecuciones fallidas? Esta accion no se puede deshacer.</p></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmClearFailedBtn">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Eliminar workflow -->
    <div class="modal fade" id="deleteWorkflowModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Eliminar workflow</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body"><p>Estas seguro de que deseas eliminar el workflow <strong id="deleteWorkflowName"></strong>?</p></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteWorkflowBtn">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Detalles de ejecucion -->
    <div class="modal fade" id="executionDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles de la ejecucion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="executionDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(function () {
            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            // ── Select2 dentro de cada modal de filtros ─────────────────────
            function s2(sel, parent) { $(sel).select2({ dropdownParent: $(parent), width: '100%' }); }
            s2('#wf-modal-type, #wf-modal-status', '#wf-filter-modal');
            s2('#ex-modal-status, #ex-modal-trigger', '#ex-filter-modal');
            s2('#tr-modal-type, #tr-modal-status', '#tr-filter-modal');
            s2('#al-modal-severity, #al-modal-type, #al-modal-ack', '#al-filter-modal');

            // Pre-cargar valores activos
            $('#wf-modal-type').val(@json($wfType)).trigger('change');
            $('#wf-modal-status').val(@json($wfStatus)).trigger('change');
            $('#ex-modal-status').val(@json($exStatus)).trigger('change');
            $('#ex-modal-trigger').val(@json($exTrigger)).trigger('change');
            $('#tr-modal-type').val(@json($trType)).trigger('change');
            $('#tr-modal-status').val(@json($trStatus)).trigger('change');
            $('#al-modal-severity').val(@json($alSeverity)).trigger('change');
            $('#al-modal-type').val(@json($alType)).trigger('change');
            $('#al-modal-ack').val(@json($alAck)).trigger('change');

            // ── Aplicar / limpiar filtros por tab ───────────────────────────
            window.applyWfFilters = function () {
                $('#wf-filter-type').val($('#wf-modal-type').val());
                $('#wf-filter-status').val($('#wf-modal-status').val());
                $('#wf-filter-form').submit();
            };
            window.clearWfFilters = function () {
                $('#wf-filter-type, #wf-filter-status').val('');
                $('#wf-filter-form').submit();
            };

            window.applyExFilters = function () {
                $('#ex-filter-status').val($('#ex-modal-status').val());
                $('#ex-filter-trigger').val($('#ex-modal-trigger').val());
                $('#ex-filter-form').submit();
            };
            window.clearExFilters = function () {
                $('#ex-filter-status, #ex-filter-trigger').val('');
                $('#ex-filter-form').submit();
            };

            window.applyTrFilters = function () {
                $('#tr-filter-type').val($('#tr-modal-type').val());
                $('#tr-filter-status').val($('#tr-modal-status').val());
                $('#tr-filter-form').submit();
            };
            window.clearTrFilters = function () {
                $('#tr-filter-type, #tr-filter-status').val('');
                $('#tr-filter-form').submit();
            };

            window.applyAlFilters = function () {
                $('#al-filter-severity').val($('#al-modal-severity').val());
                $('#al-filter-type').val($('#al-modal-type').val());
                $('#al-filter-ack').val($('#al-modal-ack').val());
                $('#al-filter-form').submit();
            };
            window.clearAlFilters = function () {
                $('#al-filter-severity, #al-filter-type, #al-filter-ack').val('');
                $('#al-filter-form').submit();
            };

            // ── Acciones del header ─────────────────────────────────────────
            $('#refreshStatsBtn').on('click', function (e) {
                e.preventDefault();
                $.get('{{ route('settings.suppliers.automation.stats') }}').always(function () { location.reload(); });
            });
            $('#runAllBtn').on('click', function (e) { e.preventDefault(); $('#runAllModal').modal('show'); });
            $('#confirmRunAllBtn').on('click', function () {
                $.post('{{ route('settings.suppliers.automation.workflows.run-all') }}', { _token: csrfToken }).always(function () { location.reload(); });
            });
            $('#clearFailedBtn').on('click', function (e) { e.preventDefault(); $('#clearFailedModal').modal('show'); });
            $('#confirmClearFailedBtn').on('click', function () {
                $.post('{{ route('settings.suppliers.automation.executions.clear-failed') }}', { _token: csrfToken }).always(function () { location.reload(); });
            });

            // ── Workflows ───────────────────────────────────────────────────
            $(document).on('click', '.run-workflow', function (e) {
                e.preventDefault();
                var url = '{{ route('settings.suppliers.automation.workflows.run', ':uid') }}'.replace(':uid', $(this).data('uid'));
                $.post(url, { _token: csrfToken }).always(function () { location.reload(); });
            });
            var deleteUid = null;
            $(document).on('click', '.delete-workflow', function (e) {
                e.preventDefault();
                deleteUid = $(this).data('uid');
                $('#deleteWorkflowName').text($(this).data('name'));
                $('#deleteWorkflowModal').modal('show');
            });
            $('#confirmDeleteWorkflowBtn').on('click', function () {
                if (!deleteUid) return;
                var url = '{{ route('settings.suppliers.automation.workflows.destroy', ':uid') }}'.replace(':uid', deleteUid);
                fetch(url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                }).then(() => location.reload()).catch(() => location.reload());
            });

            // ── Ejecuciones ─────────────────────────────────────────────────
            $(document).on('click', '.retry-execution', function (e) {
                e.preventDefault();
                var url = '{{ route('settings.suppliers.automation.executions.retry', ':uid') }}'.replace(':uid', $(this).data('uid'));
                $.post(url, { _token: csrfToken }).always(function () { location.reload(); });
            });
            $(document).on('click', '.cancel-execution', function (e) {
                e.preventDefault();
                var url = '{{ route('settings.suppliers.automation.executions.cancel', ':uid') }}'.replace(':uid', $(this).data('uid'));
                $.post(url, { _token: csrfToken }).always(function () { location.reload(); });
            });
            $(document).on('click', '.view-execution', function (e) {
                e.preventDefault();
                var url = '{{ route('settings.suppliers.automation.executions.show', ':uid') }}'.replace(':uid', $(this).data('uid'));
                $('#executionDetailsContent').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>');
                $('#executionDetailsModal').modal('show');
                $.get(url, function (response) {
                    if (response.success && response.data) {
                        var d = response.data;
                        var html = '<div class="row g-3">' +
                            '<div class="col-md-6"><label class="form-label text-muted">Workflow</label><p class="fw-semibold">' + (d.workflow_name || '-') + '</p></div>' +
                            '<div class="col-md-6"><label class="form-label text-muted">Proveedor</label><p class="fw-semibold">' + (d.supplier_name || '-') + '</p></div>' +
                            '<div class="col-md-6"><label class="form-label text-muted">Estado</label><p>' + (d.status_label || '-') + '</p></div>' +
                            '<div class="col-md-6"><label class="form-label text-muted">Disparador</label><p>' + (d.trigger_type || '-') + '</p></div>' +
                            '<div class="col-md-6"><label class="form-label text-muted">Inicio</label><p>' + (d.started_at || '-') + '</p></div>' +
                            '<div class="col-md-6"><label class="form-label text-muted">Fin</label><p>' + (d.completed_at || '-') + '</p></div>' +
                            '<div class="col-md-6"><label class="form-label text-muted">Duracion (ms)</label><p>' + (d.duration_ms || '-') + '</p></div>' +
                            '<div class="col-md-6"><label class="form-label text-muted">Reintentos</label><p>' + (d.retry_count || '0') + '</p></div>' +
                            '<div class="col-md-4"><label class="form-label text-muted">Procesados</label><p>' + (d.items_processed || '0') + '</p></div>' +
                            '<div class="col-md-4"><label class="form-label text-muted">Exitosos</label><p class="text-success">' + (d.items_succeeded || '0') + '</p></div>' +
                            '<div class="col-md-4"><label class="form-label text-muted">Fallidos</label><p class="text-danger">' + (d.items_failed || '0') + '</p></div>';
                        if (d.output_data && Object.keys(d.output_data).length > 0) {
                            html += '<div class="col-12"><label class="form-label text-muted">Datos de salida</label><pre class="bg-light p-2 rounded"><code>' + JSON.stringify(d.output_data, null, 2) + '</code></pre></div>';
                        }
                        if (d.error_details && Object.keys(d.error_details).length > 0) {
                            html += '<div class="col-12"><label class="form-label text-muted">Detalles del error</label><pre class="bg-light p-2 rounded text-danger"><code>' + JSON.stringify(d.error_details, null, 2) + '</code></pre></div>';
                        }
                        html += '</div>';
                        $('#executionDetailsContent').html(html);
                    } else {
                        $('#executionDetailsContent').html('<div class="alert alert-danger">Error al cargar los detalles</div>');
                    }
                }).fail(function () {
                    $('#executionDetailsContent').html('<div class="alert alert-danger">Error al cargar los detalles</div>');
                });
            });
        });
    </script>
@endpush
