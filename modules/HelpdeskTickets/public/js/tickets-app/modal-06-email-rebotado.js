'use strict';

    // ── Modal 06: Email rebotado ──────────────────────────────
    function openBounceModal(t, mail) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-arrow-rotate-left',
            iconClass: 'danger',
            kicker: 'Correo · rebote',
            title: 'Email rebotado',
            titleChip: t.ticket_number,
            width: 'md',
            body: '<div class="tkt-headline bad">' +
                    '<div class="t mono">' + escapeHtml(mail.delivery_error || 'Rebote sin detalle del servidor') + '</div>' +
                    '<div class="s">El envío a <strong>' + escapeHtml(mail.to || '—') + '</strong> no llegó a su destino.</div>' +
                  '</div>' +
                  '<div class="tkt-side-rows">' +
                    sideRow('Asunto', mail.subject || '—') +
                    sideRow('Estado', mail.status || '—', { mono: true, last: true }) +
                  '</div>' +
                  '<div class="tkt-field"><label class="tkt-label" for="tkt-bounce-to">Corregir destinatario<span class="req">*</span></label>' +
                    '<input type="email" class="tkt-input" id="tkt-bounce-to" value="' + escapeHtml(mail.to || '') + '"></div>' +
                  '<label class="tkt-check"><input type="checkbox" id="tkt-bounce-contact" checked> Actualizar el email del contacto</label>' +
                  '<label class="tkt-check"><input type="checkbox" id="tkt-bounce-suppress" checked> Añadir la dirección anterior a la lista de supresión</label>' +
                  '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Se dejará una nota interna en el ticket con el cambio de destinatario.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-bounce-confirm">Corregir y reenviar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Descartar</button>',
        }));

        $backdrop.on('click', '#tkt-bounce-confirm', function () {
            var to = ($('#tkt-bounce-to').val() || '').trim();
            if (!to) {
                if (window.toastr) toastr.error('Indica el destinatario corregido');
                return;
            }
            if (to === mail.to) {
                if (window.toastr) toastr.error('El destinatario es el mismo que rebotó: corrígelo antes de reenviar');
                return;
            }
            var $btn = $(this).prop('disabled', true).text('Reenviando…');
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
                    if (window.toastr) toastr.success((resp && resp.message) || 'Destinatario corregido y correo reenviado');
                    closeModal();
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = apiErrorMessage(xhr, 'No se pudo corregir el rebote');
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text('Corregir y reenviar');
                },
            });
        });
    }


