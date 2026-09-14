<div class="row g-4">
    <div class="col-12">
        <div class="mb-4">
            <h6 class="mb-1 fw-bold">Historial de actividad</h6>
            <p class="text-muted mb-0">Últimos intentos de inicio de sesión y eventos relevantes</p>
        </div>

        <div class="card border">
            <div class="card-body p-4">

                {{-- Eventos del perfil (estáticos) --}}
                <div class="timeline-widget mb-4">
                    @if ($user->email_verified_at ?? $user->mail_verified_at)
                        @php $verifiedAt = $user->email_verified_at ?? $user->mail_verified_at; @endphp
                        <div class="timeline-item d-flex position-relative pb-4">
                            <div class="timeline-time text-muted me-3" style="min-width: 120px;">
                                {{ $verifiedAt->format('d M, H:i') }}
                            </div>
                            <div class="timeline-badge-wrap d-flex flex-column align-items-center">
                                <div class="timeline-badge bg-success rounded-circle" style="width: 12px; height: 12px;"></div>
                                <div class="timeline-badge-border bg-light" style="width: 2px; height: 100%;"></div>
                            </div>
                            <div class="timeline-desc fs-3 text-dark ms-3 flex-grow-1">
                                <h6 class="mb-1">Correo verificado</h6>
                                <p class="mb-0 text-muted">Verificación exitosa del correo electrónico</p>
                            </div>
                        </div>
                    @endif

                    <div class="timeline-item d-flex position-relative">
                        <div class="timeline-time text-muted me-3" style="min-width: 120px;">
                            {{ $user->created_at->format('d M, H:i') }}
                        </div>
                        <div class="timeline-badge-wrap d-flex flex-column align-items-center">
                            <div class="timeline-badge bg-info rounded-circle" style="width: 12px; height: 12px;"></div>
                        </div>
                        <div class="timeline-desc fs-3 text-dark ms-3 flex-grow-1">
                            <h6 class="mb-1">Cuenta creada</h6>
                            <p class="mb-0 text-muted">Registro en el sistema completado</p>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                {{-- Login attempts (AJAX) --}}
                <h6 class="fw-bold mb-3">Intentos de inicio de sesión recientes</h6>

                <div id="activity-loading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                </div>

                <div id="activity-list" class="d-none"></div>

                {{-- Info Alert --}}
                <div class="alert alert-info border-0 mt-4" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fa fa-circle-info fs-5 me-3 mt-1"></i>
                        <div>
                            <h6 class="fw-bold mb-2">Historial de eventos</h6>
                            <p class="mb-0 small">
                                Se muestran los últimos 50 intentos de inicio de sesión.
                                Si detectas accesos que no reconoces, cambia tu contraseña inmediatamente.
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

    function statusBadge(status) {
        const map = {
            'success':     ['bg-success', 'Éxito'],
            'failed':      ['bg-danger',  'Fallido'],
            'lockout':     ['bg-warning', 'Bloqueado'],
            '2fa_success': ['bg-success', '2FA Éxito'],
            '2fa_failed':  ['bg-danger',  '2FA Fallido'],
        };
        const [cls, label] = map[status] || ['bg-secondary', status];
        return '<span class="badge ' + cls + '">' + label + '</span>';
    }

    function statusIcon(status) {
        if (status === 'success' || status === '2fa_success') {
            return '<i class="fas fa-check-circle text-success"></i>';
        }
        return '<i class="fas fa-xmark-circle text-danger"></i>';
    }

    function browserName(userAgent) {
        if (!userAgent) { return 'Desconocido'; }
        if (/Edg/i.test(userAgent))     { return 'Edge'; }
        if (/Chrome/i.test(userAgent))  { return 'Chrome'; }
        if (/Firefox/i.test(userAgent)) { return 'Firefox'; }
        if (/Safari/i.test(userAgent))  { return 'Safari'; }
        return 'Otro';
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

    function renderActivity(attempts) {
        const $list = $('#activity-list');
        $list.empty();

        if (!attempts.length) {
            $list.append('<p class="text-muted text-center py-4">No hay intentos registrados.</p>');
            return;
        }

        attempts.forEach(function (a) {
            $list.append(
                '<div class="d-flex align-items-start justify-content-between py-2 border-bottom">' +
                '<div class="d-flex align-items-start gap-3">' +
                '<div class="pt-1">' + statusIcon(a.status) + '</div>' +
                '<div>' +
                '<div class="d-flex align-items-center gap-2 mb-1 flex-wrap">' +
                statusBadge(a.status) +
                (a.reason ? '<small class="text-muted">' + $('<div>').text(a.reason).html() + '</small>' : '') +
                '</div>' +
                '<small class="text-muted d-block"><i class="fas fa-globe me-1"></i>' + (a.ip_address || '—') + ' · ' + browserName(a.user_agent) + '</small>' +
                '</div>' +
                '</div>' +
                '<small class="text-muted">' + formatDate(a.attempted_at) + '</small>' +
                '</div>'
            );
        });
    }

    function loadActivity() {
        $('#activity-loading').removeClass('d-none');
        $('#activity-list').addClass('d-none');

        $.get("{{ route('settings.auth.activity.list') }}", function (response) {
            $('#activity-loading').addClass('d-none');
            $('#activity-list').removeClass('d-none');
            renderActivity(response.attempts || []);
        }).fail(function () {
            $('#activity-loading').addClass('d-none');
            $('#activity-list').removeClass('d-none').html('<p class="text-danger">Error al cargar la actividad.</p>');
        });
    }

    const activityTabEl = document.getElementById('activity-tab');
    if (activityTabEl) {
        activityTabEl.addEventListener('shown.bs.tab', loadActivity);
    }

    @if ($activeTab === 'activity')
    loadActivity();
    @endif
});
</script>
@endpush
