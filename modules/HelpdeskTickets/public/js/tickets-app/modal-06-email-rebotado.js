'use strict';

    // ── Modal 06: Email rebotado ──────────────────────────────
    function openBounceModal(t, mail) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-arrow-rotate-left',
            iconClass: 'danger',
            kicker: TKA.t('kicker_mail_bounce', 'Correo · rebote'),
            title: TKA.t('modal_title_bounced_email', 'Email rebotado'),
            titleChip: t.ticket_number,
            width: 'md',
            body: '<div class="tkt-headline bad">' +
                    '<div class="t mono">' + escapeHtml(mail.delivery_error || TKA.t('modal_06_bounce_no_detail', 'Rebote sin detalle del servidor')) + '</div>' +
                    '<div class="s">' + TKA.t('modal_06_send_failed_to', 'El envío a :to no llegó a su destino.', { ':to': '<strong>' + escapeHtml(mail.to || '—') + '</strong>' }) + '</div>' +
                  '</div>' +
                  '<div class="tkt-side-rows">' +
                    sideRow(TKA.t('modal_06_subject_label', 'Asunto'), mail.subject || '—') +
                    sideRow(TKA.t('status', 'Estado'), mail.status || '—', { mono: true, last: true }) +
                  '</div>' +
                  '<div class="tkt-field"><label class="tkt-label" for="tkt-bounce-to">' + TKA.t('modal_06_fix_recipient_label', 'Corregir destinatario') + '<span class="req">*</span></label>' +
                    '<input type="email" class="tkt-input" id="tkt-bounce-to" value="' + escapeHtml(mail.to || '') + '"></div>' +
                  '<label class="tkt-check"><input type="checkbox" id="tkt-bounce-contact" checked> ' + TKA.t('modal_06_update_contact_email', 'Actualizar el email del contacto') + '</label>' +
                  '<label class="tkt-check"><input type="checkbox" id="tkt-bounce-suppress" checked> ' + TKA.t('modal_06_add_to_suppression_list', 'Añadir la dirección anterior a la lista de supresión') + '</label>' +
                  '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> ' + TKA.t('modal_06_internal_note_notice', 'Se dejará una nota interna en el ticket con el cambio de destinatario.') + '</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-bounce-confirm">' + TKA.t('btn_fix_and_resend', 'Corregir y reenviar') + '</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>' + TKA.t('discard', 'Descartar') + '</button>',
        }));

        $backdrop.on('click', '#tkt-bounce-confirm', function () {
            var to = ($('#tkt-bounce-to').val() || '').trim();
            if (!to) {
                if (window.toastr) toastr.error(TKA.t('corrected_recipient_required', 'Indica el destinatario corregido'));
                return;
            }
            if (to === mail.to) {
                if (window.toastr) toastr.error(TKA.t('recipient_same_as_bounced', 'El destinatario es el mismo que rebotó: corrígelo antes de reenviar'));
                return;
            }
            var $btn = $(this).prop('disabled', true).text(TKA.t('btn_resending', 'Reenviando…'));
            $.ajax({
                url: mail.url_fix_bounce,
                method: 'POST',
                data: {
                    to: to,
                    update_contact: $('#tkt-bounce-contact').is(':checked') ? 1 : 0,
                    suppress_old: $('#tkt-bounce-suppress').is(':checked') ? 1 : 0,
                },
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || TKA.t('recipient_fixed_and_resent', 'Destinatario corregido y correo reenviado'));
                    closeModal();
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = apiErrorMessage(xhr, 'No se pudo corregir el rebote');
                    tktNotify('error', msg);
                    $btn.prop('disabled', false).text(TKA.t('btn_fix_and_resend', 'Corregir y reenviar'));
                },
            });
        });
    }


