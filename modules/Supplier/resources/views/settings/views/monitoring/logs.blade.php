@extends('layouts.theme')

@section('title', $pageTitle)

@section('page_header')
    @include('core::components.card', ['title' => $pageTitle])
@endsection

@push('styles')
<style>
/* Latencia: se distingue por intensidad de gris (claro → oscuro), no por
   color de severidad (antes: amarillo/rojo) — paleta propia de la app
   (fondo #f5f6f8 y negro), no los grises genéricos de Bootstrap. */
.latency-fast   { background: #f5f6f8; color: #333333; }
.latency-medium { background: #9a9a9a; color: #ffffff; }
.latency-slow   { background: #000000; color: #ffffff; }
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
                        <h5 class="mb-1 fw-bold">Logs de consumo IA</h5>
                        <p class="small mb-0 text-muted">Detalle de cada llamada a los modelos de IA con tokens, costo y latencia</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.suppliers.monitoring.index') }}" class="btn btn-secondary ">
                            <i class="fas fa-arrow-left me-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            @php
                $totalCost = $logs->getCollection()->sum(fn ($l) => $l->total_cost);
                $totalTokens = $logs->getCollection()->sum(fn ($l) => $l->total_tokens);
                $avgLatency = $logs->getCollection()->whereNotNull('latency_ms')->avg('latency_ms');
            @endphp
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total registros</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($logs->total()) }}</h4>
                                <small class="text-muted">Llamadas registradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Costo acumulado (página)</h6>
                                <h4 class="mb-1 fw-bold">${{ number_format($totalCost, 4) }}</h4>
                                <small class="text-muted">Suma de los registros mostrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Tokens (página)</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($totalTokens) }}</h4>
                                <small class="text-muted">Suma de los registros mostrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Latencia media (página)</h6>
                                <h4 class="mb-1 fw-bold">
                                    @if($avgLatency)
                                        {{ number_format($avgLatency) }} <small class="fw-normal text-muted">ms</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </h4>
                                <small class="text-muted">Promedio de registros con dato</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="card-body border-bottom">
                @php
                    $activeFilterCount = collect(['model', 'operation', 'supplier_id', 'date_from', 'date_to'])
                        ->filter(fn ($k) => request($k) !== null && request($k) !== '')->count();
                @endphp
                <form id="logs-filter-form" method="GET" action="{{ route('settings.suppliers.monitoring.logs') }}">
                    <input type="hidden" name="model"       id="filter-model"       value="{{ request('model') }}">
                    <input type="hidden" name="operation"   id="filter-operation"   value="{{ request('operation') }}">
                    <input type="hidden" name="supplier_id" id="filter-supplier-id" value="{{ request('supplier_id') }}">
                    <input type="hidden" name="date_from"   id="filter-date-from"   value="{{ request('date_from') }}">
                    <input type="hidden" name="date_to"     id="filter-date-to"     value="{{ request('date_to') }}">

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Modelo, request_id..." value="{{ request('search') }}">

                        <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#logs-filter-modal" title="Filtros avanzados">
                            <i class="fas fa-filter"></i>
                            @if($activeFilterCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary"
                                      style="font-size:0.6rem;">{{ $activeFilterCount }}</span>
                            @endif
                        </button>

                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if(request()->anyFilled(['search', 'model', 'operation', 'supplier_id', 'date_from', 'date_to']))
                                <a href="{{ route('settings.suppliers.monitoring.logs') }}" class="btn btn-secondary" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Tabla --}}
            <div class="card-body">
                @if($logs->isNotEmpty())

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="min-width:130px;">Proveedor</th>
                                    <th style="min-width:140px;">Modelo</th>
                                    <th style="min-width:110px;">Operación</th>
                                    <th class="text-end" style="min-width:160px;" title="Tokens de entrada / salida / total">Tokens (in / out / total)</th>
                                    <th class="text-end" style="min-width:110px;">Costo</th>
                                    <th class="text-center" style="min-width:90px;">Latencia</th>
                                    <th style="min-width:110px;">Batch</th>
                                    <th style="min-width:110px;">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $log)
                                    @php
                                        // Tipo de operación: solo es una etiqueta de clasificación, no un
                                        // estado de severidad — un único color evita mezclar colores sin
                                        // significado real (antes: azul/ámbar dispersos por tipo).
                                        $opColor = 'secondary';
                                        // Latencia: se distingue por intensidad de gris (claro → oscuro),
                                        // no por color de severidad (antes: amarillo/rojo).
                                        $latClass = $log->latency_ms
                                            ? ($log->latency_ms < 1000 ? 'latency-fast' : ($log->latency_ms < 3000 ? 'latency-medium' : 'latency-slow'))
                                            : null;
                                        $costPer1k = $log->getCostPer1kTokens();
                                    @endphp
                                    <tr>
                                        <td class="ps-3">
                                            @if($log->supplier)
                                                <span>{{ $log->supplier->label }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $log->model }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $opColor }}-subtle text-{{ $opColor }}">
                                                {{ ucfirst($log->operation_type) }}
                                            </span>
                                        </td>
                                        <td class="text-end font-monospace">
                                            <span class="text-muted">{{ number_format($log->input_tokens) }}</span>
                                            <span class="text-muted mx-1">/</span>
                                            <span class="text-muted">{{ number_format($log->output_tokens) }}</span>
                                            <span class="text-muted mx-1">/</span>
                                            <strong>{{ number_format($log->total_tokens) }}</strong>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-semibold" title="${{ number_format($costPer1k, 6) }} / 1K tokens">
                                                ${{ number_format($log->total_cost, 6) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($log->latency_ms)
                                                <span class="badge {{ $latClass }}">
                                                    {{ $log->latency_ms >= 1000
                                                        ? number_format($log->latency_ms / 1000, 1) . ' s'
                                                        : $log->latency_ms . ' ms' }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->batch_id)
                                                <code class="small" title="{{ $log->batch_id }}">{{ Str::limit($log->batch_id, 10) }}</code>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted small" title="{{ $log->created_at->format('d/m/Y H:i:s') }}">
                                                {{ $log->created_at->diffForHumans() }}
                                            </span>
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
                                <i class="fas fa-inbox fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay logs con los filtros aplicados</h6>
                            <p class="text-muted mb-0">Ajusta los filtros o espera a que se registren nuevas llamadas de IA.</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Paginación --}}
            @if($logs->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $logs->firstItem() }}</strong> a <strong>{{ $logs->lastItem() }}</strong>
                            de <strong>{{ number_format($logs->total()) }}</strong> registros
                        </div>
                        <nav>{{ $logs->appends(request()->query())->links() }}</nav>
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Modal de filtros avanzados --}}
    <div class="modal fade" id="logs-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Modelo</label>
                        <select id="modal-model" class="form-select select2">
                            <option value="">Todos los modelos</option>
                            @foreach($models as $m)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Operación</label>
                        <select id="modal-operation" class="form-select select2">
                            <option value="">Todas las operaciones</option>
                            @foreach($operations as $op)
                                <option value="{{ $op }}">{{ ucfirst($op) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Proveedor</label>
                        <select id="modal-supplier-id" class="form-select select2">
                            <option value="">Todos los proveedores</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Desde</label>
                            <input type="date" id="modal-date-from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Hasta</label>
                            <input type="date" id="modal-date-to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-1" onclick="applyLogFilters()">Aplicar filtros</button>
                    <button type="button" class="btn btn-secondary w-100" onclick="clearLogFilters()">Limpiar filtros</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    // Select2 dentro del modal de filtros
    $('#modal-model, #modal-operation, #modal-supplier-id').select2({
        dropdownParent: $('#logs-filter-modal'),
        width: '100%'
    });

    // Pre-cargar valores activos en el modal
    $('#modal-model').val(@json(request('model'))).trigger('change');
    $('#modal-operation').val(@json(request('operation'))).trigger('change');
    $('#modal-supplier-id').val(@json(request('supplier_id'))).trigger('change');

    // Aplicar filtros del modal -> inputs ocultos del formulario
    window.applyLogFilters = function () {
        $('#filter-model').val($('#modal-model').val());
        $('#filter-operation').val($('#modal-operation').val());
        $('#filter-supplier-id').val($('#modal-supplier-id').val());
        $('#filter-date-from').val($('#modal-date-from').val());
        $('#filter-date-to').val($('#modal-date-to').val());
        $('#logs-filter-form').submit();
    };

    // Limpiar solo los filtros avanzados (mantiene la búsqueda por texto)
    window.clearLogFilters = function () {
        $('#filter-model, #filter-operation, #filter-supplier-id, #filter-date-from, #filter-date-to').val('');
        $('#logs-filter-form').submit();
    };

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
