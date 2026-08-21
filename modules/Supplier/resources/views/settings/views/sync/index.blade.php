@extends('layouts.theme')

@section('title', $pageTitle)

@section('page_header')
    @include('core::components.card', ['title' => $pageTitle])
@endsection

@section('content')
<div class="widget-content searchable-container list">
    @include('core::components.alerts')

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Sincronización con ERP</h5>
                    <p class="small mb-0 text-muted">Gestión de sincronizaciones, horarios y configuración</p>
                </div>
                <div class="dropdown">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a href="#" class="dropdown-item" onclick="openScheduleModal(); return false;">
                                Nuevo horario
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a href="{{ route('settings.suppliers.sync.test.index') }}" class="dropdown-item">
                                Panel de pruebas
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('settings.suppliers.sync.failures.index') }}" class="dropdown-item">
                                Ver fallos de sincronización
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a href="{{ route('settings.suppliers.sync.cleanup.index') }}" class="dropdown-item text-danger">
                                 Limpiar datos de prueba
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a href="#" class="dropdown-item" onclick="saveSettings(); return false;">
                                Guardar configuración
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Worker warning --}}
        @if($stats['stale_pending'] > 0)
        <div class="alert alert-warning border-0 rounded-0 mb-0 d-flex align-items-center gap-2 px-4 py-2">
            <i class="fas fa-triangle-exclamation"></i>
            <span>
                Hay <strong>{{ $stats['stale_pending'] }}</strong>
                {{ Str::plural('lote', $stats['stale_pending']) }}
                pendiente{{ $stats['stale_pending'] > 1 ? 's' : '' }} desde hace más de 5 minutos.
                Es posible que el worker de la cola <code>sync</code> no esté activo.
                Ejecuta <code>supervisorctl status</code> en el servidor para verificarlo.
            </span>
        </div>
        @endif

        {{-- Stats --}}
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted mb-2">Total ejecuciones</h6>
                            <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                            <small class="text-muted">Lotes registrados</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted mb-2">En progreso</h6>
                            <h4 class="mb-1 fw-bold ">{{ $stats['running'] }}</h4>
                            <small class="text-muted">Ejecutándose ahora</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted mb-2">Completados</h6>
                            <h4 class="mb-1 fw-bold ">{{ $stats['completed'] }}</h4>
                            <small class="text-muted">Finalizados correctamente</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title text-muted mb-2">Fallidos</h6>
                            <h4 class="mb-1 fw-bold text-danger">{{ $stats['failed'] }}</h4>
                            <small class="text-muted">Con errores</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <ul class="nav nav-pills user-profile-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 active"
                        data-bs-toggle="pill" data-bs-target="#tab-schedules" type="button" role="tab">
                    <span class="d-none d-md-block">Horarios</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                        data-bs-toggle="pill" data-bs-target="#tab-history" type="button" role="tab">
                    <span class="d-none d-md-block">Historial</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                        data-bs-toggle="pill" data-bs-target="#tab-config" type="button" role="tab">
                    <span class="d-none d-md-block">Configuración</span>
                </button>
            </li>
        </ul>

        <div class="tab-content">

            {{-- Tab: Horarios --}}
            <div class="tab-pane fade show active" id="tab-schedules" role="tabpanel">
                <div class="card-body">
                    @if($schedules->count() > 0)
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Horarios programados</h6>
                        <p class="text-muted small mb-0">{{ $scheduleStats['total'] }} horario(s) — {{ $scheduleStats['enabled'] }} activo(s) — {{ $scheduleStats['errors'] }} con error</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" class="form-check-input" id="select-all-schedules"></th>
                                    <th class="ps-3">Tipo</th>
                                    <th>Etiqueta</th>
                                    <th>Hora</th>
                                    <th class="text-center">Activo</th>
                                    <th class="text-center">Última ejecución</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="schedules-tbody">
                                @foreach($schedules as $s)
                                    @php
                                        $isStuck = $s->last_run_status === 'running' && $s->last_run_at && $s->last_run_at->lt(now()->subMinutes(30));
                                        $resolvedStatus = $isStuck ? 'stuck' : $s->last_run_status;
                                    @endphp
                                    <tr data-uid="{{ $s->uid }}" data-sync-type="{{ $s->sync_type }}">
                                    <td>
                                        <input type="checkbox" class="form-check-input schedule-bulk-checkbox" value="{{ $s->uid }}">
                                    </td>
                                    <td class="ps-3">
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            {{ $s->sync_type === 'model' ? 'Modelos' : 'Productos' }}
                                        </span>
                                    </td>
                                    <td><strong>{{ $s->label }}</strong></td>
                                    <td><span class="badge bg-secondary-subtle text-dark">{{ $s->formatted_time }}</span></td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-flex justify-content-center mb-0">
                                            <input class="form-check-input schedule-toggle" type="checkbox" data-uid="{{ $s->uid }}" {{ $s->is_enabled ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted">{{ $s->last_run_at?->format('d/m/Y H:i') ?: '—' }}</small>
                                    </td>
                                    <td class="text-center">
                                        @if(!$s->is_enabled)
                                            <span class="badge bg-secondary-subtle text-secondary">Pausado</span>
                                        @elseif($resolvedStatus === 'running')
                                            <span class="badge bg-warning-subtle text-warning">Ejecutando</span>
                                        @elseif($resolvedStatus === 'stuck')
                                            <span class="badge bg-danger-subtle text-danger">Colgado</span>
                                        @elseif($resolvedStatus === 'success' || $resolvedStatus === 'completed')
                                            <span class="badge bg-success-subtle text-success">Completado</span>
                                        @elseif($resolvedStatus === 'error' || $resolvedStatus === 'failed')
                                            <span class="badge bg-danger-subtle text-danger">Error</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Sin ejecuciones</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-vertical"></i></a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="#" onclick="editSchedule('{{ $s->uid }}'); return false;">Editar</a></li>
                                                <li><a class="dropdown-item" href="#" onclick="triggerSchedule('{{ $s->uid }}'); return false;">Ejecutar ahora</a></li>
                                                @if($resolvedStatus === 'running' || $resolvedStatus === 'stuck')
                                                    <li><a class="dropdown-item" href="#" onclick="resetScheduleStatus('{{ $s->uid }}'); return false;">Limpiar estado</a></li>
                                                @endif
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="confirmDeleteSchedule('{{ $s->uid }}'); return false;">Eliminar</a></li>
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
                        <i class="fas fa-clock fa-3x mb-3 d-block opacity-25"></i>
                        <h6 class="mb-1">No hay horarios configurados</h6>
                        <p class="small mb-0">Crea un horario para automatizar las sincronizaciones.</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Tab: Historial --}}
            <div class="tab-pane fade" id="tab-history" role="tabpanel">

                {{-- Filtros --}}
                <div class="card-body border-bottom">
                    <form method="GET" action="{{ route('settings.suppliers.sync.index') }}">
                        <input type="hidden" name="tab" value="history">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div style="width: 180px; flex-shrink: 0;">
                                <select name="sync_type" class="form-control select2 w-100">
                                    <option value="">Todos los tipos</option>
                                    <option value="model"    {{ request('sync_type') === 'model'    ? 'selected' : '' }}>Modelo</option>
                                    <option value="product"  {{ request('sync_type') === 'product'  ? 'selected' : '' }}>Producto</option>
                                    <option value="provider" {{ request('sync_type') === 'provider' ? 'selected' : '' }}>Proveedor</option>
                                    <option value="category" {{ request('sync_type') === 'category' ? 'selected' : '' }}>Categoría</option>
                                </select>
                            </div>
                            <div style="width: 180px; flex-shrink: 0;">
                                <select name="status" class="form-control select2 w-100">
                                    <option value="">Todos los estados</option>
                                    <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Pendiente</option>
                                    <option value="running"   {{ request('status') === 'running'   ? 'selected' : '' }}>En progreso</option>
                                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completado</option>
                                    <option value="failed"    {{ request('status') === 'failed'    ? 'selected' : '' }}>Fallido</option>
                                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                                </select>
                            </div>
                            <div class="d-flex gap-1 flex-shrink-0">
                                <button type="submit" class="btn btn-primary" title="Buscar">
                                    <i class="fas fa-magnifying-glass"></i>
                                </button>
                                @if(request('status') || request('sync_type'))
                                    <a href="{{ route('settings.suppliers.sync.index') }}" class="btn btn-secondary" title="Limpiar filtros">
                                        <i class="fas fa-xmark"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    @if($batches->count() > 0)
                    <div class="mb-3">
                        <h6 class="mb-1 fw-bold">Historial de ejecuciones</h6>
                        <p class="text-muted small mb-0">{{ $batches->total() }} ejecuciones encontradas</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="sync-batches-table">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" class="form-check-input" id="select-all-batches"></th>
                                    <th class="ps-3">Nombre</th>
                                    <th>Tipo</th>
                                    <th>Origen</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Progreso</th>
                                    <th class="text-center">Fallidos</th>
                                    <th>Inicio</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($batches as $batch)
                                @php
                                    $isDead = $batch->status === 'running' && $batch->updated_at && $batch->updated_at->diffInMinutes(now()) > 120;
                                    $statusClass = match($batch->status) {
                                        'completed' => 'success',
                                        'running' => $isDead ? 'danger' : 'warning',
                                        'failed'    => 'secondary',
                                        'cancelled' => 'secondary',
                                        default     => 'secondary',
                                    };
                                    $statusLabel = match($batch->status) {
                                        'completed' => 'Completado',
                                        'running' => $isDead ? 'Atascado' : 'En progreso',
                                        'failed'    => 'Fallido',
                                        'cancelled' => 'Cancelado',
                                        default     => 'Pendiente',
                                    };
                                    $originBadge = match($batch->triggered_by) {
                                        'manual'   => '<span class="badge bg-primary-subtle text-primary">Manual</span>',
                                        'schedule' => '<span class="badge bg-secondary-subtle text-secondary">Programado</span>',
                                        'retry'    => '<span class="badge bg-warning-subtle text-warning">Reintento</span>',
                                        default    => '<span class="badge bg-secondary-subtle text-secondary">'.e($batch->triggered_by ?? 'Sistema').'</span>',
                                    };
                                @endphp
                                <tr data-batch-id="{{ $batch->id }}" data-status="{{ $batch->status }}">
                                    <td>
                                        <input type="checkbox" class="form-check-input batch-bulk-checkbox" value="{{ $batch->id }}" data-status="{{ $batch->status }}">
                                    </td>
                                    <td class="ps-3">
                                        <span class="fw-semibold">{{ $batch->batch_name }}</span>
                                        @if($isDead)
                                            <i class="fas fa-exclamation-triangle text-danger ms-1" title="Sin actividad desde {{ $batch->updated_at->format('d/m/Y H:i') }}"></i>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-secondary-subtle text-secondary">{{ $batch->sync_type_name ?? $batch->sync_type }}</span></td>
                                    <td>{!! $originBadge !!}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="text-center sync-progress-cell">
                                        @if($batch->exceeds_estimated_total)
                                            <span class="sync-processed">{{ $batch->processed_items + $batch->failed_items }}</span>
                                            <small class="d-block text-muted" style="font-size:.7rem;">procesados (estimado {{ $batch->total_items }})</small>
                                        @elseif($batch->total_items > 0)
                                            <span class="sync-processed">{{ $batch->processed_items }}</span> / <span class="sync-total">{{ $batch->total_items }}</span>
                                        @else
                                            <span class="sync-processed">{{ $batch->processed_items }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center sync-failed-cell">
                                        @if($batch->failed_items > 0)
                                            <a href="{{ route('settings.suppliers.sync.failures.index', ['batch_id' => $batch->id, 'tab' => 'failures']) }}"
                                               class="text-danger fw-semibold sync-failed-count text-decoration-none">
                                                {{ $batch->failed_items }}
                                            </a>
                                        @else
                                            <span class="text-muted sync-failed-count">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $batch->started_at?->format('d/m/Y H:i') ?? $batch->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-vertical"></i></a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a href="{{ route('settings.suppliers.sync.show', $batch->id) }}" class="dropdown-item">Ver detalle</a>
                                                </li>
                                                @if($batch->canCancel())
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a href="#" class="dropdown-item text-danger btn-cancel" data-batch-id="{{ $batch->id }}">Cancelar</a>
                                                    </li>
                                                @endif
                                                @if($batch->canRetry())
                                                    <li>
                                                        <a href="#" class="dropdown-item btn-retry" data-batch-id="{{ $batch->id }}">Reintentar</a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer bg-white border-top py-2 mt-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                @if($batches->total() > 0)
                                    <span class="text-muted small">
                                        Mostrando {{ $batches->firstItem() }}–{{ $batches->lastItem() }} de {{ $batches->total() }}
                                    </span>
                                @endif
                                <form method="GET" action="{{ route('settings.suppliers.sync.index') }}" class="d-inline-flex align-items-center gap-1 mb-0">
                                    <input type="hidden" name="tab" value="history">
                                    @foreach(request()->except(['per_page', 'page', 'tab']) as $key => $value)
                                        @if(is_array($value))
                                            @foreach($value as $v)
                                                <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                                            @endforeach
                                        @else
                                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                        @endif
                                    @endforeach
                                    <label class="text-muted small mb-0">Por página:</label>
                                    <select name="per_page" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                        @foreach([10, 20, 50, 100] as $opt)
                                            <option value="{{ $opt }}" {{ request('per_page', 15) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                        @endforeach
                                        <option value="200" {{ request('per_page') == '200' ? 'selected' : '' }}>200</option>
                                    </select>
                                </form>
                            </div>
                            @if($batches->hasPages())
                                <nav>{{ $batches->appends(request()->query())->links('pagination::bootstrap-5') }}</nav>
                            @endif
                        </div>
                    </div>
                    @else
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-list-check fa-3x mb-3 d-block opacity-25"></i>
                        @if(request('status') || request('sync_type'))
                            <h6 class="mb-1">Sin resultados</h6>
                            <p class="small mb-0">No hay ejecuciones que coincidan con los filtros aplicados.</p>
                        @else
                            <h6 class="mb-1">No hay ejecuciones registradas</h6>
                            <p class="small mb-0">Las sincronizaciones ejecutadas aparecerán aquí.</p>
                        @endif
                    </div>
                    @endif
                </div>

            </div>

            {{-- Tab: Configuración --}}
            <div class="tab-pane fade" id="tab-config" role="tabpanel">
                <div class="card-body">
                    <form id="syncSettingsForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">URL base del ERP</label>
                                <input type="url" class="form-control" name="erp_base_url" value="{{ $settings['erp_base_url'] }}" placeholder="http://...">
                                <small class="text-muted">Endpoint base Oracle ERP</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Timeout (seg)</label>
                                <input type="number" class="form-control" name="erp_timeout" value="{{ $settings['erp_timeout'] }}" min="5" max="300">
                                <small class="text-muted">Espera máxima</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Batch size</label>
                                <input type="number" class="form-control" name="default_batch_size" value="{{ $settings['default_batch_size'] }}" min="1" max="1000">
                                <small class="text-muted">Registros por lote</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Reintentos</label>
                                <input type="number" class="form-control" name="max_retries" value="{{ $settings['max_retries'] }}" min="0" max="10">
                                <small class="text-muted">Máx. reintentos</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nivel de log</label>
                                <select class="form-select" name="log_level">
                                    <option value="debug"   {{ $settings['log_level'] === 'debug'   ? 'selected' : '' }}>Debug</option>
                                    <option value="info"    {{ $settings['log_level'] === 'info'    ? 'selected' : '' }}>Info</option>
                                    <option value="warning" {{ $settings['log_level'] === 'warning' ? 'selected' : '' }}>Warning</option>
                                    <option value="error"   {{ $settings['log_level'] === 'error'   ? 'selected' : '' }}>Error</option>
                                </select>
                                <small class="text-muted">Detalle de logs</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Sincronización activa</label>
                                <select class="form-select" name="sync_enabled" id="syncEnabled">
                                    <option value="1" {{ $settings['sync_enabled'] ? 'selected' : '' }}>Activa</option>
                                    <option value="0" {{ $settings['sync_enabled'] ? '' : 'selected' }}>Inactiva</option>
                                </select>
                                <small class="text-muted">Habilita ejecuciones automáticas</small>
                            </div>
                            <div class="col-12"><hr class="my-1"></div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fecha desde por defecto</label>

                                    <input type="text" class="form-control" name="filter_date_from" id="cfg-date-from"
                                           value="{{ $settings['filter_date_from'] ?? config('supplier.erp_sync.filter_date_from', '') }}"
                                           placeholder="YYYY-MM-DD" autocomplete="off">

                                <small class="text-muted">Solo procesa registros desde esta fecha</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cantidad de artículos por defecto</label>
                                <input type="number" class="form-control" name="default_limit"
                                       value="{{ $settings['default_limit'] ?? 0 }}" min="0" max="99999">
                                <small class="text-muted"><strong>0</strong> = todos los artículos sin límite</small>
                            </div>

                            <div class="col-12"><hr class="my-1"></div>
                            <div class="col-12">
                                <p class="text-uppercase fw-bold text-muted mb-0" style="font-size:.68rem;letter-spacing:.08em;">Opciones de ejecución por defecto</p>
                                <p class="text-muted small mb-3">Valores por defecto que usarán los horarios programados y la <a href="{{ route('settings.suppliers.sync.test.index') }}" target="_blank">página de ejecución manual</a>.</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Modo de consulta ERP</label>
                                <select class="form-select select2" name="default_mode">
                                    <option value="filter" {{ ($settings['default_mode'] ?? 'filter') === 'filter' ? 'selected' : '' }}>Filter — recomendado</option>
                                    <option value="legacy" {{ ($settings['default_mode'] ?? 'filter') === 'legacy' ? 'selected' : '' }}>Legacy (/api/erp/modelos)</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Forzar re-sincronización</label>
                                <select class="form-select select2" name="default_force">
                                    <option value="0" {{ ($settings['default_force'] ?? '0') === '0' ? 'selected' : '' }}>No — solo procesar cambios</option>
                                    <option value="1" {{ ($settings['default_force'] ?? '0') === '1' ? 'selected' : '' }}>Sí — sobreescribir siempre</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Opciones activas por defecto</label>
                                <select class="form-select select2" name="exec_flags" id="exec-flags-select" multiple>
                                    <option value="dry_run"
                                        {{ ($settings['default_dry_run'] ?? '0') === '1' ? 'selected' : '' }}>
                                        Dry-run (solo lectura) — consulta el ERP y valida datos sin guardar nada en la base de datos
                                    </option>
                                    <option value="register_only"
                                        {{ ($settings['default_register_only'] ?? '0') === '1' ? 'selected' : '' }}>
                                        Solo registrar content (sin generar) — crea el registro AiContent en pendiente aunque la subfamilia tenga prompt, no dispara la generación IA
                                    </option>
                                </select>
                                <small class="text-muted">Las opciones seleccionadas se aplicarán como valores por defecto en horarios y ejecución manual.</small>
                                <div class="alert alert-light border mt-2 mb-0 py-2 px-3" style="font-size:.78rem;">
                                    <i class="fas fa-info-circle text-primary me-1"></i>
                                    La sincronización trae <strong>todos los productos</strong> (con y sin descripción) y registra los que estén <strong>pendientes de publicar en web (web=2)</strong> — único estado sincronizable.
                                </div>
                            </div>

                        </div>
                        <div class="mt-4">
                            <button type="button" class="btn btn-primary w-100" onclick="saveSettings()">
                                Guardar configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>{{-- tab-content --}}

    </div>{{-- card --}}

</div>{{-- widget-content --}}

{{-- Modal: Crear/Editar horario --}}
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleModalTitle">Nuevo horario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="scheduleUid">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tipo</label>
                    <select class="form-select" id="scheduleSyncType">
                        <option value="model">Modelos</option>
                        <option value="product">Productos</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Etiqueta</label>
                    <input type="text" class="form-control" id="scheduleLabel" placeholder="Ej: Modelos - mañana">
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label fw-semibold">Hora (0–23)</label>
                        <input type="number" class="form-control" id="scheduleHour" min="0" max="23" value="8">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label fw-semibold">Minuto (0–59)</label>
                        <input type="number" class="form-control" id="scheduleMinute" min="0" max="59" value="0">
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Habilitado</label>
                    <select class="form-select" id="scheduleEnabled">
                        <option value="1">Sí — ejecución activa</option>
                        <option value="0">No — pausado</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary w-100 mb-1" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary w-100" onclick="saveSchedule()">Guardar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Confirmar eliminación de horario --}}
<div class="modal fade" id="deleteScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="mb-3"><i class="fas fa-trash-alt fa-2x text-danger"></i></div>
                <h6 class="fw-semibold mb-1">¿Eliminar este horario?</h6>
                <p class="text-muted small mb-4">Esta acción no se puede deshacer.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteSchedule()">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Bulk toolbar: Horarios --}}
<div id="bulk-toolbar-schedules" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
    <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-schedules-modal">
        <span data-bulk-count>0</span> horario(s) seleccionado(s) &mdash; Aplicar acción
    </button>
</div>

{{-- Bulk toolbar: Historial --}}
<div id="bulk-toolbar-batches" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
    <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-batches-modal">
        <span data-bulk-count>0</span> ejecución(es) seleccionada(s) &mdash; Aplicar acción
    </button>
</div>

{{-- Bulk modal: Horarios --}}
<div class="modal fade" id="bulk-schedules-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Acción masiva — Horarios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> horario(s)</strong>.</p>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Acción</label>
                    <select id="bulk-schedules-action" class="form-select">
                        <option value="">Seleccionar acción...</option>
                        <option value="enable">Activar</option>
                        <option value="disable">Desactivar</option>
                        <option value="delete">Eliminar</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button id="bulk-schedules-apply" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- Bulk modal: Historial --}}
