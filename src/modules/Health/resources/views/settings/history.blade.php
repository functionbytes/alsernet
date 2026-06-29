@extends('layouts.theme')

@section('title', 'Historial de verificaciones')

@section('page_header')
    @include('core::components.card', ['title' => 'Historial de verificaciones'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Historial de verificaciones</h5>
                        <p class="small mb-0 text-muted">Registro histórico de las verificaciones de salud del sistema</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" onclick="loadHistory()">
                            <i class="fas fa-sync me-1"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total registros</h6>
                                <h4 class="mb-1 fw-bold" id="totalRecords">—</h4>
                                <small class="text-muted">Verificaciones en el período</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Exitosos</h6>
                                <h4 class="mb-1 fw-bold text-success" id="successRecords">—</h4>
                                <small class="text-muted">Estado OK</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Advertencias</h6>
                                <h4 class="mb-1 fw-bold text-warning" id="warningRecords">—</h4>
                                <small class="text-muted">Requieren atención</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Errores</h6>
                                <h4 class="mb-1 fw-bold text-danger" id="errorRecords">—</h4>
                                <small class="text-muted">Críticos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filtro + estado actual --}}
            <div class="card-body border-bottom">
                <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                    <div class="flex-shrink-0" style="min-width: 200px;">
                        <select class="form-select select2 h-100" id="periodFilter">
                            <option value="1">Último día</option>
                            <option value="7" selected>Últimos 7 días</option>
                            <option value="30">Últimos 30 días</option>
                            <option value="90">Últimos 90 días</option>
                        </select>
                    </div>
                    <div class="flex-fill">
                        <div class="input-group h-100">
                            <span class="input-group-text bg-white border-end-1">
                                <i class="fas fa-circle-dot text-muted"></i>
                            </span>
                            <div class="form-control -0 d-flex align-items-center gap-2 flex-wrap" id="currentStatusBadges" style="height:auto; min-height:38px;">
                                <span class="text-muted small"><i class="fas fa-spinner fa-spin me-1"></i> Cargando estado actual...</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <button type="button" class="btn btn-primary" onclick="loadHistory()" id="refreshBtn">
                            <i class="fas fa-sync"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tabla --}}
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                <th width="22%">Verificación</th>
                                <th width="15%">Estado</th>
                                <th width="32%">Resumen</th>
                                <th width="20%">Fecha</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-spinner fa-spin me-2"></i>Cargando historial...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

    {{-- Bulk toolbar flotante --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> registro(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

    $('#periodFilter').select2({ minimumResultsForSearch: Infinity });
    $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

    loadHistory();
    loadCurrentStatus();
    $('#periodFilter').on('change', loadHistory);

    // Bulk modal reset on close
    $('#bulk-modal').on('hide.bs.modal', function () {
        $('#bulk-action-select').val('').trigger('change');
        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
        bulk.reset();
    });

    // Bulk apply
    $('#bulk-apply-btn').on('click', function () {
        const action = $('#bulk-action-select').val();
        const ids    = bulk.getIds();

        if (!action) { toastr.warning('Selecciona una acción.'); return; }
        if (!ids.length) { toastr.warning('Selecciona al menos un registro.'); return; }
        if (action === 'delete' && !confirm('¿Eliminar los ' + ids.length + ' registro(s) seleccionados?')) { return; }

        $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

        $.ajax({
            url: '{{ route('settings.health.history.bulk') }}',
            method: 'POST',
            data: JSON.stringify({ action, ids, _token: $('meta[name="csrf-token"]').attr('content') }),
            contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#bulk-modal').modal('hide');
                toastr.success(res.count + ' registro(s) eliminados.');
                loadHistory();
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
            },
        });
    });

    // Per-row delete
    $(document).on('click', '.delete-record-btn', function () {
        const id = $(this).data('id');
        const checkName = $(this).data('check-name');
        $('#delete-modal .modal-title').text('Eliminar: ' + checkName);
        $('#delete-form').attr('action', '{{ url('panel/setting/health/history') }}/' + id);
    });
});

