'use strict';

    // ── Modal 44: Plantillas de ticket ────────────────────────
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
            $backdrop.find('#tkt-ttpl-preview').text(chosen ? [chosen.subject, chosen.body].filter(Boolean).join('\n\n') : '');
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


