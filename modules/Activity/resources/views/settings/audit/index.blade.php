@extends('layouts.theme')

@section('title', 'Auditoría de actividad')

@section('content')

    @include('core::components.card', ['title' => 'Auditoría de actividad'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Auditoría de actividad</h5>
                        <p class="small mb-0 text-muted">Historial detallado de eventos del sistema con soporte de filtros avanzados</p>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total eventos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">Registrados en el sistema</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Creaciones</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['created']) }}</h4>
                                <small class="text-muted">Eventos de creación</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Actualizaciones</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['updated']) }}</h4>
                                <small class="text-muted">Eventos de modificación</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Eliminaciones</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['deleted']) }}</h4>
                                <small class="text-muted">Eventos de eliminación</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom">
                <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                    <div class="flex-shrink-0" style="min-width:160px;">
                        <select class="form-select select2 h-100" id="filterEvent">
                            <option value="">Todos los eventos</option>
                            <option value="created">Creado</option>
                            <option value="updated">Actualizado</option>
                            <option value="deleted">Eliminado</option>
                            <option value="login">Login</option>
                            <option value="logout">Logout</option>
                        </select>
                    </div>
                    <div class="flex-shrink-0" style="min-width:160px;">
                        <select class="form-select select2 h-100" id="filterLogName">
                            <option value="">Todos los módulos</option>
                            <option value="reviews">Reviews</option>
                            <option value="pages">Páginas</option>
                            <option value="users">Usuarios</option>
                            <option value="seo">SEO</option>
                            <option value="settings">Configuración</option>
                        </select>
                    </div>
                    <div class="flex-shrink-0" style="min-width:140px;">
                        <input type="date" class="form-control h-100" id="filterDateFrom" placeholder="Desde">
                    </div>
                    <div class="flex-shrink-0" style="min-width:140px;">
                        <input type="date" class="form-control h-100" id="filterDateTo" placeholder="Hasta">
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <button class="btn btn-primary px-4" id="applyFilters">
                            <i class="fas fa-filter me-1"></i>
                        </button>
                        <button class="btn btn-outline-secondary" id="clearFilters" title="Limpiar filtros">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">Historial de actividad</h6>
                        <p class="text-muted small mb-0" id="total-count">Cargando...</p>
                    </div>
                </div>

                <div id="activity-table-container">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary mb-2"></div>
                        <p class="text-muted small mb-0">Cargando actividad...</p>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-white py-2" id="pagination-container"></div>

        </div>
    </div>

    {{-- Detail modal --}}
    <div class="modal fade" id="activityDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de actividad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="activity-detail-body"></div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
