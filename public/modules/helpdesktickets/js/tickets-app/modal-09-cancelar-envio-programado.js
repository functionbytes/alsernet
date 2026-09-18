'use strict';

    // ── Modal 09: Cancelar envío programado ───────────────────
    function openCancelScheduledModal(t, mail) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-calendar-xmark',
            kicker: 'Envíos programados',
            title: 'Cancelar envío programado',
            titleChip: t.ticket_number,
            width: 'md',
            body: '<div class="tkt-headline">' +
                    '<div class="t">' + escapeHtml(mail.subject || '(sin asunto)') + '</div>' +
                    '<div class="s">Para ' + escapeHtml(mail.to || '—') +
                        (mail.scheduled_at_human ? ' · saldrá el ' + escapeHtml(mail.scheduled_at_human) : '') + '</div>' +
                  '</div>' +
                  '<div class="tkt-pick-list">' +
                    '<button type="button" class="tkt-pick" data-cancel-mode="draft">' +
                        '<span class="av light"><i class="fa-regular fa-file-lines"></i></span>' +
                        '<span class="who"><span class="n">Guardar como borrador</span>' +
                        '<span class="s">Se conserva el contenido y deja de tener hora de envío</span></span></button>' +
                    '<button type="button" class="tkt-pick" data-cancel-mode="delete">' +
                        '<span class="av light"><i class="fa-regular fa-trash-can"></i></span>' +
                        '<span class="who"><span class="n">Eliminar definitivamente</span>' +
                        '<span class="s">El correo desaparece del ticket</span></span></button>' +
                  '</div>' +
                  '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Si la automatización que lo generó sigue activa, podría volver a programarse.</div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>Volver</button>',
        }));

        $backdrop.on('click', '[data-cancel-mode]', function () {
            var mode = $(this).data('cancel-mode');
            $backdrop.find('[data-cancel-mode]').prop('disabled', true);
            $.ajax({
                url: mail.url_cancel_scheduled,
                method: 'POST',
                data: { mode: mode },
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Envío cancelado');
                    closeModal();
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = apiErrorMessage(xhr, 'No se pudo cancelar el envío');
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $backdrop.find('[data-cancel-mode]').prop('disabled', false);
                },
            });
        });
    }


