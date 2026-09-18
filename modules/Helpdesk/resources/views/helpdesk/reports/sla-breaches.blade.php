@extends('layouts.theme')
@section('title', 'Incumplimientos SLA · Helpdesk')

@section('page_header')
    @include('core::components.card', ['title' => 'Incumplimientos SLA · Helpdesk'])
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations.css') }}?v={{ @filemtime(public_path('vendor/helpdesk/conversations.css')) }}"/>
@endpush

@section('content')

{{-- Estado no disponible (módulo de tickets desactivado) --}}
<div id="state-unavailable" class="text-center py-5 d-none">
    <i class="fas fa-plug-circle-xmark fa-3x mb-3 text-muted opacity-50"></i>
    <h5 class="fw-bold mb-2">El módulo de tickets no está disponible</h5>
    <p class="text-muted mb-0">Sin él no hay SLA que medir. Actívalo en los ajustes de integraciones del helpdesk.</p>
</div>

<div id="state-content" class="widget-content searchable-container list d-none">

    <div class="card">

        {{-- Header --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Incumplimientos SLA</h5>
                    <p class="small mb-0 text-muted">Tickets abiertos que han superado su plazo de resolución, y los que lo superarán en las próximas 24 horas</p>
                </div>
                <div class="ms-auto d-flex gap-2">
                    <button type="button" id="btn-export" class="btn btn-secondary">
                        <i class="fas fa-file-export me-1"></i> Exportar
                    </button>
                    <button type="button" id="btn-refresh" class="btn btn-primary">
                        <i class="fas fa-rotate me-1"></i> Actualizar
                    </button>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Incumplidos ahora</h6>
                            <h4 class="mb-1 fw-bold" id="kpi-breached">—</h4>
                            <small class="text-muted" id="kpi-breached-hint">&nbsp;</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Agentes afectados</h6>
                            <h4 class="mb-1 fw-bold" id="kpi-agents">—</h4>
                            <small class="text-muted" id="kpi-agents-hint">&nbsp;</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Retraso medio</h6>
                            <h4 class="mb-1 fw-bold" id="kpi-avg">—</h4>
                            <small class="text-muted" id="kpi-avg-hint">&nbsp;</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-secondary h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Vencen en 24 h</h6>
                            <h4 class="mb-1 fw-bold" id="kpi-upcoming">—</h4>
                            <small class="text-muted" id="kpi-upcoming-hint">&nbsp;</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="card-body border-bottom">
            <div class="d-flex align-items-center gap-2">
                <input type="search" id="slb-search" class="form-control flex-grow-1"
                       placeholder="Buscar por asunto o número..." autocomplete="off">
                <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                        data-bs-toggle="modal" data-bs-target="#slb-filter-modal" title="Filtros avanzados">
                    <i class="fas fa-filter"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary slb-filter-badge d-none" id="slb-filter-badge">1</span>
                </button>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="card-body">
            <ul class="nav nav-pills user-profile-tab mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            data-bs-toggle="pill" data-bs-target="#slb-tab-breached"
                            type="button" role="tab" aria-controls="slb-tab-breached" aria-selected="true">
                        Vencidos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative rounded-0 d-flex align-items-center justify-content-center bg-transparent fs-3 py-3"
                            data-bs-toggle="pill" data-bs-target="#slb-tab-upcoming"
                            type="button" role="tab" aria-controls="slb-tab-upcoming" aria-selected="false">
                        Próximos a vencer
                    </button>
                </li>
            </ul>

            <div class="tab-content">

                {{-- Vencidos --}}
                <div class="tab-pane fade show active" id="slb-tab-breached">
                    <p class="small text-muted mb-3">Ordenados por retraso, de mayor a menor.</p>

                    <div id="slb-breached-wrap">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        @if($bulkUrl)
                                            <th width="3%"><input type="checkbox" id="slb-select-all-breached" class="form-check-input" aria-label="Seleccionar todos los tickets"></th>
                                        @endif
                                        <th>Ticket</th>
                                        <th>Asunto</th>
                                        <th>Agente</th>
                                        <th>Vencía</th>
                                        <th>Retraso</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="slb-breached-body"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="text-muted small" id="slb-count">&nbsp;</div>
                            <div class="text-muted small" id="slb-updated">&nbsp;</div>
                        </div>
                    </div>

                    <div id="slb-breached-empty" class="text-center py-5 d-none">
                        <i class="fas fa-circle-check fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2" id="slb-breached-empty-title">Ningún SLA incumplido</h5>
                        <p class="text-muted mb-0" id="slb-breached-empty-text">Todos los tickets abiertos están dentro de plazo.</p>
                    </div>
                </div>

                {{-- Próximos a vencer --}}
                <div class="tab-pane fade" id="slb-tab-upcoming">
                    <p class="small text-muted mb-3">Todavía a tiempo. La barra marca cuánto queda de las 24 horas.</p>

                    <div id="slb-upcoming-wrap">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        @if($bulkUrl)
                                            <th width="3%"><input type="checkbox" id="slb-select-all-upcoming" class="form-check-input" aria-label="Seleccionar todos los tickets"></th>
                                        @endif
                                        <th>Ticket</th>
                                        <th>Asunto</th>
                                        <th>Agente</th>
                                        <th>Vence</th>
                                        <th>Tiempo restante</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="slb-upcoming-body"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="text-muted small" id="slb-upcoming-count">&nbsp;</div>
                        </div>
                    </div>

                    <div id="slb-upcoming-empty" class="text-center py-5 d-none">
                        <i class="fas fa-circle-check fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">Sin vencimientos a la vista</h5>
                        <p class="text-muted mb-0">Ningún ticket abierto vence en las próximas 24 horas.</p>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

{{-- Modal de filtros avanzados --}}
<div class="modal fade" id="slb-filter-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filtros avanzados</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Agente</label>
                    <select id="slb-modal-agent" class="form-control select2-filter-modal">
                        <option value="">Todos los agentes</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Retraso</label>
                    <select id="slb-modal-band" class="form-control select2-filter-modal">
                        <option value="all">Todos (0)</option>
                        <option value="high">Más de 48 h (0)</option>
                        <option value="mid">24 – 48 h (0)</option>
                        <option value="low">Menos de 24 h (0)</option>
                    </select>
                    <p class="small text-muted mt-2 mb-0">Solo aplica a la pestaña "Vencidos".</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="slb-filter-apply-btn" class="btn btn-primary w-100 mb-1">Aplicar filtros</button>
                <button type="button" id="slb-filter-clear-btn" class="btn btn-secondary w-100">Limpiar</button>
            </div>
        </div>
    </div>
</div>

@if($bulkUrl)
    {{-- Bulk: reasignar en masa — una barra/modal por pestaña (Vencidos / Próximos
         a vencer), igual patrón que ticket-templates, para no mezclar la selección
         de una tabla con la otra. --}}
    {{-- Los ids de toolbar/modal siguen la convención que espera core/js/bulk.js
         (bulk-toolbar-X → bulk-X-modal, ver getCountEls() ahí): con otro prefijo
         el contador del modal se queda pegado en 0 porque no encuentra el modal. --}}
    @foreach(['breached' => 'Vencidos', 'upcoming' => 'Próximos a vencer'] as $slbGroup => $slbGroupLabel)
        <div id="bulk-toolbar-{{ $slbGroup }}" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none slb-bulk-toolbar">
            <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-{{ $slbGroup }}-modal">
                <span data-bulk-count>0</span> ticket(s) seleccionado(s) — Reasignar
            </button>
        </div>

        <div class="modal fade" id="bulk-{{ $slbGroup }}-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reasignar tickets — {{ $slbGroupLabel }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">Se reasignarán <strong><span data-bulk-count>0</span> ticket(s)</strong> al agente seleccionado.</p>
                        <div class="mb-0">
                            <label class="form-label fw-semibold">Agente</label>
                            <select id="slb-bulk-{{ $slbGroup }}-agent" class="form-select select2">
                                <option value="">Seleccionar agente...</option>
                                @foreach($bulkAgents as $bulkAgent)
                                    <option value="{{ $bulkAgent['id'] }}">{{ $bulkAgent['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button id="slb-bulk-{{ $slbGroup }}-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Reasignar</button>
                        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif

@endsection

@push('styles')
<style>
    .slb-filter-badge { font-size: .6rem; }
    .slb-progress { height: 6px; }
    .slb-bulk-toolbar { z-index: 1050; }
</style>
@endpush

@php
    // Construido en PHP y pasado a @json() como variable simple: un
    // @json([...]) multilinea directo (con coma final antes del `]`) trunca
    // la compilación de Blade en silencio.
    $hdReportsSlaBreachesConfig = [
        'dataUrl' => route('manager.helpdesk.reports.sla-breaches.data'),
        'bulkUrl' => $bulkUrl,
    ];
@endphp

@if($bulkUrl)
    @push('scripts')
    <script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
    @endpush
@endif

@push('scripts')
<script>window.HdReportsSlaBreaches = @json($hdReportsSlaBreachesConfig);</script>
{{-- JS extraido a public/vendor/helpdesk/reports/: se cachea en el navegador
     en vez de re-descargarse en cada carga de esta página. --}}
<script src="{{ asset('vendor/helpdesk/reports/sla-breaches.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/reports/sla-breaches.js')) }}" defer></script>
@endpush
