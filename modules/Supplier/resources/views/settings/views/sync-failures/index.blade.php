@extends('layouts.theme')

@section('title', $pageTitle)

@section('page_header')
    @include('core::components.card', ['title' => $pageTitle])
@endsection

@push('styles')
    <style>
        .failure-row, .conflict-row { cursor: pointer; }
        .sync-error-message {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
            word-break: break-word;
            max-width: 320px;
        }
        /* Mensaje de error en color de texto normal, sin rojo de alerta. */
        .text-error { color: #212529 !important; }
        .badge-error-subtle { background: #f8d7da; color: #842029; }
        .badge-error-solid  { background: #dc3545; color: #fff; }
        /* Clasificaciones sin severidad real (tipo de fallo, estado pendiente)
           en gris neutro — sin colores de alerta dispersos. */
        .badge-neutral { background: #eef0f2; color: #495057; }

        /* Modal de detalle: agrupar en tarjetas para mejor jerarquía visual */
        .detail-group { background: #f8f9fb; border-radius: 10px; padding: 14px 16px; }
        .detail-group label { font-size: .72rem; text-transform: uppercase; letter-spacing: .02em; }
    </style>
@endpush

@section('content')
    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Fallos de sincronización</h5>
                        <p class="small mb-0 text-muted">Gestión y resolución de errores en la sincronización con el ERP</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.suppliers.sync.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Fallos activos</h6>
                                <h2 class="fw-bold mb-1">{{ number_format($stats['total_failures']) }}</h2>
                                <small class="text-muted">Pendientes de resolver</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Reintentables</h6>
                                <h2 class="fw-bold mb-1">{{ number_format($stats['retryable_failures']) }}</h2>
                                <small class="text-muted">Listos para reintentar</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Sin proveedor</h6>
                                <h2 class="fw-bold mb-1">{{ number_format($stats['failures_by_type']['sin_proveedor'] ?? 0) }}</h2>
                                <small class="text-muted">Modelos ERP sin supplier</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Otros fallos</h6>
                                <h2 class="fw-bold mb-1">{{ number_format(($stats['failures_by_type']['error_api'] ?? 0) + ($stats['failures_by_type']['error_db'] ?? 0) + ($stats['failures_by_type']['datos_invalidos'] ?? 0) + ($stats['failures_by_type']['sin_categoria'] ?? 0)) }}</h2>
                                <small class="text-muted">API, DB, datos inválidos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs --}}
            <ul class="nav nav-pills user-profile-tab" id="failuresTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $tab === 'failures' ? 'active' : '' }}"
                            data-bs-toggle="pill" data-bs-target="#failures-pane" type="button" role="tab">
                        <span class="d-none d-md-block">Fallos de sincronización</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $tab === 'conflicts' ? 'active' : '' }}"
                            data-bs-toggle="pill" data-bs-target="#conflicts-pane" type="button" role="tab">
                        <span class="d-none d-md-block">Conflictos detectados</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3 {{ $tab === 'history' ? 'active' : '' }}"
                            data-bs-toggle="pill" data-bs-target="#history-pane" type="button" role="tab">
                        <span class="d-none d-md-block">Histórico ({{ number_format($stats['total_history']) }})</span>
                    </button>
                </li>
            </ul>

            {{-- Tab content --}}
            <div class="tab-content">

                {{-- Tab Fallos --}}
                <div class="tab-pane fade {{ $tab === 'failures' ? 'show active' : '' }}" id="failures-pane" role="tabpanel">

                    {{-- Filtros --}}
                    <div class="card-body border-bottom">
                        @if($batchId)
                            <div class="mb-2">
                                <span class="badge bg-secondary-subtle text-secondary">Filtrando por batch #{{ $batchId }}</span>
                                <a href="{{ route('settings.suppliers.sync.show', $batchId) }}" class="small ms-2">Ver batch</a>
                            </div>
                        @endif
                        <form id="failures-filter-form" method="GET" action="{{ route('settings.suppliers.sync.failures.index') }}">
                            <input type="hidden" name="tab" value="failures">
                            @if($batchId)
                                <input type="hidden" name="batch_id" value="{{ $batchId }}">
                            @endif
                            <input type="hidden" name="sync_type"   id="filter-sync-type"   value="{{ $syncType }}">
                            <input type="hidden" name="failure_type" id="filter-failure-type" value="{{ $failureType }}">
                            <input type="hidden" name="status"      id="filter-status"      value="{{ request('status') }}">

                            <div class="d-flex align-items-center gap-2">
                                <input type="search" name="search" class="form-control flex-grow-1"
                                       placeholder="Buscar por referencia, ID ERP o mensaje de error..."
                                       value="{{ $searchKey }}">

                                <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                        data-bs-toggle="modal" data-bs-target="#failures-filter-modal" title="Filtros avanzados">
                                    <i class="fas fa-filter"></i>
                                    @php
                                        $activeFilterCount = collect(['sync_type', 'failure_type', 'status'])
                                            ->filter(fn($k) => request($k) !== null && request($k) !== '')->count();
                                    @endphp
                                    @if($activeFilterCount > 0)
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary"
                                              style="font-size:0.6rem;">{{ $activeFilterCount }}</span>
                                    @endif
                                </button>

                                <div class="d-flex gap-1 flex-shrink-0">
                                    <button type="submit" class="btn btn-primary" title="Buscar">
                                        <i class="fas fa-magnifying-glass"></i>
                                    </button>
                                    @if($searchKey || $syncType || $failureType || $batchId || request('status'))
                                        <a href="{{ route('settings.suppliers.sync.failures.index') }}?tab=failures"
                                           class="btn btn-secondary" title="Limpiar filtros">
                                            <i class="fas fa-xmark"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card-body">

                        @if($failures->count() > 0)

                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1 fw-bold">Listado de fallos</h6>
                                    <p class="text-muted small mb-0">{{ $failures->total() }} fallos encontrados</p>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary btn-sm"
                                            onclick="retryAllVisible()" title="Reintentar visibles">
                                        <i class="fas fa-rotate-right"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="3%"><input type="checkbox" class="form-check-input" id="select-all"></th>
                                            <th>Entidad</th>
                                            <th>Tipo de fallo</th>
                                            <th>ID ERP</th>
                                            <th>Referencia</th>
                                            <th style="min-width:220px;">Mensaje</th>
                                            <th>Batch</th>
                                            <th class="text-center">Estado</th>
                                            <th class="text-center">Reintentos</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($failures as $failure)
                                            @php
                                                // Clasificación de entidad sincronizada, no severidad — un único
                                                // color evita mezclar colores sin significado real.
                                                $typeColors = ['price' => 'secondary', 'product' => 'secondary', 'provider' => 'secondary'];
                                                $color      = $typeColors[$failure->sync_type] ?? 'secondary';

                                                // Tipo de fallo: clasificación, no severidad — gris uniforme
                                                // (el mensaje de error de abajo ya comunica la urgencia real).
                                                $ftBadgeClass = 'badge-neutral';
                                            @endphp
                                            <tr data-failure-id="{{ $failure->id }}" class="failure-row" role="button" onclick="viewFailureDetails({{ $failure->id }})">
                                                <td onclick="event.stopPropagation();">
                                                    <input type="checkbox" class="form-check-input failure-checkbox" value="{{ $failure->id }}">
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">
                                                        {{ $failure->type_name }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($failure->failure_type)
                                                        <span class="badge {{ $ftBadgeClass }}">
                                                            {{ $failure->failure_type_name }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>
                                                <td><code class="bg-light px-2 py-1 rounded">{{ $failure->erp_id ?? '—' }}</code></td>
                                                <td>
                                                    @if($failure->reference)
                                                        <code class="bg-light px-2 py-1 rounded">{{ $failure->reference }}</code>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="small text-error font-monospace sync-error-message" title="{{ $failure->error_message }}">
                                                        {{ $failure->error_message }}
                                                    </span>
                                                    <small class="text-muted">{{ $failure->created_at->format('d/m/Y H:i') }}</small>
                                                </td>
                                                <td onclick="event.stopPropagation();">
                                                    @if($failure->batch_id)
                                                        <a href="{{ route('settings.suppliers.sync.show', $failure->batch_id) }}">#{{ $failure->batch_id }}</a>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if($failure->failure_status === 'resolved')
                                                        <span class="badge bg-success-subtle text-success">Resuelto</span>
                                                    @elseif($failure->failure_status === 'pending')
                                                        <span class="badge badge-neutral">Pendiente</span>
                                                    @else
                                                        <span class="badge bg-secondary-subtle text-secondary">{{ $failure->failure_status_name }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge {{ $failure->retry_count >= $failure->max_retries ? 'badge-error-solid' : 'bg-light text-dark' }}">
                                                        {{ $failure->retry_count }}/{{ $failure->max_retries }}
                                                    </span>
                                                </td>
                                                <td class="text-center" onclick="event.stopPropagation();">
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
                                                                <a class="dropdown-item" href="#" onclick="viewFailureDetails({{ $failure->id }}); return false;">
                                                                    Ver datos del ERP
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="deleteFailure({{ $failure->id }}); return false;">
                                                                    Mover al histórico
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

                            <div class="card-footer bg-white border-top py-2 mt-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        @if($failures->total() > 0)
                                        <span class="text-muted small">
                                            Mostrando {{ $failures->firstItem() }}–{{ $failures->lastItem() }} de {{ $failures->total() }}
                                        </span>
                                        @endif
                                        <form method="GET" action="{{ route('settings.suppliers.sync.failures.index') }}" class="d-inline-flex align-items-center gap-1 mb-0">
                                            @foreach(request()->except(['per_page', 'page']) as $key => $value)
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
                                    @if($failures->hasPages())
                                    <nav>{{ $failures->appends(request()->query())->links('pagination::bootstrap-5') }}</nav>
                                    @endif
                                </div>
                            </div>

                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
                                <h6 class="mb-1">No hay fallos de sincronización</h6>
                                <p class="small mb-0">Todas las sincronizaciones se han completado exitosamente.</p>
                            </div>
                        @endif

                    </div>
                </div>

                {{-- Tab Conflictos --}}
                <div class="tab-pane fade {{ $tab === 'conflicts' ? 'show active' : '' }}" id="conflicts-pane" role="tabpanel">

                    {{-- Filtros --}}
                    <div class="card-body border-bottom">
                        <form method="GET" action="{{ route('settings.suppliers.sync.failures.index') }}">
                            <input type="hidden" name="tab" value="conflicts">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-5">
                                    <select name="sync_type" class="form-control select2 w-100" id="conflicts-sync-type">
                                        <option value="">Todos los tipos</option>
                                        <option value="price"    {{ ($tab === 'conflicts' && request('sync_type') === 'price')    ? 'selected' : '' }}>Precio</option>
                                        <option value="product"  {{ ($tab === 'conflicts' && request('sync_type') === 'product')  ? 'selected' : '' }}>Producto</option>
                                        <option value="provider" {{ ($tab === 'conflicts' && request('sync_type') === 'provider') ? 'selected' : '' }}>Proveedor</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <select name="status" class="form-control select2 w-100" id="conflicts-status">
                                        <option value="">Todos los estados</option>
                                        <option value="resolved"   {{ ($tab === 'conflicts' && request('status') === 'resolved')   ? 'selected' : '' }}>Resueltos</option>
                                        <option value="unresolved" {{ ($tab === 'conflicts' && request('status') === 'unresolved') ? 'selected' : '' }}>Sin resolver</option>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex gap-1">
                                    <button type="submit" class="btn btn-primary" title="Buscar">
                                        <i class="fas fa-magnifying-glass"></i>
                                    </button>
                                    @if($tab === 'conflicts' && (request('sync_type') || request('status')))
                                        <a href="{{ route('settings.suppliers.sync.failures.index') }}?tab=conflicts"
                                           class="btn btn-secondary" title="Limpiar filtros">
                                            <i class="fas fa-xmark"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card-body">

                        @if($conflicts->count() > 0)

                            <div class="mb-3">
                                <h6 class="mb-1 fw-bold">Conflictos detectados</h6>
                                <p class="text-muted small mb-0">{{ $conflicts->total() }} conflictos encontrados</p>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tipo</th>
                                            <th>ID ERP</th>
                                            <th>Referencia</th>
                                            <th>Estrategia</th>
                                            <th>Detectado</th>
                                            <th>Resuelto</th>
                                            <th class="text-center">Estado</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($conflicts as $conflict)
                                            @php
                                                // Clasificación de entidad sincronizada, no severidad — un único
                                                // color evita mezclar colores sin significado real.
                                                $typeColors = ['price' => 'secondary', 'product' => 'secondary', 'provider' => 'secondary'];
                                                $color      = $typeColors[$conflict->entity_type] ?? 'secondary';
                                                $strategyColors = [
                                                    'erp_wins'   => 'secondary',
                                                    'local_wins' => 'secondary',
                                                    'manual'     => 'warning',
                                                    'merge'      => 'success',
                                                ];
                                                $sColor = $strategyColors[$conflict->resolution_strategy] ?? 'secondary';
                                            @endphp
                                            <tr class="conflict-row" role="button" onclick="viewConflictDetails({{ $conflict->id }})">
                                                <td>
                                                    <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">
                                                        {{ $conflict->entity_type_name }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($conflict->erp_id)
                                                        <code class="bg-light px-2 py-1 rounded">{{ $conflict->erp_id }}</code>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($conflict->reference)
                                                        <code class="bg-light px-2 py-1 rounded">{{ $conflict->reference }}</code>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $sColor }}-subtle text-{{ $sColor }}">
                                                        {{ $conflict->resolution_strategy_name }}
                                                    </span>
                                                </td>
                                                <td class="text-muted small">{{ $conflict->conflict_detected_at->format('d/m/Y H:i') }}</td>
                                                <td class="text-muted small">{{ $conflict->resolved_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                                <td class="text-center">
                                                    @if($conflict->isResolved())
                                                        <span class="badge bg-success-subtle text-success">Resuelto</span>
                                                    @else
                                                        <span class="badge badge-error-subtle">Sin resolver</span>
                                                    @endif
                                                </td>
                                                <td class="text-center" onclick="event.stopPropagation();">
                                                    <div class="dropdown">
                                                        <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-ellipsis-vertical"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="viewConflictDetails({{ $conflict->id }}); return false;">
                                                                    Ver detalles
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

                            <div class="card-footer bg-white border-top py-2 mt-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        @if($conflicts->total() > 0)
                                        <span class="text-muted small">
                                            Mostrando {{ $conflicts->firstItem() }}–{{ $conflicts->lastItem() }} de {{ $conflicts->total() }}
                                        </span>
                                        @endif
                                        <form method="GET" action="{{ route('settings.suppliers.sync.failures.index') }}" class="d-inline-flex align-items-center gap-1 mb-0">
                                            @foreach(request()->except(['per_page', 'page']) as $key => $value)
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
                                    @if($conflicts->hasPages())
                                    <nav>{{ $conflicts->appends(request()->query())->links('pagination::bootstrap-5') }}</nav>
                                    @endif
                                </div>
                            </div>

                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-shield-halved fa-3x text-success mb-3 d-block"></i>
                                <h6 class="mb-1">No hay conflictos detectados</h6>
                                <p class="small mb-0">Las sincronizaciones se están ejecutando sin conflictos.</p>
                            </div>
                        @endif

                    </div>
                </div>

                {{-- Tab Histórico --}}
                <div class="tab-pane fade {{ $tab === 'history' ? 'show active' : '' }}" id="history-pane" role="tabpanel">

                    <div class="card-body border-bottom">
                        <p class="small text-muted mb-3">
                            Reporte permanente de todos los productos que han fallado alguna vez, tanto los resueltos
                            por reintento (automático o manual) como los descartados manualmente. Estos registros
                            <strong>no se borran solos</strong> — solo se purgan si se eliminan a mano desde aquí.
                        </p>
                        <form method="GET" action="{{ route('settings.suppliers.sync.failures.index') }}">
                            <input type="hidden" name="tab" value="history">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-7">
                                    <input type="search" name="search" class="form-control"
                                           placeholder="Buscar por referencia, ID ERP o mensaje de error..."
                                           value="{{ $tab === 'history' ? $searchKey : '' }}">
                                </div>
                                <div class="col-md-3">
                                    <select name="status" class="form-control select2 w-100" id="history-status">
                                        <option value="">Todos los estados</option>
                                        <option value="resolved" {{ ($tab === 'history' && $statusFilter === 'resolved') ? 'selected' : '' }}>Resueltos</option>
                                        <option value="archived" {{ ($tab === 'history' && $statusFilter === 'archived') ? 'selected' : '' }}>Archivados</option>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex gap-1">
                                    <button type="submit" class="btn btn-primary" title="Buscar">
                                        <i class="fas fa-magnifying-glass"></i>
                                    </button>
                                    @if($tab === 'history' && ($searchKey || $statusFilter))
                                        <a href="{{ route('settings.suppliers.sync.failures.index') }}?tab=history"
                                           class="btn btn-secondary" title="Limpiar filtros">
                                            <i class="fas fa-xmark"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card-body">

                        @if($historyFailures->count() > 0)

                            <div class="mb-3">
                                <h6 class="mb-1 fw-bold">Histórico de fallos</h6>
                                <p class="text-muted small mb-0">{{ $historyFailures->total() }} registro(s) encontrados</p>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Entidad</th>
                                            <th>Tipo de fallo</th>
                                            <th>ID ERP</th>
                                            <th>Referencia</th>
                                            <th style="min-width:220px;">Mensaje</th>
                                            <th class="text-center">Estado final</th>
                                            <th>Resuelto</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($historyFailures as $failure)
                                            @php
                                                // Clasificación de entidad sincronizada, no severidad — un único
                                                // color evita mezclar colores sin significado real.
                                                $typeColors = ['price' => 'secondary', 'product' => 'secondary', 'provider' => 'secondary'];
                                                $color      = $typeColors[$failure->sync_type] ?? 'secondary';

                                                // Tipo de fallo: clasificación, no severidad — gris uniforme
                                                // (el mensaje de error de abajo ya comunica la urgencia real).
                                                $ftBadgeClass = 'badge-neutral';
                                            @endphp
                                            <tr data-failure-id="{{ $failure->id }}" class="failure-row" role="button" onclick="viewFailureDetails({{ $failure->id }})">
                                                <td>
                                                    <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">
                                                        {{ $failure->type_name }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($failure->failure_type)
                                                        <span class="badge {{ $ftBadgeClass }}">
                                                            {{ $failure->failure_type_name }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>
                                                <td><code class="bg-light px-2 py-1 rounded">{{ $failure->erp_id ?? '—' }}</code></td>
                                                <td>
                                                    @if($failure->reference)
                                                        <code class="bg-light px-2 py-1 rounded">{{ $failure->reference }}</code>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="small text-muted font-monospace sync-error-message" title="{{ $failure->error_message }}">
                                                        {{ $failure->error_message }}
                                                    </span>
                                                    <small class="text-muted d-block">{{ $failure->created_at->format('d/m/Y H:i') }}</small>
                                                </td>
                                                <td class="text-center">
                                                    @if($failure->failure_status === 'resolved')
                                                        <span class="badge bg-success-subtle text-success">Resuelto</span>
                                                    @else
                                                        <span class="badge bg-secondary-subtle text-secondary">Archivado</span>
                                                    @endif
                                                </td>
                                                <td class="text-muted small">
                                                    {{ $failure->resolved_at?->format('d/m/Y H:i') ?? '—' }}
                                                    @if($failure->resolution_notes)
                                                        <small class="text-muted d-block">{{ $failure->resolution_notes }}</small>
                                                    @endif
                                                </td>
                                                <td class="text-center" onclick="event.stopPropagation();">
                                                    <div class="dropdown">
                                                        <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-ellipsis-vertical"></i>
                                                        </a>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="viewFailureDetails({{ $failure->id }}); return false;">
                                                                    Ver datos del ERP
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item" href="#" onclick="deleteFailure({{ $failure->id }}, true); return false;">
                                                                    Eliminar definitivamente
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

                            <div class="card-footer bg-white border-top py-2 mt-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    @if($historyFailures->total() > 0)
                                    <span class="text-muted small">
                                        Mostrando {{ $historyFailures->firstItem() }}–{{ $historyFailures->lastItem() }} de {{ $historyFailures->total() }}
                                    </span>
                                    @endif
                                    @if($historyFailures->hasPages())
                                    <nav>{{ $historyFailures->appends(request()->query())->links('pagination::bootstrap-5') }}</nav>
                                    @endif
                                </div>
                            </div>

                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-clock-rotate-left fa-3x text-muted mb-3 d-block"></i>
                                <h6 class="mb-1">Todavía no hay histórico</h6>
                                <p class="small mb-0">Aquí se irán acumulando los fallos a medida que se resuelvan o se descarten.</p>
                            </div>
                        @endif

                    </div>
                </div>

            </div>{{-- tab-content --}}

        </div>{{-- card --}}

    </div>{{-- widget-content --}}

    {{-- Filtros avanzados modal --}}
    <div class="modal fade" id="failures-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de sincronización</label>
                        <select id="modal-sync-type" class="form-select select2">
                            <option value="">Todos</option>
                            <option value="price">Precio</option>
                            <option value="product">Producto</option>
                            <option value="provider">Proveedor</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de fallo</label>
                        <select id="modal-failure-type" class="form-select select2">
                            <option value="">Todos</option>
                            <option value="sin_proveedor">Sin proveedor</option>
                            <option value="sin_categoria">Sin categoría</option>
                            <option value="error_api">Error API</option>
                            <option value="error_db">Error DB</option>
                            <option value="datos_invalidos">Datos inválidos</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="modal-status" class="form-select select2">
                            <option value="">Todos</option>
                            <option value="pending">Pendiente</option>
                            <option value="acknowledged">Reconocido</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" onclick="applyFilters()">Aplicar filtros</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

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
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> fallo(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="retry">Reintentar</option>
                            <option value="delete">Mover al histórico</option>
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

    {{-- Modal detalles de fallo --}}
    <div class="modal fade" id="failureDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Detalles del fallo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="failureDetailsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal detalles de conflicto --}}
    <div class="modal fade" id="conflictDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Detalles del conflicto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="conflictDetailsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
<script>
$(document).ready(function () {

    const retryUrl       = '{{ route("settings.suppliers.sync.failures.retry", "__ID__") }}';
    const destroyUrl     = '{{ route("settings.suppliers.sync.failures.destroy", "__ID__") }}';
    const bulkRetryUrl   = '{{ route("settings.suppliers.sync.failures.bulk-retry") }}';
    const bulkDelUrl     = '{{ route("settings.suppliers.sync.failures.bulk-delete") }}';
    const conflictUrl    = '{{ route("settings.suppliers.sync.failures.conflicts.show", "__ID__") }}';
    const failuresBase   = '{{ route("settings.suppliers.sync.failures.index") }}';
    const syncBase       = '{{ route("settings.suppliers.sync.index") }}';

    const escHtml = function (value) {
        return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
    };

    const renderJsonBlock = function (obj) {
        if (!obj || (typeof obj === 'object' && !Object.keys(obj).length)) {
            return '<p class="text-muted mb-0">—</p>';
        }
        return '<pre class="bg-light p-3 rounded small mb-0" style="max-height:280px;overflow:auto;">'
            + escHtml(JSON.stringify(obj, null, 2)) + '</pre>';
    };

    const detailRow = function (label, value) {
        return '<div class="col-md-4"><label class="form-label text-muted small mb-1">' + label
            + '</label><p class="mb-0">' + value + '</p></div>';
    };

    // Badge de estado para el modal de detalle — misma paleta gris/verde de la tabla.
    const failureStatusBadge = function (status, name) {
        const cls = status === 'resolved' ? 'bg-success-subtle text-success' : 'badge-neutral';
        return '<span class="badge ' + cls + '">' + escHtml(name) + '</span>';
    };

    // ── Select2 ─────────────────────────────────────────────────────────
    const s2opts = { minimumResultsForSearch: Infinity, width: '100%' };
    $('#conflicts-sync-type, #conflicts-status, #history-status').select2(s2opts);
    $('#modal-sync-type, #modal-failure-type, #modal-status').select2({ dropdownParent: $('#failures-filter-modal'), width: '100%' });
    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    // Pre-fill modal filters
    $('#modal-sync-type').val('{{ $syncType }}').trigger('change');
    $('#modal-failure-type').val('{{ $failureType }}').trigger('change');
    $('#modal-status').val('{{ request('status') }}').trigger('change');

    // ── Apply filters from modal ────────────────────────────────────────
    window.applyFilters = function () {
        $('#filter-sync-type').val($('#modal-sync-type').val());
        $('#filter-failure-type').val($('#modal-failure-type').val());
        $('#filter-status').val($('#modal-status').val());
        $('#failures-filter-form').submit();
    };

    // ── Bulk ─────────────────────────────────────────────────────────────
    const bulk = window.BulkActions.init({ checkbox: '.failure-checkbox' });

    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();
        if (!action) { toastr.warning('Selecciona una acción'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un fallo'); return; }

        const $btn = $(this).prop('disabled', true).text('Procesando...');

        if (action === 'retry') {
            $.post(bulkRetryUrl, { ids: ids, _token: '{{ csrf_token() }}' })
                .done(r => { toastr.success(r.message); setTimeout(() => location.reload(), 800); })
                .fail(x => { toastr.error(x.responseJSON?.message || 'Error'); $btn.prop('disabled', false).text('Aplicar'); });
        } else if (action === 'delete') {
            if (!confirm('¿Mover ' + ids.length + ' fallo(s) al histórico?')) { $btn.prop('disabled', false).text('Aplicar'); return; }
            fetch(bulkDelUrl, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: ids })
            })
                .then(r => r.json())
                .then(d => { toastr.success(d.message); setTimeout(() => location.reload(), 800); })
                .catch(x => { toastr.error('Error'); $btn.prop('disabled', false).text('Aplicar'); });
        }
    });

    // ── Retry single ─────────────────────────────────────────────────────
    window.retryFailure = function (id) {
        // El disparador de acciones (⋮) de la fila es lo único clicable que
        // existe ahí; se usa para dar señal visual de "procesando".
        const $trigger = $('tr[data-failure-id="' + id + '"] .dropdown > a').html('<i class="fas fa-spinner fa-spin"></i>');
        $.post(retryUrl.replace('__ID__', id), { _token: '{{ csrf_token() }}' })
            .done(r => {
                toastr.success(r.message);
                $('tr[data-failure-id="' + id + '"]').fadeOut(400, function () { $(this).remove(); });
            })
            .fail(x => {
                toastr.error(x.responseJSON?.message || 'Error al reintentar');
                $trigger.html('<i class="fas fa-ellipsis-vertical"></i>');
            });
    };

    // ── Retry all visible ────────────────────────────────────────────────
    window.retryAllVisible = function () {
        const ids = $('.failure-checkbox:checked').map(function () { return $(this).val(); }).get();
        if (!ids.length) { toastr.warning('Selecciona al menos un fallo'); return; }
        if (!confirm('¿Reintentar ' + ids.length + ' fallo(s)?')) return;
        $.post(bulkRetryUrl, { ids: ids, _token: '{{ csrf_token() }}' })
            .done(r => { toastr.success(r.message); setTimeout(() => location.reload(), 800); })
            .fail(x => { toastr.error(x.responseJSON?.message || 'Error'); });
    };

    // ── Delete single (activo → se mueve al histórico; histórico → se purga) ──
    window.deleteFailure = function (id, fromHistory) {
        const confirmMsg = fromHistory
            ? '¿Eliminar definitivamente este registro del histórico? Esta acción no se puede deshacer.'
            : '¿Mover este fallo al histórico?';
        if (!confirm(confirmMsg)) return;
        fetch(destroyUrl.replace('__ID__', id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
            .then(r => r.json())
            .then(d => { toastr.success(d.message); $('tr[data-failure-id="' + id + '"]').fadeOut(400, function () { $(this).remove(); }); })
            .catch(x => { toastr.error('Error al eliminar'); });
    };

    // ── View failure details ─────────────────────────────────────────────
    window.viewFailureDetails = function (id) {
        const modal = new bootstrap.Modal(document.getElementById('failureDetailsModal'));
        $('#failureDetailsContent').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
        modal.show();

        $.get(failuresBase + '/' + id)
            .done(r => {
                if (!r.success || !r.failure) {
                    $('#failureDetailsContent').html('<div class="alert alert-danger mb-0">No se pudieron cargar los detalles del fallo.</div>');
                    return;
                }
                const f = r.failure;
                const createdAt = f.created_at ? new Date(f.created_at).toLocaleString() : '—';
                const lastRetry = f.last_retry_at ? new Date(f.last_retry_at).toLocaleString() : '—';
                const erpId     = f.erp_id ? '<code class="bg-light px-2 py-1 rounded">' + escHtml(f.erp_id) + '</code>' : '—';
                const reference = f.reference ? '<code class="bg-light px-2 py-1 rounded">' + escHtml(f.reference) + '</code>' : '—';
                const batchCell = f.batch_id ? '<a href="' + syncBase + '/' + f.batch_id + '" class="fw-semibold">#' + escHtml(f.batch_id) + '</a>' : '—';

                // Fila superior: clasificación como badges (igual que la tabla), no texto plano.
                let html = '<div class="d-flex flex-wrap gap-2 mb-3">';
                html += '<span class="badge badge-neutral">' + escHtml(f.type_name || f.sync_type || '—') + '</span>';
                html += '<span class="badge badge-neutral">' + escHtml(f.failure_type_name || '—') + '</span>';
                html += failureStatusBadge(f.failure_status, f.failure_status_name || f.failure_status || '—');
                html += '</div>';

                // Grupo 1: identificación (ID ERP / Referencia / Batch)
                html += '<div class="detail-group row g-3 mb-3">';
                html += detailRow('ID ERP', erpId);
                html += detailRow('Referencia', reference);
                html += detailRow('Batch', batchCell);
                html += '</div>';

                // Grupo 2: seguimiento (reintentos / código / fechas)
                html += '<div class="detail-group row g-3 mb-3">';
                html += detailRow('Reintentos', escHtml((f.retry_count ?? 0) + ' / ' + (f.max_retries ?? 0)));
                html += detailRow('Código de error', escHtml(f.error_code || '—'));
                html += detailRow('Último reintento', escHtml(lastRetry));
                html += detailRow('Registrado', escHtml(createdAt));
                html += '</div>';

                html += '<label class="form-label text-muted small mb-1">Mensaje de error</label>'
                      + '<pre class="bg-light p-3 rounded small text-error mb-3" style="white-space:pre-wrap;">' + escHtml(f.error_message || '—') + '</pre>';

                html += '<div class="row g-3">';
                html += '<div class="col-md-6"><label class="form-label text-muted small mb-1">Datos enviados al ERP</label>' + renderJsonBlock(f.changed_data) + '</div>';
                html += '<div class="col-md-6"><label class="form-label text-muted small mb-1">Contexto</label>' + renderJsonBlock(f.context) + '</div>';
                html += '</div>';

                if (f.batch_id) {
                    html += '<div class="mt-3"><a href="' + syncBase + '/' + f.batch_id + '" class="btn btn-sm btn-secondary">Ver batch asociado</a></div>';
                }
                $('#failureDetailsContent').html(html);
            })
            .fail(() => $('#failureDetailsContent').html('<div class="alert alert-danger mb-0">No se pudieron cargar los detalles del fallo.</div>'));
    };

    // ── View conflict details ────────────────────────────────────────────
    window.viewConflictDetails = function (id) {
        const modal = new bootstrap.Modal(document.getElementById('conflictDetailsModal'));
        $('#conflictDetailsContent').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
        modal.show();

        $.get(conflictUrl.replace('__ID__', id))
            .done(r => {
                if (!r.success) { $('#conflictDetailsContent').html('<div class="alert alert-danger">Error cargando detalles</div>'); return; }
                const c = r.conflict;

                let html = '<div class="row g-3">';
                html += detailRow('Entidad', escHtml(c.entity_type_name || c.entity_type || '—'));
                html += detailRow('ID ERP', c.erp_id ? '<code class="bg-light px-2 py-1 rounded">' + escHtml(c.erp_id) + '</code>' : '—');
                html += detailRow('Referencia', c.reference ? '<code class="bg-light px-2 py-1 rounded">' + escHtml(c.reference) + '</code>' : '—');
                html += detailRow('Estrategia', escHtml(c.resolution_strategy_name || c.resolution_strategy || '—'));
                html += detailRow('Estado', c.resolved_at
                    ? '<span class="badge bg-success-subtle text-success">Resuelto</span>'
                    : '<span class="badge badge-error-subtle">Sin resolver</span>');
                html += detailRow('Detectado', escHtml(c.conflict_detected_at ? new Date(c.conflict_detected_at).toLocaleString() : '—'));
                html += detailRow('Resuelto', escHtml(c.resolved_at ? new Date(c.resolved_at).toLocaleString() : '—'));
                html += detailRow('Resuelto por', escHtml(c.resolved_by?.name || (c.resolved_by_ip ? 'IP ' + c.resolved_by_ip : '—')));
                if (c.notes) {
                    html += '<div class="col-12"><label class="form-label text-muted small mb-1">Notas</label>'
                          + '<p class="mb-0">' + escHtml(c.notes) + '</p></div>';
                }
                if (c.changed_fields && Object.keys(c.changed_fields).length) {
                    html += '<div class="col-12"><label class="form-label text-muted small mb-1">Campos en conflicto</label>' + renderJsonBlock(c.changed_fields) + '</div>';
                }
                html += '<div class="col-md-6"><label class="form-label text-muted small mb-1">Datos del ERP</label>' + renderJsonBlock(c.erp_data) + '</div>';
                html += '<div class="col-md-6"><label class="form-label text-muted small mb-1">Datos locales</label>' + renderJsonBlock(c.local_data) + '</div>';
                html += '</div>';

                $('#conflictDetailsContent').html(html);
            })
            .fail(() => $('#conflictDetailsContent').html('<div class="alert alert-danger">Error cargando detalles</div>'));
    };

});
</script>
@endpush
