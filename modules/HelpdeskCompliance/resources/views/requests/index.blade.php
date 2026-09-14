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
<script>
$(function () {
    const dataUrl = @json(route('helpdeskcompliance.requests.data'));
    let currentPage = 1;
    let lastPage = 1;

    const typeLabels = {
        delete_soft: 'Anonimizado',
        delete_hard: 'Borrado',
        export: 'Exportacion',
    };

    function fmt(iso) {
        if (!iso) return '—';
        return new Date(iso).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    // Sin rojos en la UI (paleta del proyecto): "failed" se resuelve en gris
    // oscuro con icono de aviso, igual que 'failed' => 'secondary' en
    // Supplier/sync/index.blade.php, en vez de bg-danger-subtle.
    function statusBadge(status) {
        const label = $('<div>').text(status).html();
        return status === 'completed'
            ? '<span class="badge bg-success-subtle text-success"><i class="fas fa-check me-1"></i>' + label + '</span>'
            : '<span class="badge bg-secondary-subtle text-secondary-emphasis"><i class="fas fa-triangle-exclamation me-1"></i>' + label + '</span>';
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
                '<td>' + statusBadge(r.status) + '</td>' +
                '<td>' + fmt(r.completedAt || r.createdAt) + '</td>' +
                '</tr>';
        }).join('');
        $('#request-rows').html(html);
    }

    // Igual patron que #breach-pagination en HelpdeskSla/breaches/index.blade.php:
    // la pagina la calcula el servidor (meta.currentPage/lastPage), no el cliente.
    function renderPagination(meta) {
        const $pag = $('#request-pagination').empty();
        if (!meta || meta.lastPage <= 1) {
            $('#request-pagination-info').addClass('d-none');
            return;
        }
        $('#request-pagination-info').removeClass('d-none');
        $('#request-pagination-summary').text('Pagina ' + meta.currentPage + ' de ' + meta.lastPage + ' — ' + meta.total + ' solicitud(es)');
        lastPage = meta.lastPage;

        $pag.append('<li class="page-item' + (meta.currentPage === 1 ? ' disabled' : '') + '"><a class="page-link" href="#" data-page="' + (meta.currentPage - 1) + '">&laquo;</a></li>');
        const range = 5;
        let start = Math.max(1, meta.currentPage - Math.floor(range / 2));
        const end = Math.min(meta.lastPage, start + range - 1);
        start = Math.max(1, end - range + 1);
        for (let i = start; i <= end; i++) {
            $pag.append('<li class="page-item' + (i === meta.currentPage ? ' active' : '') + '"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>');
        }
        $pag.append('<li class="page-item' + (meta.currentPage === meta.lastPage ? ' disabled' : '') + '"><a class="page-link" href="#" data-page="' + (meta.currentPage + 1) + '">&raquo;</a></li>');
    }

    function load(page) {
        currentPage = page || currentPage;
        $.get(dataUrl, { type: $('#f-type').val(), page: currentPage })
            .done(function (res) {
                render(res.data || []);
                renderPagination(res.meta);
            })
            .fail(function () { toastr.error('No se pudieron cargar las solicitudes.'); });
    }

    $('#f-type').on('change', function () { load(1); });
    $('#btn-refresh').on('click', function () { load(1); });
    $('#request-pagination').on('click', '.page-link', function (e) {
        e.preventDefault();
        const page = parseInt($(this).data('page'), 10);
        if (page >= 1 && page <= lastPage) { load(page); }
    });

    load();
});
</script>
@endpush
