<div class="card w-100 mb-4" id="sync-status-widget">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h4 class="card-title fw-semibold mb-0">Estado de sincronización</h4>
                <p class="card-subtitle mt-1">Conexiones y estado de sync con Google</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span id="sync-status-badge" class="badge rounded-pill">
                    <i class="fas fa-circle me-1"></i> Cargando...
                </span>
                <a href="javascript:void(0)" id="refresh-sync-status"
                   class="d-flex align-items-center justify-content-center rounded-circle text-muted"
                   style="width:30px;height:30px;background:#f5f6f8;" title="Actualizar">
                    <i class="fas fa-redo" style="font-size:0.75rem;"></i>
                </a>
            </div>
        </div>
        <div id="sync-status-content">
            <div class="text-center text-muted py-3">
                <i class="fas fa-spinner fa-spin me-2"></i> Cargando estado de sincronizacion...
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    const syncStatusUrl = '{{ route("reviews.sync-status.index") }}';
    const syncNowBaseUrl = '{{ route("reviews.sync-status.sync-now", ["connection" => "__ID__"]) }}';
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    function loadSyncStatus() {
        $.get(syncStatusUrl)
            .done(function (response) {
                if (!response.success) {
                    showError('No se pudo obtener el estado de sincronizacion.');
                    return;
                }
                renderConnections(response.data, response.sync_interval_minutes);
            })
            .fail(function () {
                showError('Error al cargar el estado de sincronizacion.');
            });
    }

    function renderConnections(connections, intervalMinutes) {
        const container = $('#sync-status-content');

        if (!connections || connections.length === 0) {
            container.html(
                '<div class="text-center text-muted py-3">' +
                '<i class="fas fa-plug fa-2x mb-2 d-block opacity-50"></i>' +
                '<p class="mb-0">No hay conexiones de Google configuradas.</p>' +
                '</div>'
            );
            updateBadge('secondary', 'Sin conexiones');
            return;
        }

        let hasExpired = connections.some(c => c.status !== 'active');
        updateBadge(
            hasExpired ? 'warning' : 'success',
            hasExpired ? 'Conexion expirada' : 'Activo'
        );

        let html = '<div class="row g-3">';
        connections.forEach(function (conn) {
            html += buildConnectionCard(conn, intervalMinutes);
        });
        html += '</div>';

        container.html(html);
    }

    function buildConnectionCard(conn, intervalMinutes) {
        const statusBadge = conn.is_active
            ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Activo</span>'
            : '<span class="badge bg-' + escapeHtml(conn.status_color) + '"><i class="fas fa-exclamation-circle me-1"></i>' + escapeHtml(conn.status) + '</span>';

        const lastSyncHtml = conn.last_sync_at
            ? '<small class="text-muted"><i class="fas fa-clock me-1"></i>Ultimo sync: ' + escapeHtml(formatDateTime(conn.last_sync_at)) + '</small>'
            : '<small class="text-muted"><i class="fas fa-minus-circle me-1"></i>Sin sincronizacion</small>';

        const nextSyncHtml = conn.next_sync_estimate
            ? '<small class="text-muted"><i class="fas fa-calendar-check me-1"></i>Proximo: ' + escapeHtml(formatDateTime(conn.next_sync_estimate)) + '</small>'
            : '<small class="text-muted"><i class="fas fa-info-circle me-1"></i>Intervalo: ' + intervalMinutes + ' min</small>';

        const syncBtn = conn.is_active
            ? '<button class="btn btn-sm btn-outline-primary sync-now-btn mt-2" data-connection-id="' + conn.id + '">' +
              '<i class="fas fa-sync-alt me-1"></i>Sincronizar ahora</button>'
            : '<button class="btn btn-sm btn-outline-secondary mt-2" disabled title="Conexion no activa">' +
              '<i class="fas fa-sync-alt me-1"></i>Sincronizar ahora</button>';

        const locCount = conn.active_locations_count || 0;
        const locText = locCount === 1 ? '1 ubicacion activa' : locCount + ' ubicaciones activas';

        return '<div class="col-md-6 col-lg-4">' +
            '<div class="border rounded p-3 h-100 d-flex flex-column">' +
            '<div class="d-flex justify-content-between align-items-start mb-2">' +
            '<div class="fw-semibold text-truncate me-2" title="' + escapeHtml(conn.name) + '">' + escapeHtml(conn.name) + '</div>' +
            statusBadge +
            '</div>' +
            '<small class="text-muted mb-2"><i class="fas fa-envelope me-1"></i>' + escapeHtml(conn.google_email) + '</small>' +
            '<div class="d-flex flex-column gap-1 mb-2">' +
            lastSyncHtml +
            nextSyncHtml +
            '</div>' +
            '<small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i>' + escapeHtml(locText) + '</small>' +
            '<div class="mt-auto">' + syncBtn + '</div>' +
            '</div>' +
            '</div>';
    }

    function updateBadge(color, text) {
        const colorMap = { success: '#13C672', warning: '#FEC90F', secondary: '#adb5bd', danger: '#FA896B' };
        const badge = $('#sync-status-badge');
        badge.attr('class', 'badge bg-' + color + ' rounded-pill');
        badge.html('<i class="fas fa-circle me-1"></i>' + escapeHtml(text));
    }

    function showError(message) {
        $('#sync-status-content').html(
            '<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle me-2"></i>' + escapeHtml(message) + '</div>'
        );
    }

    function formatDateTime(isoString) {
        if (!isoString) { return '-'; }
        try {
            const d = new Date(isoString);
            return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit' }) +
                ' ' + d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            return isoString;
        }
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) { return ''; }
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    $(document).on('click', '.sync-now-btn', function () {
        const btn = $(this);
        const connectionId = btn.data('connection-id');
        const url = syncNowBaseUrl.replace('__ID__', connectionId);

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Sincronizando...');

        $.ajax({
            url: url,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    loadSyncStatus();
                } else {
                    toastr.error(response.message || 'Error al iniciar la sincronizacion.');
                    btn.prop('disabled', false).html('<i class="fas fa-sync-alt me-1"></i>Sincronizar ahora');
                }
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Error al iniciar la sincronizacion.';
                toastr.error(msg);
                btn.prop('disabled', false).html('<i class="fas fa-sync-alt me-1"></i>Sincronizar ahora');
            },
        });
    });

    $('#refresh-sync-status').on('click', function () {
        loadSyncStatus();
    });

    loadSyncStatus();
}());
</script>
@endpush
