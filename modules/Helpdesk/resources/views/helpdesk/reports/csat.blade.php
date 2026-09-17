@extends('layouts.theme')
@section('title', 'Reporte CSAT · Helpdesk')
@section('page_header')
    @include('core::components.card', ['title' => 'Reporte CSAT · Helpdesk'])
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations.css') }}?v={{ @filemtime(public_path('vendor/helpdesk/conversations.css')) }}"/>
@endpush

@section('content')

    <div class="widget-content dashboard-page">

        {{-- Filters Bar --}}
        <div class="card card-body mb-4 border-0 shadow-sm">
            <div class="row align-items-center g-3">
                <div class="col-md-auto">
                    <div class="d-flex align-items-center gap-3 px-3 py-2 rounded-1 p-2 dashboard-health-badge">
                        <div class="position-relative d-flex align-items-center justify-content-center rounded-circle bv-wh-24">
                            <i class="fas fa-smile"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-baseline gap-1 lh-1 mb-1">
                                <span class="fw-bold">CSAT</span>
                                <span class="small fw-semibold">satisfacción</span>
                            </div>
                            <div class="lh-1 bv-fs-70">Conversaciones del Helpdesk</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-auto d-none d-md-block">
                    <div class="bv-vdivider-36"></div>
                </div>
                <div class="col-md-auto">
                    <label class="form-label form-label-sm mb-1">Desde</label>
                    <input type="date" id="filter-from" class="form-control form-control-sm">
                </div>
                <div class="col-md-auto">
                    <label class="form-label form-label-sm mb-1">Hasta</label>
                    <input type="date" id="filter-to" class="form-control form-control-sm">
                </div>
                <div class="col-md"></div>
                <div class="col-md-auto d-flex align-items-center gap-2">
                    <button id="btn-apply" class="btn btn-sm btn-primary" title="Aplicar filtros">
                        <i class="fas fa-filter"></i>
                    </button>
                    <a id="btn-export" href="#" class="btn btn-sm btn-light" title="Exportar CSV">
                        <i class="fas fa-file-csv"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="row mb-4 g-3">

            <div class="col-lg-3 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Promedio global</h5>
                                <h4 class="fw-semibold mb-2 kpi-value" id="metric-avg"><div class="skeleton skeleton-title"></div></h4>
                                <p class="fs-3 mb-0 text-muted">de 5 estrellas</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="rounded-circle bg-warning-subtle d-flex align-items-center justify-content-center bv-wh-44">
                                        <i class="fas fa-star text-warning"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Total valoraciones</h5>
                                <h4 class="fw-semibold mb-2 kpi-value" id="metric-total"><div class="skeleton skeleton-title"></div></h4>
                                <p class="fs-3 mb-0 text-muted">en el período</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="rounded-circle bg-primary-subtle d-flex align-items-center justify-content-center bv-wh-44">
                                        <i class="fas fa-poll text-primary"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Score</h5>
                                <h4 class="fw-semibold mb-2 kpi-value" id="metric-nps"><div class="skeleton skeleton-title"></div></h4>
                                <p class="fs-3 mb-0 text-muted">% prom. − % det.</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="rounded-circle bg-success-subtle d-flex align-items-center justify-content-center bv-wh-44">
                                        <i class="fas fa-thumbs-up text-success"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-8">
                                <h5 class="card-title fw-semibold mb-3">Tendencia</h5>
                                <h4 class="fw-semibold mb-2 kpi-value" id="metric-trend"><div class="skeleton skeleton-title"></div></h4>
                                <p class="fs-3 mb-0 text-muted">1ª vs 2ª mitad del período</p>
                            </div>
                            <div class="col-4">
                                <div class="d-flex justify-content-end">
                                    <span class="rounded-circle bg-info-subtle d-flex align-items-center justify-content-center bv-wh-44">
                                        <i class="fas fa-arrow-trend-up text-info"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Distribution + Trend --}}
        <div class="row mb-4 g-3">
            <div class="col-lg-5">
                <div class="card w-100 h-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Distribución de ratings</h4>
                        <p class="card-subtitle mt-1">Valoraciones por número de estrellas</p>
                    </div>
                    <div class="card-body">
                        <div id="chart-distribution" class="bv-chart-260">
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <div class="spinner-border spinner-border-sm text-secondary"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card w-100 h-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Tendencia promedio</h4>
                        <p class="card-subtitle mt-1">Evolución diaria del CSAT en el período</p>
                    </div>
                    <div class="card-body">
                        <div id="chart-trend" class="bv-chart-260">
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <div class="spinner-border spinner-border-sm text-secondary"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top agents --}}
        <div class="row mb-4 g-3">
            <div class="col-12">
                <div class="card w-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">Top agentes por CSAT</h4>
                        <p class="card-subtitle mt-1">Promedio de valoración por agente en el período</p>
                    </div>
                    <div class="card-body">
                        <div id="chart-agents" class="bv-chart-280">
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <div class="spinner-border spinner-border-sm text-secondary"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Low ratings table --}}
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card w-100">
                    <div class="card-header">
                        <h4 class="card-title fw-semibold mb-0">
                            <i class="fas fa-exclamation-triangle text-brand me-1"></i>Valoraciones bajas
                        </h4>
                        <p class="card-subtitle mt-1">Últimos comentarios con 2 estrellas o menos</p>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="table-low-ratings">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Agente</th>
                                        <th scope="col">Puntuación</th>
                                        <th scope="col">Comentario</th>
                                        <th scope="col">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-low-ratings">
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Cargando datos...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('css')
<style>
    .skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: skeleton-loading 1.5s infinite;
        border-radius: 4px;
    }
    @keyframes skeleton-loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    .skeleton-title { height: 32px; width: 80px; }

    /* Mismo estilo del "health badge" que panel/dashboard: vive en el bloque
       de estilos propio de esa vista y no se hereda solo con la clase, hay
       que traerlo tal cual para que el badge de esta pagina se vea igual. */
    .dashboard-page .dashboard-health-badge {
        background-color: rgba(144, 187, 19, 0.12);
        color: #555555;
    }
    .dashboard-page .dashboard-health-badge .fw-bold { color: #90bb13; }

    /* Misma reasignacion de paleta que el dashboard: el tema base pinta el
       estado "danger" en rosa/rojo, que no es de la paleta de la casa. Aqui
       se reasigna al verde de marca, igual que en panel/dashboard. */
    .dashboard-page .text-brand { color: #90bb13 !important; }
    .dashboard-page .bg-brand-subtle {
        background-color: rgba(144, 187, 19, 0.12) !important;
        color: #90bb13 !important;
        border-color: #90bb13 !important;
    }
    .dashboard-page .bg-brand { background-color: #90bb13 !important; }
    .dashboard-page .text-warning { color: #7d9f10 !important; }
    .dashboard-page .bg-warning-subtle {
        background-color: rgba(144, 187, 19, 0.12) !important;
        color: #7d9f10 !important;
        border-color: #b6d34a !important;
    }
    .dashboard-page .bg-success-subtle { background-color: rgba(144, 187, 19, 0.12) !important; }
    .dashboard-page .text-success { color: #90bb13 !important; }
</style>
@endpush

@php
    // Construido en PHP y pasado a @json() como variable simple: un
    // @json([...]) multilinea directo (con coma final antes del `]`) trunca
    // la compilación de Blade en silencio.
    $hdReportsCsatConfig = [
        'dataUrl' => route('manager.helpdesk.reports.csat.data'),
        'exportUrl' => route('manager.helpdesk.exports.csat'),
    ];
@endphp

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
<script>window.HdReportsCsat = @json($hdReportsCsatConfig);</script>
{{-- JS extraido a public/vendor/helpdesk/reports/: se cachea en el navegador
     en vez de re-descargarse en cada carga de esta página. --}}
<script src="{{ asset('vendor/helpdesk/reports/csat.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/reports/csat.js')) }}" defer></script>
@endpush