(function () {
    var currentPage = 1;
    var eventColors = {
        created: 'success',
        updated: 'primary',
        deleted: 'primary',
        login: 'info',
        logout: 'secondary'
    };

    function eventBadge(event) {
        var color = eventColors[event] || 'secondary';
        return '<span class="badge bg-' + color + '-subtle text-' + color + '">' + (event || 'n/a') + '</span>';
    }

    function loadData(page) {
        page = page || 1;
        currentPage = page;

        $('#activity-table-container').html(
            '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary"></div></div>'
        );

        $.get('{{ route("activity.audit.data") }}', {
            page: page,
            event: $('#filterEvent').val(),
            log_name: $('#filterLogName').val(),
            date_from: $('#filterDateFrom').val(),
            date_to: $('#filterDateTo').val()
        }, function (res) {
            if (!res.status) { return; }

            $('#total-count').text(res.pagination.total + ' registros encontrados');

            if (!res.data.length) {
                $('#activity-table-container').html(
                    '<div class="text-center py-5">' +
                    '<i class="fas fa-inbox fa-3x text-muted opacity-50 d-block mb-3"></i>' +
                    '<h6 class="text-muted">Sin actividad registrada</h6></div>'
                );
                $('#pagination-container').html('');
                return;
            }

            var rows = res.data.map(function (a) {
                var subject = a.subject_type
                    ? '<span class="badge bg-light text-dark">' + a.subject_type + (a.subject_id ? ' #' + a.subject_id : '') + '</span>'
                    : '';
                var safeData = JSON.stringify(a).replace(/"/g, '&quot;');
                return '<tr style="cursor:pointer;" onclick="showDetail(' + safeData + ')">' +
                    '<td>' +
                        '<div class="small fw-semibold">' + a.causer_name + '</div>' +
                        '<small class="text-muted">' + a.causer_email + '</small>' +
                    '</td>' +
                    '<td>' + eventBadge(a.event) + '</td>' +
                    '<td>' +
                        '<small class="text-truncate d-block" style="max-width:240px;">' + (a.description || '-') + '</small>' +
                        subject +
                    '</td>' +
                    '<td><span class="badge bg-light text-dark">' + (a.log_name || 'default') + '</span></td>' +
                    '<td>' +
                        '<div class="small">' + a.created_at + '</div>' +
                        '<small class="text-muted">' + a.created_at_human + '</small>' +
                    '</td>' +
                    '</tr>';
            }).join('');

            $('#activity-table-container').html(
                '<div class="table-responsive">' +
                '<table class="table table-hover align-middle mb-0">' +
                '<thead class="table-light">' +
                '<tr><th>Usuario</th><th>Evento</th><th>Descripción</th><th>Módulo</th><th>Fecha</th></tr>' +
                '</thead><tbody>' + rows + '</tbody></table></div>'
            );

            renderPagination(res.pagination);
        });
    }

    function renderPagination(p) {
        if (p.last_page <= 1) { $('#pagination-container').html(''); return; }

        var html = '<nav><ul class="pagination pagination-sm mb-0 justify-content-center">';
        html += '<li class="page-item' + (p.current_page === 1 ? ' disabled' : '') + '">' +
            '<a class="page-link" href="#" data-page="' + (p.current_page - 1) + '">&#8249;</a></li>';
        var start = Math.max(1, p.current_page - 2);
        var end = Math.min(p.last_page, p.current_page + 2);
        for (var i = start; i <= end; i++) {
            html += '<li class="page-item' + (i === p.current_page ? ' active' : '') + '">' +
                '<a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
        }
        html += '<li class="page-item' + (p.current_page === p.last_page ? ' disabled' : '') + '">' +
            '<a class="page-link" href="#" data-page="' + (p.current_page + 1) + '">&#8250;</a></li>';
        html += '</ul></nav>';
        $('#pagination-container').html(html);
    }

    window.showDetail = function (a) {
        var hasProps = a.properties && Object.keys(a.properties).length > 0;
        var propsHtml = hasProps
            ? '<pre class="bg-light p-3 rounded small mb-0">' + JSON.stringify(a.properties, null, 2) + '</pre>'
            : '<p class="text-muted small mb-0">Sin propiedades adicionales</p>';

        $('#activity-detail-body').html(
            '<dl class="row mb-0">' +
            '<dt class="col-4 small">Usuario</dt><dd class="col-8 small">' + a.causer_name + (a.causer_email ? ' (' + a.causer_email + ')' : '') + '</dd>' +
            '<dt class="col-4 small">Evento</dt><dd class="col-8 small">' + eventBadge(a.event) + '</dd>' +
            '<dt class="col-4 small">Descripción</dt><dd class="col-8 small">' + (a.description || '-') + '</dd>' +
            '<dt class="col-4 small">Módulo</dt><dd class="col-8 small">' + (a.log_name || 'default') + '</dd>' +
            '<dt class="col-4 small">Objeto</dt><dd class="col-8 small">' + (a.subject_type ? a.subject_type + (a.subject_id ? ' #' + a.subject_id : '') : '-') + '</dd>' +
            '<dt class="col-4 small">Fecha</dt><dd class="col-8 small">' + a.created_at + '</dd>' +
            '<dt class="col-4 small">Propiedades</dt><dd class="col-8">' + propsHtml + '</dd>' +
            '</dl>'
        );

        new bootstrap.Modal(document.getElementById('activityDetailModal')).show();
    };

    $(document).on('click', '#pagination-container .page-link', function (e) {
        e.preventDefault();
        var page = parseInt($(this).data('page'));
        if (page >= 1) { loadData(page); }
    });

    $('#applyFilters').on('click', function () { loadData(1); });
    $('#filterEvent, #filterLogName').on('change', function () { loadData(1); });
    $('#clearFilters').on('click', function () {
        $('#filterEvent, #filterLogName').val('').trigger('change');
        $('#filterDateFrom, #filterDateTo').val('');
        loadData(1);
    });

    loadData(1);
})();
</script>
@endpush
