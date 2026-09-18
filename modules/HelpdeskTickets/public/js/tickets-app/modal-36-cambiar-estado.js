'use strict';

    // ── Modal 36: Cambiar estado ─────────────────────────────
    // El <select> del panel Gestión cambia el estado de golpe, sin decir qué
    // implica cada uno ni dejar constancia del motivo. Este modal describe la
    // consecuencia real de cada estado (el SLA que se pausa, la encuesta que
    // se dispara) y guarda la nota del cambio como nota interna.

    // Qué implica cada estado. Sale de la BD (helpdesk_ticket_statuses.
    // description) y no de un mapa fijo por slug: el catálogo es editable y
    // un texto codificado aquí mentiría en cuanto alguien añada un estado o
    // cambie lo que hace uno existente. Las banderas reales del estado
    // (pausa del SLA, cierre) se añaden detrás porque son la consecuencia
    // que de verdad cambia el comportamiento del ticket.
    function statusEffect(st) {
        var parts = [];
        if (st.description) parts.push(st.description);
        if (st.stops_sla) parts.push('pausa el reloj del SLA');
        if (st.is_closed) parts.push('no admite más respuestas del cliente');

        return parts.join(' · ');
    }

    function openChangeStatusModal(t) {
        var statuses = TKA.state.statuses || [];
        var options = statuses.map(function (st) {
            var effect = statusEffect(st);
            return '<label class="tkt-option' + (st.id === t.status_id ? ' on' : '') + '">' +
                '<input type="radio" name="tkt-state-pick" value="' + st.id + '"' + (st.id === t.status_id ? ' checked' : '') + '>' +
                '<span class="tkt-option-body">' +
                    '<span class="tkt-option-title">' + escapeHtml(st.name) + '</span>' +
                    (effect ? '<span class="tkt-option-sub">' + escapeHtml(effect) + '</span>' : '') +
                '</span>' +
            '</label>';
        }).join('');

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-arrow-right-arrow-left',
            kicker: 'Ticket · estado',
            titleChip: t.ticket_number,
            title: 'Cambiar estado',
            width: 'md',
            body: options +
                '<div class="tkt-field"><label class="tkt-label" for="tkt-state-note">Nota del cambio <span class="hint">interna</span></label>' +
                    '<textarea class="tkt-input" id="tkt-state-note" rows="2" placeholder="Por qué cambia de estado…"></textarea></div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-state-notify"> Notificar al cliente por email</label>' +
                '<div class="tkt-note">El cambio se registra en la actividad con fecha, hora y agente.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-state-save">Guardar estado</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('change', '[name="tkt-state-pick"]', function () {
            $backdrop.find('.tkt-option').removeClass('on');
            $(this).closest('.tkt-option').addClass('on');
        });

        $backdrop.on('click', '#tkt-state-save', function () {
            var statusId = $backdrop.find('[name="tkt-state-pick"]:checked').val();
            if (!statusId) { if (window.toastr) toastr.error('Elige un estado'); return; }

            var note = ($backdrop.find('#tkt-state-note').val() || '').trim();
            var notify = $backdrop.find('#tkt-state-notify').is(':checked');
            var $btn = $(this).prop('disabled', true).text('Guardando…');

            $.ajax({
                url: t.url_update,
                method: 'POST',
                data: { _method: 'PUT', status_id: statusId, notify_customer: notify ? 1 : 0 },
                headers: { Accept: 'application/json' },
            }).done(function () {
                // La nota va después y en su propio endpoint. Se espera a que
                // termine ANTES de recargar: recargar con la petición en vuelo
                // la aborta y la nota se pierde sin avisar.
                function done() {
                    if (window.toastr) toastr.success('Estado actualizado');
                    closeModal();
                    applyLocalTicketField(t, 'status_id', statusId);
                    queueTicketListRefresh('ticket-status-changed', t, {
                        freshCounts: true,
                        refreshDetail: true,
                        forceDetail: true,
                    });
                }

                if (!note || !TKA.urls.notesStoreTemplate) { done(); return; }

                // StoreTicketNoteRequest exige ticket_id además del cuerpo, y
                // el campo es 'body' (no 'content'): sin los dos, la nota se
                // rechazaba con un 422 que el .fail() de abajo silenciaba.
                $.ajax({
                    url: TKA.urls.notesStoreTemplate.replace('__TICKET__', t.id),
                    method: 'POST',
                    data: { ticket_id: t.id, body: note },
                    headers: { Accept: 'application/json' },
                }).fail(function () {
                    if (window.toastr) toastr.warning('El estado se guardó, pero no se pudo añadir la nota.');
                }).always(done);
            }).fail(function (xhr) {
                var msg = apiErrorMessage(xhr, 'No se pudo cambiar el estado');
                if (window.toastr) toastr.error(msg); else window.alert(msg);
                $btn.prop('disabled', false).text('Guardar estado');
            });
        });
    }

    // Macros reales (MacroApplyController) — mismo backend/patrón que ya
    // funciona en la ficha antigua show.blade.php: la lista se pide una vez
    // y se cachea; aplicar una ejecuta MacroExecutor en el servidor
    // (puede añadir una respuesta, cambiar estado/prioridad, etc. — lo que
    // la macro defina) y se refresca el detalle para reflejar el resultado
    // real en vez de adivinar qué cambió.
    // Las macros se piden una sola vez por sesión: la lista es la misma para
    // todo el listado y cambia solo cuando alguien las edita en ajustes.
