@extends('layouts.theme')
@section('title', 'Tendencias · Helpdesk')
@section('page_header')
    @include('core::components.card', ['title' => 'Tendencias · Helpdesk'])
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations.css') }}?v={{ @filemtime(public_path('vendor/helpdesk/conversations.css')) }}"/>
@endpush

@section('content')

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <h1 class="h4 mb-0 fw-bold">
        <i class="fas fa-chart-line text-primary me-2"></i>Tendencias
    </h1>
    <div class="ms-auto d-flex gap-2 flex-wrap">
        <input type="date" id="filter-from" class="form-control form-control-sm bv-w-150">
        <input type="date" id="filter-to"   class="form-control form-control-sm bv-w-150">
        <select id="filter-inbox" class="form-select form-select-sm bv-w-160">
            <option value="">Todos los inboxes</option>
        </select>
        <button id="btn-apply" class="btn btn-sm btn-primary">
            Aplicar
        </button>
    </div>
</div>

{{-- Tabs --}}
<ul class="nav nav-tabs mb-3" id="trends-tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-volumen" type="button">
            <i class="fas fa-comments me-1"></i>Volumen
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tiempos" type="button">
            <i class="fas fa-clock me-1"></i>Tiempos
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-satisfaccion" type="button">
            <i class="fas fa-star me-1"></i>Satisfacción
        </button>
    </li>
</ul>

<div class="tab-content">

    {{-- ── Volumen ── --}}
    <div class="tab-pane fade show active" id="tab-volumen">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase small text-muted mb-3">Mensajes recibidos</h6>
                        <canvas id="chart-messages" height="160"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase small text-muted mb-3">Conversaciones creadas vs resueltas</h6>
                        <canvas id="chart-created-closed" height="160"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-uppercase small text-muted mb-3">Volumen por canal</h6>
                        <canvas id="chart-channels" height="120"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tiempos ── --}}
    <div class="tab-pane fade" id="tab-tiempos">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase small text-muted mb-3">Tiempo promedio de primera respuesta (min)</h6>
                        <canvas id="chart-frt" height="160"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase small text-muted mb-3">Tiempo promedio de resolución (min)</h6>
                        <canvas id="chart-resolution" height="160"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Satisfacción ── --}}
    <div class="tab-pane fade" id="tab-satisfaccion">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-uppercase small text-muted mb-3">CSAT promedio diario</h6>
                        <canvas id="chart-csat" height="160"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>window.HdReportsTrends = @json(['dataUrl' => route('manager.helpdesk.reports.trends.data')]);</script>
{{-- JS extraido a public/vendor/helpdesk/reports/: se cachea en el navegador
     en vez de re-descargarse en cada carga de esta página. --}}
<script src="{{ asset('vendor/helpdesk/reports/trends.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/reports/trends.js')) }}" defer></script>
@endpush
@endsection
