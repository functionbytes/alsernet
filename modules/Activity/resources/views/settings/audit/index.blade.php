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
                    <div class="flex-fill">
                        <div class="input-group h-100">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="search" class="form-control border-start-0 ps-0" id="filterSearch" placeholder="Buscar en descripción...">
                        </div>
                    </div>
                    <div class="flex-shrink-0" style="min-width: 180px;">
                        <select class="form-select select2 h-100" id="filterEvent">
                            <option value="">Todos los eventos</option>
                            <option value="created">Creado</option>
                            <option value="updated">Actualizado</option>
                            <option value="deleted">Eliminado</option>
                            <option value="login">Login</option>
                            <option value="logout">Logout</option>
                        </select>
                    </div>
                    <div class="flex-shrink-0" style="min-width: 180px;">
                        <select class="form-select select2 h-100" id="filterLogName">
                            <option value="">Todos los módulos</option>
                            @foreach($logNames as $logName)
                                <option value="{{ $logName }}">{{ ucfirst($logName) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-shrink-0" style="min-width: 150px;">
                        <input type="date" class="form-control h-100" id="filterDateFrom">
                    </div>
                    <div class="flex-shrink-0" style="min-width: 150px;">
                        <input type="date" class="form-control h-100" id="filterDateTo">
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <button type="button" class="btn btn-primary" id="applyFilters">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 fw-bold">Historial de actividad</h6>
                        <p class="text-muted mb-0" id="total-count">Cargando...</p>
                    </div>
                </div>

                <div id="activity-table-container">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary mb-2"></div>
                        <p class="text-muted mb-0">Cargando actividad...</p>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-white px-4" id="pagination-container"></div>

        </div>
    </div>

    {{-- Detail modal --}}
    <div class="modal fade" id="activityDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="activity-detail-title">Detalle de actividad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="activity-detail-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100 mb-2" data-bs-dismiss="modal">Cerrar</button>
                </div>
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
            search: $('#filterSearch').val(),
            event: $('#filterEvent').val(),
            log_name: $('#filterLogName').val(),
            date_from: $('#filterDateFrom').val(),
            date_to: $('#filterDateTo').val()
        }, function (res) {
            if (!res.success) { return; }

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
        if (p.last_page <= 1) {
            $('#pagination-container').html(
                '<div class="d-flex justify-content-center py-2">' +
                '<small class="text-muted">Mostrando ' + p.total + ' registro(s)</small>' +
                '</div>'
            );
            return;
        }

        var from = (p.current_page - 1) * p.per_page + 1;
        var to = Math.min(p.current_page * p.per_page, p.total);

        var info = '<small class="text-muted">Mostrando ' + from + '-' + to + ' de ' + p.total + ' registros</small>';

        var pages = [];
        pages.push(1);
        if (p.current_page > 3) { pages.push('...'); }
        for (var i = Math.max(2, p.current_page - 1); i <= Math.min(p.last_page - 1, p.current_page + 1); i++) {
            pages.push(i);
        }
        if (p.current_page < p.last_page - 2) { pages.push('...'); }
        if (p.last_page > 1) { pages.push(p.last_page); }

        var nav = '<ul class="pagination pagination-sm mb-0">';
        nav += '<li class="page-item' + (p.current_page === 1 ? ' disabled' : '') + '">' +
            '<a class="page-link" href="#" data-page="' + (p.current_page - 1) + '">&lsaquo;</a></li>';
        for (var j = 0; j < pages.length; j++) {
            if (pages[j] === '...') {
                nav += '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
            } else {
                nav += '<li class="page-item' + (pages[j] === p.current_page ? ' active' : '') + '">' +
                    '<a class="page-link" href="#" data-page="' + pages[j] + '">' + pages[j] + '</a></li>';
            }
        }
        nav += '<li class="page-item' + (p.current_page === p.last_page ? ' disabled' : '') + '">' +
            '<a class="page-link" href="#" data-page="' + (p.current_page + 1) + '">&rsaquo;</a></li>';
        nav += '</ul>';

        $('#pagination-container').html(
            '<div class="d-flex justify-content-between align-items-center py-2">' +
            info + '<nav>' + nav + '</nav></div>'
        );
    }

    function formatValue(val) {
        if (val === null || val === undefined) return '<span class="text-muted fst-italic">vacío</span>';
        if (typeof val === 'object') return JSON.stringify(val);
        return String(val);
    }

    function renderChangesTable(props) {
        var attrs = props.attributes || {};
        var old = props.old || {};
        var keys = Object.keys(attrs);
        if (!keys.length) return '';

        var rows = keys.map(function (key) {
            var oldVal = old.hasOwnProperty(key) ? old[key] : null;
            var newVal = attrs[key];
            return '<tr>' +
                '<td class="fw-semibold">' + key + '</td>' +
                '<td class="text-danger">' + formatValue(oldVal) + '</td>' +
                '<td class="text-success fw-semibold">' + formatValue(newVal) + '</td>' +
                '</tr>';
        }).join('');

        return '<label class="form-label fw-semibold text-muted mb-2">Cambios realizados</label>' +
            '<div class="table-responsive">' +
            '<table class="table table-striped table-bordered w-100 text-nowrap">' +
            '<thead><tr><th>Campo</th><th>Valor anterior</th><th>Valor nuevo</th></tr>' +
            '</thead><tbody>' + rows + '</tbody></table></div>';
    }

    function renderFlatProperties(props) {
        var exclude = ['attributes', 'old'];
        var keys = Object.keys(props).filter(function (k) { return exclude.indexOf(k) === -1; });
        if (!keys.length) return '';

        var rows = keys.map(function (key) {
            var val = props[key];
            var display = (typeof val === 'object' && val !== null) ? JSON.stringify(val) : formatValue(val);
            return '<tr>' +
                '<td class="fw-semibold">' + key + '</td>' +
                '<td>' + display + '</td>' +
                '</tr>';
        }).join('');

        return '<label class="form-label fw-semibold text-muted mb-2">Propiedades</label>' +
            '<div class="table-responsive">' +
            '<table class="table table-striped table-bordered w-100 text-nowrap">' +
            '<thead><tr><th>Campo</th><th>Valor</th></tr>' +
            '</thead><tbody>' + rows + '</tbody></table></div>';
    }

    window.showDetail = function (a) {
        $('#activity-detail-title').text(a.description || 'Detalle de actividad');

        var subject = a.subject_type ? a.subject_type + (a.subject_id ? ' #' + a.subject_id : '') : '-';

        var html = '<div class="row g-3 mb-4">' +
            '<div class="col-md-6">' +
                '<label class="form-label fw-semibold text-muted mb-1">Usuario</label>' +
                '<input type="text" class="form-control bg-light" value="' + a.causer_name + (a.causer_email ? ' (' + a.causer_email + ')' : '') + '" readonly>' +
            '</div>' +
            '<div class="col-md-6">' +
                '<label class="form-label fw-semibold text-muted mb-1">Fecha</label>' +
                '<input type="text" class="form-control bg-light" value="' + a.created_at + ' (' + a.created_at_human + ')" readonly>' +
            '</div>' +
            '<div class="col-md-4">' +
                '<label class="form-label fw-semibold text-muted mb-1">Evento</label>' +
                '<input type="text" class="form-control bg-light" value="' + (a.event || 'n/a') + '" readonly>' +
            '</div>' +
            '<div class="col-md-4">' +
                '<label class="form-label fw-semibold text-muted mb-1">Modulo</label>' +
                '<input type="text" class="form-control bg-light" value="' + (a.log_name || 'default') + '" readonly>' +
            '</div>' +
            '<div class="col-md-4">' +
                '<label class="form-label fw-semibold text-muted mb-1">Objeto</label>' +
                '<input type="text" class="form-control bg-light" value="' + subject + '" readonly>' +
            '</div>' +
        '</div>';

        var hasProps = a.properties && Object.keys(a.properties).length > 0;
        if (hasProps) {
            var hasChanges = a.properties.attributes && Object.keys(a.properties.attributes).length > 0;
            if (hasChanges) {
                html += renderChangesTable(a.properties);
            }
            html += renderFlatProperties(a.properties);
        }

        $('#activity-detail-body').html(html);

        new bootstrap.Modal(document.getElementById('activityDetailModal')).show();
    };

    $(document).on('click', '#pagination-container .page-link', function (e) {
        e.preventDefault();
        var page = parseInt($(this).data('page'));
        if (page >= 1) { loadData(page); }
    });

    $('#applyFilters').on('click', function () { loadData(1); });
    $('#filterEvent, #filterLogName').on('change', function () { loadData(1); });
    $('#filterSearch').on('keypress', function (e) {
        if (e.which === 13) { loadData(1); }
    });

    loadData(1);
})();
</script>
@endpush