<div class="modal fade" id="bulk-batches-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Acción masiva — Ejecuciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> ejecución(es)</strong>.</p>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Acción</label>
                    <select id="bulk-batches-action" class="form-select">
                        <option value="">Seleccionar acción...</option>
                        <option value="retry">Reintentar</option>
                        <option value="cancel">Cancelar</option>
                        <option value="delete">Eliminar</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button id="bulk-batches-apply" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal de confirmación genérico para acciones bulk --}}
<div class="modal fade" id="bulk-confirm-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulk-confirm-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
            </div>
            <div class="modal-body text-center">
                <div class="display-4 text-success mb-3">
                    <i id="bulk-confirm-icon" class="fas fa-exclamation-triangle"></i>
                </div>
                <h4 class="my-0" id="bulk-confirm-heading"></h4>
                <p id="bulk-confirm-text"></p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary" id="bulk-confirm-ok"></button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
<script src="{{ themeAsset('libs/moment/moment.js') }}"></script>
<script src="{{ themeAsset('libs/daterangepicker/daterangepicker.js') }}"></script>
<script>
const routes = @json($routes);
const csrf = document.querySelector('meta[name="csrf-token"]').content;
let deleteTargetUid = null;

// Restaurar pestaña activa tras recarga (sessionStorage o parámetro ?tab=)
(function () {
    const urlTab = new URLSearchParams(location.search).get('tab');
    const saved  = sessionStorage.getItem('syncActiveTab') || urlTab;
    if (!saved) return;
    sessionStorage.removeItem('syncActiveTab');
    const tabEl = document.querySelector(`[data-bs-target="#tab-${saved}"]`);
    if (tabEl) bootstrap.Tab.getOrCreateInstance(tabEl).show();
})();

