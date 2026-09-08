'use strict';

    // ── Modal 05: Detalle de entrega ──────────────────────────
    var TRACE_ICON = { queued: 'fa-inbox', sent: 'fa-paper-plane', delivered: 'fa-circle-check', bounced: 'fa-arrow-rotate-left', failed: 'fa-triangle-exclamation', opened: 'fa-envelope-open', clicked: 'fa-arrow-pointer' };

    function openDeliveryModal(t, mail, trace) {
        var events = trace || [];
        var headline = mail.status === 'delivered' ? 'Entregado al servidor destino'
            : (mail.status === 'bounced' ? 'Rebotado por el servidor destino'
            : (mail.status === 'failed' ? 'No se pudo entregar'
            : (mail.status === 'sent' ? 'Aceptado por el servidor de correo' : 'En cola de envío')));
        var when = mail.delivered_at_human || mail.sent_at_human || mail.created_at_human || '';

        var timeline = events.length
            ? '<div class="tkt-timeline">' + events.map(function (ev) {
                return '<div class="tkt-timeline-item">' +
                    '<div class="tkt-timeline-dot done"><i class="fa-solid ' + (TRACE_ICON[ev.type] || 'fa-circle') + '"></i></div>' +
                    '<div class="tkt-timeline-body">' +
                        '<div class="t">' + escapeHtml(ev.label || ev.type) + '</div>' +
                        '<div class="s mono" title="' + escapeHtml(ev.at || '') + '">' + escapeHtml(ev.at_human || ev.at || '') + '</div>' +
                    '</div></div>';
              }).join('') + '</div>'
            : '<div class="tkt-empty-box">Sin eventos de trazabilidad: este correo no tiene seguimiento asociado en el log de emails.</div>';

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-route',
            kicker: 'Correo · entrega',
            title: 'Detalle de entrega',
            titleChip: t.ticket_number,
            width: 'lg',
            body: '<div class="tkt-headline' + (mail.status === 'bounced' || mail.status === 'failed' ? ' bad' : '') + '">' +
                    '<div class="t">' + escapeHtml(headline) + '</div>' +
                    (when ? '<div class="s mono">' + escapeHtml(when) + '</div>' : '') +
                  '</div>' +
                  (mail.delivery_error ? '<div class="tkt-note"><i class="fa-solid fa-triangle-exclamation"></i> ' + escapeHtml(mail.delivery_error) + '</div>' : '') +
                  '<div class="tkt-cap">Recorrido</div>' + timeline +
                  '<div class="tkt-side-rows">' +
                    sideRow('Message-ID', mail.message_id || '—', { mono: true }) +
                    sideRow('Estado BD', mail.status || '—', { mono: true }) +
                    sideRow('Destinatario', mail.to || '—', { mono: true, last: true }) +
                  '</div>',
            foot: (mail.message_id ? '<button type="button" class="tkt-btn" data-copy="' + escapeHtml(mail.message_id) + '">Copiar Message-ID</button>' : '') +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        $backdrop.on('click', '[data-copy]', function () {
            if (!navigator.clipboard) return;
            navigator.clipboard.writeText($(this).data('copy')).then(function () {
                if (window.toastr) toastr.success('Message-ID copiado');
            });
        });
    }


