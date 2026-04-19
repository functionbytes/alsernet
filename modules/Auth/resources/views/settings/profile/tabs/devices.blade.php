<div class="row g-4">
    <div class="col-12">
        <div class="mb-4">
            <h6 class="mb-1 fw-bold">Dispositivos conectados</h6>
            <p class="text-muted mb-0">Gestiona los dispositivos desde los que has accedido a tu cuenta</p>
        </div>

        <div class="card border">
            <div class="card-body p-4">

                {{-- Device list (populated via AJAX) --}}
                <div id="devices-loading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                </div>

                <div id="devices-list" class="d-none"></div>

                {{-- Info Alert --}}
                <div class="alert alert-info border-0 mt-4" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-2">Control de dispositivos</h6>
                            <p class="mb-0 small">
                                Revisa regularmente los dispositivos que han accedido a tu cuenta.
                                Marca como "de confianza" los que reconozcas; si encuentras alguno desconocido,
                                revoca la confianza y cambia tu contraseña inmediatamente.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Etiquetar dispositivo --}}
<div class="modal fade" id="trustDeviceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Marcar como de confianza</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Dale un nombre a este dispositivo para reconocerlo fácilmente.</p>
                <form id="formTrustDevice" onsubmit="return false">
                    @csrf
                    <input type="hidden" name="device_id">
                    <div class="mb-3">
                        <label for="device_label" class="form-label">Nombre del dispositivo</label>
                        <input type="text" class="form-control" id="device_label" name="label"
                               placeholder="Ej. Mi portátil, iPhone de casa..." maxlength="100">
                    </div>
                </form>
            </div>
            <div class="modal-footer d-block">
                <button type="button" class="btn btn-primary w-100 mb-2" id="btnTrustConfirm">
                    <i class="fas fa-shield-check me-1"></i> Confirmar confianza
                </button>
                <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {

    function platformIcon(platform, deviceType) {
        if (deviceType === 'mobile') { return '<i class="fas fa-mobile-screen text-primary fs-4"></i>'; }
        if (deviceType === 'tablet') { return '<i class="fas fa-tablet text-primary fs-4"></i>'; }
        return '<i class="fas fa-desktop text-primary fs-4"></i>';
    }

    function formatDate(iso) {
        if (!iso) { return '—'; }
        try {
            return new Date(iso).toLocaleString('es-ES', {
                day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        } catch (e) { return iso; }
    }

    function renderDevices(devices) {
        const $list = $('#devices-list');
        $list.empty();

        if (!devices.length) {
            $list.append('<p class="text-muted text-center py-4">No hay dispositivos registrados aún.</p>');
            return;
        }

        devices.forEach(function (d) {
            const statusBadge = d.is_current
                ? '<span class="badge bg-success">Dispositivo actual</span>'
                : (d.trusted
                    ? '<span class="badge bg-info">De confianza</span>'
                    : '<span class="badge bg-secondary">No verificado</span>');

            const label = d.label || (d.browser || 'Navegador desconocido') + ' en ' + (d.platform || 'plataforma desconocida');

            const actions = [
                '<div class="dropdown">',
                '<button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">',
                '<i class="fas fa-ellipsis-vertical"></i>',
                '</button>',
                '<ul class="dropdown-menu dropdown-menu-end">',
                (d.trusted
                    ? '<li><a class="dropdown-item btn-device-revoke" href="#" data-id="' + d.id + '">Revocar confianza</a></li>'
                    : '<li><a class="dropdown-item btn-device-trust" href="#" data-id="' + d.id + '">Marcar como de confianza</a></li>'),
                (d.is_current
                    ? ''
                    : '<li><a class="dropdown-item btn-device-delete" href="#" data-id="' + d.id + '">Eliminar</a></li>'),
                '</ul>',
                '</div>'
            ].join('');

            $list.append(
                '<div class="d-flex align-items-start justify-content-between py-3 border-bottom device-item" data-id="' + d.id + '">' +
                '<div class="d-flex align-items-start gap-3 flex-grow-1">' +
                '<div class="bg-light-primary rounded-1 p-3 d-flex align-items-center justify-content-center">' +
                platformIcon(d.platform, d.device_type) +
                '</div>' +
                '<div class="flex-grow-1">' +
                '<div class="d-flex align-items-center gap-2 mb-1 flex-wrap">' +
                '<h6 class="mb-0">' + $('<div>').text(label).html() + '</h6>' +
                statusBadge +
                '</div>' +
                '<small class="text-muted d-block"><i class="fas fa-globe me-1"></i><strong>IP:</strong> ' + (d.ip_address || 'Desconocida') + '</small>' +
                '<small class="text-muted d-block mt-1"><i class="fas fa-clock me-1"></i>Última actividad: ' + formatDate(d.last_seen_at) + '</small>' +
                '<small class="text-muted d-block mt-1"><i class="fas fa-calendar me-1"></i>Primera vez: ' + formatDate(d.first_seen_at) + '</small>' +
                '</div>' +
                '</div>' +
                '<div class="ms-2">' + actions + '</div>' +
                '</div>'
            );
        });
    }

    function loadDevices() {
        $('#devices-loading').removeClass('d-none');
        $('#devices-list').addClass('d-none');

        $.get("{{ route('settings.auth.devices.list') }}", function (response) {
            $('#devices-loading').addClass('d-none');
            $('#devices-list').removeClass('d-none');
            renderDevices(response.devices || []);
        }).fail(function () {
            $('#devices-loading').addClass('d-none');
            $('#devices-list').removeClass('d-none').html('<p class="text-danger">Error al cargar dispositivos.</p>');
        });
    }

    // Open trust modal
    $(document).on('click', '.btn-device-trust', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        $('#formTrustDevice [name="device_id"]').val(id);
        $('#device_label').val('');
        new bootstrap.Modal(document.getElementById('trustDeviceModal')).show();
    });

    // Confirm trust
    $('#btnTrustConfirm').on('click', function () {
        const id = $('#formTrustDevice [name="device_id"]').val();
        const label = $('#device_label').val();

        $.ajax({
            url: "{{ url('panel/setting/auth/devices') }}/" + id + '/trust',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { label: label },
            success: function (response) {
                toastr.success(response.message);
                bootstrap.Modal.getInstance(document.getElementById('trustDeviceModal')).hide();
                loadDevices();
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al marcar dispositivo.');
            }
        });
    });

    // Revoke trust
    $(document).on('click', '.btn-device-revoke', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        if (!confirm('¿Revocar la confianza de este dispositivo?')) { return; }

        $.ajax({
            url: "{{ url('panel/setting/auth/devices') }}/" + id + '/revoke',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                toastr.success(response.message);
                loadDevices();
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al revocar.');
            }
        });
    });

    // Delete device
    $(document).on('click', '.btn-device-delete', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        if (!confirm('¿Eliminar este dispositivo del registro?')) { return; }

        $.ajax({
            url: "{{ url('panel/setting/auth/devices') }}/" + id,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-HTTP-Method-Override': 'DELETE'
            },
            data: { _method: 'DELETE' },
            success: function (response) {
                toastr.success(response.message);
                loadDevices();
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al eliminar.');
            }
        });
    });

    // Load on tab shown
    const devicesTabEl = document.getElementById('devices-tab');
    if (devicesTabEl) {
        devicesTabEl.addEventListener('shown.bs.tab', loadDevices);
    }

    @if($activeTab === 'devices')
    loadDevices();
    @endif
});
</script>
@endpush
