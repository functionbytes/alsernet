<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Autenticación de dos factores (2FA)</h6>
                <p class="text-muted mb-4">Agrega una capa extra de seguridad requiriendo un código de tu aplicación autenticadora al iniciar sesión.</p>

                {{-- 2FA Status badge --}}
                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white rounded-1 p-3">
                            <i class="fas fa-shield-halved fs-4 {{ $user->hasTwoFactorEnabled() ? 'text-success' : 'text-warning' }}"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Estado de 2FA</h6>
                            <small class="text-muted">
                                {{ $user->hasTwoFactorEnabled() ? 'La autenticación de dos factores está activada' : 'La autenticación de dos factores está desactivada' }}
                            </small>
                        </div>
                    </div>
                    <span class="badge {{ $user->hasTwoFactorEnabled() ? 'bg-success' : 'bg-secondary' }}">
                        {{ $user->hasTwoFactorEnabled() ? 'Activada' : 'Desactivada' }}
                    </span>
                </div>

                @if(! $user->hasTwoFactorEnabled())
                    {{-- SETUP FLOW --}}
                    <div id="section-setup">
                        <h6 class="fw-bold mb-3">Cómo activar 2FA</h6>
                        <ol class="text-muted ps-3 mb-4">
                            <li class="mb-2">Descarga Google Authenticator, Authy o Microsoft Authenticator</li>
                            <li class="mb-2">Haz clic en "Activar 2FA" para generar un código QR</li>
                            <li class="mb-2">Escanea el código QR con tu aplicación</li>
                            <li class="mb-2">Ingresa el código de 6 dígitos que aparece en tu aplicación</li>
                            <li>Guarda tus códigos de recuperación en un lugar seguro</li>
                        </ol>
                        <div class="d-grid">
                            <button type="button" class="btn btn-primary" id="btnStartSetup">
                                <i class="fas fa-qrcode me-2"></i>Activar 2FA
                            </button>
                        </div>
                    </div>

                    {{-- QR Code step --}}
                    <div id="section-qr" class="d-none">
                        <p class="text-muted mb-3">
                            Escanea este código QR con tu aplicación autenticadora y luego ingresa el código de 6 dígitos para confirmar.
                        </p>

                        <div class="text-center mb-4">
                            <div id="qr-loading" class="py-4">
                                <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                            </div>
                            <div id="qr-image" class="d-none">
                                <img id="qr-svg" src="" alt="QR Code 2FA" class="img-fluid border rounded p-2" style="max-width: 200px;">
                                <div class="mt-2">
                                    <small class="text-muted">¿No puedes escanear? Usa este código: </small>
                                    <code id="secret-text" class="d-block mt-1 fw-bold"></code>
                                </div>
                            </div>
                        </div>

                        <form id="formConfirm2FA" onSubmit="return false">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Código de verificación <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control text-center"
                                       id="confirm_code"
                                       name="code"
                                       maxlength="6"
                                       inputmode="numeric"
                                       placeholder="000000"
                                       autocomplete="one-time-code">
                                <small class="text-muted">Ingresa el código de 6 dígitos de tu aplicación</small>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success flex-grow-1">
                                    <i class="fas fa-check me-2"></i>Confirmar y activar
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="btnCancelSetup">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Recovery codes shown after activation --}}
                    <div id="section-recovery-new" class="d-none">
                        <div class="alert alert-warning">
                            <i class="fas fa-triangle-exclamation me-2"></i>
                            <strong>Guarda estos códigos ahora.</strong> No volverán a mostrarse y son tu único acceso si pierdes tu dispositivo.
                        </div>
                        <div id="recovery-codes-list" class="row g-2 mb-4"></div>
                        <button type="button" class="btn btn-primary" onclick="window.location.reload()">
                            <i class="fas fa-check me-2"></i>Listo, los guardé
                        </button>
                    </div>

                @else
                    {{-- MANAGEMENT FLOW (2FA already enabled) --}}
                    <div id="section-manage">
                        <h6 class="fw-bold mb-3">Gestionar 2FA</h6>

                        {{-- Regenerate recovery codes --}}
                        <div class="card border mb-3">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="mb-1">Códigos de recuperación</h6>
                                        <small class="text-muted">Genera nuevos códigos si perdiste los anteriores</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnShowRegenForm">
                                        <i class="fas fa-refresh me-1"></i>Regenerar
                                    </button>
                                </div>
                                <div id="regen-form" class="d-none mt-3">
                                    <form id="formRegenCodes" onSubmit="return false">
                                        @csrf
                                        @method('POST')
                                        <div class="mb-2">
                                            <input type="password" class="form-control form-control-sm"
                                                   name="password" placeholder="Confirma tu contraseña">
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-sm btn-primary">Confirmar</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCancelRegen">Cancelar</button>
                                        </div>
                                    </form>
                                    <div id="new-recovery-codes" class="d-none mt-3">
                                        <div class="alert alert-warning small">
                                            <i class="fas fa-triangle-exclamation me-1"></i>Guarda estos códigos. Reemplazan a los anteriores.
                                        </div>
                                        <div id="regen-codes-list" class="row g-2"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Disable 2FA --}}
                        <div class="card border border-danger">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="mb-1 text-danger">Desactivar 2FA</h6>
                                        <small class="text-muted">Tu cuenta quedará menos protegida</small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="btnShowDisableForm">
                                        <i class="fas fa-shield-xmark me-1"></i>Desactivar
                                    </button>
                                </div>
                                <div id="disable-form" class="d-none mt-3">
                                    <form id="formDisable2FA" onSubmit="return false">
                                        @csrf
                                        @method('DELETE')
                                        <div class="mb-2">
                                            <input type="password" class="form-control form-control-sm"
                                                   name="password" placeholder="Confirma tu contraseña">
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-sm btn-danger">Desactivar 2FA</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCancelDisable">Cancelar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border bg-light">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <i class="fas fa-mobile-screen fa-3x text-primary mb-3"></i>
                    <h6 class="fw-bold">Aplicaciones recomendadas</h6>
                </div>

                <div class="mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="fab fa-google text-primary"></i>
                        <strong class="small">Google Authenticator</strong>
                    </div>
                    <small class="text-muted d-block">Aplicación oficial de Google para 2FA</small>
                </div>

                <div class="mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="fas fa-shield text-success"></i>
                        <strong class="small">Authy</strong>
                    </div>
                    <small class="text-muted d-block">Respaldo en la nube y multi-dispositivo</small>
                </div>

                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="fas fa-key text-warning"></i>
                        <strong class="small">Microsoft Authenticator</strong>
                    </div>
                    <small class="text-muted d-block">Aplicación de Microsoft con 2FA</small>
                </div>
            </div>
        </div>

        <div class="card border mt-3">
            <div class="card-body p-4">
                <div class="text-center">
                    <i class="fas fa-info-circle text-info fa-2x mb-2"></i>
                    <h6 class="fw-bold mb-2">¿Por qué 2FA?</h6>
                    <p class="text-muted mb-0">
                        Protege tu cuenta incluso si alguien conoce tu contraseña. El código cambia cada 30 segundos.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {

    // --- SETUP FLOW ---

    function renderRecoveryCodes(codes, containerId) {
        const $container = $('#' + containerId);
        $container.empty();
        codes.forEach(function (code) {
            $container.append(
                '<div class="col-6"><code class="d-block p-2 bg-light border rounded text-center small">' + code + '</code></div>'
            );
        });
    }

    $('#btnStartSetup').on('click', function () {
        $('#section-setup').addClass('d-none');
        $('#section-qr').removeClass('d-none');
        $('#qr-loading').removeClass('d-none');
        $('#qr-image').addClass('d-none');

        $.ajax({
            url: "{{ route('settings.auth.two-factor.setup') }}",
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                const svgData = 'data:image/svg+xml;base64,' + response.qr_code;
                $('#qr-svg').attr('src', svgData);
                $('#secret-text').text(response.secret);
                $('#qr-loading').addClass('d-none');
                $('#qr-image').removeClass('d-none');
                $('#confirm_code').focus();
            },
            error: function () {
                toastr.error('Error al generar el código QR. Inténtalo de nuevo.');
                $('#section-qr').addClass('d-none');
                $('#section-setup').removeClass('d-none');
            }
        });
    });

    $('#btnCancelSetup').on('click', function () {
        $('#section-qr').addClass('d-none');
        $('#section-setup').removeClass('d-none');
    });

    // Only allow digits in confirm code input
    $('#confirm_code').on('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });

    $('#formConfirm2FA').on('submit', function () {
        const code = $('#confirm_code').val().trim();
        if (code.length !== 6) {
            toastr.warning('El código debe tener 6 dígitos.');
            return;
        }

        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Verificando...');

        $.ajax({
            url: "{{ route('settings.auth.two-factor.confirm') }}",
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { code: code },
            success: function (response) {
                $('#section-qr').addClass('d-none');
                renderRecoveryCodes(response.recovery_codes, 'recovery-codes-list');
                $('#section-recovery-new').removeClass('d-none');
                toastr.success(response.message);
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-check me-2"></i>Confirmar y activar');
                toastr.error(xhr.responseJSON?.message || 'Código incorrecto.');
            }
        });
    });

    // --- MANAGEMENT FLOW ---

    // Regenerate recovery codes
    $('#btnShowRegenForm').on('click', function () {
        $('#regen-form').removeClass('d-none');
        $('#new-recovery-codes').addClass('d-none');
    });

    $('#btnCancelRegen').on('click', function () {
        $('#regen-form').addClass('d-none');
        $('#formRegenCodes')[0].reset();
    });

    $('#formRegenCodes').on('submit', function () {
        const password = $(this).find('[name="password"]').val();
        if (!password) {
            toastr.warning('Ingresa tu contraseña.');
            return;
        }

        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>...');

        $.ajax({
            url: "{{ route('settings.auth.two-factor.recovery-codes') }}",
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { password: password },
            success: function (response) {
                renderRecoveryCodes(response.recovery_codes, 'regen-codes-list');
                $('#new-recovery-codes').removeClass('d-none');
                $btn.prop('disabled', false).html('Confirmar');
                $(this).find('[name="password"]').val('');
                toastr.success(response.message);
            }.bind(this),
            error: function (xhr) {
                $btn.prop('disabled', false).html('Confirmar');
                toastr.error(xhr.responseJSON?.message || 'Error al regenerar códigos.');
            }
        });
    });

    // Disable 2FA
    $('#btnShowDisableForm').on('click', function () {
        $('#disable-form').removeClass('d-none');
    });

    $('#btnCancelDisable').on('click', function () {
        $('#disable-form').addClass('d-none');
        $('#formDisable2FA')[0].reset();
    });

    $('#formDisable2FA').on('submit', function () {
        const password = $(this).find('[name="password"]').val();
        if (!password) {
            toastr.warning('Ingresa tu contraseña.');
            return;
        }

        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Desactivando...');

        $.ajax({
            url: "{{ route('settings.auth.two-factor.disable') }}",
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-HTTP-Method-Override': 'DELETE'
            },
            data: { password: password, _method: 'DELETE' },
            success: function (response) {
                toastr.success(response.message);
                setTimeout(() => window.location.reload(), 1000);
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-shield-xmark me-1"></i>Desactivar 2FA');
                toastr.error(xhr.responseJSON?.message || 'Error al desactivar 2FA.');
            }
        });
    });
});
</script>
@endpush
