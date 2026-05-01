@extends('layouts.theme')

@section('title', 'Configuración de autenticación de dos factores')

@section('page_header')
    @include('core::components.card', ['title' => 'Seguridad de cuenta'])
@endsection

@section('content')
<div class="widget-content">

    @include('core::components.alerts')

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xl-6">

            @if(auth()->user()->two_factor_confirmed_at)
                {{-- 2FA already active --}}
                <div class="card">
                    <div class="card-body text-center py-4">
                        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3"
                             style="width:64px;height:64px;">
                            <i class="fas fa-shield-alt text-success fs-3"></i>
                        </div>
                        <h5 class="fw-bold mb-1">Autenticación de dos factores activa</h5>
                        <p class="text-muted mb-0">
                            Activada el
                            {{ auth()->user()->two_factor_confirmed_at->format('d/m/Y \a \l\a\s H:i') }}
                        </p>
                    </div>
                    <div class="card-footer bg-white border-top p-3">
                        <button type="button" class="btn btn-danger w-100 mb-2" data-bs-toggle="modal" data-bs-target="#disableModal">
                            Desactivar autenticación de dos factores
                        </button>
                        <a href="{{ route('manager.helpdesk') }}" class="btn btn-light w-100">Volver al inicio</a>
                    </div>
                </div>

                {{-- Disable 2FA modal --}}
                <div class="modal fade" id="disableModal" tabindex="-1" aria-labelledby="disableModalLabel" aria-modal="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold" id="disableModalLabel">Desactivar 2FA</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted mb-3">
                                    Ingresa tu contraseña actual para confirmar la desactivación.
                                    Tu cuenta quedará menos protegida.
                                </p>
                                <div class="mb-3">
                                    <label for="disable-password" class="form-label">Contraseña</label>
                                    <input type="password"
                                           id="disable-password"
                                           class="form-control"
                                           placeholder="Tu contraseña actual"
                                           autocomplete="current-password">
                                    <div class="invalid-feedback" id="disable-password-error"></div>
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0 flex-column gap-2">
                                <button type="button" id="btn-disable" class="btn btn-danger w-100">
                                    Desactivar 2FA
                                </button>
                                <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            @else
                {{-- Setup wizard --}}
                <div class="card">
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Configurar autenticación de dos factores</h5>
                        <small class="text-muted">Protege tu cuenta con un segundo paso de verificación</small>
                    </div>
                    <div class="card-body">

                        {{-- Stepper --}}
                        <div class="d-flex align-items-center justify-content-center mb-4 gap-0">
                            <div class="d-flex flex-column align-items-center">
                                <div class="step-circle active" id="circle-1">1</div>
                                <small class="text-muted mt-1 text-center" style="font-size:.75rem;">Configurar app</small>
                            </div>
                            <div class="step-line flex-grow-1 mx-2" id="line-1-2"></div>
                            <div class="d-flex flex-column align-items-center">
                                <div class="step-circle" id="circle-2">2</div>
                                <small class="text-muted mt-1 text-center" style="font-size:.75rem;">Escanear QR</small>
                            </div>
                            <div class="step-line flex-grow-1 mx-2" id="line-2-3"></div>
                            <div class="d-flex flex-column align-items-center">
                                <div class="step-circle" id="circle-3">3</div>
                                <small class="text-muted mt-1 text-center" style="font-size:.75rem;">Códigos de respaldo</small>
                            </div>
                        </div>

                        {{-- Step 1: Configure app --}}
                        <div id="step-1">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Paso 1: Instala una aplicación autenticadora</h6>
                            <p class="text-muted mb-3">
                                Necesitas una aplicación autenticadora en tu teléfono para generar
                                los códigos de verificación de 6 dígitos.
                            </p>
                            <div class="row g-3 mb-4">
                                <div class="col-6">
                                    <div class="border rounded p-3 text-center h-100">
                                        <i class="fas fa-mobile-alt text-muted fs-3 mb-2 d-block"></i>
                                        <div class="fw-semibold small">Google Authenticator</div>
                                        <small class="text-muted">iOS y Android</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-3 text-center h-100">
                                        <i class="fas fa-lock text-muted fs-3 mb-2 d-block"></i>
                                        <div class="fw-semibold small">Authy</div>
                                        <small class="text-muted">iOS, Android y escritorio</small>
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="btn-generate-qr" class="btn btn-primary w-100 mb-2">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="generate-spinner"></span>
                                Generar QR
                            </button>
                        </div>

                        {{-- Step 2: Scan QR --}}
                        <div id="step-2" class="d-none">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Paso 2: Escanea el código QR</h6>
                            <p class="text-muted mb-3">
                                Abre tu aplicación autenticadora y escanea el siguiente código QR.
                                Luego ingresa el código de 6 dígitos que aparece en la app.
                            </p>

                            <div class="text-center mb-3">
                                <div id="qr-image-wrapper" class="d-inline-block border rounded p-3 bg-white">
                                    <img id="qr-image" src="" alt="QR Code 2FA" width="200" height="200" loading="lazy">
                                </div>
                            </div>

                            <div class="bg-light rounded p-3 mb-3">
                                <label class="form-label fw-semibold small mb-1">Clave manual (si no puedes escanear)</label>
                                <div class="d-flex align-items-center gap-2">
                                    <code id="secret-text" class="flex-grow-1 text-break small"></code>
                                    <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" id="btn-copy-secret" title="Copiar clave">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="totp-code" class="form-label fw-semibold">Código de verificación</label>
                                <input type="text"
                                       id="totp-code"
                                       class="form-control form-control-lg text-center"
                                       placeholder="000000"
                                       maxlength="6"
                                       inputmode="numeric"
                                       autocomplete="one-time-code"
                                       pattern="[0-9]{6}">
                                <div class="invalid-feedback" id="totp-error"></div>
                            </div>

                            <button type="button" id="btn-verify-code" class="btn btn-primary w-100 mb-2">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="verify-spinner"></span>
                                Verificar código
                            </button>
                            <button type="button" class="btn btn-light w-100" id="btn-back-step1">
                                Volver
                            </button>
                        </div>

                        {{-- Step 3: Recovery codes --}}
                        <div id="step-3" class="d-none">
                            <h6 class="fw-bold mb-3 border-bottom pb-2">Paso 3: Guarda tus códigos de recuperación</h6>

                            <div class="alert alert-warning d-flex gap-2" role="alert">
                                <i class="fas fa-exclamation-triangle flex-shrink-0 mt-1"></i>
                                <div>
                                    <strong>Importante:</strong> Guarda estos códigos en un lugar seguro.
                                    Podrás usarlos para acceder a tu cuenta si pierdes tu dispositivo.
                                    No podrás verlos de nuevo.
                                </div>
                            </div>

                            <div class="bg-light border rounded p-3 mb-3">
                                <div id="recovery-codes-list" class="row g-2">
                                    {{-- Filled by JS --}}
                                </div>
                            </div>

                            <button type="button" id="btn-copy-codes" class="btn btn-outline-secondary w-100 mb-3">
                                <i class="fas fa-copy me-2"></i>
                                Copiar todos los códigos
                            </button>

                            <a href="{{ route('manager.helpdesk') }}" class="btn btn-primary w-100">
                                He guardado mis códigos — Finalizar
                            </a>
                        </div>

                    </div>
                </div>
            @endif

        </div>
    </div>

