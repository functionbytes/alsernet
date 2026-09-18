/**
 * Helpdesk · Misc — pantalla de verificacion 2FA en el login
 * (compliance/2fa/challenge.blade.php): alterna entre el panel de codigo
 * TOTP y el de codigo de recuperacion, y envia el codigo al endpoint de
 * verificacion.
 *
 * Requiere `window.HdTwoFaChallengeConfig` definido antes de cargar este
 * script:
 *
 *   window.HdTwoFaChallengeConfig = {
 *       verifyUrl: '{{ route("manager.helpdesk.2fa.verify") }}',
 *       fallbackRedirectUrl: '{{ route("manager.helpdesk") }}',
 *   };
 */
(function ($) {
    'use strict';

    const cfg = window.HdTwoFaChallengeConfig;

    if (!cfg) {
        return;
    }

    $(document).ready(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        const verifyUrl = cfg.verifyUrl;

        // ── Toggle panels ────────────────────────────────────────────────
        $('#btn-show-recovery').on('click', function () {
            $('#panel-otp').addClass('d-none');
            $('#panel-recovery').removeClass('d-none');
            $('#recovery-code').focus();
        });

        $('#btn-show-otp').on('click', function () {
            $('#panel-recovery').addClass('d-none');
            $('#panel-otp').removeClass('d-none');
            $('#otp-code').focus();
        });

        // ── Restrict OTP input to digits ─────────────────────────────────
        $('#otp-code').on('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });

        // ── Submit to verify endpoint ─────────────────────────────────────
        function submitVerify(code) {
            const $btns = $('.submit');
            $btns.prop('disabled', true);

            $.ajax({
                url: verifyUrl,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                data: { code: code },
                success: function (response) {
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        window.location.href = cfg.fallbackRedirectUrl;
                    }
                },
                error: function (xhr) {
                    $btns.prop('disabled', false);
                    const msg = xhr.responseJSON?.message || 'Código incorrecto. Inténtalo de nuevo.';
                    $('.errors, .errors-recovery').text(msg).removeClass('d-none');
                }
            });
        }

        $('#form-otp').on('submit', function () {
            const code = $('#otp-code').val().trim();
            $('.errors').addClass('d-none');

            if (code.length !== 6) {
                $('.errors').text('El código debe tener 6 dígitos.').removeClass('d-none');
                return;
            }

            submitVerify(code);
        });

        $('#form-recovery').on('submit', function () {
            const code = $('#recovery-code').val().trim();
            $('.errors-recovery').addClass('d-none');

            if (!code) {
                $('.errors-recovery').text('Ingresa un código de recuperación.').removeClass('d-none');
                return;
            }

            submitVerify(code);
        });
    });
})(jQuery);
