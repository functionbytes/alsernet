'use strict';

    // ── Modal 40: Encuesta CSAT ──────────────────────────────
    // La valoración del cliente con el contexto que hace falta para leerla:
    // cuánto se tardó, cuántas veces se reabrió y cómo puntúa de normal ese
    // agente (una nota de 3 significa cosas distintas si su media es 4,8).

    var CSAT_LABELS = {
        1: 'Muy insatisfecho', 2: 'Insatisfecho', 3: 'Neutral',
        4: 'Satisfecho', 5: 'Muy satisfecho',
    };

    function csatStars(rating) {
        var out = '';
        for (var i = 1; i <= 5; i++) {
            out += '<i class="fa-solid fa-star tkt-csat-star' + (i <= rating ? ' on' : '') + '"></i>';
        }

        return out;
    }

    function openCsatModal(t) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-face-smile',
            kicker: 'Ticket · satisfacción',
            titleChip: t.ticket_number,
            title: 'Encuesta CSAT',
            width: 'sm',
            body: '<div class="tkt-empty-box">Cargando…</div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        $.getJSON(t.url_csat).done(function (res) {
            var c = res && res.csat;
            if (!c) { $backdrop.find('.tkt-modal-body').html('<div class="tkt-empty-box">No se pudo cargar la valoración.</div>'); return; }

            var html;
            if (c.rating) {
                html = '<div class="tkt-cap">Respuesta del cliente</div>' +
                    '<div class="tkt-csat-head">' +
                        '<span class="tkt-csat-score">' + c.rating + '</span>' +
                        '<span class="tkt-csat-main">' +
                            '<span class="tkt-csat-stars">' + csatStars(c.rating) + '</span>' +
                            '<span class="tkt-csat-label">' + escapeHtml(CSAT_LABELS[c.rating] || '') +
                                (c.rated_at_human ? ' · respondida el ' + escapeHtml(c.rated_at_human) : '') +
                            '</span>' +
                        '</span>' +
                    '</div>' +
                    (c.comment ? '<blockquote class="tkt-form-quote">' + escapeHtml(c.comment) + '</blockquote>' : '') +
                    (c.reason ? '<div class="tkt-kv"><span class="k">Motivo</span><span class="v">' + escapeHtml(c.reason) + '</span></div>' : '');
            } else {
                html = '<div class="tkt-empty-box">Este ticket todavía no tiene valoración.</div>' +
                    (c.can_resend ? '<button type="button" class="tkt-btn tkt-w-100" id="tkt-csat-resend">Reenviar encuesta de satisfacción</button>' : '');
            }

            // Contexto: se muestra siempre, también sin valoración — sirve
            // para decidir si merece la pena volver a pedirla.
            html += '<div class="tkt-kv-grid tkt-csat-meta">' +
                '<span>agente</span><span>' + escapeHtml(c.agent || '—') + '</span>' +
                '<span>tiempo de resolución</span><span>' + escapeHtml(c.resolution_human || '—') + '</span>' +
                '<span>reaperturas</span><span>' + c.reopenings + '</span>' +
                '<span>media del agente</span><span>' + (c.agent_average !== null ? c.agent_average + ' / 5' : 'sin valoraciones') + '</span>' +
            '</div>';

            $backdrop.find('.tkt-modal-body').html(html);
        }).fail(function () {
            $backdrop.find('.tkt-modal-body').html('<div class="tkt-empty-box">No se pudo cargar la valoración.</div>');
        });

        $backdrop.on('click', '#tkt-csat-resend', function () {
            var $btn = $(this).prop('disabled', true).text('Enviando…');
            $.ajax({
                url: t.url_send_csat,
                method: 'POST',
                headers: { Accept: 'application/json' },
            }).done(function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Encuesta reenviada.');
                closeModal();
            }).fail(function (xhr) {
                var msg = apiErrorMessage(xhr, 'No se pudo reenviar la encuesta.');
                if (window.toastr) toastr.error(msg); else window.alert(msg);
                $btn.prop('disabled', false).text('Reenviar encuesta de satisfacción');
            });
        });
    }


