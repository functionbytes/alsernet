@extends('layouts.theme')

@section('title', $pageTitle)

@section('content')

@include('core::components.card', ['title' => $pageTitle])

<div class="widget-content searchable-container list">
    @include('core::components.alerts')

    <div class="card">

        {{-- Cabecera --}}
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Horarios de sincronización</h5>
                    <p class="small mb-0 text-muted">Gestiona y habilita/deshabilita los horarios de ejecución automática</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
                        <i class="fas fa-plus me-1"></i> Nuevo horario
                    </button>
                </div>
            </div>
        </div>

        {{-- Stats (calculados desde JS tras cargar datos) --}}
        <div class="card-body border-bottom" id="stats-section">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Total</h6>
                            <h4 class="mb-1 fw-bold" id="stat-total">—</h4>
                            <small class="text-muted">Horarios configurados</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Habilitados</h6>
                            <h4 class="mb-1 fw-bold" id="stat-enabled">—</h4>
                            <small class="text-muted">Con ejecución activa</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Deshabilitados</h6>
                            <h4 class="mb-1 fw-bold" id="stat-disabled">—</h4>
                            <small class="text-muted">Pausados manualmente</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light-secondary stat-card h-100">
                        <div class="card-body">
                            <h6 class="card-title mb-2">Con errores</h6>
                            <h4 class="mb-1 fw-bold" id="stat-errors">—</h4>
                            <small class="text-muted">Último estado fallido</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Listado --}}
        <div class="card-body">
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1 fw-bold">Listado de horarios</h6>
                    <p class="text-muted small mb-0" id="schedules-count">Cargando...</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tipo</th>
                            <th>Etiqueta</th>
                            <th>Hora</th>
                            <th class="text-center">Activo</th>
                            <th class="text-center">Última ejecución</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="schedules-tbody">
                        <tr id="loading-row">
                            <td colspan="7" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                Cargando horarios...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="empty-state" class="text-center py-5 d-none">
                <i class="fas fa-clock fa-2x mb-3 d-block text-muted"></i>
                <h6 class="mb-1">No hay horarios configurados</h6>
                <p class="text-muted mb-3 small">Crea un horario para automatizar la sincronización</p>
                <button class="btn btn-sm btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus me-1"></i> Nuevo horario
                </button>
            </div>
        </div>

    </div>
</div>