// ── Configuración ────────────────────────────────────────────────────────────

function execFlagsToFields() {
    const flags = Array.from(document.querySelectorAll('#exec-flags-select option:checked')).map(o => o.value);
    const has = (f) => flags.includes(f);
    return {
        // El registro no depende de la descripción ERP: nunca se filtra por descripción vacía.
        default_description_empty: '0',
        // Único estado sincronizable: pendientes de publicar en web (web=2).
        default_web_filter:        '2',
        default_dry_run:           has('dry_run')       ? '1' : '0',
        default_skip_ai:           '0',
        default_register_only:     has('register_only') ? '1' : '0',
    };
}

function saveSettings() {
    const form = document.getElementById('syncSettingsForm');
    const defaultLimit = parseInt(form.querySelector('[name="default_limit"]').value || '0');
    const fv = (name) => form.querySelector(`[name="${name}"]`)?.value ?? null;
    const data = {
        erp_base_url:               fv('erp_base_url'),
        erp_timeout:                parseInt(fv('erp_timeout')),
        default_batch_size:         parseInt(fv('default_batch_size')),
        max_retries:                parseInt(fv('max_retries')),
        log_level:                  fv('log_level'),
        sync_enabled:               parseInt(fv('sync_enabled'), 10),
        filter_date_from:           fv('filter_date_from') || null,
        default_limit:              isNaN(defaultLimit) ? 0 : defaultLimit,
        // Opciones de ejecución por defecto
        default_mode:               fv('default_mode'),
        default_force:              fv('default_force'),
        ...execFlagsToFields(),
    };

    $.ajax({
        url: routes.settings_save,
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify(data),
    })
    .done(function (data) { data.success ? toastr.success(data.message) : toastr.error(data.message || 'Error al guardar'); })
    .fail(function (xhr) { toastr.error('Error: ' + (xhr.responseJSON?.message || xhr.statusText)); });
}

