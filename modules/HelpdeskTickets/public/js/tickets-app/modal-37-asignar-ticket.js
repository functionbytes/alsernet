'use strict';

    // ── Modal 37: Asignar ticket ──────────────────────────────
    function openAssignModal(t) {
        var d = TKA.state.currentDetail;
        var currentId = t.assignee ? t.assignee.id : null;
        var agents = TKA.state.agentsFull || [];
        // Carga real de cada agente, del mismo endpoint que alimenta el modal
        // "Carga de agentes". Sin esto la lista era una fila de nombres
        // idénticos y no había forma de repartir con criterio.
        var carga = TKA.state.agentWorkload || null;

        function subtitulo(a, isCurrent) {
            if (isCurrent) return 'Asignado actualmente';

            // El estado manda sobre la carga: "0 abiertos" en alguien que no ha
            // entrado nunca al panel invita justo al error que se quiere evitar.
            if (a.available === false) return a.status_label || 'No disponible';

            var w = carga && carga[a.id];
            var cargaTexto = w
                ? w.open_tickets + (w.open_tickets === 1 ? ' abierto' : ' abiertos') + (w.at_risk ? ' · ' + w.at_risk + ' en riesgo' : '')
                : '';

            return [a.status_label, cargaTexto].filter(Boolean).join(' · ') || 'Agente';
        }

        function agentRows(filter) {
            var q = String(filter || '').trim().toLowerCase();
            var list = agents.filter(function (a) { return !q || String(a.name).toLowerCase().indexOf(q) !== -1; });
            if (!list.length) return '<div class="tkt-empty-box">Ningún agente coincide con la búsqueda.</div>';

            // Primero quien puede atenderlo, y dentro de ellos el menos
            // ocupado. Antes solo se ordenaba por carga, así que los agentes
            // que nunca han entrado —con cero tickets, claro— encabezaban la
            // lista y eran los primeros candidatos a recibir el ticket.
            list = list.slice().sort(function (a, b) {
                var dispA = a.available === false ? 1 : 0;
                var dispB = b.available === false ? 1 : 0;
                if (dispA !== dispB) return dispA - dispB;

                if (carga) {
                    return ((carga[a.id] && carga[a.id].open_tickets) || 0) - ((carga[b.id] && carga[b.id].open_tickets) || 0);
                }

                return String(a.name).localeCompare(String(b.name));
            });

            return list.map(function (a) {
                var isCurrent = String(a.id) === String(currentId);
                var w = carga && carga[a.id];
                return '<button type="button" class="tkt-pick' + (isCurrent ? ' on' : '') +
                    (a.available === false ? ' is-unavailable' : '') + '" data-agent="' + a.id + '">' +
                    '<span class="av' + (a.available === false ? ' light' : '') + '">' + escapeHtml(initials(a.name)) + '</span>' +
                    '<span class="who"><span class="n">' + escapeHtml(a.name) + '</span>' +
                    '<span class="s">' + escapeHtml(subtitulo(a, isCurrent)) + '</span></span>' +
                    (w && w.at_risk ? '<span class="tkt-chip tkt-chip-warn">' + w.at_risk + '</span>' : '') +
                    (isCurrent ? '<i class="fa-solid fa-check"></i>' : '') +
                '</button>';
            }).join('');
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-user-plus', kicker: 'Ticket · asignación',
            title: 'Asignar ticket', titleChip: t.ticket_number, width: 'lg',
            body: '<div class="tkt-field"><input type="search" class="tkt-input" id="tkt-assign-search" placeholder="Buscar agente…" aria-label="Buscar agente"></div>' +
                '<div class="tkt-pick-list" id="tkt-assign-list">' + agentRows('') + '</div>' +
                (currentId ? '<button type="button" class="tkt-btn tkt-btn-start tkt-w-100" id="tkt-assign-none">Dejar sin asignar</button>' : ''),
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('input', '#tkt-assign-search', function () {
            $backdrop.find('#tkt-assign-list').html(agentRows(this.value));
        });

        // La carga se pide una vez por sesión y se cachea: es la misma para
        // todos los tickets y no cambia entre dos asignaciones seguidas.
        if (!carga && TKA.urls.workload) {
            $.getJSON(TKA.urls.workload).done(function (res) {
                if (!res || !res.agents) return;
                carga = {};
                res.agents.forEach(function (a) { carga[a.id] = a; });
                TKA.state.agentWorkload = carga;
                $backdrop.find('#tkt-assign-list').html(agentRows($backdrop.find('#tkt-assign-search').val()));
            });
        }

        function apply(agentId) {
            patchTicketSilent(t, 'assignee_id', agentId || '', function () {
                var agent = agents.find(function (a) { return String(a.id) === String(agentId); });
                t.assignee = agent ? { id: agent.id, name: agent.name } : null;
                if (d && d.assignment) d.assignment.assigned_at_human = 'hace un momento';
                closeModal();
                renderDetail(t);
                renderSidePanel(t);
            });
        }

        $backdrop.on('click', '[data-agent]', function () { apply($(this).data('agent')); });
        $backdrop.on('click', '#tkt-assign-none', function () { apply(null); });
    }


