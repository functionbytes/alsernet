/**
 * verify-customer-identity.js — HelpdeskIntegration module
 *
 * Modal estandalone: verificacion de identidad del cliente (OTP).
 * Reutilizable desde cualquier punto de la app via
 * window.openCustomerIdentityVerification(customerId, onVerified).
 * Reemplaza el gate que antes vivia embebido dentro de
 * modals/customer-integrations.blade.php — mismos endpoints, mismo
 * servicio (CustomerIdentityVerificationService), UX enriquecida segun
 * el mockup Alvarez #51 (intentos limitados, cronometro de reenvio,
 * cuenta atras de caducidad, banner de "ya validada").
 *
 * User-facing strings come from window.HelpdeskIntegrationLang, emitted by
 * the customer-integrations and verify-customer-identity Blade partials
 * (`__('helpdeskintegration::messages.js')`). Hardcoded Spanish fallbacks
 * keep the file working if that script is absent.
 */
(function () {
    'use strict';

    var L = window.HelpdeskIntegrationLang || {};

    function t(key, fallback, repl) {
        var s = L[key] || fallback;
        if (repl) { Object.keys(repl).forEach(function (k) { s = s.replace(':' + k, repl[k]); }); }
        return s;
    }

    var S = null;
    var resendTimer = null, blockedTimer = null, expiryTimer = null;

    function base() { return '/panel/helpdesk/customers/' + S.customerId; }
    function esc(s) { return HDCommerce.esc ? HDCommerce.esc(s) : $('<div>').text(s == null ? '' : s).html(); }

    function fmt(seconds) {
        seconds = Math.max(0, seconds);
        var m = Math.floor(seconds / 60);
        var s = seconds % 60;
        return m + ':' + (s < 10 ? '0' : '') + s;
    }

    function channelLabel(channel) {
        if (channel === 'sms') { return t('channel_sms', 'SMS'); }
        if (channel === 'manual') { return t('verify_manual_method', 'Marcar como verificado manualmente'); }
        return t('channel_email', 'email');
    }

    function clearTimers() {
        clearInterval(resendTimer); resendTimer = null;
        clearInterval(blockedTimer); blockedTimer = null;
        clearInterval(expiryTimer); expiryTimer = null;
    }

    // ── Punto de entrada publico ────────────────────────────────────────────

    window.openCustomerIdentityVerification = function (customerId, onVerified) {
        if (!customerId) { return; }
        S = { customerId: customerId, onVerified: typeof onVerified === 'function' ? onVerified : function () {}, channel: 'email', smsEnabled: false, customerName: '', hasEmail: false, linkablePlatforms: [] };
        HDCommerce.open('verify-customer-identity');
        loadStatus();
    };

    function loadStatus() {
        renderLoading(t('verify_identity_title', 'Verificar identidad'), t('loading', 'Cargando…'));
        HDCommerce.ajax({ url: base() + '/integrations', method: 'GET' })
            .done(function (resp) {
                var identity = resp.identity || {};
                S.smsEnabled = !!identity.sms_enabled;
                S.customerName = (resp.customer && resp.customer.name) || '';
                S.hasEmail = !!(resp.customer && resp.customer.email);
                S.channel = S.hasEmail ? 'email' : (S.smsEnabled ? 'sms' : 'manual');
                // Catalogo de plataformas: llega también sin verificar (no es dato
                // del cliente) para poder ofrecer el buscador de PrestaShop/ERP.
                S.linkablePlatforms = resp.linkable_platforms || [];
                if (identity.verified) {
                    renderAlready(identity);
                } else {
                    renderMethod();
                }
            })
            .fail(function () { renderMethod(); });
    }

    function renderLoading(title, msg) {
        clearTimers();
        $('#viTitle').text(title);
        $('#viBody').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> ' + esc(msg) + '</div>');
        $('#viFoot').html('');
    }

    // ── Ya validada ──────────────────────────────────────────────────────────

    function renderAlready(identity) {
        clearTimers();
        $('#viTitle').text(t('already_validated_title', 'Identidad ya validada'));
        var mins = Math.max(1, Math.round((new Date(identity.expires_at).getTime() - Date.now()) / 60000));
        $('#viBody').html(
            '<div class="bv-vi-banner">' +
                '<div class="ic"><i class="fas fa-circle-check"></i></div>' +
                '<div class="b">' +
                    '<div class="t">' + t('identity_validated', 'Identidad validada') + '</div>' +
                    '<div class="s">' + t('validated_by_detail', 'Validada por :agent vía :channel · caduca en :mins min', {
                        agent: esc(identity.verified_by || t('system_label', 'Sistema')),
                        channel: channelLabel(identity.channel),
                        mins: mins,
                    }) + '</div>' +
                '</div>' +
            '</div>' +
            '<div class="vi-lead">' + t('already_validated_lead', 'La identidad de <strong>:name</strong> ya fue verificada recientemente. Puedes cerrar esta ventana sin repetir el proceso.', { name: esc(S.customerName) }) + '</div>'
        );
        $('#viFoot').html(
            '<button class="btn-secondary w-100 mb-2" id="viRevalidate" type="button">' + t('revalidate_button', 'Volver a validar') + '</button>' +
            '<button class="btn-secondary w-100" data-bv-close>' + t('close_button', 'Cerrar') + '</button>'
        );
    }

    $(document).on('click', '#viRevalidate', renderMethod);

    // ── Seleccionar metodo ───────────────────────────────────────────────────

    function renderMethod() {
        clearTimers();
        $('#viTitle').text(t('choose_method_title', 'Elige el método de verificación'));

        var isManual = S.channel === 'manual';
        var leadText = isManual
            ? t('manual_verify_lead', 'Vas a marcar la identidad como verificada sin enviar ningún código. Confirma que has verificado la identidad del cliente por otros medios (documento, contexto de la conversación, etc.).')
            : t('choose_method_lead', 'Enviaremos un código de un solo uso al cliente por el canal que elijas.');
        var noticeHtml = isManual
            ? '<div class="minfo danger"><i class="fas fa-triangle-exclamation"></i><div>' + t('manual_verify_warning', 'Esta acción queda registrada con tu usuario y la fecha/hora.') + '</div></div>'
            : '<div class="minfo"><i class="fas fa-lock"></i><div>' + t('hidden_data_notice', 'Los datos completos del cliente permanecen ocultos hasta validar la identidad.') + '</div></div>';

        $('#viBody').html(
            '<div class="vi-lead">' + leadText + '</div>' +
            '<div class="bv-vi-methods">' +
                methodBtn('email', t('send_email_method', 'Enviar código por email') + (S.hasEmail ? '' : t('no_email_suffix', ' (sin email)')), ! S.hasEmail) +
                methodBtn('sms', t('send_sms_method', 'Enviar código por SMS') + (S.smsEnabled ? '' : t('coming_soon_suffix', ' (próximamente)')), ! S.smsEnabled) +
                methodBtn('manual', t('verify_manual_method', 'Marcar como verificado manualmente'), false) +
            '</div>' +
            noticeHtml +
            '<div class="bv-vi-actions-row">' +
                '<button type="button" class="bv-vi-linkbtn" id="viOpenLinkCustomer">' + t('open_link_customer_button', '¿Es otro cliente? Buscar y reasignar') + '</button>' +
                (S.linkablePlatforms.length
                    ? '<button type="button" class="bv-vi-linkbtn" id="viOpenPlatformSearch">' + t('open_platform_search_button', 'Buscar en PrestaShop / ERP') + '</button>'
                    : '') +
            '</div>'
        );
        $('#viFoot').html(
            '<button class="btn-primary w-100 mb-2" id="viSend" type="button">' + (isManual ? t('confirm_manual_button', 'Confirmar identidad verificada') : t('send_code_button', 'Enviar código')) + '</button>' +
            '<button class="btn-secondary w-100" data-bv-close>' + t('cancel', 'Cancelar') + '</button>'
        );
    }

    $(document).on('click', '#viOpenLinkCustomer', function () {
        HDCommerce.close('verify-customer-identity');
        if (typeof window.openLinkCustomerModal === 'function') { window.openLinkCustomerModal(); }
    });

    // ── Buscar en plataformas (PrestaShop/ERP) — solo consulta, no vincula ──
    // Disponible antes de verificar identidad: el catálogo de plataformas no
    // es dato del cliente, y esta búsqueda no persiste nada — ayuda al agente
    // a confirmar que está hablando con el cliente correcto antes de elegir
    // el canal del código o de marcarlo como verificado manualmente.

    $(document).on('click', '#viOpenPlatformSearch', renderPlatformSearch);

    function renderPlatformSearch() {
        clearTimers();
        $('#viTitle').text(t('platform_search_title', 'Buscar en plataformas'));
        $('#viBody').html(
            '<div class="vi-lead">' + t('platform_search_lead', 'Busca al cliente en las plataformas conectadas para confirmar sus datos. Esto no vincula nada, solo consulta.') + '</div>' +
            '<div class="field">' +
                '<div class="search-field">' +
                    '<i class="fas fa-magnifying-glass"></i>' +
                    '<input type="text" id="viPlatformSearchQ" placeholder="' + t('search_placeholder', 'Email, teléfono o identificador…') + '">' +
                '</div>' +
            '</div>' +
            '<div id="viPlatformSearchResults">' +
                '<div class="bv-oc-empty"><i class="fas fa-magnifying-glass"></i><div>' + t('search_prompt', 'Introduce un email, teléfono o identificador para buscar al cliente.') + '</div></div>' +
            '</div>'
        );
        $('#viFoot').html(
            '<button class="btn-secondary w-100" id="viPlatformSearchBack" type="button">' + t('back_button', 'Volver') + '</button>'
        );
        setTimeout(function () { $('#viPlatformSearchQ').trigger('focus'); }, 40);
    }

    $(document).on('click', '#viPlatformSearchBack', renderMethod);

    function guessPlatformSearchType(q) {
        if (q.indexOf('@') !== -1) { return 'email'; }
        if (/^[0-9\s+()-]+$/.test(q)) { return 'phone'; }
        return 'name';
    }

    function renderPlatformSearchResults(results, failedPlatforms) {
        failedPlatforms = failedPlatforms || [];

        var failNote = failedPlatforms.length
            ? '<div class="minfo danger"><i class="fas fa-triangle-exclamation"></i>' +
              '<div>' + t('platforms_no_response_prefix', 'No respondieron: ') + '<strong>' + failedPlatforms.map(esc).join(', ') + '</strong>' + t('platforms_no_response_suffix', '. Los resultados pueden estar incompletos; inténtalo de nuevo en unos minutos.') + '</div></div>'
            : '';

        if (!results.length) {
            if (failedPlatforms.length) {
                $('#viPlatformSearchResults').html(
                    failNote +
                    '<div class="bv-oc-empty"><i class="fas fa-plug-circle-exclamation"></i>' +
                    '<div class="title">' + t('search_incomplete_title', 'No se pudo completar la búsqueda') + '</div>' +
                    '<div>' + t('search_incomplete_body', 'Las plataformas indicadas no respondieron. No significa que el cliente no exista.') + '</div>' +
                    '</div>'
                );
                return;
            }

            $('#viPlatformSearchResults').html(
                '<div class="bv-oc-empty"><i class="fas fa-user-slash"></i>' +
                '<div class="title">' + t('no_results_title', 'Sin resultados') + '</div></div>'
            );
            return;
        }

        var html = results.map(function (r) {
            var icoStyle = r.color ? ' style="color:' + esc(r.color) + '"' : '';
            var badgeStyle = r.color ? ' style="color:' + esc(r.color) + ';border-color:' + esc(r.color) + '"' : '';
            return '<div class="bv-intg-row">' +
                '<div class="ico"' + icoStyle + '><i class="' + esc(r.icon || 'fas fa-plug') + '"></i></div>' +
                '<div class="meta">' +
                    '<span class="name">' + esc(r.name || '—') + ' <span class="badge bg-white border ms-1"' + badgeStyle + '>' + esc(r.platformLabel || '') + '</span></span>' +
                    '<span class="det">' + esc(r.email || r.meta || '') + '</span>' +
                '</div>' +
            '</div>';
        }).join('');

        $('#viPlatformSearchResults').html(failNote + html);
    }

    function doPlatformSearch(q, type) {
        $('#viPlatformSearchResults').html('<div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> ' + t('searching_all_platforms', 'Buscando en todas las plataformas…') + '</div>');

        var searches = S.linkablePlatforms.map(function (p) {
            return HDCommerce.ajax({
                url: base() + '/integrations/search',
                method: 'GET',
                data: { platform: p.platform, q: q, type: type },
            }).then(function (resp) {
                if (resp.platform_error) { return { failed: p.label, results: [] }; }

                return {
                    results: (resp.results || []).map(function (r) {
                        r.platform = p.platform;
                        r.platformLabel = p.label;
                        r.icon = p.icon;
                        r.color = p.color;
                        return r;
                    }),
                };
            }, function () { return { failed: p.label, results: [] }; });
        });

        Promise.all(searches).then(function (outcomes) {
            var results = [], failed = [];
            outcomes.forEach(function (o) {
                results = results.concat(o.results);
                if (o.failed) { failed.push(o.failed); }
            });
            renderPlatformSearchResults(results, failed);
        });
    }

    var viPlatformSearchDebounce = null;
    $(document).on('input', '#viPlatformSearchQ', function () {
        clearTimeout(viPlatformSearchDebounce);
        var q = $.trim($(this).val());
        if (q.length < 2) {
            $('#viPlatformSearchResults').html('<div class="bv-oc-empty"><i class="fas fa-magnifying-glass"></i><div>' + t('search_prompt', 'Introduce un email, teléfono o identificador para buscar al cliente.') + '</div></div>');
            return;
        }
        viPlatformSearchDebounce = setTimeout(function () { doPlatformSearch(q, guessPlatformSearchType(q)); }, 650);
    });
    $(document).on('keydown', '#viPlatformSearchQ', function (e) {
        var q = $.trim($(this).val());
        if (e.key === 'Enter' && q.length >= 2) {
            clearTimeout(viPlatformSearchDebounce);
            doPlatformSearch(q, guessPlatformSearchType(q));
        }
    });

    function methodBtn(value, label, disabled) {
        var on = S.channel === value;
        return '<button type="button" class="bv-vi-method' + (on ? ' on' : '') + '" data-channel="' + value + '"' + (disabled ? ' disabled' : '') + '>' +
            '<span class="t">' + esc(label) + '</span>' +
            '<span class="radio"></span>' +
        '</button>';
    }

    $(document).on('click', '.bv-vi-method', function () {
        if ($(this).prop('disabled')) { return; }
        S.channel = $(this).data('channel');
        renderMethod();
    });

    $(document).on('click', '#viSend', function () {
        if (S.channel === 'manual') {
            renderLoading(t('verifying_manual', 'Confirmando…'), t('verifying_manual', 'Confirmando…'));
            HDCommerce.ajax({
                url: base() + '/identity/verify-manual',
                method: 'POST',
                data: JSON.stringify({ conversation_id: (window.HDCommerce && HDCommerce.conversationId && HDCommerce.conversationId()) || null }),
                contentType: 'application/json',
            })
                .done(function (resp) { renderSuccess(resp); })
                .fail(function (xhr) {
                    toastr.error(HDCommerce.errorMessage(xhr, t('manual_verify_failed', 'No se pudo confirmar la verificación manual.')));
                    renderMethod();
                });
            return;
        }

        renderLoading(
            t('sending_code_title', 'Enviando código…'),
            t('sending_code_message', 'Enviando código por :channel…', { channel: S.channel === 'sms' ? t('channel_sms', 'SMS') : t('channel_email', 'email') })
        );
        HDCommerce.ajax({
            url: base() + '/identity/request',
            method: 'POST',
            data: JSON.stringify({ channel: S.channel }),
            contentType: 'application/json',
        })
            .done(function () { renderCode(); })
            .fail(function (xhr) {
                toastr.error(HDCommerce.errorMessage(xhr, t('send_code_failed', 'No se pudo enviar el código.')));
                renderMethod();
            });
    });

    // ── Introducir codigo (6 casillas) ──────────────────────────────────────

    function renderCode() {
        clearTimers();
        $('#viTitle').text(t('enter_code_title', 'Introduce el código'));
        var boxes = '';
        for (var i = 0; i < 6; i++) {
            boxes += '<input class="bv-vi-otp-input" data-otp="' + i + '" maxlength="1" inputmode="numeric" autocomplete="one-time-code" aria-label="' + t('digit_aria_label', 'Dígito :n de 6', { n: i + 1 }) + '">';
        }
        $('#viBody').html(
            '<div class="vi-lead">' + t('code_sent_lead', 'Hemos enviado un código de 6 dígitos por <strong>:channel</strong>. Pídeselo al cliente e introdúcelo aquí.', { channel: S.channel === 'sms' ? t('channel_sms', 'SMS') : t('channel_email', 'email') }) + '</div>' +
            '<div class="bv-vi-otp-row">' + boxes + '</div>' +
            '<div class="d-flex align-items-center justify-content-between">' +
                '<span class="bv-vi-linkbtn muted" id="viResendLabel">' + t('resend_in', 'Reenviar código en :time', { time: '0:30' }) + '</span>' +
                '<button type="button" class="bv-vi-linkbtn" id="viChangeMethod">' + t('change_method_button', 'Cambiar método') + '</button>' +
            '</div>'
        );
        $('#viFoot').html(
            '<button class="btn-primary w-100 mb-2" id="viVerify" type="button" disabled>' + t('validate_code_button', 'Validar código') + '</button>' +
            '<button class="btn-secondary w-100" data-bv-close>' + t('cancel', 'Cancelar') + '</button>'
        );
        wireOtp();
        startResendTimer();
        setTimeout(function () { $('.bv-vi-otp-input[data-otp="0"]').trigger('focus'); }, 40);
    }

    $(document).on('click', '#viChangeMethod', renderMethod);

    function getCode() {
        return $('.bv-vi-otp-input').map(function () { return this.value; }).get().join('');
    }

    function syncVerifyBtn() {
        $('#viVerify').prop('disabled', getCode().length !== 6);
    }

    function wireOtp() {
        var $inputs = $('.bv-vi-otp-input');
        $inputs.on('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 1);
            $(this).removeClass('err');
            var idx = parseInt($(this).data('otp'), 10);
            if (this.value && idx < 5) { $inputs.eq(idx + 1).trigger('focus'); }
            syncVerifyBtn();
        });
        $inputs.on('keydown', function (e) {
            var idx = parseInt($(this).data('otp'), 10);
            if (e.key === 'Backspace' && ! this.value && idx > 0) { $inputs.eq(idx - 1).trigger('focus'); }
            if (e.key === 'Enter' && getCode().length === 6) { doVerify(); }
        });
        $inputs.on('paste', function (e) {
            e.preventDefault();
            var clip = e.originalEvent && e.originalEvent.clipboardData ? e.originalEvent.clipboardData : window.clipboardData;
            var digits = ((clip && clip.getData('text')) || '').replace(/[^0-9]/g, '').slice(0, 6);
            digits.split('').forEach(function (ch, i) { $inputs.eq(i).val(ch); });
            $inputs.eq(Math.min(digits.length, 5)).trigger('focus');
            syncVerifyBtn();
        });
    }

    function startResendTimer() {
        var remain = 30;
        function tick() {
            var $label = $('#viResendLabel');
            if (! $label.length) { clearInterval(resendTimer); resendTimer = null; return; }
            if (remain > 0) {
                $label.text(t('resend_in', 'Reenviar código en :time', { time: fmt(remain) }));
                remain--;
            } else {
                clearInterval(resendTimer); resendTimer = null;
                $label.removeClass('muted').attr('id', 'viResendActive').text(t('resend_code_button', 'Reenviar código'));
            }
        }
        tick();
        resendTimer = setInterval(tick, 1000);
    }

    $(document).on('click', '#viResendActive', function () {
        HDCommerce.ajax({
            url: base() + '/identity/request',
            method: 'POST',
            data: JSON.stringify({ channel: S.channel }),
            contentType: 'application/json',
        })
            .done(function () {
                toastr.success(t('code_resent', 'Código reenviado.'));
                renderCode();
            })
            .fail(function (xhr) {
                toastr.error(HDCommerce.errorMessage(xhr, t('resend_failed', 'No se pudo reenviar el código.')));
            });
    });

    $(document).on('click', '#viVerify', doVerify);

    function doVerify() {
        var code = getCode();
        if (code.length !== 6) { return; }

        renderLoading(t('validating_code', 'Validando código…'), t('validating_code', 'Validando código…'));

        HDCommerce.ajax({
            url: base() + '/identity/verify',
            method: 'POST',
            data: JSON.stringify({
                code: code,
                conversation_id: (window.HDCommerce && HDCommerce.conversationId && HDCommerce.conversationId()) || null,
            }),
            contentType: 'application/json',
        })
            .done(function (resp) { renderSuccess(resp); })
            .fail(function (xhr) {
                if (xhr.status === 429) {
                    var j = xhr.responseJSON || {};
                    renderBlocked(j.locked_seconds || 60);
                    return;
                }
                var j2 = xhr.responseJSON || {};
                renderError(j2.attempts_made || null, j2.max_attempts || 3);
            });
    }

    // ── Error / bloqueo ──────────────────────────────────────────────────────

    function renderError(attemptsMade, maxAttempts) {
        clearTimers();
        $('#viTitle').text(t('invalid_code_title', 'Código incorrecto'));
        var attemptsLine = attemptsMade
            ? t('attempts_made', 'Intento :made de :max.', { made: attemptsMade, max: maxAttempts }) + ' ' +
              t('attempts_lockout_warning', 'Tras :max intentos fallidos la validación se bloqueará temporalmente.', { max: maxAttempts })
            : '';
        $('#viBody').html(
            '<div class="bv-vi-ring bad"><i class="fas fa-circle-xmark"></i></div>' +
            '<div class="bv-vi-center">' +
                '<div class="h">' + t('invalid_code_heading', 'El código no es válido') + '</div>' +
                '<div class="p">' + t('invalid_code_body', 'El código introducido es incorrecto o ha caducado.<br>Puedes reintentarlo, reenviar uno nuevo o cambiar de método.') + '</div>' +
            '</div>' +
            (attemptsMade
                ? '<div class="minfo danger"><i class="fas fa-triangle-exclamation"></i><div>' + attemptsLine + '</div></div>'
                : '')
        );
        $('#viFoot').html(
            '<button class="btn-primary w-100 mb-2" id="viRetryCode" type="button">' + t('retry', 'Reintentar') + '</button>' +
            '<button class="btn-secondary w-100 mb-2" id="viResendFromError" type="button">' + t('resend_code_button', 'Reenviar código') + '</button>' +
            '<button class="btn-secondary w-100" id="viChangeMethodFromError" type="button">' + t('change_method_button', 'Cambiar método') + '</button>'
        );
    }

    $(document).on('click', '#viRetryCode', renderCode);
    $(document).on('click', '#viChangeMethodFromError', renderMethod);
    $(document).on('click', '#viResendFromError', function () {
        HDCommerce.ajax({
            url: base() + '/identity/request',
            method: 'POST',
            data: JSON.stringify({ channel: S.channel }),
            contentType: 'application/json',
        })
            .done(function () {
                toastr.success(t('code_resent', 'Código reenviado.'));
                renderCode();
            })
            .fail(function (xhr) {
                toastr.error(HDCommerce.errorMessage(xhr, t('resend_failed', 'No se pudo reenviar el código.')));
            });
    });

    function renderBlocked(seconds) {
        clearTimers();
        $('#viTitle').text(t('blocked_title', 'Validación bloqueada'));
        $('#viBody').html(
            '<div class="bv-vi-ring bad"><i class="fas fa-lock"></i></div>' +
            '<div class="bv-vi-center">' +
                '<div class="h">' + t('too_many_attempts_heading', 'Demasiados intentos fallidos') + '</div>' +
                '<div class="p">' + t('blocked_body', 'Por seguridad, la validación se ha bloqueado temporalmente.') + '</div>' +
            '</div>' +
            '<div class="minfo danger"><i class="fas fa-triangle-exclamation"></i><div>' + t('retry_in', 'Podrás reintentar en <strong id="viCooldown">:time</strong>.', { time: esc(fmt(seconds)) }) + '</div></div>'
        );
        $('#viFoot').html(
            '<button class="btn-primary w-100 mb-2" id="viUnblock" type="button" disabled>' + t('retry', 'Reintentar') + '</button>' +
            '<button class="btn-secondary w-100" data-bv-close>' + t('close_button', 'Cerrar') + '</button>'
        );

        var remain = seconds;
        blockedTimer = setInterval(function () {
            remain--;
            var $el = $('#viCooldown');
            if (! $el.length) { clearInterval(blockedTimer); blockedTimer = null; return; }
            if (remain <= 0) {
                clearInterval(blockedTimer); blockedTimer = null;
                $el.text(t('now_label', 'ahora'));
                $('#viUnblock').prop('disabled', false);
            } else {
                $el.text(fmt(remain));
            }
        }, 1000);
    }

    $(document).on('click', '#viUnblock', renderMethod);

    // ── Exito ────────────────────────────────────────────────────────────────

    function renderSuccess(resp) {
        clearTimers();
        $('#viTitle').text(t('identity_validated', 'Identidad validada'));
        var identity = resp.identity || {};
        var conversationRow = identity.conversation_id
            ? '<div class="lbl">' + t('conversation_label', 'Conversación') + '</div><div class="val mono">#' + esc(identity.conversation_id) + '</div>'
            : '';
        $('#viBody').html(
            '<div class="bv-vi-ring ok"><i class="fas fa-circle-check"></i></div>' +
            '<div class="bv-vi-center">' +
                '<div class="h">' + t('identity_verified_heading', 'Identidad verificada') + '</div>' +
                '<div class="p">' + t('identity_verified_body', 'La identidad de <strong>:name</strong> se ha verificado correctamente.', { name: esc(S.customerName) }) + '</div>' +
            '</div>' +
            '<div class="field">' +
                '<div class="flabel" style="font-size:9.5px;text-transform:uppercase;letter-spacing:.06em">' + t('validation_record_label', 'Registro de la validación') + '</div>' +
                '<div class="info-table">' +
                    '<div class="lbl">' + t('validated_label', 'Validado') + '</div><div class="val">' + esc(identity.verified_at ? new Date(identity.verified_at).toLocaleString() : t('dash', '—')) + '</div>' +
                    '<div class="lbl">' + t('method_label', 'Método') + '</div><div class="val">' + (identity.channel === 'sms' ? t('channel_sms', 'SMS') : (identity.channel === 'manual' ? t('verify_manual_method', 'Marcar como verificado manualmente') : t('channel_email_label', 'Email'))) + '</div>' +
                    '<div class="lbl">' + t('agent_label', 'Agente') + '</div><div class="val">' + esc(identity.verified_by || t('dash', '—')) + '</div>' +
                    '<div class="lbl">' + t('expires_label', 'Caduca') + '</div><div class="val" id="viExpiry">' + t('dash', '—') + '</div>' +
                    conversationRow +
                '</div>' +
            '</div>'
        );
        $('#viFoot').html('<button class="btn-primary w-100" data-bv-close>' + t('close_button', 'Cerrar') + '</button>');

        if (identity.expires_at) { startExpiry(identity.expires_at); }

        S.onVerified(resp);
    }

    function startExpiry(expiresAtIso) {
        var target = new Date(expiresAtIso).getTime();
        function tick() {
            var $el = $('#viExpiry');
            if (! $el.length) { clearInterval(expiryTimer); expiryTimer = null; return; }
            var remain = Math.round((target - Date.now()) / 1000);
            if (remain <= 0) {
                clearInterval(expiryTimer); expiryTimer = null;
                $el.text(t('expired_label', 'Caducado'));
                return;
            }
            $el.text(t('expires_in', 'en :time', { time: fmt(remain) }));
        }
        tick();
        expiryTimer = setInterval(tick, 1000);
    }
})();
