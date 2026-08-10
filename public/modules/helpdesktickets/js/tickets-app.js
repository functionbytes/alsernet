/**
 * Gestión de tickets — /panel/helpdesk/tickets/list
 * Fase A: shell de 3 columnas, listado, tabs de estado, filtros, bulk
 * actions y una vista de detalle básica (solo con los datos ya cargados
 * en #tkt-data, sin AJAX todavía). El hilo completo, la trazabilidad, el
 * modo Kanban con arrastrar/soltar y el panel lateral de 8 pestañas llegan
 * en las fases siguientes — ver el plan de "Gestión de tickets".
 *
 * Mismo patrón de namespace/hidratación que emails.js (bandeja "Emails
 * enviados"): un único objeto TKA = {state, urls}, bootstrap() lee
 * #tkt-data, y las funciones render* son puras sobre TKA.state.
 */
(function ($) {
    'use strict';

    var TKA = {
        state: {
            tickets: [],
            tabCounts: {},
            filter: 'open',
            selected: null,
            currentUserId: null,
            bulk: {},
        },
        urls: {},
    };

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function initials(name) {
        var parts = String(name || '').trim().split(/\s+/).filter(Boolean);
        if (!parts.length) return '?';
        return (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase();
    }

    var ORIGIN_LABELS = {
        email: 'Email', widget: 'Widget', wa: 'WhatsApp', fb: 'Facebook',
        ig: 'Instagram', agent: 'Agente', formulario: 'Formulario', web_form: 'Formulario',
    };

    var STATUS_LABEL_FALLBACK = {
        open: 'Abierto', progress: 'En curso', pending: 'En espera', resolved: 'Resuelto', closed: 'Cerrado',
    };

    var PRIORITY_LABELS = { low: 'Baja', normal: 'Normal', high: 'Alta', urgent: 'Urgente' };
    function priorityLabel(slug) {
        return PRIORITY_LABELS[slug] || slug;
    }

    // Mismo criterio de color que Ticket::PRIORITY_COLORS (backend) y el
    // mockup (chip de prioridad con color propio, no siempre gris) — bug de
    // diseño encontrado en QA visual: el chip de prioridad en la cabecera
    // del detalle usaba tkt-chip-muted fijo, así que "Alta"/"Urgente" no se
    // distinguían de "Baja"/"Normal" de un vistazo.
    var PRIORITY_CHIP_CLASS = { low: 'tkt-chip-muted', normal: 'tkt-chip-info', high: 'tkt-chip-warn', urgent: 'tkt-chip-danger' };
    function priorityChipClass(slug) {
        return PRIORITY_CHIP_CLASS[slug] || 'tkt-chip-muted';
    }

    // Clase de chip por estado real (open/pending/resolved/closed) — antes
    // el chip de los tickets relacionados era siempre gris sin importar si
    // seguían abiertos o ya se habían cerrado.
    var STATUS_CHIP_CLASS = { open: 'tkt-chip-info', progress: 'tkt-chip-info', pending: 'tkt-chip-warn', resolved: 'tkt-chip-ok', closed: 'tkt-chip-muted' };
    function statusChipClass(slug) {
        return STATUS_CHIP_CLASS[slug] || 'tkt-chip-muted';
    }

    var FILE_ICON_EXT_MAP = {
        pdf: 'fa-file-pdf',
        doc: 'fa-file-word', docx: 'fa-file-word',
        xls: 'fa-file-excel', xlsx: 'fa-file-excel', csv: 'fa-file-excel',
        jpg: 'fa-file-image', jpeg: 'fa-file-image', png: 'fa-file-image', gif: 'fa-file-image', webp: 'fa-file-image',
        zip: 'fa-file-zipper', rar: 'fa-file-zipper',
    };
    function fileIconClass(filename) {
        var ext = String(filename || '').split('.').pop().toLowerCase();
        return 'fa-solid ' + (FILE_ICON_EXT_MAP[ext] || 'fa-file');
    }

    // ═══════════ Bootstrap ═══════════
    function bootstrap() {
        var $data = $('#tkt-data');
        if (!$data.length) return;

        TKA.state.tickets = safeJson($data.attr('data-tickets'), []);
        TKA.state.tabCounts = safeJson($data.attr('data-tab-counts'), {});
        TKA.state.currentUserId = $data.attr('data-user-id') ? parseInt($data.attr('data-user-id'), 10) : null;
        TKA.state.filter = $data.attr('data-initial-filter') || 'all';
        TKA.state.statuses = safeJson($data.attr('data-statuses'), []);
        TKA.state.categories = safeJson($data.attr('data-categories'), []);
        TKA.state.groups = safeJson($data.attr('data-groups'), []);
        TKA.state.agentsFull = safeJson($data.attr('data-agents-full'), []);
        TKA.state.cannedReplies = safeJson($data.attr('data-canned-replies'), []);
        TKA.state.closeReasons = safeJson($data.attr('data-close-reasons'), []);
        var selectedId = $data.attr('data-selected-id');
        TKA.state.selected = selectedId ? parseInt(selectedId, 10) : null;

        TKA.urls.bulk = $data.attr('data-bulk-url');
        TKA.urls.index = $data.attr('data-index-url');
        TKA.urls.emailsIndex = $data.attr('data-emails-index-url');
        TKA.urls.notesStoreTemplate = $data.attr('data-notes-store-url-template');
        TKA.urls.viewsStore = $data.attr('data-views-store-url');
        TKA.urls.contactsSyncTemplate = $data.attr('data-contacts-sync-url-template');
        TKA.urls.macrosList = $data.attr('data-macros-list-url');
        TKA.urls.emailsStore = $data.attr('data-emails-store-url');
        TKA.urls.typingTemplate = $data.attr('data-typing-url-template');
        TKA.urls.exportTemplate = $data.attr('data-export-url-template');
        TKA.urls.automationsIndex = $data.attr('data-automations-index-url');
        TKA.urls.contactsMergeSearchTemplate = $data.attr('data-contacts-merge-search-url-template');
        TKA.urls.contactsMergePreviewTemplate = $data.attr('data-contacts-merge-preview-url-template');
        TKA.urls.contactsMergeExecuteTemplate = $data.attr('data-contacts-merge-execute-url-template');
        TKA.urls.ops = $data.attr('data-ops-url');
        TKA.urls.workload = $data.attr('data-workload-url');
        TKA.urls.workloadDistribute = $data.attr('data-workload-distribute-url');

        bindEvents();
        renderTabs();

        // Selects estáticos ya presentes en el DOM al cargar la página: la
        // barra de filtros (Origen/Categoría/Agente/Prioridad/Etiquetas) y
        // los del modal "Más filtros" (éste último oculto pero ya en el DOM,
        // no inyectado por JS). Los generados dinámicamente (panel Gestión,
        // modales de bulk, etc.) se inicializan en su propio render.
        initSelect2();

        // El listado se hidrata de forma síncrona desde #tkt-data (sin AJAX
        // real todavía — ver cabecera del archivo), pero el esqueleto sigue
        // el mismo criterio que tendría un refetch real: visible mientras
        // se prepara la lista, oculto en cuanto está pintada.
        $('#tkt-skeleton').addClass('on');
        $('#tkt-list').hide();
        renderList();
        $('#tkt-skeleton').removeClass('on');
        $('#tkt-list').show();

        if (TKA.state.selected) {
            var pre = TKA.state.tickets.find(function (t) { return t.id === TKA.state.selected; });
            if (pre) selectTicket(pre);
        }
    }

    function safeJson(raw, fallback) {
        if (!raw) return fallback;
        try { return JSON.parse(raw); } catch (e) { return fallback; }
    }

    // ═══════════ Filtro de tabs (mismo criterio que el toolbar htk-pill anterior) ═══════════
    function passesFilter(t) {
        var f = TKA.state.filter;
        if (f === 'all') return true;
        if (f === 'mine') return t.assignee && t.assignee.id === TKA.state.currentUserId;
        if (f === 'unassigned') return !t.assignee;
        if (f === 'urgent') return t.priority === 'urgent' || t.sla_kind === 'breach';
        if (f === 'sla_risk') return t.sla_kind === 'warn' || t.sla_kind === 'breach';
        if (f === 'pending') return t.status_slug === 'pending';
        if (f === 'resolved') return t.status_slug === 'resolved';
        if (f === 'open') return t.status_slug === 'open' || t.status_slug === 'progress';
        return t.status_slug === f;
    }

    function visibleTickets() {
        return TKA.state.tickets.filter(passesFilter);
    }

    // ═══════════ Render: tabs de estado + chips de vistas ═══════════
    // Ambas barras (tabs de estado y chips "Vistas") comparten el mismo
    // filtro activo (TKA.state.filter) — igual que el mockup, donde
    // savedView() también reemplaza el filtro de estado en vez de
    // combinarse con él.
    function renderTabs() {
        $('.tkt-state-tab[data-filter], .tkt-view-pill[data-filter]').each(function () {
            var $t = $(this);
            $t.toggleClass('on', $t.data('filter') === TKA.state.filter);
        });
    }

    // Recalcula los contadores de tabs/vistas a partir de TKA.state.tickets
    // (mismo criterio que passesFilter) — necesario tras el Kanban, cuyo
    // "soltar" cambia el estado sin recargar la página (a diferencia de
    // bulk/Gestión, que sí recargan y traen los conteos frescos del
    // servidor). Sin esto, "Pendientes/Resueltos"/"SLA en riesgo" quedaban
    // desactualizados después de arrastrar una tarjeta — bug real
    // encontrado al probar el drag&drop.
    function recomputeTabCounts() {
        var c = { open: 0, urgent: 0, mine: 0, unassigned: 0, pending: 0, resolved: 0, closed: 0, sla_risk: 0, all: TKA.state.tickets.length };
        TKA.state.tickets.forEach(function (t) {
            if (t.status_slug === 'open' || t.status_slug === 'progress') c.open++;
            if (t.priority === 'urgent' || t.sla_kind === 'breach') c.urgent++;
            if (t.assignee && t.assignee.id === TKA.state.currentUserId) c.mine++;
            if (!t.assignee) c.unassigned++;
            if (t.status_slug === 'pending') c.pending++;
            if (t.status_slug === 'resolved') c.resolved++;
            if (t.status_slug === 'closed') c.closed++;
            if (t.sla_kind === 'warn' || t.sla_kind === 'breach') c.sla_risk++;
        });
        TKA.state.tabCounts = c;
        Object.keys(c).forEach(function (k) {
            $('.tkt-state-tab[data-filter="' + k + '"] .c, .tkt-view-pill[data-filter="' + k + '"] .mono').text(c[k]);
        });
        $('.tkt-queue-hint').text('SLA en riesgo: ' + c.sla_risk);
    }

    // ═══════════ Render: lista ═══════════
    function slaClass(kind) {
        if (kind === 'breach') return 'tkt-sla-breach';
        if (kind === 'warn') return 'tkt-sla-warn';
        return 'tkt-sla-ok';
    }

    function renderRow(t) {
        var isActive = TKA.state.selected === t.id;
        var checked = TKA.state.bulk[t.id] ? 'checked' : '';
        var statusRowClass = 's-' + (t.status_slug || 'open');
        var statusLabel = t.status_name || STATUS_LABEL_FALLBACK[t.status_slug] || t.status_slug;
        var originLabel = ORIGIN_LABELS[t.source] || t.source || '—';
        var prioClass = t.priority === 'urgent' ? 'tkt-prio-urgent' : (t.priority === 'high' ? 'tkt-prio-high' : '');
        var timeLabel = t.updated_at ? new Date(t.updated_at).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }) : '—';

        var $row = $(
            '<div class="tkt-ticket-row ' + statusRowClass + (isActive ? ' on' : '') + '" data-id="' + t.id + '">' +
            '<input type="checkbox" class="tkt-ticket-check" data-check="' + t.id + '" ' + checked + '>' +
            '<div class="tkt-ticket-main">' +
                '<span class="tkt-ticket-subject">' + escapeHtml(t.subject || '(sin asunto)') + '</span>' +
                '<div class="tkt-ticket-sub">' +
                    '<span class="tkt-ticket-id">' + escapeHtml(t.ticket_number) + '</span>' +
                    '<span class="tkt-trunc">' + escapeHtml(t.customer ? t.customer.name : 'Sin cliente') + '</span>' +
                '</div>' +
                '<div class="tkt-ticket-meta">' +
                    '<span class="tkt-ticket-state">' + escapeHtml(statusLabel) + '</span>' +
                    '<span class="tkt-divider-v"></span>' +
                    '<span class="tkt-origin" title="' + escapeHtml(originLabel) + '">' + escapeHtml(originLabel) + '</span>' +
                    '<span class="tkt-trunc">' + escapeHtml(t.category_name || '') + '</span>' +
                    (t.message_count ? '<span class="mono" style="margin-left:auto;color:var(--tkt-text-faint);font-size:10px"><i class="fa-regular fa-comment"></i> ' + t.message_count + '</span>' : '') +
                    (t.unread_count > 0 ? '<span style="color:var(--tkt-danger-fg);font-weight:700">●</span>' : '') +
                '</div>' +
            '</div>' +
            '<div class="tkt-ticket-side">' +
                '<span class="tkt-ticket-time">' + timeLabel + '</span>' +
                '<span class="tkt-sla ' + slaClass(t.sla_kind) + '"><span class="tkt-sla-dot"></span>' + escapeHtml(t.sla_text || '—') + '</span>' +
                (t.priority && t.priority !== 'normal' && t.priority !== 'low'
                    ? '<span class="tkt-prio ' + prioClass + '"><span class="tkt-prio-dot"></span>' + escapeHtml(priorityLabel(t.priority)) + '</span>'
                    : '') +
            '</div>' +
            '</div>'
        );

        $row.find('[data-check]').on('click', function (ev) { ev.stopPropagation(); });
        $row.find('[data-check]').on('change', function () {
            var id = parseInt($(this).data('check'), 10);
            if (this.checked) TKA.state.bulk[id] = true; else delete TKA.state.bulk[id];
            renderBulkBar();
        });
        $row.on('click', function () {
            var t2 = TKA.state.tickets.find(function (x) { return x.id === t.id; });
            if (t2) selectTicket(t2);
        });

        return $row;
    }

    function renderList() {
        var rows = visibleTickets();
        var $list = $('#tkt-list').empty();

        if (!rows.length) {
            $list.append(
                '<div class="tkt-empty-state">' +
                '<div class="tkt-empty-icon"><i class="fa-solid fa-ticket"></i></div>' +
                '<div class="tkt-empty-title">No hay tickets</div>' +
                '<div class="tkt-empty-text">No se encontraron tickets que coincidan con este filtro.</div>' +
                '</div>'
            );
        } else {
            rows.forEach(function (t) { $list.append(renderRow(t)); });
        }

        $('#tkt-count').text(rows.length + (rows.length === 1 ? ' ticket' : ' tickets') + ' en esta página');
    }

    // ═══════════ Kanban (Fase D) ═══════════
    // El Kanban ignora deliberadamente el filtro de tabs activo (a
    // diferencia de la lista): su propósito es visualizar el flujo de
    // trabajo completo entre estados, así que agrupa TODOS los tickets
    // cargados en la página, no solo los del tab seleccionado.
    var KANBAN_COLS = [
        { key: 'unassigned', label: 'Sin asignar', dot: 'unassigned' },
        { key: 'open', label: 'Abiertos', dot: 'open' },
        { key: 'pending', label: 'Pendientes', dot: 'pending' },
        { key: 'resolved', label: 'Resueltos', dot: 'resolved' },
        { key: 'closed', label: 'Cerrados', dot: 'closed' },
    ];

    function bucketFor(t) {
        if (!t.assignee) return 'unassigned';
        if (t.status_slug === 'open' || t.status_slug === 'progress') return 'open';
        if (t.status_slug === 'pending') return 'pending';
        if (t.status_slug === 'resolved') return 'resolved';
        if (t.status_slug === 'closed') return 'closed';
        return 'open';
    }

    function renderKanbanCard(t) {
        var $card = $(
            '<div class="tkt-kcard" draggable="true" data-id="' + t.id + '">' +
                '<div style="font-size:12px;font-weight:600;line-height:1.4">' + escapeHtml(t.subject || '(sin asunto)') + '</div>' +
                '<div style="font-size:11px;color:var(--tkt-text-mute)" class="tkt-trunc">' + escapeHtml(t.customer ? t.customer.name : 'Sin cliente') + '</div>' +
                '<div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">' +
                    '<span class="tkt-chip-mono">' + escapeHtml(t.ticket_number) + '</span>' +
                    '<span style="font-size:10px;color:var(--tkt-text-faint)">' + escapeHtml(ORIGIN_LABELS[t.source] || t.source || '') + '</span>' +
                    '<span class="tkt-sla ' + slaClass(t.sla_kind) + '" style="margin-left:auto"><span class="tkt-sla-dot"></span>' + escapeHtml(t.sla_text || '—') + '</span>' +
                '</div>' +
            '</div>'
        );
        $card.on('click', function () { selectTicket(t); });
        $card.on('dragstart', function (ev) {
            TKA.state.dragged = t;
            $card.addClass('dragging');
            if (ev.originalEvent.dataTransfer) ev.originalEvent.dataTransfer.effectAllowed = 'move';
        });
        $card.on('dragend', function () { $card.removeClass('dragging'); });
        return $card;
    }

    function renderKanban() {
        var $board = $('#tkt-kanban').empty();
        var buckets = {};
        KANBAN_COLS.forEach(function (c) { buckets[c.key] = []; });
        TKA.state.tickets.forEach(function (t) { buckets[bucketFor(t)].push(t); });

        KANBAN_COLS.forEach(function (col) {
            var items = buckets[col.key];
            var $drop = $('<div class="tkt-kcol-drop" data-bucket="' + col.key + '"></div>');
            items.forEach(function (t) { $drop.append(renderKanbanCard(t)); });

            $drop.on('dragover', function (ev) { ev.preventDefault(); $drop.addClass('over'); });
            $drop.on('dragleave', function () { $drop.removeClass('over'); });
            $drop.on('drop', function (ev) {
                ev.preventDefault();
                $drop.removeClass('over');
                if (TKA.state.dragged) moveTicketToBucket(TKA.state.dragged, col.key);
            });

            var $col = $(
                '<div class="tkt-kcol">' +
                    '<div class="tkt-kcol-head">' +
                        '<span class="tkt-kcol-dot ' + col.dot + '"></span>' +
                        '<span>' + col.label + '</span>' +
                        '<span class="tkt-kcol-count">' + items.length + '</span>' +
                    '</div>' +
                '</div>'
            );
            $col.append($drop);
            $board.append($col);
        });
    }

    function moveTicketToBucket(t, bucket) {
        if (bucketFor(t) === bucket) return;

        if (bucket === 'unassigned') {
            patchTicketSilent(t, 'assignee_id', '', function () { t.assignee = null; renderKanban(); });
            return;
        }

        var targetStatus = (TKA.state.statuses || []).find(function (s) { return s.slug === bucket; });
        if (!targetStatus) {
            if (window.toastr) toastr.error('No existe un estado "' + bucket + '" configurado.');
            renderKanban();
            return;
        }
        patchTicketSilent(t, 'status_id', targetStatus.id, function () {
            t.status_id = targetStatus.id;
            t.status_slug = bucket;
            t.status_name = targetStatus.name;
            renderKanban();
        });
    }

    // Variante de patchTicket() sin recargar la página entera — el Kanban
    // necesita mover la tarjeta al soltar sin perder el modo/posición de
    // scroll, a diferencia de los selects de la Gestión, que sí recargan.
    function patchTicketSilent(t, field, value, onSuccess) {
        var data = { _method: 'PUT' };
        data[field] = value;
        $.ajax({
            url: t.url_update,
            method: 'POST',
            data: data,
            headers: { Accept: 'application/json' },
            success: function () {
                if (window.toastr) toastr.success('Ticket actualizado');
                onSuccess();
                recomputeTabCounts();
                renderTabs();
                renderList();
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo mover el ticket';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
                renderKanban();
            },
        });
    }

    // ═══════════ Vistas guardadas: guardado rápido (Fase D) ═══════════
    // Modal propio en vez de window.prompt() — bug de diseño real: era el
    // único punto del app bar principal que rompía el estilo con el diálogo
    // nativo del navegador, sin marca ni control sobre texto/validación.
    function saveCurrentView() {
        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-bookmark',
            title: 'Guardar vista',
            width: 'sm',
            body:
                '<div class="tkt-field">' +
                    '<label class="tkt-label">Nombre<span class="req">*</span><span class="hint">guarda el origen/categoría/agente/prioridad activos</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-save-view-name" maxlength="255" placeholder="Ej: Urgentes de facturación">' +
                '</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-save-view-confirm">Guardar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.find('#tkt-save-view-name').trigger('focus');
        $backdrop.on('keydown', '#tkt-save-view-name', function (ev) {
            if (ev.key === 'Enter') { ev.preventDefault(); $backdrop.find('#tkt-save-view-confirm').trigger('click'); }
        });

        $backdrop.on('click', '#tkt-save-view-confirm', function () {
            var name = ($backdrop.find('#tkt-save-view-name').val() || '').trim();
            if (!name) { if (window.toastr) toastr.error('Escribe un nombre para la vista'); return; }

            var params = new URLSearchParams(window.location.search);
            var filters = {};
            if (params.get('source')) filters.source = params.get('source');
            if (params.get('category')) filters.category_id = params.get('category');
            if (params.get('assignee') === 'me') filters.mine = true;
            else if (params.get('assignee') === 'unassigned') filters.unassigned = true;
            else if (params.get('assignee')) filters.assignee_id = params.get('assignee');
            if (params.get('priority')) filters.priority = params.get('priority');

            closeModal();
            $.ajax({
                url: TKA.urls.viewsStore,
                method: 'POST',
                data: { name: name, filters: filters },
                headers: { Accept: 'application/json' },
                success: function () {
                    if (window.toastr) toastr.success('Vista guardada');
                    window.location.reload();
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo guardar la vista';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    // Resincroniza las integraciones (ERP/PrestaShop) del cliente del ticket
    // seleccionado — mismo backend real que la ficha de Contactos 360
    // (ContactAggregatorService::syncIntegrations vía la ruta contacts.sync).
    // Sin ticket seleccionado, sin cliente vinculado o con el módulo
    // HelpdeskContacts desactivado, se avisa honestamente en vez de fingir
    // progreso.
    function syncCurrentCustomer() {
        var d = TKA.state.currentDetail;
        var customer = d && d.customer;

        if (!customer || !customer.id || !TKA.urls.contactsSyncTemplate) {
            var warnMsg = 'Este ticket no tiene un cliente vinculado con integraciones que sincronizar.';
            if (window.toastr) toastr.warning(warnMsg); else window.alert(warnMsg);
            return;
        }

        var $btn = $('#tkt-sync');
        $btn.prop('disabled', true);

        $.ajax({
            url: TKA.urls.contactsSyncTemplate.replace('__CUSTOMER__', customer.id),
            method: 'POST',
            headers: { Accept: 'application/json' },
            success: function (res) {
                var integrations = (res && res.data && res.data.integrations) || [];
                var connected = integrations.filter(function (i) { return i.connected; }).map(function (i) { return i.label; });
                var msg = connected.length
                    ? 'Sincronizado con ' + connected.join(', ') + '.'
                    : 'Sincronizado: sin integraciones externas encontradas para este cliente.';
                if (window.toastr) toastr.success(msg); else window.alert(msg);
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo sincronizar las integraciones del cliente.';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            },
            complete: function () {
                $btn.prop('disabled', false);
            },
        });
    }

    // ═══════════ Modales genéricos (design system del mockup) ═══════════
    // A diferencia del mockup original (que clona plantillas estáticas ya
    // pre-renderizadas en el HTML), aquí cada "openXxxModal()" construye su
    // propio HTML con datos reales del ticket seleccionado y lo pasa a
    // openModal(). Reemplaza los window.prompt()/confirm() encadenados que
    // se usaban como solución provisional en Aplazar/Vincular/Fusionar/
    // Etiquetar/Conversación paralela. Se cierra con la X (o cualquier
    // elemento con [data-modal-close]), clic en el fondo, o Escape.
    function openModal(html) {
        closeModal();
        var $backdrop = $('<div class="tkt-modal-backdrop on" id="tkt-modal-backdrop"></div>').html(html);
        // Se añade dentro de .tkt (no de body): las variables --tkt-* solo
        // se definen bajo ese selector — fuera de él el modal se renderiza
        // sin fondo/borde/sombra (transparente). position:fixed hace que
        // igualmente cubra toda la ventana pese a no ser hijo de <body>.
        $('.tkt').append($backdrop);
        $('body').css('overflow', 'hidden');
        $backdrop.on('mousedown', function (ev) {
            if (ev.target === this) closeModal();
        });
        $backdrop.on('click', '[data-modal-close]', function () { closeModal(); });
        $(document).on('keydown.tktModal', function (ev) {
            if (ev.key === 'Escape') closeModal();
        });
        // Todo modal dinámico (bulk actions, seguidores, conversación
        // paralela…) trae sus <select> ya con las opciones finales en el
        // html pasado a openModal() — un único punto de inicialización
        // cubre cualquier modal presente y futuro sin tener que acordarse
        // de llamarlo en cada función que abre uno.
        initSelect2($backdrop);
        return $backdrop;
    }

    function closeModal() {
        $('#tkt-modal-backdrop').remove();
        $(document).off('keydown.tktModal');
        $('body').css('overflow', '');
    }

    // Sustituye a los window.confirm() encadenados que quedaban sueltos
    // (cancelar seguimiento, eliminar nota, desvincular ticket, bulk actions
    // directas) — bug de diseño real encontrado en QA: eran los únicos
    // puntos de la pantalla que rompían el estilo con el diálogo nativo del
    // navegador, sin marca ni control sobre el texto/botones.
    function openConfirmModal(opts) {
        var $backdrop = openModal(modalShell({
            icon: opts.icon || 'fa-solid fa-triangle-exclamation',
            iconClass: opts.danger ? 'danger' : '',
            title: opts.title,
            width: 'sm',
            body: '<p style="margin:0;font-size:var(--tkt-t-md);color:var(--tkt-text-soft)">' + escapeHtml(opts.message) + '</p>',
            foot: '<button type="button" class="tkt-btn ' + (opts.danger ? 'tkt-btn-danger' : 'tkt-btn-primary') + '" id="tkt-confirm-ok">' + escapeHtml(opts.confirmLabel || 'Confirmar') + '</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));
        $backdrop.on('click', '#tkt-confirm-ok', function () {
            closeModal();
            opts.onConfirm();
        });
    }

    // Para modales ESTÁTICOS ya presentes en el blade (el formulario "Más
    // filtros" trae los <option selected> ya resueltos por el servidor, no
    // tiene sentido reconstruirlo con openModal()/HTML por JS) — mismo
    // comportamiento de apertura/cierre (fondo, ✕, Escape) que los dinámicos.
    function bindStaticModal(openSel, backdropSel, closeSel) {
        var $backdrop = $(backdropSel);
        $(openSel).on('click', function () {
            $backdrop.addClass('on');
            $('body').css('overflow', 'hidden');
        });
        function close() {
            $backdrop.removeClass('on');
            $('body').css('overflow', '');
        }
        $(closeSel).on('click', close);
        $backdrop.on('mousedown', function (ev) {
            if (ev.target === this) close();
        });
        $(document).on('keydown.' + backdropSel.replace(/[^\w]/g, ''), function (ev) {
            if (ev.key === 'Escape' && $backdrop.hasClass('on')) close();
        });
    }

    // Cabecera + pie estándar de modal (mismo markup en los ~15 modales
    // reales que se han ido añadiendo) — evita repetir el boilerplate.
    function modalShell(opts) {
        var iconCls = opts.iconClass ? ' ' + opts.iconClass : '';
        return '' +
            '<div class="tkt-modal' + (opts.width ? ' w-' + opts.width : '') + '">' +
                '<div class="tkt-modal-head">' +
                    '<div class="tkt-modal-icon' + iconCls + '"><i class="' + opts.icon + '"></i></div>' +
                    '<div style="flex:1;min-width:0">' +
                        (opts.kicker ? '<div class="tkt-modal-kicker">' + escapeHtml(opts.kicker) + '</div>' : '') +
                        '<div class="tkt-modal-title">' + escapeHtml(opts.title) + '</div>' +
                    '</div>' +
                    '<button type="button" class="tkt-modal-close" data-modal-close><i class="fa-solid fa-xmark"></i></button>' +
                '</div>' +
                '<div class="tkt-modal-body">' + opts.body + '</div>' +
                (opts.foot ? '<div class="tkt-modal-foot' + (opts.footTwoCol ? ' two-col' : '') + '">' + opts.foot + '</div>' : '') +
            '</div>';
    }

    // ═══════════ Render: detalle básico (Fase A — sin AJAX todavía) ═══════════
    function chip(text, cls) {
        return '<span class="tkt-chip ' + (cls || 'tkt-chip-muted') + '">' + escapeHtml(text) + '</span>';
    }

    function selectTicket(t) {
        TKA.state.selected = t.id;
        $('.tkt-ticket-row').removeClass('on');
        $('.tkt-ticket-row[data-id="' + t.id + '"]').addClass('on');
        // Deep-link real: al seleccionar un ticket, la URL de ESTA MISMA
        // pantalla lo referencia (?ticket=id) — igual que Helpdesk hace con
        // ?selected= en Conversaciones. No se navega a otra página/paradigma
        // distinto (la vieja ficha /tickets/{id}/full).
        if (window.history && window.history.replaceState) {
            var params = new URLSearchParams(window.location.search);
            params.set('ticket', t.id);
            window.history.replaceState(null, '', window.location.pathname + '?' + params.toString());
        }
        renderDetail(t);
        renderSidePanel(t);
    }

    // Texto "Creado ... · <agente o sin asignar>" de la cabecera del
    // detalle — función propia para poder refrescarlo tras cambiar el
    // agente asignado sin tener que repintar toda la cabecera.
    function detailMetaText(t) {
        return 'Creado ' + (t.created_at_human || '—') + (t.assignee ? ' · ' + t.assignee.name : ' · sin asignar');
    }

    function renderDetail(t) {
        $('#tkt-detail-empty').hide();
        // .css('display','flex') en vez de .show(): #tkt-detail necesita
        // ser flex-column (cabecera fija + panes con scroll interno propio,
        // ver layout de altura completa en tickets-app.css) — jQuery.show()
        // restaura el display "por defecto" del tag (block), no el flex
        // que pide la hoja de estilos.
        var $d = $('#tkt-detail').css('display', 'flex');

        var statusLabel = t.status_name || STATUS_LABEL_FALLBACK[t.status_slug] || t.status_slug;
        var slaChip = t.sla_kind === 'breach' ? chip('SLA vencido', 'tkt-chip-danger')
            : (t.sla_kind === 'warn' ? chip('SLA en riesgo', 'tkt-chip-warn') : chip('SLA ' + (t.sla_text || 'ok'), 'tkt-chip-ok'));

        $d.html(
            '<div class="tkt-detail-head">' +
                '<div class="tkt-detail-head-row">' +
                    '<div class="tkt-detail-title-col">' +
                        '<div class="tkt-cap">Tickets · Detalle</div>' +
                        '<div class="tkt-detail-title">' + escapeHtml(t.subject || '(sin asunto)') + '</div>' +
                        '<div class="tkt-detail-chips">' +
                            '<span class="tkt-chip-id">' + escapeHtml(t.ticket_number) + '</span>' +
                            chip(statusLabel, statusChipClass(t.status_slug)) +
                            (t.priority ? chip(priorityLabel(t.priority), priorityChipClass(t.priority)) : '') +
                            '<span class="tkt-chip-mono">' + escapeHtml(ORIGIN_LABELS[t.source] || t.source || '—') + '</span>' +
                            slaChip +
                            '<span id="tkt-detail-meta" style="font-size:11px;color:var(--tkt-text-faint)">' + escapeHtml(detailMetaText(t)) + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="tkt-detail-actions">' +
                        '<button type="button" class="tkt-btn-icon" id="tkt-goto-reply" title="Responder al cliente"><i class="fa-solid fa-reply"></i></button>' +
                        '<button type="button" class="tkt-btn-icon" id="tkt-goto-state" title="Cambiar estado"><i class="fa-solid fa-arrow-right-arrow-left"></i></button>' +
                        '<button type="button" class="tkt-btn-icon" id="tkt-goto-assign" title="Asignar"><i class="fa-solid fa-user-plus"></i></button>' +
                        '<button type="button" class="tkt-btn-icon" id="tkt-goto-actions" title="Más acciones"><i class="fa-solid fa-ellipsis"></i></button>' +
                    '</div>' +
                '</div>' +
                '<div class="tkt-icon-rail">' +
                    '<button type="button" class="tkt-icon-tab" data-dtab="mail" title="Correo"><i class="fa-regular fa-envelope"></i><span class="badge" data-badge="mail"></span></button>' +
                    '<button type="button" class="tkt-icon-tab on" data-dtab="thread" title="Hilo"><i class="fa-solid fa-comments"></i><span class="badge" data-badge="thread"></span></button>' +
                    '<button type="button" class="tkt-icon-tab" data-dtab="trace" title="Trazabilidad"><i class="fa-solid fa-route"></i><span class="badge" data-badge="trace"></span></button>' +
                    '<button type="button" class="tkt-icon-tab" data-dtab="activity" title="Actividad"><i class="fa-solid fa-wave-square"></i><span class="badge" data-badge="activity"></span></button>' +
                    '<button type="button" class="tkt-icon-tab" data-dtab="files" title="Archivos"><i class="fa-solid fa-paperclip"></i><span class="badge" data-badge="files"></span></button>' +
                '</div>' +
            '</div>' +
            '<div class="tkt-banner-warn" id="tkt-collision-banner" style="display:none;margin:12px 20px 0" role="status"><i class="fa-solid fa-users"></i><span id="tkt-collision-text" style="flex:1"></span></div>' +
            '<div class="tkt-banner-warn" id="tkt-typing-indicator" style="display:none;margin:12px 20px 0" role="status"><i class="fa-solid fa-pen"></i><span id="tkt-typing-text" style="flex:1"></span></div>' +
            '<div class="tkt-banner-ai" id="tkt-ai-banner" style="display:none;margin:12px 20px 0"><i class="fa-solid fa-wand-magic-sparkles"></i><span id="tkt-ai-banner-text" style="flex:1"></span></div>' +
            '<div class="tkt-pane" id="tkt-dpane-mail" hidden></div>' +
            '<div class="tkt-pane" id="tkt-dpane-thread"><div class="tkt-skeleton"></div></div>' +
            '<div class="tkt-pane" id="tkt-dpane-trace" hidden></div>' +
            '<div class="tkt-pane" id="tkt-dpane-activity" hidden></div>' +
            '<div class="tkt-pane" id="tkt-dpane-files" hidden></div>'
        );

        joinTicketPresence(t.id);

        $('#tkt-goto-state, #tkt-goto-assign, #tkt-goto-actions').on('click', function () {
            var focusId = this.id === 'tkt-goto-state' ? 'tkt-sg-state' : (this.id === 'tkt-goto-assign' ? 'tkt-sg-assignee-wrap' : 'tkt-sg-actions');
            // Los tres viven dentro del panel "Gestión" del lateral: hay que abrirlo primero.
            if (TKA.state.sideTab !== 'gestion') selectSideTab('gestion');
            var $target = $('#' + focusId);
            if ($target.length) {
                $target[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
                if ($target.is('select')) $target.trigger('focus');
                else $target.addClass('tkt-highlight-flash').one('animationend', function () { $(this).removeClass('tkt-highlight-flash'); });
            }
        });

        $('#tkt-goto-reply').on('click', function () {
            selectDetailTab('thread');
            var $body = $('#tkt-reply-body');
            if ($body.length) $body.trigger('focus');
        });

        $d.find('[data-dtab]').on('click', function () {
            if ($(this).is('[disabled]')) return;
            selectDetailTab($(this).data('dtab'));
        });

        fetchDetailData(t);
        fetchAiSummary(t);
    }

    // Banner "Resumen IA ·" — TicketMailAiSummaryService::summarize(Ticket),
    // cacheado 10 min por el propio servicio. Igual que el resto de bloques
    // de IA en este proyecto: si no hay API key configurada o no hay
    // contexto suficiente, el servicio devuelve null y el banner
    // simplemente no aparece (nunca un resumen inventado).
    function fetchAiSummary(t) {
        $('#tkt-ai-banner').hide();
        if (!t.url_summary) return;
        $.getJSON(t.url_summary).done(function (res) {
            if (TKA.state.currentTicket !== t) return; // el agente ya cambió de ticket
            if (res && res.summary) {
                $('#tkt-ai-banner-text').html('<strong>Resumen IA ·</strong> ' + escapeHtml(res.summary));
                $('#tkt-ai-banner').show();
            }
        });
    }

    function selectDetailTab(which) {
        $('#tkt-detail [data-dtab]').each(function () {
            $(this).toggleClass('on', $(this).data('dtab') === which);
        });
        ['mail', 'thread', 'trace', 'activity', 'files'].forEach(function (k) {
            $('#tkt-dpane-' + k).prop('hidden', k !== which);
        });
    }

    // ═══════════ Detalle: Hilo / Trazabilidad / Actividad / Archivos / Correo ═══════════
    function fetchDetailData(t) {
        $.getJSON(t.url_data)
            .done(function (d) {
                TKA.state.currentDetail = d;
                renderThreadPane(d.thread || []);
                renderActivityPane(d.activity || []);
                renderFilesPane(d.files || [], t);
                renderMailPane(d.mail);
                renderTracePane(d.trace || []);
                renderActiveSidePane();
                updateDetailTabBadges(d);
            })
            .fail(function () {
                $('#tkt-dpane-thread').html('<div class="tkt-empty-box">No se pudo cargar el hilo de este ticket.</div>');
            });
    }

    // Badges numéricos del riel de iconos del detalle (Correo/Hilo/
    // Trazabilidad/Actividad/Archivos) — cuentan lo que realmente llegó en
    // data(), sin pedir nada nuevo al backend.
    function updateDetailTabBadges(d) {
        var counts = {
            mail: d.mail ? 1 : 0,
            thread: (d.thread || []).length,
            trace: (d.trace || []).length,
            activity: (d.activity || []).length,
            files: (d.files || []).length,
        };
        Object.keys(counts).forEach(function (key) {
            $('#tkt-detail [data-badge="' + key + '"]').text(counts[key] || '');
        });
    }

    var EVENT_ICONS = {
        message: 'fa-comment', internal_note: 'fa-lock', status_change: 'fa-arrow-right-arrow-left',
        assigned: 'fa-user-check', unassigned: 'fa-user-xmark', closed: 'fa-lock', reopened: 'fa-rotate-left',
    };

    // Clave de día (año-mes-día local) para agrupar el hilo — separada del
    // texto mostrado porque "Hoy" no sirve para comparar.
    function threadDayKey(isoDate) {
        var d = new Date(isoDate);
        return d.getFullYear() + '-' + d.getMonth() + '-' + d.getDate();
    }

    function threadDayLabel(isoDate) {
        var d = new Date(isoDate);
        var today = new Date();
        if (threadDayKey(isoDate) === threadDayKey(today)) return 'Hoy';
        return new Intl.DateTimeFormat('es-ES', { day: '2-digit', month: 'short', year: 'numeric' }).format(d);
    }

    function renderThreadPane(items) {
        var $p = $('#tkt-dpane-thread');
        var html = '';
        var lastDayKey = null;

        // Filtro del hilo (Todo/Solo cliente/Sin notas) — puramente
        // client-side sobre los datos ya cargados, sin refetch. Cada fila
        // lleva su tipo en data-thread-kind para poder ocultarla sin
        // volver a renderizar.
        html += '<div class="tkt-seg" id="tkt-thread-filter" style="margin-bottom:8px">' +
            '<button type="button" class="on" data-thread-filter="all">Todo</button>' +
            '<button type="button" data-thread-filter="customer">Solo cliente</button>' +
            '<button type="button" data-thread-filter="no-notes">Sin notas</button>' +
        '</div>';

        if (!items.length) {
            html += '<div class="tkt-empty-box">Este ticket todavía no tiene mensajes ni eventos.</div>';
        } else {
            items.forEach(function (it) {
                if (it.created_at && threadDayKey(it.created_at) !== lastDayKey) {
                    lastDayKey = threadDayKey(it.created_at);
                    html += '<div class="tkt-thread-day" data-thread-kind="day"><span>' + escapeHtml(threadDayLabel(it.created_at)) + '</span></div>';
                }
                if (it.type === 'message') {
                    var mine = it.from_agent;
                    html += '<div class="tkt-msg' + (mine ? ' mine' : '') + '" data-thread-kind="' + (mine ? 'agent' : 'customer') + '">' +
                        '<div class="tkt-msg-avatar">' + initials(it.sender_name) + '</div>' +
                        '<div class="tkt-msg-col">' +
                            '<div class="tkt-msg-head">' + escapeHtml(it.sender_name) + ' · ' + escapeHtml(it.created_at_human) + (it.attachment_count ? ' · ' + it.attachment_count + ' adjunto(s)' : '') + '</div>' +
                            '<div class="tkt-msg-bubble">' + escapeHtml(it.body || '') + '</div>' +
                        '</div>' +
                    '</div>';
                } else if (it.is_internal) {
                    html += '<div class="tkt-note-internal" data-thread-kind="note"><i class="fa-solid fa-lock" style="margin-top:2px"></i><div><strong>Nota interna</strong> · ' + escapeHtml(it.sender_name) + ' · ' + escapeHtml(it.created_at_human) + '<br>' + escapeHtml(it.body || '') + '</div></div>';
                } else {
                    html += '<div class="tkt-timeline-item" data-thread-kind="event"><div class="tkt-timeline-dot done"></div><div style="flex:1"><div style="font-size:11.5px">' + escapeHtml(it.body || it.type) + '</div><div style="font-size:10.5px;color:var(--tkt-text-faint)">' + escapeHtml(it.created_at_human) + '</div></div></div>';
                }
            });
        }

        // Caja de respuesta persistente al fondo del hilo — antes "Responder
        // al cliente" quedaba deshabilitado en todas partes (icono del
        // detalle, Acciones del panel Gestión) sin ningún sitio real donde
        // escribir. Reusa los endpoints que ya existen
        // (TicketMessagingController::storeMessage, url_message_store) con
        // adjuntos (multipart, ya soportado por StoreTicketMessageRequest) y
        // plantillas (TicketCannedReply, mismo criterio que showFull()).
        var templateOptions = '<option value="">/ plantilla…</option>' +
            (TKA.state.cannedReplies || []).map(function (r) { return '<option value="' + r.id + '">' + escapeHtml(r.title) + '</option>'; }).join('');

        html += '<div style="display:flex;flex-direction:column;gap:6px;margin-top:4px">' +
            '<div class="tkt-reply-bar" style="align-items:flex-start">' +
                '<div style="flex:1;display:flex;flex-direction:column;gap:6px">' +
                    '<textarea id="tkt-reply-body" class="tkt-w-100" style="border:none;font-size:12px;min-height:20px;max-height:120px;resize:vertical;padding:0" placeholder="Escribe una respuesta o una nota interna…"></textarea>' +
                    '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">' +
                        '<select id="tkt-reply-template" class="tkt-fselect" style="width:auto;font-size:10.5px;padding:3px 6px">' + templateOptions + '</select>' +
                        '<select id="tkt-reply-macro" class="tkt-fselect" style="width:auto;font-size:10.5px;padding:3px 6px"><option value="">/ macros…</option></select>' +
                        '<label class="tkt-btn-icon" style="cursor:pointer" title="Adjuntar archivo"><i class="fa-solid fa-paperclip"></i><input type="file" id="tkt-reply-attach" multiple hidden></label>' +
                        '<span id="tkt-reply-attach-count" class="mono" style="font-size:10px;color:var(--tkt-text-faint)"></span>' +
                        '<label style="display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--tkt-text-mute);white-space:nowrap;margin-left:auto"><input type="checkbox" id="tkt-reply-internal"> Nota interna</label>' +
                    '</div>' +
                '</div>' +
                '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-reply-send" style="width:auto">Responder</button>' +
            '</div>' +
        '</div>';

        $p.html(html);

        $('#tkt-reply-internal').on('change', function () {
            $('#tkt-reply-send').text(this.checked ? 'Guardar nota' : 'Responder');
        });
        $('#tkt-reply-template').on('change', function () {
            var id = $(this).val();
            var reply = (TKA.state.cannedReplies || []).find(function (r) { return String(r.id) === id; });
            if (!reply) return;
            var $body = $('#tkt-reply-body');
            $body.val(($body.val() ? $body.val() + '\n' : '') + reply.content);
            $(this).val('');
        });
        $('#tkt-reply-attach').on('change', function () {
            var n = this.files.length;
            $('#tkt-reply-attach-count').text(n ? n + ' archivo(s)' : '');
        });
        $('#tkt-thread-filter [data-thread-filter]').on('click', function () {
            var mode = $(this).data('thread-filter');
            $('#tkt-thread-filter button').removeClass('on');
            $(this).addClass('on');
            $p.find('[data-thread-kind]').each(function () {
                var kind = $(this).data('thread-kind');
                var visible = mode === 'all'
                    || (mode === 'customer' && kind === 'customer')
                    || (mode === 'no-notes' && kind !== 'note');
                $(this).toggle(Boolean(visible));
            });
        });

        $('#tkt-reply-send').on('click', sendReply);
        var typingTimer;
        $('#tkt-reply-body').on('input', function () {
            if ($('#tkt-reply-internal').is(':checked')) return; // notas internas no son "respuesta"
            clearTimeout(typingTimer);
            emitTyping(true);
            typingTimer = setTimeout(function () { emitTyping(false); }, 2500);
        });
        loadMacrosInto($('#tkt-reply-macro'));
        $('#tkt-reply-macro').on('change', function () {
            var macroId = $(this).val();
            if (!macroId) return;
            applyMacro(TKA.state.currentTicket, macroId);
            $(this).val('');
        });
    }

    // Macros reales (MacroApplyController) — mismo backend/patrón que ya
    // funciona en la ficha antigua show.blade.php: la lista se pide una vez
    // y se cachea; aplicar una ejecuta MacroExecutor en el servidor
    // (puede añadir una respuesta, cambiar estado/prioridad, etc. — lo que
    // la macro defina) y se refresca el detalle para reflejar el resultado
    // real en vez de adivinar qué cambió.
    function loadMacrosInto($select) {
        if (!TKA.urls.macrosList) return;
        if (TKA.state.macros) {
            appendMacroOptions($select, TKA.state.macros);
            return;
        }
        $.getJSON(TKA.urls.macrosList).done(function (res) {
            TKA.state.macros = (res && res.macros) || [];
            appendMacroOptions($select, TKA.state.macros);
        });
    }

    function appendMacroOptions($select, macros) {
        macros.forEach(function (m) {
            $select.append($('<option>', { value: m.id, text: m.name }));
        });
    }

    function applyMacro(t, macroId) {
        if (!t || !t.url_macro_apply_template) return;
        $.ajax({
            url: t.url_macro_apply_template.replace('__MACRO__', macroId),
            method: 'POST',
            headers: { Accept: 'application/json' },
            success: function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Macro aplicada');
                fetchDetailData(t);
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo aplicar la macro';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            },
        });
    }

    function sendReply() {
        var t = TKA.state.currentTicket;
        var body = $('#tkt-reply-body').val().trim();
        if (!t || !body) return;
        var isInternal = $('#tkt-reply-internal').is(':checked');
        var files = document.getElementById('tkt-reply-attach').files;

        var formData = new FormData();
        formData.append('body', body);
        formData.append('is_internal', isInternal ? 1 : 0);
        for (var i = 0; i < files.length; i++) formData.append('attachments[]', files[i]);

        $.ajax({
            url: t.url_message_store,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { Accept: 'application/json' },
            success: function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Mensaje enviado');
                emitTyping(false);
                fetchDetailData(t);
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'No se pudo enviar el mensaje';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            },
        });
    }

    function renderActivityPane(activities) {
        var $p = $('#tkt-dpane-activity');
        if (!activities.length) {
            $p.html('<div class="tkt-empty-box">Sin actividad registrada todavía.</div>');
            return;
        }
        var html = '<div class="tkt-timeline">';
        activities.forEach(function (a) {
            html += '<div class="tkt-timeline-item"><div class="tkt-timeline-dot done"></div><div style="flex:1"><div style="font-size:12.5px">' + escapeHtml(a.description) + (a.causer ? ' · <strong>' + escapeHtml(a.causer) + '</strong>' : '') + '</div><div style="font-size:10.5px;color:var(--tkt-text-faint)">' + escapeHtml(a.created_at_human) + '</div></div></div>';
        });
        $p.html(html + '</div>');
    }

    function renderFilesPane(files, t) {
        var $p = $('#tkt-dpane-files');
        var html = files.length ? files.map(fileRowHtml).join('') : '<div class="tkt-empty-box">Este ticket no tiene archivos adjuntos.</div>';
        // Adjuntar aquí publica un TicketItem sin texto vía el mismo
        // endpoint que la caja de respuesta del Hilo (StoreTicketMessageRequest
        // ya acepta attachments[] sin exigir body) — antes solo se podía
        // adjuntar redactando una respuesta.
        html += '<label class="tkt-btn" style="cursor:pointer;margin-top:9px;display:inline-flex">' +
            '<i class="fa-solid fa-paperclip"></i> Adjuntar archivo' +
            '<input type="file" id="tkt-files-attach-input" multiple hidden>' +
        '</label>';
        $p.html(html);

        $p.find('#tkt-files-attach-input').on('change', function () {
            if (!this.files.length || !t) return;
            // StoreTicketMessageRequest exige body (min:1) — no acepta
            // adjuntar sin ningún texto, así que se manda un texto mínimo
            // en vez de forzar un string vacío que el backend rechazaría.
            var formData = new FormData();
            formData.append('body', '(archivo adjunto)');
            formData.append('is_internal', 0);
            for (var i = 0; i < this.files.length; i++) formData.append('attachments[]', this.files[i]);

            $.ajax({
                url: t.url_message_store, method: 'POST', data: formData, processData: false, contentType: false,
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Archivo adjuntado');
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'No se pudo adjuntar el archivo';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    // Tamaño real (Storage::size(), null si el fichero no está en disco —
    // nunca se inventa un tamaño) + enlace de descarga real
    // (TicketAttachmentDownloadController, antes sin ningún <a> que lo usara).
    function formatFileSize(bytes) {
        if (bytes === null || bytes === undefined) return '';
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function fileRowHtml(f) {
        var meta = [f.created_at_human, formatFileSize(f.size)].filter(Boolean).join(' · ');
        var inner = '<i class="' + fileIconClass(f.name) + '"></i> ' + escapeHtml(f.name) +
            ' <span class="mono" style="color:var(--tkt-text-faint);margin-left:auto">' + escapeHtml(meta) + '</span>';
        return f.url_download
            ? '<a href="' + f.url_download + '" class="tkt-attach" style="text-decoration:none;color:inherit">' + inner + '</a>'
            : '<div class="tkt-attach">' + inner + '</div>';
    }

    function renderMailPane(mail) {
        var $p = $('#tkt-dpane-mail');
        if (!mail) {
            $p.html('<div class="tkt-empty-box">Este ticket no tiene correos asociados.</div>');
            return;
        }
        // Solo HTML/Texto — el mockup también trae "Fuente" (MIME crudo),
        // pero no archivamos las cabeceras reales del envío; mostrar un
        // "Content-Type" sintetizado sería inventar datos, así que se omite
        // en vez de fingir, mismo criterio ya aplicado en el resto de la
        // pantalla (Trazabilidad/Formulario vacíos honestos).
        var plainText = String(mail.body_html || '').replace(/<[^>]+>/g, '').replace(/\s+\n/g, '\n').trim();
        $p.html(
            '<div class="tkt-mail-meta">' +
                '<span>Para</span><span class="mono">' + escapeHtml(mail.to) + '</span>' +
                '<span>Asunto</span><span>' + escapeHtml(mail.subject) + '</span>' +
                '<span>Estado</span><span>' + escapeHtml(mail.status) + '</span>' +
                '<span>Enviado</span><span>' + escapeHtml(mail.created_at_human) + '</span>' +
            '</div>' +
            '<div class="tkt-mail-body-card">' +
                '<div class="tkt-mail-body-head">' +
                    '<span class="tkt-cap">Cuerpo del mensaje</span>' +
                    '<div class="view-toggle">' +
                        '<span class="on" data-mailview="html">HTML</span>' +
                        '<span data-mailview="text">Texto</span>' +
                    '</div>' +
                '</div>' +
                '<div class="tkt-mail-body" id="tkt-mail-body-html">' + (mail.body_html || '<em>Sin contenido</em>') + '</div>' +
                '<pre class="tkt-mail-body" id="tkt-mail-body-text" style="display:none;white-space:pre-wrap;font-family:var(--tkt-mono);font-size:11px">' + escapeHtml(plainText || 'Sin contenido') + '</pre>' +
            '</div>'
        );

        $p.find('[data-mailview]').on('click', function () {
            var which = $(this).data('mailview');
            $p.find('[data-mailview]').toggleClass('on', false);
            $(this).addClass('on');
            $('#tkt-mail-body-html').toggle(which === 'html');
            $('#tkt-mail-body-text').toggle(which === 'text');
        });
    }

    var TRACE_LABELS = { queued: 'Encolado', sent: 'Aceptado por el servidor de correo', bounced: 'Rebotado', failed: 'Fallido', opened: 'Abierto por el destinatario' };

    function renderTracePane(events) {
        var $p = $('#tkt-dpane-trace');
        if (!events.length) {
            $p.html('<div class="tkt-empty-box">Sin trazabilidad de entrega — este ticket no tiene un correo con seguimiento asociado.</div>');
            return;
        }
        var html = '<div class="tkt-timeline">';
        events.forEach(function (ev) {
            html += '<div class="tkt-timeline-item"><div class="tkt-timeline-dot done"></div><div style="flex:1"><div style="font-size:12px;font-weight:600">' + escapeHtml(TRACE_LABELS[ev.type] || ev.type) + '</div><div class="mono" style="font-size:10px;color:var(--tkt-text-faint)">' + escapeHtml(ev.at || '') + '</div></div></div>';
        });
        $p.html(html + '</div>');
    }

    // ═══════════ Render: panel lateral (8 pestañas, Fase C) ═══════════
    function optionsHtml(list, key, current) {
        var html = '';
        (list || []).forEach(function (item) {
            html += '<option value="' + item.id + '"' + (String(item.id) === String(current) ? ' selected' : '') + '>' + escapeHtml(item.name) + '</option>';
        });
        return html;
    }

    // Todo <select> de la pantalla debe ser select2 (bug de diseño real
    // encontrado en QA: el selector "Agente" era un <select> nativo con
    // decenas de opciones — imposible de usar sin buscador). Un único punto
    // de inicialización, llamado tras cada render() que introduce selects
    // nuevos (estáticos del filtro/modales, o generados por JS en el panel
    // Gestión/modales de bulk). NUNCA theme:'bootstrap-5' (gotcha ya
    // conocido: su CSS no está cargado y rompe el estilo) — select2 clásico,
    // ya cargado globalmente en el layout.
    function initSelect2($scope) {
        var $sel = $scope ? $scope.find('select').addBack('select') : $('select');
        $sel.each(function () {
            var $s = $(this);
            if ($s.data('select2')) return; // ya inicializado, evita doble-init
            // width:'100%' (el que usa create.blade.php) rompía la barra de
            // filtros: dentro de un flex row cada select2 pasaba a ocupar
            // toda la línea y los apilaba verticalmente. 'style' respeta el
            // ancho real del <select> original (auto en la barra de
            // filtros/panel Gestión, 100% dentro de los .tkt-field de los
            // modales porque ahí el <select> ya es block-level de por sí).
            //
            // dropdownParent: por defecto select2 cuelga su panel de <body>,
            // con su propio z-index — por debajo del z-index:9999 de
            // .tkt-modal-backdrop, así que dentro de un modal el desplegable
            // "abría" (el <select> quedaba en estado open) pero se
            // renderizaba TAPADO detrás del propio modal, invisible. Anclarlo
            // al backdrop más cercano lo mete en su mismo stacking context.
            var $backdrop = $s.closest('.tkt-modal-backdrop');
            $s.select2({
                width: 'style',
                minimumResultsForSearch: 6,
                dropdownAutoWidth: true,
                dropdownParent: $backdrop.length ? $backdrop : $(document.body),
            });
        });
    }

    function renderSidePanel(t) {
        $('#tkt-side').show();
        TKA.state.currentTicket = t;
        TKA.state.currentDetail = null;
        if (!TKA.state.sideTab) TKA.state.sideTab = 'gestion';
        renderActiveSidePane();
    }

    function selectSideTab(which) {
        TKA.state.sideTab = which;
        $('#tkt-side-rail [data-side]').each(function () {
            $(this).toggleClass('on', $(this).data('side') === which);
        });
        renderActiveSidePane();
    }

    // Único punto que decide qué pestaña del panel lateral pintar — se
    // llama tanto al seleccionar ticket (Gestión, sin AJAX) como al llegar
    // la respuesta de data() (el resto, que sí depende de ella) y al
    // cambiar de pestaña (usa lo ya cacheado, sin refetch).
    function renderActiveSidePane() {
        var t = TKA.state.currentTicket;
        var d = TKA.state.currentDetail;
        if (!t) return;
        var which = TKA.state.sideTab || 'gestion';
        var $c = $('#tkt-side-content');

        if (which === 'gestion') { renderGestionPane($c, t, d); return; }
        if (!d) { $c.html('<div class="tkt-skeleton"></div>'); return; }
        if (which === 'cliente') return renderClientePane($c, d.customer, t);
        if (which === 'form') return renderFormPane($c, d.form, t);
        if (which === 'correo') return renderCorreoSidePane($c, d.mail, t, d.side_conversations, d.followups, d.mails);
        if (which === 'notas') return renderNotasPane($c, d.notes, t);
        if (which === 'tags') return renderTagsPane($c, t, d.ai_suggestion);
        if (which === 'files') return renderArchivosPane($c, d.files);
        if (which === 'hist') return renderHistorialPane($c, d.activity, d.related);
    }

    // CSAT — el ticket ya trae rating/rating_comment/rating_reason/rated_at
    // reales (FeedbackController::submit() los rellena cuando el cliente
    // puntúa desde el enlace firmado del email). Sin valoración: solo se
    // ofrece reenviar si sendCsatSurvey() lo permitiría (mismas 3
    // condiciones ya validadas en el backend); si no aplica ninguna de las
    // dos, la tarjeta simplemente no se muestra (nunca "0 valoraciones").
    function renderCsatCard(csat) {
        if (!csat || (!csat.rating && !csat.can_resend)) return '';

        var body;
        if (csat.rating) {
            var stars = '';
            for (var i = 1; i <= 5; i++) stars += '<i class="fa-solid fa-star" style="color:' + (i <= csat.rating ? 'var(--tkt-warn)' : 'var(--tkt-border-strong)') + ';font-size:13px"></i>';
            body = '<div class="tkt-kv"><span class="k">Puntuación</span><span class="v">' + stars + '</span></div>' +
                '<div class="tkt-kv"><span class="k">Recibida</span><span class="v">' + escapeHtml(csat.rated_at_human || '—') + '</span></div>' +
                (csat.reason ? '<div class="tkt-kv"><span class="k">Motivo</span><span class="v">' + escapeHtml(csat.reason) + '</span></div>' : '') +
                (csat.comment ? '<div class="tkt-note">' + escapeHtml(csat.comment) + '</div>' : '');
        } else {
            body = '<div class="tkt-empty-box">Sin valorar todavía.</div>' +
                '<button type="button" class="tkt-btn" id="tkt-act-csat-resend">Reenviar encuesta de satisfacción</button>';
        }

        return '<div class="tkt-side-card"><div class="tkt-side-card-head">Satisfacción (CSAT)</div><div class="tkt-side-card-body">' + body + '</div></div>';
    }

    function renderGestionPane($c, t, d) {
        var slaLabel = t.sla_kind === 'breach' ? 'Vencido' : (t.sla_kind === 'warn' ? 'En riesgo' : 'En plazo');
        var slaColor = t.sla_kind === 'breach' ? 'var(--tkt-danger-fg)' : (t.sla_kind === 'warn' ? 'var(--tkt-warn-fg)' : 'var(--tkt-text)');
        var csat = d && d.csat;

        $c.html(
            '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">Gestión del ticket<span class="tkt-spacer">' + chip(t.status_name || STATUS_LABEL_FALLBACK[t.status_slug] || t.status_slug, statusChipClass(t.status_slug)) + '</span></div>' +
                '<div class="tkt-side-card-body">' +
                    '<div class="tkt-kv-select"><span class="k">Estado</span><select id="tkt-sg-state" data-field="status_id">' + optionsHtml(TKA.state.statuses, 'id', t.status_id) + '</select></div>' +
                    '<div class="tkt-kv-select"><span class="k">Prioridad</span><select id="tkt-sg-priority" data-field="priority">' +
                        ['low', 'normal', 'high', 'urgent'].map(function (p) { return '<option value="' + p + '"' + (p === t.priority ? ' selected' : '') + '>' + escapeHtml(priorityLabel(p)) + '</option>'; }).join('') +
                    '</select></div>' +
                    '<div class="tkt-kv-select"><span class="k">Categoría</span><select id="tkt-sg-category" data-field="category_id"><option value="">—</option>' + optionsHtml(TKA.state.categories, 'id', t.category_id) + '</select></div>' +
                    '<div class="tkt-kv-select"><span class="k">Equipo</span><select id="tkt-sg-group" data-field="group_id"><option value="">—</option>' + optionsHtml(TKA.state.groups, 'id', t.group_id) + '</select></div>' +
                '</div>' +
            '</div>' +
            '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">Asignado a</div>' +
                '<div class="tkt-side-card-body">' +
                    '<div class="tkt-kv-select" id="tkt-sg-assignee-wrap"><span class="k">Agente</span><select id="tkt-sg-assignee"><option value="">Sin asignar</option>' + optionsHtml(TKA.state.agentsFull, 'id', t.assignee ? t.assignee.id : '') + '</select></div>' +
                '</div>' +
            '</div>' +
            '<div class="tkt-side-card" id="tkt-sg-actions">' +
                '<div class="tkt-side-card-head">Acciones</div>' +
                '<div class="tkt-side-card-body">' +
                    '<button type="button" class="tkt-btn" id="tkt-act-reply"><i class="fa-solid fa-reply"></i> Responder al cliente</button>' +
                    '<button type="button" class="tkt-btn" data-lifecycle="resolve"><i class="fa-solid fa-circle-check"></i> Marcar como resuelto</button>' +
                    '<button type="button" class="tkt-btn" data-lifecycle="close"><i class="fa-solid fa-lock"></i> Cerrar ticket</button>' +
                    '<button type="button" class="tkt-btn" data-lifecycle="reopen"><i class="fa-solid fa-rotate-left"></i> Reabrir ticket</button>' +
                    '<button type="button" class="tkt-btn" id="tkt-act-snooze"><i class="fa-regular fa-clock"></i> Aplazar seguimiento</button>' +
                    '<button type="button" class="tkt-btn" id="tkt-act-merge"><i class="fa-solid fa-code-merge"></i> Fusionar duplicado</button>' +
                    '<button type="button" class="tkt-btn" id="tkt-act-followers"><i class="fa-solid fa-users"></i> Seguidores</button>' +
                    '<button type="button" class="tkt-btn" disabled title="Sin funcionalidad de backend equivalente"><i class="fa-solid fa-scissors"></i> Dividir ticket</button>' +
                    '<button type="button" class="tkt-btn tkt-btn-danger" id="tkt-act-delete"><i class="fa-solid fa-trash"></i> Eliminar / Archivar</button>' +
                '</div>' +
            '</div>' +
            '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">SLA</div>' +
                '<div class="tkt-side-card-body">' +
                    '<div class="tkt-kv"><span class="k">Estado</span><span class="v" style="color:' + slaColor + '">' + slaLabel + '</span></div>' +
                    '<div class="tkt-kv"><span class="k">Resolución</span><span class="v mono">' + escapeHtml(t.sla_text || '—') + '</span></div>' +
                '</div>' +
            '</div>' +
            renderCsatCard(csat)
        );

        $c.find('#tkt-act-csat-resend').on('click', function () {
            var $btn = $(this).prop('disabled', true).text('Enviando…');
            $.ajax({
                url: t.url_send_csat,
                method: 'POST',
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Encuesta enviada');
                    $btn.text('Enviada').prop('disabled', true);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo enviar la encuesta';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text('Reenviar encuesta de satisfacción');
                },
            });
        });

        $c.find('select[data-field]').on('change', function () {
            patchTicket(t, $(this).data('field'), $(this).val());
        });

        // A diferencia de Estado/Prioridad/Categoría/Equipo (que sí
        // recargan la página vía patchTicket), reasignar el agente usa la
        // variante silenciosa: actualiza t.assignee, la fila de la lista,
        // los contadores de tabs y la cabecera del detalle sin perder la
        // posición/pestaña activa.
        $c.find('#tkt-sg-assignee').on('change', function () {
            var agentId = $(this).val();
            var agent = agentId ? (TKA.state.agentsFull || []).find(function (a) { return String(a.id) === agentId; }) : null;
            patchTicketSilent(t, 'assignee_id', agentId, function () {
                t.assignee = agent ? { id: agent.id, name: agent.name } : null;
                $('#tkt-detail-meta').text(detailMetaText(t));
            });
        });

        // select2 después de enlazar los .on('change') — select2 solo oculta
        // el <select> original y sigue disparando 'change' sobre él, así que
        // el orden de los .on() no importa, pero conviene inicializarlo ya
        // con las opciones finales en el DOM (incluida la seleccionada).
        initSelect2($c);

        $c.find('[data-lifecycle]').on('click', function () {
            var action = $(this).data('lifecycle');
            // Cerrar pide motivo (grid, mismo criterio que el modal "Cerrar
            // conversación" de Conversaciones); resolver/reabrir no lo piden
            // en ningún flujo existente, se mantienen directos.
            if (action === 'close') { openCloseTicketModal(t); return; }
            runLifecycleAction(t, action);
        });

        $c.find('#tkt-act-reply').on('click', function () {
            selectSideTab('gestion'); // ya estamos aquí, solo por si acaso
            selectDetailTab('thread');
            var $body = $('#tkt-reply-body');
            if ($body.length) $body.trigger('focus');
        });

        $c.find('#tkt-act-snooze').on('click', function () { snoozeTicket(t); });
        $c.find('#tkt-act-merge').on('click', function () { mergeTicketPrompt(t); });
        $c.find('#tkt-act-followers').on('click', function () { openFollowersModal(t); });
        $c.find('#tkt-act-delete').on('click', function () { openDeleteModal(t); });
    }

    // Fusionar es DESTRUCTIVO (mueve historial/mails/notas al ticket destino
    // y cierra/elimina este) — el checkbox de confirmación dentro del modal
    // sustituye al window.confirm() encadenado, manteniendo la misma
    // fricción explícita antes de ejecutar. Backend real (MergeTicketRequest,
    // campo merge_into_id).
    function mergeTicketPrompt(t) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-code-merge',
            iconClass: 'danger',
            kicker: t.ticket_number,
            title: 'Fusionar duplicado',
            width: 'sm',
            body: '' +
                '<div class="tkt-field"><label class="tkt-label">Nº o ID del ticket destino<span class="req">*</span><span class="hint">visible en la URL al abrirlo</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-merge-target" placeholder="Ej: 7"></div>' +
                '<div class="tkt-note danger">Esta acción moverá el historial, los correos y las notas de <strong>' + escapeHtml(t.ticket_number) + '</strong> al ticket destino y no se puede deshacer.</div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-merge-ack"> Entiendo que esta acción no se puede deshacer</label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-merge-confirm" disabled>Fusionar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('change', '#tkt-merge-ack', function () {
            $('#tkt-merge-confirm').prop('disabled', !this.checked);
        });

        $backdrop.on('click', '#tkt-merge-confirm', function () {
            var input = $('#tkt-merge-target').val().trim();
            if (!/^\d+$/.test(input)) { if (window.toastr) toastr.error('Escribe un ID numérico de ticket'); return; }

            $.ajax({
                url: t.url_merge,
                method: 'POST',
                data: { merge_into_id: input },
                headers: { Accept: 'application/json' },
                success: function () {
                    if (window.toastr) toastr.success('Ticket fusionado');
                    window.location = TKA.urls.index;
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'No se pudo fusionar el ticket';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    // Modal "Aplazar seguimiento" — el backend (SnoozeTicketRequest) solo
    // acepta snoozed_until (fecha futura), así que el formulario tiene un
    // único campo datetime-local; no se inventa un motivo/select que el
    // backend no valida.
    function snoozeTicket(t) {
        var d = new Date();
        d.setDate(d.getDate() + 1);
        d.setHours(9, 0, 0, 0);
        var pad = function (n) { return String(n).padStart(2, '0'); };
        var defaultValue = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':00';

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-clock',
            kicker: t.ticket_number,
            title: 'Aplazar seguimiento',
            width: 'sm',
            body: '' +
                '<div class="tkt-field"><label class="tkt-label">Posponer hasta<span class="req">*</span></label>' +
                    '<input type="datetime-local" class="tkt-input" id="tkt-snooze-until" value="' + defaultValue + '"></div>' +
                '<div class="tkt-note">El ticket saldrá de las colas activas y volverá a aparecer automáticamente en esta fecha.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-snooze-confirm">Posponer</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('click', '#tkt-snooze-confirm', function () {
            var input = $('#tkt-snooze-until').val();
            if (!input) { if (window.toastr) toastr.error('Indica fecha y hora'); return; }

            $.ajax({
                url: t.url_snooze,
                method: 'POST',
                data: { snoozed_until: input },
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Ticket pospuesto');
                    window.location.reload();
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'No se pudo posponer el ticket';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    function renderClientePane($c, customer, t) {
        if (!customer) {
            $c.html('<div class="tkt-empty-box">Este ticket no tiene un cliente asociado.</div>');
            return;
        }
        var badges = '';
        if (customer.tickets_count != null) badges += '<span class="tkt-tag">' + customer.tickets_count + ' tickets</span>';
        if (customer.avg_csat != null) badges += '<span class="tkt-tag">CSAT ' + customer.avg_csat + '</span>';
        (customer.integrations || []).forEach(function (i) {
            badges += '<span class="tkt-tag">' + escapeHtml(i.label || i.name || 'Integración') + '</span>';
        });

        var html = '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">Cliente</div>' +
                '<div class="tkt-side-card-body">' +
                    (customer.url_c360
                        ? '<a href="' + customer.url_c360 + '" class="tkt-person" style="text-decoration:none"><span class="tkt-person-avatar">' + initials(customer.name) + '</span><span style="flex:1;min-width:0"><span style="display:block;font-size:12.5px;font-weight:700">' + escapeHtml(customer.name) + '</span><span style="display:block;font-size:11px;color:var(--tkt-text-mute)">' + escapeHtml(customer.company || 'Sin empresa') + (customer.customer_since_year ? ' · cliente desde ' + customer.customer_since_year : '') + '</span></span></a>'
                        : '<div class="tkt-person"><span class="tkt-person-avatar">' + initials(customer.name) + '</span><span style="flex:1;font-size:12.5px;font-weight:700">' + escapeHtml(customer.name) + '</span></div>') +
                    '<div class="tkt-kv"><span class="k">Email</span><span class="v mono">' + escapeHtml(customer.email || '—') + '</span></div>' +
                    '<div class="tkt-kv"><span class="k">Teléfono</span><span class="v mono">' + escapeHtml(customer.phone || '—') + '</span></div>' +
                    '<div class="tkt-kv"><span class="k">Idioma</span><span class="v">' + escapeHtml(customer.language || '—') + '</span></div>' +
                    (badges ? '<div style="display:flex;flex-wrap:wrap;gap:5px">' + badges + '</div>' : '') +
                '</div>' +
            '</div>';

        // Identidades/fusión de duplicados — ContactsMergeController ya
        // existía (search/preview/execute) sin ningún punto de entrada
        // desde la pantalla de tickets, solo desde Contactos 360.
        if (TKA.urls.contactsMergeSearchTemplate) {
            html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Identidades</div><div class="tkt-side-card-body">' +
                '<button type="button" class="tkt-btn" id="tkt-contact-merge-open"><i class="fa-solid fa-code-merge"></i> Fusionar contacto duplicado</button>' +
            '</div></div>';
        }

        // Tarjeta barata: todos los datos ya viajan en toListRow(), solo
        // faltaba componerlos aquí (mockup: "Detalles del ticket").
        if (t) {
            html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Detalles del ticket</div><div class="tkt-side-card-body">' +
                '<div class="tkt-kv"><span class="k">Creado</span><span class="v">' + escapeHtml(t.created_at_human || '—') + '</span></div>' +
                '<div class="tkt-kv"><span class="k">Actualizado</span><span class="v">' + escapeHtml(formatDateShort(t.updated_at)) + '</span></div>' +
                '<div class="tkt-kv"><span class="k">Origen</span><span class="v">' + escapeHtml(ORIGIN_LABELS[t.source] || t.source || '—') + '</span></div>' +
                '<div class="tkt-kv"><span class="k">Categoría</span><span class="v">' + escapeHtml(t.category_name || '—') + '</span></div>' +
            '</div></div>';
        }

        // "Ver como el cliente" del mockup, versión segura: enlace firmado
        // de solo lectura (SharedTicketController), sin suplantar la
        // sesión del agente — ver el porqué en el docblock del controller.
        if (t && t.url_shared_ticket) {
            html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Portal de cliente</div><div class="tkt-side-card-body">' +
                '<div class="tkt-note">Enlace de solo lectura, válido 30 días. El cliente ve el hilo de mensajes, no las notas internas.</div>' +
                '<button type="button" class="tkt-btn" id="tkt-shared-link-copy" data-url="' + escapeHtml(t.url_shared_ticket) + '"><i class="fa-solid fa-link"></i> Copiar enlace para el cliente</button>' +
            '</div></div>';
        }

        $c.html(html);

        $c.find('#tkt-shared-link-copy').on('click', function () {
            var url = $(this).data('url');
            var $btn = $(this);
            navigator.clipboard.writeText(url).then(function () {
                if (window.toastr) toastr.success('Enlace copiado');
                else { $btn.text('¡Copiado!'); setTimeout(function () { $btn.html('<i class="fa-solid fa-link"></i> Copiar enlace para el cliente'); }, 1500); }
            }).catch(function () {
                window.prompt('Copia el enlace:', url);
            });
        });

        $c.find('#tkt-contact-merge-open').on('click', function () { openContactMergeModal(customer); });
    }

    // Fusionar contacto duplicado — ContactsMergeController::search()/
    // preview()/execute() ya existían (los usa Contactos 360), aquí solo se
    // conecta desde la pantalla de tickets. winner = el contacto de este
    // ticket; loser = el duplicado elegido, que se fusiona DENTRO del
    // winner (irreversible, por eso el checkbox de confirmación).
    function openContactMergeModal(customer) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-code-merge',
            kicker: customer.name,
            title: 'Fusionar contacto duplicado',
            width: 'md',
            body: '' +
                '<div class="tkt-field"><label class="tkt-label">Buscar el contacto duplicado</label>' +
                    '<input type="text" class="tkt-input" id="tkt-merge-search-input" placeholder="Nombre, email o teléfono…"></div>' +
                '<div id="tkt-merge-search-results"></div>' +
                '<div id="tkt-merge-preview"></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-merge-execute-btn" disabled>Fusionar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        var searchUrl = TKA.urls.contactsMergeSearchTemplate.replace('__CUSTOMER__', customer.id);
        var selectedLoserId = null;
        var searchTimer;

        $backdrop.find('#tkt-merge-search-input').on('input', function () {
            var q = $(this).val().trim();
            clearTimeout(searchTimer);
            if (q.length < 2) { $backdrop.find('#tkt-merge-search-results').empty(); return; }
            searchTimer = setTimeout(function () {
                $.getJSON(searchUrl, { q: q, exclude_id: customer.id }).done(function (res) {
                    var results = (res && res.data) || [];
                    $backdrop.find('#tkt-merge-search-results').html(
                        results.length
                            ? results.map(function (r) {
                                return '<div class="tkt-line" data-loser-id="' + r.id + '" style="cursor:pointer">' +
                                    '<span style="flex:1;min-width:0" class="tkt-trunc">' + escapeHtml(r.name || r.email || ('#' + r.id)) + '</span>' +
                                    '<span class="mono" style="font-size:10px;color:var(--tkt-text-faint)">' + escapeHtml(r.email || r.phone || '') + '</span>' +
                                '</div>';
                            }).join('')
                            : '<div class="tkt-empty-box">Sin coincidencias.</div>'
                    );
                });
            }, 300);
        });

        $backdrop.on('click', '[data-loser-id]', function () {
            selectedLoserId = $(this).data('loser-id');
            $backdrop.find('[data-loser-id]').removeClass('on');
            $(this).addClass('on');

            var previewUrl = TKA.urls.contactsMergePreviewTemplate.replace('__CUSTOMER__', customer.id);
            $.getJSON(previewUrl, { loser_id: selectedLoserId }).done(function (res) {
                if (!res || !res.success) { $backdrop.find('#tkt-merge-preview').html('<div class="tkt-empty-box">No se pudo cargar la vista previa.</div>'); return; }
                var w = res.data.winner, l = res.data.loser;
                $backdrop.find('#tkt-merge-preview').html(
                    '<div class="tkt-note warn">Se fusionará <strong>' + escapeHtml(l.name || l.email) + '</strong> (' + l.total_conversations + ' conversaciones) dentro de <strong>' + escapeHtml(w.name || w.email) + '</strong>. Esta acción no se puede deshacer.</div>' +
                    '<label class="tkt-check"><input type="checkbox" id="tkt-merge-ack"> Entiendo que esta acción no se puede deshacer</label>'
                );
                $backdrop.find('#tkt-merge-execute-btn').prop('disabled', true);
            });
        });

        $backdrop.on('change', '#tkt-merge-ack', function () {
            $backdrop.find('#tkt-merge-execute-btn').prop('disabled', !this.checked);
        });

        $backdrop.on('click', '#tkt-merge-execute-btn', function () {
            if (!selectedLoserId) return;
            var executeUrl = TKA.urls.contactsMergeExecuteTemplate.replace('__CUSTOMER__', customer.id);
            $.ajax({
                url: executeUrl,
                method: 'POST',
                data: { loser_id: selectedLoserId },
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Contactos fusionados');
                    closeModal();
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo fusionar';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    function formatDateShort(iso) {
        if (!iso) return '—';
        try {
            return new Date(iso).toLocaleString('es-ES', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        } catch (e) {
            return iso;
        }
    }

    function renderFormPane($c, form, t) {
        if (!form) {
            $c.html('<div class="tkt-empty-box">Este ticket no proviene de un formulario. Origen: <strong>' + escapeHtml(ORIGIN_LABELS[t.source] || t.source || '—') + '</strong>.</div>');
            return;
        }
        var rows = '';
        Object.keys(form.fields || {}).forEach(function (k) {
            rows += '<div class="tkt-kv"><span class="k">' + escapeHtml(k) + '</span><span class="v">' + escapeHtml(String(form.fields[k])) + '</span></div>';
        });
        // Badge con el origen real (widget/formulario/PrestaShop según lo
        // que ya reporta sourceSlug()) — antes la cabecera no distinguía de
        // dónde viene el formulario.
        $c.html('<div class="tkt-side-card"><div class="tkt-side-card-head">Datos del formulario<span class="tkt-spacer">' + chip(ORIGIN_LABELS[t.source] || t.source, 'tkt-chip-muted') + '</span></div><div class="tkt-side-card-body">' + (rows || '<div class="tkt-empty-box">Sin campos capturados.</div>') + '</div></div>');
    }

    function renderCorreoSidePane($c, mail, t, sideConversations, followups, mails) {
        var html = '<div class="tkt-side-card"><div class="tkt-side-card-head">Último correo del ticket</div><div class="tkt-side-card-body">';
        html += mail
            ? '<div class="tkt-kv"><span class="k">Estado</span><span class="v">' + escapeHtml(mail.status) + '</span></div>' +
              '<div class="tkt-kv"><span class="k">Enviado</span><span class="v">' + escapeHtml(mail.created_at_human) + '</span></div>' +
              '<div class="tkt-kv"><span class="k">Aperturas</span><span class="v">' + (mail.opens_count > 0 ? mail.opens_count + (mail.last_opened_human ? ' · última ' + escapeHtml(mail.last_opened_human) : '') : 'Sin abrir') + '</span></div>'
            : '<div class="tkt-empty-box">Sin correos asociados a este ticket.</div>';
        html += '</div></div>';
        html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Envíos</div><div class="tkt-side-card-body">' +
            '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-compose-mail"><i class="fa-regular fa-paper-plane"></i> Redactar email</button>' +
            '<button type="button" class="tkt-btn" id="tkt-mails-list-open"><i class="fa-regular fa-envelope-open"></i> Correos del ticket' + (mails ? ' <span class="mono" style="color:var(--tkt-text-faint)">(' + mails.length + ')</span>' : '') + '</button>' +
            (mail && mail.url_resend
                ? '<button type="button" class="tkt-btn" id="tkt-resend-mail"><i class="fa-solid fa-rotate-right"></i> Reenviar último correo</button>'
                : '<button type="button" class="tkt-btn" disabled title="Sin correos que reenviar"><i class="fa-solid fa-rotate-right"></i> Reenviar último correo</button>') +
        '</div></div>';

        // Conversación paralela — hilo privado con un compañero o un
        // contacto externo, invisible para el cliente. Reusa
        // TicketSideConversationsController (store/addMessage), que ya
        // existía sin ninguna UI de agente que lo consumiera.
        var sideList = (sideConversations || []).map(function (s) {
            return '<div class="tkt-line" data-side-id="' + s.id + '" style="cursor:pointer">' +
                '<span style="flex:1;min-width:0" class="tkt-trunc">' + escapeHtml(s.subject) + '</span>' +
                '<span class="tkt-chip tkt-chip-muted">' + escapeHtml(s.status) + '</span>' +
                '<span class="mono" style="font-size:10px;color:var(--tkt-text-faint)">' + s.message_count + '</span>' +
            '</div>';
        }).join('');
        html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Conversación paralela<span class="tkt-spacer mono" style="color:var(--tkt-text-faint)">' + (sideConversations ? sideConversations.length : 0) + '</span></div><div class="tkt-side-card-body">' +
            (sideList || '<div class="tkt-empty-box">Sin conversaciones paralelas todavía.</div>') +
            '<button type="button" class="tkt-btn" id="tkt-side-new"><i class="fa-solid fa-people-arrows"></i> Nueva conversación paralela</button>' +
        '</div></div>';

        // Seguimientos programados (TicketFollowup) — recordatorios propios
        // sobre este ticket, notificados por el comando programado
        // helpdesk:send-due-ticket-followups. Ya funcionaba en la ficha
        // antigua show.blade.php; aquí solo se porta la UI.
        var followupsList = (followups || []).map(function (f) {
            return '<div class="tkt-line" data-followup-id="' + f.id + '">' +
                '<span style="flex:1;min-width:0" class="tkt-trunc">' + escapeHtml(f.scheduled_at_human || '') + (f.note ? ' — ' + escapeHtml(f.note) : '') + '</span>' +
                '<button type="button" class="tkt-btn-icon" data-followup-cancel="' + f.id + '" title="Cancelar"><i class="fa-solid fa-xmark"></i></button>' +
            '</div>';
        }).join('');
        html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Seguimientos<span class="tkt-spacer mono" style="color:var(--tkt-text-faint)">' + (followups ? followups.length : 0) + '</span></div><div class="tkt-side-card-body">' +
            (followupsList || '<div class="tkt-empty-box">Sin seguimientos programados.</div>') +
            '<button type="button" class="tkt-btn" id="tkt-followup-new"><i class="fa-regular fa-bell"></i> Programar seguimiento</button>' +
        '</div></div>';

        $c.html(html);

        $c.find('#tkt-followup-new').on('click', function () { openFollowupModal(t); });
        $c.find('[data-followup-cancel]').on('click', function () { cancelFollowup(t, $(this).data('followup-cancel')); });

        $c.find('#tkt-resend-mail').on('click', function () { openResendMailModal(mail); });

        $c.find('#tkt-side-new').on('click', function () { newSideConversation(t); });
        $c.find('[data-side-id]').on('click', function () { addSideConversationMessage(t, $(this).data('side-id')); });
        $c.find('#tkt-compose-mail').on('click', function () { openComposeModal(t); });
        $c.find('#tkt-mails-list-open').on('click', function () { openMailsListModal(t, mails); });
    }

    // Seguidores reales (TicketWatcher) — antes solo existía auto-seguirse
    // sin ninguna lista visible; ahora también se puede añadir a un
    // compañero (TicketLifecycleController::watch ampliado para aceptar
    // user_id, con permiso helpdesk.tickets.update de por medio).
    function openFollowersModal(t) {
        var d = TKA.state.currentDetail;
        var watchers = (d && d.watchers) || [];

        function rowsHtml() {
            if (!watchers.length) return '<div class="tkt-empty-box">Nadie sigue este ticket todavía.</div>';
            return watchers.map(function (w) {
                return '<div class="tkt-line"><span style="flex:1">' + escapeHtml(w.name) + (w.is_me ? ' <span class="tkt-chip tkt-chip-muted">tú</span>' : '') + '</span>' +
                    '<button type="button" class="tkt-btn-icon" data-follower-remove="' + w.user_id + '" title="Quitar"><i class="fa-solid fa-xmark"></i></button></div>';
            }).join('');
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-users',
            kicker: t.ticket_number,
            title: 'Seguidores',
            width: 'sm',
            body: '<div id="tkt-followers-list">' + rowsHtml() + '</div>' +
                '<div class="tkt-field"><label class="tkt-label">Añadir compañero</label>' +
                    '<select class="tkt-select" id="tkt-follower-add"><option value="">Selecciona un agente…</option>' + optionsHtml(TKA.state.agentsFull, 'id', '') + '</select></div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        function refresh() {
            $backdrop.find('#tkt-followers-list').html(rowsHtml());
        }

        $backdrop.on('click', '[data-follower-remove]', function () {
            var userId = $(this).data('follower-remove');
            $.ajax({
                url: t.url_unwatch, method: 'DELETE', data: { user_id: userId }, headers: { Accept: 'application/json' },
                success: function () {
                    watchers = watchers.filter(function (w) { return w.user_id !== userId; });
                    refresh();
                    if (d) d.watchers = watchers;
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo quitar el seguidor';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });

        $backdrop.on('change', '#tkt-follower-add', function () {
            var agentId = parseInt($(this).val(), 10);
            if (!agentId) return;
            var agent = (TKA.state.agentsFull || []).find(function (a) { return a.id === agentId; });
            $.ajax({
                url: t.url_watch, method: 'POST', data: { user_id: agentId }, headers: { Accept: 'application/json' },
                success: function () {
                    if (!watchers.some(function (w) { return w.user_id === agentId; })) {
                        watchers.push({ user_id: agentId, name: agent ? agent.name : ('Usuario #' + agentId), is_me: false });
                    }
                    refresh();
                    if (d) d.watchers = watchers;
                    $('#tkt-follower-add').val('');
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo añadir el seguidor';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    // Eliminar (soft-delete real) o archivar — dos acciones distintas del
    // backend (TicketsCrudController::destroy / TicketLifecycleController::
    // archive), presentadas como opciones excluyentes en un mismo modal en
    // vez de dos botones sueltos, con la fricción de un checkbox para la
    // irreversible.
    function openDeleteModal(t) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-trash',
            iconClass: 'danger',
            kicker: t.ticket_number,
            title: 'Eliminar ticket',
            width: 'sm',
            body: '' +
                '<label class="tkt-option on" data-delete-option="archive"><input type="radio" name="tkt-delete-mode" value="archive" checked style="margin:0">' +
                    '<span><span class="tkt-option-title">Archivar</span><br><span class="tkt-option-sub">Se oculta de las vistas activas, se puede restaurar</span></span></label>' +
                '<label class="tkt-option" data-delete-option="destroy"><input type="radio" name="tkt-delete-mode" value="destroy" style="margin:0">' +
                    '<span><span class="tkt-option-title">Eliminar</span><br><span class="tkt-option-sub">Borrado (recuperable solo desde la papelera técnica)</span></span></label>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-delete-ack"> Entiendo lo que va a pasar</label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-danger" id="tkt-delete-confirm" disabled>Confirmar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('click', '[data-delete-option]', function () {
            $backdrop.find('[data-delete-option]').removeClass('on');
            $(this).addClass('on').find('input').prop('checked', true);
        });
        $backdrop.on('change', '#tkt-delete-ack', function () {
            $('#tkt-delete-confirm').prop('disabled', !this.checked);
        });

        $backdrop.on('click', '#tkt-delete-confirm', function () {
            var mode = $backdrop.find('input[name="tkt-delete-mode"]:checked').val();
            var url = mode === 'archive' ? t.url_archive : t.url_destroy;
            $.ajax({
                url: url,
                method: mode === 'archive' ? 'POST' : 'DELETE',
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Hecho');
                    window.location = TKA.urls.index;
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo completar la acción';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    // Pill "Cola" — OpsHealthService::cached() ya alimentaba el dashboard de
    // reports; aquí se reusa tal cual (mismas 3 sondas: profundidad de
    // colas, dead-letter, breaches SLA última hora). "Entregabilidad"/
    // "Avisos" siguen fuera de alcance — no hay sonda real para esos.
    function openQueueModal() {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-layer-group',
            title: 'Estado de las colas',
            width: 'md',
            body: '<div class="tkt-empty-box">Cargando…</div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        $.getJSON(TKA.urls.ops).done(function (res) {
            var s = res && res.snapshot;
            if (!s) { $backdrop.find('.tkt-modal-body').html('<div class="tkt-empty-box">No se pudo cargar el estado de las colas.</div>'); return; }

            var queueRows = Object.keys(s.queues || {}).map(function (name) {
                return '<div class="tkt-line"><span style="flex:1">' + escapeHtml(name) + '</span><span class="mono">' + s.queues[name] + '</span></div>';
            }).join('') || '<div class="tkt-empty-box">Sin colas configuradas.</div>';

            var html = '' +
                '<div class="tkt-kpi3">' +
                    '<div><div class="v">' + (s.queue_total ?? 0) + '</div><div class="l">En cola</div></div>' +
                    '<div><div class="v">' + (s.failed_jobs ?? '—') + '</div><div class="l">Dead-letter</div></div>' +
                    '<div><div class="v">' + (s.sla_breaches_last_hour ?? '—') + '</div><div class="l">SLA vencido (1h)</div></div>' +
                '</div>' +
                '<div class="tkt-field"><label class="tkt-label">Por cola</label>' + queueRows + '</div>' +
                (s.unassigned_sla_warning > 0 ? '<div class="tkt-note warn">' + s.unassigned_sla_warning + ' ticket(s) sin asignar con SLA próximo a vencer.</div>' : '') +
                '<div style="font-size:10px;color:var(--tkt-text-faint)">Actualizado ' + escapeHtml(s.generated_at || '') + '</div>';

            $backdrop.find('.tkt-modal-body').html(html);
        }).fail(function () {
            $backdrop.find('.tkt-modal-body').html('<div class="tkt-empty-box">No se pudo cargar el estado de las colas.</div>');
        });
    }

    // Pill "Carga" — AssignmentService::getAgentWorkload() ya existía (la
    // usa la asignación automática al crear ticket) pero sin ningún punto
    // de entrada HTTP para verla desde aquí. Sin "capacidad"/"% ocupación":
    // no hay ninguna columna de capacidad máxima configurada por agente.
    function openWorkloadModal() {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-scale-balanced',
            title: 'Carga por agente',
            width: 'md',
            body: '<div class="tkt-empty-box">Cargando…</div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        function load() {
            $.getJSON(TKA.urls.workload).done(function (res) {
                if (!res || !res.agents) { $backdrop.find('.tkt-modal-body').html('<div class="tkt-empty-box">No se pudo cargar la carga por agente.</div>'); return; }

                var rows = res.agents.map(function (a) {
                    return '<div class="tkt-line"><span style="flex:1">' + escapeHtml(a.name) + '</span><span class="mono">' + a.open_tickets + ' abiertos</span></div>';
                }).join('') || '<div class="tkt-empty-box">No hay agentes disponibles.</div>';

                var html = '<div class="tkt-field"><label class="tkt-label">Tickets abiertos por agente</label>' + rows + '</div>';
                if (res.unassigned_count > 0) {
                    html += '<div class="tkt-note warn">' + res.unassigned_count + ' ticket(s) sin asignar.</div>' +
                        '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-workload-distribute">Repartir ' + res.unassigned_count + ' sin asignar</button>';
                } else {
                    html += '<div class="tkt-note ok">No hay tickets sin asignar ahora mismo.</div>';
                }
                $backdrop.find('.tkt-modal-body').html(html);
            }).fail(function () {
                $backdrop.find('.tkt-modal-body').html('<div class="tkt-empty-box">No se pudo cargar la carga por agente.</div>');
            });
        }

        $backdrop.on('click', '#tkt-workload-distribute', function () {
            var $btn = $(this).prop('disabled', true).text('Repartiendo…');
            $.ajax({
                url: TKA.urls.workloadDistribute,
                method: 'POST',
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Hecho');
                    load();
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo repartir';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text('Reintentar');
                },
            });
        });

        load();
    }

    // Exportar respeta el filtro/búsqueda actual (TicketExportController ya
    // reusa TicketFilter) — el modal solo elige el formato, ambos ya
    // soportados; no hay selección de columnas en el backend, no se inventa.
    function openExportModal() {
        openModal(modalShell({
            icon: 'fa-solid fa-download',
            title: 'Exportar tickets',
            width: 'sm',
            body: '<div class="tkt-note">Se exportarán los tickets que coinciden con el filtro/búsqueda actual (hasta 1000).</div>' +
                '<label class="tkt-option on" data-export-option="csv"><input type="radio" name="tkt-export-format" value="csv" checked style="margin:0"><span class="tkt-option-title">CSV</span></label>' +
                '<label class="tkt-option" data-export-option="pdf"><input type="radio" name="tkt-export-format" value="pdf" style="margin:0"><span class="tkt-option-title">PDF</span></label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-export-confirm">Exportar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        })).on('click', '[data-export-option]', function () {
            $('[data-export-option]').removeClass('on');
            $(this).addClass('on').find('input').prop('checked', true);
        }).on('click', '#tkt-export-confirm', function () {
            var format = $('input[name="tkt-export-format"]:checked').val();
            window.location = TKA.urls.exportTemplate.replace('__FORMAT__', format);
            closeModal();
        });
    }

    // Lista completa de correos del ticket (antes solo se veía el último;
    // ver el resto obligaba a salir a la bandeja global). Datos ya
    // incluidos en data() — sin endpoint nuevo.
    function openMailsListModal(t, mails) {
        var rows = (mails || []).map(function (m) {
            var dirIcon = m.direction === 'inbound' ? 'fa-arrow-down' : 'fa-arrow-up';
            return '<div class="tkt-line"><i class="fa-solid ' + dirIcon + '" style="color:var(--tkt-text-faint);font-size:10px"></i>' +
                '<span style="flex:1;min-width:0" class="tkt-trunc">' + escapeHtml(m.subject || '(sin asunto)') + '</span>' +
                chip(m.status, 'tkt-chip-muted') +
                '<span class="mono" style="font-size:10px;color:var(--tkt-text-faint);white-space:nowrap">' + escapeHtml(m.created_at_human) + '</span>' +
            '</div>';
        }).join('');

        openModal(modalShell({
            icon: 'fa-regular fa-envelope-open',
            kicker: t.ticket_number,
            title: 'Correos del ticket',
            width: 'lg',
            body: rows || '<div class="tkt-empty-box">Sin correos asociados a este ticket.</div>',
            foot: '<a href="' + TKA.urls.emailsIndex + '?search=' + encodeURIComponent(t.ticket_number) + '" class="tkt-btn">Abrir en la bandeja completa</a>' +
                  '<button type="button" class="tkt-btn tkt-btn-primary" data-modal-close>Cerrar</button>',
            footTwoCol: true,
        }));
    }

    // Modal de confirmación para reenviar el último correo del ticket —
    // mismos datos que antes mostraba el window.confirm() (destinatario,
    // asunto), solo que en formato modal.
    function openResendMailModal(mail) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-rotate-right',
            title: 'Reenviar correo',
            width: 'sm',
            body: '<div class="tkt-kv-grid"><span>Para</span><span>' + escapeHtml(mail.to) + '</span><span>Asunto</span><span>' + escapeHtml(mail.subject) + '</span></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-resend-confirm">Reenviar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('click', '#tkt-resend-confirm', function () {
            var $btn = $(this).prop('disabled', true).text('Reenviando…');
            $.ajax({
                url: mail.url_resend,
                method: 'POST',
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Correo reenviado');
                    closeModal();
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo reenviar el correo';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text('Reenviar');
                },
            });
        });
    }

    // Redactar/programar un email REAL del ticket (TicketMail vía
    // TicketMailsController::store — ComposeTicketMailRequest), a
    // diferencia de la caja de respuesta del Hilo que solo publica un
    // TicketItem interno. Reutiliza el mismo endpoint que la bandeja de
    // Emails enviados.
    function openComposeModal(t) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-paper-plane',
            kicker: t.ticket_number,
            title: 'Redactar email',
            width: 'lg',
            body: '' +
                '<div class="tkt-field"><label class="tkt-label">Para<span class="req">*</span></label>' +
                    '<input type="email" class="tkt-input" id="tkt-compose-to" value="' + escapeHtml((t.customer && t.customer.email) || '') + '"></div>' +
                '<div class="tkt-field-row">' +
                    '<div class="tkt-field"><label class="tkt-label">CC <span class="hint">opcional, separados por coma</span></label><input type="text" class="tkt-input" id="tkt-compose-cc"></div>' +
                    '<div class="tkt-field"><label class="tkt-label">CCO <span class="hint">opcional</span></label><input type="text" class="tkt-input" id="tkt-compose-bcc"></div>' +
                '</div>' +
                '<div class="tkt-field"><label class="tkt-label">Asunto<span class="req">*</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-compose-subject" value="' + escapeHtml('Re: ' + (t.subject || '')) + '"></div>' +
                '<div class="tkt-field"><label class="tkt-label">Mensaje<span class="req">*</span></label>' +
                    '<textarea class="tkt-input" id="tkt-compose-body" style="min-height:120px"></textarea></div>' +
                '<div class="tkt-field"><label class="tkt-label">Programar envío <span class="hint">opcional — se manda solo, en la fecha indicada</span></label>' +
                    '<input type="datetime-local" class="tkt-input" id="tkt-compose-scheduled"></div>' +
                '<div class="tkt-field"><label class="tkt-label">Adjuntos <span class="hint">opcional, máx 10&nbsp;MB c/u</span></label>' +
                    '<input type="file" class="tkt-input" id="tkt-compose-attach" multiple></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-compose-confirm">Enviar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('click', '#tkt-compose-confirm', function () {
            var to = $('#tkt-compose-to').val().trim();
            var subject = $('#tkt-compose-subject').val().trim();
            var body = $('#tkt-compose-body').val().trim();
            if (!to || !subject || !body) {
                if (window.toastr) toastr.error('Rellena destinatario, asunto y mensaje');
                return;
            }

            var $btn = $(this).prop('disabled', true).text('Enviando…');
            var formData = new FormData();
            formData.append('ticket_id', t.id);
            formData.append('to', to);
            formData.append('subject', subject);
            formData.append('body', body);
            ($('#tkt-compose-cc').val() || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean).forEach(function (e) { formData.append('cc[]', e); });
            ($('#tkt-compose-bcc').val() || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean).forEach(function (e) { formData.append('bcc[]', e); });
            var scheduled = $('#tkt-compose-scheduled').val();
            if (scheduled) formData.append('scheduled_at', scheduled);
            var files = document.getElementById('tkt-compose-attach').files;
            for (var i = 0; i < files.length; i++) formData.append('attachments[]', files[i]);

            $.ajax({
                url: TKA.urls.emailsStore,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || (scheduled ? 'Email programado' : 'Email enviado'));
                    closeModal();
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'No se pudo enviar el email';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text('Enviar');
                },
            });
        });
    }

    // Picker simplificado por prompt() (el mockup abre un modal de
    // participantes con buscador) — backend real, tres preguntas mínimas.
    function openFollowupModal(t) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-bell',
            kicker: t.ticket_number,
            title: 'Programar seguimiento',
            width: 'sm',
            body: '' +
                '<div class="tkt-field"><label class="tkt-label">Fecha y hora<span class="req">*</span></label>' +
                    '<input type="datetime-local" class="tkt-input" id="tkt-followup-when"></div>' +
                '<div class="tkt-field"><label class="tkt-label">Nota <span class="hint">opcional</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-followup-note" maxlength="1000" placeholder="Motivo del recordatorio…"></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-followup-confirm">Programar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
            footTwoCol: false,
        }));

        $backdrop.on('click', '#tkt-followup-confirm', function () {
            var when = $('#tkt-followup-when').val();
            if (!when) { if (window.toastr) toastr.error('Indica fecha y hora'); return; }
            $.ajax({
                url: t.url_followups_store,
                method: 'POST',
                data: { scheduled_at: when, note: $('#tkt-followup-note').val() || null },
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Seguimiento programado');
                    closeModal();
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo programar el seguimiento';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    function cancelFollowup(t, followupId) {
        openConfirmModal({
            icon: 'fa-regular fa-clock',
            title: 'Cancelar seguimiento',
            message: '¿Cancelar este seguimiento programado?',
            confirmLabel: 'Cancelar seguimiento',
            danger: true,
            onConfirm: function () {
                $.ajax({
                    url: t.url_followup_destroy_template.replace('__FOLLOWUP__', followupId),
                    method: 'DELETE',
                    headers: { Accept: 'application/json' },
                    success: function () {
                        if (window.toastr) toastr.success('Seguimiento cancelado');
                        fetchDetailData(t);
                    },
                    error: function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo cancelar el seguimiento';
                        if (window.toastr) toastr.error(msg); else window.alert(msg);
                    },
                });
            },
        });
    }

    // Modal "Nueva conversación paralela" — campos y nombres exactos de
    // StoreSideConversationRequest (subject, participant_type: team|
    // external_email, participant_user_id, participant_email, body).
    function newSideConversation(t) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-people-arrows',
            kicker: t.ticket_number,
            title: 'Nueva conversación paralela',
            width: 'md',
            body: '' +
                '<div class="tkt-field"><label class="tkt-label">Asunto<span class="req">*</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-side-subject" maxlength="255"></div>' +
                '<div class="tkt-field"><label class="tkt-label">Participante<span class="req">*</span></label>' +
                    '<select class="tkt-select" id="tkt-side-type"><option value="team">Compañero de equipo</option><option value="external_email">Email externo</option></select></div>' +
                '<div class="tkt-field" id="tkt-side-team-field"><label class="tkt-label">Compañero<span class="req">*</span></label>' +
                    '<select class="tkt-select" id="tkt-side-user"><option value="">Selecciona un compañero…</option>' + optionsHtml(TKA.state.agentsFull, 'id', '') + '</select></div>' +
                '<div class="tkt-field" id="tkt-side-email-field" hidden><label class="tkt-label">Email externo<span class="req">*</span></label>' +
                    '<input type="email" class="tkt-input" id="tkt-side-email"></div>' +
                '<div class="tkt-field"><label class="tkt-label">Mensaje inicial<span class="req">*</span></label>' +
                    '<textarea class="tkt-input" id="tkt-side-body" maxlength="20000" style="min-height:90px"></textarea></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-side-confirm">Crear</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('change', '#tkt-side-type', function () {
            var isTeam = $(this).val() === 'team';
            $('#tkt-side-team-field').prop('hidden', !isTeam);
            $('#tkt-side-email-field').prop('hidden', isTeam);
        });

        $backdrop.on('click', '#tkt-side-confirm', function () {
            var subject = $('#tkt-side-subject').val().trim();
            var type = $('#tkt-side-type').val();
            var userId = $('#tkt-side-user').val();
            var email = $('#tkt-side-email').val().trim();
            var body = $('#tkt-side-body').val().trim();

            if (!subject || !body || (type === 'team' && !userId) || (type === 'external_email' && !email)) {
                if (window.toastr) toastr.error('Rellena todos los campos obligatorios'); else window.alert('Rellena todos los campos obligatorios');
                return;
            }

            $.ajax({
                url: t.url_side_conversations_store,
                method: 'POST',
                data: {
                    subject: subject,
                    participant_type: type,
                    participant_user_id: type === 'team' ? userId : undefined,
                    participant_email: type === 'external_email' ? email : undefined,
                    body: body,
                },
                headers: { Accept: 'application/json' },
                success: function () {
                    if (window.toastr) toastr.success('Conversación paralela creada');
                    closeModal();
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'No se pudo crear la conversación paralela';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    function addSideConversationMessage(t, sideId) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-comment-dots',
            title: 'Responder en la conversación paralela',
            width: 'sm',
            body: '<div class="tkt-field"><label class="tkt-label">Mensaje<span class="req">*</span></label><textarea class="tkt-input" id="tkt-side-msg-body" rows="4" placeholder="Escribe el mensaje…"></textarea></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-side-msg-confirm">Enviar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));
        $backdrop.find('#tkt-side-msg-body').trigger('focus');

        $backdrop.on('click', '#tkt-side-msg-confirm', function () {
            var body = ($backdrop.find('#tkt-side-msg-body').val() || '').trim();
            if (!body) { if (window.toastr) toastr.error('Escribe un mensaje'); return; }
            closeModal();
            $.ajax({
                url: t.url_side_conversations_message_template.replace('__SIDE__', sideId),
                method: 'POST',
                data: { body: body },
                headers: { Accept: 'application/json' },
                success: function () {
                    if (window.toastr) toastr.success('Mensaje enviado');
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'No se pudo enviar el mensaje';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    var NOTE_COLORS = ['yellow', 'blue', 'green', 'red', 'purple', 'orange'];

    // Autocompletado @mención genérico sobre un <textarea> — el backend
    // (MentionService::notifyMentions) hace matching por texto sobre
    // "TRIM(firstname+' '+lastname) LIKE 'nombre%'", así que insertar el
    // nombre completo real (no un @handle inventado) es lo que garantiza
    // que la notificación real se dispare al guardar.
    function bindMentionAutocomplete($textarea, $drop) {
        $textarea.on('input', function () {
            var val = this.value;
            var pos = this.selectionStart;
            var atIndex = val.lastIndexOf('@', pos - 1);
            var textSinceAt = atIndex > -1 ? val.slice(atIndex + 1, pos) : null;
            if (atIndex === -1 || textSinceAt === null || /[\n@]/.test(textSinceAt)) {
                $drop.prop('hidden', true).empty();
                return;
            }

            var matches = (TKA.state.agentsFull || []).filter(function (a) {
                return a.name.toLowerCase().indexOf(textSinceAt.toLowerCase()) === 0;
            }).slice(0, 6);

            if (!matches.length) { $drop.prop('hidden', true).empty(); return; }

            // .prop('hidden', ...) en vez de .show()/.hide(): el atributo
            // HTML hidden lleva !important en el reset de Bootstrap — un
            // simple display:block inline (lo que hace .show()) no lo gana.
            $drop.html(matches.map(function (a) {
                return '<button type="button" class="tkt-drop-item" data-mention-name="' + escapeHtml(a.name) + '">' + escapeHtml(a.name) + '</button>';
            }).join('')).prop('hidden', false);

            $drop.off('click').on('click', '[data-mention-name]', function () {
                var name = $(this).data('mention-name');
                var before = val.slice(0, atIndex);
                var after = val.slice(pos);
                var newVal = before + '@' + name + ' ' + after;
                $textarea.val(newVal);
                $drop.prop('hidden', true).empty();
                $textarea.trigger('focus');
            });
        });

        $textarea.on('blur', function () {
            // pequeño delay para que el click en el dropdown se registre antes de ocultarlo
            setTimeout(function () { $drop.prop('hidden', true).empty(); }, 150);
        });
    }

    function renderNotasPane($c, notes, t) {
        var list = (notes || []).map(function (n) {
            var colorDots = NOTE_COLORS.map(function (c) {
                return '<span class="tkt-note-color-dot' + (n.color === c ? ' on' : '') + '" data-note-color="' + n.id + '" data-color="' + c + '" style="background:var(--tkt-note-' + c + ',' + c + ')"></span>';
            }).join('');
            return '<div class="tkt-note-card' + (n.is_pinned ? ' pinned' : '') + '"><span class="tkt-person-avatar" style="width:22px;height:22px;font-size:9px">' + initials(n.author_name) + '</span>' +
                '<div style="flex:1;min-width:0">' +
                    '<div style="display:flex;align-items:center;gap:6px">' +
                        '<span style="font-size:10.5px;color:var(--tkt-text-faint);flex:1;min-width:0">' + escapeHtml(n.author_name) + ' · ' + escapeHtml(n.created_at_human) + (n.is_pinned ? ' · <strong>fijada</strong>' : '') + '</span>' +
                        '<button type="button" class="tkt-btn-icon" data-note-pin="' + n.id + '" title="' + (n.is_pinned ? 'Desfijar' : 'Fijar') + '" style="width:20px;height:20px"><i class="fa-solid fa-thumbtack" style="font-size:9px"></i></button>' +
                        '<button type="button" class="tkt-btn-icon" data-note-delete="' + n.id + '" title="Eliminar" style="width:20px;height:20px"><i class="fa-solid fa-trash" style="font-size:9px"></i></button>' +
                    '</div>' +
                    (n.title ? '<div style="font-size:11.5px;font-weight:700">' + escapeHtml(n.title) + '</div>' : '') +
                    '<div style="font-size:11.5px;line-height:1.5">' + escapeHtml(n.body) + '</div>' +
                    '<div style="display:flex;gap:4px;margin-top:4px">' + colorDots + '</div>' +
                '</div></div>';
        }).join('');

        $c.html(
            '<div class="tkt-side-card"><div class="tkt-side-card-head">Nota interna</div><div class="tkt-side-card-body">' +
                '<div style="position:relative">' +
                    '<textarea id="tkt-new-note" class="tkt-w-100" style="padding:9px 10px;border:1px solid var(--tkt-border);border-radius:7px;font-size:11.5px;min-height:66px" placeholder="Escribe una nota que solo verá el equipo… (usa @ para mencionar)"></textarea>' +
                    '<div class="tkt-drop" id="tkt-note-mention-drop" style="top:100%;left:0;right:0;max-height:160px;overflow:auto" hidden></div>' +
                '</div>' +
                '<label style="display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--tkt-text-mute)"><input type="checkbox" id="tkt-new-note-pin"> Fijar esta nota</label>' +
                '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-note-save"><i class="fa-regular fa-note-sticky"></i> Guardar nota interna</button>' +
            '</div></div>' +
            '<div class="tkt-side-card"><div class="tkt-side-card-head">Notas del ticket<span class="tkt-spacer mono" style="color:var(--tkt-text-faint)">' + (notes ? notes.length : 0) + '</span></div><div class="tkt-side-card-body">' +
                (list || '<div class="tkt-empty-box">Sin notas todavía.</div>') +
            '</div></div>'
        );

        $c.find('#tkt-note-save').on('click', function () { createNote(t); });
        $c.find('[data-note-pin]').on('click', function () { toggleNotePin(t, $(this).data('note-pin')); });
        $c.find('[data-note-delete]').on('click', function () { deleteNote(t, $(this).data('note-delete')); });
        bindMentionAutocomplete($c.find('#tkt-new-note'), $c.find('#tkt-note-mention-drop'));
        $c.find('[data-note-color]').on('click', function () { setNoteColor(t, $(this).data('note-color'), $(this).data('color')); });
    }

    function toggleNotePin(t, noteId) {
        $.ajax({
            url: t.url_note_pin_template.replace('__NOTE__', noteId),
            method: 'POST',
            headers: { Accept: 'application/json' },
            success: function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Nota actualizada');
                fetchDetailData(t);
            },
            error: function () { if (window.toastr) toastr.error('No se pudo fijar la nota'); },
        });
    }

    function setNoteColor(t, noteId, color) {
        $.ajax({
            url: t.url_note_color_template.replace('__NOTE__', noteId),
            method: 'POST',
            data: { color: color },
            headers: { Accept: 'application/json' },
            success: function () { fetchDetailData(t); },
            error: function () { if (window.toastr) toastr.error('No se pudo cambiar el color'); },
        });
    }

    function deleteNote(t, noteId) {
        openConfirmModal({
            icon: 'fa-solid fa-trash',
            title: 'Eliminar nota interna',
            message: 'No se puede deshacer.',
            confirmLabel: 'Eliminar',
            danger: true,
            onConfirm: function () {
                $.ajax({
                    url: t.url_note_destroy_template.replace('__NOTE__', noteId),
                    method: 'DELETE',
                    headers: { Accept: 'application/json' },
                    success: function () {
                        if (window.toastr) toastr.success('Nota eliminada');
                        fetchDetailData(t);
                    },
                    error: function () { if (window.toastr) toastr.error('No se pudo eliminar la nota'); },
                });
            },
        });
    }

    function renderTagsPane($c, t, aiSuggestion) {
        var chips = (t.tags || []).map(function (tag) {
            return '<span class="tkt-tag" data-tag-remove="' + escapeHtml(tag) + '">' + escapeHtml(tag) + ' <i class="fa-solid fa-xmark"></i></span>';
        }).join('');

        var html = '<div class="tkt-side-card"><div class="tkt-side-card-head">Etiquetas</div><div class="tkt-side-card-body">' +
                '<div style="display:flex;flex-wrap:wrap;gap:5px">' + chips + '</div>' +
                '<input type="text" id="tkt-tag-add" class="tkt-w-100" style="padding:7px 9px;border:1px solid var(--tkt-border);border-radius:7px;font-size:11.5px" placeholder="Nueva etiqueta y Enter…">' +
            '</div></div>';

        // Sugerencias ya calculadas por TicketAiService (ai_suggested_category_id/
        // ai_suggested_priority) — sin porcentaje de confianza porque el
        // backend no lo guarda; el mockup lo muestra pero sería un dato
        // inventado. Solo aparece si hay una sugerencia real pendiente.
        if (aiSuggestion && (aiSuggestion.category || aiSuggestion.priority)) {
            html += '<div class="tkt-side-card"><div class="tkt-side-card-head"><i class="fa-solid fa-wand-magic-sparkles"></i> Sugerido por IA</div><div class="tkt-side-card-body">';
            if (aiSuggestion.category) {
                html += '<div class="tkt-row"><span style="flex:1;font-size:11.5px">Categoría: <strong>' + escapeHtml(aiSuggestion.category.name) + '</strong></span>' +
                    '<button type="button" class="tkt-btn tkt-btn-sm" data-ai-apply="category">Aplicar</button></div>';
            }
            if (aiSuggestion.priority) {
                html += '<div class="tkt-row"><span style="flex:1;font-size:11.5px">Prioridad: <strong>' + escapeHtml(priorityLabel(aiSuggestion.priority)) + '</strong></span>' +
                    '<button type="button" class="tkt-btn tkt-btn-sm" data-ai-apply="priority">Aplicar</button></div>';
            }
            html += '</div></div>';
        }

        html += '<a href="' + TKA.urls.automationsIndex + '" class="tkt-btn"><i class="fa-solid fa-gears"></i> Reglas de etiquetado automático</a>';

        $c.html(html);

        $c.find('[data-tag-remove]').on('click', function () { patchTags(t, null, $(this).data('tag-remove')); });
        $c.find('#tkt-tag-add').on('keydown', function (ev) {
            if (ev.key !== 'Enter' || !this.value.trim()) return;
            patchTags(t, this.value.trim(), null);
        });
        $c.find('[data-ai-apply]').on('click', function () { applyAiSuggestion(t, $(this).data('ai-apply')); });
    }

    function applyAiSuggestion(t, field) {
        $.ajax({
            url: t.url_ai_apply,
            method: 'POST',
            data: { field: field },
            headers: { Accept: 'application/json' },
            success: function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Sugerencia aplicada');
                fetchDetailData(t);
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo aplicar la sugerencia';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            },
        });
    }

    function renderArchivosPane($c, files) {
        var list = (files || []).map(fileRowHtml).join('');
        $c.html('<div class="tkt-side-card"><div class="tkt-side-card-head">Archivos del ticket<span class="tkt-spacer mono" style="color:var(--tkt-text-faint)">' + (files ? files.length : 0) + '</span></div><div class="tkt-side-card-body">' + (list || '<div class="tkt-empty-box">Sin archivos adjuntos.</div>') + '</div></div>');
    }

    function renderHistorialPane($c, activity, related) {
        var recent = (activity || []).slice(0, 5).map(function (a) {
            return '<div class="tkt-timeline-item"><div class="tkt-timeline-dot done"></div><div style="flex:1"><div style="font-size:11.5px">' + escapeHtml(a.description) + '</div><div style="font-size:10.5px;color:var(--tkt-text-faint)">' + escapeHtml(a.created_at_human) + '</div></div></div>';
        }).join('');
        var relatedHtml = (related || []).map(function (r) {
            return '<div class="tkt-line">' +
                '<a href="' + TKA.urls.index + '?ticket=' + r.id + '" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:9px;flex:1;min-width:0">' +
                    '<span class="mono" style="font-size:10px;color:var(--tkt-text-faint)">' + escapeHtml(r.ticket_number) + '</span>' +
                    '<span style="flex:1;min-width:0" class="tkt-trunc">' + escapeHtml(r.subject) + '</span>' +
                    (r.link_type ? chip(LINK_TYPE_LABELS[r.link_type] || r.link_type, 'tkt-chip-info') : '') +
                    chip(r.status_name || STATUS_LABEL_FALLBACK[r.status_slug] || '—', statusChipClass(r.status_slug)) +
                '</a>' +
                (r.url_unlink ? '<button type="button" class="tkt-btn-icon" data-unlink="' + r.id + '" data-unlink-url="' + r.url_unlink + '" title="Desvincular"><i class="fa-solid fa-link-slash"></i></button>' : '') +
            '</div>';
        }).join('');

        $c.html(
            '<div class="tkt-side-card"><div class="tkt-side-card-head">Historial reciente</div><div class="tkt-side-card-body">' + (recent || '<div class="tkt-empty-box">Sin actividad.</div>') + '</div></div>' +
            '<div class="tkt-side-card"><div class="tkt-side-card-head">Tickets relacionados</div><div class="tkt-side-card-body">' +
                (relatedHtml || '<div class="tkt-empty-box">No hay otros tickets de este cliente.</div>') +
                '<button type="button" class="tkt-btn" id="tkt-link-ticket"><i class="fa-solid fa-link"></i> Vincular ticket</button>' +
            '</div></div>'
        );

        $c.find('#tkt-link-ticket').on('click', function () { linkTicketPrompt(TKA.state.currentTicket); });
        $c.find('[data-unlink]').on('click', function () {
            var unlinkUrl = $(this).data('unlink-url');
            openConfirmModal({
                icon: 'fa-solid fa-link-slash',
                title: 'Desvincular ticket',
                message: 'El enlace entre ambos tickets se eliminará.',
                confirmLabel: 'Desvincular',
                danger: true,
                onConfirm: function () {
                    var t = TKA.state.currentTicket;
                    $.ajax({
                        url: unlinkUrl,
                        method: 'DELETE',
                        headers: { Accept: 'application/json' },
                        success: function () {
                            if (window.toastr) toastr.success('Ticket desvinculado');
                            fetchDetailData(t);
                        },
                        error: function () { if (window.toastr) toastr.error('No se pudo desvincular'); },
                    });
                },
            });
        });
    }

    var LINK_TYPE_LABELS = { related: 'Relacionado', duplicate_of: 'Duplicado de', blocks: 'Bloquea a', blocked_by: 'Bloqueado por' };

    // Versión simplificada del modal "Vincular ticket" del mockup (que
    // busca por texto en vivo): pide el número de ticket por prompt. El
    // backend (LinkTicketRequest→TicketLink, con exists+self-link guard) es
    // el real; solo el picker es más simple.
    var LINK_TYPES = [
        { value: 'related', title: 'Relacionado', sub: 'Los dos tickets tratan del mismo asunto' },
        { value: 'duplicate_of', title: 'Es un duplicado de', sub: 'Este ticket repite el otro' },
        { value: 'blocks', title: 'Bloquea a', sub: 'Este ticket debe cerrarse antes que el otro' },
        { value: 'blocked_by', title: 'Bloqueado por', sub: 'No se puede cerrar hasta que el otro se resuelva' },
    ];

    // El picker de participante en vivo del mockup se sustituye por un ID
    // numérico (mismo criterio que Aplazar/Fusionar) — el backend real
    // (LinkTicketRequest→TicketLink) sí valida existencia y evita
    // auto-enlace. `blocks`/`blocked_by` tienen efecto real:
    // Ticket::openBlockers() impide cerrar el ticket bloqueado.
    function linkTicketPrompt(t) {
        var options = LINK_TYPES.map(function (lt, i) {
            return '<label class="tkt-option' + (i === 0 ? ' on' : '') + '" data-link-type-option="' + lt.value + '" style="cursor:pointer">' +
                '<input type="radio" name="tkt-link-type" value="' + lt.value + '"' + (i === 0 ? ' checked' : '') + ' style="margin:0">' +
                '<span><span class="tkt-option-title">' + escapeHtml(lt.title) + '</span><br><span class="tkt-option-sub">' + escapeHtml(lt.sub) + '</span></span>' +
            '</label>';
        }).join('');

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-link',
            kicker: t.ticket_number,
            title: 'Vincular ticket',
            width: 'sm',
            body: '' +
                '<div class="tkt-field"><label class="tkt-label">Nº o ID del ticket<span class="req">*</span><span class="hint">visible en la URL al abrirlo</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-link-target" placeholder="Ej: 7"></div>' +
                '<div style="display:flex;flex-direction:column;gap:6px">' + options + '</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-link-confirm">Vincular</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('click', '[data-link-type-option]', function () {
            $backdrop.find('[data-link-type-option]').removeClass('on');
            $(this).addClass('on').find('input').prop('checked', true);
        });

        $backdrop.on('click', '#tkt-link-confirm', function () {
            var input = $('#tkt-link-target').val().trim();
            if (!/^\d+$/.test(input)) { if (window.toastr) toastr.error('Escribe un ID numérico de ticket'); return; }
            var linkType = $backdrop.find('input[name="tkt-link-type"]:checked').val();

            $.ajax({
                url: t.url_link,
                method: 'POST',
                data: { linked_ticket_id: input, link_type: linkType },
                headers: { Accept: 'application/json' },
                success: function () {
                    if (window.toastr) toastr.success('Ticket vinculado');
                    closeModal();
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'No se pudo vincular el ticket';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        });
    }

    // ═══════════ Colaboración en vivo (Fase E) ═══════════
    // Reusa tal cual la infraestructura ya existente (canal de presencia
    // ticket.{id} + eventos TicketTyping/TicketViewing, mismo patrón que
    // emails.js) — sin backend nuevo. El indicador de escritura, ausente
    // hasta ahora, ya tiene dónde engancharse: la caja de respuesta real
    // del Hilo (#tkt-reply-body).
    function joinTicketPresence(ticketId) {
        if (typeof window.Echo === 'undefined' || !ticketId) return;
        if (TKA.state.presenceTicketId === ticketId) return;

        leaveTicketPresence();
        TKA.state.presenceTicketId = ticketId;

        try {
            TKA.state.presenceChannel = window.Echo.join('ticket.' + ticketId)
                .here(function (users) { renderCollisionBanner(users); })
                .joining(function () { renderCollisionBanner(); })
                .leaving(function () { renderCollisionBanner(); })
                .listen('.typing', function (e) {
                    if (e.userId === TKA.state.currentUserId) return;
                    var $ind = $('#tkt-typing-indicator');
                    if (e.isTyping) {
                        $('#tkt-typing-text').html('<strong>' + escapeHtml(e.userName) + '</strong> está redactando una respuesta…');
                        $ind.show();
                    } else {
                        $ind.hide();
                    }
                });
        } catch (e) {
            // Sin Echo/Reverb levantado en este entorno: la pantalla sigue
            // funcionando igual, solo sin el aviso de colaboración en vivo.
            TKA.state.presenceChannel = null;
        }
    }

    function emitTyping(isTyping) {
        var t = TKA.state.currentTicket;
        if (!t || !TKA.urls.typingTemplate) return;
        $.post(TKA.urls.typingTemplate.replace('__TICKET__', t.id), { is_typing: isTyping ? 1 : 0 });
    }

    function leaveTicketPresence() {
        if (TKA.state.presenceTicketId && typeof window.Echo !== 'undefined') {
            try { window.Echo.leave('ticket.' + TKA.state.presenceTicketId); } catch (e) { /* ignore */ }
        }
        TKA.state.presenceChannel = null;
        TKA.state.presenceTicketId = null;
    }

    function renderCollisionBanner(users) {
        if (users) TKA.state.presenceUsers = users;
        var others = (TKA.state.presenceUsers || []).filter(function (u) { return u.id !== TKA.state.currentUserId; });

        if (!others.length) {
            $('#tkt-collision-banner').hide();
            return;
        }

        var names = others.map(function (u) { return u.name; }).join(', ');
        var verb = others.length > 1 ? 'están viendo' : 'está viendo';
        $('#tkt-collision-text').html('<strong>' + escapeHtml(names) + '</strong> ' + verb + ' este ticket.');
        $('#tkt-collision-banner').show();
    }

    function patchTags(t, add, remove) {
        var data = {};
        if (add) data.add = add;
        if (remove) data.remove = remove;
        $.ajax({
            url: t.url_tags,
            method: 'PATCH',
            data: data,
            headers: { Accept: 'application/json' },
            success: function (resp) {
                t.tags = resp.tags;
                renderRowTagsIfVisible(t);
                renderTagsPane($('#tkt-side-content'), t, TKA.state.currentDetail ? TKA.state.currentDetail.ai_suggestion : null);
                if (window.toastr) toastr.success('Etiquetas actualizadas');
            },
            error: function () {
                if (window.toastr) toastr.error('No se pudo actualizar las etiquetas');
            },
        });
    }

    function renderRowTagsIfVisible() {
        // Las etiquetas no se muestran en la fila de la lista en esta
        // pantalla (a diferencia del mockup de Emails) — no-op reservado
        // por si una fase posterior las añade a la fila.
    }

    function createNote(t) {
        var $ta = $('#tkt-new-note');
        var body = $ta.val().trim();
        if (!body) return;
        var isPinned = $('#tkt-new-note-pin').is(':checked');
        $.ajax({
            url: TKA.urls.notesStoreTemplate.replace('__TICKET__', t.id),
            method: 'POST',
            data: { ticket_id: t.id, body: body, is_pinned: isPinned ? 1 : 0 },
            headers: { Accept: 'application/json' },
            success: function () {
                if (window.toastr) toastr.success('Nota guardada');
                fetchDetailData(t);
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo guardar la nota';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            },
        });
    }

    function patchTicket(t, field, value) {
        // La ruta es PUT, pero un PUT real por AJAX devuelve 405 en este
        // entorno Docker aunque route:list/OPTIONS lo muestren registrado
        // (gotcha ya documentado del proyecto) — se manda como POST con
        // _method=PUT (spoofing nativo de Laravel), igual que un formulario
        // Blade con @method('PUT').
        var data = { _method: 'PUT' };
        data[field] = value;
        $.ajax({
            url: t.url_update,
            method: 'POST',
            data: data,
            headers: { Accept: 'application/json' },
            success: function () {
                if (window.toastr) toastr.success('Ticket actualizado');
                window.location.reload();
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo actualizar el ticket';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            },
        });
    }

    function runLifecycleAction(t, action, extra) {
        var url = action === 'resolve' ? t.url_resolve : (action === 'close' ? t.url_close : t.url_reopen);
        if (!url) return;
        $.ajax({
            url: url,
            method: 'POST',
            headers: { Accept: 'application/json' },
            data: extra || {},
            success: function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Ticket actualizado');
                window.location.reload();
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo completar la acción';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            },
        });
    }

    // Modal "Cerrar ticket": motivos de config('helpdesktickets.close_reasons')
    // (mismas claves/descripciones que el modal "Cerrar conversación" de
    // Conversaciones) + campo libre para "Otro motivo" — mismo patrón radio
    // ya usado en openDeleteModal/openExportModal (label.tkt-option +
    // input radio oculto). El motivo es opcional: cerrar sin elegir ninguno
    // sigue funcionando (equivalente al confirm() que sustituye este modal).
    var CLOSE_REASON_SUB = {
        resolved: 'El cliente quedó satisfecho con la solución',
        duplicated: 'Ya hay otro ticket abierto para este caso',
        spam: 'Mensaje no solicitado o fuera de contexto',
        unresponsive: 'Cerrar por inactividad prolongada',
        other: '',
    };

    function openCloseTicketModal(t) {
        var reasons = TKA.state.closeReasons || [];
        var optionsHtml = reasons.map(function (r, i) {
            var sub = CLOSE_REASON_SUB[r.key];
            return '<label class="tkt-option' + (i === 0 ? ' on' : '') + '" data-reason-option="' + escapeHtml(r.key) + '">' +
                '<input type="radio" name="tkt-close-reason" value="' + escapeHtml(r.key) + '"' + (i === 0 ? ' checked' : '') + ' style="margin:0">' +
                '<span><span class="tkt-option-title">' + escapeHtml(r.label) + '</span>' +
                (sub ? '<br><span class="tkt-option-sub">' + escapeHtml(sub) + '</span>' : '') + '</span></label>';
        }).join('');

        var $modal = openModal(modalShell({
            icon: 'fa-solid fa-lock',
            kicker: '#' + (t.ticket_number || t.id),
            title: 'Cerrar ticket',
            body:
                '<div class="tkt-field">' +
                    '<label class="tkt-label">Motivo (opcional)</label>' +
                    optionsHtml +
                '</div>' +
                '<div class="tkt-field" id="tkt-close-other-wrap" hidden>' +
                    '<label class="tkt-label">Detalle</label>' +
                    '<input type="text" class="tkt-input" id="tkt-close-other-input" maxlength="100" placeholder="Describe el motivo…">' +
                '</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-close-confirm">Cerrar ticket</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $modal.on('click', '[data-reason-option]', function () {
            $modal.find('[data-reason-option]').removeClass('on');
            $(this).addClass('on').find('input').prop('checked', true);
            var reason = $(this).data('reason-option');
            $modal.find('#tkt-close-other-wrap').prop('hidden', reason !== 'other');
            if (reason === 'other') $modal.find('#tkt-close-other-input').trigger('focus');
        });

        $modal.on('click', '#tkt-close-confirm', function () {
            var reason = $modal.find('input[name="tkt-close-reason"]:checked').val() || '';
            if (reason === 'other') reason = $modal.find('#tkt-close-other-input').val() || '';
            closeModal();
            runLifecycleAction(t, 'close', { reason: reason });
        });
    }

    // ═══════════ Bulk actions ═══════════
    function renderBulkBar() {
        var ids = Object.keys(TKA.state.bulk).map(Number);
        $('#tkt-bulk-count').text(ids.length + (ids.length === 1 ? ' seleccionado' : ' seleccionados'));
        $('#tkt-bulk-bar').toggleClass('on', ids.length > 0);
    }

    // Acciones en bloque que necesitan un valor adicional que
    // BulkTicketRequest exige (agent_id/tag/status_id) — cada una abre su
    // propio modal con el control correcto (select de agente/estado, input
    // de texto) en vez de un prompt(). Mismo patrón que
    // openBulkMoveTeamModal (ya existente para assign_group).
    var BULK_EXTRA_CONFIG = {
        assign: {
            icon: 'fa-solid fa-user-check', title: 'Asignar agente', field: 'agent_id', confirmLabel: 'Asignar',
            emptyError: 'Selecciona un agente',
            body: function () {
                return '<div class="tkt-field"><label class="tkt-label">Agente</label><select id="tkt-bulk-extra" class="tkt-select"><option value="">Selecciona un agente…</option>' + optionsHtml(TKA.state.agentsFull, 'id', '') + '</select></div>';
            },
        },
        add_tag: {
            icon: 'fa-solid fa-tag', title: 'Añadir etiqueta', field: 'tag', confirmLabel: 'Añadir',
            emptyError: 'Escribe una etiqueta',
            body: function () {
                return '<div class="tkt-field"><label class="tkt-label">Etiqueta</label><input type="text" class="tkt-input" id="tkt-bulk-extra" maxlength="50" placeholder="Ej: urgente-cliente"></div>';
            },
        },
        change_status: {
            icon: 'fa-solid fa-arrow-right-arrow-left', title: 'Cambiar estado', field: 'status_id', confirmLabel: 'Cambiar',
            emptyError: 'Selecciona un estado',
            body: function () {
                return '<div class="tkt-field"><label class="tkt-label">Estado</label><select id="tkt-bulk-extra" class="tkt-select"><option value="">Selecciona un estado…</option>' + optionsHtml(TKA.state.statuses, 'id', '') + '</select></div>';
            },
        },
    };

    function openBulkExtraModal(action) {
        var ids = Object.keys(TKA.state.bulk).map(Number);
        var config = BULK_EXTRA_CONFIG[action];
        if (!ids.length || !config) return;

        var $modal = openModal(modalShell({
            icon: config.icon,
            kicker: ids.length + (ids.length === 1 ? ' ticket seleccionado' : ' tickets seleccionados'),
            title: config.title,
            body: config.body(),
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-bulk-extra-confirm">' + config.confirmLabel + '</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $modal.on('click', '#tkt-bulk-extra-confirm', function () {
            var value = ($('#tkt-bulk-extra').val() || '').toString().trim();
            if (!value) {
                if (window.toastr) toastr.error(config.emptyError); else window.alert(config.emptyError);
                return;
            }
            closeModal();
            var extra = {};
            extra[config.field] = value;
            runBulkAction(action, extra);
        });
    }

    // extra es opcional: las acciones sin valor adicional (cerrar/resolver/
    // reabrir/eliminar) no lo necesitan; las que sí (assign/add_tag/
    // change_status/assign_group) siempre lo pasan ya resuelto desde su
    // modal — ver openBulkExtraModal/openBulkMoveTeamModal.
    //
    // Sin confirm() aquí dentro a propósito — bug real de UX encontrado en
    // QA: al venir de un modal (assign/add_tag/change_status/assign_group)
    // el usuario YA confirmó al pulsar "Asignar"/"Cambiar"/etc., así que un
    // window.confirm() adicional aquí era una doble confirmación con el
    // nombre interno de la acción en crudo ('¿Aplicar "assign" a...?'). La
    // única acción SIN modal previo (resolve/close/delete) confirma en su
    // propio punto de entrada, ver bindEvents().
    function runBulkAction(action, extra) {
        var ids = Object.keys(TKA.state.bulk).map(Number);
        if (!ids.length) return;
        extra = extra || {};

        $.ajax({
            url: TKA.urls.bulk,
            method: 'POST',
            data: $.extend({ ticket_ids: ids, action: action }, extra),
            success: function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Acción completada');
                window.location.reload();
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'No se pudo completar la acción';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            },
        });
    }

    // "Mover a equipo" es la única acción en bloque con un modal real en
    // vez de prompt() — el equipo (TKA.state.groups) ya se usa como
    // <select> en el panel Gestión, así que aquí también se elige de una
    // lista en vez de tener que escribir un ID a ciegas. Ejecuta la misma
    // acción/endpoint que el resto (runBulkAction → BulkTicketsController,
    // action: assign_group, ya soportado en el backend).
    function openBulkMoveTeamModal() {
        var ids = Object.keys(TKA.state.bulk).map(Number);
        if (!ids.length) return;

        var body =
            '<div class="tkt-field">' +
                '<label class="tkt-label">Equipo</label>' +
                '<select id="tkt-bulk-group" class="tkt-select"><option value="">Selecciona un equipo…</option>' + optionsHtml(TKA.state.groups, 'id', '') + '</select>' +
            '</div>';
        var foot =
            '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-bulk-group-confirm">Mover</button>' +
            '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>';

        var $modal = openModal(modalShell({
            icon: 'fa-solid fa-people-group',
            kicker: ids.length + (ids.length === 1 ? ' ticket seleccionado' : ' tickets seleccionados'),
            title: 'Mover a equipo',
            body: body,
            foot: foot,
        }));

        $modal.on('click', '#tkt-bulk-group-confirm', function () {
            var groupId = $('#tkt-bulk-group').val();
            if (!groupId) {
                if (window.toastr) toastr.error('Selecciona un equipo'); else window.alert('Selecciona un equipo');
                return;
            }
            closeModal();
            runBulkAction('assign_group', { group_id: groupId });
        });
    }

    // ═══════════ Eventos ═══════════
    function bindEvents() {
        $('.tkt-state-tab[data-filter], .tkt-view-pill[data-filter]').on('click', function () {
            TKA.state.filter = $(this).data('filter');
            renderTabs();
            renderList();
        });

        bindKeyboardShortcuts();

        $('#tkt-mode-switch [data-mode]').on('click', function () {
            var mode = $(this).data('mode');
            $('#tkt-mode-switch [data-mode]').removeClass('on');
            $(this).addClass('on');
            $('#tkt-split-wrap').toggle(mode === 'list');
            $('#tkt-kanban').toggleClass('on', mode === 'kanban');
            if (mode === 'kanban') renderKanban();
        });

        $('#tkt-sync').on('click', syncCurrentCustomer);
        $('#tkt-export-open').on('click', openExportModal);
        bindStaticModal('#tkt-filters-modal-open', '#tkt-filters-modal-backdrop', '#tkt-filters-modal-close');

        $('#tkt-save-view').on('click', saveCurrentView);
        $('#tkt-pill-queue').on('click', openQueueModal);
        $('#tkt-pill-workload').on('click', openWorkloadModal);

        $('#tkt-side-rail [data-side]').on('click', function () {
            selectSideTab($(this).data('side'));
        });

        $('#tkt-select-all').on('change', function () {
            var checked = this.checked;
            visibleTickets().forEach(function (t) {
                if (checked) TKA.state.bulk[t.id] = true; else delete TKA.state.bulk[t.id];
            });
            $('.tkt-ticket-check').prop('checked', checked);
            renderBulkBar();
        });

        $('#tkt-bulk-clear').on('click', function () {
            TKA.state.bulk = {};
            $('.tkt-ticket-check').prop('checked', false);
            $('#tkt-select-all').prop('checked', false);
            renderBulkBar();
        });

        var BULK_DIRECT_LABELS = {
            resolve: { title: 'Marcar como resuelto', message: '¿Marcar como resuelto?', confirmLabel: 'Resolver', danger: false },
            close: { title: 'Cerrar tickets', message: '¿Cerrar los tickets seleccionados?', confirmLabel: 'Cerrar', danger: false },
            delete: { title: 'Eliminar tickets', message: 'Esta acción no se puede deshacer.', confirmLabel: 'Eliminar', danger: true },
        };
        $('[data-bulk-action]').on('click', function () {
            var action = $(this).data('bulk-action');
            if (BULK_EXTRA_CONFIG[action]) { openBulkExtraModal(action); return; }

            var ids = Object.keys(TKA.state.bulk).map(Number);
            var l = BULK_DIRECT_LABELS[action] || { title: 'Confirmar acción', message: '¿Aplicar esta acción?', confirmLabel: 'Confirmar', danger: false };
            openConfirmModal({
                icon: l.danger ? 'fa-solid fa-trash' : 'fa-solid fa-check',
                title: l.title,
                message: ids.length + (ids.length === 1 ? ' ticket seleccionado. ' : ' tickets seleccionados. ') + l.message,
                confirmLabel: l.confirmLabel,
                danger: l.danger,
                onConfirm: function () { runBulkAction(action); },
            });
        });

        $('#tkt-bulk-move-team').on('click', openBulkMoveTeamModal);

        $('#tkt-search').on('keydown', function (ev) {
            if (ev.key !== 'Enter') return;
            var params = new URLSearchParams(window.location.search);
            var val = $(this).val();
            if (val) params.set('search', val); else params.delete('search');
            window.location = TKA.urls.index + '?' + params.toString();
        });
    }

    // ═══════════ Atajos de teclado (J/K navegar, C nuevo ticket) ═══════════
    // El mockup también documenta "⌘K" para el buscador, pero el tema base
    // YA usa ⌘K globalmente para el buscador del sistema (barra superior,
    // #gs-input) — bug real encontrado al probar: mi atajo local competía
    // con ese y a veces robaba el foco. Se deja el ⌘K como está (el global),
    // sin duplicarlo aquí; el chip visual junto al buscador local queda solo
    // como texto informativo del propio input, no como atajo real distinto.
    function bindKeyboardShortcuts() {
        $(document).on('keydown', function (ev) {
            var tag = (ev.target.tagName || '').toLowerCase();
            var typing = tag === 'input' || tag === 'textarea' || tag === 'select' || ev.target.isContentEditable;

            if (typing) return;

            if (ev.key === 'j' || ev.key === 'J') {
                ev.preventDefault();
                moveSelection(1);
            } else if (ev.key === 'k' || ev.key === 'K') {
                ev.preventDefault();
                moveSelection(-1);
            } else if (ev.key === 'c' || ev.key === 'C') {
                window.location = $('a.tkt-btn-primary').attr('href');
            }
        });
    }

    function moveSelection(delta) {
        var rows = visibleTickets();
        if (!rows.length) return;
        var idx = rows.findIndex(function (t) { return t.id === TKA.state.selected; });
        var next = rows[Math.min(Math.max(idx + delta, 0), rows.length - 1)] || rows[0];
        selectTicket(next);
        var $row = $('.tkt-ticket-row[data-id="' + next.id + '"]');
        if ($row.length) $row[0].scrollIntoView({ block: 'nearest' });
    }

    $(document).ready(bootstrap);
})(window.jQuery);
