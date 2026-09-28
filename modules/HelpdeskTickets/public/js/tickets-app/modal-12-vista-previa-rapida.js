'use strict';

    // ── Modal 12: Vista previa rápida ─────────────────────────
    // Se abre con la barra espaciadora sobre la fila seleccionada, sin salir
    // de la lista. J/K siguen navegando con la vista previa abierta.
    function openQuickPreviewModal(t) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-eye',
            kicker: TKA.t('kicker_ticket_quick_view', 'Ticket · vista rápida'),
            title: TKA.t('modal_title_quick_preview', 'Vista previa rápida'),
            titleChip: t.ticket_number,
            width: 'md',
            body: '<div class="tkt-side-rows">' +
                    sideRow(TKA.t('modal_12_customer_label', 'Cliente'), t.customer ? t.customer.name : TKA.t('modal_12_no_customer', 'Sin cliente')) +
                    sideRow(TKA.t('modal_12_subject_label', 'Asunto'), t.subject || TKA.t('no_subject', '(sin asunto)')) +
                    sideRow(TKA.t('status', 'Estado'), t.status_name || t.status_slug || '—', { strong: true }) +
                    sideRow(TKA.t('priority', 'Prioridad'), priorityLabel(t.priority), { strong: true }) +
                    sideRow('SLA', t.sla_text || '—', { mono: true, last: true }) +
                  '</div>' +
                  (t.last_message_snippet
                    ? '<div class="tkt-field"><label class="tkt-label">' + TKA.t('modal_12_last_message_label', 'Último mensaje') + '</label>' +
                      '<div class="tkt-tpl-preview">' + escapeHtml(t.last_message_snippet) + '</div></div>'
                    : '') +
                  '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> ' + TKA.t('modal_12_navigate_note', 'Navega con :j / :k sin cerrar la vista previa.', { ':j': '<span class="mono">J</span>', ':k': '<span class="mono">K</span>' }) + '</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-qp-open">' + TKA.t('modal_12_open_full_btn', 'Abrir completo') + '</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-qp-reply">' + TKA.t('modal_12_reply_btn', 'Responder') + '</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>' + TKA.t('close', 'Cerrar') + '</button>',
        }));

        $backdrop.on('click', '#tkt-qp-open', function () { closeModal(); selectTicket(t); });
        $backdrop.on('click', '#tkt-qp-reply', function () { closeModal(); selectTicket(t); openComposeModal(t); });
    }

    // Modal de confirmación para reenviar el último correo del ticket —
    // mismos datos que antes mostraba el window.confirm() (destinatario,
    // asunto), solo que en formato modal. 'mail' aquí es last_outbound_mail
    // ({to, subject, url_resend}), no el bloque 'mail' general del ticket.

