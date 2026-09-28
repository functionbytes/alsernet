'use strict';

    // ── Modal 32: Portal del cliente ──────────────────────────
    function openPortalModal(t) {
        var d = TKA.state.currentDetail;
        var thread = (d && d.thread) || [];
        var visible = thread.filter(function (m) { return !m.is_internal; });
        var bubbles = visible.length
            ? visible.map(function (m) {
                var body = String(m.body || '').replace(/<[^>]+>/g, '').trim();
                return '<div class="tkt-portal-msg' + (m.from_agent ? ' agent' : '') + '">' +
                    '<div class="who">' + escapeHtml(m.from_agent ? TKA.t('modal_32_support_label', 'Soporte') : (t.customer ? t.customer.name : TKA.t('modal_32_customer_label', 'Cliente'))) +
                        ' · ' + escapeHtml(m.created_at_human || '') + '</div>' +
                    '<div class="body">' + escapeHtml(body || TKA.t('modal_32_no_text', '(sin texto)')) + '</div></div>';
              }).join('')
            : '<div class="tkt-empty-box">' + TKA.t('modal_32_customer_sees_no_messages', 'El cliente todavía no vería ningún mensaje en este ticket.') + '</div>';
        var hidden = thread.length - visible.length;

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-window-maximize', kicker: TKA.t('kicker_portal_customer_view', 'Portal · vista cliente'),
            title: TKA.t('modal_title_customer_portal', 'Portal del cliente'), titleChip: t.ticket_number, width: 'lg',
            body: '<div class="tkt-portal"><div class="tkt-portal-head">' + TKA.t('modal_32_ticket_prefix', 'Ticket :number', { ':number': escapeHtml(t.ticket_number) }) + ' · ' + escapeHtml(t.subject || '') + '</div>' + bubbles + '</div>' +
                  (hidden > 0 ? '<div class="tkt-note"><i class="fa-solid fa-eye-slash"></i> ' + (hidden === 1
                        ? TKA.t('modal_32_hidden_notes_singular', ':n nota interna no se muestra al cliente.', { ':n': hidden })
                        : TKA.t('modal_32_hidden_notes_plural', ':n notas internas no se muestran al cliente.', { ':n': hidden })) + '</div>' : ''),
            foot: (t.url_shared_ticket ? '<a class="tkt-btn tkt-btn-primary" href="' + escapeHtml(t.url_shared_ticket) + '" target="_blank" rel="noopener">' + TKA.t('modal_32_open_real_view', 'Abrir la vista real') + '</a>' : '') +
                  (t.url_shared_ticket ? '<button type="button" class="tkt-btn" id="tkt-portal-copy" data-url="' + escapeHtml(t.url_shared_ticket) + '">' + TKA.t('modal_32_copy_link', 'Copiar enlace') + '</button>' : '') +
                  (t.customer && t.customer.email ? '<button type="button" class="tkt-btn" id="tkt-portal-send-access">' + TKA.t('btn_send_customer_access', 'Enviar acceso al cliente') + '</button>' : '') +
                  '<button type="button" class="tkt-btn" data-modal-close>' + TKA.t('close', 'Cerrar') + '</button>',
        }));

        $backdrop.on('click', '#tkt-portal-copy', function () {
            tktCopyToClipboard($(this).data('url'), TKA.t('modal_32_link_copied', 'Enlace copiado'));
        });

        // "Enviar acceso al cliente": mismo enlace mágico de un solo uso que
        // ya manda el login del portal (CustomerPortalController::login()),
        // no el enlace de solo lectura de "Copiar enlace" — ese es un link
        // firmado sin sesión, este crea una sesión real del cliente.
        $backdrop.on('click', '#tkt-portal-send-access', function () {
            var $btn = $(this).prop('disabled', true).text(TKA.t('btn_sending', 'Enviando…'));
            $.post(t.url_portal_send_access).done(function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || TKA.t('access_sent', 'Acceso enviado'));
            }).fail(function (xhr) {
                var msg = apiErrorMessage(xhr, TKA.t('modal_32_send_access_failed', 'No se pudo enviar el acceso.'));
                tktNotify('error', msg);
            }).always(function () {
                $btn.prop('disabled', false).text(TKA.t('btn_send_customer_access', 'Enviar acceso al cliente'));
            });
        });
    }

    // Los modales 16/28/29/30 leen la misma foto de configuración: se pide
    // una vez y se guarda, en vez de cuatro veces seguidas al abrirlos.
    var settingsSnapshot = null;

    function withSettings(cb) {
        if (settingsSnapshot) { cb(settingsSnapshot); return; }
        if (!TKA.urls.settingsSnapshot) { cb(null); return; }
        $.getJSON(TKA.urls.settingsSnapshot).done(function (d) { settingsSnapshot = d; cb(d); }).fail(function () { cb(null); });
    }


