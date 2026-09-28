'use strict';

    // ── Modal 50: Traducir respuesta ──────────────────────────
    // Función, no un array de módulo: TKA.i18n todavía no está poblado
    // cuando este archivo se parsea (ver csatLabel en modal-40 para el mismo
    // razonamiento), así que los nombres de idioma se resuelven al abrir el
    // modal, no al cargar el script.
    function translateLangs() {
        return [
            { code: 'es', label: TKA.t('modal_50_lang_es', 'Español') },
            { code: 'en', label: TKA.t('modal_50_lang_en', 'Inglés') },
            { code: 'pt', label: TKA.t('modal_50_lang_pt', 'Portugués') },
            { code: 'fr', label: TKA.t('modal_50_lang_fr', 'Francés') },
            { code: 'de', label: TKA.t('modal_50_lang_de', 'Alemán') },
            { code: 'it', label: TKA.t('modal_50_lang_it', 'Italiano') },
        ];
    }

    /**
     * Traducir la respuesta antes de enviarla.
     *
     * Nació como satélite del modal "Redactar email": leía y escribía en
     * composeDraft y al cerrarse reabría ese modal. Desde la barra del
     * composer del HILO eso lo dejaba muerto — composeDraft es null ahí, así
     * que el botón contestaba siempre "escribe primero el texto" por mucho
     * que hubieras escrito. Ahora sabe de dónde viene.
     */
    function openTranslateModal(t, desdeCompose) {
        // Sin indicación explícita se deduce del contexto: si el modal de
        // redactar está abierto, viene de él; si no, del composer del hilo.
        var enCompose = desdeCompose !== undefined ? desdeCompose : !!(composeDraft && $('#tkt-compose-to').length);
        var $hilo = $('#tkt-reply-body');
        var source = enCompose
            ? ((composeDraft && composeDraft.body) || '')
            : ($hilo.val() || '');

        if (!source.trim()) {
            if (window.toastr) toastr.info(TKA.t('write_text_to_translate_first', 'Escribe primero el texto que quieres traducir'));
            return;
        }
        // Idioma detectado del cliente, si el contacto lo tiene guardado.
        var detected = (TKA.state.currentDetail && TKA.state.currentDetail.customer && TKA.state.currentDetail.customer.language) || null;

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-language',
            kicker: TKA.t('kicker_language_translate', 'Idioma · traducir'),
            title: TKA.t('modal_title_translate_reply', 'Traducir respuesta'),
            titleChip: t.ticket_number,
            width: 'lg',
            body: '<div class="tkt-field"><label class="tkt-label" for="tkt-tr-target">' + TKA.t('modal_50_target_lang_label', 'Idioma del cliente') +
                    (detected ? '<span class="hint">' + TKA.t('modal_50_detected_prefix', 'detectado: ') + escapeHtml(detected) + '</span>' : '') + '</label>' +
                    '<select class="tkt-input" id="tkt-tr-target" data-no-select2>' + translateLangs().map(function (l) {
                        return '<option value="' + l.code + '"' + (detected && detected.indexOf(l.code) === 0 ? ' selected' : '') + '>' + escapeHtml(l.label) + '</option>';
                    }).join('') + '</select></div>' +
                  '<div class="tkt-field"><label class="tkt-label">' + TKA.t('modal_50_your_text_label', 'Tu texto') + '</label>' +
                    '<div class="tkt-tpl-preview">' + escapeHtml(source) + '</div></div>' +
                  '<div class="tkt-field"><label class="tkt-label">' + TKA.t('modal_50_will_be_sent_label', 'Se enviará al cliente') + '</label>' +
                    '<div class="tkt-tpl-preview" id="tkt-tr-out">' + TKA.t('modal_50_translate_placeholder', 'Pulsa "Traducir" para ver el resultado.') + '</div></div>' +
                  '<label class="tkt-check"><input type="checkbox" id="tkt-tr-keep"> ' + TKA.t('modal_50_keep_original_checkbox', 'Adjuntar la versión original al pie') + '</label>' +
                  '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> ' + TKA.t('modal_50_replace_note', 'La traducción sustituye el cuerpo del redactor; el texto original solo se conserva si marcas la casilla.') + '</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-tr-use" disabled>' + TKA.t('modal_50_use_translation_btn', 'Usar traducción') + '</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-tr-run">' + TKA.t('btn_translate', 'Traducir') + '</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-tr-back">' + TKA.t('cancel', 'Cancelar') + '</button>',
        }));

        var translated = null;

        $backdrop.on('click', '#tkt-tr-run', function () {
            var $btn = $(this).prop('disabled', true).text(TKA.t('btn_translating', 'Traduciendo…'));
            $.ajax({
                url: t.url_translate_text,
                method: 'POST',
                data: { text: source, target_lang: $('#tkt-tr-target').val() },
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    $btn.prop('disabled', false).text(TKA.t('btn_translate_again', 'Volver a traducir'));
                    if (!resp || !resp.translated) {
                        $backdrop.find('#tkt-tr-out').text(TKA.t('translation_engine_no_result', 'El motor de traducción no devolvió resultado para este texto.'));
                        return;
                    }
                    translated = resp.text;
                    $backdrop.find('#tkt-tr-out').text(translated);
                    $backdrop.find('#tkt-tr-use').prop('disabled', false);
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).text(TKA.t('btn_translate', 'Traducir'));
                    var msg = apiErrorMessage(xhr, TKA.t('modal_50_translate_failed', 'No se pudo traducir el texto'));
                    tktNotify('error', msg);
                },
            });
        });

        $backdrop.on('click', '#tkt-tr-use', function () {
            if (!translated) return;

            var resultado = $('#tkt-tr-keep').is(':checked')
                ? translated + '\n\n---\n' + source
                : translated;

            closeModal();

            if (enCompose) {
                composeDraft.body = resultado;
                openComposeModal(t);
                return;
            }

            // Vuelta al composer del hilo, que es de donde salió el texto.
            $hilo.val(resultado).trigger('input').trigger('focus');
            if (typeof autoResizeTextarea === 'function') autoResizeTextarea($hilo[0]);
        });
        $backdrop.on('click', '#tkt-tr-back', function () {
            closeModal();
            if (enCompose) openComposeModal(t);
        });
    }

    // Estado del borrador que comparten el modal 01 y sus tres satélites
    // (02 Programar, 03 Plantillas, 04 Adjuntar): al abrir uno de ellos el
    // compose se queda debajo, así que hay que guardar lo escrito y
    // devolverlo al volver.
    var composeDraft = null;

    function readComposeDraft() {
        if (!$('#tkt-compose-to').length) return;
        composeDraft.to = $('#tkt-compose-to').val();
        composeDraft.cc = $('#tkt-compose-cc').val();
        composeDraft.bcc = $('#tkt-compose-bcc').val();
        composeDraft.subject = $('#tkt-compose-subject').val();
        composeDraft.body = $('#tkt-compose-body').val();
        composeDraft.from = $('#tkt-compose-from').val();
        composeDraft.attachToThread = $('#tkt-compose-thread').is(':checked');
    }

    function composeScheduleLabel() {
        if (!composeDraft || !composeDraft.scheduledAt) return '';
        var d = new Date(composeDraft.scheduledAt);
        var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
        return pad(d.getDate()) + ' ' + MONTH_SHORT[d.getMonth()] + ' · ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function openComposeModal(t) {
        // Primera apertura del ticket: borrador limpio. Si se vuelve desde un
        // satélite, se conserva lo ya escrito.
        if (!composeDraft || composeDraft.ticketId !== t.id) {
            composeDraft = {
                ticketId: t.id,
                to: (t.customer && t.customer.email) || '',
                cc: '', bcc: '',
                subject: 'Re: ' + (t.subject || ''),
                body: '',
                from: (TKA.state.senders || [])[0] || '',
                attachToThread: true,
                scheduledAt: null,
                cancelIfReplies: false,
                files: [],
            };
        }

        var senders = TKA.state.senders || [];
        var hasCcBcc = !!(composeDraft.cc || composeDraft.bcc);

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-pen',
            kicker: TKA.t('kicker_ticket_reply', 'Ticket · responder'),
            title: TKA.t('modal_title_compose_email', 'Redactar email'),
            titleChip: t.ticket_number,
            width: 'xl',
            body: '' +
                '<div class="tkt-field">' +
                    '<label class="tkt-label" for="tkt-compose-to">' + TKA.t('modal_01_to_label', 'Para') + '<span class="req">*</span>' +
                        '<button type="button" class="tkt-label-action" id="tkt-compose-ccbcc">' + TKA.t('modal_01_ccbcc_toggle_btn', '+ CC · CCO') + '</button>' +
                    '</label>' +
                    '<input type="email" class="tkt-input" id="tkt-compose-to" value="' + escapeHtml(composeDraft.to) + '"></div>' +
                '<div class="tkt-field-row" id="tkt-compose-ccbcc-row"' + (hasCcBcc ? '' : ' hidden') + '>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-compose-cc">' + TKA.t('modal_01_cc_label', 'CC') + ' <span class="hint">' + TKA.t('modal_01_cc_hint', 'separados por coma') + '</span></label><input type="text" class="tkt-input" id="tkt-compose-cc" value="' + escapeHtml(composeDraft.cc) + '"></div>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-compose-bcc">' + TKA.t('modal_01_bcc_label', 'CCO') + '</label><input type="text" class="tkt-input" id="tkt-compose-bcc" value="' + escapeHtml(composeDraft.bcc) + '"></div>' +
                '</div>' +
                '<div class="tkt-field"><label class="tkt-label" for="tkt-compose-subject">' + TKA.t('modal_01_subject_label', 'Asunto') + '<span class="req">*</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-compose-subject" value="' + escapeHtml(composeDraft.subject) + '"></div>' +
                '<div class="tkt-field-row">' +
                    '<div class="tkt-field"><label class="tkt-label">' + TKA.t('modal_01_template_label', 'Plantilla') + '</label>' +
                        '<button type="button" class="tkt-input tkt-input-btn" id="tkt-compose-tpl"><span>' + TKA.t('modal_01_choose_template_btn', 'Elegir plantilla…') + '</span><i class="fa-solid fa-chevron-down"></i></button></div>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-compose-from">' + TKA.t('modal_01_sender_label', 'Remitente') + '</label>' +
                        (senders.length > 1
                            ? '<select class="tkt-input" id="tkt-compose-from" data-no-select2>' + senders.map(function (a) {
                                return '<option value="' + escapeHtml(a) + '"' + (a === composeDraft.from ? ' selected' : '') + '>' + escapeHtml(a) + '</option>';
                              }).join('') + '</select>'
                            : '<input type="text" class="tkt-input" id="tkt-compose-from" value="' + escapeHtml(composeDraft.from) + '" readonly title="' + escapeHtml(TKA.t('modal_01_single_sender_title', 'Única dirección de envío configurada')) + '">') +
                    '</div>' +
                '</div>' +
                '<div class="tkt-field">' +
                    '<label class="tkt-label" for="tkt-compose-body">' + TKA.t('modal_01_message_label', 'Mensaje') + '<span class="req">*</span>' +
                        // Sintaxis real de TicketVariableInterpolator: snake_case
                        // plano, nunca con puntos (bug 11-sep-2026).
                        '<span class="hint">' + TKA.t('modal_01_variables_hint', 'variables: {{customer_name}} {{ticket_number}} {{agent_name}}') + '</span></label>' +
                    '<div class="tkt-composer">' +
                        '<div class="tkt-composer-bar">' +
                            '<button type="button" data-wrap="**" title="' + escapeHtml(TKA.t('modal_01_bold_title', 'Negrita')) + '"><i class="fa-solid fa-bold"></i></button>' +
                            '<button type="button" data-wrap="_" title="' + escapeHtml(TKA.t('modal_01_italic_title', 'Cursiva')) + '"><i class="fa-solid fa-italic"></i></button>' +
                            '<button type="button" data-prefix="- " title="' + escapeHtml(TKA.t('modal_01_list_title', 'Lista')) + '"><i class="fa-solid fa-list-ul"></i></button>' +
                            '<button type="button" id="tkt-compose-link" title="' + escapeHtml(TKA.t('modal_01_link_title', 'Enlace')) + '"><i class="fa-solid fa-link"></i></button>' +
                            '<button type="button" id="tkt-compose-translate" class="right" title="' + escapeHtml(TKA.t('modal_01_translate_before_send_title', 'Traducir antes de enviar')) + '"><i class="fa-solid fa-language"></i></button>' +
                            '<button type="button" id="tkt-compose-attach-open" title="' + escapeHtml(TKA.t('modal_01_attach_files_title', 'Adjuntar archivos')) + '"><i class="fa-solid fa-paperclip"></i></button>' +
                        '</div>' +
                        '<textarea class="tkt-composer-area" id="tkt-compose-body" placeholder="' + escapeHtml(TKA.t('modal_01_body_placeholder', 'Escribe la respuesta…')) + '">' + escapeHtml(composeDraft.body) + '</textarea>' +
                    '</div>' +
                '</div>' +
                '<div id="tkt-compose-files">' + composeFilesHtml() + '</div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-compose-thread"' + (composeDraft.attachToThread ? ' checked' : '') + '> ' + TKA.t('modal_01_attach_to_thread_checkbox', 'Adjuntar el email al hilo del ticket') + '</label>' +
                (composeDraft.scheduledAt
                    ? '<div class="tkt-note ok" id="tkt-compose-sched-note"><i class="fa-regular fa-clock"></i> ' + TKA.t('modal_01_scheduled_for_prefix', 'Programado para ') + '<strong>' + escapeHtml(composeScheduleLabel()) + '</strong>' +
                        '<button type="button" class="tkt-link-btn" id="tkt-compose-sched-clear">' + TKA.t('modal_01_remove_schedule_btn', 'quitar') + '</button></div>'
                    : ''),
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-compose-confirm">' + (composeDraft.scheduledAt ? TKA.t('modal_01_schedule_send_btn', 'Programar envío') : TKA.t('modal_01_send_now_btn', 'Enviar ahora')) + '</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-compose-schedule">' + TKA.t('modal_02_schedule_btn', 'Programar') + '</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-compose-draft">' + TKA.t('modal_01_draft_btn', 'Borrador') + '</button>',
        }));

        // "+ CC · CCO" despliega la fila, como en el mockup (oculta mientras
        // no haga falta para no cargar el formulario de campos vacíos).
        $backdrop.on('click', '#tkt-compose-ccbcc', function () {
            var row = document.getElementById('tkt-compose-ccbcc-row');
            row.hidden = !row.hidden;
            if (!row.hidden) $('#tkt-compose-cc').trigger('focus');
        });

        // Barra de formato: envuelve la selección, sin editor rico — el
        // backend guarda el cuerpo tal cual y el mockup solo enseña estos
        // cuatro controles.
        $backdrop.on('click', '[data-wrap], [data-prefix]', function () {
            var ta = document.getElementById('tkt-compose-body');
            var wrap = $(this).data('wrap');
            var prefix = $(this).data('prefix');
            var start = ta.selectionStart, end = ta.selectionEnd;
            var sel = ta.value.slice(start, end);
            var out = wrap ? wrap + (sel || 'texto') + wrap : prefix + (sel || '');
            ta.setRangeText(out, start, end, 'end');
            ta.focus();
        });

        $backdrop.on('click', '#tkt-compose-link', function () {
            var ta = document.getElementById('tkt-compose-body');
            var sel = ta.value.slice(ta.selectionStart, ta.selectionEnd) || 'enlace';
            ta.setRangeText('[' + sel + '](https://)', ta.selectionStart, ta.selectionEnd, 'end');
            ta.focus();
        });

        $backdrop.on('click', '#tkt-compose-tpl', function () { readComposeDraft(); openTemplatesModal(t); });
        $backdrop.on('click', '#tkt-compose-attach-open', function () { readComposeDraft(); openAttachModal(t); });
        $backdrop.on('click', '#tkt-compose-translate', function () { readComposeDraft(); openTranslateModal(t, true); });
        $backdrop.on('click', '#tkt-compose-schedule', function () { readComposeDraft(); openScheduleModal(t); });
        $backdrop.on('click', '#tkt-compose-sched-clear', function () {
            readComposeDraft();
            composeDraft.scheduledAt = null;
            composeDraft.cancelIfReplies = false;
            closeModal();
            openComposeModal(t);
        });
        $backdrop.on('click', '[data-file-remove]', function () {
            readComposeDraft();
            composeDraft.files.splice(parseInt($(this).data('file-remove'), 10), 1);
            $('#tkt-compose-files').html(composeFilesHtml());
        });

        // "Borrador": guarda lo escrito en el navegador y cierra. No hay
        // endpoint de borradores en el backend, así que se persiste en
        // localStorage por ticket — se recupera al reabrir el compose.
        $backdrop.on('click', '#tkt-compose-draft', function () {
            readComposeDraft();
            try {
                window.localStorage.setItem('tkt-draft-' + t.id, JSON.stringify({
                    to: composeDraft.to, cc: composeDraft.cc, bcc: composeDraft.bcc,
                    subject: composeDraft.subject, body: composeDraft.body,
                }));
                if (window.toastr) toastr.success(TKA.t('draft_saved_in_browser', 'Borrador guardado en este navegador'));
            } catch (e) {
                if (window.toastr) toastr.error(TKA.t('draft_save_failed', 'No se pudo guardar el borrador'));
            }
            closeModal();
        });

        $backdrop.on('click', '#tkt-compose-confirm', function () {
            readComposeDraft();
            if (!composeDraft.to || !composeDraft.subject || !composeDraft.body) {
                if (window.toastr) toastr.error(TKA.t('fill_recipient_subject_message', 'Rellena destinatario, asunto y mensaje'));
                return;
            }

            var scheduled = composeDraft.scheduledAt;
            var $btn = $(this).prop('disabled', true).text(scheduled ? TKA.t('modal_01_scheduling_progress', 'Programando…') : TKA.t('btn_sending', 'Enviando…'));
            var formData = new FormData();
            formData.append('ticket_id', t.id);
            formData.append('to', composeDraft.to.trim());
            formData.append('subject', composeDraft.subject.trim());
            formData.append('body', composeDraft.body.trim());
            if (composeDraft.from) formData.append('from', composeDraft.from);
            (composeDraft.cc || '').split(',').map(function (x) { return x.trim(); }).filter(Boolean).forEach(function (e) { formData.append('cc[]', e); });
            (composeDraft.bcc || '').split(',').map(function (x) { return x.trim(); }).filter(Boolean).forEach(function (e) { formData.append('bcc[]', e); });
            if (scheduled) formData.append('scheduled_at', scheduled);
            if (scheduled && composeDraft.cancelIfReplies) formData.append('cancel_if_customer_replies', '1');
            composeDraft.files.forEach(function (f) { formData.append('attachments[]', f); });

            $.ajax({
                url: TKA.urls.emailsStore,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || (scheduled ? TKA.t('email_scheduled', 'Email programado') : TKA.t('email_sent', 'Email enviado')));
                    try { window.localStorage.removeItem('tkt-draft-' + t.id); } catch (e) { /* sin localStorage */ }
                    composeDraft = null;
                    closeModal();
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || TKA.t('modal_01_send_failed', 'No se pudo enviar el email');
                    tktNotify('error', msg);
                    $btn.prop('disabled', false).text(scheduled ? TKA.t('modal_01_schedule_send_btn', 'Programar envío') : TKA.t('modal_01_send_now_btn', 'Enviar ahora'));
                },
            });
        });
    }

    function composeFilesHtml() {
        if (!composeDraft || !composeDraft.files.length) return '';
        return '<div class="tkt-att-strip compact"><span class="tkt-cap">' + TKA.t('modal_01_attachments_caption', 'Adjuntos · :n', { ':n': composeDraft.files.length }) + '</span>' +
            composeDraft.files.map(function (f, i) {
                return '<span class="tkt-att-pill"><i class="' + fileIconClass(f.name) + '"></i>' + escapeHtml(f.name) +
                    '<span class="mono">' + formatFileSize(f.size) + '</span>' +
                    '<button type="button" data-file-remove="' + i + '" title="' + escapeHtml(TKA.t('modal_01_remove_file_title', 'Quitar')) + '"><i class="fa-solid fa-xmark"></i></button></span>';
            }).join('') + '</div>';
    }


