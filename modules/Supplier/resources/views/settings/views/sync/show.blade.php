@extends('layouts.theme')

@section('title', $pageTitle)

@section('page_header')
    @include('core::components.card', ['title' => $pageTitle])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        @php
            $statusColor = match ($batch->status) {
                'completed' => 'success',
                'running'   => 'warning',
                'failed'    => 'danger',
                'cancelled' => 'secondary',
                default     => 'info',
            };
            $progressBarColor = match ($batch->status) {
                'completed' => 'success',
                'failed'    => 'danger',
                'cancelled' => 'secondary',
                default     => 'primary',
            };
        @endphp

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1 fw-bold">{{ $batch->batch_name }}</h5>
                        <p class="small mb-0 text-muted">
                            <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} me-1">{{ $batch->status_name }}</span>
                            <span class="badge bg-secondary-subtle text-secondary me-1">{{ $batch->sync_type_name }}</span>
                            <span class="text-muted">Batch #{{ $batch->id }}</span>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.suppliers.sync.index') }}" class="btn btn-secondary btn-sm">
                           Volver
                        </a>
                        @if($failuresCount > 0)
                            <a href="{{ route('settings.suppliers.sync.failures.index', ['batch_id' => $batch->id]) }}"
                               class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-up-right-from-square"></i>
                            </a>
                        @endif
                        @if($batch->canRetry())
                            <button type="button" class="btn btn-warning btn-sm btn-retry-batch" data-batch-id="{{ $batch->id }}">
                               Reintentar
                            </button>
                        @endif
                        @if($batch->canCancel())
                            <button type="button" class="btn btn-danger btn-sm btn-cancel-batch" data-batch-id="{{ $batch->id }}">
                                Cancelar
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Live progress (solo cuando está en ejecución) --}}
            @if($batch->isRunning())
            <div class="card-body border-bottom bg-light-subtle" id="live-progress-section">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold small">
                        Sincronización en progreso…
                    </span>
                    <span class="badge bg-warning-subtle text-warning" id="lp-status">En progreso</span>
                </div>
                <div class="progress mb-2" style="height:10px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                         id="lp-bar" style="width:0%"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <span><span id="lp-processed">0</span> procesados · <span id="lp-failed">0</span> fallidos</span>
                    <span id="lp-pct">0%</span>
                </div>
            </div>
            @endif

            {{-- Stats --}}
            <div class="card-body border-bottom" id="stats-section">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Progreso</h6>
                                <h2 class="fw-bold mb-1">{{ $batch->progress_percentage }}%</h2>
                                @php
                                    $displayTotal = $batch->total_items > 0
                                        ? $batch->total_items
                                        : ($batch->processed_items + $batch->failed_items);
                                @endphp
                                <small class="text-muted">{{ number_format($batch->processed_items) }} / {{ number_format($displayTotal) }} registros</small>
                                <div class="progress mt-2" style="height: 4px;">
                                    <div class="progress-bar bg-{{ $progressBarColor }}" style="width: {{ $batch->progress_percentage }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Tasa de éxito</h6>
                                <h2 class="fw-bold mb-1 text-{{ $batch->success_rate >= 95 ? 'success' : ($batch->success_rate >= 80 ? 'warning' : 'danger') }}">
                                    {{ $batch->success_rate }}%
                                </h2>
                                <small class="text-muted">{{ number_format($batch->failed_items) }} fallidos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Duración</h6>
                                <h2 class="fw-bold mb-1">
                                    @if($batch->duration_seconds)
                                        @if($batch->duration_seconds < 60)
                                            {{ round($batch->duration_seconds) }}s
                                        @else
                                            {{ floor($batch->duration_seconds / 60) }}m {{ round($batch->duration_seconds % 60) }}s
                                        @endif
                                    @else
                                        —
                                    @endif
                                </h2>
                                <small class="text-muted">{{ $batch->started_at?->format('d/m/Y H:i') ?? 'Sin iniciar' }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Disparado por</h6>
                                <h2 class="fw-bold mb-1">{{ $batch->triggered_by ?? '—' }}</h2>
                                <small class="text-muted">{{ $batch->created_at->format('d/m/Y H:i') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <ul class="nav nav-pills user-profile-tab" id="syncShowTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $tab === 'failures' ? '' : 'active' }}"
                            data-bs-toggle="pill" data-bs-target="#logs-pane" type="button" role="tab">
                        <span class="d-none d-md-block">Logs de sincronización</span>
                        <span class="badge bg-info ms-2">{{ $logsCount }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $tab === 'failures' ? 'active' : '' }}"
                            data-bs-toggle="pill" data-bs-target="#failures-pane" type="button" role="tab">
                        <span class="d-none d-md-block">Fallos</span>
                        <span class="badge {{ $failuresCount > 0 ? 'bg-black' : 'bg-info' }} ms-2">{{ $failuresCount }}</span>
                    </button>
                </li>
            </ul>

            {{-- Tab content --}}
            <div class="tab-content">

                {{-- Tab: Logs --}}
                <div class="tab-pane fade {{ $tab === 'failures' ? '' : 'show active' }}" id="logs-pane" role="tabpanel">

                    {{-- Filtros --}}
                    <div class="card-body border-bottom">
                        @php $logsActiveFilters = $logsResult ? 1 : 0; @endphp
                        <form id="logs-filter-form" method="GET" action="{{ route('settings.suppliers.sync.show', $batch->id) }}">
                            <input type="hidden" name="tab" value="logs">
                            <input type="hidden" name="logs_result" id="logs-filter-result" value="{{ $logsResult }}">
                            <div class="d-flex align-items-center gap-2">
                                <input type="search" name="logs_search" class="form-control flex-grow-1"
                                       placeholder="Buscar por mensaje, entidad o acción..." value="{{ $logsSearch }}">
                                <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                        data-bs-toggle="modal" data-bs-target="#logs-filter-modal" title="Filtros avanzados">
                                    <i class="fas fa-filter"></i>
                                    @if($logsActiveFilters > 0)
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary" style="font-size:0.6rem;">{{ $logsActiveFilters }}</span>
                                    @endif
                                </button>
                                <div class="d-flex gap-1 flex-shrink-0">
                                    <button type="submit" class="btn btn-primary" title="Buscar">
                                        <i class="fas fa-magnifying-glass"></i>
                                    </button>
                                    @if($logsSearch || $logsResult)
                                        <a href="{{ route('settings.suppliers.sync.show', $batch->id) }}?tab=logs" class="btn btn-secondary" title="Limpiar filtros">
                                            <i class="fas fa-xmark"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card-body">
                        @if($logs->count() > 0)
                            <div class="mb-3">
                                <h6 class="mb-1 fw-bold">Registro de actividad</h6>
                                <p class="text-muted small mb-0">{{ $logs->total() }} entradas encontradas</p>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Entidad</th>
                                            <th>Acción</th>
                                            <th class="text-center">Resultado</th>
                                            <th class="text-center" style="width:48px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($logs as $log)
                                            @php
                                                $resultBadge = match ($log->result) {
                                                    'success' => 'bg-success-subtle text-success',
                                                    'failed'  => 'bg-danger-subtle text-danger',
                                                    'skipped' => 'bg-warning-subtle text-warning',
                                                    default   => 'bg-secondary-subtle text-secondary',
                                                };
                                            @endphp
                                            <tr data-log-id="{{ $log->id }}">
                                                <td>
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ $log->entity_type }}</span>
                                                    @if($log->entity_id)
                                                        <small class="text-muted ms-1">#{{ $log->entity_id }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ $log->action }}</td>
                                                <td class="text-center">
                                                    <span class="badge {{ $resultBadge }}">{{ $log->result }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="dropdown">
                                                        <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-ellipsis-vertical"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="showLogDetail({{ $log->id }}); return false;">
                                                                    Ver detalle
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
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-clipboard-list fa-3x mb-3 d-block opacity-25"></i>
                                @if($logsSearch || $logsResult)
                                    <h6 class="mb-1">Sin resultados</h6>
                                    <p class="small mb-0">No hay logs que coincidan con los filtros aplicados.</p>
                                @else
                                    <h6 class="mb-1">Sin logs registrados</h6>
                                    <p class="small mb-0">No hay actividad registrada para este batch.</p>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if($logs->count() > 0)
                        @include('supplier::settings.views.sync.partials.show-table-footer', ['paginator' => $logs, 'batch' => $batch, 'tab' => 'logs'])
                    @endif
                </div>

                {{-- Tab: Fallos --}}
                <div class="tab-pane fade {{ $tab === 'failures' ? 'show active' : '' }}" id="failures-pane" role="tabpanel">

                    {{-- Filtros --}}
                    <div class="card-body border-bottom">
                        @php $failuresActiveFilters = collect([$failuresStatus, $failuresType])->filter(fn ($v) => $v !== null && $v !== '')->count(); @endphp
                        <form id="failures-filter-form" method="GET" action="{{ route('settings.suppliers.sync.show', $batch->id) }}">
                            <input type="hidden" name="tab" value="failures">
                            <input type="hidden" name="failures_status" id="failures-filter-status" value="{{ $failuresStatus }}">
                            <input type="hidden" name="failures_type"   id="failures-filter-type"   value="{{ $failuresType }}">
                            <div class="d-flex align-items-center gap-2">
                                <input type="search" name="failures_search" class="form-control flex-grow-1"
                                       placeholder="Buscar por ID ERP o mensaje de error..." value="{{ $failuresSearch }}">
                                <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                        data-bs-toggle="modal" data-bs-target="#failures-filter-modal" title="Filtros avanzados">
                                    <i class="fas fa-filter"></i>
                                    @if($failuresActiveFilters > 0)
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary" style="font-size:0.6rem;">{{ $failuresActiveFilters }}</span>
                                    @endif
                                </button>
                                <div class="d-flex gap-1 flex-shrink-0">
                                    <button type="submit" class="btn btn-primary" title="Buscar">
                                        <i class="fas fa-magnifying-glass"></i>
                                    </button>
                                    @if($failuresSearch || $failuresStatus || $failuresType)
                                        <a href="{{ route('settings.suppliers.sync.show', $batch->id) }}?tab=failures" class="btn btn-secondary" title="Limpiar filtros">
                                            <i class="fas fa-xmark"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card-body">
                        @if($failures->count() > 0)
                            <div class="mb-3">
                                <h6 class="mb-1 fw-bold">Listado de fallos</h6>
                                <p class="text-muted small mb-0">{{ $failures->total() }} fallos encontrados</p>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="3%"><input type="checkbox" class="form-check-input" id="select-all"></th>
                                            <th>Tipo de fallo</th>
                                            <th>ID ERP</th>
                                            <th>Error</th>
                                            <th class="text-center">Reintentos</th>
                                            <th class="text-center">Estado</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($failures as $failure)
                                            @php
                                                $failureTypeColors = [
                                                    'sin_proveedor'   => 'danger',
                                                    'sin_categoria'   => 'warning',
                                                    'error_api'       => 'info',
                                                    'error_db'        => 'dark',
                                                    'datos_invalidos' => 'secondary',
                                                ];
                                                $ftColor = $failureTypeColors[$failure->failure_type] ?? 'secondary';
                                            @endphp
                                            <tr data-failure-id="{{ $failure->id }}">
                                                <td>
                                                    <input type="checkbox" class="form-check-input failure-checkbox" value="{{ $failure->id }}">
                                                </td>
                                                <td>
                                                    @if($failure->failure_type)
                                                        <span class="badge bg-{{ $ftColor }}-subtle text-{{ $ftColor }}">
                                                            {{ $failure->failure_type_name }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>
                                                <td><code class="bg-light px-2 py-1 rounded">{{ $failure->erp_id ?? '—' }}</code></td>
                                                <td>
                                                    <span class="small text-danger font-monospace"
                                                          style="max-width:340px; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                                                          title="{{ $failure->error_message }}">
                                                        {{ $failure->error_message }}
                                                    </span>
                                                    <small class="text-muted">{{ $failure->created_at->format('d/m/Y H:i') }}</small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge {{ $failure->retry_count >= $failure->max_retries ? 'bg-danger' : 'bg-light text-dark' }}">
                                                        {{ $failure->retry_count }}/{{ $failure->max_retries }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    @if($failure->failure_status === 'resolved')
                                                        <span class="badge bg-success-subtle text-success">Resuelto</span>
                                                    @elseif($failure->failure_status === 'pending')
                                                        <span class="badge bg-warning-subtle text-warning">Pendiente</span>
                                                    @else
                                                        <span class="badge bg-secondary-subtle text-secondary">{{ $failure->failure_status }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <div class="dropdown">
                                                        <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-ellipsis-vertical"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="retryFailure({{ $failure->id }}); return false;">
                                                                    Reintentar sincronización
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="showFailureDetail({{ $failure->id }}); return false;">
                                                                    Ver información y error
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="#" onclick="deleteFailure({{ $failure->id }}); return false;">
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
                            <div class="text-center py-5 text-muted">
                                @if($failuresSearch || $failuresStatus || $failuresType)
                                    <i class="fas fa-magnifying-glass fa-3x mb-3 d-block opacity-25"></i>
                                    <h6 class="mb-1">Sin resultados</h6>
                                    <p class="small mb-0">No hay fallos que coincidan con los filtros aplicados.</p>
                                @else
                                    <i class="fas fa-check-circle fa-3x text-success mb-3 d-block opacity-50"></i>
                                    <h6 class="mb-1">No hay fallos registrados</h6>
                                    <p class="small mb-0">Todas las sincronizaciones de este batch se completaron exitosamente.</p>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if($failures->count() > 0)
                        @include('supplier::settings.views.sync.partials.show-table-footer', ['paginator' => $failures, 'batch' => $batch, 'tab' => 'failures'])

                    @endif
                </div>

            </div>{{-- tab-content --}}

        </div>{{-- card --}}

    </div>{{-- widget-content --}}

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> fallo(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="retry">Reintentar</option>
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

    {{-- Modal filtros avanzados — Logs --}}
    <div class="modal fade" id="logs-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Filtros avanzados — Logs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Resultado</label>
                        <select id="logs-modal-result" class="form-select select2">
                            <option value="">Todos los resultados</option>
                            <option value="success">Éxito</option>
                            <option value="failed">Fallido</option>
                            <option value="skipped">Omitido</option>
                            <option value="warning">Advertencia</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" onclick="applyLogsFilters()">Aplicar filtros</button>
                    <button type="button" class="btn btn-secondary w-100" onclick="clearLogsFilters()">Limpiar filtros</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal filtros avanzados — Fallos --}}
    <div class="modal fade" id="failures-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Filtros avanzados — Fallos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="failures-modal-status" class="form-select select2">
                            <option value="">Todos los estados</option>
                            <option value="pending">Pendiente</option>
                            <option value="resolved">Resuelto</option>
                            <option value="acknowledged">Reconocido</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de fallo</label>
                        <select id="failures-modal-type" class="form-select select2">
                            <option value="">Todos los tipos</option>
                            <option value="sin_proveedor">Sin proveedor</option>
                            <option value="sin_categoria">Sin categoría</option>
                            <option value="error_api">Error API</option>
                            <option value="error_db">Error DB</option>
                            <option value="datos_invalidos">Datos inválidos</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" onclick="applyFailuresFilters()">Aplicar filtros</button>
                    <button type="button" class="btn btn-secondary w-100" onclick="clearFailuresFilters()">Limpiar filtros</button>
                </div>
            </div>
        </div>
    </div>

{{-- Modal detalle de fallo --}}
<div class="modal fade" id="failureDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    Detalle del fallo
                    <span id="fd-erp-id" class="badge bg-secondary-subtle text-secondary ms-2 fw-normal font-monospace" style="font-size:.75rem;"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="fd-body">
                <div class="text-center py-5 text-muted" id="fd-loading">
                    <p class="small mb-0">Cargando información…</p>
                </div>
                <div id="fd-content" class="d-none">

                    {{-- Error --}}
                    <div class="p-4 border-bottom">
                        <p class="text-uppercase fw-bold text-muted mb-2" >Error de sincronización</p>
                        <div class="bg-light border rounded py-2 px-3 mb-0 d-flex align-items-start gap-2">
                            <div>
                                <strong id="fd-failure-type" class="d-block small mb-1 text-secondary"></strong>
                                <span id="fd-error-message" class="small text-muted"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Datos del modelo ERP --}}
                    <div class="p-4 border-bottom">
                        <p class="text-uppercase fw-bold text-muted mb-3" >Datos del modelo ERP</p>
                        <div class="row g-3" id="fd-model-fields">
                            {{-- generado por JS --}}
                        </div>
                    </div>

                    {{-- Categoría --}}
                    <div class="p-4 border-bottom d-none" id="fd-category-section">
                        <p class="text-uppercase fw-bold text-muted mb-3" >Categoría ERP</p>
                        <div id="fd-category-fields" class="row g-2"></div>
                    </div>

                    {{-- Artículos / atributos --}}
                    <div class="p-4 border-bottom d-none" id="fd-attrs-section">
                        <p class="text-uppercase fw-bold text-muted mb-2" >
                            Artículos relacionados
                            <span id="fd-attrs-count" class="badge bg-secondary ms-1"></span>
                        </p>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Código</th>
                                        <th>Descripción</th>
                                        <th class="text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="fd-attrs-body"></tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Meta --}}
                    <div class="p-4">
                        <p class="text-uppercase fw-bold text-muted mb-3" >Información del fallo</p>
                        <div class="row g-2" id="fd-meta-fields"></div>
                    </div>

                </div>
            </div>
            <div class="modal-footer flex-column gap-2 pt-3">
                <button type="button" id="fd-btn-retry" class="btn btn-primary w-100">Reintentar</button>
                <button type="button" class="btn btn-light w-100 text-muted" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal detalle de log --}}
