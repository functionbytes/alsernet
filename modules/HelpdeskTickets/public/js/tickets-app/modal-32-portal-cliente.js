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
                    '<div class="who">' + escapeHtml(m.from_agent ? 'Soporte' : (t.customer ? t.customer.name : 'Cliente')) +
                        ' · ' + escapeHtml(m.created_at_human || '') + '</div>' +
                    '<div class="body">' + escapeHtml(body || '(sin texto)') + '</div></div>';
              }).join('')
            : '<div class="tkt-empty-box">El cliente todavía no vería ningún mensaje en este ticket.</div>';
        var hidden = thread.length - visible.length;

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-window-maximize', kicker: 'Portal · vista cliente',
            title: 'Portal del cliente', titleChip: t.ticket_number, width: 'lg',
            body: '<div class="tkt-portal"><div class="tkt-portal-head">Ticket ' + escapeHtml(t.ticket_number) + ' · ' + escapeHtml(t.subject || '') + '</div>' + bubbles + '</div>' +
                  (hidden > 0 ? '<div class="tkt-note"><i class="fa-solid fa-eye-slash"></i> ' + hidden + (hidden === 1 ? ' nota interna' : ' notas internas') + ' no se muestran al cliente.</div>' : ''),
            foot: (t.url_shared_ticket ? '<a class="tkt-btn tkt-btn-primary" href="' + escapeHtml(t.url_shared_ticket) + '" target="_blank" rel="noopener">Abrir la vista real</a>' : '') +
                  (t.url_shared_ticket ? '<button type="button" class="tkt-btn" id="tkt-portal-copy" data-url="' + escapeHtml(t.url_shared_ticket) + '">Copiar enlace</button>' : '') +
                  (t.customer && t.customer.email ? '<button type="button" class="tkt-btn" id="tkt-portal-send-access">Enviar acceso al cliente</button>' : '') +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        $backdrop.on('click', '#tkt-portal-copy', function () {
            tktCopyToClipboard($(this).data('url'), 'Enlace copiado');
        });

        // "Enviar acceso al cliente": mismo enlace mágico de un solo uso que
        // ya manda el login del portal (CustomerPortalController::login()),
        // no el enlace de solo lectura de "Copiar enlace" — ese es un link
        // firmado sin sesión, este crea una sesión real del cliente.
        $backdrop.on('click', '#tkt-portal-send-access', function () {
            var $btn = $(this).prop('disabled', true).text('Enviando…');
            $.post(t.url_portal_send_access).done(function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Acceso enviado');
            }).fail(function (xhr) {
                var msg = apiErrorMessage(xhr, 'No se pudo enviar el acceso.');
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            }).always(function () {
                $btn.prop('disabled', false).text('Enviar acceso al cliente');
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


