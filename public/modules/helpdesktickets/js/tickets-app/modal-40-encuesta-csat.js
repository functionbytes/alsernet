'use strict';

    // ── Modal 40: Encuesta CSAT ──────────────────────────────
    // La valoración del cliente con el contexto que hace falta para leerla:
    // cuánto se tardó, cuántas veces se reabrió y cómo puntúa de normal ese
    // agente (una nota de 3 significa cosas distintas si su media es 4,8).

    // Función (no objeto de módulo) porque TKA.i18n todavía no está poblado
    // cuando este archivo se parsea: initTicketsApp() lo rellena en el
    // DOMContentLoaded, después de que todos los <script> ya se han
    // ejecutado. Un objeto de nivel de módulo con TKA.t() se quedaría fijo
    // en el fallback en español para siempre.
    function csatLabel(rating) {
        switch (rating) {
            case 1: return TKA.t('modal_40_rating_1', 'Muy insatisfecho');
            case 2: return TKA.t('modal_40_rating_2', 'Insatisfecho');
            case 3: return TKA.t('modal_40_rating_3', 'Neutral');
            case 4: return TKA.t('modal_40_rating_4', 'Satisfecho');
            case 5: return TKA.t('modal_40_rating_5', 'Muy satisfecho');
            default: return '';
        }
    }

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
            kicker: TKA.t('kicker_ticket_satisfaction', 'Ticket · satisfacción'),
            titleChip: t.ticket_number,
            title: TKA.t('modal_title_csat_survey', 'Encuesta CSAT'),
            width: 'sm',
            body: '<div class="tkt-empty-box">' + TKA.t('loading', 'Cargando…') + '</div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>' + TKA.t('close', 'Cerrar') + '</button>',
        }));

        $.getJSON(t.url_csat).done(function (res) {
            var c = res && res.csat;
            if (!c) { $backdrop.find('.tkt-modal-body').html('<div class="tkt-empty-box">' + TKA.t('modal_40_load_failed', 'No se pudo cargar la valoración.') + '</div>'); return; }

            var html;
            if (c.rating) {
                html = '<div class="tkt-cap">' + TKA.t('modal_40_customer_response_caption', 'Respuesta del cliente') + '</div>' +
                    '<div class="tkt-csat-head">' +
                        '<span class="tkt-csat-score">' + c.rating + '</span>' +
                        '<span class="tkt-csat-main">' +
                            '<span class="tkt-csat-stars">' + csatStars(c.rating) + '</span>' +
                            '<span class="tkt-csat-label">' + escapeHtml(csatLabel(c.rating)) +
                                (c.rated_at_human ? TKA.t('modal_40_answered_on_prefix', ' · respondida el ') + escapeHtml(c.rated_at_human) : '') +
                            '</span>' +
                        '</span>' +
                    '</div>' +
                    (c.comment ? '<blockquote class="tkt-form-quote">' + escapeHtml(c.comment) + '</blockquote>' : '') +
                    (c.reason ? '<div class="tkt-kv"><span class="k">' + TKA.t('modal_40_reason_label', 'Motivo') + '</span><span class="v">' + escapeHtml(c.reason) + '</span></div>' : '');
            } else {
                html = '<div class="tkt-empty-box">' + TKA.t('modal_40_no_rating_yet', 'Este ticket todavía no tiene valoración.') + '</div>' +
                    (c.can_resend ? '<button type="button" class="tkt-btn tkt-w-100" id="tkt-csat-resend">' + TKA.t('btn_resend_csat_survey', 'Reenviar encuesta de satisfacción') + '</button>' : '');
            }

            // Contexto: se muestra siempre, también sin valoración — sirve
            // para decidir si merece la pena volver a pedirla.
            html += '<div class="tkt-kv-grid tkt-csat-meta">' +
                '<span>' + TKA.t('modal_40_agent_label', 'agente') + '</span><span>' + escapeHtml(c.agent || '—') + '</span>' +
                '<span>' + TKA.t('modal_40_resolution_time_label', 'tiempo de resolución') + '</span><span>' + escapeHtml(c.resolution_human || '—') + '</span>' +
                '<span>' + TKA.t('modal_40_reopenings_label', 'reaperturas') + '</span><span>' + c.reopenings + '</span>' +
                '<span>' + TKA.t('modal_40_agent_average_label', 'media del agente') + '</span><span>' + (c.agent_average !== null ? TKA.t('modal_40_agent_average_value', ':n / 5', { ':n': c.agent_average }) : TKA.t('modal_40_no_ratings_value', 'sin valoraciones')) + '</span>' +
            '</div>';

            $backdrop.find('.tkt-modal-body').html(html);
        }).fail(function () {
            $backdrop.find('.tkt-modal-body').html('<div class="tkt-empty-box">' + TKA.t('modal_40_load_failed', 'No se pudo cargar la valoración.') + '</div>');
        });

        $backdrop.on('click', '#tkt-csat-resend', function () {
            var $btn = $(this).prop('disabled', true).text(TKA.t('btn_sending', 'Enviando…'));
            $.ajax({
                url: t.url_send_csat,
                method: 'POST',
                headers: { Accept: 'application/json' },
            }).done(function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || TKA.t('survey_resent', 'Encuesta reenviada.'));
                closeModal();
            }).fail(function (xhr) {
                var msg = apiErrorMessage(xhr, TKA.t('modal_40_resend_failed', 'No se pudo reenviar la encuesta.'));
                tktNotify('error', msg);
                $btn.prop('disabled', false).text(TKA.t('btn_resend_csat_survey', 'Reenviar encuesta de satisfacción'));
            });
        });
    }


