'use strict';

    // ── Modal 44: Plantillas de ticket ────────────────────────
    // Datos de EJEMPLO para la vista previa — aquí nunca hay un cliente real
    // todavía: este modal solo elige qué precargar en "Nuevo ticket", el
    // cliente se selecciona después en el propio formulario (StoreTicket
    // interpola con los datos reales recién ahí, ver
    // TicketsCrudController::store()). Sin esto la vista previa enseñaba los
    // {{...}} tal cual — indistinguible de un bug real (reportado
    // 11-sep-2026). Mismos valores que usa el editor de plantillas
    // (ticket-templates/form.blade.php, SAMPLE_VALUES) para no inventar un
    // segundo juego de datos de ejemplo distinto.
    var TTPL_SAMPLE_VALUES = {
        '{{ticket_number}}': 'TCK-2026-00123',
        '{{ticket_subject}}': 'Asunto de ejemplo',
        '{{ticket_status}}': 'Abierto',
        '{{ticket_priority}}': 'Media',
        '{{ticket_category}}': 'Soporte técnico',
        '{{customer_name}}': 'Ana Pérez',
        '{{customer_email}}': 'ana.perez@ejemplo.com',
        '{{customer_phone}}': '600 111 222',
        '{{agent_name}}': 'Tu nombre',
        '{{assignee_name}}': 'Tu nombre',
        // Mismas opciones explícitas que el resto de fechas mostradas en la
        // pantalla (día/mes abreviado/año, es-ES) — sin ellas, toLocaleDateString
        // sin argumentos cae al formato corto por defecto del motor ("14/9/2026"),
        // que no coincide con ningún otro sitio del sistema (14-sep-2026,
        // auditoría de calidad JS).
        '{{fecha}}': new Date().toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' }),
        '{{erp_id_cliente}}': '4521',
        '{{erp_nif}}': 'B12345678',
        '{{erp_ciudad}}': 'Madrid',
        '{{erp_saldo_pendiente}}': '150.00',
        '{{erp_limite_credito}}': '5000',
        '{{erp_ultimo_pedido_numero}}': 'PED-000987',
        '{{erp_ultimo_pedido_fecha}}': '15/08/2026',
    };

    function applyTicketTemplateSample(text) {
        Object.keys(TTPL_SAMPLE_VALUES).forEach(function (key) {
            text = text.split(key).join(TTPL_SAMPLE_VALUES[key]);
        });

        return text;
    }

    function openTicketTemplatesModal() {
        var all = TKA.state.ticketTemplates || [];
        if (!all.length) { if (window.toastr) toastr.info('No hay plantillas de ticket activas'); return; }
        var chosen = null;

        function listHtml(filter) {
            var q = String(filter || '').trim().toLowerCase();
            var list = all.filter(function (r) { return !q || String(r.name).toLowerCase().indexOf(q) !== -1; });
            if (!list.length) return '<div class="tkt-empty-box">Ninguna plantilla coincide con la búsqueda.</div>';
            return list.map(function (r) {
                var meta = [r.category_name, r.priority ? priorityLabel(r.priority) : null].filter(Boolean).join(' · ');
                return '<button type="button" class="tkt-pick' + (chosen && chosen.id === r.id ? ' on' : '') + '" data-ttpl="' + r.id + '">' +
                    '<span class="av light"><i class="fa-solid fa-clone"></i></span>' +
                    '<span class="who"><span class="n">' + escapeHtml(r.name) + '</span>' +
                    '<span class="s">' + escapeHtml(meta || r.description || 'Sin categoría') + '</span></span>' +
                    (chosen && chosen.id === r.id ? '<i class="fa-solid fa-check"></i>' : '') + '</button>';
            }).join('');
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-clone', kicker: 'Tickets · plantillas',
            title: 'Plantillas de ticket', width: 'lg',
            body: '<div class="tkt-field"><input type="search" class="tkt-input" id="tkt-ttpl-search" placeholder="Buscar plantilla…" aria-label="Buscar plantilla"></div>' +
                '<div class="tkt-pick-list" id="tkt-ttpl-list">' + listHtml('') + '</div>' +
                '<div class="tkt-cap">Vista previa · con datos de ejemplo</div>' +
                '<div class="tkt-tpl-preview" id="tkt-ttpl-preview">Elige una plantilla para ver el ticket que va a crear.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-ttpl-use" disabled>Crear ticket</button>' +
                  '<a class="tkt-btn" href="' + escapeHtml(TKA.urls.ticketTemplatesIndex || '#') + '">Gestionar plantillas</a>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('input', '#tkt-ttpl-search', function () { $backdrop.find('#tkt-ttpl-list').html(listHtml(this.value)); });
        $backdrop.on('click', '[data-ttpl]', function () {
            var id = $(this).data('ttpl');
            chosen = all.find(function (r) { return String(r.id) === String(id); }) || null;
            $backdrop.find('#tkt-ttpl-list').html(listHtml($('#tkt-ttpl-search').val()));
            var previewText = chosen ? [chosen.subject, chosen.body].filter(Boolean).join('\n\n') : '';
            $backdrop.find('#tkt-ttpl-preview').text(previewText ? applyTicketTemplateSample(previewText) : '');
            $backdrop.find('#tkt-ttpl-use').prop('disabled', !chosen);
        });
        $backdrop.on('click', '#tkt-ttpl-use', function () {
            if (!chosen) return;
            // La creación pasa por el formulario normal con los campos ya
            // rellenos: el agente revisa antes de crear y no se duplica la
            // validación de StoreTicketRequest en un endpoint paralelo.
            var params = new URLSearchParams({ template: chosen.id, subject: chosen.subject || '', priority: chosen.priority || '' });
            if (chosen.category_id) params.set('category_id', chosen.category_id);
            window.location = TKA.urls.ticketCreate + '?' + params.toString();
        });
    }


