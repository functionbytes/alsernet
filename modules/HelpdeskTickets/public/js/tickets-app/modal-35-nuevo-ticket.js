'use strict';

    // ── Modal 35: Nuevo ticket ───────────────────────────────
    // Crear sin salir del listado. La página /tickets/create sigue existiendo
    // (tiene plantillas y adjuntos); este modal cubre el caso rápido y avisa
    // del duplicado antes de crear, que es lo que aporta el mockup.

    function openNewTicketModal() {
        var customerOptions = '<option value="">Selecciona un cliente…</option>' +
            (TKA.state.customers || []).map(function (c) {
                return '<option value="' + c.id + '">' + escapeHtml(c.name + (c.email ? ' · ' + c.email : '')) + '</option>';
            }).join('');

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-plus',
            kicker: 'Tickets · nuevo',
            title: 'Nuevo ticket',
            width: 'md',
            body:
                '<div class="tkt-field-row">' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-new-source">Origen del ticket</label>' +
                        '<select id="tkt-new-source" class="tkt-select">' +
                            '<option value="manual">Manual (agente)</option>' +
                            '<option value="email">Email entrante</option>' +
                            '<option value="formulario">Formulario</option>' +
                            '<option value="widget">Widget</option>' +
                            '<option value="wa">WhatsApp</option>' +
                            '<option value="phone">Teléfono</option>' +
                        '</select></div>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-new-customer">Cliente<span class="req">*</span></label>' +
                        '<select id="tkt-new-customer" class="tkt-select">' + customerOptions + '</select></div>' +
                '</div>' +
                '<div class="tkt-field"><label class="tkt-label" for="tkt-new-subject">Asunto</label>' +
                    '<input type="text" class="tkt-input" id="tkt-new-subject" maxlength="255" placeholder="Resumen en una línea"></div>' +
                '<div id="tkt-new-dupe"></div>' +
                '<div class="tkt-field-row">' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-new-category">Categoría</label>' +
                        '<select id="tkt-new-category" class="tkt-select"><option value="">—</option>' + optionsHtml(TKA.state.categories, 'id') + '</select></div>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-new-priority">Prioridad</label>' +
                        '<select id="tkt-new-priority" class="tkt-select">' +
                            '<option value="normal">Normal</option><option value="high">Alta</option>' +
                            '<option value="urgent">Urgente</option><option value="low">Baja</option>' +
                        '</select></div>' +
                '</div>' +
                '<div class="tkt-field-row">' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-new-assignee">Agente</label>' +
                        '<select id="tkt-new-assignee" class="tkt-select"><option value="">Sin asignar</option>' + optionsHtml(TKA.state.agentsFull, 'id') + '</select></div>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-new-group">Equipo</label>' +
                        '<select id="tkt-new-group" class="tkt-select"><option value="">—</option>' + optionsHtml(TKA.state.groups, 'id') + '</select></div>' +
                '</div>' +
                '<div class="tkt-field"><label class="tkt-label" for="tkt-new-description">Descripción<span class="req">*</span></label>' +
                    '<textarea class="tkt-input" id="tkt-new-description" rows="4" placeholder="Qué ha contado el cliente…"></textarea></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-new-create">Crear y abrir</button>' +
                  '<a href="' + TKA.urls.ticketCreate + '" class="tkt-btn tkt-link-plain">Formulario completo</a>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
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

                $box.html('<div class="tkt-note warn">Este cliente ya tiene ' +
                    (list.length === 1 ? 'un ticket abierto' : list.length + ' tickets abiertos') +
                    ' con un asunto parecido: ' +
                    list.map(function (d) {
                        return '<a href="' + escapeHtml(d.url) + '" target="_blank" rel="noopener">' +
                            escapeHtml(d.ticket_number) + '</a> (' + Math.round(d.similarity * 100) + ' %)';
                    }).join(', ') + '.</div>');
            });
        }

        $backdrop.on('click', '#tkt-new-create', function () {
            var customerId = $backdrop.find('#tkt-new-customer').val();
            var description = ($backdrop.find('#tkt-new-description').val() || '').trim();

            if (!customerId) { if (window.toastr) toastr.error('Elige un cliente'); return; }
            if (!description) { if (window.toastr) toastr.error('Escribe la descripción'); return; }

            var $btn = $(this).prop('disabled', true).text('Creando…');

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
                if (window.toastr) toastr.success('Ticket creado');
                // El controlador redirige al detalle; con Accept JSON llega el
                // id, y si no, se recarga el listado sin más.
                var id = resp && (resp.id || (resp.ticket && resp.ticket.id));
                window.location = id
                    ? TKA.urls.index + '?ticket=' + id
                    : TKA.urls.index;
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && (xhr.responseJSON.message
                    || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'No se pudo crear el ticket';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
                $btn.prop('disabled', false).text('Crear y abrir');
            });
        });
    }