// ── Horarios ─────────────────────────────────────────────────────────────────

function openScheduleModal() {
    document.getElementById('scheduleModalTitle').textContent = 'Nuevo horario';
    document.getElementById('scheduleUid').value = '';
    document.getElementById('scheduleSyncType').value = 'model';
    document.getElementById('scheduleLabel').value = '';
    document.getElementById('scheduleHour').value = 8;
    document.getElementById('scheduleMinute').value = '0';
    document.getElementById('scheduleEnabled').value = '1';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('scheduleModal')).show();
}

function editSchedule(uid) {
    const row = document.querySelector('tr[data-uid="' + uid + '"]');
    if (!row) return;
    const cells = row.querySelectorAll('td');
    document.getElementById('scheduleModalTitle').textContent = 'Editar horario';
    document.getElementById('scheduleUid').value = uid;
    document.getElementById('scheduleSyncType').value = row.dataset.syncType || 'model';
    document.getElementById('scheduleLabel').value = cells[2].textContent.trim();
    const timeParts = cells[3].textContent.trim().split(':');
    document.getElementById('scheduleHour').value = parseInt(timeParts[0]);
    document.getElementById('scheduleMinute').value = timeParts[1] || '0';
    const isEnabled = row.querySelector('.schedule-toggle')?.checked ?? true;
    document.getElementById('scheduleEnabled').value = isEnabled ? '1' : '0';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('scheduleModal')).show();
}

