'use strict';

    // ── Modal 19: Auto-respuesta IA ──────────────────────────
    // La franja del composer enseña el borrador y poco más. Aquí se ve
    // entero, se puede pedir en otro tono y se ven las fuentes de las que
    // salió: sin eso no hay forma de juzgar si el borrador se puede enviar.
    //
    // Lo que el mockup tiene y aquí NO: "enviar automáticamente si la
    // confianza supera el 90 %". No existe ese automatismo, y añadir el
    // interruptor sin él haría creer que los correos salen solos.

    var AI_TONES = [
        { key: '', label: 'Por defecto' },
        { key: 'formal', label: 'Formal' },
        { key: 'cercano', label: 'Cercano' },
        { key: 'breve', label: 'Breve' },
    ];

    function openAiDraftModal(t) {
        var tone = '';

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-wand-magic-sparkles',
            iconClass: 'ok',
            kicker: 'IA · borrador',
            titleChip: t.ticket_number,
            title: 'Auto-respuesta IA',
            width: 'lg',
            body: '<div class="tkt-seg" id="tkt-ai-tones">' +
                    AI_TONES.map(function (x) {
                        return '<button type="button" class="' + (x.key === '' ? 'on' : '') + '" data-ai-tone="' + x.key + '">' + x.label + '</button>';
                    }).join('') +
                  '</div>' +
                  '<div id="tkt-ai-draft-box"><div class="tkt-empty-box">Generando un borrador…</div></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-ai-use" disabled>Usar borrador</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-ai-regen">Regenerar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Descartar</button>',
        }));

        function load(refresh) {
            $backdrop.find('#tkt-ai-draft-box').html('<div class="tkt-empty-box">Generando un borrador…</div>');
            $backdrop.find('#tkt-ai-use').prop('disabled', true);

            $.ajax({
                url: t.url_ai_suggest_reply,
                method: 'POST',
                data: { refresh: refresh ? 1 : 0, tone: tone },
                headers: { Accept: 'application/json' },
            }).done(function (res) {
                var sg = res && res.suggestion;
                if (!sg || !sg.draft) {
                    $backdrop.find('#tkt-ai-draft-box').html('<div class="tkt-empty-box">' +
                        escapeHtml((res && res.message) || 'No se pudo generar una sugerencia.') + '</div>');

                    return;
                }

                TKA.state.aiDraft = sg.draft;

                var meta = '<div class="tkt-kv-grid">' +
                    '<span>confianza</span><span>' + (typeof sg.confidence === 'number' ? Math.round(sg.confidence * 100) + ' %' : '—') + '</span>' +
                    '<span>fuentes</span><span>' + escapeHtml((sg.sources || []).join(' · ') || 'el hilo del ticket') + '</span>' +
                    '<span>plantilla</span><span>' + escapeHtml(sg.template ? (sg.template.kind + ' ' + sg.template.name) : 'redactado de cero') + '</span>' +
                    '<span>idioma</span><span>' + escapeHtml(String(sg.language || '—').toUpperCase()) + '</span>' +
                '</div>';

                $backdrop.find('#tkt-ai-draft-box').html(
                    '<div class="tkt-cap">Respuesta sugerida</div>' +
                    '<div class="tkt-ai-draft-text">' + escapeHtml(sg.draft) + '</div>' +
                    meta +
                    '<div class="tkt-note">Un agente siempre edita y envía: el borrador no sale solo.</div>'
                );
                $backdrop.find('#tkt-ai-use').prop('disabled', false);
            }).fail(function () {
                $backdrop.find('#tkt-ai-draft-box').html('<div class="tkt-empty-box">No se pudo generar el borrador.</div>');
            });
        }

        // Cambiar de tono regenera: es una respuesta distinta, no un filtro.
        $backdrop.on('click', '[data-ai-tone]', function () {
            tone = String($(this).data('ai-tone') || '');
            $backdrop.find('#tkt-ai-tones button').removeClass('on');
            $(this).addClass('on');
            load(true);
        });

        $backdrop.on('click', '#tkt-ai-regen', function () { load(true); });

        $backdrop.on('click', '#tkt-ai-use', function () {
            // Deja el borrador en el composer, en la pestaña de respuesta:
            // "usar" nunca significa enviar.
            selectDetailTab('thread');
            var $body = $('#tkt-reply-body');
            $body.val(TKA.state.aiDraft || '');
            autoResizeTextarea($body[0]);
            closeModal();
            $body.trigger('focus');
        });

        load(false);
    }


