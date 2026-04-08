<div class="row g-4">
    <div class="col-12">
        <div class="mb-4">
            <h6 class="mb-1 fw-bold">Sesiones activas</h6>
            <p class="text-muted mb-0">Gestiona tus sesiones activas en diferentes dispositivos y navegadores</p>
        </div>

        <div class="card border">
            <div class="card-body p-4">

                {{-- Session list (populated via AJAX) --}}
                <div id="sessions-loading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                </div>

                <div id="sessions-list" class="d-none"></div>

                {{-- Revoke all others --}}
                <div class="mt-4" id="revoke-others-section" style="display:none!important">
                    <div class="card border border-warning">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="mb-1">Cerrar otras sesiones</h6>
                                    <small class="text-muted">Cierra sesión en todos los demás dispositivos</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-warning" id="btnShowRevokeAll">
                                    <i class="fas fa-sign-out-alt me-1"></i>Cerrar todas
                                </button>
                            </div>
                            <div id="revoke-all-form" class="d-none mt-3">
                                <form id="formRevokeOthers" onsubmit="return false">
                                    @csrf
                                    <div class="mb-2">
                                        <input type="password" class="form-control form-control-sm"
                                               name="password" placeholder="Confirma tu contraseña" required>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-sm btn-warning">Confirmar</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCancelRevokeAll">Cancelar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Info Alert --}}
                <div class="alert alert-info border-0 mt-4" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-2">Control de sesiones</h6>
                            <p class="mb-0 small">
                                Si detectas actividad sospechosa, puedes revocar sesiones individuales o cerrar todas las sesiones excepto la actual.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {

    function deviceIcon(userAgent) {
        if (!userAgent) { return '<i class="fas fa-question-circle text-muted"></i>'; }
        if (/mobile/i.test(userAgent)) { return '<i class="fas fa-mobile-screen text-primary"></i>'; }
        if (/tablet/i.test(userAgent)) { return '<i class="fas fa-tablet text-primary"></i>'; }
        return '<i class="fas fa-desktop text-primary"></i>';
    }

    function browserName(userAgent) {
        if (!userAgent) { return 'Desconocido'; }
        if (/Edg/i.test(userAgent))     { return 'Microsoft Edge'; }
        if (/Chrome/i.test(userAgent))  { return 'Google Chrome'; }
        if (/Firefox/i.test(userAgent)) { return 'Mozilla Firefox'; }
        if (/Safari/i.test(userAgent))  { return 'Safari'; }
        return 'Otro navegador';
    }

    function timeAgo(timestamp) {
        const diff = Math.floor(Date.now() / 1000) - timestamp;
        if (diff < 60) { return 'Hace unos segundos'; }
        if (diff < 3600) { return 'Hace ' + Math.floor(diff / 60) + ' minutos'; }
        if (diff < 86400) { return 'Hace ' + Math.floor(diff / 3600) + ' horas'; }
        return 'Hace ' + Math.floor(diff / 86400) + ' días';
    }

    function renderSessions(sessions) {
        const $list = $('#sessions-list');
        $list.empty();

        if (!sessions.length) {
            $list.append('<p class="text-muted text-center py-4">No hay sesiones activas registradas.</p>');
            return;
        }

        let hasOthers = false;

        sessions.forEach(function (s) {
            const isCurrent = s.is_current;
            if (!isCurrent) { hasOthers = true; }

            const revokeBtn = isCurrent
                ? '<span class="badge bg-success">Sesión actual</span>'
                : '<button type="button" class="btn btn-sm btn-outline-danger btn-revoke" data-session-id="' + s.id + '">' +
                  '<i class="fas fa-times me-1"></i>Revocar</button>';

            $list.append(
                '<div class="d-flex align-items-start justify-content-between py-3 border-bottom session-item" data-id="' + s.id + '">' +
                '<div class="d-flex align-items-start gap-3 flex-grow-1">' +
                '<div class="bg-light-primary rounded-1 p-3 d-flex align-items-center justify-content-center">' +
                deviceIcon(s.user_agent) +
                '</div>' +
                '<div class="flex-grow-1">' +
                '<div class="d-flex align-items-center gap-2 mb-1">' +
                '<h6 class="mb-0">' + browserName(s.user_agent) + '</h6>' +
                '</div>' +
                '<small class="text-muted d-block"><i class="fas fa-globe me-1"></i><strong>IP:</strong> ' + (s.ip_address || 'Desconocida') + '</small>' +
                '<small class="text-muted d-block mt-1"><i class="fas fa-clock me-1"></i>' + timeAgo(s.last_activity) + '</small>' +
                '</div>' +
                '</div>' +
                '<div class="ms-2">' + revokeBtn + '</div>' +
                '</div>'
            );
        });

        if (hasOthers) {
            $('#revoke-others-section').css('display', '');
        }
    }

    function loadSessions() {
        $('#sessions-loading').removeClass('d-none');
        $('#sessions-list').addClass('d-none');

        $.get("{{ route('settings.auth.sessions.list') }}", function (response) {
            $('#sessions-loading').addClass('d-none');
            $('#sessions-list').removeClass('d-none');
            renderSessions(response.sessions || []);
        }).fail(function () {
            $('#sessions-loading').addClass('d-none');
            $('#sessions-list').removeClass('d-none').html('<p class="text-danger">Error al cargar las sesiones.</p>');
        });
    }

    // Revoke individual session
    $(document).on('click', '.btn-revoke', function () {
        const sessionId = $(this).data('session-id');
        const $item = $(this).closest('.session-item');

        if (!confirm('¿Revocar esta sesión?')) { return; }

        $.ajax({
            url: "{{ url('setting/auth/sessions') }}/" + sessionId,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-HTTP-Method-Override': 'DELETE'
            },
            data: { _method: 'DELETE' },
            success: function (response) {
                toastr.success(response.message);
                $item.fadeOut(300, function () { $(this).remove(); });
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error al revocar la sesión.');
            }
        });
    });

    // Show / hide revoke-all form
    $('#btnShowRevokeAll').on('click', function () {
        $('#revoke-all-form').removeClass('d-none');
    });

    $('#btnCancelRevokeAll').on('click', function () {
        $('#revoke-all-form').addClass('d-none').find('[name="password"]').val('');
    });

    $('#formRevokeOthers').on('submit', function () {
        const password = $(this).find('[name="password"]').val();
        if (!password) {
            toastr.warning('Ingresa tu contraseña.');
            return;
        }

        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>...');

        $.ajax({
            url: "{{ route('settings.auth.sessions.destroy-others') }}",
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { password: password },
            success: function (response) {
                toastr.success(response.message);
                loadSessions();
                $('#revoke-all-form').addClass('d-none').find('[name="password"]').val('');
                $btn.prop('disabled', false).html('Confirmar');
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('Confirmar');
                toastr.error(xhr.responseJSON?.message || 'Error al cerrar sesiones.');
            }
        });
    });

    // Load sessions when this tab becomes visible
    const sessionsTabEl = document.getElementById('sessions-tab');
    if (sessionsTabEl) {
        sessionsTabEl.addEventListener('shown.bs.tab', loadSessions);
    }

    // Auto-load if this tab is active on page load
    @if($activeTab === 'sessions')
    loadSessions();
    @endif
});
</script>
@endpush