function saveSchedule() {
    const uid = document.getElementById('scheduleUid').value;
    const isEdit = uid !== '';
    const url = isEdit ? routes.config_update.replace(':uid', uid) : routes.config_store;

    $.ajax({
        url: url,
        method: isEdit ? 'PUT' : 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify({
            sync_type:  document.getElementById('scheduleSyncType').value,
            label:      document.getElementById('scheduleLabel').value,
            hour:       parseInt(document.getElementById('scheduleHour').value),
            minute:     parseInt(document.getElementById('scheduleMinute').value),
            is_enabled: parseInt(document.getElementById('scheduleEnabled').value),
        }),
    })
    .done(function (data) {
        if (data.success) {
            toastr.success(data.message);
            bootstrap.Modal.getInstance(document.getElementById('scheduleModal')).hide();
            location.reload();
        } else {
            toastr.error(data.message || 'Error al guardar');
        }
    })
    .fail(function (xhr) { toastr.error('Error: ' + (xhr.responseJSON?.message || xhr.statusText)); });
}

function confirmDeleteSchedule(uid) {
    deleteTargetUid = uid;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteScheduleModal')).show();
}

function deleteSchedule() {
    $.ajax({
        url: routes.config_destroy.replace(':uid', deleteTargetUid),
        method: 'DELETE',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    })
    .done(function (data) {
        if (data.success) {
            toastr.success(data.message);
            bootstrap.Modal.getInstance(document.getElementById('deleteScheduleModal')).hide();
            location.reload();
        } else {
            toastr.error(data.message || 'Error al eliminar');
        }
    })
    .fail(function (xhr) { toastr.error('Error: ' + (xhr.responseJSON?.message || xhr.statusText)); });
}

