@extends('layouts.theme')
@section('title', 'Clientes en riesgo · Helpdesk')
@section('page_header')
    @include('core::components.card', ['title' => 'Clientes en riesgo · Helpdesk'])
@endsection

@section('content')

<div class="card">

    {{-- Cabecera --}}
    <div class="card-header p-4 border-bottom border-light">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1 fw-bold">Clientes en riesgo</h5>
                <p class="small mb-0 text-muted">Top 50 ordenados por número de sentimientos negativos recientes y, en caso de empate, por menor puntuación de salud.</p>
            </div>
            <div class="ms-auto ps-3">
                <button id="btn-refresh" class="btn btn-secondary" title="Actualizar">
                    <i class="fas fa-rotate"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Totales --}}
    <div class="card-body border-bottom">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="card bg-light-secondary h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-2">Total en riesgo</h6>
                        <h4 class="mb-1 fw-bold" id="ar-stat-total">0</h4>
                        <small class="text-muted">Clientes listados</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-light-secondary h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-2">Riesgo alto</h6>
                        <h4 class="mb-1 fw-bold" id="ar-stat-high">0</h4>
                        <small class="text-muted">Salud &lt; 40</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-light-secondary h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-2">Riesgo medio</h6>
                        <h4 class="mb-1 fw-bold" id="ar-stat-medium">0</h4>
                        <small class="text-muted">Salud 40 - 69</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-light-secondary h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-2">Último negativo</h6>
                        <h4 class="mb-1 fw-bold" id="ar-stat-last-negative">—</h4>
                        <small class="text-muted">Más reciente del listado</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card-body border-bottom">
        <form id="ar-filter-form">
            <div class="d-flex align-items-center gap-2">
                <input type="search" id="ar-search" class="form-control flex-grow-1"
                       placeholder="Buscar por nombre o email...">

                <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                        data-bs-toggle="modal" data-bs-target="#ar-filter-modal" title="Filtros avanzados">
                    <i class="fas fa-filter"></i>
                    <span id="ar-filter-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary ar-filter-badge d-none">1</span>
                </button>

                <div class="d-flex gap-1 flex-shrink-0">
                    <button type="submit" class="btn btn-primary" title="Buscar">
                        <i class="fas fa-magnifying-glass"></i>
                    </button>
                    <button type="button" id="ar-clear-btn" class="btn btn-secondary d-none" title="Limpiar filtros">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
            </div>

            <div id="ar-filter-tags" class="d-flex gap-2 flex-wrap mt-4 d-none">
                <div>
                    <h6 class="mb-1">Filtrados:</h6>
                </div>
                <div id="ar-filter-tags-list" class="d-flex gap-2 flex-wrap"></div>
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="table-at-risk">
                <thead class="table-light">
                    <tr>
                        <th scope="col" width="3%"><input type="checkbox" id="ar-select-all" class="form-check-input" aria-label="Seleccionar todos los clientes"></th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Email</th>
                        <th scope="col" class="text-center">Sentimientos negativos</th>
                        <th scope="col" class="text-center">Salud</th>
                        <th scope="col">Último negativo</th>
                    </tr>
                </thead>
                <tbody id="tbody-at-risk">
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Cargando datos...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Barra flotante de selección: sin acción real todavía (ver nota en el
     script) — el botón queda deshabilitado con un title explicativo en vez
     de abrir un modal con opciones que no harían nada. --}}
<div id="bulk-toolbar-ar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none ar-bulk-toolbar">
    <button type="button" class="btn btn-primary shadow-lg px-4" disabled
            title="Todavía no hay ninguna acción masiva definida para este reporte — es de solo lectura, no hay estado que cambiar.">
        <span data-bulk-count>0</span> seleccionado(s) — Aplicar acción (próximamente)
    </button>
</div>

{{-- Modal de filtros avanzados --}}
<div class="modal fade" id="ar-filter-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filtros avanzados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-0">
                    <label class="form-label fw-semibold">Nivel de riesgo</label>
                    <select id="ar-modal-risk" class="form-control select2-filter-modal">
                        <option value="">Todos los niveles</option>
                        <option value="high">Alto (&lt; 40)</option>
                        <option value="medium">Medio (40 - 69)</option>
                        <option value="good">Bueno (&ge; 70)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="ar-filter-apply-btn" class="btn btn-primary w-100 mb-1">
                    Aplicar filtros
                </button>
                <button type="button" id="ar-filter-clear-btn" class="btn btn-secondary w-100">
                    Limpiar
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .ar-filter-badge { font-size: .6rem; }
    .ar-bulk-toolbar { z-index: 1050; }
    #tbody-at-risk tr.ar-row-clickable { cursor: pointer; }
</style>
@endpush

@push('scripts')
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
<script>window.HdReportsAtRisk = @json(['dataUrl' => route('manager.helpdesk.reports.at-risk.data')]);</script>
{{-- JS extraido a public/vendor/helpdesk/reports/: se cachea en el navegador
     en vez de re-descargarse en cada carga de esta página. --}}
<script src="{{ asset('vendor/helpdesk/reports/at-risk.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/reports/at-risk.js')) }}" defer></script>
@endpush
@endsection
