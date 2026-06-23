@extends('layouts.theme')
@section('title', 'Solicitudes GDPR · Cumplimiento')
@section('content')

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <h1 class="h4 mb-0 fw-bold">
        <i class="fas fa-user-shield text-primary me-2"></i>Solicitudes de cumplimiento (GDPR)
    </h1>
    <p class="text-muted small mb-0 w-100 order-3 mt-1">
        Registro trazable de cada borrado/anonimizado de cliente y su cascada a tickets y sesiones de chatbot.
    </p>
    <div class="ms-auto order-2 d-flex gap-2">
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
</div>

@endsection

@push('scripts')
<script>
$(function () {
    const dataUrl = @json(route('helpdeskcompliance.requests.data'));

    const typeLabels = {
        delete_soft: 'Anonimizado',
        delete_hard: 'Borrado',
        export: 'Exportacion',
    };

    function fmt(iso) {
        if (!iso) return '—';
        return new Date(iso).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function render(rows) {
        if (!rows.length) {
            $('#request-rows').html('<tr><td colspan="5" class="text-center text-muted py-4">Sin solicitudes registradas.</td></tr>');
            return;
        }
        const html = rows.map(function (r) {
            const mods = (r.modulesAffected || []).map(function (m) {
                return '<span class="badge bg-light text-dark me-1">' + $('<div>').text(m).html() + '</span>';
            }).join('') || '<span class="text-muted">—</span>';
            const typeBadge = r.type === 'delete_hard'
                ? '<span class="badge bg-danger-subtle text-danger">' + (typeLabels[r.type] || r.type) + '</span>'
                : '<span class="badge bg-warning-subtle text-warning">' + (typeLabels[r.type] || r.type) + '</span>';
            return '<tr>' +
                '<td>' + (r.customer ? $('<div>').text(r.customer).html() : '<span class="text-muted">#' + (r.customerId || '?') + '</span>') + '</td>' +
                '<td>' + typeBadge + '</td>' +
                '<td>' + mods + '</td>' +
                '<td><span class="badge bg-success-subtle text-success">' + $('<div>').text(r.status).html() + '</span></td>' +
                '<td>' + fmt(r.completedAt || r.createdAt) + '</td>' +
                '</tr>';
        }).join('');
        $('#request-rows').html(html);
    }

    function load() {
        $.get(dataUrl, { type: $('#f-type').val() })
            .done(function (res) { render(res.data || []); })
            .fail(function () { toastr.error('No se pudieron cargar las solicitudes.'); });
    }

    $('#f-type').on('change', load);
    $('#btn-refresh').on('click', load);
    load();
});
</script>
@endpush