function triggerSchedule(uid) {
    $.ajax({
        url: routes.config_trigger.replace(':uid', uid),
        method: 'POST',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    })
    .done(function (data) {
        if (data.success) {
            toastr.info('Sincronización iniciada (batch #' + data.batch_id + ')');
            setTimeout(() => location.reload(), 1200);
        } else {
            toastr.error(data.message || 'Error al iniciar');
        }
    })
    .fail(function (xhr) { toastr.error('Error: ' + (xhr.responseJSON?.message || xhr.statusText)); });
}

function resetScheduleStatus(uid) {
    $.ajax({
        url: routes.config_reset.replace(':uid', uid),
        method: 'POST',
        dataType: 'json',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    })
    .done(function (data) {
        if (data.success) { toastr.success(data.message); location.reload(); }
        else { toastr.error(data.message || 'Error'); }
    })
    .fail(function (xhr) { toastr.error('Error: ' + (xhr.responseJSON?.message || xhr.statusText)); });
}

document.querySelectorAll('.schedule-toggle').forEach(el => {
    el.addEventListener('change', function () {
        const uid = this.dataset.uid;
        const toggleEl = this;
        $.ajax({
            url: routes.config_toggle.replace(':uid', uid),
            method: 'POST',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        })
        .done(function (data) {
            if (data.success) {
                toastr.success(data.is_enabled ? 'Horario habilitado' : 'Horario deshabilitado');
                setTimeout(() => location.reload(), 800);
            } else {
                toastr.error('Error al cambiar estado');
                toggleEl.checked = !toggleEl.checked;
            }
        })
        .fail(function () { toastr.error('Error al cambiar estado'); toggleEl.checked = !toggleEl.checked; });
    });
});

// ── Sincronización manual ────────────────────────────────────────────────────

function syncAction(url, label) {
    Swal.fire({
        title: '¿Sincronizar ' + label + '?',
        text: 'El proceso se ejecutará en segundo plano.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, ejecutar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (!result.isConfirmed) return;
        $.ajax({
            url: url,
            method: 'POST',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        })
        .done(function (data) {
            if (data.success) { toastr.success('Sincronización iniciada. Batch ID: ' + data.batch_id, label); setTimeout(() => location.reload(), 1500); }
            else { toastr.error(data.message || 'Error al iniciar', 'Error'); }
        })
        .fail(function (xhr) { toastr.error('Error: ' + (xhr.responseJSON?.message || xhr.statusText), 'Error'); });
    });
}

