'use strict';

    // ── Modal 35: Nuevo ticket ───────────────────────────────
    // Crear sin salir del listado. La página /tickets/create sigue existiendo
    // (tiene plantillas y adjuntos); este modal cubre el caso rápido y avisa
    // del duplicado antes de crear, que es lo que aporta el mockup.

    function openNewTicketModal() {
        var customerOptions = '<option value="">' + TKA.t('modal_35_choose_customer_option', 'Selecciona un cliente…') + '</option>' +
            (TKA.state.customers || []).map(function (c) {
                return '<option value="' + c.id + '">' + escapeHtml(c.name + (c.email ? ' · ' + c.email : '')) + '</option>';
            }).join('');

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-plus',
            kicker: TKA.t('kicker_tickets_new', 'Tickets · nuevo'),
            title: TKA.t('modal_title_new_ticket', 'Nuevo ticket'),
            width: 'md',
            body:
                '<div class="tkt-field-row">' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-new-source">' + TKA.t('modal_35_source_label', 'Origen del ticket') + '</label>' +
                        '<select id="tkt-new-source" class="tkt-select">' +
                            '<option value="manual">' + TKA.t('modal_35_source_manual', 'Manual (agente)') + '</option>' +
                            '<option value="email">' + TKA.t('modal_35_source_email', 'Email entrante') + '</option>' +
                            '<option value="formulario">' + TKA.t('modal_35_source_form', 'Formulario') + '</option>' +
                            '<option value="widget">' + TKA.t('modal_35_source_widget', 'Widget') + '</option>' +
                            '<option value="wa">' + TKA.t('modal_35_source_whatsapp', 'WhatsApp') + '</option>' +
                            '<option value="phone">' + TKA.t('modal_35_source_phone', 'Teléfono') + '</option>' +
                        '</select></div>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-new-customer">' + TKA.t('modal_35_customer_label', 'Cliente') + '<span class="req">*</span></label>' +
                        '<select id="tkt-new-customer" class="tkt-select">' + customerOptions + '</select></div>' +
                '</div>' +
                '<div class="tkt-field"><label class="tkt-label" for="tkt-new-subject">' + TKA.t('modal_35_subject_label', 'Asunto') + '</label>' +
                    '<input type="text" class="tkt-input" id="tkt-new-subject" maxlength="255" placeholder="' + escapeHtml(TKA.t('modal_35_subject_placeholder', 'Resumen en una línea')) + '"></div>' +
                '<div id="tkt-new-dupe"></div>' +
                '<div class="tkt-field-row">' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-new-category">' + TKA.t('modal_35_category_label', 'Categoría') + '</label>' +
                        '<select id="tkt-new-category" class="tkt-select"><option value="">—</option>' + optionsHtml(TKA.state.categories, 'id') + '</select></div>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-new-priority">' + TKA.t('modal_35_priority_label', 'Prioridad') + '</label>' +
                        '<select id="tkt-new-priority" class="tkt-select">' +
                            '<option value="normal">' + TKA.t('modal_35_priority_normal', 'Normal') + '</option><option value="high">' + TKA.t('modal_35_priority_high', 'Alta') + '</option>' +
                            '<option value="urgent">' + TKA.t('modal_35_priority_urgent', 'Urgente') + '</option><option value="low">' + TKA.t('modal_35_priority_low', 'Baja') + '</option>' +
                        '</select></div>' +
                '</div>' +
                '<div class="tkt-field-row">' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-new-assignee">' + TKA.t('modal_35_agent_label', 'Agente') + '</label>' +
                        '<select id="tkt-new-assignee" class="tkt-select"><option value="">' + TKA.t('modal_35_unassigned_option', 'Sin asignar') + '</option>' + optionsHtml(TKA.state.agentsFull, 'id') + '</select></div>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-new-group">' + TKA.t('modal_35_team_label', 'Equipo') + '</label>' +
                        '<select id="tkt-new-group" class="tkt-select"><option value="">—</option>' + optionsHtml(TKA.state.groups, 'id') + '</select></div>' +
                '</div>' +
                '<div class="tkt-field"><label class="tkt-label" for="tkt-new-description">' + TKA.t('modal_35_description_label', 'Descripción') + '<span class="req">*</span></label>' +
                    '<textarea class="tkt-input" id="tkt-new-description" rows="4" placeholder="' + escapeHtml(TKA.t('modal_35_description_placeholder', 'Qué ha contado el cliente…')) + '"></textarea></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-new-create">' + TKA.t('modal_35_create_and_open_btn', 'Crear y abrir') + '</button>' +
                  '<a href="' + TKA.urls.ticketCreate + '" class="tkt-btn tkt-link-plain">' + TKA.t('modal_35_full_form_link', 'Formulario completo') + '</a>' +
                  '<button type="button" class="tkt-btn" data-modal-close>' + TKA.t('cancel', 'Cancelar') + '</button>',
        }));

        // Aviso de duplicado en cuanto hay asunto y cliente: es el momento en
        // que se puede evitar crear el ticket, no después.
        var dupeTimer;
        $backdrop.on('input change', '#tkt-new-subject, #tkt-new-customer', function () {
            clearTimeout(dupeTimer);
            dupeTimer = setTimeout(checkNewTicketDuplicates, 500);
        });

        function checkNewTicketDuplicates() {
            var subject = ($backdrop.find('#tkt-new-subject').val() || '').trim();
            var customerId = $backdrop.find('#tkt-new-customer').val();
            var $box = $backdrop.find('#tkt-new-dupe').empty();

            if (!subject || !customerId || !TKA.urls.duplicatesPreview) return;

            $.ajax({
                url: TKA.urls.duplicatesPreview,
                method: 'POST',
                data: { subject: subject, customer_id: customerId },
                headers: { Accept: 'application/json' },
            }).done(function (res) {
                var list = (res && res.duplicates) || [];
                if (!list.length) return;

                var openTicketsLabel = list.length === 1
                    ? TKA.t('modal_35_dupe_one_open', 'un ticket abierto')
                    : TKA.t('modal_35_dupe_many_open', ':n tickets abiertos', { ':n': list.length });
                var links = list.map(function (d) {
                    return '<a href="' + escapeHtml(d.url) + '" target="_blank" rel="noopener">' +
                        escapeHtml(d.ticket_number) + '</a> (' + Math.round(d.similarity * 100) + ' %)';
                }).join(', ');
                $box.html('<div class="tkt-note warn">' + TKA.t('modal_35_dupe_warning', 'Este cliente ya tiene :open con un asunto parecido: :links.', { ':open': openTicketsLabel, ':links': links }) + '</div>');
            });
        }

        $backdrop.on('click', '#tkt-new-create', function () {
            var customerId = $backdrop.find('#tkt-new-customer').val();
            var description = ($backdrop.find('#tkt-new-description').val() || '').trim();

            if (!customerId) { if (window.toastr) toastr.error(TKA.t('choose_a_customer', 'Elige un cliente')); return; }
            if (!description) { if (window.toastr) toastr.error(TKA.t('write_the_description', 'Escribe la descripción')); return; }

            var $btn = $(this).prop('disabled', true).text(TKA.t('btn_creating', 'Creando…'));

            $.ajax({
                url: TKA.urls.ticketStore,
                method: 'POST',
                data: {
                    source: $backdrop.find('#tkt-new-source').val(),
                    customer_id: customerId,
                    subject: ($backdrop.find('#tkt-new-subject').val() || '').trim(),
                    description: description,
                    category_id: $backdrop.find('#tkt-new-category').val() || null,
                    priority: $backdrop.find('#tkt-new-priority').val(),
                    assignee_id: $backdrop.find('#tkt-new-assignee').val() || null,
                    group_id: $backdrop.find('#tkt-new-group').val() || null,
                },
                headers: { Accept: 'application/json' },
            }).done(function (resp) {
                if (window.toastr) toastr.success(TKA.t('ticket_created', 'Ticket creado'));
                // El controlador redirige al detalle; con Accept JSON llega el
                // id, y si no, se recarga el listado sin más.
                var id = resp && (resp.id || (resp.ticket && resp.ticket.id));
                window.location = id
                    ? TKA.urls.index + '?ticket=' + id
                    : TKA.urls.index;
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && (xhr.responseJSON.message
                    || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || TKA.t('modal_35_create_failed', 'No se pudo crear el ticket');
                tktNotify('error', msg);
                $btn.prop('disabled', false).text(TKA.t('btn_create_and_open', 'Crear y abrir'));
            });
        });
    }