<div class="modal fade" id="logDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    Detalle del log
                    <span id="ld-erp-id" class="badge bg-secondary-subtle text-secondary ms-2 fw-normal font-monospace" style="font-size:.75rem;"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="ld-body">
                <div class="text-center py-5 text-muted" id="ld-loading">
                    <i class="fas fa-spinner fa-spin fa-2x mb-3 d-block"></i>
                    <p class="small mb-0">Cargando información…</p>
                </div>
                <div id="ld-content" class="d-none">

                    {{-- Resultado / acción --}}
                    <div class="p-4 border-bottom">
                        <p class="text-uppercase fw-bold text-muted mb-2" style="font-size:.68rem;letter-spacing:.08em;">Resultado de la operación</p>
                        <div class="bg-light border rounded py-2 px-3 d-flex align-items-start gap-2">
                            <i class="fas fa-circle-dot mt-1 flex-shrink-0 text-muted"></i>
                            <div>
                                <strong id="ld-action" class="d-block small mb-1 text-secondary"></strong>
                                <span id="ld-message" class="small text-muted"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Error (visible solo si hay error) --}}
                    <div class="p-4 border-bottom d-none" id="ld-error-section">
                        <p class="text-uppercase fw-bold text-muted mb-2" style="font-size:.68rem;letter-spacing:.08em;">Error</p>
                        <div class="bg-light border rounded py-2 px-3 d-flex align-items-start gap-2">
                            <i class="fas fa-triangle-exclamation mt-1 flex-shrink-0 text-muted"></i>
                            <div>
                                <strong id="ld-error-code" class="d-block small mb-1 text-secondary"></strong>
                                <span id="ld-error-message" class="small text-muted font-monospace"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Datos de la entidad --}}
                    <div class="p-4 border-bottom">
                        <p class="text-uppercase fw-bold text-muted mb-3" style="font-size:.68rem;letter-spacing:.08em;">Entidad sincronizada</p>
                        <div class="row g-3" id="ld-entity-fields"></div>
                    </div>

                    {{-- Cambios (visible si hay changes) --}}
                    <div class="p-4 border-bottom d-none" id="ld-changes-section">
                        <p class="text-uppercase fw-bold text-muted mb-2" style="font-size:.68rem;letter-spacing:.08em;">Cambios detectados</p>
                        <pre id="ld-changes-pre" class="bg-light border rounded p-3 small text-muted mb-0" style="max-height:200px;overflow:auto;font-size:.72rem;"></pre>
                    </div>

                    {{-- Metadata --}}
                    <div class="p-4 border-bottom d-none" id="ld-metadata-section">
                        <p class="text-uppercase fw-bold text-muted mb-2" style="font-size:.68rem;letter-spacing:.08em;">Metadata adicional</p>
                        <pre id="ld-metadata-pre" class="bg-light border rounded p-3 small text-muted mb-0" style="max-height:200px;overflow:auto;font-size:.72rem;"></pre>
                    </div>

                    {{-- Info técnica --}}
                    <div class="p-4">
                        <p class="text-uppercase fw-bold text-muted mb-3" style="font-size:.68rem;letter-spacing:.08em;">Información técnica</p>
                        <div class="row g-2" id="ld-meta-fields"></div>
                    </div>

                </div>
            </div>
            <div class="modal-footer flex-column gap-2 pt-3">
                <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-light w-100 text-muted" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
