@extends('layouts.theme')

@section('title', 'Importar desde ERP')

@section('page_header')
    @include('core::components.card', ['title' => 'Importar desde ERP'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">

        <div class="card w-100">
            <div class="card-header border-bottom py-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-0">Importar proveedores desde ERP</h5>
                        <p class="small mb-0 text-muted mt-1">
                            Sincroniza proveedores del ERP con la base de datos local
                        </p>
                    </div>
                    <span class="badge bg-primary">ERP</span>
                </div>
            </div>

            <div class="card-body">

                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle me-1"></i>
                    Ingresa los IDs del ERP que deseas importar. Los existentes se actualizarán con los datos más recientes.
                    Los nuevos se crearán automáticamente.
                </div>

                <!-- Importación por ID específico -->
                <div class="form-group mb-3">
                    <label for="erp_id_input" class="form-label fw-bold">IDs de proveedores a importar</label>
                    <div class="input-group">
                        <input type="text"
                               id="erp_id_input"
                               class="form-control"
                               placeholder="Ej: 1050 o varios: 1050, 2030, 3001">
                        <button type="button" class="btn btn-primary" id="add-ids-btn">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <small class="text-muted">Escribe uno o varios IDs separados por coma y haz clic en "Agregar" o presiona Enter</small>
                </div>

                <!-- Lista de IDs agregados -->
                <div class="form-group mb-4" id="ids-list-container" style="display: none;">
                    <label class="form-label">IDs agregados</label>
                    <div id="ids-list" class="border rounded p-3"></div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary w-100 mb-2" id="import-btn" disabled>
                           Importar
                        </button>
                        <a href="{{ route('settings.suppliers.import.index') }}" class="btn btn-secondary w-100">
                            Cancelar
                        </a>
                    </div>
                </div>

                <hr class="mt-4">

                <!-- Importación masiva -->
                <div class="row">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">Importar todos los proveedores</label>
                        <ul class="list-unstyled small mb-3">
                            <li class="mb-1 text-muted"><i class="fas fa-check-circle me-2" style="color:#90bb13;"></i>Obtiene el listado completo de proveedores registrados en el ERP.</li>
                            <li class="mb-1 text-muted"><i class="fas fa-check-circle me-2" style="color:#90bb13;"></i>Los proveedores nuevos se crean automáticamente en el sistema.</li>
                            <li class="mb-1 text-muted"><i class="fas fa-check-circle me-2" style="color:#90bb13;"></i>Los proveedores existentes se actualizan con la información más reciente del ERP.</li>
                            <li class="mb-1 text-muted"><i class="fas fa-check-circle me-2" style="color:#90bb13;"></i>El proceso se ejecuta en segundo plano y puede tardar varios minutos según el volumen de datos.</li>
                        </ul>
                        <button type="button" class="btn btn-secondary w-100 mt-2" id="sync-all-btn">
                            Sincronizar todos
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- widget-content --}}

    <!-- ── Modal de confirmación ─────────────────────────────────────── -->
    <div id="import-confirm-modal" class="modal fade">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar importación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body text-center">
                    <h5 class="my-3">¿Deseas importar estos proveedores?</h5>
                    <p id="import-count-text" class="text-muted"></p>
                    <div class="row justify-content-center mt-4">
                        <div class="col-sm-12 col-md-5 mb-2">
                            <button type="button" id="confirm-import-btn" class="btn btn-primary w-100">Confirmar</button>
                        </div>
                        <div class="col-sm-12 col-md-5">
                            <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Modal de reporte con progreso ─────────────────────────────── -->
    <div id="report-modal" class="modal fade" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title" id="report-modal-title">Importando proveedores...</h5>
                    <button type="button" class="btn-close" id="report-modal-close" style="display:none;" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">

                    <!-- Barra de progreso -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold" id="import-progress-label">Procesando 0 de 0</span>
                            <span class="fw-bold text-primary" id="import-progress-percent">0%</span>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                 id="import-progress-bar"
                                 role="progressbar"
                                 style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="row text-center g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="border rounded bg-light p-3">
                                <h2 class="mb-1 fw-bold" id="r-total">0</h2>
                                <p class="mb-0 text-muted small">Total</p>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border rounded bg-light p-3">
                                <h2 class="mb-1 fw-bold " id="r-created">0</h2>
                                <p class="mb-0 text-muted small">Creados</p>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border rounded bg-light p-3">
                                <h2 class="mb-1 fw-bold text-primary" id="r-updated">0</h2>
                                <p class="mb-0 text-muted small">Actualizados</p>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="border rounded bg-light p-3">
                                <h2 class="mb-1 fw-bold text-danger" id="r-errors">0</h2>
                                <p class="mb-0 text-muted small">Errores</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de detalle -->
                    <h6 class="mb-3">Detalle</h6>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:80px;">ID ERP</th>
                                    <th>Proveedor</th>
                                    <th>Descripción</th>
                                    <th class="text-end" style="width:110px;">Estado</th>
                                </tr>
                            </thead>
                            <tbody id="report-tbody"></tbody>
                        </table>
                    </div>

                </div>
                <div class="modal-footer border-top justify-content-center">
                    <a href="{{ route('settings.suppliers.index') }}" class="btn btn-primary w-100">
                        Ver proveedores
                    </a>
                    <button type="button" class="btn btn-secondary px-4 w-100" id="import-again-btn">
                        Importar más
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Modal de progreso para sincronización masiva ───────────────── -->
    <div class="modal fade" id="bulkSyncModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sincronizando proveedores...</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span id="bulk-sync-status">Iniciando sincronización...</span>
                            <span class="fw-bold" id="bulk-sync-percent">0%</span>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                 role="progressbar"
                                 id="bulk-sync-bar"
                                 style="width: 0%"></div>
                        </div>
                    </div>
                    <div id="bulk-sync-details" class="small text-muted"></div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('settings.suppliers.sync.index') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-history me-1"></i> Ver historial
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" disabled id="btn-close-bulk-modal">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    const $input      = $('#erp_id_input');
    const $addBtn     = $('#add-ids-btn');
    const $container  = $('#ids-list-container');
    const $list       = $('#ids-list');
    const $importBtn  = $('#import-btn');
    let selectedIds   = [];

    // ── Agregar IDs ────────────────────────────────────────────────────
    $addBtn.on('click', addIds);
    $input.on('keypress', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); addIds(); }
    });

    function addIds() {
        const raw = $input.val().trim();
        if (!raw) { return; }

        const ids = raw.split(',')
            .map(s => parseInt(s.trim()))
            .filter(n => n > 0)
            .map(String);

        if (ids.length === 0) {
            toastr.warning('No se encontraron IDs válidos (solo números positivos)', 'Atención', {
                closeButton: true, progressBar: true, positionClass: 'toast-bottom-right'
            });
            return;
        }

        ids.forEach(id => { if (!selectedIds.includes(id)) { selectedIds.push(id); } });
        $input.val('');
        renderIdsList();
    }

    function renderIdsList() {
        if (selectedIds.length === 0) {
            $container.hide();
            $importBtn.prop('disabled', true);
            return;
        }

        $list.html(selectedIds.map(id => `
            <span class="badge bg-primary me-2 mb-1" style="font-size:.9rem;">
                ${id}
                <button type="button" class="btn-close btn-close-white ms-1 remove-id-btn"
                        data-id="${id}" style="font-size:.65rem;"></button>
            </span>`).join(''));

        $container.show();
        $importBtn.prop('disabled', false);
    }

    $(document).on('click', '.remove-id-btn', function () {
        const id = $(this).data('id').toString();
        selectedIds = selectedIds.filter(i => i !== id);
        renderIdsList();
    });

    // ── Botón importar → modal confirmación ────────────────────────────
    $importBtn.on('click', function () {
        if (selectedIds.length === 0) { return; }
        $('#import-count-text').text(`Se importarán ${selectedIds.length} proveedor(es)`);
        $('#import-confirm-modal').modal('show');
    });

    // ── Confirmar → importar secuencialmente con progreso ──────────────
    $(document).on('click', '#confirm-import-btn', function () {
        $('#import-confirm-modal').modal('hide');
        $importBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Importando...');
        runImport([...selectedIds]);
    });

    function runImport(ids) {
        const total = ids.length;
        let created = 0, updated = 0, errors = 0;

        $('#report-tbody').empty();
        $('#r-total').text('0');
        $('#r-created').text('0');
        $('#r-updated').text('0');
        $('#r-errors').text('0');
        $('#import-progress-bar').css('width', '0%').removeClass('bg-danger bg-success bg-warning').addClass('bg-primary progress-bar-animated');
        $('#import-progress-percent').text('0%');
        $('#import-progress-label').text(`Procesando 0 de ${total}`);
        $('#report-modal').modal('show');

        const importNext = (index) => {
            if (index >= ids.length) {
                $('#r-total').text(total);
                $('#r-created').text(created);
                $('#r-updated').text(updated);
                $('#r-errors').text(errors);
                $importBtn.prop('disabled', false).html('<i class="fas fa-download me-1"></i> Importar');
                $('#import-progress-bar').removeClass('progress-bar-animated');
                const isOk = errors === 0;
                $('#import-progress-bar').removeClass('bg-primary').addClass(isOk ? 'bg-success' : 'bg-warning');
                $('#import-progress-label').text(isOk ? 'Completado' : `Completado con ${errors} error(es)`);
                $('#import-progress-percent').text('100%');
                return;
            }

            const erpId = ids[index];
            const percent = Math.round(((index + 1) / total) * 100);
            $('#import-progress-bar').css('width', percent + '%');
            $('#import-progress-percent').text(percent + '%');
            $('#import-progress-label').text(`Procesando ${index + 1} de ${total}`);

            $.ajax({
                url: '{{ route("settings.suppliers.import.sync") }}',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: { erp_id: erpId },
                success: function (res) {
                    if (res.success) {
                        res.created ? created++ : updated++;
                        const badge       = res.created ? 'bg-success' : 'bg-primary';
                        const label       = res.created ? 'Creado'     : 'Actualizado';
                        const code        = res.supplier.code ? `<code class="small text-muted ms-1">${res.supplier.code}</code>` : '';
                        const available   = res.supplier.available
                            ? '<span class="badge bg-success-subtle text-success border border-success ms-1">Activo</span>'
                            : '<span class="badge bg-secondary-subtle text-secondary border border-secondary ms-1">Inactivo</span>';
                        const description = res.created
                            ? 'Proveedor registrado por primera vez desde el ERP'
                            : 'Información del proveedor actualizada con los datos del ERP';
                        $('#report-tbody').prepend(`
                            <tr>
                                <td><strong>${erpId}</strong></td>
                                <td>${res.supplier.label}${code}${available}</td>
                                <td class="text-muted small">${description}</td>
                                <td class="text-end"><span class="badge ${badge}">${label}</span></td>
                            </tr>`);
                    } else {
                        errors++;
                        $('#report-tbody').prepend(`
                            <tr>
                                <td><strong>${erpId}</strong></td>
                                <td class="text-muted">—</td>
                                <td class="text-danger small">${res.message}</td>
                                <td class="text-end"><span class="badge bg-danger">Error</span></td>
                            </tr>`);
                    }
                },
                error: function (xhr) {
                    errors++;
                    const msg = xhr.responseJSON?.message || 'Error desconocido al comunicarse con el ERP';
                    $('#report-tbody').prepend(`
                        <tr>
                            <td><strong>${erpId}</strong></td>
                            <td class="text-muted">—</td>
                            <td class="text-danger small">${msg}</td>
                            <td class="text-end"><span class="badge bg-danger">Error</span></td>
                        </tr>`);
                },
                complete: function () {
                    $('#r-total').text(index + 1);
                    $('#r-created').text(created);
                    $('#r-updated').text(updated);
                    $('#r-errors').text(errors);
                    importNext(index + 1);
                }
            });
        };

        importNext(0);
    }

    // ── "Importar más" cierra el reporte y resetea ─────────────────────
    $('#import-again-btn').on('click', function () {
        $('#report-modal').modal('hide');
        selectedIds = [];
        renderIdsList();
        $importBtn.html('<i class="fas fa-download me-1"></i> Importar');
        // Limpiar alerta de errores si existe
        $('#report-modal .alert-danger').remove();
        $('#report-modal-title').text('Importando proveedores...');
        $('#report-modal-close').hide();
        $('#import-progress-percent').removeClass('text-success text-danger').addClass('text-primary');
    });

    // Botón X para cerrar el modal al finalizar
    $('#report-modal-close').on('click', function () {
        $('#report-modal').modal('hide');
    });

    // ── Sincronizar todos ──────────────────────────────────────────────
    $('#sync-all-btn').on('click', function () {
        if (!confirm('¿Sincronizar todos los proveedores del ERP? Esto puede tardar unos minutos.')) {
            return;
        }

        const $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Sincronizando...');
        const modal = new bootstrap.Modal(document.getElementById('bulkSyncModal'));

        $('#bulk-sync-status').text('Iniciando sincronización...');
        $('#bulk-sync-bar').css('width', '0%').addClass('progress-bar-animated bg-primary').removeClass('bg-danger bg-success');
        $('#bulk-sync-percent').text('0%');
        $('#bulk-sync-details').html('');
        $('#btn-close-bulk-modal').prop('disabled', true);
        modal.show();

        $.ajax({
            url: '{{ route("settings.suppliers.import.sync-erp") }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                if (res.success) {
                    monitorBulkProgress(res.batch_id);
                } else {
                    showBulkError(res.message || 'Error al iniciar');
                }
            },
            error: function (xhr) {
                showBulkError(xhr.responseJSON?.message || 'Error al sincronizar');
            }
        });

        function monitorBulkProgress(batchId) {
            const interval = setInterval(function () {
                $.ajax({
                    url: '{{ route("settings.suppliers.sync.progress", ":batchId") }}'.replace(':batchId', batchId),
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    success: function (response) {
                        if (!response.success) return;
                        const batch = response.batch;
                        const progress = batch.progress_percentage || 0;

                        $('#bulk-sync-bar').css('width', progress + '%');
                        $('#bulk-sync-percent').text(Math.round(progress) + '%');

                        let details = `Procesados: ${batch.processed_items}`;
                        if (batch.total_items > 0) {
                            details += ` / ${batch.total_items}`;
                        }
                        if (batch.failed_items > 0) {
                            details += ` | Fallidos: ${batch.failed_items}`;
                        }
                        $('#bulk-sync-details').html(details);

                        if (batch.status === 'completed') {
                            clearInterval(interval);
                            $('#bulk-sync-status').text('¡Completado!');
                            $('#bulk-sync-bar').removeClass('progress-bar-animated').addClass('bg-success');
                            $('#btn-close-bulk-modal').prop('disabled', false);
                        } else if (batch.status === 'failed') {
                            clearInterval(interval);
                            $('#bulk-sync-status').text('La sincronización falló');
                            $('#bulk-sync-bar').removeClass('progress-bar-animated').addClass('bg-danger');
                            $('#btn-close-bulk-modal').prop('disabled', false);
                        }
                    },
                    error: function () {
                        clearInterval(interval);
                        showBulkError('Error al obtener progreso');
                    }
                });
            }, 3000);
        }

        function showBulkError(message) {
            $('#bulk-sync-status').text('Error');
            $('#bulk-sync-bar').removeClass('progress-bar-animated').addClass('bg-danger');
            $('#bulk-sync-details').html(`<span class="text-danger">${message}</span>`);
            $('#btn-close-bulk-modal').prop('disabled', false);
        }

        $('#bulkSyncModal').on('hidden.bs.modal', function () {
            $btn.prop('disabled', false).html('<i class="fas fa-sync-alt me-1"></i> Sincronizar todos');
        });
    });

});
</script>
@endpush