document.getElementById('hdr-sync-models')?.addEventListener('click', (e) => {
    e.preventDefault();
    const defaultDate = '{{ date('Y-m-d', strtotime(config('supplier.erp_sync.filter_date_from', '2026-01-01'))) }}';
    Swal.fire({
        title: 'Sincronizar modelos',
        html: `<div class="text-start">
            <p class="text-muted small mb-3">El proceso se ejecutará en segundo plano.</p>
            <label class="form-label fw-semibold">Límite de modelos <span class="text-muted fw-normal">(vacío = todos)</span></label>
            <input id="swal-model-limit" type="number" class="form-control mb-3" min="1" placeholder="Sin límite" value="20">
            <label class="form-label fw-semibold">Fecha desde <span class="text-muted fw-normal">(opcional)</span></label>
            <input id="swal-model-date" type="date" class="form-control" value="${defaultDate}">
            <small class="text-muted">Solo procesa registros desde esta fecha.</small>
        </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ejecutar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const limit = document.getElementById('swal-model-limit').value;
            const dateFrom = document.getElementById('swal-model-date').value;
            return { limit: limit ? parseInt(limit) : null, dateFrom: dateFrom || null };
        },
    }).then((result) => {
        if (!result.isConfirmed) return;
        const { limit, dateFrom } = result.value;
        $.ajax({
            url: routes.sync_models,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({ limit, date_from: dateFrom }),
        })
        .done(function (data) {
            if (data.success) { toastr.success('Sincronización de modelos iniciada. Batch ID: ' + data.batch_id); setTimeout(() => location.reload(), 1500); }
            else { toastr.error(data.message || 'Error al iniciar', 'Error'); }
        })
        .fail(function (xhr) { toastr.error('Error: ' + (xhr.responseJSON?.message || xhr.statusText), 'Error'); });
    });
});
document.getElementById('hdr-sync-providers')?.addEventListener('click', (e) => { e.preventDefault(); syncAction(routes.sync_providers, 'Proveedores'); });
document.getElementById('hdr-sync-products')?.addEventListener('click', (e) => { e.preventDefault(); syncAction(routes.sync_products, 'Productos'); });

// ── Historial: Retry / Cancel ────────────────────────────────────────────────

document.querySelectorAll('.btn-retry').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        const batchId = this.dataset.batchId;
        Swal.fire({
            title: '¿Reintentar sincronización?',
            text: 'Se creará un nuevo batch con los mismos parámetros.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, reintentar',
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: routes.retry.replace(':batchId', batchId),
                method: 'POST',
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
            .done(function (data) {
                if (data.success) { toastr.success(data.message); setTimeout(() => location.reload(), 1500); }
                else { toastr.error(data.message || 'Error al reintentar'); }
            })
            .fail(function (xhr) { toastr.error('Error: ' + (xhr.responseJSON?.message || xhr.statusText)); });
        });
    });
});

document.querySelectorAll('.btn-cancel').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        const batchId = this.dataset.batchId;
        Swal.fire({
            title: '¿Cancelar sincronización?',
            text: 'El proceso se marcará como cancelado.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No',
            confirmButtonColor: '#FA896B',
        }).then((result) => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: routes.cancel.replace(':batchId', batchId),
                method: 'POST',
                dataType: 'json',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
            .done(function (data) {
                if (data.success) { toastr.success(data.message); setTimeout(() => location.reload(), 1500); }
                else { toastr.error(data.message || 'Error al cancelar'); }
            })
            .fail(function (xhr) { toastr.error('Error: ' + (xhr.responseJSON?.message || xhr.statusText)); });
        });
    });
});

// ── Polling para batches en ejecución ────────────────────────────────────────

const runningRows = document.querySelectorAll('tr[data-status="running"]');
if (runningRows.length > 0) {
    const progressInterval = setInterval(() => {
        let stillRunning = 0;
        runningRows.forEach(row => {
            const batchId = row.dataset.batchId;
            if (!batchId) return;
            $.ajax({
                url: routes.progress.replace(':batchId', batchId),
                method: 'GET',
                dataType: 'json',
                headers: { 'Accept': 'application/json' },
            })
            .done(function (data) {
                if (!data.success) return;
                const batch = data.batch;
                const progressCell = row.querySelector('.sync-progress-cell');
                const exceedsTotal = batch.total_items > 0 && (batch.processed_items + batch.failed_items) > batch.total_items;
                if (progressCell && exceedsTotal) {
                    progressCell.innerHTML = `
                        <span class="sync-processed">${batch.processed_items + batch.failed_items}</span>
                        <small class="d-block text-muted" style="font-size:.7rem;">procesados (estimado ${batch.total_items})</small>`;
                } else if (progressCell && batch.total_items > 0) {
                    progressCell.innerHTML = `
                        <span class="sync-processed">${batch.processed_items}</span> / <span class="sync-total">${batch.total_items}</span>`;
                }
                const failedCell = row.querySelector('.sync-failed-count');
                if (failedCell) failedCell.textContent = batch.failed_items;
                const statusBadge = row.querySelector('td:nth-child(5) .badge');
                if (statusBadge && batch.status !== 'running') {
                    const cfg = {
                        completed: { cls: 'bg-success-subtle text-success', lbl: 'Completado' },
                        failed:    { cls: 'bg-secondary-subtle text-secondary', lbl: 'Fallido' },
                        cancelled: { cls: 'bg-secondary-subtle text-secondary', lbl: 'Cancelado' },
                    };
                    const c = cfg[batch.status];
                    if (c) { statusBadge.className = `badge ${c.cls}`; statusBadge.textContent = c.lbl; }
                    row.dataset.status = batch.status;
                }
                if (batch.status === 'running') stillRunning++;
            }).fail(function () {});
        });
        if (stillRunning === 0) clearInterval(progressInterval);
    }, 3000);
}

// ── Bulk actions: Horarios y Ejecuciones ─────────────────────────────────────

const bulkSchedules = window.BulkActions.init({
    checkbox:  '.schedule-bulk-checkbox',
    selectAll: '#select-all-schedules',
    toolbar:   '#bulk-toolbar-schedules',
});

const bulkBatches = window.BulkActions.init({
    checkbox:  '.batch-bulk-checkbox',
    selectAll: '#select-all-batches',
    toolbar:   '#bulk-toolbar-batches',
});

function selectedScheduleUids() {
    return $('.schedule-bulk-checkbox:checked').map(function () { return this.value; }).get();
}

function selectedBatchIds() {
    return $('.batch-bulk-checkbox:checked').map(function () { return parseInt(this.value, 10); }).get();
}

// Reset selecciones al cambiar de pestaña para evitar toolbars solapados.
document.querySelectorAll('[data-bs-toggle="pill"]').forEach(btn => {
    btn.addEventListener('shown.bs.tab', () => { bulkSchedules.reset(); bulkBatches.reset(); });
});

function showBulkConfirm({ title, heading, text, confirmText, onConfirm }) {
    const modal      = document.getElementById('bulk-confirm-modal');
    const titleEl    = document.getElementById('bulk-confirm-title');
    const headingEl  = document.getElementById('bulk-confirm-heading');
    const textEl     = document.getElementById('bulk-confirm-text');
    const okBtn      = document.getElementById('bulk-confirm-ok');

    titleEl.textContent   = title;
    headingEl.textContent = heading || '¿Estás seguro?';
    textEl.textContent    = text;

    // Replace button to avoid stacking listeners
    const newOk = okBtn.cloneNode(false);
    newOk.textContent = confirmText;
    newOk.className   = 'btn btn-primary';
    okBtn.parentNode.replaceChild(newOk, okBtn);
    newOk.addEventListener('click', () => {
        bootstrap.Modal.getInstance(modal)?.hide();
        onConfirm();
    });

    // Close bulk action modals first, then show confirm
    ['bulk-schedules-modal', 'bulk-batches-modal'].forEach(id => {
        const m = bootstrap.Modal.getInstance(document.getElementById(id));
        if (m) m.hide();
    });

    bootstrap.Modal.getOrCreateInstance(modal).show();
}

function postJson(url, method, body) {
    return $.ajax({
        url: url,
        method: method,
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        contentType: 'application/json',
        dataType: 'json',
        data: JSON.stringify(body),
    });
}

function handleBulkResult(d, $btn) {
    if (d && d.success) {
        toastr.success(d.message);
        if (Array.isArray(d.errors)) { d.errors.forEach(msg => toastr.warning(msg)); }
        setTimeout(() => location.reload(), 1000);
    } else {
        toastr.error((d && d.message) || 'Error');
        $btn.prop('disabled', false).text('Aplicar');
    }
}

$('#bulk-schedules-modal').on('hide.bs.modal', function () {
    $('#bulk-schedules-action').val('');
    $('#bulk-schedules-apply').prop('disabled', false).text('Aplicar');
});

$('#bulk-batches-modal').on('hide.bs.modal', function () {
    $('#bulk-batches-action').val('');
    $('#bulk-batches-apply').prop('disabled', false).text('Aplicar');
});

$('#bulk-schedules-apply').on('click', function () {
    const action = $('#bulk-schedules-action').val();
    const uids   = selectedScheduleUids();
    if (!action)       { toastr.warning('Selecciona una acción'); return; }
    if (!uids.length)  { toastr.warning('Selecciona al menos un horario'); return; }

    const $btn = $(this);

    if (action === 'delete') {
        showBulkConfirm({
            title: 'Eliminar ' + uids.length + ' horario(s)',
            heading: '¿Estás seguro de eliminar esto?',
            text: 'Esta acción no se puede deshacer. Todos los datos relacionados pueden eliminarse.',
            confirmText: 'Confirmar eliminación',
            onConfirm: () => {
                $btn.prop('disabled', true).text('Procesando...');
                postJson(routes.config_bulk_delete, 'DELETE', { uids: uids })
                    .done(d => handleBulkResult(d, $btn))
                    .fail(xhr => { toastr.error('Error: ' + (xhr.responseJSON?.message || xhr.statusText)); $btn.prop('disabled', false).text('Aplicar'); });
            },
        });
        return;
    }

    $btn.prop('disabled', true).text('Procesando...');
    postJson(routes.config_bulk_toggle, 'POST', { uids: uids, enabled: action === 'enable' })
        .done(d => handleBulkResult(d, $btn))
        .fail(xhr => { toastr.error('Error: ' + (xhr.responseJSON?.message || xhr.statusText)); $btn.prop('disabled', false).text('Aplicar'); });
});

$('#bulk-batches-apply').on('click', function () {
    const action = $('#bulk-batches-action').val();
    const ids    = selectedBatchIds();
    if (!action)      { toastr.warning('Selecciona una acción'); return; }
    if (!ids.length)  { toastr.warning('Selecciona al menos una ejecución'); return; }

    const $btn = $(this);
    const map = {
        retry:  { url: routes.bulk_retry,  method: 'POST',   title: 'Reintentar ' + ids.length + ' ejecución(es)', heading: '¿Estás seguro?',                      text: 'Se volverán a encolar para procesarse.',                                    confirmText: 'Confirmar reintento'   },
        cancel: { url: routes.bulk_cancel, method: 'POST',   title: 'Cancelar ' + ids.length + ' ejecución(es)',   heading: '¿Estás seguro de cancelar esto?',     text: 'Las ejecuciones en curso se detendrán.',                                    confirmText: 'Confirmar cancelación' },
        delete: { url: routes.bulk_delete, method: 'DELETE', title: 'Eliminar ' + ids.length + ' ejecución(es)',   heading: '¿Estás seguro de eliminar esto?',     text: 'Esta acción no se puede deshacer. Todos los datos relacionados pueden eliminarse.', confirmText: 'Confirmar eliminación' },
    };
    const cfg = map[action];
    if (!cfg) return;

    showBulkConfirm({
        title: cfg.title,
        heading: cfg.heading,
        text: cfg.text,
        confirmText: cfg.confirmText,
        onConfirm: () => {
            $btn.prop('disabled', true).text('Procesando...');
            postJson(cfg.url, cfg.method, { ids: ids })
                .done(d => handleBulkResult(d, $btn))
                .fail(xhr => { toastr.error('Error: ' + (xhr.responseJSON?.message || xhr.statusText)); $btn.prop('disabled', false).text('Aplicar'); });
        },
    });
});
</script>
@endpush
