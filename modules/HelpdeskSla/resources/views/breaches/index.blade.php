@extends('layouts.theme')
@section('title', 'Incumplimientos SLA · Conversaciones')
@section('page_header')
    @include('core::components.card', ['title' => 'Incumplimientos SLA · Conversaciones'])
@endsection

@section('content')

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <h1 class="h4 mb-0 fw-bold">
        <i class="fas fa-gauge-high text-brand me-2"></i>Incumplimientos SLA — Conversaciones
    </h1>
    <p class="text-muted small mb-0 w-100 order-3 mt-1">
        Historico de conversaciones que incumplieron su politica de SLA (primera respuesta o resolucion).
    </p>
    <div class="ms-auto order-2">
        <button id="btn-refresh" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-rotate me-1"></i>Actualizar
        </button>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Total</div>
                <div class="h4 fw-bold mb-0" id="stat-total">—</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small">Sin resolver</div>
                <div class="h4 fw-bold mb-0 text-brand" id="stat-unresolved">—</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form id="filters" class="row g-2 align-items-end"
              data-url="{{ route('helpdesksla.breaches.data') }}"
              data-resolve-url-template="{{ route('helpdesksla.breaches.resolve', ['breach' => '__ID__']) }}">
            <div class="col-sm-3">
                <label class="form-label small mb-1" for="f-type">Tipo</label>
                <select id="f-type" name="sla_type" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="first_response">Primera respuesta</option>
                    <option value="resolution">Resolucion</option>
                </select>
            </div>
            <div class="col-sm-3">
                <label class="form-label small mb-1" for="f-resolved">Estado</label>
                <select id="f-resolved" name="resolved" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="0">Sin resolver</option>
                    <option value="1">Resueltos</option>
                </select>
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1" for="f-from">Desde</label>
                <input type="date" id="f-from" name="from" class="form-control form-control-sm">
            </div>
            <div class="col-sm-2">
                <label class="form-label small mb-1" for="f-to">Hasta</label>
                <input type="date" id="f-to" name="to" class="form-control form-control-sm">
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="fas fa-filter me-1"></i>Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Conversacion</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Vencimiento</th>
                    <th>Incumplido</th>
                    <th>Retraso</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="breach-rows">
                <tr><td colspan="8" class="text-center text-muted py-4">Cargando datos...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center d-none" id="breach-pagination-info">
        <span class="text-muted small" id="breach-pagination-summary"></span>
        <nav><ul class="pagination pagination-sm mb-0" id="breach-pagination"></ul></nav>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('modules/helpdesksla/js/breaches-index.js') }}?v={{ filemtime(public_path('modules/helpdesksla/js/breaches-index.js')) }}"></script>
@endpush
