/**
 * Contactos 360 — modal "Vínculos e identidad" (#contact-links-modal).
 *
 * Datos: ContactLinksController (vinculadas, historial, permisos, estado de
 * identidad). Identidad: rutas de HelpdeskIntegration (código por email/SMS
 * o confirmación manual), que aplican sus propios límites. Vincular una
 * sugerencia usa contacts.external-link, igual que "Vincular plataforma".
 * Desvincular pide confirmación en el propio botón (sin diálogos del navegador).
 */
(function ($) {
    'use strict';

    var $modal = $('#contact-links-modal');
    if (!$modal.length) {
        return;
    }

    var C = window.Contacts360;
    var csrf = $('meta[name="csrf-token"]').attr('content');
    var state = null;

    function esc(s) {
        return C ? C.esc(s) : $('<span>').text(s == null ? '' : String(s)).html();
    }

    function when(iso, withTime) {
        return C ? C.when(iso, withTime) : (iso || '');
    }

    function toast(type, msg) {
        if (window.toastr) {
            window.toastr[type](msg);
        }
    }

    function errorMessage(xhr, fallback) {
        var r = xhr && xhr.responseJSON;
        if (r && r.errors) {
            var first = Object.keys(r.errors)[0];
            if (first && r.errors[first] && r.errors[first][0]) {
                return r.errors[first][0];
            }
        }
        return (r && r.message) || fallback;
    }

    function post(url, data) {
        return $.ajax({ url: url, method: 'POST', data: data || {}, headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });
    }

    // ── pintado ─────────────────────────────────────────────────────────

    function renderIdentity() {
        var id = state.identity || {};
        var urls = id.urls || {};
        var html = '';

        if (id.verified) {
            var s = id.summary || {};
            var channel = { email: 'por email', sms: 'por SMS', manual: 'por el agente' }[s.channel] || '';
            html = '<div class="c3k-status is-ok">Identidad verificada ' + esc(channel) +
                (s.verified_by ? ' · ' + esc(s.verified_by) : '') +
                (s.expires_at ? ' · válida hasta ' + esc(when(s.expires_at, true)) : '') + '</div>';
            $('#c3k-identity').html(html);
            return;
        }

        html = '<div class="c3k-status">Sin verificar. Verifícala antes de dar datos de pedidos o cuenta por teléfono o chat.</div>';

        var sendBtns = '';
        if (urls.request && id.hasEmail) {
            sendBtns += '<button type="button" class="psc-btn psc-btn--outline" data-c3k-send="email">Enviar código por email</button>';
        }
        if (urls.request && id.smsEnabled && id.hasPhone) {
            sendBtns += '<button type="button" class="psc-btn psc-btn--outline" data-c3k-send="sms">Enviar código por SMS</button>';
        }
        if (sendBtns) {
            html += '<div class="c3k-actions">' + sendBtns + '</div>';
        }
        if (urls.verify) {
            html += '<div class="c3k-code d-none" id="c3k-code-row">' +
                '<label class="visually-hidden" for="c3k-code-input">Código de 6 dígitos</label>' +
                '<input type="text" inputmode="numeric" maxlength="6" class="ct-finput" id="c3k-code-input" placeholder="Código de 6 dígitos" autocomplete="one-time-code">' +
                '<button type="button" class="psc-btn psc-btn--primary" data-c3k-verify>Verificar</button></div>';
        }
        if (urls.verifyManual && state.can && state.can.verifyManual) {
            html += '<button type="button" class="c3-link c3k-manual" data-c3k-manual>Confirmar sin código (ya la has comprobado tú)</button>';
        }
        if (!sendBtns && !(urls.verifyManual && state.can && state.can.verifyManual)) {
            html += '<div class="c3k-muted">El contacto no tiene email ni teléfono para enviarle un código.</div>';
        }

        $('#c3k-identity').html(html);
    }

    function renderLinked() {
        var list = state.integrations || [];
        if (!list.length) {
            $('#c3k-linked').html('<div class="c3k-muted">Ninguna plataforma vinculada. Usa "Vincular plataforma" en el menú del contacto.</div>');
            return;
        }
        $('#c3k-linked').html(list.map(function (i) {
            var meta = [i.externalId ? ('id ' + i.externalId) : null, i.lastSyncedAt ? ('sincronizado ' + when(i.lastSyncedAt, false)) : null].filter(Boolean).join(' · ');
            return '<div class="c3k-row">' +
                '<span class="c3k-row-body"><span class="t">' + esc(i.label) + '</span>' + (meta ? '<span class="s">' + esc(meta) + '</span>' : '') + '</span>' +
                (state.can && state.can.unlink ? '<button type="button" class="psc-btn psc-btn--outline" data-c3k-unlink="' + esc(i.platform) + '">Desvincular</button>' : '') +
                '</div>';
        }).join(''));
    }

    // Acciones que HelpdeskIntegration aún no traduce y llegan en bruto.
    var RAW_ACTIONS = {
        link_failed: 'La vinculación automática no encontró coincidencias',
        unlinked: 'desvinculó una plataforma',
        synced: 'sincronizó',
        sync_failed: 'falló la sincronización'
    };

    function renderHistory() {
        var list = state.history || [];
        if (!list.length) {
            $('#c3k-history').html('<div class="c3k-muted">Sin cambios registrados.</div>');
            return;
        }
        $('#c3k-history').html(list.map(function (h) {
            var summary = RAW_ACTIONS[h.summary] || h.summary;
            return '<div class="c3k-hist"><span class="t">' + esc(summary) + '</span>' +
                '<span class="s">' + esc([h.agent, when(h.created_at, true)].filter(Boolean).join(' · ')) + '</span></div>';
        }).join(''));
    }

    function renderAll() {
        renderIdentity();
        renderLinked();
        renderHistory();
    }

    function loadSuggestions() {
        var linked = (state.integrations || []).map(function (i) { return i.platform; });
        var hasPs = linked.indexOf('prestashop') !== -1;
        var hasErp = linked.indexOf('erp') !== -1;
        // Solo tiene sentido con exactamente una de las dos vinculada.
        if (hasPs === hasErp) {
            $('#c3k-suggest-sec').addClass('d-none');
            return;
        }
        $('#c3k-suggest-sec').removeClass('d-none');
        $('#c3k-suggest').html('<div class="c3k-muted">Buscando la otra plataforma…</div>');

        $.ajax({ url: $modal.data('suggestions-url'), method: 'GET', headers: { 'Accept': 'application/json' } }).done(function (r) {
            var list = (r && Array.isArray(r.data)) ? r.data : [];
            if (!list.length) {
                $('#c3k-suggest').html('<div class="c3k-muted">' + (r && r.unavailable ? 'No se pudo consultar la otra plataforma ahora.' : 'No hay ninguna cuenta que encaje.') + '</div>');
                return;
            }
            $('#c3k-suggest').html(list.map(function (sg) {
                return '<div class="c3k-row is-suggest">' +
                    '<span class="c3k-row-body"><span class="t">' + esc(sg.platformLabel) + ': ' + esc(sg.name || sg.detail) + '</span>' +
                    '<span class="s">' + esc(sg.detail) + '</span><span class="s">' + esc(sg.reason) + '</span></span>' +
                    (state.can && state.can.link ? '<button type="button" class="psc-btn psc-btn--primary" data-c3k-link="' + esc(sg.platform) + '" data-c3k-link-id="' + esc(sg.externalId) + '">Vincular</button>' : '') +
                    '</div>';
            }).join(''));
        }).fail(function () {
            $('#c3k-suggest').html('<div class="c3k-muted">No se pudo consultar la otra plataforma ahora.</div>');
        });
    }

    function load() {
        $('#c3k-identity, #c3k-linked, #c3k-history').html('<div class="ctf-skel-line"></div>');
        return $.ajax({ url: $modal.data('url'), method: 'GET', headers: { 'Accept': 'application/json' } }).done(function (r) {
            state = (r && r.data) || {};
            renderAll();
            loadSuggestions();
        }).fail(function (xhr) {
            $('#c3k-identity').html('<div class="c3k-muted">' + esc(errorMessage(xhr, 'No se pudieron cargar los vínculos.')) + '</div>');
            $('#c3k-linked, #c3k-history').html('');
        });
    }

    // Tras vincular/desvincular: la sección "Fuentes vinculadas" y las fuentes
    // de comercio de la ficha se vuelven a pedir.
    function refreshFicha() {
        if (C && C.reload) {
            ['resumen', 'erp', 'prestashop'].forEach(function (t) { C.reload(t); });
        }
    }

    // ── acciones ────────────────────────────────────────────────────────

    $(document).on('click', '[data-c3k-open]', function () {
        $modal.modal('show');
        load();
    });

    $(document).on('click', '#contact-links-modal [data-c3k-send]', function () {
        var $b = $(this);
        var channel = $b.data('c3k-send');
        $b.prop('disabled', true);
        post(state.identity.urls.request, { channel: channel }).done(function (r) {
            toast('success', (r && r.message) || 'Código enviado.');
            $('#c3k-code-row').removeClass('d-none');
            $('#c3k-code-input').trigger('focus');
        }).fail(function (xhr) {
            toast('error', errorMessage(xhr, 'No se pudo enviar el código.'));
        }).always(function () {
            $b.prop('disabled', false);
        });
    });

    function verifyDone(r) {
        toast('success', 'Identidad verificada.');
        load();
    }

    $(document).on('click', '#contact-links-modal [data-c3k-verify]', function () {
        var code = String($('#c3k-code-input').val() || '').replace(/\D/g, '');
        if (code.length !== 6) {
            toast('warning', 'El código tiene 6 dígitos.');
            return;
        }
        var $b = $(this).prop('disabled', true);
        post(state.identity.urls.verify, { code: code }).done(verifyDone).fail(function (xhr) {
            toast('error', errorMessage(xhr, 'Código incorrecto.'));
        }).always(function () {
            $b.prop('disabled', false);
        });
    });

    $(document).on('keydown', '#c3k-code-input', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#contact-links-modal [data-c3k-verify]').trigger('click');
        }
    });

    $(document).on('click', '#contact-links-modal [data-c3k-manual]', function () {
        var $b = $(this);
        if (!$b.hasClass('is-confirm')) {
            $b.addClass('is-confirm').text('Pulsa otra vez para confirmar que has comprobado su identidad');
            return;
        }
        $b.prop('disabled', true);
        post(state.identity.urls.verifyManual, {}).done(verifyDone).fail(function (xhr) {
            toast('error', errorMessage(xhr, 'No se pudo confirmar la identidad.'));
            $b.prop('disabled', false);
        });
    });

    $(document).on('click', '#contact-links-modal [data-c3k-unlink]', function () {
        var $b = $(this);
        if (!$b.hasClass('is-confirm')) {
            $('#contact-links-modal [data-c3k-unlink].is-confirm').removeClass('is-confirm').text('Desvincular');
            $b.addClass('is-confirm').text('Confirmar');
            return;
        }
        $b.prop('disabled', true);
        post($modal.data('unlink-url'), { platform: $b.data('c3k-unlink') }).done(function (r) {
            toast('success', (r && r.message) || 'Plataforma desvinculada.');
            state = (r && r.data) || state;
            renderAll();
            loadSuggestions();
            refreshFicha();
        }).fail(function (xhr) {
            toast('error', errorMessage(xhr, 'No se pudo desvincular.'));
            $b.prop('disabled', false);
        });
    });

    $(document).on('click', '#contact-links-modal [data-c3k-link]', function () {
        var $b = $(this).prop('disabled', true);
        post($modal.data('link-url'), { platform: $b.data('c3k-link'), external_id: String($b.data('c3k-link-id')) }).done(function (r) {
            toast('success', (r && r.message) || 'Plataforma vinculada.');
            load();
            refreshFicha();
        }).fail(function (xhr) {
            toast('error', errorMessage(xhr, 'No se pudo vincular.'));
            $b.prop('disabled', false);
        });
    });
})(jQuery);
