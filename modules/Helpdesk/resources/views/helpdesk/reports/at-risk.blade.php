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
<script>
$(function () {
    const dataUrl = "{{ route('manager.helpdesk.reports.at-risk.data') }}";

    let allCustomers = [];
    let riskFilter = '';
    let bulk = null;

    const riskLabels = { high: 'Alto (< 40)', medium: 'Medio (40 - 69)', good: 'Bueno (≥ 70)' };

    function esc(value) {
        return $('<span>').text(value == null ? '' : value).html();
    }

    function healthBadge(score) {
        // Sin rojos: gris neutro (bg-info-subtle) para riesgo alto/medio,
        // verde (bg-success-subtle) solo para salud buena. En este tema
        // bg-secondary-subtle/text-success resuelven al mismo verde de marca,
        // asi que el "gris" real que pidieron es bg-info-subtle.
        const cls = score >= 70 ? 'bg-success-subtle text-success' : 'bg-info-subtle text-info';
        return '<span class="badge ' + cls + '">' + score + '</span>';
    }

    function negativeBadge(count) {
        return '<span class="badge bg-info-subtle text-info">' + count + '</span>';
    }

    function formatDate(iso) {
        if (!iso) {
            return '—';
        }
        return new Date(iso).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function riskLevelOf(score) {
        if (score < 40) {
            return 'high';
        }
        return score < 70 ? 'medium' : 'good';
    }

    function renderRows(rows) {
        const tbody = $('#tbody-at-risk');
        tbody.empty();

        if (rows.length === 0) {
            const message = allCustomers.length === 0
                ? 'No hay clientes en riesgo en los últimos 90 días.'
                : 'Ningún cliente coincide con los filtros aplicados.';

            tbody.html(
                '<tr><td colspan="6" class="text-center text-muted py-5">' +
                '<i class="fas fa-heart fa-2x mb-2 d-block text-success"></i>' +
                message +
                '</td></tr>'
            );
            return;
        }

        rows.forEach(function (row) {
            // Sin boton "Ver ficha": la fila completa navega a la ficha del
            // cliente al hacer click (salvo el checkbox de seleccion masiva).
            const rowAttrs = row.contactUrl
                ? ' class="ar-row-clickable" data-contact-url="' + row.contactUrl + '"'
                : '';
            tbody.append(
                '<tr' + rowAttrs + '>' +
                '<td><input type="checkbox" class="form-check-input bulk-checkbox-ar" value="' + row.customerId + '" aria-label="Seleccionar cliente ' + esc(row.name) + '"></td>' +
                '<td class="fw-semibold">' + esc(row.name) + '</td>' +
                '<td class="text-muted">' + esc(row.email || '—') + '</td>' +
                '<td class="text-center">' + negativeBadge(row.negativeCount) + '</td>' +
                '<td class="text-center">' + healthBadge(row.healthScore) + '</td>' +
                '<td class="text-nowrap text-muted small">' + formatDate(row.lastNegativeAt) + '</td>' +
                '</tr>'
            );
        });

        // Las filas se repintan enteras en cada búsqueda/filtro/refresco — los
        // checkboxes de la tanda anterior ya no existen, así que la barra
        // flotante y el contador deben volver a 0 en vez de arrastrar una
        // selección de filas que ya no están en el DOM.
        if (bulk) bulk.reset();
    }

    function updateStats(customers) {
        $('#ar-stat-total').text(customers.length);
        $('#ar-stat-high').text(customers.filter(function (c) { return c.healthScore < 40; }).length);
        $('#ar-stat-medium').text(customers.filter(function (c) { return c.healthScore >= 40 && c.healthScore < 70; }).length);

        let latest = null;
        customers.forEach(function (c) {
            if (c.lastNegativeAt && (!latest || new Date(c.lastNegativeAt) > new Date(latest))) {
                latest = c.lastNegativeAt;
            }
        });
        $('#ar-stat-last-negative').text(formatDate(latest));
    }

    function updateFilterUi(search) {
        const hasFilters = !!search || !!riskFilter;

        $('#ar-filter-badge').toggleClass('d-none', !riskFilter);
        $('#ar-clear-btn').toggleClass('d-none', !hasFilters);
        $('#ar-filter-tags').toggleClass('d-none', !hasFilters);

        const tagsList = $('#ar-filter-tags-list').empty();
        if (search) {
            tagsList.append('<span class="badge bg-primary-subtle text-primary py-1 px-2">Búsqueda: ' + esc(search) + '</span>');
        }
        if (riskFilter) {
            tagsList.append('<span class="badge bg-primary-subtle text-primary py-1 px-2">Riesgo: ' + esc(riskLabels[riskFilter]) + '</span>');
        }
    }

    function applyFilters() {
        const search = $('#ar-search').val().trim();
        const searchLower = search.toLowerCase();

        const filtered = allCustomers.filter(function (c) {
            const matchesSearch = !searchLower ||
                (c.name && c.name.toLowerCase().indexOf(searchLower) !== -1) ||
                (c.email && c.email.toLowerCase().indexOf(searchLower) !== -1);
            const matchesRisk = !riskFilter || riskLevelOf(c.healthScore) === riskFilter;
            return matchesSearch && matchesRisk;
        });

        renderRows(filtered);
        updateFilterUi(search);
    }

    function load() {
        const tbody = $('#tbody-at-risk');
        tbody.html('<tr><td colspan="6" class="text-center text-muted py-4">Cargando datos...</td></tr>');

        $.getJSON(dataUrl).done(function (data) {
            allCustomers = data.customers || [];
            updateStats(allCustomers);
            applyFilters();
        }).fail(function () {
            tbody.html('<tr><td colspan="6" class="text-center text-dark py-4">Error al cargar los datos.</td></tr>');
            toastr.error('Error al cargar el reporte de clientes en riesgo.');
        });
    }

    $('.select2-filter-modal').select2({ dropdownParent: $('#ar-filter-modal'), width: '100%' });

    // Selección múltiple: solo la mecánica de marcar/contar/mostrar la barra
    // (BulkActions ya la resuelve igual que en el resto de listados con
    // bulk.js). Sin acción real todavía — este reporte es de solo lectura,
    // calculado al vuelo (sentimientos + salud), no hay ningún estado
    // "revisado"/"descartado" guardado en BD que una acción masiva pueda
    // tocar hoy — así que el botón queda deshabilitado con una nota en vez
    // de simular una acción que no existe.
    bulk = window.BulkActions.init({
        checkbox: '.bulk-checkbox-ar',
        toolbar: '#bulk-toolbar-ar',
        selectAll: '#ar-select-all',
    });

    // Fila clicable hacia la ficha del cliente (reemplaza el boton "Ver
    // ficha"). Se ignora el click cuando viene del checkbox de seleccion.
    $('#tbody-at-risk').on('click', 'tr.ar-row-clickable', function (e) {
        if ($(e.target).closest('td').is(':first-child')) {
            return;
        }
        window.location.href = $(this).data('contact-url');
    });

    $('#ar-filter-form').on('submit', function (e) {
        e.preventDefault();
        applyFilters();
    });

    $('#ar-clear-btn').on('click', function () {
        $('#ar-search').val('');
        riskFilter = '';
        $('#ar-modal-risk').val(null).trigger('change');
        applyFilters();
    });

    $('#ar-filter-apply-btn').on('click', function () {
        riskFilter = $('#ar-modal-risk').val() || '';
        $('#ar-filter-modal').modal('hide');
        applyFilters();
    });

    $('#ar-filter-clear-btn').on('click', function () {
        $('#ar-modal-risk').val(null).trigger('change');
    });

    $('#btn-refresh').on('click', load);

    load();
});
</script>
@endpush
@endsection
