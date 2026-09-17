@extends('layouts.theme')
@section('title', 'Solicitudes GDPR · Cumplimiento')
@section('page_header')
    @include('core::components.card', ['title' => 'Solicitudes GDPR · Cumplimiento'])
@endsection

@section('content')

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <h1 class="h4 mb-0 fw-bold">
        <i class="fas fa-user-shield text-primary me-2"></i>Solicitudes de cumplimiento (GDPR)
    </h1>
    <p class="text-muted small mb-0 w-100 order-3 mt-1">
        Registro trazable de cada borrado/anonimizado de cliente y su cascada a tickets y sesiones de chatbot.
    </p>
    <div class="ms-auto order-2 d-flex gap-2">
        <label class="visually-hidden" for="f-type">Tipo</label>
        <select id="f-type" class="form-select form-select-sm">
            <option value="">Todos los tipos</option>
            <option value="delete_soft">Anonimizado (soft)</option>
            <option value="delete_hard">Borrado (hard)</option>
            <option value="export">Exportacion</option>
        </select>
        <button id="btn-refresh" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-rotate me-1"></i>Actualizar
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Modulos afectados</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody id="request-rows">
                <tr><td colspan="5" class="text-center text-muted py-4">Cargando datos...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center d-none" id="request-pagination-info">
        <span class="text-muted small" id="request-pagination-summary"></span>
        <nav><ul class="pagination pagination-sm mb-0" id="request-pagination"></ul></nav>
    </div>
</div>

@endsection

@push('scripts')
{{-- Config inline (datos, no lógica): la lógica real vive en
     public/js/requests.js, que no tiene acceso a route(). --}}
<script>
window.HelpdeskComplianceRequests = {
    dataUrl: @json(route('helpdeskcompliance.requests.data')),
};
</script>
<script src="{{ asset('modules/helpdeskcompliance/js/requests.js') }}?v={{ @filemtime(public_path('modules/helpdeskcompliance/js/requests.js')) }}" defer></script>
@endpush