</div>
@endsection

@push('css')
<style>
.step-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: .9rem;
    transition: background .3s, color .3s;
    flex-shrink: 0;
}
.step-circle.active {
    background: #b10100;
    color: #fff;
}
.step-circle.done {
    background: #13C672;
    color: #fff;
}
.step-line {
    height: 2px;
    background: #e9ecef;
    min-width: 40px;
    transition: background .3s;
}
.step-line.done {
    background: #13C672;
}
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const enableUrl  = '{{ route("manager.helpdesk.2fa.enable") }}';
    const confirmUrl = '{{ route("manager.helpdesk.2fa.confirm") }}';
    const disableUrl = '{{ route("manager.helpdesk.2fa.disable") }}';

    let recoveryCodes = [];

    // ── Stepper helpers ──────────────────────────────────────────────
    function goToStep(step) {
        $('#step-1, #step-2, #step-3').addClass('d-none');
        $(`#step-${step}`).removeClass('d-none');

        // Update circle states
        for (let i = 1; i <= 3; i++) {
            const $c = $(`#circle-${i}`);
            $c.removeClass('active done');
            if (i < step) { $c.addClass('done').html('<i class="fas fa-check" style="font-size:.7rem;"></i>'); }
            else if (i === step) { $c.addClass('active').text(i); }
            else { $c.text(i); }
        }

        // Update lines
        $('#line-1-2').toggleClass('done', step > 1);
        $('#line-2-3').toggleClass('done', step > 2);
    }

    // ── Step 1: Generate QR ──────────────────────────────────────────
    $('#btn-generate-qr').on('click', function () {
        const $btn = $(this);
        const $spin = $('#generate-spinner');

        $btn.prop('disabled', true);
        $spin.removeClass('d-none');

        $.ajax({
            url: enableUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                if (!res.success) {
                    toastr.error(res.message || 'Error al generar QR', 'Error');
                    return;
                }

                const data = res.data;

                // Render QR as inline SVG via Google Charts proxy
                const qrSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(data.qr_code_url);
                $('#qr-image').attr('src', qrSrc);
                $('#secret-text').text(data.secret);

                recoveryCodes = data.recovery_codes;

                goToStep(2);
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'No se pudo generar el código QR.';
                toastr.error(msg, 'Error');
            },
            complete: function () {
                $btn.prop('disabled', false);
                $spin.addClass('d-none');
            }
        });
    });

    // ── Step 2: Back to step 1 ───────────────────────────────────────
    $('#btn-back-step1').on('click', function () {
        $('#totp-code').val('').removeClass('is-invalid');
        goToStep(1);
    });

    // ── Step 2: Copy secret ──────────────────────────────────────────
    $('#btn-copy-secret').on('click', function () {
        const secret = $('#secret-text').text();
        navigator.clipboard.writeText(secret).then(function () {
            toastr.info('Clave copiada al portapapeles', '');
        });
    });

    // ── Step 2: Verify TOTP ──────────────────────────────────────────
    $('#totp-code').on('input', function () {
        $(this).removeClass('is-invalid');
        $('#totp-error').text('');
    });

    $('#btn-verify-code').on('click', function () {
        const code = $('#totp-code').val().trim();

        if (!/^\d{6}$/.test(code)) {
            $('#totp-code').addClass('is-invalid');
            $('#totp-error').text('Ingresa un código de 6 dígitos.');
            return;
        }

        const $btn = $(this);
        const $spin = $('#verify-spinner');

        $btn.prop('disabled', true);
        $spin.removeClass('d-none');

        $.ajax({
            url: confirmUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { code: code },
            success: function (res) {
                if (!res.success) {
                    $('#totp-code').addClass('is-invalid');
                    $('#totp-error').text(res.message || 'Código inválido.');
                    return;
                }

                toastr.success('Código verificado correctamente.', '');
                buildRecoveryCodes();
                goToStep(3);
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors;
                    const msg = errors?.code?.[0] || xhr.responseJSON?.message || 'Código inválido.';
                    $('#totp-code').addClass('is-invalid');
                    $('#totp-error').text(msg);
                } else {
                    toastr.error('Error al verificar el código.', 'Error');
                }
            },
            complete: function () {
                $btn.prop('disabled', false);
                $spin.addClass('d-none');
            }
        });
    });

    // ── Step 3: Build recovery codes list ───────────────────────────
    function buildRecoveryCodes() {
        const $list = $('#recovery-codes-list').empty();
        recoveryCodes.forEach(function (code) {
            $list.append(
                '<div class="col-6 col-sm-3">' +
                '<code class="d-block text-center fw-semibold small p-1 border rounded bg-white">' +
                $('<span>').text(code).html() +
                '</code></div>'
            );
        });
    }

    // ── Step 3: Copy all recovery codes ─────────────────────────────
    $('#btn-copy-codes').on('click', function () {
        const text = recoveryCodes.join('\n');
        navigator.clipboard.writeText(text).then(function () {
            toastr.success('Códigos copiados al portapapeles.', '');
        });
    });

    // ── Disable 2FA modal ────────────────────────────────────────────
    $('#btn-disable').on('click', function () {
        const password = $('#disable-password').val();

        if (!password) {
            $('#disable-password').addClass('is-invalid');
            $('#disable-password-error').text('La contraseña es obligatoria.');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).text('Desactivando...');

        $.ajax({
            url: disableUrl,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            data: { password: password },
            success: function (res) {
                if (res.success) {
                    toastr.success(res.message, '');
                    setTimeout(function () { location.reload(); }, 1200);
                }
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors;
                    const msg = errors?.password?.[0] || xhr.responseJSON?.message || 'Contraseña incorrecta.';
                    $('#disable-password').addClass('is-invalid');
                    $('#disable-password-error').text(msg);
                } else {
                    toastr.error('Error al desactivar 2FA.', 'Error');
                }
                $btn.prop('disabled', false).text('Desactivar 2FA');
            }
        });
    });

    $('#disable-password').on('input', function () {
        $(this).removeClass('is-invalid');
        $('#disable-password-error').text('');
    });

    // Allow Enter key on TOTP code field
    $('#totp-code').on('keydown', function (e) {
        if (e.key === 'Enter') { $('#btn-verify-code').trigger('click'); }
    });
});
</script>
@endpush
