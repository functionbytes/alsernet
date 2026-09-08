'use strict';

    // ── Modal 07: Reenviar email ──────────────────────────────
    function openResendMailModal(t, mail) {
        var attCount = (mail.attachments || []).length;
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-rotate-right',
            kicker: 'Correo · reenvío',
            title: 'Reenviar email',
            titleChip: t.ticket_number,
            width: 'md',
            body: '<div class="tkt-headline">' +
                    '<div class="t">' + escapeHtml(mail.subject || '(sin asunto)') + '</div>' +
                    '<div class="s">' + escapeHtml(mail.sent_at_human || mail.created_at_human || '') +
                        (attCount ? ' · ' + attCount + (attCount === 1 ? ' adjunto' : ' adjuntos') : '') + '</div>' +
                  '</div>' +
                  '<div class="tkt-field"><label class="tkt-label" for="tkt-resend-to">Destinatario<span class="req">*</span></label>' +
                    '<input type="email" class="tkt-input" id="tkt-resend-to" value="' + escapeHtml(mail.to || '') + '"></div>' +
                  '<div class="tkt-pick-list">' +
                    '<label class="tkt-pick as-option on"><input type="radio" name="tkt-resend-mode" value="asis" checked>' +
                        '<span class="who"><span class="n">Reenviar tal cual</span><span class="s">Misma copia, con sus adjuntos</span></span></label>' +
                    '<label class="tkt-pick as-option"><input type="radio" name="tkt-resend-mode" value="edit">' +
                        '<span class="who"><span class="n">Editar antes de reenviar</span><span class="s">Abre el redactor con este contenido</span></span></label>' +
                    (attCount ? '<label class="tkt-pick as-option"><input type="radio" name="tkt-resend-mode" value="noatt">' +
                        '<span class="who"><span class="n">Reenviar sin adjuntos</span><span class="s">Solo el texto del mensaje</span></span></label>' : '') +
                  '</div>' +
                  '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Se generará un nuevo Message-ID enlazado al hilo original mediante <span class="mono">In-Reply-To</span>.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-resend-confirm">Reenviar ahora</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('change', '[name="tkt-resend-mode"]', function () {
            $backdrop.find('.tkt-pick.as-option').removeClass('on');
            $(this).closest('.tkt-pick').addClass('on');
            $backdrop.find('#tkt-resend-confirm').text($(this).val() === 'edit' ? 'Abrir el redactor' : 'Reenviar ahora');
        });

        $backdrop.on('click', '#tkt-resend-confirm', function () {
            var mode = $backdrop.find('[name="tkt-resend-mode"]:checked').val();
            var to = ($('#tkt-resend-to').val() || '').trim();
            if (!to) {
                if (window.toastr) toastr.error('Indica el destinatario');
                return;
            }

            // "Editar antes de reenviar" no manda nada: abre el redactor ya
            // relleno, y el envío sale por el flujo normal del modal 01.
            if (mode === 'edit') {
                composeDraft = {
                    ticketId: t.id, to: to, cc: '', bcc: '',
                    subject: mail.subject || '', body: mail.body_text || '',
                    from: (TKA.state.senders || [])[0] || '',
                    attachToThread: true, scheduledAt: null, cancelIfReplies: false, files: [],
                };
                closeModal();
                openComposeModal(t);
                return;
            }

            var $btn = $(this).prop('disabled', true).text('Reenviando…');
            $.ajax({
                url: mail.url_resend,
                method: 'POST',
                data: { to: to, without_attachments: mode === 'noatt' ? 1 : 0 },
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Correo reenviado');
                    closeModal();
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo reenviar el correo';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text('Reenviar ahora');
                },
            });
        });
    }