<script>
$(document).ready(function () {

    const csrf            = $('meta[name="csrf-token"]').attr('content');
    const retryUrl        = '{{ route("settings.suppliers.sync.retry", "__ID__") }}';
    const destroyUrl      = '{{ route("settings.suppliers.sync.bulk-delete") }}';
    const failureShowUrl  = '{{ route("settings.suppliers.sync.show", "__ID__") }}';
    const logShowUrl      = '{{ route("settings.suppliers.sync.logs.show", ["batchId" => $batch->id, "logId" => "__LOG_ID__"]) }}';
    const bulkRetryUrl    = '{{ route("settings.suppliers.sync.bulk-retry") }}';
    const bulkDelUrl      = '{{ route("settings.suppliers.sync.bulk-delete") }}';
    const batchRetryUrl   = '{{ route("settings.suppliers.sync.retry", "__ID__") }}';
    const batchCancelUrl  = '{{ route("settings.suppliers.sync.cancel", "__ID__") }}';
    const syncIndexUrl    = '{{ route("settings.suppliers.sync.index") }}';

    // ── Select2 dentro de los modales de filtros ─────────────────────────────
    $('#logs-modal-result').select2({ dropdownParent: $('#logs-filter-modal'), width: '100%' });
    $('#failures-modal-status, #failures-modal-type').select2({ dropdownParent: $('#failures-filter-modal'), width: '100%' });

    // Pre-cargar valores activos
    $('#logs-modal-result').val(@json($logsResult)).trigger('change');
    $('#failures-modal-status').val(@json($failuresStatus)).trigger('change');
    $('#failures-modal-type').val(@json($failuresType)).trigger('change');

    // Aplicar / limpiar filtros (mantienen el texto de búsqueda)
    window.applyLogsFilters = function () {
        $('#logs-filter-result').val($('#logs-modal-result').val());
        $('#logs-filter-form').submit();
    };
    window.clearLogsFilters = function () {
        $('#logs-filter-result').val('');
        $('#logs-filter-form').submit();
    };
    window.applyFailuresFilters = function () {
        $('#failures-filter-status').val($('#failures-modal-status').val());
        $('#failures-filter-type').val($('#failures-modal-type').val());
        $('#failures-filter-form').submit();
    };
    window.clearFailuresFilters = function () {
        $('#failures-filter-status, #failures-filter-type').val('');
        $('#failures-filter-form').submit();
    };

    // ── Bulk (tabla de fallos) ───────────────────────────────────────────────
    const bulk = window.BulkActions.init({ checkbox: '.failure-checkbox' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();
        if (!action)     { toastr.warning('Selecciona una acción'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un fallo'); return; }

        const $btn   = $(this).prop('disabled', true).text('Procesando...');
        const finish = () => $btn.prop('disabled', false).text('Aplicar');

        if (action === 'retry') {
            $.post(bulkRetryUrl, { ids: ids, _token: csrf })
                .done(r => {
                    toastr.success(r.message);
                    (r.errors || []).forEach(m => toastr.warning(m));
                    setTimeout(() => location.reload(), 900);
                })
                .fail(x => { toastr.error(x.responseJSON?.message || 'Error'); finish(); });
        } else if (action === 'delete') {
            if (!confirm('¿Eliminar ' + ids.length + ' fallo(s)?')) { finish(); return; }
            fetch(bulkDelUrl, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: ids })
            })
                .then(r => r.json())
                .then(d => { toastr.success(d.message); setTimeout(() => location.reload(), 900); })
                .catch(x => { toastr.error('Error'); finish(); });
        }
    });

    // ── Single retry / delete ────────────────────────────────────────────────
    window.retryFailure = function (id) {
        $.post(retryUrl.replace('__ID__', id), { _token: csrf })
            .done(r => {
                toastr.success(r.message);
                $('tr[data-failure-id="' + id + '"]').fadeOut(400, function () { $(this).remove(); });
            })
            .fail(x => toastr.error(x.responseJSON?.message || 'Error al reintentar'));
    };

    window.deleteFailure = function (id) {
        if (!confirm('¿Eliminar este fallo?')) return;
        fetch(destroyUrl.replace('__ID__', id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        })
            .then(r => r.json())
            .then(d => {
                toastr.success(d.message);
                $('tr[data-failure-id="' + id + '"]').fadeOut(400, function () { $(this).remove(); });
            })
            .catch(x => toastr.error('Error al eliminar'));
    };

    // ── Detalle de fallo ─────────────────────────────────────────────────────
    let _fdCurrentId = null;

    window.showFailureDetail = function (id) {
        _fdCurrentId = id;
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('failureDetailModal'));

        // Reset
        document.getElementById('fd-loading').classList.remove('d-none');
        document.getElementById('fd-content').classList.add('d-none');
        document.getElementById('fd-erp-id').textContent = '';

        modal.show();

        fetch(failureShowUrl.replace('__ID__', id), { headers: { Accept: 'application/json' } })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { toastr.error('No se pudo cargar el detalle'); return; }
                renderFailureDetail(data.failure);
            })
            .catch(() => toastr.error('Error de red al cargar detalle'));
    };

    function field(label, value, col) {
        col = col || 'col-md-4';
        const val = (value !== null && value !== undefined && value !== '') ? value : '<span class="text-muted">—</span>';
        return `<div class="${col}">
            <p class="text-uppercase text-muted mb-1" style="font-size:.65rem;letter-spacing:.06em;">${label}</p>
            <p class="small fw-semibold mb-0">${val}</p>
        </div>`;
    }

    function renderFailureDetail(f) {
        const d = f.changed_data || {};

        // ERP ID badge
        document.getElementById('fd-erp-id').textContent = f.erp_id ? '#' + f.erp_id : '';

        // Error section
        document.getElementById('fd-failure-type').textContent = f.failure_type_name || f.failure_type || '—';
        document.getElementById('fd-error-message').textContent = f.error_message || '—';

        // Model fields
        const modelHtml = [
            field('ID ERP', d.id),
            field('Código', d.code),
            field('Nombre', d.name || d.description, 'col-md-8'),
            field('Estado', d.available ? 'Activo' : 'Inactivo'),
            field('Web (publicar)', d.web !== undefined ? (d.web ? '1 — Publicar' : '0 — No publicar') : null),
            field('Proveedor ERP', d.supplier ? (d.supplier.id + ' — ' + d.supplier.description) : '<span class="text-danger fw-semibold">Sin proveedor</span>', 'col-md-8'),
        ].join('');
        document.getElementById('fd-model-fields').innerHTML = modelHtml;

        // Category
        if (d.categorie) {
            const c = d.categorie;
            const sport = c.sport ? `${c.sport.description} (${c.sport.id})` : null;
            document.getElementById('fd-category-fields').innerHTML = [
                field('ID', c.id),
                field('Descripción', c.description, 'col-md-5'),
                field('Deporte', sport, 'col-md-5'),
            ].join('');
            document.getElementById('fd-category-section').classList.remove('d-none');
        } else {
            document.getElementById('fd-category-section').classList.add('d-none');
        }

        // Attributes / articles
        const attrs = d.product_attributes || [];
        if (attrs.length > 0) {
            document.getElementById('fd-attrs-count').textContent = attrs.length;
            const rows = attrs.map(a =>
                `<tr>
                    <td class="font-monospace small">${a.id ?? '—'}</td>
                    <td class="font-monospace small">${a.code ?? '—'}</td>
                    <td class="small">${a.description ?? a.name ?? '—'}</td>
                    <td class="text-center"><span class="badge ${a.available ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'}">${a.available ? 'Activo' : 'Inactivo'}</span></td>
                </tr>`
            ).join('');
            document.getElementById('fd-attrs-body').innerHTML = rows;
            document.getElementById('fd-attrs-section').classList.remove('d-none');
        } else {
            document.getElementById('fd-attrs-section').classList.add('d-none');
        }

        // Meta
        const typeLabels = { product: 'Modelo ERP', provider: 'Proveedor', price: 'Precio', category: 'Categoría' };
        const statusColors = { pending: 'warning', resolved: 'success', acknowledged: 'info', archived: 'secondary' };
        const sc = statusColors[f.failure_status] || 'secondary';
        document.getElementById('fd-meta-fields').innerHTML = [
            field('Tipo sync', typeLabels[f.sync_type] || f.sync_type),
            field('Estado', `<span class="badge bg-${sc}-subtle text-${sc}">${f.failure_status_name}</span>`),
            field('Reintentos', `${f.retry_count} / ${f.max_retries}`),
            field('Fecha', f.created_at ? f.created_at.substring(0, 16).replace('T', ' ') : '—'),
        ].join('');

        document.getElementById('fd-loading').classList.add('d-none');
        document.getElementById('fd-content').classList.remove('d-none');
    }

    // Botón Reintentar dentro del modal
    document.getElementById('fd-btn-retry').addEventListener('click', function () {
        if (!_fdCurrentId) return;
        const $btn = $(this).prop('disabled', true).html('Reintentando…');
        $.post(retryUrl.replace('__ID__', _fdCurrentId), { _token: csrf })
            .done(r => {
                toastr.success(r.message || 'Reintento iniciado');
                bootstrap.Modal.getOrCreateInstance(document.getElementById('failureDetailModal')).hide();
                $('tr[data-failure-id="' + _fdCurrentId + '"]').fadeOut(400, function () { $(this).remove(); });
            })
            .fail(x => toastr.error(x.responseJSON?.message || 'Error al reintentar'))
            .always(() => $btn.prop('disabled', false).html('Reintentar'));
    });

    // ── Batch retry / cancel ─────────────────────────────────────────────────
    $('.btn-retry-batch').on('click', function () {
        const batchId = $(this).data('batch-id');
        Swal.fire({
            title: '¿Reintentar sincronización?',
            text: 'Se creará un nuevo batch con los mismos parámetros.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, reintentar',
            cancelButtonText: 'Cancelar',
        }).then(result => {
            if (!result.isConfirmed) return;
            fetch(batchRetryUrl.replace('__ID__', batchId), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    toastr.success(data.message);
                    setTimeout(() => { location.href = syncIndexUrl; }, 1500);
                } else {
                    toastr.error(data.message || 'Error al reintentar');
                }
            })
            .catch(e => toastr.error('Error: ' + e.message));
        });
    });

    // ── Live progress polling (solo cuando está running) ───────────────────────
    @if($batch->isRunning())
    (function () {
        const progressUrl = '{{ route("settings.suppliers.sync.progress", $batch->id) }}';
        let timer = null;

        function updateLiveBar(b) {
            const pct = b.total_items > 0
                ? Math.round((b.processed_items / b.total_items) * 100)
                : (b.status === 'running' ? 50 : 100);

            document.getElementById('lp-bar').style.width = pct + '%';
            document.getElementById('lp-pct').textContent = pct + '%';
            document.getElementById('lp-processed').textContent = b.processed_items;
            document.getElementById('lp-failed').textContent    = b.failed_items;

            // Actualizar también las stats cards del server-render mientras corre
            const statEl = document.querySelector('#stats-section h2');
            if (statEl) statEl.textContent = pct + '%';
        }

        function poll() {
            fetch(progressUrl, { headers: { Accept: 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    const b = data.batch;
                    updateLiveBar(b);

                    const terminal = ['completed', 'failed', 'cancelled'].includes(b.status);
                    if (terminal) {
                        clearInterval(timer);

                        const bar = document.getElementById('lp-bar');
                        bar.className = 'progress-bar ' + (b.status === 'completed' ? 'bg-success' : 'bg-danger');
                        bar.style.width = '100%';
                        document.getElementById('lp-pct').textContent = '100%';

                        const badge = document.getElementById('lp-status');
                        badge.textContent = b.status === 'completed' ? 'Completado' : b.status === 'failed' ? 'Fallido' : 'Cancelado';
                        badge.className   = 'badge ' + (b.status === 'completed' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger');

                        if (b.status === 'completed') toastr.success('Sincronización completada. ' + b.processed_items + ' procesados.');
                        else toastr.error('Sincronización ' + b.status + '.');

                        setTimeout(() => location.reload(), 1500);
                    }
                })
                .catch(() => {});
        }

        timer = setInterval(poll, 2000);
        poll();
    })();
    @endif

    $('.btn-cancel-batch').on('click', function () {
        const batchId = $(this).data('batch-id');
        Swal.fire({
            title: '¿Cancelar sincronización?',
            text: 'El proceso se marcará como cancelado.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No',
            confirmButtonColor: '#FA896B',
        }).then(result => {
            if (!result.isConfirmed) return;
            fetch(batchCancelUrl.replace('__ID__', batchId), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    toastr.success(data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    toastr.error(data.message || 'Error al cancelar');
                }
            })
            .catch(e => toastr.error('Error: ' + e.message));
        });
    });

    // ── Detalle de log ───────────────────────────────────────────────────────
    window.showLogDetail = function (id) {
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('logDetailModal'));

        document.getElementById('ld-loading').classList.remove('d-none');
        document.getElementById('ld-content').classList.add('d-none');
        document.getElementById('ld-erp-id').textContent = '';

        modal.show();

        fetch(logShowUrl.replace('__LOG_ID__', id), { headers: { Accept: 'application/json' } })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { toastr.error('No se pudo cargar el detalle'); return; }
                renderLogDetail(data.log);
            })
            .catch(() => toastr.error('Error de red al cargar detalle'));
    };

    function ldField(label, value, col) {
        col = col || 'col-md-4';
        const val = (value !== null && value !== undefined && value !== '') ? value : '<span class="text-muted">—</span>';
        return `<div class="${col}">
            <p class="text-uppercase text-muted mb-1" style="font-size:.65rem;letter-spacing:.06em;">${label}</p>
            <p class="small fw-semibold mb-0">${val}</p>
        </div>`;
    }

    function renderLogDetail(l) {
        const resultColors = { success: 'success', failed: 'danger', skipped: 'warning' };
        const rc = resultColors[l.result] || 'secondary';

        document.getElementById('ld-erp-id').textContent = l.erp_id ? '#' + l.erp_id : (l.entity_id ? '#' + l.entity_id : '');

        // Resultado / acción
        document.getElementById('ld-action').textContent   = (l.action || '—') + ' — ' + (l.result || '—');
        document.getElementById('ld-message').textContent  = l.message || '—';

        // Error
        if (l.error_message || l.error_code) {
            document.getElementById('ld-error-code').textContent    = l.error_code || '';
            document.getElementById('ld-error-message').textContent = l.error_message || '—';
            document.getElementById('ld-error-section').classList.remove('d-none');
        } else {
            document.getElementById('ld-error-section').classList.add('d-none');
        }

        // Entidad
        document.getElementById('ld-entity-fields').innerHTML = [
            ldField('Tipo de entidad', l.entity_type),
            ldField('ID entidad', l.entity_id),
            ldField('ID ERP', l.erp_id),
            ldField('Acción', l.action),
            ldField('Resultado', `<span class="badge bg-${rc}-subtle text-${rc}">${l.result || '—'}</span>`, 'col-md-4'),
            ldField('Duración', l.duration_ms !== null && l.duration_ms !== undefined ? l.duration_ms + ' ms' : null),
        ].join('');

        // Cambios
        if (l.changes && Object.keys(l.changes).length > 0) {
            document.getElementById('ld-changes-pre').textContent = JSON.stringify(l.changes, null, 2);
            document.getElementById('ld-changes-section').classList.remove('d-none');
        } else {
            document.getElementById('ld-changes-section').classList.add('d-none');
        }

        // Metadata
        if (l.metadata && Object.keys(l.metadata).length > 0) {
            document.getElementById('ld-metadata-pre').textContent = JSON.stringify(l.metadata, null, 2);
            document.getElementById('ld-metadata-section').classList.remove('d-none');
        } else {
            document.getElementById('ld-metadata-section').classList.add('d-none');
        }

        // Info técnica
        const date = l.created_at ? l.created_at.substring(0, 16).replace('T', ' ') : '—';
        document.getElementById('ld-meta-fields').innerHTML = [
            ldField('Disparado por', l.triggered_by),
            ldField('Reintentos', l.retry_count),
            ldField('Fecha', date),
        ].join('');

        document.getElementById('ld-loading').classList.add('d-none');
        document.getElementById('ld-content').classList.remove('d-none');
    }

});
</script>
@endpush
