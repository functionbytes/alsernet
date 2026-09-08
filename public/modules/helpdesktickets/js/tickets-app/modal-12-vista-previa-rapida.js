'use strict';

    // ── Modal 12: Vista previa rápida ─────────────────────────
    // Se abre con la barra espaciadora sobre la fila seleccionada, sin salir
    // de la lista. J/K siguen navegando con la vista previa abierta.
    function openQuickPreviewModal(t) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-eye',
            kicker: 'Ticket · vista rápida',
            title: 'Vista previa rápida',
            titleChip: t.ticket_number,
            width: 'md',
            body: '<div class="tkt-side-rows">' +
                    sideRow('Cliente', t.customer ? t.customer.name : 'Sin cliente') +
                    sideRow('Asunto', t.subject || '(sin asunto)') +
                    sideRow('Estado', t.status_name || t.status_slug || '—', { strong: true }) +
                    sideRow('Prioridad', priorityLabel(t.priority), { strong: true }) +
                    sideRow('SLA', t.sla_text || '—', { mono: true, last: true }) +
                  '</div>' +
                  (t.last_message_snippet
                    ? '<div class="tkt-field"><label class="tkt-label">Último mensaje</label>' +
                      '<div class="tkt-tpl-preview">' + escapeHtml(t.last_message_snippet) + '</div></div>'
                    : '') +
                  '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Navega con <span class="mono">J</span> / <span class="mono">K</span> sin cerrar la vista previa.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-qp-open">Abrir completo</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-qp-reply">Responder</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        $backdrop.on('click', '#tkt-qp-open', function () { closeModal(); selectTicket(t); });
        $backdrop.on('click', '#tkt-qp-reply', function () { closeModal(); selectTicket(t); openComposeModal(t); });
    }

    // Modal de confirmación para reenviar el último correo del ticket —
    // mismos datos que antes mostraba el window.confirm() (destinatario,
    // asunto), solo que en formato modal. 'mail' aquí es last_outbound_mail
    // ({to, subject, url_resend}), no el bloque 'mail' general del ticket.