function loadHistory() {
    $.ajax({
        url: '{{ route('settings.health.history') }}',
        method: 'GET',
        data: { days: $('#periodFilter').val() },
        headers: { 'Accept': 'application/json' },
        dataType: 'json',
        success: function (response) {
            updateStats(response.history);
            renderTable(response.history);
        },
        error: function () {
            toastr.error('Error al cargar el historial');
            $('#historyTableBody').html('<tr><td colspan="6" class="text-center text-muted py-4">Error al cargar el historial</td></tr>');
        }
    });
}

function updateStats(history) {
    let total = 0, success = 0, warning = 0, error = 0;

    $.each(history, function (checkName, items) {
        $.each(items, function (index, item) {
            total++;
            if (item.status === 'ok') { success++; }
            else if (item.status === 'warning') { warning++; }
            else { error++; }
        });
    });

    $('#totalRecords').text(total);
    $('#successRecords').text(success);
    $('#warningRecords').text(warning);
    $('#errorRecords').text(error);
}

function renderTable(history) {
    const $tbody = $('#historyTableBody');

    if (Object.keys(history).length === 0) {
        $tbody.html(`<tr><td colspan="6">
            <div class="text-center py-5">
                <div class="d-flex flex-column align-items-center">
                    <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                        <i class="fas fa-history fs-7"></i>
                    </div>
                    <h6 class="mb-1">No hay registros</h6>
                    <p class="text-muted mb-0">No hay verificaciones en el período seleccionado</p>
                </div>
            </div>
        </td></tr>`);
        return;
    }

    let html = '';
    $.each(history, function (checkName, items) {
        $.each(items, function (index, item) {
            const date = new Date(item.created_at);
            const formatted = date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES');
            const displayName = formatCheckName(checkName);
            html += `<tr>
                <td><input type="checkbox" class="form-check-input bulk-checkbox" value="${item.id}"></td>
                <td class="fw-semibold">${displayName}</td>
                <td>${statusBadge(item.status)}</td>
                <td><small class="text-muted">${item.short_summary || '-'}</small></td>
                <td><small class="text-muted">${formatted}</small></td>
                <td class="text-center">
                    <div class="dropdown">
                        <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-vertical"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <button type="button" class="dropdown-item delete-record-btn"
                                        data-bs-toggle="modal" data-bs-target="#delete-modal"
                                        data-id="${item.id}"
                                        data-check-name="${displayName}">
                                    Eliminar
                                </button>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>`;
        });
    });

    $tbody.html(html);

    // Re-sync bulk after dynamic rows injected
    window.BulkActions.init({ checkbox: '.bulk-checkbox' });
}

function loadCurrentStatus() {
    $.ajax({
        url: '{{ route('settings.health.check') }}',
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        dataType: 'json',
        success: function (response) {
            renderCurrentStatus(response.checks);
        },
        error: function () {
            $('#currentStatusBadges').html('<span class="small">Error al cargar estado actual</span>');
        }
    });
}

function renderCurrentStatus(checks) {
    if (!checks || checks.length === 0) {
        $('#currentStatusBadges').html('<span class="text-muted">Sin verificaciones registradas</span>');
        return;
    }

    const colorMap = { ok: 'success', warning: 'warning', failed: 'danger' };

    let html = '';
    $.each(checks, function (i, check) {
        const color = colorMap[check.status] ?? 'secondary';
        const label = check.label || formatCheckName(check.name);
        const title = check.short_summary ? ` title="${check.short_summary}"` : '';
        html += `<span class="badge bg-${color}-subtle text-${color} px-3 py-2"${title}>
            <i class="fas fa-circle me-1" style="font-size:0.5rem; vertical-align:middle;"></i>${label}
        </span>`;
    });

    $('#currentStatusBadges').html(html);
}

function statusBadge(status) {
    const map = {
        ok:      '<span class="badge bg-success-subtle text-success">OK</span>',
        warning: '<span class="badge bg-warning-subtle text-warning">Advertencia</span>',
        failed:  '<span class="badge bg-danger-subtle text-danger">Error</span>',
    };
    return map[status] ?? `<span class="badge bg-secondary-subtle text-secondary">${status}</span>`;
}

function formatCheckName(name) {
    return name.replace(/Check$/, '').replace(/([A-Z])/g, ' $1').trim();
}
</script>
@endpush