{{-- Sincronización manual --}}
<div class="card mt-3">
    <div class="card-header p-4 border-bottom">
        <h5 class="mb-1 fw-bold">Sincronización manual</h5>
        <p class="small mb-0 text-muted">Ejecuta sincronizaciones desde ERP de forma inmediata, sin esperar al horario programado</p>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="border rounded p-3 d-flex align-items-center gap-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-truck fa-2x text-success"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0 fw-bold">Sincronizar proveedores</h6>
                        <small class="text-muted">Importa desde tabla PROVEEDOR en ERP Oracle</small>
                    </div>
                    <button class="btn btn-success btn-sm" id="btn-manual-providers">
                        <i class="fas fa-play me-1"></i> Ejecutar
                    </button>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-3 d-flex align-items-center gap-3">
                    <div class="flex-shrink-0">
                        <i class="fas fa-boxes fa-2x text-primary"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0 fw-bold">Sincronizar productos</h6>
                        <small class="text-muted">Importa desde tabla ARTIPROV en ERP Oracle</small>
                    </div>
                    <button class="btn btn-primary btn-sm" id="btn-manual-products">
                        <i class="fas fa-play me-1"></i> Ejecutar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal crear/editar --}}
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-labelledby="scheduleModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleModalTitle">Nuevo horario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="scheduleUid">

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="scheduleSyncType">Tipo de sincronización</label>
                    <select class="form-select select2-modal" id="scheduleSyncType">
                        <option value="model">Modelos</option>
                        <option value="product">Productos</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="scheduleLabel">Etiqueta</label>
                    <input type="text" class="form-control" id="scheduleLabel" placeholder="Ej: Modelos - mañana">
                </div>

                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label fw-semibold" for="scheduleHour">Hora (0–23)</label>
                        <input type="number" class="form-control" id="scheduleHour" min="0" max="23" value="8">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label fw-semibold" for="scheduleMinute">Minuto</label>
                        <select class="form-select select2-modal" id="scheduleMinute">
                            <option value="0">:00</option>
                            <option value="15">:15</option>
                            <option value="30">:30</option>
                            <option value="45">:45</option>
                        </select>
                    </div>
                </div>

                <div class="mb-0">
                    <label class="form-label fw-semibold" for="scheduleEnabled">Habilitado</label>
                    <select class="form-select select2-modal" id="scheduleEnabled">
                        <option value="1">Sí — ejecución activa</option>
                        <option value="0">No — pausado</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary w-100 mb-1" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary w-100" onclick="saveSchedule()">Guardar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal confirmación eliminación --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-trash-alt fa-2x text-danger"></i>
                </div>
                <h6 class="fw-semibold mb-1">¿Eliminar este horario?</h6>
                <p class="text-muted small mb-4">Esta acción no se puede deshacer.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteSchedule()">
                        <i class="fas fa-trash me-1"></i>Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const routes = @json($routes);
    const csrf   = $('meta[name="csrf-token"]').attr('content');
    let cachedSchedules  = [];
    let pollingInterval  = null;
    let deleteTargetUid  = null;

    // ── Helpers ───────────────────────────────────────────────────────────────

    function statusBadge(status, isEnabled) {
        if (!isEnabled) {
            return '<span class="badge bg-secondary-subtle text-secondary">Pausado</span>';
        }
        const map = {
            running: '<span class="badge bg-warning-subtle text-warning">Ejecutando</span>',
            success: '<span class="badge bg-success-subtle text-success">Completado</span>',
            error:   '<span class="badge bg-info-subtle text-info">Error</span>',
            failed:  '<span class="badge bg-info-subtle text-info">Error</span>',
            stuck:   '<span class="badge bg-info-subtle text-info">Colgado</span>',
        };
        return map[status] || '<span class="badge bg-secondary-subtle text-secondary">Sin ejecuciones</span>';
    }

    function typeBadge(syncType) {
        return syncType === 'model'
            ? '<span class="badge bg-primary-subtle text-primary">Modelos</span>'
            : '<span class="badge bg-info-subtle text-info">Productos</span>';
    }

    function formatDate(iso) {
        if (!iso) { return '<span class="text-muted">—</span>'; }
        return '<small class="text-muted">' + new Date(iso).toLocaleString('es-ES') + '</small>';
    }

    function toggleHtml(uid, isEnabled) {
        return `<div class="form-check form-switch d-flex justify-content-center mb-0">
                    <input class="form-check-input toggle-switch" type="checkbox" role="switch"
                           data-uid="${uid}" ${isEnabled ? 'checked' : ''} style="cursor:pointer;">
                </div>`;
    }

    // ── Renderizado ───────────────────────────────────────────────────────────

    function renderRow(s) {
        return `
            <tr data-uid="${s.uid}">
                <td>${typeBadge(s.sync_type)}</td>
                <td><strong>${s.label}</strong></td>
                <td><span class="badge bg-light-secondary text-dark">${s.formatted_time}</span></td>
                <td class="enabled-cell">${toggleHtml(s.uid, s.is_enabled)}</td>
                <td class="text-center lastrun-cell">${formatDate(s.last_run_at)}</td>
                <td class="text-center status-cell">${statusBadge(s.last_run_status, s.is_enabled)}</td>
                <td class="text-center">
                    <div class="dropdown">
                        <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-vertical"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="#" onclick="openEditModal('${s.uid}'); return false;">
                                   Editar
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="triggerSync('${s.uid}'); return false;">
                                  Ejecutar ahora
                                </a>
                            </li>
                            ${(s.last_run_status === 'running' || s.last_run_status === 'stuck') ? `
                            <li>
                                <a class="dropdown-item" href="#" onclick="resetStatus('${s.uid}'); return false;">
                                  Limpiar estado
                                </a>
                            </li>` : ''}
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item" onclick="confirmDelete('${s.uid}')">
                                    Eliminar
                                </button>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>`;
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    function updateStats(schedules) {
        const total    = schedules.length;
        const enabled  = schedules.filter(s => s.is_enabled).length;
        const disabled = total - enabled;
        const errors   = schedules.filter(s => s.last_run_status === 'error').length;

        $('#stat-total').text(total);
        $('#stat-enabled').text(enabled);
        $('#stat-disabled').text(disabled);
        $('#stat-errors').text(errors);
        $('#schedules-count').text(total + ' horario' + (total !== 1 ? 's' : '') + ' configurado' + (total !== 1 ? 's' : ''));
    }

    // ── Cargar tabla ──────────────────────────────────────────────────────────

    function loadSchedules() {
        $.get(routes.data, function (response) {
            cachedSchedules = response.data || [];
            const $tbody = $('#schedules-tbody');

            if (!cachedSchedules.length) {
                $tbody.html('');
                $('#empty-state').removeClass('d-none');
                $('#schedules-count').text('0 horarios configurados');
                updateStats([]);
                return;
            }

            $('#empty-state').addClass('d-none');
            $tbody.html(cachedSchedules.map(renderRow).join(''));
            updateStats(cachedSchedules);

            if (cachedSchedules.some(s => s.last_run_status === 'running')) {
                startPolling();
            }
        }).fail(function () {
            toastr.error('Error al cargar los horarios');
            $('#loading-row').html('<td colspan="7" class="text-center text-danger py-4">Error al cargar los horarios</td>');
        });
    }

    // ── Reset estado colgado ──────────────────────────────────────────────────

    window.resetStatus = function (uid) {
        $.ajax({
            url:     routes.resetStatus.replace(':uid', uid),
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            success: function (r) {
                const s = cachedSchedules.find(x => x.uid === uid);
                if (s) { s.last_run_status = null; }
                const $row = $('tr[data-uid="' + uid + '"]');
                $row.find('.status-cell').html(statusBadge(null, s ? s.is_enabled : true));
                // Re-render the row to remove the "Limpiar estado" option
                $row.replaceWith(renderRow(s));
                updateStats(cachedSchedules);
                toastr.success(r.message);
            },
            error: function () {
                toastr.error('Error al limpiar el estado');
            }
        });
    };

    // ── Toggle enable/disable ─────────────────────────────────────────────────

    $(document).on('change', '.toggle-switch', function () {
        const uid     = $(this).data('uid');
        const $switch = $(this);
        const enabled = $switch.prop('checked');

        $.ajax({
            url:     routes.toggle.replace(':uid', uid),
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            success: function (r) {
                const s = cachedSchedules.find(x => x.uid === uid);
                if (s) { s.is_enabled = r.is_enabled; }
                // Refresh status badge immediately to reflect enabled state
                const $row = $('tr[data-uid="' + uid + '"]');
                $row.find('.status-cell').html(statusBadge(s ? s.last_run_status : null, r.is_enabled));
                updateStats(cachedSchedules);
                toastr.success(r.is_enabled ? 'Horario habilitado' : 'Horario deshabilitado');
            },
            error: function () {
                toastr.error('Error al cambiar el estado');
                $switch.prop('checked', !enabled);
            }
        });
    });

    // ── Modal crear/editar ────────────────────────────────────────────────────

    $('#scheduleModal').on('shown.bs.modal', function () {
        $('.select2-modal').select2({
            dropdownParent: $('#scheduleModal'),
            minimumResultsForSearch: Infinity,
            width: '100%',
        });
    });

    function setModalValues(uid, syncType, label, hour, minute, isEnabled) {
        $('#scheduleUid').val(uid);
        $('#scheduleLabel').val(label);
        $('#scheduleHour').val(hour);
        $('#scheduleSyncType').val(syncType).trigger('change');
        $('#scheduleMinute').val(String(minute)).trigger('change');
        $('#scheduleEnabled').val(isEnabled ? '1' : '0').trigger('change');
    }

    window.openCreateModal = function () {
        $('#scheduleModalTitle').text('Nuevo horario');
        setModalValues('', 'model', '', 8, 0, true);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('scheduleModal')).show();
    };

    window.openEditModal = function (uid) {
        const s = cachedSchedules.find(x => x.uid === uid);
        if (!s) { return; }
        $('#scheduleModalTitle').text('Editar horario');
        setModalValues(s.uid, s.sync_type, s.label, s.hour, s.minute, s.is_enabled);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('scheduleModal')).show();
    };

    // ── Guardar ───────────────────────────────────────────────────────────────

    window.saveSchedule = function () {
        const uid    = $('#scheduleUid').val();
        const isEdit = uid !== '';
        const url    = isEdit ? routes.update.replace(':uid', uid) : routes.store;

        $.ajax({
            url:    url,
            method: isEdit ? 'PUT' : 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            data: {
                sync_type:  $('#scheduleSyncType').val(),
                label:      $('#scheduleLabel').val(),
                hour:       parseInt($('#scheduleHour').val()),
                minute:     parseInt($('#scheduleMinute').val()),
                is_enabled: parseInt($('#scheduleEnabled').val()),
            },
            success: function (r) {
                toastr.success(r.message);
                bootstrap.Modal.getInstance(document.getElementById('scheduleModal')).hide();
                loadSchedules();
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    toastr.error(Object.values(xhr.responseJSON.errors).flat().join('<br>'));
                } else {
                    toastr.error('Error al guardar el horario');
                }
            }
        });
    };

    // ── Eliminar ──────────────────────────────────────────────────────────────

    window.confirmDelete = function (uid) {
        deleteTargetUid = uid;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteModal')).show();
    };

    window.deleteSchedule = function () {
        $.ajax({
            url:     routes.destroy.replace(':uid', deleteTargetUid),
            method:  'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
            success: function (r) {
                toastr.success(r.message);
                bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
                loadSchedules();
            },
            error: function () {
                toastr.error('Error al eliminar el horario');
            }
        });
    };

    // ── Ejecutar ahora ────────────────────────────────────────────────────────

    window.triggerSync = function (uid) {
        $.ajax({
            url:     routes.trigger.replace(':uid', uid),
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            success: function (r) {
                toastr.info('Sincronización iniciada (batch #' + r.batch_id + ')');
                loadSchedules();
                startPolling();
            },
            error: function () {
                toastr.error('Error al iniciar la sincronización');
            }
        });
    };

    // ── Polling de estado ─────────────────────────────────────────────────────

    function updateRowStatus(uid, s) {
        const $row = $('tr[data-uid="' + uid + '"]');
        if (!$row.length) { return; }
        const cached = cachedSchedules.find(x => x.uid === uid);
        $row.find('.status-cell').html(statusBadge(s.last_run_status, cached ? cached.is_enabled : true));
        $row.find('.lastrun-cell').html(formatDate(s.last_run_at));
    }

    function startPolling() {
        if (pollingInterval) { return; }

        pollingInterval = setInterval(function () {
            $.get(routes.status, function (statuses) {
                let anyRunning = false;

                Object.entries(statuses).forEach(function ([uid, s]) {
                    updateRowStatus(uid, s);
                    const cached = cachedSchedules.find(x => x.uid === uid);
                    if (cached) {
                        cached.last_run_status = s.last_run_status;
                        cached.last_run_at     = s.last_run_at;
                    }
                    if (s.last_run_status === 'running') { anyRunning = true; }
                });

                updateStats(cachedSchedules);

                if (!anyRunning) {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                }
            });
        }, 10000);
    }

    document.addEventListener('visibilitychange', function () {
        if (document.hidden && pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    });

    // ── Init ──────────────────────────────────────────────────────────────────

    $(document).ready(function () {
        loadSchedules();
    });

}());

// ── Sincronización manual ─────────────────────────────────────────────────────
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    function triggerManualSync(url, label) {
        Swal.fire({
            title: '¿Sincronizar ' + label + '?',
            text: 'El proceso se ejecutará en segundo plano.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, ejecutar',
            cancelButtonText: 'Cancelar',
        }).then(function (result) {
            if (!result.isConfirmed) { return; }

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    toastr.success('Sincronización iniciada. Batch ID: ' + data.batch_id, label);
                } else {
                    toastr.error(data.message || 'Error al iniciar', 'Error');
                }
            })
            .catch(function (e) {
                toastr.error('Error: ' + e.message, 'Error');
            });
        });
    }

    document.getElementById('btn-manual-providers')?.addEventListener('click', function () {
        triggerManualSync('{{ route("settings.suppliers.sync.sync-providers") }}', 'Proveedores');
    });

    document.getElementById('btn-manual-products')?.addEventListener('click', function () {
        triggerManualSync('{{ route("settings.suppliers.sync.sync-products") }}', 'Productos');
    });
}());
</script>
@endpush
