@extends('layouts.theme')
@section('title', 'Incumplimientos SLA · Conversaciones')
@section('page_header')
    @include('core::components.card', ['title' => 'Incumplimientos SLA · Conversaciones'])
@endsection

@section('content')

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <h1 class="h4 mb-0 fw-bold">
        <i class="fas fa-gauge-high text-danger me-2"></i>Incumplimientos SLA — Conversaciones
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
                <div class="h4 fw-bold mb-0 text-danger" id="stat-unresolved">—</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form id="filters" class="row g-2 align-items-end">
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
<script>
$(function () {
    const dataUrl = @json(route('helpdesksla.breaches.data'));
    const resolveUrlTpl = @json(route('helpdesksla.breaches.resolve', ['breach' => '__ID__']));
    const csrf = $('meta[name="csrf-token"]').attr('content');
    let currentPage = 1;
    let lastPage = 1;

    function fmt(iso) {
        if (!iso) return '—';
        const d = new Date(iso);
        return d.toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function overdue(min) {
        if (!min && min !== 0) return '—';
        if (min < 60) return min + 'm';
        if (min < 1440) return Math.floor(min / 60) + 'h';
        return Math.floor(min / 1440) + 'd';
    }

    function render(rows) {
        if (!rows.length) {
            $('#breach-rows').html('<tr><td colspan="8" class="text-center text-muted py-4">Sin incumplimientos para los filtros seleccionados.</td></tr>');
            return;
        }
        const html = rows.map(function (r) {
            const badge = r.resolved
                ? '<span class="badge bg-success-subtle text-success">Resuelto</span>'
                : '<span class="badge bg-danger-subtle text-danger">Sin resolver</span>';
            const actions = r.resolved ? '' :
                '<div class="dropdown">' +
                  '<button class="btn btn-sm btn-link text-body" data-bs-toggle="dropdown" aria-expanded="false">' +
                    '<i class="fas fa-ellipsis-vertical"></i></button>' +
                  '<ul class="dropdown-menu dropdown-menu-end">' +
                    '<li><button class="dropdown-item btn-resolve" data-id="' + r.id + '">Marcar resuelto</button></li>' +
                  '</ul></div>';
            return '<tr>' +
                '<td>#' + r.conversationId + ' <span class="text-muted">' + $('<div>').text(r.subject).html() + '</span></td>' +
                '<td>' + $('<div>').text(r.customer).html() + '</td>' +
                '<td>' + $('<div>').text(r.slaTypeLabel).html() + '</td>' +
                '<td>' + fmt(r.dueAt) + '</td>' +
                '<td>' + fmt(r.breachedAt) + '</td>' +
                '<td>' + overdue(r.minutesOver) + '</td>' +
                '<td>' + badge + '</td>' +
                '<td class="text-end">' + actions + '</td>' +
                '</tr>';
        }).join('');
        $('#breach-rows').html(html);
    }

    // Igual patron que #products-pagination en Supplier/settings/suppliers/detail.blade.php,
    // pero la pagina la calcula el servidor (meta.currentPage/lastPage) en vez del cliente.
    function renderPagination(meta) {
        const $pag = $('#breach-pagination').empty();
        if (!meta || meta.lastPage <= 1) {
            $('#breach-pagination-info').addClass('d-none');
            return;
        }
        $('#breach-pagination-info').removeClass('d-none');
        $('#breach-pagination-summary').text('Pagina ' + meta.currentPage + ' de ' + meta.lastPage + ' — ' + meta.total + ' incumplimiento(s)');
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
        $.get(dataUrl, $('#filters').serialize() + '&page=' + currentPage)
            .done(function (res) {
                render(res.data || []);
                $('#stat-total').text(res.meta ? res.meta.total : '—');
                $('#stat-unresolved').text(res.meta ? res.meta.unresolved : '—');
                renderPagination(res.meta);
            })
            .fail(function () {
                toastr.error('No se pudieron cargar los incumplimientos.');
            });
    }

    $('#filters').on('submit', function (e) { e.preventDefault(); load(1); });
    $('#btn-refresh').on('click', function () { load(1); });

    $('#breach-pagination').on('click', '.page-link', function (e) {
        e.preventDefault();
        const page = parseInt($(this).data('page'), 10);
        if (page >= 1 && page <= lastPage) { load(page); }
    });

    $(document).on('click', '.btn-resolve', function () {
        const id = $(this).data('id');
        $.ajax({
            url: resolveUrlTpl.replace('__ID__', id),
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
        }).done(function (res) {
            toastr.success(res.message || 'Marcado como resuelto.');
            load();
        }).fail(function (xhr) {
            toastr.error(xhr.responseJSON?.message || 'No se pudo marcar como resuelto.');
        });
    });

    load();
});
</script>
@endpush
