'use strict';

    // ── Modal 27: Etiquetado automático ───────────────────────
    function openAiTaggingModal(t, suggestion) {
        if (!suggestion) { if (window.toastr) toastr.info(TKA.t('no_classification_suggestions', 'No hay sugerencias de clasificación para este ticket')); return; }

        // % de confianza real (cuota de coincidencia de palabras clave, ver
        // TicketAiService::suggestCategory()) — no todas las sugerencias
        // antiguas tienen el dato (se calculó antes de que existiera esta
        // columna), así que se omite el chip en vez de mostrar "NaN%".
        function confidenceChip(conf) {
            if (conf == null) return '';
            var pct = Math.round(conf * 100);
            return ' <span class="tkt-rchip' + (pct > 90 ? ' ok' : '') + '">' + TKA.t('modal_27_confidence_pct', ':pct% confianza', { ':pct': pct }) + '</span>';
        }

        // sideRow() escapa siempre su 'value' — aquí hace falta concatenar
        // el chip (HTML real) detrás del texto, así que se arma la fila a
        // mano con la misma estructura/clases, escapando solo la parte de
        // texto plano.
        function sideRowWithChip(key, text, chipHtml, opts) {
            opts = opts || {};
            return '<div class="tkt-side-row' + (opts.last ? ' last' : '') + '">' +
                '<span class="k">' + escapeHtml(key) + '</span>' +
                '<span class="v' + (opts.strong ? ' strong' : '') + '">' + escapeHtml(text) + chipHtml + '</span>' +
            '</div>';
        }

        var d = TKA.state.currentDetail || {};
        var autoApplyOn = !!d.ai_auto_apply_high_confidence;

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-tags', kicker: TKA.t('kicker_ai_classification', 'IA · clasificación'),
            title: TKA.t('modal_title_auto_tagging', 'Etiquetado automático'), titleChip: t.ticket_number, width: 'md',
            body: '<div class="tkt-side-rows">' +
                    sideRowWithChip(TKA.t('modal_27_suggested_category', 'Categoría sugerida'), (suggestion.category && suggestion.category.name) || '—',
                        suggestion.category ? confidenceChip(suggestion.category.confidence) : '', { strong: true }) +
                    sideRowWithChip(TKA.t('modal_27_suggested_priority', 'Prioridad sugerida'), suggestion.priority ? priorityLabel(suggestion.priority) : '—',
                        confidenceChip(suggestion.priority_confidence), { strong: true, last: true }) +
                  '</div><div class="tkt-side-rows">' +
                    sideRow(TKA.t('modal_27_current_category', 'Categoría actual'), t.category_name || '—') +
                    sideRow(TKA.t('modal_27_current_priority', 'Prioridad actual'), priorityLabel(t.priority), { last: true }) +
                  '</div>' +
                  '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> ' + TKA.t('modal_27_confidence_explanation', 'La confianza es la cuota de coincidencia de palabras clave, no la certeza de un modelo entrenado. La clasificación se calcula al entrar el correo, antes de asignar agente.') + '</div>' +
                  '<label class="tkt-check"><input type="checkbox" id="tkt-tag-autoapply"' + (autoApplyOn ? ' checked' : '') + '> ' + TKA.t('modal_27_autoapply_checkbox', 'Aplicar automáticamente las próximas sugerencias con más del 90% de confianza') + '</label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-tag-apply">' + TKA.t('modal_27_apply_suggestions', 'Aplicar sugerencias') + '</button>' +
                  '<a class="tkt-btn" href="' + escapeHtml(TKA.urls.automationsIndex || '#') + '">' + TKA.t('modal_27_adjust_rules', 'Ajustar reglas') + '</a>' +
                  '<button type="button" class="tkt-btn" data-modal-close>' + TKA.t('cancel', 'Cancelar') + '</button>',
        }));

        $backdrop.on('change', '#tkt-tag-autoapply', function () {
            if (!TKA.urls.aiAutoApply) return;
            var checked = $(this).is(':checked');
            $.ajax({ url: TKA.urls.aiAutoApply, method: 'PATCH', data: { enabled: checked ? 1 : 0 } })
                .done(function (resp) {
                    d.ai_auto_apply_high_confidence = checked;
                    if (window.toastr) toastr.success((resp && resp.message) || TKA.t('saved_generic', 'Guardado'));
                })
                .fail(function (xhr) {
                    var msg = apiErrorMessage(xhr, TKA.t('modal_27_save_failed', 'No se pudo guardar.'));
                    tktNotify('error', msg);
                });
        });

        // Antes mandaba el POST sin 'field' (siempre fallaba la validación
        // del backend, "El campo a aplicar es obligatorio") — reusa
        // applyAiSuggestion(), la misma función que ya funciona desde el
        // chip de IA del panel, una vez por cada sugerencia presente.
        $backdrop.on('click', '#tkt-tag-apply', function () {
            if (suggestion.category) applyAiSuggestion(t, 'category');
            if (suggestion.priority) applyAiSuggestion(t, 'priority');
            closeModal();
        });
    }


