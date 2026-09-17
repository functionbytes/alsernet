/**
 * Helpdesk · Misc — asistente de configuracion de 2FA
 * (compliance/2fa/setup.blade.php): stepper de 3 pasos, generacion de QR,
 * verificacion del codigo TOTP, listado de codigos de recuperacion y el
 * modal de desactivacion de 2FA.
 *
 * Requiere `window.HdTwoFaSetupConfig` definido antes de cargar este script:
 *
 *   window.HdTwoFaSetupConfig = {
 *       enableUrl: '{{ route("manager.helpdesk.2fa.enable") }}',
 *       confirmUrl: '{{ route("manager.helpdesk.2fa.confirm") }}',
 *       disableUrl: '{{ route("manager.helpdesk.2fa.disable") }}',
 *   };
 */
(function ($) {
    'use strict';

    const cfg = window.HdTwoFaSetupConfig;

    if (!cfg) {
        return;
    }

    function init() {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        const enableUrl = cfg.enableUrl;
        const confirmUrl = cfg.confirmUrl;
        const disableUrl = cfg.disableUrl;

        let recoveryCodes = [];

        // ── Stepper helpers ──────────────────────────────────────────────
        function goToStep(step) {
            $('#step-1, #step-2, #step-3').addClass('d-none');
            $(`#step-${step}`).removeClass('d-none');

            // Update circle states
            for (let i = 1; i <= 3; i++) {
                const $c = $(`#circle-${i}`);
                $c.removeClass('active done');
                if (i < step) { $c.addClass('done').html('<i class="fas fa-check bv-fs-70"></i>'); }
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
    }

    if (document.readyState !== 'loading') {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})(jQuery);
