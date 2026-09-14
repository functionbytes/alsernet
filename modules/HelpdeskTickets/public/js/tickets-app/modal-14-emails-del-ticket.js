'use strict';

    // ── Modal 14: Emails del ticket ───────────────────────────
    function openMailsListModal(t, mails) {
        var all = mails || [];
        var filter = 'all';

        function visibleList() {
            return all.filter(function (m) {
                return filter === 'all'
                    || (filter === 'inbound' && m.direction === 'inbound')
                    || (filter === 'outbound' && m.direction !== 'inbound');
            });
        }

        function rowsHtml() {
            var list = visibleList();
            if (!list.length) return '<div class="tkt-empty-box">No hay correos en esta pestaña.</div>';
            return list.map(function (m, i) {
                var meta = [
                    m.direction === 'inbound' ? 'Entrante' : 'Saliente',
                    m.created_at_human,
                    m.status,
                    m.attachment_count ? m.attachment_count + (m.attachment_count === 1 ? ' adjunto' : ' adjuntos') : null,
                ].filter(Boolean).join(' · ');
                return '<div class="tkt-mailitem">' +
                    '<span class="av">' + escapeHtml(m.initials || '··') + '</span>' +
                    '<span class="who"><span class="n">' + escapeHtml(m.subject || '(sin asunto)') + '</span>' +
                    '<span class="s">' + escapeHtml(meta) + '</span></span>' +
                    (m.status === 'scheduled'
                        ? '<button type="button" class="tkt-link-btn" data-cancel-sched="' + i + '">Cancelar</button>' : '') +
                    '<button type="button" class="tkt-link-btn" data-link-mail="' + i + '">Mover</button>' +
                '</div>';
            }).join('');
        }

        function tabsHtml() {
            var c = {
                all: all.length,
                inbound: all.filter(function (m) { return m.direction === 'inbound'; }).length,
                outbound: all.filter(function (m) { return m.direction !== 'inbound'; }).length,
            };
            return '<div class="tkt-seg-tabs">' +
                '<button type="button" class="' + (filter === 'all' ? 'on' : '') + '" data-mfilter="all">Todos · ' + c.all + '</button>' +
                '<button type="button" class="' + (filter === 'inbound' ? 'on' : '') + '" data-mfilter="inbound">Entrantes · ' + c.inbound + '</button>' +
                '<button type="button" class="' + (filter === 'outbound' ? 'on' : '') + '" data-mfilter="outbound">Salientes · ' + c.outbound + '</button>' +
            '</div>';
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-envelope-open',
            kicker: 'Ticket · correos',
            title: 'Emails del ticket',
            titleChip: t.ticket_number,
            width: 'lg',
            body: '<div id="tkt-mails-tabs">' + tabsHtml() + '</div>' +
                '<div class="tkt-mailitems" id="tkt-mails-rows">' + rowsHtml() + '</div>' +
                '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Cada correo entrante del hilo se anexa al ticket conservando su <span class="mono">message_id</span>.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-mails-compose">Redactar respuesta</button>' +
                  '<a href="' + TKA.urls.emailsIndex + '?search=' + encodeURIComponent(t.ticket_number) + '" class="tkt-btn">Ver en la bandeja completa</a>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        function refresh() {
            $backdrop.find('#tkt-mails-tabs').html(tabsHtml());
            $backdrop.find('#tkt-mails-rows').html(rowsHtml());
        }

        $backdrop.on('click', '[data-mfilter]', function () { filter = $(this).data('mfilter'); refresh(); });
        $backdrop.on('click', '#tkt-mails-compose', function () { closeModal(); openComposeModal(t); });
        $backdrop.on('click', '[data-cancel-sched]', function () {
            closeModal();
            openCancelScheduledModal(t, visibleList()[parseInt($(this).data('cancel-sched'), 10)]);
        });
        $backdrop.on('click', '[data-link-mail]', function () {
            closeModal();
            openLinkMailModal(t, visibleList()[parseInt($(this).data('link-mail'), 10)]);
        });
    }

    // Fila "clave — valor" de las tablitas del panel derecho y los modales.
    function sideRow(key, value, opts) {
        opts = opts || {};
        return '<div class="tkt-side-row' + (opts.last ? ' last' : '') + '">' +
            '<span class="k">' + escapeHtml(key) + '</span>' +
            '<span class="v' + (opts.mono ? ' mono' : '') + (opts.strong ? ' strong' : '') + '">' + escapeHtml(value) + '</span>' +
        '</div>';
    }

    // Hint pasivo "· cola de correo: N" de la barra de estados + badge del
    // botón "Cola". Reusa OpsHealthService, que ya alimenta el dashboard.
    function fetchOpsQueueHint() {
        if (!TKA.urls.ops) return;
        $.getJSON(TKA.urls.ops).done(function (res) {
            var st = res && res.snapshot;
            if (!st || !st.queues) return;
            var n = st.queues.emails;
            if (n == null) return;
            $('#tkt-mail-queue-hint').text(' · cola de correo: ' + n);
            var $badge = $('#tkt-ops-queue-badge');
            if (n > 0) $badge.text(n).prop('hidden', false);
            else $badge.prop('hidden', true);

            // Mismo dato en la barra de estado del pie — solo se muestra
            // cuando hay algo real en cola, igual que el badge de arriba.
            TKA.state.mailQueueCount = Number(n) || 0;
            if (typeof updateOfflineQueueStatus === 'function') updateOfflineQueueStatus();
        });
    }

    // ═══════════ Barra de estado (pie de pantalla) ═══════════
    // Ningún dato aquí es de relleno: conexión reusa el propio conector de
    // Reverb (el mismo canal de presencia que ya abre openTicketPresence()),
    // agentes en línea reusa TKA.urls.workloadOverview (modal 24 "Carga de
    // agentes"), y SLA/resueltos reusan TKA.state.tabCounts, que ya llega
    // hidratado en el initTicketsApp y se mantiene fresco por recomputeTabCounts()
    // y cada refetch del listado.
    function bindStatusBar() {
        if (!$('#tkt-status-bar').length) return;

        renderStatusCounts();
        bindStatusConnection();
        fetchOnlineAgentsCount();

        var soundOn = localStorage.getItem('tkt:status:sound') === '1';
        var $soundBtn = $('#tkt-status-sound');
        $soundBtn.toggleClass('on', soundOn).attr('aria-pressed', soundOn ? 'true' : 'false')
            .find('i').toggleClass('fa-volume-high', soundOn).toggleClass('fa-volume-xmark', !soundOn);
        $soundBtn.on('click', function () {
            soundOn = !soundOn;
            localStorage.setItem('tkt:status:sound', soundOn ? '1' : '0');
            $(this).toggleClass('on', soundOn).attr('aria-pressed', soundOn ? 'true' : 'false')
                .find('i').toggleClass('fa-volume-high', soundOn).toggleClass('fa-volume-xmark', !soundOn);
        });

        $('#tkt-status-shortcuts').on('click', openShortcutsModal);
        $('#tkt-status-queue').off('click.tktOffline').on('click.tktOffline', openOfflineQueueModal);
    }

    /**
     * SLA en riesgo / resueltos de la barra: mismos números que ya pintan
     * los tabs de arriba (TKA.state.tabCounts), no un cálculo aparte —
     * llamarla junto a cada sitio que ya actualiza esos tabs evita que la
     * barra se desincronice de ellos (mismo bug de fondo que
     * recomputeTabCounts() ya documenta para "Todos"/"Resueltos").
     */
    function renderStatusCounts() {
        var c = TKA.state.tabCounts || {};
        if (c.sla_risk != null) $('#tkt-status-sla').html('<i class="fa-regular fa-clock"></i> SLA en riesgo: ' + c.sla_risk);
        if (c.resolved != null) $('#tkt-status-resolved').html('<i class="fa-solid fa-circle-check"></i> ' + c.resolved + ' resueltos');
    }

    /**
     * Punto de estado real: escucha el conector de Reverb/Pusher (el mismo
     * que usa la presencia del ticket abierto) en vez de asumir "conectado"
     * porque la página cargó. Sin Echo/Reverb en este entorno el punto se
     * queda apagado — el propio arranque de la presencia ya tolera esto
     * (ver openTicketPresence()), aquí se refleja el mismo hecho en vez de
     * mentir con un punto verde fijo.
     */
    function bindStatusConnection() {
        var $dot = $('#tkt-status-conn-dot');
        var $text = $('#tkt-status-conn-text');

        function paint(state) {
            $dot.removeClass('on connecting off');
            if (state === 'connected') { $dot.addClass('on'); $text.text('Conectado'); } else if (state === 'connecting' || state === 'unavailable') { $dot.addClass('connecting'); $text.text('Conectando…'); } else { $dot.addClass('off'); $text.text('Sin conexión en vivo'); }
        }

        var connectWaits = 0;
        var bind = function () {
            if (TKA.state.statusConnectionBound) return;

            if (typeof window.Echo === 'undefined' || !window.Echo.connector || !window.Echo.connector.pusher) {
                paint('off');
                // En la entrada directa Echo puede aparecer después de que
                // initTicketsApp() haya pintado la barra. Esperar aquí evita
                // dejar el indicador en rojo toda la sesión por una carrera
                // de carga entre Vite y el bundle de Tickets.
                if (++connectWaits <= 20) window.setTimeout(bind, 500);
                return;
            }

            TKA.state.statusConnectionBound = true;
            var pusher = window.Echo.connector.pusher;
            var previous = pusher.connection.state;
            paint(previous);

            pusher.connection.bind('state_change', function (states) {
                var current = states.current;
                paint(current);

                // Durante una caída se pueden perder eventos broadcast. Al
                // recuperar el socket se sincronizan lista, contadores y el
                // ticket abierto; el refetch respeta filtros y no modifica
                // el historial del navegador.
                if (current === 'connected' && previous !== 'connected') {
                    var ticket = TKA.state.currentTicket || null;
                    if (typeof queueTicketListRefresh === 'function') {
                        queueTicketListRefresh('reconnected', ticket, {
                            freshCounts: true,
                            refreshDetail: !!ticket,
                            forceDetail: !!ticket,
                            silent: true,
                            delay: 0,
                        });
                    }
                }

                previous = current;
            });

            window.addEventListener('offline', function () { paint('off'); });
            window.addEventListener('online', function () {
                paint(pusher.connection.state);
                if (pusher.connection.state === 'connected' && typeof queueTicketListRefresh === 'function') {
                    var ticket = TKA.state.currentTicket || null;
                    queueTicketListRefresh('browser-online', ticket, {
                        freshCounts: true,
                        refreshDetail: !!ticket,
                        forceDetail: !!ticket,
                        silent: true,
                        delay: 0,
                    });
                }
            });
        };

        bind();
    }

    /**
     * Cuenta agentes en línea de verdad — bug real (QA 14-sep-2026): esto
     * leía TKA.urls.workloadOverview (modal 24 "Carga de agentes"), cuyo
     * JSON nunca trae un campo 'status' — el comentario original decía que
     * reutilizaba "AgentAvailabilityService::forWorkload()", un método que
     * no existe en el repo. Resultado: el filtro daba siempre 0, el "0
     * agentes en línea" que se ve en el pie de pantalla sea cual sea la
     * realidad.
     *
     * AgentPresenceController::list() (módulo Helpdesk hermano) sí calcula
     * presencia real con heartbeat en Redis — ver agentPresenceBeat() más
     * arriba en core.js, que es quien alimenta ese heartbeat mientras el
     * panel está abierto. "En línea" = cualquier estado que no sea
     * 'offline' (disponible/ocupado/ausente cuentan como conectado; el
     * matiz de disponibilidad es otro dato, no este contador).
     */
    function fetchOnlineAgentsCount() {
        var $el = $('#tkt-status-agents');
        if (!TKA.urls.agentPresenceAgents) { $el.hide(); return; }

        $.getJSON(TKA.urls.agentPresenceAgents).done(function (res) {
            var agentes = (res && res.agents) || [];
            var enLinea = agentes.filter(function (a) { return a.presence_state && a.presence_state !== 'offline'; }).length;
            $el.html('<i class="fa-solid fa-users"></i> ' + enLinea + (enLinea === 1 ? ' agente en línea' : ' agentes en línea'));
        }).fail(function () {
            $el.hide();
        });
    }

    /**
     * Aviso sonoro de mensaje nuevo (toggle de la barra de estado). Un tono
     * generado con Web Audio en vez de un mp3: nada que publicar en
     * public/modules/helpdesktickets/ ni que cargar de un CDN para un pitido
     * de un solo uso. Se salta entero si el agente lo apagó o si el
     * navegador bloquea el audio sin interacción previa (Safari/Chrome
     * exigen un gesto del usuario antes del primer sonido — el propio click
     * en el botón de la barra ya cuenta como uno).
     */
    function playNewMessageSound() {
        if (localStorage.getItem('tkt:status:sound') !== '1') return;

        try {
            var Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            var ctx = new Ctx();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = 720;
            gain.gain.setValueAtTime(0.001, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.18, ctx.currentTime + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.22);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.24);
            osc.onended = function () { ctx.close(); };
        } catch (e) { /* audio bloqueado por el navegador: sin aviso, sin romper nada */ }
    }
