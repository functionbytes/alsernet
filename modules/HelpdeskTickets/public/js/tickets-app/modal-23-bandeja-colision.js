'use strict';

    // ── Modal 23: Bandeja compartida (colisión) ───────────────
    function openCollisionModal(t, presence) {
        // TKA.state.presenceUsers (Echo .here()) incluye al propio usuario —
        // el banner ya se filtra al pintarse, pero este modal recibe la
        // lista tal cual desde el listener del botón.
        var people = (presence || []).filter(function (u) { return u.id !== TKA.state.currentUserId; });

        // Y quien esté escribiendo, aunque .here() no lo haya listado (mismo
        // criterio que las burbujas): pulsar una burbuja que dice "Eva está
        // redactando" y encontrarse "nadie más está en este ticket" era una
        // contradicción en la misma pantalla.
        typingOthers().forEach(function (u) {
            if (!people.some(function (p) { return p.id === u.id; })) people.push(u);
        });
        var rows = people.length
            ? people.map(function (pr) {
                // pr.typing no lo trae el canal de presencia (solo id y name):
                // quién escribe se sabe por los eventos .typing, que ahora se
                // guardan en TKA.state.typingUsers.
                var escribiendo = isTypingNow(pr.id);

                return '<div class="tkt-mailitem"><span class="av">' + escapeHtml(initials(pr.name)) + '</span>' +
                    '<span class="who"><span class="n">' + escapeHtml(pr.name) + '</span>' +
                    '<span class="s">' + escapeHtml(escribiendo ? 'Está redactando una respuesta' : 'Viendo el ticket') + '</span></span>' +
                    '<span class="tkt-live"><span class="dot"></span>' + (escribiendo ? 'escribiendo' : 'viendo') + '</span>' +
                    // Acceso rápido a las dos acciones que se piden desde aquí:
                    // pasarle el ticket a esa persona, o avisarla de que estáis
                    // los dos dentro.
                    '<button type="button" class="tkt-btn tkt-btn-sm" data-assign-to="' + escapeHtml(String(pr.id)) + '">Asignar</button>' +
                    '<button type="button" class="tkt-btn tkt-btn-sm" data-nudge="' + escapeHtml(String(pr.id)) + '">Avisar</button></div>';
              }).join('')
            : '<div class="tkt-empty-box">Ahora mismo nadie más está en este ticket.</div>';

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-users', kicker: 'Bandeja · colisión',
            // 'sm' (340px) se quedaba corto: cada fila lleva avatar + nombre +
            // estado + dos botones ("Asignar"/"Avisar") y el nombre se
            // truncaba de inmediato.
            title: 'Bandeja compartida', titleChip: t.ticket_number, width: 'xl',
            body: '<div class="tkt-mailitems">' + rows + '</div>' +
                '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> La presencia se actualiza mientras la pestaña está abierta; al cerrarla el resto deja de verte.</div>',
            foot: (people.length ? '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-collision-take">Tomar el control</button>' : '') +
                '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        // "Avisar a X": notificación puntual al agente elegido, no cambia
        // nada del ticket — puede pulsarse varias veces sin más efecto que
        // el que ya aplica el cooldown del propio backend.
        $backdrop.on('click', '[data-nudge]', function () {
            var $btn = $(this).prop('disabled', true);
            if (!t.url_presence_nudge) { $btn.prop('disabled', false); return; }
            $.post(t.url_presence_nudge, { to_user_id: $(this).data('nudge') })
                .done(function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Aviso enviado'); else window.alert('Aviso enviado');
                })
                .fail(function (xhr) {
                    var msg = apiErrorMessage(xhr, 'No se pudo avisar.');
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                })
                .always(function () { $btn.prop('disabled', false); });
        });

        // "Tomar el control": me asigno el ticket, igual que el selector de
        // agente de la ficha — misma ruta/autorización, solo con el destino
        // fijado a mí mismo en vez de elegirlo de un <select>.
        $backdrop.on('click', '#tkt-collision-take', function () {
            if (!TKA.state.currentUserId) return;
            patchTicket(t, 'assignee_id', TKA.state.currentUserId);
        });

        // "Asignar": misma ruta y autorización que el selector de agente del
        // panel Gestión, con el destino ya elegido — evita cerrar este modal,
        // abrir el de asignación y buscar a la persona que tienes delante.
        $backdrop.on('click', '[data-assign-to]', function () {
            patchTicket(t, 'assignee_id', $(this).data('assign-to'));
            closeModal();
        });
    }


