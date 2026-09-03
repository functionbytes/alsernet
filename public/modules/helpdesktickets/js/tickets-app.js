/**
 * Gestión de tickets — /panel/helpdesk/tickets
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

    // Auto-crece el textarea de respuesta hasta max-height:120px (definido
    // inline junto al propio <textarea>) — antes se quedaba fijo en su
    // altura mínima (~2 líneas) al insertar una plantilla larga, sin ningún
    // indicio visual de que había más contenido debajo del scroll.
    function autoResizeTextarea(el) {
        if (!el) return;
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 120) + 'px';
    }

    function initials(name) {
        var parts = String(name || '').trim().split(/\s+/).filter(Boolean);
        if (!parts.length) return '?';
        return (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase();
    }

    // Claves de origen tal como están en la BD. Conviven varias formas para
    // lo mismo ('form'/'formulario'/'web_form') por datos de distintas épocas:
    // se mapean todas en vez de normalizar la columna, que obligaría a una
    // migración de datos por una etiqueta.
    var ORIGIN_LABELS = {
        email: 'Email', widget: 'Widget', wa: 'WhatsApp', whatsapp: 'WhatsApp',
        fb: 'Facebook', facebook: 'Facebook', ig: 'Instagram', instagram: 'Instagram',
        agent: 'Agente', manual: 'Manual', api: 'API', phone: 'Teléfono',
        form: 'Formulario', formulario: 'Formulario', web_form: 'Formulario',
        prestashop: 'PrestaShop', recurring: 'Recurrente', scheduled: 'Programado',
        import: 'Importado', chat: 'Chat', portal: 'Portal',
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
    // Meses abreviados en español, para la fecha de la fila del listado.
    var MONTH_SHORT = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    // En el mockup "Alta" y "Urgente" comparten exactamente el mismo chip
    // (fondo #18181b, texto #fff) — es el único elemento de la pantalla que
    // usa el negro sólido, y por eso destaca sobre los grises del resto.
    // "Baja"/"Normal" van en gris, sin peso visual.
    var PRIORITY_CHIP_CLASS = { low: 'tkt-chip-muted', normal: 'tkt-chip-muted', high: 'tkt-chip-danger', urgent: 'tkt-chip-danger' };
    function priorityChipClass(slug) {
        return PRIORITY_CHIP_CLASS[slug] || 'tkt-chip-muted';
    }

    // Clase de chip por estado real (open/pending/resolved/closed) — antes
    // el chip de los tickets relacionados era siempre gris sin importar si
    // seguían abiertos o ya se habían cerrado.
    // Los cinco chips de estado del mockup: Abierto #f5f6f8/#3f3f46 (info),
    // Pendiente y Cerrado #f5f6f8/#52525b (muted), Resuelto #eef5d9/#5b7a0d
    // (ok) y Sin asignar #e4e4e7/#18181b (warn).
    var STATUS_CHIP_CLASS = { open: 'tkt-chip-info', progress: 'tkt-chip-info', pending: 'tkt-chip-muted', resolved: 'tkt-chip-ok', closed: 'tkt-chip-muted', unassigned: 'tkt-chip-warn' };
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
        TKA.state.view = $data.attr('data-initial-view') || 'list';
        TKA.state.statuses = safeJson($data.attr('data-statuses'), []);
        TKA.state.categories = safeJson($data.attr('data-categories'), []);
        TKA.state.groups = safeJson($data.attr('data-groups'), []);
        TKA.state.agentsFull = safeJson($data.attr('data-agents-full'), []);
        TKA.state.cannedReplies = safeJson($data.attr('data-canned-replies'), []);
        TKA.state.ticketTemplates = safeJson($data.attr('data-ticket-templates'), []);
        TKA.state.closeReasons = safeJson($data.attr('data-close-reasons'), []);
        TKA.state.closeRootCauses = safeJson($data.attr('data-close-root-causes'), []);
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
        TKA.urls.mailResendTemplate = $data.attr('data-mail-resend-url-template');
        TKA.urls.mailDataTemplate = $data.attr('data-mail-data-url-template');
        TKA.urls.exportTemplate = $data.attr('data-export-url-template');
        TKA.urls.exportEstimate = $data.attr('data-export-estimate-url') || null;
        TKA.urls.automationsIndex = $data.attr('data-automations-index-url');
        TKA.urls.slaPolicies = $data.attr('data-sla-policies-url');
        TKA.urls.ticketTemplatesIndex = $data.attr('data-ticket-templates-index-url');
        TKA.urls.ticketCreate = $data.attr('data-ticket-create-url');
        TKA.urls.settingsSnapshot = $data.attr('data-settings-snapshot-url');
        TKA.urls.emailChannels = $data.attr('data-email-channels-url');
        TKA.urls.recurring = $data.attr('data-recurring-url');
        TKA.urls.cannedUpdateTemplate = $data.attr('data-canned-update-url-template');
        TKA.state.senders = safeJson($data.attr('data-senders'), []);
        TKA.urls.contactsMergeSearchTemplate = $data.attr('data-contacts-merge-search-url-template');
        TKA.urls.contactsMergePreviewTemplate = $data.attr('data-contacts-merge-preview-url-template');
        TKA.urls.activityAudit = $data.attr('data-activity-audit-url') || null;
        TKA.urls.notificationsIndex = $data.attr('data-notifications-index-url') || null;
        TKA.urls.ticketSearch = $data.attr('data-ticket-search-url') || null;
        TKA.urls.notifPrefs = $data.attr('data-notif-prefs-url') || null;
        TKA.urls.notifPrefsUpdate = $data.attr('data-notif-prefs-update-url') || null;
        TKA.urls.recurringOps = $data.attr('data-recurring-ops-url') || null;
        TKA.urls.recurringStore = $data.attr('data-recurring-store-url') || null;
        TKA.urls.recurringUpdateTemplate = $data.attr('data-recurring-update-url-template') || null;
        TKA.urls.recurringToggleTemplate = $data.attr('data-recurring-toggle-url-template') || null;
        TKA.urls.mailboxes = $data.attr('data-mailboxes-url') || null;
        TKA.urls.mailboxBehaviorTemplate = $data.attr('data-mailbox-behavior-url-template') || null;
        TKA.urls.mailboxTestTemplate = $data.attr('data-mailbox-test-url-template') || null;
        TKA.urls.workloadOverview = $data.attr('data-workload-overview-url') || null;
        TKA.urls.workloadAssignment = $data.attr('data-workload-assignment-url') || null;
        TKA.urls.slaCalendar = $data.attr('data-sla-calendar-url') || null;
        TKA.urls.slaPauseStatus = $data.attr('data-sla-pause-status-url') || null;
        TKA.urls.automationsList = $data.attr('data-automations-list-url') || null;
        TKA.urls.automationsStore = $data.attr('data-automations-store-url') || null;
        TKA.urls.automationsPreview = $data.attr('data-automations-preview-url') || null;
        TKA.urls.automationsToggleTemplate = $data.attr('data-automations-toggle-url-template') || null;
        TKA.urls.contactsMergeExecuteTemplate = $data.attr('data-contacts-merge-execute-url-template');
        TKA.urls.ops = $data.attr('data-ops-url');
        TKA.urls.macrosIndex = $data.attr('data-macros-index-url');
        TKA.urls.ticketStore = $data.attr('data-ticket-store-url');
        TKA.urls.duplicatesPreview = $data.attr('data-duplicates-preview-url');
        TKA.state.customers = safeJson($data.attr('data-customers'), []);
        TKA.urls.queueRetry = $data.attr('data-queue-retry-url');
        TKA.urls.queueFlush = $data.attr('data-queue-flush-url');
        TKA.urls.workload = $data.attr('data-workload-url');
        TKA.urls.reputation = $data.attr('data-reputation-url');
        TKA.urls.workloadDistribute = $data.attr('data-workload-distribute-url');
        TKA.urls.ticketTemplates = safeJson($data.attr('data-ticket-url-templates'), {});

        // PERF-09: toListRow() ya no manda las ~27 URLs de acción por fila,
        // solo el id — las plantillas viajan una sola vez en
        // data-ticket-url-templates (Ticket::listRowUrlTemplates()). Se
        // expanden aquí, una vez, sustituyendo '__TICKET__' por el id real,
        // para que el resto del archivo siga leyendo t.url_update,
        // t.url_summary, etc. exactamente igual que antes.
        TKA.state.tickets = TKA.state.tickets.map(hydrateTicketUrls);

        bindEvents();
        renderTabs();

        fetchOpsQueueHint();

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

        // El blade ya prepara data-initial-view desde ?view= (mismo
        // criterio que data-initial-filter arriba) pero antes nunca se leía
        // — el toggle Lista/Kanban se perdía siempre al recargar. Solo se
        // aplica si difiere de 'list' (el estado por defecto ya renderizado
        // por el blade), evitando un toggle visual innecesario en el caso
        // común.
        if (TKA.state.view === 'kanban') applyViewMode('kanban');

        if (TKA.state.selected) {
            var pre = TKA.state.tickets.find(function (t) { return t.id === TKA.state.selected; });
            if (pre) selectTicket(pre);
        }
    }

    function safeJson(raw, fallback) {
        if (!raw) return fallback;
        try { return JSON.parse(raw); } catch (e) { return fallback; }
    }

    // Completa un ticket de TKA.state.tickets con sus URLs de acción a
    // partir de TKA.urls.ticketTemplates (ver bootstrap()) — las plantillas
    // con un segundo placeholder de sub-recurso (macro/nota/seguimiento/
    // conversación paralela) quedan con ese placeholder intacto, igual que
    // antes de PERF-09, listas para el .replace('__MACRO__', ...) etc. que
    // ya hace cada acción.
    function hydrateTicketUrls(t) {
        var templates = TKA.urls.ticketTemplates || {};
        for (var key in templates) {
            if (Object.prototype.hasOwnProperty.call(templates, key)) {
                t[key] = templates[key].replace('__TICKET__', t.id);
            }
        }
        return t;
    }

    // ═══════════ Filtro de tabs (mismo criterio que el toolbar htk-pill anterior) ═══════════
    function passesFilter(t) {
        var f = TKA.state.filter;
        if (f === 'all') return true;
        if (f === 'mine') return t.assignee && t.assignee.id === TKA.state.currentUserId;
        if (f === 'unassigned') return !t.assignee;
        if (f === 'urgent') return t.priority === 'urgent' || t.sla_kind === 'breach';
        if (f === 'sla_risk') return t.sla_kind === 'warn' || t.sla_kind === 'breach';
        // Vistas "Desde PrestaShop" / "Desde email" del mockup. El formulario
        // público de PrestaShop entra como 'formulario' y el alias 'web_form'
        // que también acepta Ticket::sourceSlug().
        if (f === 'from_presta') return t.source === 'formulario' || t.source === 'web_form';
        if (f === 'from_email') return t.source === 'email';
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
        // Miga de pan dinámica (comparado contra el mockup: "Helpdesk › Tickets
        // › {tab activo}") — toma el label del propio tab de estado activo
        // (clonado sin su contador .c) para no duplicar el mapeo de nombres en
        // un sitio aparte. Si el filtro activo es un chip de "Vistas"
        // (urgent/mine/sla_risk) en vez de un tab de estado, cae a "Todos"
        // — esos chips tampoco vivían en la miga del mockup.
        var $activeTab = $('.tkt-state-tab[data-filter="' + TKA.state.filter + '"]');
        var crumbLabel = 'Todos';
        if ($activeTab.length) {
            var $clone = $activeTab.clone();
            $clone.find('.c').remove();
            crumbLabel = $clone.text().trim();
        }
        $('#tkt-crumb-tab').text(crumbLabel);
    }

    // Recalcula los contadores de tabs/vistas a partir de TKA.state.tickets
    // (mismo criterio que passesFilter) — necesario tras el Kanban, cuyo
    // "soltar" cambia el estado sin recargar la página (a diferencia de
    // bulk/Gestión, que sí recargan y traen los conteos frescos del
    // servidor). Sin esto, "Pendientes/Resueltos"/"SLA en riesgo" quedaban
    // desactualizados después de arrastrar una tarjeta — bug real
    // encontrado al probar el drag&drop.
    function recomputeTabCounts() {
        // 'all' se preserva del total real que ya trajo el servidor
        // (TKA.state.tabCounts.all, cargado en bootstrap()) en vez de
        // recalcularlo como TKA.state.tickets.length: el array del cliente
        // solo contiene la página actual, así que "recalcularlo" aquí hacía
        // que el tab "Todos" bajara (p.ej. de 12 a 11) tras la primera
        // interacción de Kanban sin que el número de tickets reales hubiera
        // cambiado — discrepancia real encontrada en QA.
        var c = { open: 0, urgent: 0, mine: 0, unassigned: 0, pending: 0, resolved: 0, closed: 0, sla_risk: 0, all: TKA.state.tabCounts.all };
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
        // "01 sep 10:42": el mockup pone día y mes junto a la hora, no solo la
        // hora — con 30 tickets en pantalla, un "10:42" a secas no dice si es
        // de hoy o de la semana pasada.
        // toLocaleDateString('es-MX', {month:'short'}) devuelve "02-sep" (con
        // guion) en Chrome; el mockup escribe "01 sep 10:42". Se compone a
        // mano para no depender del separador que elija cada navegador.
        var timeLabel = '—';
        if (t.updated_at) {
            var d = new Date(t.updated_at);
            var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
            timeLabel = pad(d.getDate()) + ' ' + MONTH_SHORT[d.getMonth()] +
                ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        }

        // s-unassigned se añade como clase EXTRA (nunca sustituye la del
        // estado real) — la regla CSS .tkt-ticket-row.s-unassigned ya
        // existía en la hoja de estilos pero era inalcanzable (esta clase
        // nunca se usaba fuera del Kanban); al declararse después de
        // s-open/s-pending/… en el CSS, gana el borde rojo "necesita
        // atención" sobre el color normal del estado cuando el ticket no
        // tiene agente.
        // Fila del mockup: cuatro líneas apiladas en una sola columna, no una
        // columna principal + una lateral con la hora/SLA/prioridad.
        //   1) nº de ticket · asunto · clip · fecha
        //   2) cliente · empresa
        //   3) resumen del último mensaje
        //   4) chips de estado, prioridad, categoría y SLA
        var customerLine = t.customer
            ? (t.customer.company ? t.customer.name + ' · ' + t.customer.company : t.customer.name)
            : 'Sin cliente';
        var $row = $(
            '<div class="tkt-ticket-row ' + statusRowClass + (t.assignee ? '' : ' s-unassigned') + (isActive ? ' on' : '') + '" data-id="' + t.id + '">' +
            '<label class="tkt-ticket-checkzone">' +
                '<input type="checkbox" class="tkt-ticket-check" data-check="' + t.id + '" aria-label="Seleccionar ticket ' + escapeHtml(t.ticket_number) + '" ' + checked + '>' +
            '</label>' +
            '<div class="tkt-ticket-main">' +
                '<div class="tkt-ticket-line1">' +
                    '<span class="tkt-ticket-id mono">' + escapeHtml(t.ticket_number) + '</span>' +
                    '<span class="tkt-ticket-subject">' + escapeHtml(t.subject || '(sin asunto)') + '</span>' +
                    (t.has_attachments ? '<i class="fa-solid fa-paperclip tkt-ticket-clip" title="Con adjuntos"></i>' : '') +
                    '<span class="tkt-ticket-time mono">' + timeLabel + '</span>' +
                '</div>' +
                '<div class="tkt-ticket-customer">' + escapeHtml(customerLine) + '</div>' +
                (t.last_message_snippet ? '<div class="tkt-ticket-last">' + escapeHtml(t.last_message_snippet) + '</div>' : '') +
                '<div class="tkt-ticket-metarow">' +
                    '<span class="tkt-rchip">' + escapeHtml(statusLabel) + '</span>' +
                    (t.priority && t.priority !== 'normal' && t.priority !== 'low'
                        ? '<span class="tkt-rchip strong">' + escapeHtml(priorityLabel(t.priority)) + '</span>'
                        : '') +
                    (t.category_name ? '<span class="tkt-ticket-cat" title="' + escapeHtml(t.category_name) + '">' + escapeHtml(t.category_name) + '</span>' : '') +
                    // slaRowText() devuelve el guion largo cuando el ticket no
                    // tiene plazo: es un valor "vacío" con forma de texto, así
                    // que un truthy a secas pintaba "● —" en casi todas las
                    // filas. El mockup no muestra nada en ese caso.
                    (t.sla_text && t.sla_text !== '—' ? '<span class="tkt-rsla ' + slaClass(t.sla_kind) + '"><span class="tkt-sla-dot"></span>' + escapeHtml(t.sla_text) + '</span>' : '') +
                    '<span class="tkt-ticket-metaend">' +
                        // Sin contador de mensajes: el mockup deja este hueco
                        // vacío y el número repetido en cada fila era ruido.
                        // Se conserva solo el punto de "sin leer", que es la
                        // única señal de que hay algo nuevo que mirar.
                        (t.unread_count > 0 ? '<span class="tkt-ticket-unread" title="Con mensajes sin leer">●</span>' : '') +
                    '</span>' +
                '</div>' +
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
            // Sin icono ni título: en la columna estrecha de la lista el
            // mockup solo pone las dos frases, y la segunda dice qué hacer.
            $list.append(
                '<div class="tkt-list-empty">' +
                'Ningún ticket coincide con estos filtros.<br>' +
                '<span>Prueba a quitar alguno o amplía el rango de fechas.</span>' +
                '</div>'
            );
        } else {
            rows.forEach(function (t) { $list.append(renderRow(t)); });
        }

        // "6 tickets" a secas, como el mockup — el "en esta página" sobra
        // ahora que el pie de la lista dice el rango exacto.
        $('#tkt-count').text(rows.length + (rows.length === 1 ? ' ticket' : ' tickets'));

        // El pie de paginación ('1–N de N') es un partial Blade estático,
        // renderizado una sola vez por el servidor con el total SIN filtrar
        // — los tabs/chips de estado son 100% client-side, así que quedaba
        // congelado y podía contradecir directamente al empty-state (p.ej.
        // "1–11 de 11" a la vez que "No hay tickets" en el mismo pantallazo,
        // verificado en vivo con el tab "Resueltos"). Se actualiza aquí con
        // el conteo real ya filtrado, mismo criterio que #tkt-count arriba.
        var $footCount = $('.tkt-list-foot > span').first();
        if ($footCount.length) {
            $footCount.text((rows.length ? '1–' + rows.length : '0–0') + ' de ' + rows.length + ' tickets');
        }
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
        // Mismo criterio de chip de prioridad que renderRow() en la vista
        // Lista (solo high/urgent, con el mismo color) — antes la tarjeta
        // Kanban no daba ninguna señal de prioridad, reduciendo su utilidad
        // de triage a simple vista.
        var prioClass = t.priority === 'urgent' ? 'tkt-prio-urgent' : (t.priority === 'high' ? 'tkt-prio-high' : '');
        var prioBadge = (t.priority === 'high' || t.priority === 'urgent')
            ? '<span class="tkt-prio ' + prioClass + '"><span class="tkt-prio-dot"></span>' + escapeHtml(priorityLabel(t.priority)) + '</span>'
            : '';
        var $card = $(
            '<div class="tkt-kcard" draggable="true" data-id="' + t.id + '">' +
                '<div class="tkt-kcard-title">' + escapeHtml(t.subject || '(sin asunto)') + '</div>' +
                '<div class="tkt-kcard-sub tkt-trunc">' + escapeHtml(t.customer ? t.customer.name : 'Sin cliente') + '</div>' +
                '<div class="tkt-kcard-meta">' +
                    '<span class="tkt-chip-mono">' + escapeHtml(t.ticket_number) + '</span>' +
                    '<span class="tkt-kcard-origin">' + escapeHtml(ORIGIN_LABELS[t.source] || t.source || '') + '</span>' +
                    prioBadge +
                    // Mismo criterio que la fila del listado: sin plazo, sin chip.
                    (t.sla_text && t.sla_text !== '—'
                        ? '<span class="tkt-sla tkt-kcard-sla ' + slaClass(t.sla_kind) + '"><span class="tkt-sla-dot"></span>' + escapeHtml(t.sla_text) + '</span>'
                        : '') +
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
                        '<button type="button" class="tkt-kcol-add" data-kcol-add="' + col.key + '" title="Crear un ticket en ' + col.label + '" aria-label="Crear un ticket en ' + col.label + '"><i class="fa-solid fa-plus"></i></button>' +
                    '</div>' +
                '</div>'
            );
            $col.find('[data-kcol-add]').on('click', function () {
                window.location = TKA.urls.ticketCreate + '?status=' + encodeURIComponent($(this).data('kcol-add'));
            });
            $col.append($drop);
            $board.append($col);
        });
    }

    function moveTicketToBucket(t, bucket) {
        var sourceBucket = bucketFor(t);
        if (sourceBucket === bucket) return;

        // bucketFor() prioriza "sin asignar" sobre el estado real (a
        // propósito, ver el comentario de la función) — así que soltar un
        // ticket sin agente sobre una columna de estado real mutaba
        // status_id en el backend en silencio sin que la tarjeta se moviera
        // NUNCA visualmente (sigue sin agente, vuelve a "Sin asignar" en el
        // siguiente renderKanban()). Se bloquea aquí, ANTES de disparar el
        // PUT, con feedback explícito — el caso inverso (soltar cualquier
        // ticket sobre "Sin asignar") sigue funcionando igual que antes.
        if (sourceBucket === 'unassigned' && bucket !== 'unassigned') {
            if (window.toastr) toastr.warning('Asigna un agente antes de poder cambiar el estado desde Kanban.');
            else window.alert('Asigna un agente antes de poder cambiar el estado desde Kanban.');
            renderKanban();
            return;
        }

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
    // `source` opcional: un objeto {clave: valor} con los filtros a guardar.
    // Sin él se leen de la URL (el caso del botón "+ guardar vista" de la
    // barra de vistas); el modal de filtros pasa los suyos porque los campos
    // recién tocados todavía no están en la querystring y guardaría los
    // anteriores.
    function saveCurrentView(source) {
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

            var params = source
                ? { get: function (k) { return source[k] || null; } }
                : new URLSearchParams(window.location.search);

            // Solo las claves que TicketView::applyFilters() sabe traducir:
            // guardar cualquier otra la dejaría en la BD sin efecto y la
            // vista mentiría sobre lo que filtra.
            var filters = {};
            if (params.get('source')) filters.source = params.get('source');
            if (params.get('category')) filters.category_id = params.get('category');
            if (params.get('status')) filters.status_id = params.get('status');
            if (params.get('group')) filters.group_id = params.get('group');
            if (params.get('assignee') === 'me') filters.mine = true;
            else if (params.get('assignee') === 'unassigned') filters.unassigned = true;
            else if (params.get('assignee')) filters.assignee_id = params.get('assignee');
            if (params.get('priority')) filters.priority = params.get('priority');
            if (params.get('tag')) filters.tags = params.get('tag').split(',').map(function (x) { return x.trim(); }).filter(Boolean);
            if (params.get('sla_status') === 'breach') filters.sla_breach = true;
            if (params.get('created_from')) filters.created_from = params.get('created_from');
            if (params.get('created_to')) filters.created_to = params.get('created_to');
            if (params.get('archived')) filters.is_archived = true;

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
        // fa-spin (utilidad de FontAwesome, ya cargado globalmente) — el
        // icono fa-rotate sugiere giro pero antes nunca lo hacía; en una red
        // local rápida el único feedback (opacity:.5 del [disabled]) es casi
        // imperceptible antes de que aparezca el toast final.
        $btn.prop('disabled', true).find('i').addClass('fa-spin');

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
                $btn.prop('disabled', false).find('i').removeClass('fa-spin');
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
                    '<div class="tkt-fill">' +
                        (opts.kicker ? '<div class="tkt-modal-kicker">' + escapeHtml(opts.kicker) + '</div>' : '') +
                        // titleChip: el nº de ticket en negro junto al título,
                        // como en las cabeceras de modal del mockup.
                        '<div class="tkt-modal-title">' + escapeHtml(opts.title) +
                            (opts.titleChip ? '<span class="tkt-chip-id">' + escapeHtml(opts.titleChip) + '</span>' : '') +
                        '</div>' +
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

    // Botón "Crear un ticket" del estado vacío del detalle: mismo destino
    // que el "Nuevo ticket" de la barra superior.
    $(document).on('click', '#tkt-empty-create', function () {
        openNewTicketModal();
    });

    // El botón "Nuevo ticket" y el del estado vacío abren el modal 35. Los
    // dos conservan su href a /tickets/create: si el JS no cargara, el enlace
    // sigue llevando al formulario completo.
    $(document).on('click', '#tkt-new-ticket', function (ev) {
        ev.preventDefault();
        openNewTicketModal();
    });

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

    /**
     * Fecha corta para la cabecera: "hoy 10:42" cuando es de hoy y
     * "30 ago 10:42" en cualquier otro caso — el formato del mockup. Con el
     * día entero delante, la línea de contexto no cabía.
     */
    function shortDateTime(iso) {
        var d = new Date(iso);
        if (isNaN(d)) return '';

        var hhmm = d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        var hoy = new Date();
        var esHoy = d.toDateString() === hoy.toDateString();

        return esHoy
            ? 'hoy ' + hhmm
            : d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' }) + ' ' + hhmm;
    }

    // Texto "Creado ... · <agente o sin asignar>" de la cabecera del
    // detalle — función propia para poder refrescarlo tras cambiar el
    // agente asignado sin tener que repintar toda la cabecera.
    function detailMetaText(t) {
        // "Creado … · última actualización … · <agente>", como el mockup. La
        // fecha de actualización solo se añade si aporta algo: en un ticket
        // recién creado coincide con la de creación y repetirla es ruido.
        var parts = ['Creado ' + (t.created_at_human || '—')];

        if (t.updated_at && t.updated_at !== t.created_at) {
            parts.push('última actualización ' + shortDateTime(t.updated_at));
        }

        parts.push(t.assignee ? t.assignee.name : 'sin asignar');

        return parts.join(' · ');
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
        // Mismo criterio que la fila del listado: slaRowText() devuelve el
        // guion largo cuando el ticket no tiene plazo, y "SLA —" no dice nada.
        // Sin plazo, el chip no se pinta.
        var slaChip = '';
        if (t.sla_kind === 'breach') slaChip = chip('SLA vencido', 'tkt-chip-danger');
        else if (t.sla_kind === 'warn') slaChip = chip('SLA en riesgo', 'tkt-chip-warn');
        else if (t.sla_text && t.sla_text !== '—') slaChip = chip('SLA ' + t.sla_text, 'tkt-chip-ok');

        // Posición dentro de la lista visible ("1 de 6" en el mockup), para
        // la barra de navegación de la cabecera.
        var visible = visibleTickets();
        var pos = visible.findIndex(function (x) { return x.id === t.id; });
        var positionLabel = pos >= 0 ? (pos + 1) + ' de ' + visible.length : '';

        // Chip de entrega del último correo. Solo se pinta cuando hay un dato
        // real que mostrar: el mockup lo enseña siempre porque su ticket de
        // ejemplo tiene correo enviado, pero un ticket de widget o WhatsApp
        // no tiene ninguna entrega que reportar.
        var deliveryChip = t.last_mail_status === 'delivered' || t.last_mail_status === 'sent'
            ? '<span class="tkt-chip-delivery"><i class="fa-solid fa-circle-check"></i> Email entregado</span>'
            : (t.last_mail_status === 'failed' || t.last_mail_status === 'bounced'
                ? '<span class="tkt-chip-delivery" style="background:var(--tkt-danger-bg);color:#fff"><i class="fa-solid fa-circle-exclamation"></i> Email rebotado</span>'
                : '');

        $d.html(
            '<div class="tkt-detail-head">' +
                '<div class="tkt-detail-navbar">' +
                    '<span class="tkt-cap">Tickets · Detalle</span>' +
                    '<span class="tkt-detail-position" id="tkt-detail-position">' + escapeHtml(positionLabel) + '</span>' +
                    '<span class="tkt-detail-nav">' +
                        '<button type="button" id="tkt-detail-prev" title="Anterior (K)" aria-label="Ticket anterior"' + (pos <= 0 ? ' disabled' : '') + '><i class="fa-solid fa-chevron-up"></i></button>' +
                        '<button type="button" id="tkt-detail-next" title="Siguiente (J)" aria-label="Ticket siguiente"' + (pos < 0 || pos >= visible.length - 1 ? ' disabled' : '') + '><i class="fa-solid fa-chevron-down"></i></button>' +
                    '</span>' +
                '</div>' +
                '<div class="tkt-detail-head-row">' +
                    '<div class="tkt-detail-title-col">' +
                        '<div class="tkt-detail-title">' + escapeHtml(t.subject || '(sin asunto)') + '</div>' +
                        '<div class="tkt-detail-chips">' +
                            deliveryChip +
                            '<span class="tkt-chip-id">' + escapeHtml(t.ticket_number) + '</span>' +
                            chip(statusLabel, statusChipClass(t.status_slug)) +
                            (t.priority ? chip(priorityLabel(t.priority), priorityChipClass(t.priority)) : '') +
                            '<span class="tkt-chip-mono">' + escapeHtml(ORIGIN_LABELS[t.source] || t.source || '—') + '</span>' +
                            slaChip +
                            (t.has_attachments ? '<span class="tkt-chip-att"><i class="fa-solid fa-paperclip"></i> 1</span>' : '') +
                        '</div>' +
                        '<div class="tkt-detail-context">' +
                            (t.customer ? '<span class="who"><i class="fa-regular fa-user"></i>' + escapeHtml(t.customer.name) + '</span>' : '<span class="who"><i class="fa-regular fa-user"></i>Sin cliente</span>') +
                            (t.customer && t.customer.email ? '<span class="sep">·</span><span class="mail">' + escapeHtml(t.customer.email) + '</span>' : '') +
                            '<span class="sep">·</span>' +
                            '<span id="tkt-detail-meta">' + escapeHtml(detailMetaText(t)) + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="tkt-detail-actions">' +
                        '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-goto-reply" title="Responder al cliente"><i class="fa-solid fa-reply"></i> Responder</button>' +
                        '<button type="button" class="tkt-btn-icon" id="tkt-goto-state" title="Cambiar estado" aria-label="Cambiar estado"><i class="fa-solid fa-arrow-right-arrow-left"></i></button>' +
                        '<button type="button" class="tkt-btn-icon" id="tkt-goto-assign" title="Asignar" aria-label="Asignar"><i class="fa-solid fa-user-plus"></i></button>' +
                        '<button type="button" class="tkt-btn-icon" id="tkt-goto-actions" title="Más acciones" aria-label="Más acciones"><i class="fa-solid fa-ellipsis"></i></button>' +
                    '</div>' +
                '</div>' +
                '<div class="tkt-dtabs">' +
                    '<button type="button" class="tkt-dtab" data-dtab="mail"><i class="fa-regular fa-envelope"></i> Correo<span class="tkt-dcount" data-badge="mail"></span></button>' +
                    '<button type="button" class="tkt-dtab on" data-dtab="thread"><i class="fa-solid fa-comments"></i> Hilo<span class="tkt-dcount" data-badge="thread"></span></button>' +
                    '<button type="button" class="tkt-dtab" data-dtab="trace"><i class="fa-solid fa-route"></i> Traza<span class="tkt-dcount" data-badge="trace"></span></button>' +
                    '<button type="button" class="tkt-dtab" data-dtab="activity"><i class="fa-solid fa-wave-square"></i> Actividad<span class="tkt-dcount" data-badge="activity"></span></button>' +
                    '<button type="button" class="tkt-dtab" data-dtab="files"><i class="fa-solid fa-paperclip"></i> Adjuntos<span class="tkt-dcount" data-badge="files"></span></button>' +
                '</div>' +
            '</div>' +
            '<div class="tkt-banner-warn tkt-detail-banner" id="tkt-collision-banner"  role="status"><i class="fa-solid fa-users"></i><span id="tkt-collision-text" class="tkt-flex1"></span></div>' +
            '<div class="tkt-banner-warn tkt-detail-banner" id="tkt-typing-indicator"  role="status"><i class="fa-solid fa-pen"></i><span id="tkt-typing-text" class="tkt-flex1"></span></div>' +
            '<div class="tkt-banner-ai tkt-detail-banner" id="tkt-ai-banner" ><i class="fa-solid fa-wand-magic-sparkles"></i>' +
                '<span class="tkt-banner-ai-main">' +
                    '<span class="tkt-banner-ai-head"><span class="tkt-banner-ai-title">Resumen IA</span>' +
                        '<span class="tkt-banner-ai-chips" id="tkt-ai-banner-chips"></span></span>' +
                    '<span id="tkt-ai-banner-text"></span>' +
                '</span>' +
                '<button type="button" class="tkt-btn tkt-btn-mini" id="tkt-ai-expand">Ampliar</button></div>' +
            '<div class="tkt-pane" id="tkt-dpane-mail" hidden></div>' +
            '<div class="tkt-pane" id="tkt-dpane-thread"><div class="tkt-skeleton"></div></div>' +
            '<div class="tkt-pane" id="tkt-dpane-trace" hidden></div>' +
            '<div class="tkt-pane" id="tkt-dpane-activity" hidden></div>' +
            '<div class="tkt-pane" id="tkt-dpane-files" hidden></div>'
        );

        joinTicketPresence(t.id);

        // El icono de estado abre el modal 36; los otros dos siguen llevando
        // el foco al campo correspondiente del panel Gestión.
        $('#tkt-goto-state').on('click', function () { openChangeStatusModal(t); });

        $('#tkt-goto-assign, #tkt-goto-actions').on('click', function () {
            var focusId = this.id === 'tkt-goto-assign' ? 'tkt-sg-assignee-wrap' : 'tkt-sg-actions';
            // Los tres viven dentro del panel "Gestión" del lateral: hay que abrirlo primero.
            if (TKA.state.sideTab !== 'gestion') selectSideTab('gestion');
            var $target = $('#' + focusId);
            if ($target.length) {
                $target[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
                if ($target.is('select')) {
                    // select2 oculta el <select> nativo con
                    // aria-hidden="true" (select2-hidden-accessible) —
                    // enfocarlo directamente (.trigger('focus')) hacía que
                    // Chrome bloqueara el foco con un warning real
                    // ("Blocked aria-hidden on an element because its
                    // descendant retained focus"), sin mover el foco a
                    // ningún control usable. select2('open') abre el propio
                    // combobox visible, que sí es accesible por teclado —
                    // efecto secundario: también da un affordance mucho más
                    // visible que el simple anillo de foco del navegador.
                    if ($target.data('select2')) $target.select2('open');
                    else $target.trigger('focus');
                }
                else $target.addClass('tkt-highlight-flash').one('animationend', function () { $(this).removeClass('tkt-highlight-flash'); });
            }
        });

        $('#tkt-goto-reply').on('click', function () { openComposeModal(t); });

        $d.find('[data-dtab]').on('click', function () {
            if ($(this).is('[disabled]')) return;
            selectDetailTab($(this).data('dtab'));
        });

        // Chevrones de la barra "N de M": mismo salto que los atajos J/K,
        // que ya existían pero solo por teclado.
        $('#tkt-ai-expand').on('click', function () { openAiSummaryModal(t); });
        $('#tkt-collision-banner').on('click', '[data-collision-open]', function () {
            openCollisionModal(t, TKA.state.presenceUsers || []);
        });

        $('#tkt-detail-prev').on('click', function () { moveSelection(-1); });
        $('#tkt-detail-next').on('click', function () { moveSelection(1); });

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
                // Chips del mockup: cuántos correos resume y en qué idioma
                // escribe el cliente. El mockup añade "confianza 86 %", que
                // el servicio de resumen no devuelve — no se inventa.
                var d = TKA.state.currentDetail;
                var chips = '';
                var nMails = d && d.mails ? d.mails.length : 0;
                if (nMails) chips += '<span class="tkt-banner-ai-chip">' + nMails + (nMails === 1 ? ' correo' : ' correos') + '</span>';
                var lang = d && d.customer && d.customer.language;
                if (lang) chips += '<span class="tkt-banner-ai-chip">' + escapeHtml(String(lang).toUpperCase()) + '</span>';

                $('#tkt-ai-banner-chips').html(chips);
                $('#tkt-ai-banner-text').text(res.summary);
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
                renderActivityPane(d.activity || [], d.activity_total_count);
                renderFilesPane(d.files || [], t);
                renderMailPane(d.mail);
                renderTracePane(d.trace || [], d.mail, d.trace_meta);
                renderActiveSidePane();
                updateDetailTabBadges(d);
                checkDuplicates(t);
            })
            .fail(function () {
                showDetailError(t);
            });
    }

    // Fallo al cargar el detalle. Sustituye al panel entero, no solo a la
    // pestaña Hilo: dejar las otras cuatro con los datos del ticket anterior
    // mientras la cabecera ya muestra el nuevo es peor que no mostrar nada.
    function showDetailError(t) {
        var $d = $('#tkt-detail');
        $d.html(
            '<div class="tkt-empty-state tkt-detail-error">' +
                '<div class="tkt-empty-icon"><i class="fa-solid fa-plug-circle-xmark"></i></div>' +
                '<div class="tkt-empty-title">No se pudo cargar el hilo</div>' +
                '<div class="tkt-empty-text">No pudimos recuperar la conversación de este ticket. Puedes reintentar o revisar el estado de la cola.</div>' +
                '<div class="tkt-badge-row">' +
                    '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-retry-detail">Reintentar</button>' +
                    '<button type="button" class="tkt-btn" id="tkt-error-queue">Ver la cola</button>' +
                '</div>' +
            '</div>'
        );
        $d.find('#tkt-retry-detail').on('click', function () { selectTicket(t); });
        $d.find('#tkt-error-queue').on('click', function () { openQueueModal(); });
    }

    // Badges numéricos del riel de iconos del detalle (Correo/Hilo/
    // Trazabilidad/Actividad/Archivos) — cuentan lo que realmente llegó en
    // data(), sin pedir nada nuevo al backend.
    function updateDetailTabBadges(d) {
        var activityCount = (d.activity || []).length;
        var activityTruncated = d.activity_total_count && d.activity_total_count > activityCount;
        var counts = {
            mail: d.mail ? 1 : 0,
            thread: (d.thread || []).length,
            trace: (d.trace || []).length,
            activity: activityTruncated ? (activityCount + '+') : activityCount,
            files: (d.files || []).length,
        };
        Object.keys(counts).forEach(function (key) {
            $('#tkt-detail [data-badge="' + key + '"]').text(counts[key] || '');
        });

        // El chip de adjuntos de la cabecera se pinta antes de que lleguen
        // estos datos, y con información incompleta: has_attachments solo mira
        // el ÚLTIMO mensaje, así que un ticket con ficheros en mensajes
        // anteriores no lo enseñaba, y cuando lo enseñaba era con un "1" fijo.
        // Aquí ya se conoce el número real de ficheros del ticket: se corrige,
        // se crea si faltaba, o se retira si al final no había ninguno.
        var $chips = $('#tkt-detail .tkt-detail-chips');
        var $att = $chips.find('.tkt-chip-att');

        if (counts.files > 0) {
            var html = '<i class="fa-solid fa-paperclip"></i> ' + counts.files;
            if ($att.length) {
                $att.html(html);
            } else {
                $chips.append('<span class="tkt-chip-att">' + html + '</span>');
            }
        } else {
            $att.remove();
        }
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

    // Etiquetas de canal del hilo: el ticket guarda el origen en clave
    // ('email', 'form'…) y el mockup lo enseña en cristiano.
    // (el mapa de canales es el mismo ORIGIN_LABELS de arriba)

    /**
     * Chips de la cabecera de un mensaje: quién es (rol), por dónde entró
     * (canal) y en qué sentido (entrante/saliente).
     *
     * Cada chip se omite si su dato no viene: un ticket creado a mano no
     * tiene canal, y pintar "—" en su lugar solo añade ruido.
     */
    function threadChips(it) {
        var out = '';

        if (it.role) out += '<span class="tkt-tchip">' + escapeHtml(it.role) + '</span>';

        if (it.channel) {
            out += '<span class="tkt-tchip">' + escapeHtml(ORIGIN_LABELS[it.channel] || it.channel) + '</span>';
        }

        if (it.direction) {
            out += '<span class="tkt-tchip solid">' + (it.direction === 'inbound' ? 'Entrante' : 'Saliente') + '</span>';
        }

        return out;
    }

    /**
     * Acciones por mensaje. "Reenviar" solo en los salientes: reenviar algo
     * que escribió el cliente no significa nada.
     */
    function threadMsgActions(it) {
        // Sin correo detrás no hay nada que reenviar ni ninguna fuente que
        // enseñar (nota interna, mensaje de widget, evento): en vez de pintar
        // botones que darían error al pulsarlos, no se pintan.
        if (!it.mail_id) return '';

        var out = '<span class="tkt-tmsg-actions">';

        if (it.direction === 'outbound') {
            out += '<button type="button" class="tkt-btn-icon tkt-tmsg-act" data-thread-resend="' + it.mail_id + '" title="Reenviar este correo"><i class="fa-solid fa-rotate-right"></i></button>';
        }

        out += '<button type="button" class="tkt-btn-icon tkt-tmsg-act" data-thread-source="' + it.mail_id + '" title="Ver original"><i class="fa-solid fa-code"></i></button>';

        return out + '</span>';
    }

    /**
     * Línea de traza bajo un mensaje saliente ("entregado 10:42:11"). Solo se
     * pinta cuando el envío consta entregado o aceptado: sin confirmación no
     * se afirma nada.
     */
    function threadDelivery(it) {
        if (!it.delivery) return '';

        return '<div class="tkt-tdelivery">' +
            '<i class="fa-solid fa-check-double"></i> ' +
            escapeHtml(it.delivery.label) + ' ' + escapeHtml(it.delivery.at) +
        '</div>';
    }

    /**
     * Chips de adjuntos con nombre y peso, enlazados al fichero real.
     *
     * Si el backend no pudo leer el tamaño (fichero purgado o disco caído)
     * llega sin él y el chip se pinta igual: saber que hubo un adjunto sigue
     * siendo información, aunque no se sepa cuánto pesaba.
     */
    function threadAttachments(it) {
        var files = it.attachments || [];
        if (!files.length) return '';

        // Antes: <a href target="_blank"> lisa y llana -- clic y se
        // descargaba/abría el fichero crudo, sin previsualización ni
        // metadatos en la propia app (mockup "ve-file-preview", modal 49).
        // data-att-index identifica CUÁL adjunto de it.attachments es, para
        // que el delegado de clic de renderThreadPane() pueda recuperar el
        // objeto completo (nombre/url/mime/tamaño) sin repetirlo en atributos.
        return '<div class="tkt-tatts"><span class="tkt-cap">Adjuntos</span>' +
            files.map(function (f, idx) {
                return '<span class="tkt-tatt" data-attachment-preview data-item-id="' + it.id + '" data-att-index="' + idx + '" title="' + escapeHtml(f.name) + '">' +
                    '<i class="fa-regular ' + attachmentIcon(f.name) + '"></i>' +
                    '<span class="n">' + escapeHtml(f.name) + '</span>' +
                    (f.size ? '<span class="s">' + escapeHtml(f.size) + '</span>' : '') +
                '</span>';
            }).join('') +
        '</div>';
    }

    /** Icono según extensión, como el mockup (PDF, imagen, hoja de cálculo…). */
    function attachmentIcon(name) {
        var ext = String(name).split('.').pop().toLowerCase();
        if (ext === 'pdf') return 'fa-file-pdf';
        if (['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'].indexOf(ext) !== -1) return 'fa-file-image';
        if (['xls', 'xlsx', 'csv'].indexOf(ext) !== -1) return 'fa-file-excel';
        if (['doc', 'docx'].indexOf(ext) !== -1) return 'fa-file-word';
        if (['zip', 'rar', '7z'].indexOf(ext) !== -1) return 'fa-file-zipper';
        return 'fa-file-lines';
    }

    function renderThreadPane(items) {
        var $p = $('#tkt-dpane-thread');
        var html = '';
        var lastDayKey = null;

        // Filtro del hilo (Todo/Solo cliente/Sin notas) — puramente
        // client-side sobre los datos ya cargados, sin refetch. Cada fila
        // lleva su tipo en data-thread-kind para poder ocultarla sin
        // volver a renderizar.
        html += '<div class="tkt-thread-bar">' +
            '<span class="tkt-cap">Conversación completa</span>' +
            '<div class="tkt-seg" id="tkt-thread-filter">' +
                '<button type="button" class="on" data-thread-filter="all">Todo</button>' +
                '<button type="button" data-thread-filter="customer">Solo cliente</button>' +
                '<button type="button" data-thread-filter="no-notes">Sin notas</button>' +
                '<button type="button" data-thread-filter="no-events">Sin eventos</button>' +
            '</div>' +
        '</div>';

        if (!items.length) {
            html += '<div class="tkt-empty-box">Este ticket todavía no tiene mensajes ni eventos.</div>';
        } else {
            items.forEach(function (it) {
                if (it.created_at && threadDayKey(it.created_at) !== lastDayKey) {
                    lastDayKey = threadDayKey(it.created_at);
                    html += '<div class="tkt-thread-day" data-thread-kind="day"><span>' + escapeHtml(threadDayLabel(it.created_at)) + '</span></div>';
                }
                if (it.type === 'message' && !it.is_internal) {
                    var mine = it.from_agent;
                    // Placeholder discreto cuando body es null/vacío y no es
                    // el caso ya cubierto de "adjunto sin texto" (ese llega
                    // aquí con body='(archivo adjunto)', ya no vacío) — antes
                    // se renderizaba una burbuja totalmente en blanco, sin
                    // ninguna pista de qué evento fue (visto en vivo en un
                    // mensaje de Sistema con body:null).
                    //
                    // it.is_html: antes esto era SIEMPRE escapeHtml(it.body),
                    // así que un mensaje con html_body real (cualquier correo
                    // entrante en HTML) salía con las etiquetas literales en
                    // pantalla en vez de renderizarse (detectado 3-sep-2026,
                    // TCK-2026-00093). TicketDetailDataController ya manda
                    // it.body purificado (mismo saneador que la "ficha
                    // completa") cuando is_html es true, así que aquí es
                    // seguro inyectarlo tal cual.
                    var bubbleHtml = it.body ? (it.is_html ? it.body : escapeHtml(it.body)) : '<em class="tkt-mute">(sin contenido)</em>';
                    // TranslateIncomingTicketMessage ya calcula translated_body/
                    // source_language_name para cada mensaje del cliente en un
                    // idioma distinto al del agente (ver TicketDetailDataController),
                    // pero este panel nunca lo mostraba -- el agente no se
                    // enteraba de que había una traducción disponible (detectado
                    // 3-sep-2026 probando el flujo real con un mensaje en inglés).
                    var translationHtml = it.translated_body ?
                        '<div class="tkt-tmsg-translation">' +
                            '<i class="fa-solid fa-language"></i> Traducido automáticamente del ' + escapeHtml(it.source_language_name || '') +
                            '<div class="tkt-tmsg-translation-text">' + escapeHtml(it.translated_body).replace(/\n/g, '<br>') + '</div>' +
                        '</div>' : '';
                    html += '<div class="tkt-tmsg" data-thread-kind="' + (mine ? 'agent' : 'customer') + '">' +
                        '<div class="tkt-tmsg-head">' +
                            '<span class="tkt-tmsg-avatar">' + initials(it.sender_name) + '</span>' +
                            '<span class="tkt-tmsg-who">' + escapeHtml(it.sender_name) + '</span>' +
                            threadChips(it) +
                            '<span class="tkt-tmsg-time">' + escapeHtml(it.time || it.created_at_human || '') + '</span>' +
                            threadMsgActions(it) +
                        '</div>' +
                        '<div class="tkt-tmsg-body">' + bubbleHtml + '</div>' +
                        translationHtml +
                        threadAttachments(it) +
                        threadDelivery(it) +
                    '</div>';
                } else if (it.is_internal) {
                    // Nota interna: mismo formato de tarjeta que el mensaje
                    // pero con borde marcado y el aviso de que el cliente no
                    // la ve — es la diferencia que hay que poder leer de un
                    // vistazo antes de escribir algo comprometido.
                    html += '<div class="tkt-tnote" data-thread-kind="note">' +
                        '<div class="tkt-tmsg-head">' +
                            '<span class="tkt-tnote-icon"><i class="fa-solid fa-lock"></i></span>' +
                            '<span class="tkt-tmsg-who">Nota interna · ' + escapeHtml(it.sender_name) + '</span>' +
                            '<span class="tkt-tchip warn">No visible al cliente</span>' +
                            '<span class="tkt-tmsg-time">' + escapeHtml(it.time || it.created_at_human || '') + '</span>' +
                        '</div>' +
                        '<div class="tkt-tmsg-body">' + escapeHtml(it.body || '') + '</div>' +
                        threadAttachments(it) +
                    '</div>';
                } else {
                    // title con el ISO-8601 completo (it.created_at) además
                    // del texto humanizado — sin esto, dos eventos reales y
                    // distintos separados por <1 min (p.ej. dos "Ticket
                    // closed" reales) son indistinguibles a simple vista
                    // porque ambos redondean a la misma etiqueta relativa
                    // ("hace 3 semanas").
                    html += '<div class="tkt-tevent" data-thread-kind="event">' +
                        '<span class="tkt-tevent-icon"><i class="fa-solid ' + (EVENT_ICONS[it.type] || 'fa-circle-info') + '"></i></span>' +
                        '<span class="tkt-tevent-text">' + escapeHtml(it.body || it.type) + '</span>' +
                        // El ISO completo en el title: dos eventos reales
                        // separados por <1 min redondean a la misma etiqueta
                        // relativa y serían indistinguibles.
                        '<span class="tkt-tevent-time" title="' + escapeHtml(it.created_at || '') + '">' + escapeHtml(it.time || it.created_at_human || '') + '</span>' +
                    '</div>';
                }
            });
        }

        // Composer del mockup: cuatro pestañas de modo, la franja del
        // borrador sugerido por IA, el textarea y la barra de herramientas.
        // Reusa los mismos endpoints que ya existían
        // (TicketMessagingController::storeMessage con adjuntos multipart,
        // TicketCannedReply para plantillas, MacroApplyController para
        // macros) — cambia la superficie, no el backend.
        html += composerHtml();

        $p.html(html);

        $('#tkt-thread-filter [data-thread-filter]').on('click', function () {
            var mode = $(this).data('thread-filter');
            $('#tkt-thread-filter button').removeClass('on');
            $(this).addClass('on');
            $p.find('[data-thread-kind]').each(function () {
                var kind = $(this).data('thread-kind');
                var visible = mode === 'all'
                    || (mode === 'customer' && kind === 'customer')
                    || (mode === 'no-notes' && kind !== 'note')
                    || (mode === 'no-events' && kind !== 'event');
                $(this).toggle(Boolean(visible));
            });

            // Un separador de día cuyo contenido entero quedó filtrado se
            // queda flotando sobre nada: se oculta también.
            $p.find('[data-thread-kind="day"]').each(function () {
                var $siblings = $(this).nextUntil('[data-thread-kind="day"]');
                $(this).toggle($siblings.filter(':visible').length > 0);
            });
        });

        // ── Acciones por mensaje del hilo ──
        // Reenviar pide confirmación: manda un correo real al cliente y no hay
        // forma de retirarlo.
        $p.on('click', '[data-thread-resend]', function () {
            var mailId = $(this).data('thread-resend');

            // openConfirmModal, no window.confirm: el diálogo nativo ya se
            // retiró de esta pantalla en QA por romper el estilo y por dejar
            // el navegador bloqueado (ver el comentario de esa función).
            openConfirmModal({
                icon: 'fa-solid fa-rotate-right',
                title: 'Reenviar este correo',
                message: 'Se reenviará a su destinatario original. Es un correo real y no se puede retirar una vez enviado.',
                confirmLabel: 'Reenviar',
                onConfirm: function () {
                    // El CSRF lo añade el $.ajaxSetup global del layout, igual
                    // que en el resto de llamadas de este archivo.
                    $.ajax({
                        url: TKA.urls.mailResendTemplate.replace('__MAIL__', mailId),
                        method: 'POST',
                        headers: { Accept: 'application/json' },
                    }).done(function (res) {
                        var msg = (res && res.message) ? res.message : 'Correo reenviado.';
                        if (window.toastr) toastr.success(msg); else window.alert(msg);
                    }).fail(function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'No se pudo reenviar el correo.';
                        if (window.toastr) toastr.error(msg); else window.alert(msg);
                    });
                },
            });
        });

        // Ver original: abre el correo en la pestaña "Correo", que ya sabe
        // pintar cabeceras, cuerpo y fuente — en vez de duplicar ese visor.
        $p.on('click', '[data-thread-source]', function () {
            selectDetailTab('mail');
        });

        // Previsualizar adjunto (modal 49 del mockup) — reusa
        // openFilePreviewModal(), que ya existe para la pestaña Adjuntos
        // (renderFilesPane()) y que no tenía ningún punto de entrada desde
        // aquí, el hilo. Se adapta la forma del objeto (bytes en vez del
        // tamaño ya formateado que usa el chip del hilo, url_download en vez
        // de url) en vez de duplicar el modal con su propio CSS/markup.
        $p.on('click', '[data-attachment-preview]', function () {
            var itemId = $(this).data('item-id');
            var idx = $(this).data('att-index');
            var item = items.filter(function (it) { return it.id === itemId; })[0];
            var f = item && (item.attachments || [])[idx];
            if (!f) return;

            openFilePreviewModal(TKA.state.currentTicket, {
                name: f.name,
                url_download: f.url,
                size: f.bytes,
                source: item.from_agent ? 'agent' : 'customer',
                created_at_human: item.created_at_human || item.time,
            });
        });

        bindComposer($p);
    }

    // ── Composer del hilo ────────────────────────────────────────────
    // Cuatro modos excluyentes (respuesta / nota interna / plantillas /
    // traducir), la franja del borrador de IA y una barra de herramientas.
    // El modo vive en data-mode del contenedor, no en variables sueltas:
    // sendReply() lo lee para decidir si crea un mensaje público o una nota.

    function composerHtml() {
        return '<div class="tkt-composer" id="tkt-composer" data-mode="reply">' +
            '<div class="tkt-comp-tabs">' +
                '<button type="button" class="tkt-comp-tab on" data-comp-mode="reply"><i class="fa-solid fa-reply"></i> Respuesta</button>' +
                '<button type="button" class="tkt-comp-tab" data-comp-mode="note"><i class="fa-solid fa-lock"></i> Nota interna</button>' +
                '<button type="button" class="tkt-comp-tab" data-comp-act="templates"><i class="fa-regular fa-file-lines"></i> Plantillas</button>' +
                '<button type="button" class="tkt-comp-tab" data-comp-act="translate"><i class="fa-solid fa-language"></i> Traducir</button>' +
                '<span class="tkt-comp-lang" id="tkt-comp-lang" hidden><i class="fa-solid fa-language"></i> <span></span></span>' +
            '</div>' +
            '<div class="tkt-comp-ai" id="tkt-comp-ai" hidden>' +
                '<span class="tkt-comp-ai-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span>' +
                '<div class="tkt-comp-ai-main">' +
                    '<div class="tkt-comp-ai-head">' +
                        '<span class="tkt-comp-ai-title">Borrador sugerido</span>' +
                        '<span class="tkt-comp-ai-chip" id="tkt-comp-ai-tpl" hidden></span>' +
                        '<span class="tkt-comp-ai-chip" id="tkt-comp-ai-conf" hidden></span>' +
                    '</div>' +
                    '<div class="tkt-comp-ai-text" id="tkt-comp-ai-text"></div>' +
                '</div>' +
                '<span class="tkt-comp-ai-actions">' +
                    '<button type="button" class="tkt-comp-ai-use" id="tkt-comp-ai-use">Usar</button>' +
                    '<button type="button" class="tkt-comp-ai-edit" id="tkt-comp-ai-edit">Editar</button>' +
                    '<button type="button" class="tkt-comp-ai-drop" id="tkt-comp-ai-drop" aria-label="Descartar el borrador sugerido"><i class="fa-solid fa-xmark"></i></button>' +
                '</span>' +
            '</div>' +
            '<textarea id="tkt-reply-body" class="tkt-comp-body" rows="3" placeholder="Escribe tu respuesta… (/ para respuestas rápidas, @ para mencionar)" aria-label="Cuerpo de la respuesta o nota interna"></textarea>' +
            '<div class="tkt-comp-tools">' +
                '<label class="tkt-comp-tool" title="Adjuntar archivo"><i class="fa-solid fa-paperclip"></i> Adjuntar<input type="file" id="tkt-reply-attach" multiple hidden></label>' +
                '<span class="tkt-comp-attach-count" id="tkt-reply-attach-count"></span>' +
                '<button type="button" class="tkt-comp-tool" data-comp-act="templates"><i class="fa-regular fa-file-lines"></i> Plantilla</button>' +
                '<button type="button" class="tkt-comp-tool" data-comp-act="macros"><i class="fa-solid fa-bolt"></i> Macros</button>' +
                '<button type="button" class="tkt-comp-tool" data-comp-act="followup"><i class="fa-solid fa-list-ol"></i> Automático</button>' +
                '<button type="button" class="tkt-comp-tool" data-comp-act="ai"><i class="fa-solid fa-wand-magic-sparkles"></i> IA</button>' +
                '<button type="button" class="tkt-comp-tool" data-comp-act="translate"><i class="fa-solid fa-language"></i> Traducir</button>' +
                '<button type="button" class="tkt-comp-tool" data-comp-act="mention"><i class="fa-solid fa-at"></i> Mencionar</button>' +
                '<span class="tkt-comp-send-group">' +
                    '<button type="button" class="tkt-comp-schedule" data-comp-act="schedule"><i class="fa-regular fa-clock"></i> Programar</button>' +
                    '<button type="button" class="tkt-comp-send" id="tkt-reply-send"><i class="fa-solid fa-paper-plane"></i> Enviar <span class="tkt-comp-kbd">' + sendShortcutLabel() + '</span></button>' +
                '</span>' +
            '</div>' +
        '</div>';
    }

    // ⌘↵ en Mac, Ctrl+↵ en el resto. El mockup se dibujó en Mac y muestra
    // ⌘↵ fijo; anunciar un atajo que no existe en Windows es peor que
    // apartarse del mockup en dos caracteres.
    function sendShortcutLabel() {
        return /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent) ? '⌘↵' : 'Ctrl+↵';
    }

    function composerIsNote() {
        return $('#tkt-composer').attr('data-mode') === 'note';
    }

    function bindComposer($p) {
        var $c = $('#tkt-composer');
        var $body = $('#tkt-reply-body');

        $c.on('click', '[data-comp-mode]', function () {
            var mode = $(this).data('comp-mode');
            $c.attr('data-mode', mode).toggleClass('is-note', mode === 'note');
            $c.find('[data-comp-mode]').removeClass('on');
            $(this).addClass('on');
            // El botón dice lo que va a pasar de verdad: una nota interna no
            // se "envía" a nadie.
            $('#tkt-reply-send').html(mode === 'note'
                ? '<i class="fa-solid fa-lock"></i> Guardar nota'
                : '<i class="fa-solid fa-paper-plane"></i> Enviar <span class="tkt-comp-kbd">' + sendShortcutLabel() + '</span>');
            $body.attr('placeholder', mode === 'note'
                ? 'Nota interna: el cliente no la verá… (@ para mencionar)'
                : 'Escribe tu respuesta… (/ para respuestas rápidas, @ para mencionar)');
        });

        $c.on('click', '[data-comp-act]', function () {
            var t = TKA.state.currentTicket;
            switch ($(this).data('comp-act')) {
                case 'templates': openTemplatesModal(t); break;
                case 'macros': openMacrosModal(t); break;
                case 'followup': openFollowupModal(t); break;
                case 'ai': openAiDraftModal(t); break;
                case 'translate': openTranslateModal(t, false); break;
                case 'mention': insertAtCursor($body, '@'); $body.trigger('focus').trigger('input'); break;
                case 'schedule': openScheduleModal(t); break;
            }
        });

        $('#tkt-reply-attach').on('change', function () {
            var n = this.files.length;
            $('#tkt-reply-attach-count').text(n ? n + ' archivo(s)' : '');
        });

        $('#tkt-reply-send').on('click', sendReply);

        var typingTimer;
        $body.on('input', function () {
            autoResizeTextarea(this);
            if (composerIsNote()) return; // una nota interna no es "está escribiendo"
            clearTimeout(typingTimer);
            emitTyping(true);
            typingTimer = setTimeout(function () { emitTyping(false); }, 2500);
        });

        // ⌘/Ctrl+Enter envía. Enter a secas hace salto de línea: una
        // respuesta a un cliente casi nunca es de una sola línea.
        $body.on('keydown', function (ev) {
            if (ev.key === 'Enter' && (ev.metaKey || ev.ctrlKey)) {
                ev.preventDefault();
                sendReply();
            }
        });

        bindComposerAi($c, $body);
        requestAiDraft(TKA.state.currentTicket, false);
    }

    // Las macros no tienen modal propio: se despliegan en un <select> nativo
    // que se crea al vuelo junto al botón. Reusa loadMacrosInto/applyMacro,
    // que ya hablan con MacroApplyController.
    function openComposerMacros($btn) {
        var $existing = $('#tkt-comp-macro');
        if ($existing.length) { $existing.remove(); return; }
        var $sel = $('<select id="tkt-comp-macro" class="tkt-fselect tkt-comp-macro-select" aria-label="Aplicar macro"><option value="">/ macros…</option></select>');
        $btn.after($sel);
        loadMacrosInto($sel);
        $sel.on('change', function () {
            var macroId = $(this).val();
            if (!macroId) return;
            applyMacro(TKA.state.currentTicket, macroId);
            $sel.remove();
        });
    }

    function insertAtCursor($el, text) {
        var el = $el[0];
        var start = el.selectionStart || 0;
        var end = el.selectionEnd || 0;
        el.value = el.value.slice(0, start) + text + el.value.slice(end);
        el.selectionStart = el.selectionEnd = start + text.length;
    }

    function bindComposerAi($c, $body) {
        // "Usar" reemplaza el borrador; "Editar" lo pega y deja el foco
        // dentro para retocarlo. Ninguno de los dos envía nada.
        $('#tkt-comp-ai-use').on('click', function () {
            $body.val(TKA.state.aiDraft || '');
            autoResizeTextarea($body[0]);
            $('#tkt-comp-ai').attr('hidden', true);
        });
        $('#tkt-comp-ai-edit').on('click', function () {
            $body.val(TKA.state.aiDraft || '');
            autoResizeTextarea($body[0]);
            $('#tkt-comp-ai').attr('hidden', true);
            $body.trigger('focus');
        });
        $('#tkt-comp-ai-drop').on('click', function () {
            $('#tkt-comp-ai').attr('hidden', true);
        });
    }

    // Borrador sugerido real (TicketAiSuggestionController::suggestReply →
    // TicketReplySuggestionService). Sin agente de IA configurado el endpoint
    // responde 200 con suggestion:null y aquí simplemente no se muestra nada:
    // la franja es un extra, no puede romper el composer.
    function requestAiDraft(t, refresh) {
        if (!t || !t.url_ai_suggest_reply) return;
        var $box = $('#tkt-comp-ai');
        if (refresh) {
            $box.removeAttr('hidden');
            $('#tkt-comp-ai-text').text('Generando un borrador…');
            $('#tkt-comp-ai-tpl, #tkt-comp-ai-conf').attr('hidden', true);
        }
        $.ajax({
            url: t.url_ai_suggest_reply,
            method: 'POST',
            data: { refresh: refresh ? 1 : 0 },
            headers: { Accept: 'application/json' },
        }).done(function (res) {
            var s = res && res.suggestion;
            if (!s || !s.draft) {
                $box.attr('hidden', true);
                if (refresh && window.toastr) toastr.info((res && res.message) || 'No hay ninguna sugerencia disponible.');
                return;
            }
            TKA.state.aiDraft = s.draft;
            $('#tkt-comp-ai-text').text(s.draft);
            if (s.template && s.template.name) {
                $('#tkt-comp-ai-tpl').text(s.template.kind + ' ' + s.template.name).removeAttr('hidden');
            }
            if (typeof s.confidence === 'number') {
                $('#tkt-comp-ai-conf').text('confianza ' + Math.round(s.confidence * 100) + ' %').removeAttr('hidden');
            }
            if (s.language) {
                $('#tkt-comp-lang').removeAttr('hidden').find('span').text('responde en ' + String(s.language).toUpperCase());
            }
            $box.removeAttr('hidden');
        }).fail(function () {
            $box.attr('hidden', true);
            if (refresh && window.toastr) toastr.error('No se pudo generar el borrador.');
        });
    }

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
            width: 'sm',
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

    // ── Modal 24: Macros y atajos ────────────────────────────
    // El desplegable del composer solo enseña el nombre. Una macro que cierra
    // el ticket y otra que solo responde se leen igual desde ahí, y aplicarla
    // es irreversible en la práctica (manda un correo, cambia el estado). Este
    // modal enseña qué hace cada una ANTES de ejecutarla.

    function openMacrosModal(t) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-bolt',
            kicker: 'Redactor · macros',
            titleChip: t.ticket_number,
            title: 'Macros y atajos',
            width: 'sm',
            body: '<div class="tkt-field"><input type="search" class="tkt-input" id="tkt-macro-search" placeholder="Buscar macro…" aria-label="Buscar macro"></div>' +
                  '<div id="tkt-macro-list"><div class="tkt-empty-box">Cargando…</div></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-macro-apply" disabled>Aplicar macro</button>' +
                  '<a href="' + TKA.urls.macrosIndex + '" class="tkt-btn tkt-link-plain">Editar macros</a>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        loadMacros(function (macros) {
            if (!macros.length) {
                $backdrop.find('#tkt-macro-list').html('<div class="tkt-empty-box">No hay macros disponibles todavía.</div>');

                return;
            }

            $backdrop.find('#tkt-macro-list').html(macros.map(function (m) {
                var effects = (m.effects || []).map(function (e) {
                    return '<li>' + escapeHtml(e) + '</li>';
                }).join('');

                return '<label class="tkt-option tkt-macro-row" data-macro-name="' + escapeHtml((m.name || '').toLowerCase()) + '">' +
                    '<input type="radio" name="tkt-macro-pick" value="' + m.id + '">' +
                    '<span class="tkt-option-body">' +
                        '<span class="tkt-option-title">' + escapeHtml(m.name) + '</span>' +
                        (m.description ? '<span class="tkt-option-sub">' + escapeHtml(m.description) + '</span>' : '') +
                        (effects ? '<ul class="tkt-macro-effects">' + effects + '</ul>' : '') +
                    '</span>' +
                '</label>';
            }).join(''));
        });

        $backdrop.on('change', '[name="tkt-macro-pick"]', function () {
            $backdrop.find('.tkt-option').removeClass('on');
            $(this).closest('.tkt-option').addClass('on');
            $backdrop.find('#tkt-macro-apply').prop('disabled', false);
        });

        $backdrop.on('input', '#tkt-macro-search', function () {
            var term = this.value.trim().toLowerCase();
            $backdrop.find('.tkt-macro-row').each(function () {
                $(this).toggle(!term || String($(this).data('macro-name')).indexOf(term) > -1);
            });
        });

        $backdrop.on('click', '#tkt-macro-apply', function () {
            var id = $backdrop.find('[name="tkt-macro-pick"]:checked').val();
            if (!id) return;
            closeModal();
            applyMacro(t, id);
        });
    }

    // ── Modal 46: Posible duplicado ──────────────────────────
    // El mockup lo dispara al crear un ticket. Aquí se dispara al abrirlo,
    // que es donde esta pantalla puede actuar: al crear todavía no hay nada
    // que fusionar ni que enlazar. Las dos salidas son las del mockup:
    // unificar (fusionar en el existente) o tratarlos como independientes.

    function openDuplicateModal(t, candidates, windowDays) {
        var rows = candidates.map(function (c) {
            return '<label class="tkt-option">' +
                '<input type="radio" name="tkt-dupe-pick" value="' + c.id + '">' +
                '<span class="tkt-option-body">' +
                    '<span class="tkt-option-title">' + escapeHtml(c.ticket_number) + ' · ' + escapeHtml(c.subject || '(sin asunto)') + '</span>' +
                    '<span class="tkt-option-sub">' + escapeHtml(c.status || '—') +
                        ' · coincidencia de asunto ' + Math.round(c.similarity * 100) + ' %' +
                        (c.same_customer ? ' · mismo cliente' : '') +
                    '</span>' +
                '</span>' +
            '</label>';
        }).join('');

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-clone',
            kicker: 'Creación · duplicados',
            titleChip: t.ticket_number,
            title: 'Posible duplicado al crear',
            width: 'sm',
            body: '<div class="tkt-cap">Ya existe un ticket similar</div>' +
                '<div class="tkt-note">Este cliente tiene ' + (candidates.length === 1 ? 'otro ticket abierto' : 'otros tickets abiertos') +
                    ' con un asunto muy parecido dentro de la ventana de detección.</div>' +
                rows +
                '<div class="tkt-kv-grid">' +
                    '<span>ventana</span><span>' + (windowDays ? 'últimos ' + windowDays + ' días' : '—') + '</span>' +
                    '<span>criterio</span><span>asunto y cliente</span>' +
                '</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-dupe-merge" disabled>Fusionar con el seleccionado</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-dupe-keep" disabled>Son independientes</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('change', '[name="tkt-dupe-pick"]', function () {
            $backdrop.find('.tkt-option').removeClass('on');
            $(this).closest('.tkt-option').addClass('on');
            $backdrop.find('#tkt-dupe-merge, #tkt-dupe-keep').prop('disabled', false);
        });

        // Fusionar es destructivo: se delega en el modal 20, que ya pide
        // confirmación explícita y explica qué se mueve y qué se cierra.
        $backdrop.on('click', '#tkt-dupe-merge', function () {
            var target = $backdrop.find('[name="tkt-dupe-pick"]:checked').val();
            closeModal();
            mergeTicketPrompt(t, target);
        });

        // "Son independientes": se deja constancia con un enlace 'related'
        // para que el aviso no vuelva a salir con los mismos dos tickets.
        $backdrop.on('click', '#tkt-dupe-keep', function () {
            var target = $backdrop.find('[name="tkt-dupe-pick"]:checked').val();
            $.ajax({
                url: t.url_link,
                method: 'POST',
                data: { linked_ticket_id: target, link_type: 'related' },
                headers: { Accept: 'application/json' },
            }).done(function () {
                if (window.toastr) toastr.success('Marcados como relacionados, no duplicados.');
                closeModal();
                dismissDuplicateBanner();
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo guardar la relación.';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            });
        });
    }

    function dismissDuplicateBanner() {
        $('#tkt-dupe-banner').remove();
    }

    // Aviso discreto sobre el detalle. Solo aparece si hay candidatos reales;
    // sin ellos no se pinta nada (nunca "0 duplicados").
    function checkDuplicates(t) {
        dismissDuplicateBanner();
        if (!t || !t.url_duplicates) return;

        $.getJSON(t.url_duplicates).done(function (res) {
            var list = (res && res.duplicates) || [];
            if (!list.length) return;

            var $banner = $('<div class="tkt-banner-warn" id="tkt-dupe-banner" role="status">' +
                '<i class="fa-solid fa-clone"></i>' +
                '<span class="tkt-banner-text">Este cliente tiene ' + (list.length === 1 ? 'otro ticket abierto' : list.length + ' tickets abiertos') + ' con un asunto muy parecido.</span>' +
                '<button type="button" class="tkt-btn tkt-btn-sm" id="tkt-dupe-open">Revisar duplicado</button>' +
                '<button type="button" class="tkt-btn-icon" id="tkt-dupe-close" aria-label="Descartar el aviso"><i class="fa-solid fa-xmark"></i></button>' +
            '</div>');

            $banner.find('#tkt-dupe-open').on('click', function () { openDuplicateModal(t, list, res.window_days); });
            $banner.find('#tkt-dupe-close').on('click', dismissDuplicateBanner);
            $('#tkt-collision-banner').after($banner);
        });
    }

    // ── Modal 40: Encuesta CSAT ──────────────────────────────
    // La valoración del cliente con el contexto que hace falta para leerla:
    // cuánto se tardó, cuántas veces se reabrió y cómo puntúa de normal ese
    // agente (una nota de 3 significa cosas distintas si su media es 4,8).

    var CSAT_LABELS = {
        1: 'Muy insatisfecho', 2: 'Insatisfecho', 3: 'Neutral',
        4: 'Satisfecho', 5: 'Muy satisfecho',
    };

    function csatStars(rating) {
        var out = '';
        for (var i = 1; i <= 5; i++) {
            out += '<i class="fa-solid fa-star tkt-csat-star' + (i <= rating ? ' on' : '') + '"></i>';
        }

        return out;
    }

    function openCsatModal(t) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-face-smile',
            kicker: 'Ticket · satisfacción',
            titleChip: t.ticket_number,
            title: 'Encuesta CSAT',
            width: 'sm',
            body: '<div class="tkt-empty-box">Cargando…</div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        $.getJSON(t.url_csat).done(function (res) {
            var c = res && res.csat;
            if (!c) { $backdrop.find('.tkt-modal-body').html('<div class="tkt-empty-box">No se pudo cargar la valoración.</div>'); return; }

            var html;
            if (c.rating) {
                html = '<div class="tkt-cap">Respuesta del cliente</div>' +
                    '<div class="tkt-csat-head">' +
                        '<span class="tkt-csat-score">' + c.rating + '</span>' +
                        '<span class="tkt-csat-main">' +
                            '<span class="tkt-csat-stars">' + csatStars(c.rating) + '</span>' +
                            '<span class="tkt-csat-label">' + escapeHtml(CSAT_LABELS[c.rating] || '') +
                                (c.rated_at_human ? ' · respondida el ' + escapeHtml(c.rated_at_human) : '') +
                            '</span>' +
                        '</span>' +
                    '</div>' +
                    (c.comment ? '<blockquote class="tkt-form-quote">' + escapeHtml(c.comment) + '</blockquote>' : '') +
                    (c.reason ? '<div class="tkt-kv"><span class="k">Motivo</span><span class="v">' + escapeHtml(c.reason) + '</span></div>' : '');
            } else {
                html = '<div class="tkt-empty-box">Este ticket todavía no tiene valoración.</div>' +
                    (c.can_resend ? '<button type="button" class="tkt-btn tkt-w-100" id="tkt-csat-resend">Reenviar encuesta de satisfacción</button>' : '');
            }

            // Contexto: se muestra siempre, también sin valoración — sirve
            // para decidir si merece la pena volver a pedirla.
            html += '<div class="tkt-kv-grid tkt-csat-meta">' +
                '<span>agente</span><span>' + escapeHtml(c.agent || '—') + '</span>' +
                '<span>tiempo de resolución</span><span>' + escapeHtml(c.resolution_human || '—') + '</span>' +
                '<span>reaperturas</span><span>' + c.reopenings + '</span>' +
                '<span>media del agente</span><span>' + (c.agent_average !== null ? c.agent_average + ' / 5' : 'sin valoraciones') + '</span>' +
            '</div>';

            $backdrop.find('.tkt-modal-body').html(html);
        }).fail(function () {
            $backdrop.find('.tkt-modal-body').html('<div class="tkt-empty-box">No se pudo cargar la valoración.</div>');
        });

        $backdrop.on('click', '#tkt-csat-resend', function () {
            var $btn = $(this).prop('disabled', true).text('Enviando…');
            $.ajax({
                url: t.url_send_csat,
                method: 'POST',
                headers: { Accept: 'application/json' },
            }).done(function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Encuesta reenviada.');
                closeModal();
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo reenviar la encuesta.';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
                $btn.prop('disabled', false).text('Reenviar encuesta de satisfacción');
            });
        });
    }

    // ── Modal 36: Cambiar estado ─────────────────────────────
    // El <select> del panel Gestión cambia el estado de golpe, sin decir qué
    // implica cada uno ni dejar constancia del motivo. Este modal describe la
    // consecuencia real de cada estado (el SLA que se pausa, la encuesta que
    // se dispara) y guarda la nota del cambio como nota interna.

    // Qué implica cada estado. Sale de la BD (helpdesk_ticket_statuses.
    // description) y no de un mapa fijo por slug: el catálogo es editable y
    // un texto codificado aquí mentiría en cuanto alguien añada un estado o
    // cambie lo que hace uno existente. Las banderas reales del estado
    // (pausa del SLA, cierre) se añaden detrás porque son la consecuencia
    // que de verdad cambia el comportamiento del ticket.
    function statusEffect(st) {
        var parts = [];
        if (st.description) parts.push(st.description);
        if (st.stops_sla) parts.push('pausa el reloj del SLA');
        if (st.is_closed) parts.push('no admite más respuestas del cliente');

        return parts.join(' · ');
    }

    function openChangeStatusModal(t) {
        var statuses = TKA.state.statuses || [];
        var options = statuses.map(function (st) {
            var effect = statusEffect(st);
            return '<label class="tkt-option' + (st.id === t.status_id ? ' on' : '') + '">' +
                '<input type="radio" name="tkt-state-pick" value="' + st.id + '"' + (st.id === t.status_id ? ' checked' : '') + '>' +
                '<span class="tkt-option-body">' +
                    '<span class="tkt-option-title">' + escapeHtml(st.name) + '</span>' +
                    (effect ? '<span class="tkt-option-sub">' + escapeHtml(effect) + '</span>' : '') +
                '</span>' +
            '</label>';
        }).join('');

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-arrow-right-arrow-left',
            kicker: 'Ticket · estado',
            titleChip: t.ticket_number,
            title: 'Cambiar estado',
            width: 'sm',
            body: options +
                '<div class="tkt-field"><label class="tkt-label" for="tkt-state-note">Nota del cambio <span class="hint">interna</span></label>' +
                    '<textarea class="tkt-input" id="tkt-state-note" rows="2" placeholder="Por qué cambia de estado…"></textarea></div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-state-notify"> Notificar al cliente por email</label>' +
                '<div class="tkt-note">El cambio se registra en la actividad con fecha, hora y agente.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-state-save">Guardar estado</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('change', '[name="tkt-state-pick"]', function () {
            $backdrop.find('.tkt-option').removeClass('on');
            $(this).closest('.tkt-option').addClass('on');
        });

        $backdrop.on('click', '#tkt-state-save', function () {
            var statusId = $backdrop.find('[name="tkt-state-pick"]:checked').val();
            if (!statusId) { if (window.toastr) toastr.error('Elige un estado'); return; }

            var note = ($backdrop.find('#tkt-state-note').val() || '').trim();
            var notify = $backdrop.find('#tkt-state-notify').is(':checked');
            var $btn = $(this).prop('disabled', true).text('Guardando…');

            $.ajax({
                url: t.url_update,
                method: 'POST',
                data: { _method: 'PUT', status_id: statusId, notify_customer: notify ? 1 : 0 },
                headers: { Accept: 'application/json' },
            }).done(function () {
                // La nota va después y en su propio endpoint. Se espera a que
                // termine ANTES de recargar: recargar con la petición en vuelo
                // la aborta y la nota se pierde sin avisar.
                function done() {
                    if (window.toastr) toastr.success('Estado actualizado');
                    closeModal();
                    // Mismo criterio que patchTicket(): recargar es lo único
                    // que refresca a la vez la fila, los contadores de los
                    // tabs y la cabecera del detalle.
                    window.location.reload();
                }

                if (!note || !TKA.urls.notesStoreTemplate) { done(); return; }

                // StoreTicketNoteRequest exige ticket_id además del cuerpo, y
                // el campo es 'body' (no 'content'): sin los dos, la nota se
                // rechazaba con un 422 que el .fail() de abajo silenciaba.
                $.ajax({
                    url: TKA.urls.notesStoreTemplate.replace('__TICKET__', t.id),
                    method: 'POST',
                    data: { ticket_id: t.id, body: note },
                    headers: { Accept: 'application/json' },
                }).fail(function () {
                    if (window.toastr) toastr.warning('El estado se guardó, pero no se pudo añadir la nota.');
                }).always(done);
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo cambiar el estado';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
                $btn.prop('disabled', false).text('Guardar estado');
            });
        });
    }

    // Macros reales (MacroApplyController) — mismo backend/patrón que ya
    // funciona en la ficha antigua show.blade.php: la lista se pide una vez
    // y se cachea; aplicar una ejecuta MacroExecutor en el servidor
    // (puede añadir una respuesta, cambiar estado/prioridad, etc. — lo que
    // la macro defina) y se refresca el detalle para reflejar el resultado
    // real en vez de adivinar qué cambió.
    // Las macros se piden una sola vez por sesión: la lista es la misma para
    // todo el listado y cambia solo cuando alguien las edita en ajustes.
    function loadMacros(cb) {
        if (!TKA.urls.macrosList) { cb([]); return; }
        if (TKA.state.macros) { cb(TKA.state.macros); return; }

        $.getJSON(TKA.urls.macrosList).done(function (res) {
            TKA.state.macros = (res && res.macros) || [];
            cb(TKA.state.macros);
        }).fail(function () { cb([]); });
    }

    function loadMacrosInto($select) {
        loadMacros(function (macros) { appendMacroOptions($select, macros); });
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
        var isInternal = composerIsNote();
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

    // El backend guarda la descripción ya redactada ("Estado cambiado de
    // 'Resuelto' a 'En Espera'"), así que el icono se deduce de esa frase.
    //
    // Se mira el estado DESTINO, no la frase entera: buscar "resuelto" en
    // toda la cadena marcaba en verde un cambio que precisamente SALE de
    // resuelto, porque el estado de origen también aparece en el texto.
    function activityStepType(description) {
        var d = String(description || '').toLowerCase();

        if (d.indexOf('creado') !== -1) return 'created';
        if (d.indexOf('elimin') !== -1 || d.indexOf('borrad') !== -1) return 'removed';
        if (d.indexOf('email') !== -1 || d.indexOf('correo') !== -1) return 'sent';
        if (d.indexOf('asign') !== -1) return 'assign';
        if (d.indexOf('priorid') !== -1) return 'priority';

        if (d.indexOf('estado') !== -1) {
            // Todo lo que va tras el último " a " es el estado al que se pasó.
            var destino = d.indexOf(' a ') !== -1 ? d.slice(d.lastIndexOf(' a ') + 3) : d;
            if (destino.indexOf('resuelt') !== -1) return 'resolved';
            if (destino.indexOf('cerrad') !== -1) return 'closed';
            return 'state';
        }

        return 'edited';
    }

    function renderActivityPane(activities, totalCount) {
        var $p = $('#tkt-dpane-activity');
        if (!activities || !activities.length) {
            $p.html('<div class="tkt-empty-box">Sin actividad registrada todavía.</div>');
            return;
        }

        var html = '<div class="tkt-pane-head">' +
                '<span class="t">Actividad del ticket</span>' +
                '<span class="s">cambios de estado, asignación y envíos</span>' +
                '<span class="n mono">' + (totalCount || activities.length) + ' eventos</span>' +
            '</div>' +
            '<div class="tkt-card pad">';

        activities.forEach(function (a, i) {
            // "por María García" / "Sistema" / "Cliente": el mockup atribuye
            // cada línea. causer_kind lo manda el backend, porque sin causer
            // registrado la entrada la escribió un proceso, no una persona.
            var actor = a.causer
                ? 'por ' + escapeHtml(a.causer)
                : (a.causer_kind === 'customer' ? 'Cliente' : 'Sistema');

            html += stepHtml({
                type: activityStepType(a.description),
                title: escapeHtml(a.description),
                detail: actor,
                time: a.created_at_human || '',
                iso: a.created_at,
                last: i === activities.length - 1,
            });
        });
        html += '</div>';

        // activity_total_count = total real sin el limit(20) del backend —
        // sin este aviso, el recorte a 20 entradas quedaba silencioso (un
        // ticket longevo con 32+ entradas no daba ninguna pista de que
        // faltaban por ver).
        if (totalCount && totalCount > activities.length) {
            html += '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Mostrando las ' + activities.length + ' más recientes de ' + totalCount + '.</div>';
        }
        // Pie del mockup: recuerda que esto es la bitácora de auditoría.
        html += '<div class="tkt-note"><i class="fa-solid fa-shield-halved"></i> Cada cambio queda registrado con usuario y fecha en la bitácora de auditoría.</div>';
        $p.html(html);
    }

    // Subida de adjuntos al ticket. Vivía dentro de renderFilesPane como
    // closure, así que solo la pestaña Adjuntos podía usarla; el botón
    // "Adjuntar" del panel de notas necesita exactamente lo mismo.
    function uploadTicketAttachments(t, fileList) {
        if (!t || !fileList || !fileList.length) return;

        // StoreTicketMessageRequest exige body (min:1) — no acepta adjuntar
        // sin ningún texto, así que se manda un texto mínimo en vez de
        // forzar un string vacío que el backend rechazaría.
        var formData = new FormData();
        formData.append('body', '(archivo adjunto)');
        formData.append('is_internal', 0);
        for (var i = 0; i < fileList.length; i++) formData.append('attachments[]', fileList[i]);

        $.ajax({
            url: t.url_message_store, method: 'POST', data: formData, processData: false, contentType: false,
            headers: { Accept: 'application/json' },
            success: function () {
                if (window.toastr) toastr.success('Archivo adjuntado');
                fetchDetailData(t);
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo adjuntar el archivo';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            },
        });
    }

    function renderFilesPane(files, t) {
        var $p = $('#tkt-dpane-files');
        files = files || [];
        var totalBytes = files.reduce(function (n, f) { return n + (f.size || 0); }, 0);

        var html = '<div class="tkt-pane-head">' +
                '<span class="t">Adjuntos del ticket</span>' +
                '<span class="s">archivos del hilo y del formulario</span>' +
                '<span class="n mono">' + (files.length
                    ? files.length + (files.length === 1 ? ' archivo' : ' archivos') + (totalBytes ? ' · ' + formatFileSize(totalBytes) : '')
                    : 'sin archivos') + '</span>' +
            '</div>';

        html += files.length
            ? '<div class="tkt-filelist">' + files.map(fileRowHtml).join('') + '</div>'
            : '<div class="tkt-empty-box">Este ticket no tiene archivos adjuntos.</div>';

        // Adjuntar aquí publica un TicketItem sin texto vía el mismo endpoint
        // que la caja de respuesta del Hilo (StoreTicketMessageRequest ya
        // acepta attachments[] sin exigir body).
        html += '<label class="tkt-dropzone" id="tkt-files-drop">' +
                '<i class="fa-solid fa-cloud-arrow-up"></i>' +
                '<span class="t">Arrastra archivos aquí</span>' +
                '<span class="s">o pulsa para seleccionar · máx. 10 MB por archivo</span>' +
                '<input type="file" id="tkt-files-attach-input" multiple hidden>' +
            '</label>';

        // Pie del mockup: el botón explícito (la zona de arrastre sola no se
        // lee como pulsable) y el aviso de retención.
        html += '<div class="tkt-pane-foot">' +
                '<span class="tkt-note-inline"><i class="fa-solid fa-circle-info"></i> Los adjuntos se purgan junto con el contenido del email según la retención configurada.</span>' +
                '<button type="button" class="tkt-btn" id="tkt-files-attach-btn"><i class="fa-solid fa-paperclip"></i> Adjuntar archivo</button>' +
            '</div>';

        $p.html(html);

        $p.find('#tkt-files-attach-btn').on('click', function () {
            $p.find('#tkt-files-attach-input').trigger('click');
        });

        $p.find('[data-preview]').on('click', function (ev) {
            ev.preventDefault();
            openFilePreviewModal(t, files[parseInt($(this).data('preview'), 10)]);
        });

        function upload(fileList) { uploadTicketAttachments(t, fileList); }

        $p.find('#tkt-files-attach-input').on('change', function () { upload(this.files); this.value = ''; });
        // Arrastrar y soltar, que es lo que promete el copy de la zona.
        $p.find('#tkt-files-drop')
            .on('dragover', function (e) { e.preventDefault(); $(this).addClass('over'); })
            .on('dragleave drop', function () { $(this).removeClass('over'); })
            .on('drop', function (e) { e.preventDefault(); upload(e.originalEvent.dataTransfer.files); });
    }

    function formatFileSize(bytes) {
        if (bytes === null || bytes === undefined) return '';
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    // Fila de archivo del mockup: icono en cuadro, nombre + origen, tamaño y
    // dos acciones (previsualizar y descargar).
    function fileRowHtml(f, index) {
        var origen = f.source === 'customer' ? 'enviado por el cliente' : 'enviado por un agente';
        var meta = [origen, f.created_at_human].filter(Boolean).join(' · ');
        var previsualizable = f.url_download && PREVIEWABLE.test(f.name || '');

        return '<div class="tkt-filerow">' +
            '<span class="ico"><i class="' + fileIconClass(f.name) + '"></i></span>' +
            '<span class="info">' +
                '<span class="n">' + escapeHtml(f.name) + '</span>' +
                '<span class="s">' + escapeHtml(meta) + '</span>' +
            '</span>' +
            '<span class="size mono">' + escapeHtml(f.size ? formatFileSize(f.size) : '—') + '</span>' +
            '<span class="acts">' +
                (previsualizable
                    ? '<button type="button" class="tkt-btn-icon sm" data-preview="' + index + '" title="Previsualizar" aria-label="Previsualizar ' + escapeHtml(f.name) + '"><i class="fa-regular fa-eye"></i></button>'
                    : '') +
                (f.url_download
                    ? '<a class="tkt-btn-icon sm" href="' + escapeHtml(f.url_download) + '" download title="Descargar" aria-label="Descargar ' + escapeHtml(f.name) + '"><i class="fa-solid fa-download"></i></a>'
                    : '') +
            '</span>' +
        '</div>';
    }

    // Tokens de destinatario del card "Destinatarios": una píldora por
    // dirección, separando por coma la cadena que guarda la columna.
    function recipientTokens(value) {
        var list = String(value || '').split(',').map(function (x) { return x.trim(); }).filter(Boolean);
        if (!list.length) return '<span class="tkt-mailrow-empty">—</span>';
        return list.map(function (addr) {
            return '<span class="tkt-token">' + escapeHtml(addr) + '</span>';
        }).join('');
    }

    function renderMailPane(mail) {
        var $p = $('#tkt-dpane-mail');
        if (!mail) {
            $p.html('<div class="tkt-empty-box">Este ticket no tiene correos asociados.</div>');
            return;
        }

        // Vista "Texto": el body_text real del envío. Si el correo no llevaba
        // parte text/plain se deriva del HTML, indicándolo, en vez de dejar
        // la pestaña vacía.
        var derivedText = String(mail.body_html || '').replace(/<[^>]+>/g, '').replace(/\s+\n/g, '\n').trim();
        var plainText = mail.body_text || derivedText;

        var recipientCount = ['to', 'cc', 'bcc'].reduce(function (n, k) {
            return n + String(mail[k] || '').split(',').filter(function (x) { return x.trim(); }).length;
        }, 0);

        // El mockup ofrece HTML · Texto · Fuente. "Fuente" solo aparece si
        // raw_email tiene el MIME archivado: sintetizar cabeceras que no se
        // guardaron sería inventar datos (mismo criterio que el resto de la
        // pantalla, que deja vacíos honestos en vez de rellenos falsos).
        var hasSource = !!mail.raw_email;

        // Chip "imágenes y links" del mockup: se calcula del HTML real del
        // correo (cuántas <img> y cuántos <a href> lleva), no es un adorno.
        // Es lo que un agente quiere saber antes de fiarse de un preview:
        // si el mensaje depende de recursos remotos.
        var probe = document.createElement('div');
        probe.innerHTML = mail.body_html || '';
        var imgCount = probe.querySelectorAll('img').length;
        var linkCount = probe.querySelectorAll('a[href]').length;
        var assetsChip = (imgCount || linkCount)
            ? '<span class="tkt-chip-assets"><i class="fa-solid fa-check"></i> ' +
                [imgCount ? imgCount + (imgCount === 1 ? ' imagen' : ' imágenes') : '',
                 linkCount ? linkCount + (linkCount === 1 ? ' link' : ' links') : '']
                .filter(Boolean).join(' · ') + '</span>'
            : '<span class="tkt-chip-assets plain">sin imágenes ni links</span>';

        // Chip de spam: la puntuación real del filtro del servidor entrante
        // (X-Spam-Score / X-Spam-Status). Si el correo no pasó por ningún
        // filtro, mail.spam llega null y el chip no se pinta — mejor que
        // enseñar un 0 que parecería "verificado y limpio".
        var spamChip = '';
        if (mail.spam) {
            var over = mail.spam.is_spam;
            spamChip = '<span class="tkt-chip-spam' + (over ? ' over' : '') + '" title="Umbral del filtro: ' +
                mail.spam.threshold + '">Spam ' + mail.spam.score + '/' + mail.spam.threshold + '</span>';
        }

        $p.html(
            '<div class="tkt-card">' +
                '<div class="tkt-card-head">' +
                    '<span class="tkt-cap">Destinatarios</span>' +
                    '<span class="tkt-pill-count">' + recipientCount + (recipientCount === 1 ? ' destinatario' : ' destinatarios') + '</span>' +
                    '<button type="button" class="tkt-link-btn" id="tkt-open-delivery">Detalle de entrega</button>' +
                    (mail.status === 'bounced' || mail.status === 'failed'
                        ? '<button type="button" class="tkt-link-btn strong" id="tkt-open-bounce">Ver rebote</button>' : '') +
                '</div>' +
                '<div class="tkt-mailrows">' +
                    '<div class="tkt-mailrow"><span class="k">De</span><span class="v"><span class="mono">' + escapeHtml(mail.from || '—') + '</span></span></div>' +
                    '<div class="tkt-mailrow"><span class="k">Para</span><span class="v">' + recipientTokens(mail.to) + '</span></div>' +
                    (mail.cc ? '<div class="tkt-mailrow"><span class="k">CC</span><span class="v">' + recipientTokens(mail.cc) + '</span></div>' : '') +
                    (mail.bcc ? '<div class="tkt-mailrow"><span class="k">CCO</span><span class="v">' + recipientTokens(mail.bcc) + '</span></div>' : '') +
                    '<div class="tkt-mailrow last"><span class="k">Asunto</span><span class="v">' + escapeHtml(mail.subject || '—') + '</span></div>' +
                '</div>' +
                (mail.message_id || mail.in_reply_to
                    ? '<details class="tkt-tech">' +
                        '<summary><i class="fa-solid fa-chevron-right"></i> Detalles técnicos<span class="mono">Message-ID · In-Reply-To</span></summary>' +
                        '<div class="tkt-tech-body">' +
                            (mail.message_id ? '<div class="tkt-tech-row"><span class="k">Message-ID</span><span class="v mono">' + escapeHtml(mail.message_id) + '</span><button type="button" class="tkt-copy" data-copy="' + escapeHtml(mail.message_id) + '" title="Copiar"><i class="fa-regular fa-copy"></i></button></div>' : '') +
                            (mail.in_reply_to ? '<div class="tkt-tech-row"><span class="k">In-Reply-To</span><span class="v mono">' + escapeHtml(mail.in_reply_to) + '</span></div>' : '') +
                        '</div>' +
                      '</details>'
                    : '') +
            '</div>' +
            '<div class="tkt-card">' +
                '<div class="tkt-card-head">' +
                    '<span class="tkt-cap">Cuerpo del mensaje</span>' +
                    assetsChip +
                    spamChip +
                    // Escritorio/Móvil: acota el ancho del preview a 380px
                    // para ver cómo le llega el correo al cliente en el
                    // teléfono, que es donde se abre la mayoría.
                    '<span class="tkt-seg-mini" role="group" aria-label="Ancho de previsualización">' +
                        '<button type="button" class="on" data-mailwidth="desk">Escritorio</button>' +
                        '<button type="button" data-mailwidth="mob">Móvil</button>' +
                    '</span>' +
                    '<span class="tkt-seg-mini" role="group" aria-label="Formato del cuerpo">' +
                        '<button type="button" class="on" data-mailview="html">HTML</button>' +
                        '<button type="button" data-mailview="text">Texto</button>' +
                        (hasSource ? '<button type="button" data-mailview="source">Fuente</button>' : '') +
                    '</span>' +
                '</div>' +
                '<div class="tkt-mail-body" id="tkt-mail-body-html">' + (mail.body_html || '<em>Sin contenido</em>') + '</div>' +
                '<pre class="tkt-mail-body raw" id="tkt-mail-body-text" hidden>' + escapeHtml(plainText || 'Sin contenido') + '</pre>' +
                (hasSource ? '<pre class="tkt-mail-body raw" id="tkt-mail-body-source" hidden>' + escapeHtml(mail.raw_email) + '</pre>' : '') +
            '</div>' +
            ((mail.attachments && mail.attachments.length)
                ? '<div class="tkt-att-strip"><span class="tkt-cap">Adjuntos · ' + mail.attachments.length + '</span>' +
                    mail.attachments.map(function (a) {
                        var name = a.name || a.filename || 'archivo';
                        return '<span class="tkt-att-pill"><i class="' + fileIconClass(name) + '"></i>' + escapeHtml(name) +
                            (a.size ? '<span class="mono">' + formatFileSize(a.size) + '</span>' : '') + '</span>';
                    }).join('') +
                  '</div>'
                : '')
        );

        $p.find('#tkt-open-delivery').on('click', function () {
            var d = TKA.state.currentDetail;
            openDeliveryModal(TKA.state.currentTicket, mail, d && d.trace);
        });
        $p.find('#tkt-open-bounce').on('click', function () {
            openBounceModal(TKA.state.currentTicket, mail);
        });

        $p.find('[data-mailview]').on('click', function () {
            var which = $(this).data('mailview');
            $p.find('[data-mailview]').removeClass('on');
            $(this).addClass('on');
            $('#tkt-mail-body-html').prop('hidden', which !== 'html');
            $('#tkt-mail-body-text').prop('hidden', which !== 'text');
            $('#tkt-mail-body-source').prop('hidden', which !== 'source');
        });

        $p.find('[data-mailwidth]').on('click', function () {
            var which = $(this).data('mailwidth');
            $p.find('[data-mailwidth]').removeClass('on');
            $(this).addClass('on');
            $p.find('.tkt-mail-body').toggleClass('mobile', which === 'mob');
        });

        $p.find('[data-copy]').on('click', function () {
            var value = $(this).data('copy');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(value).then(function () {
                    if (window.toastr) toastr.success('Message-ID copiado');
                });
            }
        });
    }

    var TRACE_LABELS = { queued: 'Encolado', sent: 'Aceptado por el servidor de correo', bounced: 'Rebotado', failed: 'Fallido', opened: 'Abierto por el destinatario', clicked: 'Enlace clicado por el destinatario' };

    // Icono y tono de cada hito de la línea de tiempo. Verde para lo que
    // salió bien, gris para lo informativo, negro para lo que falló.
    var TRACE_STEP = {
        queued:   { icon: 'fa-inbox',                tone: 'ok'   },
        // Tipos propios de la pestaña Actividad.
        created:  { icon: 'fa-plus',                 tone: 'ok'   },
        state:    { icon: 'fa-arrow-right-arrow-left', tone: 'mute' },
        resolved: { icon: 'fa-check',                tone: 'ok'   },
        closed:   { icon: 'fa-lock',                 tone: 'mute' },
        assign:   { icon: 'fa-user-pen',             tone: 'mute' },
        priority: { icon: 'fa-flag',                 tone: 'mute' },
        edited:   { icon: 'fa-pen',                  tone: 'mute' },
        removed:  { icon: 'fa-trash',                tone: 'bad'  },
        sent:     { icon: 'fa-paper-plane',          tone: 'ok'   },
        delivered:{ icon: 'fa-check',                tone: 'ok'   },
        opened:   { icon: 'fa-envelope-open',        tone: 'mute' },
        clicked:  { icon: 'fa-arrow-pointer',        tone: 'mute' },
        bounced:  { icon: 'fa-arrow-rotate-left',    tone: 'bad'  },
        failed:   { icon: 'fa-triangle-exclamation', tone: 'bad'  },
    };

    // Una fila de la línea de tiempo: círculo + línea vertical, texto
    // principal, detalle y hora a la derecha. La usan Traza y Actividad.
    function stepHtml(opts) {
        var step = TRACE_STEP[opts.type] || { icon: 'fa-circle', tone: 'mute' };
        return '<div class="tkt-step">' +
            '<div class="tkt-step-rail">' +
                '<span class="dot ' + step.tone + '"><i class="fa-solid ' + step.icon + '"></i></span>' +
                '<span class="line' + (opts.last ? ' last' : '') + '"></span>' +
            '</div>' +
            '<div class="tkt-step-body' + (opts.last ? ' last' : '') + '">' +
                '<span class="t">' + opts.title + '</span>' +
                (opts.detail ? '<span class="s">' + opts.detail + '</span>' : '') +
            '</div>' +
            '<span class="tkt-step-time mono"' + (opts.iso ? ' title="' + escapeHtml(opts.iso) + '"' : '') + '>' + escapeHtml(opts.time || '') + '</span>' +
        '</div>';
    }

    function renderTracePane(events, mail, meta) {
        var $p = $('#tkt-dpane-trace');
        if (!events || !events.length) {
            $p.html('<div class="tkt-empty-box">Sin trazabilidad de entrega: este ticket todavía no tiene ningún correo enviado.</div>');
            return;
        }

        var html = '<div class="tkt-pane-head">' +
                '<span class="t">Recorrido del último correo</span>' +
                '<span class="s">del encolado a la entrega</span>' +
                '<span class="n mono">' + events.length + (events.length === 1 ? ' evento' : ' eventos') + '</span>' +
            '</div>' +
            '<div class="tkt-card pad">';

        events.forEach(function (ev, i) {
            html += stepHtml({
                type: ev.type,
                // El backend ya manda el rótulo redactado ('Entregado al
                // destinatario'); TRACE_LABELS queda como respaldo para
                // eventos antiguos que no lo traigan.
                title: escapeHtml(ev.label || TRACE_LABELS[ev.type] || ev.type),
                detail: ev.detail ? escapeHtml(ev.detail) : '',
                time: ev.at_human || ev.at || '',
                iso: ev.at,
                last: i === events.length - 1,
            });
        });
        html += '</div>';

        // Pie del mockup: "Traza SMTP" e "Identificadores" en dos columnas.
        // El mockup enseña ahí relay/IP/TLS/reintentos; esta instalación no
        // guarda ninguno de esos datos (raw_headers está vacío en todo el
        // log y ticket_mails no tiene columnas de transporte), así que se
        // pinta solo lo que el backend pudo calcular de verdad — cada fila
        // sin dato se omite en vez de rellenarse con un ejemplo.
        var smtpRows = (meta && meta.smtp) || [];
        var idRows = (meta && meta.ids) || [];

        if (smtpRows.length || idRows.length) {
            html += '<div class="tkt-duo">';

            if (smtpRows.length) {
                html += '<div class="tkt-card"><div class="tkt-card-head"><span class="tkt-cap">Traza SMTP</span></div>' +
                    '<div class="tkt-side-rows">' +
                    smtpRows.map(function (r, i) {
                        return sideRow(r.k, r.v, { mono: true, last: i === smtpRows.length - 1 });
                    }).join('') +
                    '</div></div>';
            }

            if (idRows.length) {
                html += '<div class="tkt-card"><div class="tkt-card-head"><span class="tkt-cap">Identificadores</span></div>' +
                    '<div class="tkt-side-rows">' +
                    idRows.map(function (r, i) {
                        return sideRow(r.k, r.v, { mono: true, last: i === idRows.length - 1 });
                    }).join('') +
                    '</div></div>';
            }

            html += '</div>';
        } else if (mail) {
            html += '<div class="tkt-duo">' +
                '<div class="tkt-card"><div class="tkt-card-head"><span class="tkt-cap">Identificadores</span></div>' +
                    '<div class="tkt-side-rows">' +
                        sideRow('Message-ID', mail.message_id || '—', { mono: true }) +
                        sideRow('In-Reply-To', mail.in_reply_to || '—', { mono: true, last: true }) +
                    '</div></div>' +
                '<div class="tkt-card"><div class="tkt-card-head"><span class="tkt-cap">Entrega</span></div>' +
                    '<div class="tkt-side-rows">' +
                        sideRow('Estado', mail.status || '—', { mono: true }) +
                        sideRow('Enviado', mail.sent_at_human || '—', { mono: true }) +
                        sideRow('Entregado', mail.delivered_at_human || '—', { mono: true, last: true }) +
                    '</div></div>' +
            '</div>';
        }

        // "Ver mensaje original" del mockup: salta a la pestaña Correo, que
        // ya sabe pintar cabeceras y fuente — no se duplica ese visor aquí.
        if (mail) {
            html += '<button type="button" class="tkt-btn tkt-w-100" data-goto-dtab="mail">' +
                '<i class="fa-solid fa-code"></i> Ver mensaje original</button>';
        }

        $p.html(html);

        $p.find('[data-goto-dtab]').on('click', function () {
            $('[data-dtab="' + $(this).data('goto-dtab') + '"]').trigger('click');
        });
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
            // Los <select> de los chips de filtro van marcados data-no-select2:
            // están superpuestos transparentes sobre el chip (.tkt-fchip) para
            // clonar el mockup, y select2 los sustituye por su propio markup
            // visible, que rompería el chip por completo.
            if ($s.is('[data-no-select2]')) return;
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
            //
            // Bug real de QA (ago-2026): fuera de un modal caía a
            // document.body — TODO el CSS de arriba (.tkt .select2-dropdown,
            // .select2-results__option, etc.) está scopeado bajo .tkt como
            // ancestro, así que un panel colgado directo de <body> (fuera de
            // ese .tkt) no matcheaba NINGUNA de esas reglas: se veía como una
            // caja en blanco sin borde/texto (filtro "Origen" del listado,
            // entre otros). Anclarlo al .tkt más cercano lo mantiene dentro
            // del mismo scope de CSS.
            var $backdrop = $s.closest('.tkt-modal-backdrop');
            var $tktRoot = $s.closest('.tkt');
            $s.select2({
                width: 'style',
                minimumResultsForSearch: 6,
                dropdownAutoWidth: true,
                dropdownParent: $backdrop.length ? $backdrop : ($tktRoot.length ? $tktRoot : $(document.body)),
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
        if (which === 'correo') return renderCorreoSidePane($c, d.mail, t, d.side_conversations, d.followups, d.mails, d.last_outbound_mail);
        if (which === 'notas') return renderNotasPane($c, d.notes, t);
        if (which === 'tags') return renderTagsPane($c, t, d.ai_suggestion);
        if (which === 'files') return renderArchivosPane($c, d.files);
        if (which === 'tickets') return renderCustomerTicketsPane($c, d.customer_tickets, t);
        if (which === 'hist') return renderHistorialPane($c, d.activity, d.related);
    }

    // Pestaña "Tickets del cliente": el resto del histórico del mismo
    // contacto. Cada fila selecciona ese ticket en la lista si está en la
    // página actual; si no, navega al deep-link ?ticket=N.
    function renderCustomerTicketsPane($c, rows, t) {
        if (!t.customer) {
            $c.html('<div class="tkt-empty-box">Este ticket no tiene un cliente asociado, así que no hay histórico que mostrar.</div>');
            return;
        }

        var all = rows || [];
        var filtro = 'all';

        // El ticket actual también cuenta para el total del cliente: el
        // endpoint lo excluye de la lista (no tiene sentido enlazarse a sí
        // mismo), pero sí forma parte de su historial.
        var abiertos = all.filter(function (r) { return r.status_slug === 'open' || r.status_slug === 'progress' || r.status_slug === 'pending'; }).length;
        var cerrados = all.filter(function (r) { return r.status_slug === 'resolved' || r.status_slug === 'closed'; }).length;
        var esActualAbierto = t.status_slug === 'open' || t.status_slug === 'progress' || t.status_slug === 'pending';

        function lista() {
            var list = all.filter(function (r) {
                if (filtro === 'all') return true;
                var esAbierto = r.status_slug === 'open' || r.status_slug === 'progress' || r.status_slug === 'pending';
                return filtro === 'open' ? esAbierto : !esAbierto;
            });
            if (!list.length) return '<div class="tkt-empty-box">Sin tickets en este filtro.</div>';
            return list.map(function (r, i) {
                return '<button type="button" class="tkt-ctk" data-goto="' + r.id + '"' + (i === list.length - 1 ? ' data-last="1"' : '') + '>' +
                    '<span class="line1">' +
                        '<span class="tkt-ticket-id mono">' + escapeHtml(r.ticket_number) + '</span>' +
                        '<span class="subj">' + escapeHtml(r.subject || '(sin asunto)') + '</span>' +
                    '</span>' +
                    '<span class="line2">' +
                        '<span class="tkt-rchip">' + escapeHtml(r.status_name || r.status_slug) + '</span>' +
                        (r.priority && r.priority !== 'normal' && r.priority !== 'low'
                            ? '<span class="tkt-rchip strong">' + escapeHtml(priorityLabel(r.priority)) + '</span>' : '') +
                        '<span class="when">' + escapeHtml(r.created_at_human || '') + '</span>' +
                    '</span>' +
                '</button>';
            }).join('');
        }

        $c.html(
            '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">Tickets del cliente' +
                    '<span class="tkt-spacer tkt-side-tag">' + escapeHtml(t.customer.company || t.customer.name) + '</span></div>' +
                '<div class="tkt-side-card-body">' +
                    // Contadores del mockup, con el ticket actual incluido.
                    '<div class="tkt-stats three">' +
                        '<div class="tkt-stat"><span class="n">' + (all.length + 1) + '</span><span class="l">totales</span></div>' +
                        '<div class="tkt-stat"><span class="n">' + (abiertos + (esActualAbierto ? 1 : 0)) + '</span><span class="l">abiertos</span></div>' +
                        '<div class="tkt-stat"><span class="n">' + (cerrados + (esActualAbierto ? 0 : 1)) + '</span><span class="l">cerrados</span></div>' +
                    '</div>' +
                    '<div class="tkt-seg-tabs" id="tkt-ctk-tabs">' +
                        '<button type="button" class="on" data-cfilter="all">Todos</button>' +
                        '<button type="button" data-cfilter="open">Abiertos</button>' +
                        '<button type="button" data-cfilter="closed">Cerrados</button>' +
                    '</div>' +
                '</div>' +
                '<div class="tkt-side-card-body tight" id="tkt-ctk-list">' +
                    (all.length ? lista() : '<div class="tkt-empty-box">' + escapeHtml(t.customer.name) + ' no tiene ningún otro ticket.</div>') +
                '</div>' +
                // "Ver todos →" del mockup: el endpoint recorta a 20, así que
                // el resto del histórico se consulta en el listado filtrado
                // por este cliente. Solo se ofrece si hay algo que ver.
                // El listado no tiene filtro por customer_id — su único
                // filtro de contacto es la búsqueda libre, así que se enlaza
                // por el correo del cliente, que es lo que sí sabe casar.
                (all.length && t.customer.email
                    ? '<div class="tkt-side-card-body"><a class="tkt-btn" href="' + TKA.urls.index +
                        '?search=' + encodeURIComponent(t.customer.email) + '">Ver todos sus tickets →</a></div>'
                    : '') +
            '</div>'
        );

        $c.find('[data-cfilter]').on('click', function () {
            filtro = $(this).data('cfilter');
            $c.find('[data-cfilter]').removeClass('on');
            $(this).addClass('on');
            $c.find('#tkt-ctk-list').html(lista());
        });

        $c.find('[data-goto]').on('click', function () {
            var id = parseInt($(this).data('goto'), 10);
            var other = TKA.state.tickets.find(function (x) { return x.id === id; });
            if (other) selectTicket(other);
            else window.location = TKA.urls.index + '?ticket=' + id;
        });
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

        return '<div class="tkt-side-card"><div class="tkt-side-card-head">Satisfacción (CSAT)' +
            '<button type="button" class="tkt-btn-icon" id="tkt-open-csat" title="Ver la encuesta completa" aria-label="Ver la encuesta completa"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></button>' +
            '</div><div class="tkt-side-card-body">' + body + '</div></div>';
    }

    function renderGestionPane($c, t, d) {
        var csat = d && d.csat;
        var sla = d && d.sla;
        var asg = (d && d.assignment) || {};
        var agentName = t.assignee ? t.assignee.name : null;

        // Card "Asignado a": el mockup enseña avatar con iniciales, carga del
        // agente y presencia, no un <select> suelto.
        var assigneeCard =
            '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">Asignado a' +
                    (asg.group_name ? '<span class="tkt-spacer tkt-side-tag">' + escapeHtml(asg.group_name) + '</span>' : '') +
                '</div>' +
                '<div class="tkt-side-card-body">' +
                    '<button type="button" class="tkt-assignee" id="tkt-sg-assignee-wrap">' +
                        '<span class="av">' + escapeHtml(agentName ? initials(agentName) : '—') + '</span>' +
                        '<span class="who">' +
                            '<span class="n">' + escapeHtml(agentName || 'Sin asignar') + '</span>' +
                            '<span class="s">' + (agentName
                                ? 'Agente' + (asg.agent_open_tickets != null ? ' · ' + asg.agent_open_tickets + (asg.agent_open_tickets === 1 ? ' ticket abierto' : ' tickets abiertos') : '')
                                : 'Nadie lo está atendiendo') + '</span>' +
                        '</span>' +
                        (agentName ? '<span class="tkt-live"><span class="dot"></span>en línea</span>' : '') +
                        '<i class="fa-solid fa-chevron-right"></i>' +
                    '</button>' +
                    '<div class="tkt-side-rows">' +
                        sideRow('equipo', asg.group_name || '—', { mono: true }) +
                        sideRow('seguidores', (asg.watchers_count || 0) + ((asg.watchers_count || 0) === 1 ? ' agente' : ' agentes'), { mono: true }) +
                        sideRow('asignado', asg.assigned_at_human || '—', { mono: true, last: true }) +
                    '</div>' +
                    '<div class="tkt-side-duo">' +
                        '<button type="button" class="tkt-btn" id="tkt-act-reassign"><i class="fa-solid fa-user-pen"></i> Reasignar</button>' +
                        '<button type="button" class="tkt-btn" id="tkt-act-followers"><i class="fa-solid fa-users"></i> Seguidores</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        // Card SLA con barra de progreso. Solo si hay política aplicada.
        var slaCard = sla
            ? '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">SLA<span class="tkt-spacer"><button type="button" class="tkt-link-btn" id="tkt-act-slacal">Calendario</button></span></div>' +
                '<div class="tkt-side-card-body tight">' +
                    (sla.percent != null
                        ? '<div class="tkt-sla-bar-row' + (sla.state === 'breach' ? ' over' : '') + '">' +
                            '<span class="tkt-sla-bar"><span style="width:' + sla.percent + '%"></span></span>' +
                            '<span class="tkt-sla-pct mono' + (sla.state === 'breach' ? ' over' : '') + '">' + sla.percent + ' %</span>' +
                          '</div>'
                        : '') +
                    sideRow('Estado', sla.label, { strong: true }) +
                    sideRow('Resolución', sla.resolution, { strong: true }) +
                    sideRow('1ª respuesta', sla.first_response, { strong: true, last: true }) +
                '</div>' +
              '</div>'
            // Sin política aplicada la tarjeta desaparecía entera, y con ella
            // el único acceso al modal "Horario y SLA" — que es justo donde se
            // explica POR QUÉ este ticket no tiene plazos. Hoy ningún ticket
            // de esta instalación tiene sla_policy_id, así que el modal era
            // prácticamente inalcanzable.
            : '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">SLA<span class="tkt-spacer"><button type="button" class="tkt-link-btn" id="tkt-act-slacal">Calendario</button></span></div>' +
                '<div class="tkt-side-card-body">' +
                    '<div class="tkt-empty-box">Este ticket no tiene ninguna política de SLA aplicada, así que no se le calculan plazos.</div>' +
                '</div>' +
              '</div>';

        $c.html(
            '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">Gestión del ticket<span class="tkt-spacer">' + chip(t.status_name || STATUS_LABEL_FALLBACK[t.status_slug] || t.status_slug, statusChipClass(t.status_slug)) + '</span></div>' +
                // Etiqueta en versalitas ENCIMA del control, a ancho completo.
                '<div class="tkt-side-card-body">' +
                    '<div class="tkt-field"><label for="tkt-sg-state">Estado</label><select id="tkt-sg-state" data-field="status_id">' + optionsHtml(TKA.state.statuses, 'id', t.status_id) + '</select></div>' +
                    '<div class="tkt-field"><label for="tkt-sg-priority">Prioridad</label><select id="tkt-sg-priority" data-field="priority">' +
                        ['low', 'normal', 'high', 'urgent'].map(function (pr) { return '<option value="' + pr + '"' + (pr === t.priority ? ' selected' : '') + '>' + escapeHtml(priorityLabel(pr)) + '</option>'; }).join('') +
                    '</select></div>' +
                    '<div class="tkt-field"><label for="tkt-sg-category">Categoría</label><select id="tkt-sg-category" data-field="category_id"><option value="">—</option>' + optionsHtml(TKA.state.categories, 'id', t.category_id) + '</select></div>' +
                    '<div class="tkt-field"><label for="tkt-sg-group">Equipo</label><select id="tkt-sg-group" data-field="group_id"><option value="">—</option>' + optionsHtml(TKA.state.groups, 'id', t.group_id) + '</select></div>' +
                '</div>' +
            '</div>' +
            assigneeCard +
            '<div class="tkt-side-card" id="tkt-sg-actions">' +
                '<div class="tkt-side-card-head">Acciones</div>' +
                '<div class="tkt-side-card-body">' +
                    '<button type="button" class="tkt-btn tkt-btn-primary tkt-btn-start" id="tkt-act-reply"><i class="fa-solid fa-reply"></i> Responder al cliente</button>' +
                    '<button type="button" class="tkt-btn tkt-btn-start" data-lifecycle="resolve"><i class="fa-solid fa-circle-check ok"></i> Marcar como resuelto</button>' +
                    '<button type="button" class="tkt-btn tkt-btn-start" data-lifecycle="close"><i class="fa-solid fa-lock"></i> Cerrar ticket</button>' +
                    '<button type="button" class="tkt-btn tkt-btn-start" data-lifecycle="reopen"><i class="fa-solid fa-rotate-left"></i> Reabrir ticket</button>' +
                    '<button type="button" class="tkt-btn tkt-btn-start" id="tkt-act-snooze"><i class="fa-regular fa-clock"></i> Aplazar seguimiento</button>' +
                    '<button type="button" class="tkt-btn tkt-btn-start" id="tkt-act-merge"><i class="fa-solid fa-code-merge"></i> Fusionar duplicado</button>' +
                    '<button type="button" class="tkt-btn tkt-btn-start" id="tkt-act-split"><i class="fa-solid fa-scissors"></i> Dividir ticket</button>' +
                    '<button type="button" class="tkt-btn tkt-btn-start" id="tkt-act-portal"><i class="fa-regular fa-window-maximize"></i> Ver como el cliente</button>' +
                    '<button type="button" class="tkt-btn tkt-btn-danger tkt-btn-start" id="tkt-act-delete"><i class="fa-solid fa-trash"></i> Eliminar / Archivar</button>' +
                '</div>' +
            '</div>' +
            slaCard +
            renderCsatCard(csat)
        );

        // Tanto la ficha del agente como "Reasignar" abren el modal 37.
        $c.find('#tkt-sg-assignee-wrap, #tkt-act-reassign').on('click', function () { openAssignModal(t); });
        $c.find('#tkt-act-slacal').on('click', openSlaCalendarModal);

        $c.find('#tkt-open-csat').on('click', function () { openCsatModal(t); });
        $c.find('#tkt-act-csat-resend').on('click', function () {
            var $btn = $(this).prop('disabled', true).text('Enviando…');
            $.ajax({
                url: t.url_send_csat, method: 'POST', headers: { Accept: 'application/json' },
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

        initSelect2($c);

        $c.find('[data-lifecycle]').on('click', function () {
            var action = $(this).data('lifecycle');
            if (action === 'close') { openCloseTicketModal(t); return; }
            runLifecycleAction(t, action);
        });

        $c.find('#tkt-act-reply').on('click', function () { openComposeModal(t); });
        $c.find('#tkt-act-split').on('click', function () { openSplitModal(t); });
        $c.find('#tkt-act-portal').on('click', function () { openPortalModal(t); });
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
    // `prefillTarget` opcional: id del ticket destino ya elegido (llega del
    // modal 46, donde el agente ya seleccionó cuál era el duplicado).
    // Buscador de ticket destino compartido por "Fusionar" y "Vincular".
    // Ambos modales pedían el ID numérico a mano con la ayuda "visible en la
    // URL al abrirlo": había que salir a otra pantalla, copiarlo y volver.
    // Escribe el id elegido en el input que se le pase, sin cambiar el
    // contrato de los endpoints (que siguen recibiendo un id).
    function bindTicketSearch($backdrop, opts) {
        var $input = $backdrop.find(opts.input);
        var $results = $backdrop.find(opts.results);
        var excludeId = opts.excludeId;
        var timer;

        if (!TKA.urls.ticketSearch) {
            $results.remove();
            return;
        }

        $input.on('input', function () {
            var q = String(this.value || '').trim();
            clearTimeout(timer);

            // Un id tecleado a mano sigue siendo válido: no se busca por él.
            if (q.length < 2 || /^\d+$/.test(q)) { $results.empty(); return; }

            timer = setTimeout(function () {
                $.getJSON(TKA.urls.ticketSearch, { q: q, exclude_id: excludeId }).done(function (res) {
                    var filas = (res && res.data) || [];
                    ultimaBusqueda = filas;
                    if (!filas.length) {
                        $results.html('<div class="tkt-empty-box">Ningún ticket coincide.</div>');
                        return;
                    }

                    $results.html(filas.map(function (f) {
                        return '<button type="button" class="tkt-pick" data-ticket-pick="' + f.id + '">' +
                            '<span class="who"><span class="n">' + escapeHtml(f.ticket_number) + ' · ' +
                                escapeHtml(f.subject || '(sin asunto)') + '</span>' +
                            '<span class="s">' + escapeHtml([f.customer_name, f.status_name, f.updated_at_human]
                                .filter(Boolean).join(' · ')) + '</span></span></button>';
                    }).join(''));
                });
            }, 250);
        });

        var ultimaBusqueda = [];

        $results.on('click', '[data-ticket-pick]', function () {
            var id = $(this).data('ticket-pick');
            var fila = ultimaBusqueda.find(function (f) { return String(f.id) === String(id); }) || null;
            $input.val(id);
            $results.html('<div class="tkt-note">Ticket destino <strong>' +
                escapeHtml(fila ? fila.ticket_number : '#' + id) + '</strong> seleccionado.</div>');
            if (opts.onPick) opts.onPick(id, fila);
        });
    }

    function mergeTicketPrompt(t, prefillTarget) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-code-merge',
            iconClass: 'danger',
            kicker: 'Tickets · fusión',
            titleChip: t.ticket_number,
            title: 'Fusionar tickets',
            width: 'sm',
            body: '' +
                '<div class="tkt-field"><label class="tkt-label">Ticket destino<span class="req">*</span><span class="hint">busca por número o asunto</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-merge-target" placeholder="Nº de ticket, asunto o ID…" value="' + (prefillTarget ? escapeHtml(String(prefillTarget)) : '') + '">' +
                    '<div class="tkt-pick-list sm" id="tkt-merge-results"></div></div>' +
                // Vista comparada: cuál se conserva y cuál desaparece. Sin
                // esto había que fiarse de un id suelto para una acción que
                // no se puede deshacer.
                '<div class="tkt-merge-pair" id="tkt-merge-pair">' +
                    '<div class="tkt-merge-side losing">' +
                        '<span class="tkt-cap">Se fusiona y cierra</span>' +
                        '<span class="n mono">' + escapeHtml(t.ticket_number) + '</span>' +
                        '<span class="s tkt-trunc">' + escapeHtml(t.subject || '(sin asunto)') + '</span>' +
                    '</div>' +
                    '<i class="fa-solid fa-arrow-right tkt-merge-arrow"></i>' +
                    '<div class="tkt-merge-side winning" id="tkt-merge-winner">' +
                        '<span class="tkt-cap">Se conserva</span>' +
                        '<span class="n mono">—</span>' +
                        '<span class="s">elige el ticket destino</span>' +
                    '</div>' +
                '</div>' +
                // El mockup ofrece "mover correos, adjuntos y notas" como una
                // opción; aquí siempre se mueve todo, porque el ticket origen
                // se cierra y lo que no se moviera se perdería. Se dice qué
                // pasa en vez de fingir una elección que no existe.
                '<div class="tkt-note danger">Se moverán al ticket destino los <strong>mensajes, correos, adjuntos, notas, comentarios, tiempos, seguidores y enlaces</strong> de ' + escapeHtml(t.ticket_number) + '. No se puede deshacer.</div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-merge-ack"> Entiendo que esta acción no se puede deshacer</label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-merge-confirm" disabled>Fusionar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        // Al elegir destino se pinta en la vista comparada, para que quede a
        // la vista qué ticket sobrevive antes de confirmar.
        bindTicketSearch($backdrop, {
            input: '#tkt-merge-target',
            results: '#tkt-merge-results',
            excludeId: t.id,
            onPick: function (id, fila) {
                $backdrop.find('#tkt-merge-winner').html(
                    '<span class="tkt-cap">Se conserva</span>' +
                    '<span class="n mono">' + escapeHtml((fila && fila.ticket_number) || ('#' + id)) + '</span>' +
                    '<span class="s tkt-trunc">' + escapeHtml((fila && fila.subject) || '') + '</span>'
                );
            },
        });

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

    // Modal "Aplazar seguimiento". Los atajos del mockup calculan la fecha
    // en el cliente y rellenan el mismo campo, así que el backend sigue
    // recibiendo solo snoozed_until (más pause_sla, que sí valida ahora).
    function snoozeTicket(t) {
        var pad = function (n) { return String(n).padStart(2, '0'); };
        var fmt = function (fecha) {
            return fecha.getFullYear() + '-' + pad(fecha.getMonth() + 1) + '-' + pad(fecha.getDate()) +
                'T' + pad(fecha.getHours()) + ':' + pad(fecha.getMinutes());
        };

        // "3 días hábiles": saltando sábados y domingos, que es lo que
        // significa hábil aquí. No se consultan festivos: el calendario
        // laboral vive en el módulo SLA y no viaja a esta pantalla.
        var habiles = function (dias) {
            var fecha = new Date();
            while (dias > 0) {
                fecha.setDate(fecha.getDate() + 1);
                if (fecha.getDay() !== 0 && fecha.getDay() !== 6) dias--;
            }
            fecha.setHours(9, 0, 0, 0);
            return fecha;
        };

        var manana = new Date();
        manana.setDate(manana.getDate() + 1);
        manana.setHours(9, 0, 0, 0);

        var proximaSemana = new Date();
        proximaSemana.setDate(proximaSemana.getDate() + 7);
        proximaSemana.setHours(9, 0, 0, 0);

        var atajos = [
            { label: 'Mañana', sub: 'a las 9:00', value: fmt(manana) },
            { label: 'En 3 días hábiles', sub: 'sin contar el fin de semana', value: fmt(habiles(3)) },
            { label: 'La semana que viene', sub: 'dentro de 7 días', value: fmt(proximaSemana) },
        ];

        var defaultValue = atajos[0].value;

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-clock',
            kicker: 'Ticket · aplazar',
            titleChip: t.ticket_number,
            title: 'Aplazar ticket',
            width: 'sm',
            body: '' +
                '<div class="tkt-field"><label class="tkt-label">Cuándo</label>' +
                    atajos.map(function (a, i) {
                        return '<label class="tkt-option' + (i === 0 ? ' on' : '') + '" data-snooze-shortcut="' + a.value + '">' +
                            '<input type="radio" name="tkt-snooze-when" ' + (i === 0 ? 'checked' : '') + ' class="tkt-m0">' +
                            '<span><span class="tkt-option-title">' + a.label + '</span>' +
                            '<br><span class="tkt-option-sub">' + a.sub + '</span></span></label>';
                    }).join('') +
                '</div>' +
                '<div class="tkt-field"><label class="tkt-label">Fecha exacta<span class="req">*</span></label>' +
                    '<input type="datetime-local" class="tkt-input" id="tkt-snooze-until" value="' + defaultValue + '"></div>' +
                // Un ticket aplazado tres días seguía consumiendo su plazo de
                // resolución y volvía marcado como vencido sin que nadie
                // pudiera haberlo trabajado.
                '<label class="tkt-check"><input type="checkbox" id="tkt-snooze-pause-sla" checked> ' +
                    'Pausar el SLA mientras está aplazado</label>' +
                '<div class="tkt-note">El ticket saldrá de las colas activas y volverá a aparecer automáticamente en esta fecha.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-snooze-confirm">Posponer</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('click', '[data-snooze-shortcut]', function () {
            $backdrop.find('[data-snooze-shortcut]').removeClass('on');
            $(this).addClass('on').find('input').prop('checked', true);
            $backdrop.find('#tkt-snooze-until').val($(this).data('snooze-shortcut'));
        });

        // Tocar la fecha a mano deselecciona el atajo: si no, la interfaz
        // afirmaría "Mañana" con otra fecha puesta.
        $backdrop.on('input', '#tkt-snooze-until', function () {
            $backdrop.find('[data-snooze-shortcut]').removeClass('on').find('input').prop('checked', false);
        });

        $backdrop.on('click', '#tkt-snooze-confirm', function () {
            var input = $('#tkt-snooze-until').val();
            if (!input) { if (window.toastr) toastr.error('Indica fecha y hora'); return; }

            $.ajax({
                url: t.url_snooze,
                method: 'POST',
                data: {
                    snoozed_until: input,
                    pause_sla: $backdrop.find('#tkt-snooze-pause-sla').is(':checked') ? 1 : 0,
                },
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

    // Referencia de pedido que el cliente escribió en el formulario, si la
    // hay. No existe columna 'order_id' en tickets: el dato vive dentro de
    // custom_fields, con nombre distinto según el formulario.
    function ticketOrderRef() {
        var d = TKA.state.currentDetail;
        var fields = (d && d.form && d.form.fields) || null;
        if (!fields) return null;

        var keys = ['order', 'pedido', 'order_id', 'order_reference', 'num_pedido', 'numero_pedido', 'id_order'];
        for (var i = 0; i < keys.length; i++) {
            var v = fields[keys[i]];
            if (v != null && String(v).trim() !== '') return String(v).trim();
        }
        return null;
    }

    function renderClientePane($c, customer, t) {
        if (!customer) {
            $c.html('<div class="tkt-empty-box">Este ticket no tiene un cliente asociado.</div>');
            return;
        }
        var badges = '';
        if (customer.tickets_count != null) badges += '<span class="tkt-tag">' + customer.tickets_count + ' tickets</span>';
        if (customer.avg_csat != null) badges += '<span class="tkt-tag">CSAT ' + customer.avg_csat + '</span>';
        // El mockup remata la fila con "Cuenta al día". Aquí el único estado
        // real de la ficha es si está vetada, así que se dice eso: bloqueada
        // cuando lo está, al día cuando no. No se inventa un estado de
        // crédito que esta app no conoce.
        badges += customer.is_banned
            ? '<span class="tkt-tag tkt-tag-warn">Cuenta bloqueada</span>'
            : '<span class="tkt-tag">Cuenta al día</span>';

        var html = '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">Cliente</div>' +
                '<div class="tkt-side-card-body">' +
                    (customer.url_c360
                        ? '<a href="' + customer.url_c360 + '" class="tkt-person tkt-plain-link"><span class="tkt-person-avatar">' + initials(customer.name) + '</span><span class="tkt-fill"><span style="display:block;font-size:12.5px;font-weight:700">' + escapeHtml(customer.name) + '</span><span style="display:block;font-size:11px;color:var(--tkt-text-mute)">' + escapeHtml(customer.company || 'Sin empresa') + (customer.customer_since_year ? ' · cliente desde ' + customer.customer_since_year : '') + '</span></span></a>'
                        : '<div class="tkt-person"><span class="tkt-person-avatar">' + initials(customer.name) + '</span><span style="flex:1;font-size:12.5px;font-weight:700">' + escapeHtml(customer.name) + '</span></div>') +
                    '<div class="tkt-kv"><span class="k">Email</span><span class="v mono">' + escapeHtml(customer.email || '—') + '</span></div>' +
                    '<div class="tkt-kv"><span class="k">Teléfono</span><span class="v mono">' + escapeHtml(customer.phone || '—') + '</span></div>' +
                    '<div class="tkt-kv"><span class="k">Idioma</span><span class="v">' + escapeHtml(customer.language || '—') + '</span></div>' +
                    // "Cliente ID" del mockup: el identificador del cliente en
                    // PrestaShop o en el ERP, que ya resuelve
                    // CustomerSummaryService::summarize() y hasta ahora no se
                    // pintaba en ningún sitio de esta pantalla.
                    (customer.external_id ? '<div class="tkt-kv"><span class="k">Cliente ID</span><span class="v mono">' + escapeHtml(customer.external_id) + '</span></div>' : '') +
                    (badges ? '<div style="display:flex;flex-wrap:wrap;gap:5px">' + badges + '</div>' : '') +
                '</div>' +
            '</div>';

        // Identidades/fusión de duplicados — ContactsMergeController ya
        // existía (search/preview/execute) sin ningún punto de entrada
        // desde la pantalla de tickets, solo desde Contactos 360.
        if (TKA.urls.contactsMergeSearchTemplate) {
            html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Identidades</div><div class="tkt-side-card-body">' +
                '<button type="button" class="tkt-btn" id="tkt-open-c360"><i class="fa-regular fa-address-card"></i> Ficha 360 del cliente</button>' +
                '<button type="button" class="tkt-btn" id="tkt-open-identities"><i class="fa-solid fa-fingerprint"></i> Canales vinculados</button>' +
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
                // "Pedido #829575" del mockup: sale de los campos del propio
                // formulario cuando el cliente lo indicó; si no lo hay, la
                // fila no se pinta en vez de mostrar un guion.
                (ticketOrderRef() ? '<div class="tkt-kv"><span class="k">Pedido</span><span class="v mono">' + escapeHtml(ticketOrderRef()) + '</span></div>' : '') +
                '<div class="tkt-kv"><span class="k">Categoría</span><span class="v">' + escapeHtml(t.category_name || '—') + '</span></div>' +
            '</div></div>';
        }

        // Tarjeta "Integraciones" del mockup: en qué sistemas externos está
        // dado de alta este contacto y con qué id. Los datos ya los resolvía
        // CustomerSummaryService (vía Contactos 360) y solo se pintaban
        // apelotonados como chips sueltos junto a los contadores.
        var integraciones = customer.integrations || [];
        if (integraciones.length) {
            html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Integraciones</div><div class="tkt-side-card-body tight">';
            integraciones.forEach(function (i, idx) {
                html += '<div class="tkt-integration' + (idx === integraciones.length - 1 ? ' last' : '') + '">' +
                    '<span class="n">' + escapeHtml(i.label || i.platform || 'Integración') + '</span>' +
                    (i.externalId ? '<span class="s mono">' + escapeHtml(i.externalId) + '</span>' : '') +
                    '<span class="tkt-int-ok"><i class="fa-solid fa-circle-check"></i> OK</span>' +
                '</div>';
            });
            html += '</div></div>';
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

        $c.find('#tkt-open-c360').on('click', function () { openCustomer360Modal(t, customer); });
        $c.find('#tkt-open-identities').on('click', function () {
            openIdentitiesModal(t, TKA.state.currentDetail ? TKA.state.currentDetail.identities : []);
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
                                return '<div class="tkt-line tkt-pointer" data-loser-id="' + r.id + '" >' +
                                    '<span class="tkt-trunc tkt-fill">' + escapeHtml(r.name || r.email || ('#' + r.id)) + '</span>' +
                                    '<span class="mono tkt-meta-xs">' + escapeHtml(r.email || r.phone || '') + '</span>' +
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
            $c.html('<div class="tkt-empty-box">Este ticket no trae campos de formulario. Origen: <strong>' + escapeHtml(ORIGIN_LABELS[t.source] || t.source || '—') + '</strong>.</div>');
            return;
        }

        function kvRows(list) {
            return (list || []).map(function (r) {
                return '<div class="tkt-kv"><span class="k">' + escapeHtml(r.label) + '</span>' +
                    '<span class="v">' + escapeHtml(r.value) + '</span></div>';
            }).join('');
        }

        // El backend separa lo que rellenó el cliente de las claves técnicas
        // del envío; si no manda esa separación (payload antiguo), se cae al
        // volcado plano de siempre para no dejar la pestaña vacía.
        var submitted = form.submitted && form.submitted.length
            ? form.submitted
            : Object.keys(form.fields || {}).map(function (k) {
                return { label: k, value: String(form.fields[k]) };
            });

        // Badge con el origen real (widget/formulario/PrestaShop según lo
        // que ya reporta sourceSlug()) — antes la cabecera no distinguía de
        // dónde viene el formulario.
        var html = '';

        // Cita destacada: lo que el cliente escribió con sus palabras,
        // separado de los campos con valor de lista.
        if (form.message) {
            html += '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">Datos del formulario' +
                    '<span class="tkt-spacer">' + chip(ORIGIN_LABELS[t.source] || t.source, 'tkt-chip-muted') + '</span></div>' +
                '<div class="tkt-side-card-body">' +
                    '<blockquote class="tkt-form-quote">' + escapeHtml(form.message) + '</blockquote>' +
                '</div></div>';
        }

        html += '<div class="tkt-side-card"><div class="tkt-side-card-head">' +
                (form.message ? 'Campos enviados' : 'Datos del formulario') +
                (form.message
                    ? '<span class="tkt-spacer mono tkt-faint">' + submitted.length + '</span>'
                    : '<span class="tkt-spacer">' + chip(ORIGIN_LABELS[t.source] || t.source, 'tkt-chip-muted') + '</span>') +
            '</div>' +
            '<div class="tkt-side-card-body">' +
                (submitted.length ? kvRows(submitted) : '<div class="tkt-empty-box">Sin campos capturados.</div>') +
            '</div></div>';

        // "Trazabilidad del envío": página, IP, campaña y RGPD. Ninguno de
        // los formularios de esta instalación los guarda hoy, así que lo
        // habitual es que esta tarjeta no aparezca — se pinta solo si el
        // formulario capturó alguno, en vez de enseñar cuatro guiones.
        if (form.trace && form.trace.length) {
            html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Trazabilidad del envío</div>' +
                '<div class="tkt-side-card-body">' + kvRows(form.trace) + '</div></div>';
        }

        if (form.attachments && form.attachments.length) {
            html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Adjuntos del formulario' +
                    '<span class="tkt-spacer mono tkt-faint">' + form.attachments.length + '</span></div>' +
                '<div class="tkt-side-card-body tight">' +
                form.attachments.map(function (a) {
                    return '<a class="tkt-form-file" href="' + a.url_download + '">' +
                        '<i class="fa-regular fa-file"></i>' +
                        '<span class="n tkt-trunc">' + escapeHtml(a.name) + '</span>' +
                        '<span class="s mono">' + escapeHtml(a.size_human || '') + '</span></a>';
                }).join('') +
                '</div></div>';
        }

        html += '<div class="tkt-side-card"><div class="tkt-side-card-body">' +
                '<button type="button" class="tkt-btn" id="tkt-form-full"><i class="fa-regular fa-eye"></i> Ver completo</button>' +
                '<button type="button" class="tkt-btn" id="tkt-form-copy"><i class="fa-regular fa-clone"></i> Copiar datos</button>' +
            '</div></div>';

        $c.html(html);

        $c.find('#tkt-form-full').on('click', function () { openFormDataModal(t, form, submitted); });

        $c.find('#tkt-form-copy').on('click', function () {
            var texto = submitted.concat(form.trace || []).map(function (r) {
                return r.label + ': ' + r.value;
            }).join('\n');

            navigator.clipboard.writeText(texto).then(function () {
                if (window.toastr) toastr.success('Datos del formulario copiados');
            }).catch(function () {
                window.prompt('Copia los datos:', texto);
            });
        });
    }

    // "Ver completo" del panel de formulario: los mismos campos sin los
    // recortes que impone la columna estrecha del panel lateral.
    function openFormDataModal(t, form, submitted) {
        function tableRows(list) {
            return (list || []).map(function (r) {
                return '<tr><th>' + escapeHtml(r.label) + '</th><td>' + escapeHtml(r.value) + '</td></tr>';
            }).join('');
        }

        openModal(modalShell({
            icon: 'fa-regular fa-rectangle-list',
            kicker: 'PrestaShop · formulario',
            titleChip: t.ticket_number,
            title: 'Datos del formulario',
            width: 'md',
            body: (form.message ? '<blockquote class="tkt-form-quote">' + escapeHtml(form.message) + '</blockquote>' : '') +
                '<table class="tkt-info-table">' + tableRows(submitted) + '</table>' +
                (form.trace && form.trace.length
                    ? '<div class="tkt-cap tkt-cap-spaced">Trazabilidad del envío</div>' +
                      '<table class="tkt-info-table">' + tableRows(form.trace) + '</table>'
                    : ''),
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));
    }

    function renderCorreoSidePane($c, mail, t, sideConversations, followups, mails, lastOutboundMail) {
        var d = TKA.state.currentDetail;
        var customer = d && d.customer;
        var ai = d && d.ai_suggestion;
        var esFormulario = t.source === 'formulario' || t.source === 'web_form';

        // "Origen del correo" del mockup: distingue el correo de formulario
        // (campos ya mapeados) del general (texto libre al buzón).
        var html = '<div class="tkt-side-card">' +
            '<div class="tkt-side-card-head">Origen del correo' +
                '<span class="tkt-spacer tkt-side-tag">' + escapeHtml(ORIGIN_LABELS[t.source] || t.source || '—') + '</span></div>' +
            '<div class="tkt-side-card-body tight">' +
                '<div class="tkt-origin-opt' + (esFormulario ? ' on' : '') + '">' +
                    '<span class="n">Correo de formulario</span><span class="s">campos estructurados · PrestaShop</span></div>' +
                '<div class="tkt-origin-opt' + (esFormulario ? '' : ' on') + '">' +
                    '<span class="n">Correo general</span><span class="s">texto libre al buzón de soporte</span></div>' +
            '</div></div>';

        // "Idioma y traducción": el idioma del contacto es un dato real de la
        // ficha; si no lo tiene, se dice, en vez de inventar un porcentaje de
        // confianza como el del mockup.
        html += '<div class="tkt-side-card">' +
            '<div class="tkt-side-card-head">Idioma y traducción' +
                (customer && customer.language ? '<span class="tkt-spacer tkt-side-tag">' + escapeHtml(String(customer.language).toUpperCase()) + '</span>' : '') +
            '</div>' +
            '<div class="tkt-side-card-body tight">' +
                sideRow('escribe en', (customer && customer.language) ? customer.language : 'sin detectar', { mono: true }) +
                sideRow('responder en', 'su idioma', { mono: true, last: true }) +
            '</div>' +
            '<div class="tkt-side-card-body">' +
                // Estado REAL de la traducción automática. El mockup los
                // dibuja como interruptores del panel, pero son ajustes
                // globales del módulo HelpdeskTranslate: se muestran en solo
                // lectura y con enlace a su pantalla, porque cambiarlos
                // desde aquí los cambiaría para toda la empresa sin avisar.
                (d && d.translation
                    ? '<div class="tkt-toggle-row' + (d.translation.outgoing ? ' on' : '') + '">' +
                            '<i class="fa-solid ' + (d.translation.outgoing ? 'fa-toggle-on' : 'fa-toggle-off') + '"></i>' +
                            '<span class="n">Responder en el idioma del cliente</span>' +
                            '<span class="s">' + (d.translation.outgoing ? 'activo' : 'desactivado') + '</span>' +
                        '</div>' +
                        '<div class="tkt-toggle-row' + (d.translation.incoming ? ' on' : '') + '">' +
                            '<i class="fa-solid ' + (d.translation.incoming ? 'fa-toggle-on' : 'fa-toggle-off') + '"></i>' +
                            '<span class="n">Traducir los correos entrantes</span>' +
                            '<span class="s">' + (d.translation.incoming ? 'activo' : 'desactivado') + '</span>' +
                        '</div>' +
                        (d.translation.url_settings
                            ? '<a class="tkt-link-btn" href="' + d.translation.url_settings + '">Ajustes de traducción →</a>'
                            : '')
                    : '') +
                '<button type="button" class="tkt-btn" id="tkt-open-translate"><i class="fa-solid fa-language"></i> Traducir una respuesta</button>' +
            '</div></div>';

        // "Sugerido por IA": solo con una sugerencia real pendiente.
        if (ai && (ai.category || ai.priority)) {
            html += '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">Sugerido por IA' +
                    '<span class="tkt-spacer"><button type="button" class="tkt-link-btn" id="tkt-open-tagging-correo">Revisar</button></span></div>' +
                '<div class="tkt-side-card-body tight">' +
                    (ai.category ? sideRow('categoría', ai.category.name, { strong: true }) : '') +
                    (ai.priority ? sideRow('prioridad', priorityLabel(ai.priority), { strong: true, last: true }) : '') +
                '</div></div>';
        }

        html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Último correo del ticket</div><div class="tkt-side-card-body">';
        html += mail
            ? '<div class="tkt-kv"><span class="k">Estado</span><span class="v">' + escapeHtml(mail.status) + '</span></div>' +
              '<div class="tkt-kv"><span class="k">Enviado</span><span class="v">' + escapeHtml(mail.created_at_human) + '</span></div>' +
              '<div class="tkt-kv"><span class="k">Aperturas</span><span class="v">' + (mail.opens_count > 0 ? mail.opens_count + (mail.last_opened_human ? ' · última ' + escapeHtml(mail.last_opened_human) : '') : 'Sin abrir') + '</span></div>'
            : '<div class="tkt-empty-box">Sin correos asociados a este ticket.</div>';
        html += '</div></div>';
        html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Envíos</div><div class="tkt-side-card-body">' +
            '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-compose-mail"><i class="fa-regular fa-paper-plane"></i> Redactar email</button>' +
            '<button type="button" class="tkt-btn" id="tkt-mails-list-open"><i class="fa-regular fa-envelope-open"></i> Correos del ticket' + (mails ? ' <span class="mono tkt-faint">(' + mails.length + ')</span>' : '') + '</button>' +
            (mail && mail.url_resend
                ? '<button type="button" class="tkt-btn" id="tkt-resend-mail"><i class="fa-solid fa-rotate-right"></i> Reenviar último correo</button>'
                : '<button type="button" class="tkt-btn" disabled title="Sin correos que reenviar"><i class="fa-solid fa-rotate-right"></i> Reenviar último correo</button>') +
        '</div></div>';

        // Conversación paralela — hilo privado con un compañero o un
        // contacto externo, invisible para el cliente. Reusa
        // TicketSideConversationsController (store/addMessage), que ya
        // existía sin ninguna UI de agente que lo consumiera.
        var sideList = (sideConversations || []).map(function (s) {
            return '<div class="tkt-line tkt-pointer" data-side-id="' + s.id + '" >' +
                '<span class="tkt-trunc tkt-fill">' + escapeHtml(s.subject) + '</span>' +
                '<span class="tkt-chip tkt-chip-muted">' + escapeHtml(s.status) + '</span>' +
                '<span class="mono tkt-meta-xs">' + s.message_count + '</span>' +
            '</div>';
        }).join('');
        html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Conversación paralela<span class="tkt-spacer mono tkt-faint">' + (sideConversations ? sideConversations.length : 0) + '</span></div><div class="tkt-side-card-body">' +
            (sideList || '<div class="tkt-empty-box">Sin conversaciones paralelas todavía.</div>') +
            '<button type="button" class="tkt-btn" id="tkt-side-new"><i class="fa-solid fa-people-arrows"></i> Nueva conversación paralela</button>' +
        '</div></div>';

        // Seguimientos programados (TicketFollowup) — recordatorios propios
        // sobre este ticket, notificados por el comando programado
        // helpdesk:send-due-ticket-followups. Ya funcionaba en la ficha
        // antigua show.blade.php; aquí solo se porta la UI.
        var FOLLOWUP_STATE = {
            sent: { label: 'avisado', cls: 'ok' },
            cancelled: { label: 'cancelado', cls: 'muted' },
            pending: { label: 'pendiente', cls: '' },
        };

        var pasosPendientes = (followups || []).filter(function (f) { return f.state === 'pending'; }).length;

        var followupsList = (followups || []).map(function (f) {
            var estado = FOLLOWUP_STATE[f.state] || FOLLOWUP_STATE.pending;
            return '<div class="tkt-line tkt-followup' + (f.state !== 'pending' ? ' done' : '') + '" data-followup-id="' + f.id + '">' +
                (f.step ? '<span class="tkt-step-num sm">' + f.step + '</span>' : '') +
                '<span class="tkt-trunc tkt-fill">' + escapeHtml(f.scheduled_at_human || '') +
                    (f.note ? ' — ' + escapeHtml(f.note) : '') + '</span>' +
                '<span class="tkt-chip tkt-chip-' + (estado.cls || 'muted') + '">' + estado.label + '</span>' +
                // Un paso ya avisado o cancelado no se puede cancelar otra vez.
                (f.state === 'pending'
                    ? '<button type="button" class="tkt-btn-icon" data-followup-cancel="' + f.id + '" title="Cancelar"><i class="fa-solid fa-xmark"></i></button>'
                    : '') +
            '</div>';
        }).join('');

        html += '<div class="tkt-side-card"><div class="tkt-side-card-head">Seguimientos<span class="tkt-spacer mono tkt-faint">' + (followups ? followups.length : 0) + '</span></div><div class="tkt-side-card-body">' +
            (followupsList || '<div class="tkt-empty-box">Sin seguimientos programados.</div>') +
            // El aviso de "se detiene si responde" solo tiene sentido con
            // pasos vivos por delante.
            (pasosPendientes && followups.some(function (f) { return f.cancel_if_customer_replies; })
                ? '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> La secuencia se detiene sola si el cliente responde.</div>'
                : '') +
            '<button type="button" class="tkt-btn" id="tkt-followup-new"><i class="fa-regular fa-bell"></i> Programar seguimiento</button>' +
            (pasosPendientes > 1
                ? '<button type="button" class="tkt-btn" id="tkt-followup-cancel-all">Cancelar la secuencia</button>'
                : '') +
        '</div></div>';

        $c.html(html);

        $c.find('#tkt-followup-new').on('click', function () { openFollowupModal(t); });
        $c.find('#tkt-followup-cancel-all').on('click', function () {
            openConfirmModal({
                icon: 'fa-regular fa-bell-slash',
                title: 'Cancelar la secuencia',
                message: 'Se cancelarán todos los pasos que aún no se han avisado.',
                confirmLabel: 'Cancelar la secuencia',
                danger: true,
                onConfirm: function () {
                    $.ajax({
                        url: t.url_followups_destroy_all,
                        method: 'DELETE',
                        headers: { Accept: 'application/json' },
                        success: function (resp) {
                            if (window.toastr) toastr.success((resp && resp.message) || 'Secuencia cancelada');
                            fetchDetailData(t);
                        },
                        error: function () { if (window.toastr) toastr.error('No se pudo cancelar la secuencia'); },
                    });
                },
            });
        });
        $c.find('[data-followup-cancel]').on('click', function () { cancelFollowup(t, $(this).data('followup-cancel')); });

        // openResendMailModal necesita el ticket y el correo SALIENTE: 'mail'
        // es el más reciente sea cual sea su dirección, y reenviar uno
        // entrante lo mandaría a la propia bandeja de soporte.
        $c.find('#tkt-resend-mail').on('click', function () { openResendMailModal(t, lastOutboundMail || mail); });

        $c.find('#tkt-side-new').on('click', function () { newSideConversation(t); });
        $c.find('[data-side-id]').on('click', function () { addSideConversationMessage(t, $(this).data('side-id')); });
        $c.find('#tkt-compose-mail').on('click', function () { openComposeModal(t); });
        $c.find('#tkt-open-translate').on('click', function () {
            // El texto puede estar en el composer del hilo (lo habitual) o en
            // el modal de redactar. Antes solo miraba el segundo y mandaba a
            // abrir "Redactar email" aunque hubiera una respuesta escrita.
            if ($('#tkt-reply-body').val() && $('#tkt-reply-body').val().trim()) {
                openTranslateModal(t, false);
                return;
            }

            if (!composeDraft || composeDraft.ticketId !== t.id || !composeDraft.body) {
                openComposeModal(t);
                if (window.toastr) toastr.info('Escribe la respuesta y pulsa el icono de idioma para traducirla');
                return;
            }

            openTranslateModal(t, true);
        });
        $c.find('#tkt-open-tagging-correo').on('click', function () {
            openAiTaggingModal(t, TKA.state.currentDetail ? TKA.state.currentDetail.ai_suggestion : null);
        });
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
                // Qué recibe cada seguidor. Antes seguir un ticket no
                // mandaba ningún aviso, así que no había nada que elegir;
                // ahora NotifyTicketWatchers respeta estas dos casillas.
                return '<div class="tkt-follower">' +
                    '<div class="tkt-line"><span class="tkt-flex1">' + escapeHtml(w.name) +
                        (w.is_me ? ' <span class="tkt-chip tkt-chip-muted">tú</span>' : '') + '</span>' +
                        '<button type="button" class="tkt-btn-icon" data-follower-remove="' + w.user_id + '" title="Quitar"><i class="fa-solid fa-xmark"></i></button></div>' +
                    '<div class="tkt-follower-prefs">' +
                        '<label class="tkt-check sm"><input type="checkbox" data-follower-pref="replies" data-user="' + w.user_id + '"' +
                            (w.notify_customer_replies !== false ? ' checked' : '') + '> respuestas del cliente</label>' +
                        '<label class="tkt-check sm"><input type="checkbox" data-follower-pref="notes" data-user="' + w.user_id + '"' +
                            (w.notify_internal_notes !== false ? ' checked' : '') + '> notas internas</label>' +
                    '</div>' +
                '</div>';
            }).join('');
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-users',
            kicker: 'Ticket · seguidores',
            titleChip: t.ticket_number,
            title: 'Seguidores del ticket',
            width: 'sm',
            body: '<div id="tkt-followers-list">' + rowsHtml() + '</div>' +
                '<div class="tkt-field"><label class="tkt-label">Añadir compañero</label>' +
                    '<select class="tkt-select" id="tkt-follower-add"><option value="">Selecciona un agente…</option>' + optionsHtml(TKA.state.agentsFull, 'id', '') + '</select></div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        function refresh() {
            $backdrop.find('#tkt-followers-list').html(rowsHtml());
        }

        $backdrop.on('change', '[data-follower-pref]', function () {
            var userId = $(this).data('user');
            var $fila = $backdrop.find('[data-user="' + userId + '"]');
            var replies = $fila.filter('[data-follower-pref="replies"]').is(':checked');
            var notes = $fila.filter('[data-follower-pref="notes"]').is(':checked');

            $.ajax({
                url: t.url_watch,
                method: 'POST',
                data: {
                    user_id: userId,
                    notify_customer_replies: replies ? 1 : 0,
                    notify_internal_notes: notes ? 1 : 0,
                },
                headers: { Accept: 'application/json' },
                success: function () {
                    watchers.forEach(function (w) {
                        if (w.user_id !== userId) return;
                        w.notify_customer_replies = replies;
                        w.notify_internal_notes = notes;
                    });
                    if (d) d.watchers = watchers;
                    if (window.toastr) toastr.success('Preferencias de aviso guardadas');
                },
                error: function () {
                    if (window.toastr) toastr.error('No se pudieron guardar las preferencias');
                },
            });
        });

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
                '<label class="tkt-option on" data-delete-option="archive"><input type="radio" name="tkt-delete-mode" value="archive" checked class="tkt-m0">' +
                    '<span><span class="tkt-option-title">Archivar</span><br><span class="tkt-option-sub">Se oculta de las vistas activas, se puede restaurar</span></span></label>' +
                '<label class="tkt-option" data-delete-option="destroy"><input type="radio" name="tkt-delete-mode" value="destroy" class="tkt-m0">' +
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
            kicker: 'Operación · Cola',
            title: 'Cola y reintentos',
            width: 'md',
            body: '<div class="tkt-empty-box">Cargando…</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-queue-retry-all">Reintentar todos</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-queue-flush">Purgar dead-letters</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        function load() {
            $.getJSON(TKA.urls.ops).done(function (res) {
                var s = res && res.snapshot;
                if (!s) { $backdrop.find('.tkt-modal-body').html('<div class="tkt-empty-box">No se pudo cargar el estado de las colas.</div>'); return; }

                var queueRows = Object.keys(s.queues || {}).map(function (name) {
                    return '<div class="tkt-line"><span class="tkt-line-main">' + escapeHtml(name) + '</span><span class="mono">' + s.queues[name] + '</span></div>';
                }).join('') || '<div class="tkt-empty-box">Sin colas configuradas.</div>';

                // Jobs en dead-letter, uno a uno y reintentables por separado:
                // en un incidente casi nunca hay que reintentarlos todos, solo
                // los que fallaron por la causa ya resuelta.
                var failed = s.failed_jobs_sample || [];
                var failedRows = failed.length
                    ? failed.map(function (j) {
                        return '<div class="tkt-qjob">' +
                            '<div class="tkt-qjob-main">' +
                                '<div class="tkt-qjob-name">' + escapeHtml(j.job) + '<span class="tkt-chip-mono">' + escapeHtml(j.queue) + '</span></div>' +
                                '<div class="tkt-qjob-error" title="' + escapeHtml(j.error) + '">' + escapeHtml(j.error) + '</div>' +
                            '</div>' +
                            '<button type="button" class="tkt-btn tkt-btn-sm" data-queue-retry="' + escapeHtml(j.uuid) + '">Reintentar</button>' +
                        '</div>';
                    }).join('')
                    : '<div class="tkt-note ok">No hay jobs en dead-letter.</div>';

                var html = '' +
                    '<div class="tkt-kpi3">' +
                        '<div><div class="v">' + (s.queue_total ?? 0) + '</div><div class="l">En cola</div></div>' +
                        '<div><div class="v">' + (s.failed_jobs ?? '—') + '</div><div class="l">Dead-letter</div></div>' +
                        '<div><div class="v">' + (s.sla_breaches_last_hour ?? '—') + '</div><div class="l">SLA vencido (1h)</div></div>' +
                    '</div>' +
                    '<div class="tkt-field"><label class="tkt-label">Jobs fallidos</label>' + failedRows + '</div>' +
                    '<div class="tkt-field"><label class="tkt-label">Por cola</label>' + queueRows + '</div>' +
                    (s.unassigned_sla_warning > 0 ? '<div class="tkt-note warn">' + s.unassigned_sla_warning + ' ticket(s) sin asignar con SLA próximo a vencer.</div>' : '') +
                    '<div class="tkt-modal-note">Actualizado ' + escapeHtml(s.generated_at || '') + '</div>';

                $backdrop.find('.tkt-modal-body').html(html);
                $backdrop.find('#tkt-queue-retry-all, #tkt-queue-flush').prop('disabled', failed.length === 0);
            }).fail(function () {
                $backdrop.find('.tkt-modal-body').html('<div class="tkt-empty-box">No se pudo cargar el estado de las colas.</div>');
            });
        }

        function retry(uuid, label) {
            $.ajax({
                url: TKA.urls.queueRetry,
                method: 'POST',
                data: uuid ? { uuid: uuid } : {},
                headers: { Accept: 'application/json' },
            }).done(function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || label);
                load();
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || (xhr.status === 403
                    ? 'Hace falta permiso de ajustes del módulo para reencolar jobs.'
                    : 'No se pudieron reencolar los jobs.');
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            });
        }

        $backdrop.on('click', '[data-queue-retry]', function () { retry($(this).data('queue-retry'), 'Job reencolado.'); });
        $backdrop.on('click', '#tkt-queue-retry-all', function () { retry(null, 'Jobs reencolados.'); });

        // Purgar borra los jobs para siempre: se confirma antes, igual que
        // el resto de acciones destructivas de esta pantalla.
        $backdrop.on('click', '#tkt-queue-flush', function () {
            openConfirmModal({
                icon: 'fa-solid fa-trash',
                danger: true,
                title: 'Purgar la dead-letter',
                message: 'Se eliminarán todos los jobs fallidos. No se pueden recuperar ni reintentar después.',
                confirmLabel: 'Purgar',
                onConfirm: function () {
                    $.ajax({
                        url: TKA.urls.queueFlush,
                        method: 'POST',
                        headers: { Accept: 'application/json' },
                    }).done(function (resp) {
                        if (window.toastr) toastr.success((resp && resp.message) || 'Dead-letter purgada.');
                        openQueueModal();
                    }).fail(function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) || (xhr.status === 403
                            ? 'Hace falta permiso de ajustes del módulo para purgar la cola.'
                            : 'No se pudo purgar la cola de fallidos.');
                        if (window.toastr) toastr.error(msg); else window.alert(msg);
                    });
                },
            });
        });

        load();
    }

    // Pill "Carga" — AssignmentService::getAgentWorkload() ya existía (la
    // usa la asignación automática al crear ticket) pero sin ningún punto
    // de entrada HTTP para verla desde aquí. Sin "capacidad"/"% ocupación":
    // no hay ninguna columna de capacidad máxima configurada por agente.
    function openWorkloadModal() {
        var data = null;
        var vista = 'agents';        // 'agents' | 'teams'
        var verTodos = false;        // incluir agentes sin carga
        var busqueda = '';
        var equipoAbierto = null;    // id del equipo desplegado
        var confirmandoReparto = false;
        var confirmTimer = null;

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-scale-balanced',
            kicker: 'Equipo · carga',
            title: 'Reparto y capacidad',
            width: 'md',
            body: '<div class="tkt-empty-box">Cargando…</div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        // ── helpers de pintado ───────────────────────────────────────────────

        function plural(n, singular, prural) {
            return n + ' ' + (n === 1 ? singular : prural);
        }

        // La barra no representa ocupación sobre una capacidad (no existe), sino
        // la carga de cada agente respecto al más cargado. En 10 tramos porque el
        // ancho tiene que salir de una clase: en este proyecto no se escriben
        // estilos inline.
        function barra(abiertos, maximo) {
            if (!maximo || abiertos <= 0) return '';
            var tramo = Math.max(1, Math.ceil((abiertos / maximo) * 10));
            return '<div class="tkt-wl-bar"><span class="tkt-wl-bar-fill lvl-' + tramo + '"></span></div>';
        }

        function filaAgente(a, maximo) {
            var sub = plural(a.open_tickets, 'abierto', 'abiertos');
            if (a.at_risk > 0) sub += ' · <span class="tkt-wl-risk">' + plural(a.at_risk, 'en riesgo', 'en riesgo') + '</span>';

            return '<div class="tkt-wl-item' + (a.at_risk > 0 ? ' risk' : '') + '">' +
                '<div class="tkt-line">' +
                    '<span class="tkt-wl-av">' + escapeHtml(initials(a.name)) + '</span>' +
                    '<span class="tkt-line-main">' +
                        '<span class="tkt-line-title tkt-trunc">' + escapeHtml(a.name) + '</span>' +
                        '<span class="tkt-line-sub">' + sub + '</span>' +
                    '</span>' +
                    // "No recibe reparto" es el dato que faltaba: explica por qué
                    // un agente cargado no baja nunca (vacaciones, ausencia, turno
                    // fuera de horario o sin el rol).
                    (a.eligible ? '' : chip('no recibe reparto', 'tkt-chip-warn')) +
                    '<span class="tkt-chip tkt-chip-muted mono">' + a.open_tickets + '</span>' +
                '</div>' +
                barra(a.open_tickets, maximo) +
            '</div>';
        }

        function agentesFiltrados() {
            var q = busqueda.trim().toLowerCase();

            return (data.agents || []).filter(function (a) {
                if (q && a.name.toLowerCase().indexOf(q) === -1) return false;
                if (!verTodos && !q && a.open_tickets === 0 && a.at_risk === 0) return false;
                return true;
            });
        }

        function vistaAgentes() {
            if (!(data.agents || []).length) {
                return '<div class="tkt-empty-box">No hay agentes disponibles ni tickets asignados.</div>';
            }

            var visibles = agentesFiltrados();
            var maximo = (data.agents[0] && data.agents[0].open_tickets) || 0;
            var ocultos = (data.agents || []).length - visibles.length;

            var lista = visibles.length
                ? visibles.map(function (a) { return filaAgente(a, maximo); }).join('')
                : '<div class="tkt-empty-box">Ningún agente coincide con la búsqueda.</div>';

            var pie = '';
            if (ocultos > 0 && !busqueda) {
                pie = '<button type="button" class="tkt-link-btn tkt-wl-more" id="tkt-wl-toggle-all">' +
                    (verTodos ? 'Ocultar los agentes sin tickets abiertos' : 'Mostrar ' + ocultos + ' agente(s) sin tickets abiertos') +
                    '</button>';
            }

            return lista + pie;
        }

        function vistaEquipos() {
            var equipos = (data.teams || []).slice();
            var porId = {};
            (data.agents || []).forEach(function (a) { porId[a.id] = a; });

            // "Sin equipo" no viene del servidor (no existe como grupo): se compone
            // aquí con los agentes cuyo team_ids está vacío, para no inventar un
            // equipo que nadie ha creado.
            var huerfanos = (data.agents || []).filter(function (a) { return !a.team_ids.length; });
            if (huerfanos.length) {
                equipos.push({
                    id: 0,
                    name: 'Sin equipo',
                    is_active: true,
                    agents: huerfanos.length,
                    open_tickets: huerfanos.reduce(function (t, a) { return t + a.open_tickets; }, 0),
                    at_risk: huerfanos.reduce(function (t, a) { return t + a.at_risk; }, 0),
                    agent_ids: huerfanos.map(function (a) { return a.id; }),
                });
            }

            if (!equipos.length) {
                return '<div class="tkt-empty-box">No hay equipos con agentes asignados. Los grupos se gestionan en Ajustes → Grupos.</div>';
            }

            var maximo = equipos.reduce(function (m, e) { return Math.max(m, e.open_tickets); }, 0);

            return equipos.map(function (e) {
                var sub = plural(e.agents, 'agente', 'agentes') + ' · ' + plural(e.open_tickets, 'abierto', 'abiertos');
                if (e.at_risk > 0) sub += ' · <span class="tkt-wl-risk">' + e.at_risk + ' en riesgo</span>';

                var miembros = '';
                if (equipoAbierto === e.id) {
                    var filas = e.agent_ids
                        .map(function (id) { return porId[id]; })
                        .filter(function (a) { return a && (a.open_tickets > 0 || a.at_risk > 0 || verTodos); })
                        .map(function (a) { return filaAgente(a, (data.agents[0] && data.agents[0].open_tickets) || 0); })
                        .join('');
                    miembros = '<div class="tkt-wl-members">' +
                        (filas || '<div class="tkt-empty-box">Ningún miembro tiene tickets abiertos.</div>') +
                        '</div>';
                }

                return '<div class="tkt-wl-item' + (e.at_risk > 0 ? ' risk' : '') + '">' +
                    '<button type="button" class="tkt-line tkt-w-100 tkt-wl-team" data-team="' + e.id + '">' +
                        '<span class="tkt-line-main">' +
                            '<span class="tkt-line-title tkt-trunc">' + escapeHtml(e.name) +
                                (e.is_active ? '' : ' ' + chip('inactivo', 'tkt-chip-muted')) + '</span>' +
                            '<span class="tkt-line-sub">' + sub + '</span>' +
                        '</span>' +
                        '<i class="fa-solid ' + (equipoAbierto === e.id ? 'fa-chevron-up' : 'fa-chevron-down') + ' tkt-wl-caret"></i>' +
                        '<span class="tkt-chip tkt-chip-muted mono">' + e.open_tickets + '</span>' +
                    '</button>' +
                    barra(e.open_tickets, maximo) +
                    miembros +
                '</div>';
            }).join('');
        }

        function resumenHtml() {
            var conCarga = (data.agents || []).filter(function (a) { return a.open_tickets > 0; }).length;
            var disponibles = (data.agents || []).filter(function (a) { return a.eligible; }).length;
            var enRiesgo = (data.agents || []).reduce(function (t, a) { return t + a.at_risk; }, 0);
            var estrategia = etiquetaEstrategia(data.assignment.strategy);

            return '<div class="tkt-kv-grid">' +
                '<span>sin asignar</span><span>' + plural(data.unassigned.total, 'ticket', 'tickets') + '</span>' +
                '<span>en riesgo</span><span>' + enRiesgo + ' en ' + plural(conCarga, 'agente', 'agentes') + '</span>' +
                '<span>agentes disponibles</span><span>' + disponibles + '</span>' +
                // El mockup pone aquí "capacidad por agente: 12 abiertos". No hay
                // ninguna capacidad de tickets configurada en la base, así que se
                // dice eso en vez de calcular un porcentaje inventado.
                (data.capacity === null ? '<span>capacidad por agente</span><span>no configurada</span>' : '') +
                '<span>reparto</span><span>' + escapeHtml(estrategia) + (data.assignment.enabled ? '' : ' (desactivado)') + '</span>' +
            '</div>';
        }

        function etiquetaEstrategia(valor) {
            var found = (data.assignment.strategies || []).filter(function (s) { return s.value === valor; })[0];
            return found ? found.label : valor;
        }

        function ajustesHtml() {
            var a = data.assignment;

            if (!data.can_manage) {
                // Sin permiso de ajustes se ve la configuración, no se toca.
                return '<div class="tkt-field">' +
                    '<label class="tkt-label">Reparto automático</label>' +
                    '<div class="tkt-note">' +
                        (a.enabled ? 'Activado' : 'Desactivado') + ' · ' + escapeHtml(etiquetaEstrategia(a.strategy)) +
                        '. Cambiarlo requiere permiso de configuración de tickets.' +
                    '</div>' +
                '</div>';
            }

            var opciones = (a.strategies || []).map(function (s) {
                return '<button type="button" class="tkt-option' + (s.value === a.strategy ? ' on' : '') + '" data-strategy="' + escapeHtml(s.value) + '">' +
                    '<span class="tkt-fill">' +
                        '<span class="tkt-option-title">' + escapeHtml(s.label) +
                            // Una estrategia que el módulo Helpdesk ofrece pero que
                            // el lado de tickets no sabe ejecutar dejaría los
                            // tickets sin asignar en silencio: se avisa.
                            (s.supported ? '' : ' ' + chip('no aplica a tickets', 'tkt-chip-warn')) +
                        '</span>' +
                        '<span class="tkt-option-sub">' + escapeHtml(s.description) + '</span>' +
                    '</span>' +
                '</button>';
            }).join('');

            return '<div class="tkt-field">' +
                '<label class="tkt-label">Reparto automático</label>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-wl-enabled"' + (a.enabled ? ' checked' : '') + '> ' +
                    'Repartir automáticamente los nuevos tickets</label>' +
                '<div class="tkt-wl-strategies" id="tkt-wl-strategies">' + opciones + '</div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-wl-language"' + (a.language_routing ? ' checked' : '') + '> ' +
                    'Preferir agentes que hablen el idioma del ticket</label>' +
                '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> El interruptor y la estrategia son ajustes globales del helpdesk: ' +
                    'los comparten los tickets y las conversaciones. La preferencia por idioma solo afecta a tickets.</div>' +
                '<button type="button" class="tkt-btn tkt-btn-primary tkt-w-100" id="tkt-wl-save">Guardar reparto</button>' +
            '</div>';
        }

        // Avisos que hacen honesta la cifra del botón: se ve ANTES de pulsar
        // cuántos se reparten de verdad y por qué el resto no.
        function avisosRepartoHtml() {
            var u = data.unassigned;
            var avisos = [];

            if (u.total === 0) {
                return '<div class="tkt-note ok">No hay tickets sin asignar ahora mismo.</div>';
            }

            if (u.closed > 0) {
                avisos.push(plural(u.closed, 'ticket cerrado', 'tickets cerrados') + ' sin asignar entran también en el reparto: ' +
                    'el endpoint no los excluye.');
            }

            // El reparto pide el pool de agentes filtrando por la categoría del
            // ticket; hoy ese filtro revienta y el ticket se queda sin asignar sin
            // ningún error visible. Decirlo aquí evita el "he pulsado y no ha
            // pasado nada".
            if (data.assignment.category_pool_available === false && u.with_category > 0) {
                avisos.push(plural(u.with_category, 'ticket tiene', 'tickets tienen') + ' categoría y el reparto no encuentra agentes ' +
                    'para ellos: el filtro por categoría no está operativo.');
            }

            if (u.total > u.per_run_limit) {
                avisos.push('Se reparten como máximo ' + u.per_run_limit + ' por pulsación.');
            }

            if (!avisos.length) {
                return '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Se repartirán ' + u.total +
                    ' ticket(s) por carga, ponderando la prioridad.</div>';
            }

            return '<div class="tkt-note warn"><i class="fa-solid fa-triangle-exclamation"></i><span>' +
                avisos.join(' ') + '</span></div>';
        }

        function pieHtml() {
            var u = data.unassigned;
            var repartir = '';

            if (data.can_distribute) {
                repartir = u.total > 0
                    ? '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-workload-distribute">Repartir ' + u.total + ' sin asignar</button>'
                    : '<button type="button" class="tkt-btn tkt-btn-primary" disabled>No hay tickets sin asignar</button>';
            }

            return repartir +
                '<button type="button" class="tkt-btn" id="tkt-wl-view">' +
                    (vista === 'agents' ? 'Ver por equipos' : 'Ver por agentes') +
                '</button>' +
                '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>';
        }

        function render() {
            var titulo = vista === 'agents' ? 'Carga por agente' : 'Carga por equipo';
            var pieDeLista = vista === 'agents'
                ? '<div class="tkt-cap">Ordenado de más a menos carga. La barra es relativa al agente con más tickets abiertos, no a una capacidad.</div>'
                : '<div class="tkt-cap">Carga sumada de los miembros de cada grupo. Pulsa un equipo para ver quién la lleva.</div>';

            var buscador = vista === 'agents'
                ? '<input type="text" class="tkt-input" id="tkt-wl-search" placeholder="Buscar agente…" value="' + escapeHtml(busqueda) + '">'
                : '';

            $backdrop.find('.tkt-modal-body').html(
                '<div class="tkt-field">' +
                    '<label class="tkt-label">' + titulo + '</label>' +
                    buscador +
                    '<div class="tkt-wl-list">' + (vista === 'agents' ? vistaAgentes() : vistaEquipos()) + '</div>' +
                    pieDeLista +
                '</div>' +
                resumenHtml() +
                avisosRepartoHtml() +
                ajustesHtml()
            );

            $backdrop.find('.tkt-modal-foot').html(pieHtml());
        }

        // Repinta SOLO la lista. Buscar y desplegar equipos pasan por aquí: un
        // render() completo reconstruiría el <input> en cada tecla y el foco se
        // perdería a la primera letra.
        function renderLista() {
            $backdrop.find('.tkt-wl-list').html(vista === 'agents' ? vistaAgentes() : vistaEquipos());
        }

        function load() {
            $.getJSON(TKA.urls.workloadOverview).done(function (res) {
                if (!res || !res.agents) {
                    $backdrop.find('.tkt-modal-body').html('<div class="tkt-empty-box">No se pudo cargar la carga por agente.</div>');
                    return;
                }
                data = res;
                render();
            }).fail(function () {
                $backdrop.find('.tkt-modal-body').html('<div class="tkt-empty-box">No se pudo cargar la carga por agente.</div>');
            });
        }

        // ── eventos ──────────────────────────────────────────────────────────

        $backdrop.on('click', '#tkt-wl-view', function () {
            vista = vista === 'agents' ? 'teams' : 'agents';
            equipoAbierto = null;
            render();
        });

        $backdrop.on('click', '#tkt-wl-toggle-all', function () {
            verTodos = !verTodos;
            renderLista();
        });

        $backdrop.on('click', '.tkt-wl-team', function () {
            var id = parseInt($(this).data('team'), 10);
            equipoAbierto = equipoAbierto === id ? null : id;
            renderLista();
        });

        $backdrop.on('input', '#tkt-wl-search', function () {
            busqueda = $(this).val() || '';
            renderLista();
        });

        $backdrop.on('click', '#tkt-wl-strategies .tkt-option', function () {
            $backdrop.find('#tkt-wl-strategies .tkt-option').removeClass('on');
            $(this).addClass('on');
        });

        $backdrop.on('click', '#tkt-wl-save', function () {
            var $btn = $(this).prop('disabled', true).text('Guardando…');

            $.ajax({
                url: TKA.urls.workloadAssignment,
                method: 'POST',
                headers: { Accept: 'application/json' },
                data: {
                    enabled: $backdrop.find('#tkt-wl-enabled').is(':checked') ? 1 : 0,
                    strategy: $backdrop.find('#tkt-wl-strategies .tkt-option.on').data('strategy') || data.assignment.strategy,
                    language_routing: $backdrop.find('#tkt-wl-language').is(':checked') ? 1 : 0,
                },
                success: function (resp) {
                    if (resp && resp.assignment) data.assignment = resp.assignment;
                    if (window.toastr) toastr.success((resp && resp.message) || 'Reparto automático guardado.');
                    render();
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo guardar el reparto.';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text('Reintentar');
                },
            });
        });

        // Reparto en dos pulsaciones en lugar de un modal de confirmación: abrir
        // otro modal cierra éste (openModal() cierra el anterior) y se perdería la
        // vista con la que el usuario acaba de decidir. La primera pulsación deja
        // el botón diciendo exactamente qué va a pasar.
        $backdrop.on('click', '#tkt-workload-distribute', function () {
            var $btn = $(this);

            if (!confirmandoReparto) {
                confirmandoReparto = true;
                $btn.addClass('tkt-wl-confirm').text('Confirmar: repartir ' + data.unassigned.total + ' ticket(s)');
                confirmTimer = window.setTimeout(function () {
                    confirmandoReparto = false;
                    $btn.removeClass('tkt-wl-confirm').text('Repartir ' + data.unassigned.total + ' sin asignar');
                }, 6000);
                return;
            }

            window.clearTimeout(confirmTimer);
            confirmandoReparto = false;
            $btn.prop('disabled', true).removeClass('tkt-wl-confirm').text('Repartiendo…');

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
        var seleccionados = Object.keys(TKA.state.bulk || {}).map(Number);
        // Los filtros de esta pantalla viven en el querystring, así que
        // reenviarlo es lo que hace que "exportar el filtro actual" exporte
        // de verdad lo que se está viendo. Antes el modal lo prometía en el
        // texto y llamaba a la URL pelada, sin ningún filtro.
        var filtrosActuales = window.location.search.replace(/^\?/, '');
        var columnas = null;

        function scopeActual() {
            return $('input[name="tkt-export-scope"]:checked').val() || 'filter';
        }

        function paramsDeAlcance() {
            var scope = scopeActual();
            var params = new URLSearchParams(scope === 'filter' ? filtrosActuales : '');
            params.set('scope', scope);
            if (scope === 'selection') params.set('ids', seleccionados.join(','));

            return params;
        }

        var $modal = openModal(modalShell({
            icon: 'fa-solid fa-download',
            kicker: 'Tickets · exportar',
            title: 'Exportar tickets',
            width: 'sm',
            body: '<div class="tkt-field"><label class="tkt-label">Alcance</label>' +
                    '<label class="tkt-option' + (seleccionados.length ? ' on' : '') + '" data-scope-option>' +
                        '<input type="radio" name="tkt-export-scope" value="selection"' +
                        (seleccionados.length ? ' checked' : ' disabled') + ' class="tkt-m0">' +
                        '<span><span class="tkt-option-title">Selección (' + seleccionados.length + ')</span>' +
                        '<br><span class="tkt-option-sub">' + (seleccionados.length
                            ? 'solo los tickets marcados en el listado'
                            : 'no hay ninguno marcado') + '</span></span></label>' +
                    '<label class="tkt-option' + (seleccionados.length ? '' : ' on') + '" data-scope-option>' +
                        '<input type="radio" name="tkt-export-scope" value="filter"' +
                        (seleccionados.length ? '' : ' checked') + ' class="tkt-m0">' +
                        '<span><span class="tkt-option-title">Filtro actual</span>' +
                        '<br><span class="tkt-option-sub">lo que se está viendo ahora en el listado</span></span></label>' +
                    '<label class="tkt-option" data-scope-option>' +
                        '<input type="radio" name="tkt-export-scope" value="all" class="tkt-m0">' +
                        '<span><span class="tkt-option-title">Todo el histórico</span>' +
                        '<br><span class="tkt-option-sub">ignora los filtros de pantalla</span></span></label>' +
                '</div>' +
                '<div class="tkt-field"><label class="tkt-label">Formato</label>' +
                    '<label class="tkt-option on" data-export-option="csv"><input type="radio" name="tkt-export-format" value="csv" checked class="tkt-m0"><span class="tkt-option-title">CSV</span></label>' +
                    '<label class="tkt-option" data-export-option="pdf"><input type="radio" name="tkt-export-format" value="pdf" class="tkt-m0"><span class="tkt-option-title">PDF</span></label>' +
                '</div>' +
                '<div class="tkt-field" id="tkt-export-cols-wrap">' +
                    '<label class="tkt-label">Columnas<span class="hint">solo CSV</span></label>' +
                    '<div class="tkt-colgrid" id="tkt-export-cols"><span class="tkt-note">Cargando…</span></div>' +
                '</div>' +
                '<div class="tkt-note" id="tkt-export-count">Calculando cuántos tickets se exportarán…</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-export-confirm">Exportar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        // Cuántas filas van a salir, ANTES de descargarlas: una exportación
        // de 20 000 y otra de 12 no se piden igual.
        function estimar() {
            if (!TKA.urls.exportEstimate) { $modal.find('#tkt-export-count').remove(); return; }

            var params = paramsDeAlcance();
            $modal.find('#tkt-export-count').text('Calculando…');

            $.getJSON(TKA.urls.exportEstimate + '?' + params.toString()).done(function (res) {
                var n = (res && res.total) || 0;
                $modal.find('#tkt-export-count').html('<i class="fa-solid fa-circle-info"></i> Se exportarán <strong>' +
                    n + '</strong> ' + (n === 1 ? 'ticket' : 'tickets') + '.');

                if (columnas || !res || !res.columns) return;
                columnas = res.columns;
                $modal.find('#tkt-export-cols').html(columnas.map(function (c) {
                    return '<label class="tkt-check sm"><input type="checkbox" value="' + c.key + '"' +
                        (c.default ? ' checked' : '') + '> ' + escapeHtml(c.label) + '</label>';
                }).join(''));
            }).fail(function () {
                $modal.find('#tkt-export-count').text('No se pudo calcular el total.');
            });
        }

        estimar();

        $modal.on('change', '[name="tkt-export-scope"]', function () {
            $modal.find('[data-scope-option]').removeClass('on');
            $(this).closest('[data-scope-option]').addClass('on');
            estimar();
        });

        $modal.on('click', '[data-export-option]', function () {
            $modal.find('[data-export-option]').removeClass('on');
            $(this).addClass('on').find('input').prop('checked', true);
            // Las columnas solo las respeta el CSV; en PDF la plantilla es fija.
            $modal.find('#tkt-export-cols-wrap').toggle($(this).data('export-option') === 'csv');
        });

        $modal.on('click', '#tkt-export-confirm', function () {
            var format = $modal.find('input[name="tkt-export-format"]:checked').val();
            var params = paramsDeAlcance();

            if (format === 'csv') {
                var elegidas = $modal.find('#tkt-export-cols input:checked').map(function () { return this.value; }).get();
                if (!elegidas.length) { if (window.toastr) toastr.error('Elige al menos una columna'); return; }
                params.set('columns', elegidas.join(','));
            }

            window.location = TKA.urls.exportTemplate.replace('__FORMAT__', format) + '?' + params.toString();
            closeModal();
        });
    }

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
        });
    }

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
            var w = carga && carga[a.id];
            if (!w) return 'Agente';

            return w.open_tickets + (w.open_tickets === 1 ? ' abierto' : ' abiertos') +
                (w.at_risk ? ' · ' + w.at_risk + ' en riesgo' : '');
        }

        function agentRows(filter) {
            var q = String(filter || '').trim().toLowerCase();
            var list = agents.filter(function (a) { return !q || String(a.name).toLowerCase().indexOf(q) !== -1; });
            if (!list.length) return '<div class="tkt-empty-box">Ningún agente coincide con la búsqueda.</div>';

            // Con la carga cargada, los menos ocupados primero: es el orden
            // en que se quiere leer la lista al repartir.
            if (carga) {
                list = list.slice().sort(function (a, b) {
                    return ((carga[a.id] && carga[a.id].open_tickets) || 0) - ((carga[b.id] && carga[b.id].open_tickets) || 0);
                });
            }

            return list.map(function (a) {
                var isCurrent = String(a.id) === String(currentId);
                var w = carga && carga[a.id];
                return '<button type="button" class="tkt-pick' + (isCurrent ? ' on' : '') + '" data-agent="' + a.id + '">' +
                    '<span class="av">' + escapeHtml(initials(a.name)) + '</span>' +
                    '<span class="who"><span class="n">' + escapeHtml(a.name) + '</span>' +
                    '<span class="s">' + escapeHtml(subtitulo(a, isCurrent)) + '</span></span>' +
                    (w && w.at_risk ? '<span class="tkt-chip tkt-chip-warn">' + w.at_risk + '</span>' : '') +
                    (isCurrent ? '<i class="fa-solid fa-check"></i>' : '') +
                '</button>';
            }).join('');
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-user-plus', kicker: 'Ticket · asignación',
            title: 'Asignar ticket', titleChip: t.ticket_number, width: 'sm',
            body: '<div class="tkt-field"><input type="search" class="tkt-input" id="tkt-assign-search" placeholder="Buscar agente…" aria-label="Buscar agente"></div>' +
                '<div class="tkt-pick-list" id="tkt-assign-list">' + agentRows('') + '</div>' +
                (currentId ? '<button type="button" class="tkt-btn tkt-btn-start tkt-w-100" id="tkt-assign-none"><i class="fa-solid fa-user-slash"></i> Dejar sin asignar</button>' : ''),
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

    // ── Modal 39: Dividir ticket ──────────────────────────────
    function openSplitModal(t) {
        var d = TKA.state.currentDetail;
        var thread = (d && d.thread) || [];
        if (thread.length < 2) {
            if (window.toastr) toastr.info('Hace falta más de un mensaje en el hilo para poder dividirlo');
            return;
        }
        var picked = {};

        function rowsHtml() {
            return thread.map(function (m) {
                var preview = String(m.body || '').replace(/<[^>]+>/g, '').trim().slice(0, 70);
                return '<label class="tkt-pick as-option' + (picked[m.id] ? ' on' : '') + '">' +
                    '<input type="checkbox" data-split-item="' + m.id + '"' + (picked[m.id] ? ' checked' : '') + '>' +
                    '<span class="who"><span class="n">' + escapeHtml(preview || '(sin texto)') + '</span>' +
                    '<span class="s">' + escapeHtml([m.sender_name, m.created_at_human].filter(Boolean).join(' · ')) + '</span></span>' +
                '</label>';
            }).join('');
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-scissors', kicker: 'Ticket · dividir',
            title: 'Dividir ticket', titleChip: t.ticket_number, width: 'sm',
            body: '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Útil cuando el cliente mezcla dos asuntos en la misma conversación. Los mensajes se MUEVEN, no se copian.</div>' +
                '<div class="tkt-cap">Mensajes a mover</div>' +
                '<div class="tkt-pick-list" id="tkt-split-list">' + rowsHtml() + '</div>' +
                '<div class="tkt-field"><label class="tkt-label" for="tkt-split-subject">Asunto del nuevo ticket<span class="req">*</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-split-subject" value="' + escapeHtml(t.subject || '') + '"></div>' +
                '<div class="tkt-field-row">' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-split-category">Categoría</label>' +
                        '<select class="tkt-input" id="tkt-split-category" data-no-select2><option value="">La misma</option>' + optionsHtml(TKA.state.categories, 'id', t.category_id) + '</select></div>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-split-assignee">Agente</label>' +
                        '<select class="tkt-input" id="tkt-split-assignee" data-no-select2><option value="">Sin asignar</option>' + optionsHtml(TKA.state.agentsFull, 'id', '') + '</select></div>' +
                '</div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-split-link" checked> Vincular ambos tickets entre sí</label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-split-confirm" disabled>Dividir ticket</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        function refreshConfirm() {
            var n = Object.keys(picked).length;
            $backdrop.find('#tkt-split-confirm')
                .prop('disabled', n === 0 || n >= thread.length)
                .text(n ? 'Mover ' + n + (n === 1 ? ' mensaje' : ' mensajes') : 'Dividir ticket');
            $backdrop.find('#tkt-split-warn').remove();
            if (n >= thread.length) {
                $backdrop.find('#tkt-split-list').after('<div class="tkt-note" id="tkt-split-warn"><i class="fa-solid fa-triangle-exclamation"></i> Deja al menos un mensaje en el ticket original.</div>');
            }
        }

        $backdrop.on('change', '[data-split-item]', function () {
            var id = parseInt($(this).data('split-item'), 10);
            if (this.checked) picked[id] = true; else delete picked[id];
            $(this).closest('.tkt-pick').toggleClass('on', this.checked);
            refreshConfirm();
        });

        $backdrop.on('click', '#tkt-split-confirm', function () {
            var subject = ($('#tkt-split-subject').val() || '').trim();
            if (!subject) { if (window.toastr) toastr.error('Indica el asunto del nuevo ticket'); return; }
            var $btn = $(this).prop('disabled', true).text('Dividiendo…');
            $.ajax({
                url: t.url_split, method: 'POST',
                data: {
                    subject: subject, item_ids: Object.keys(picked),
                    category_id: $('#tkt-split-category').val() || null,
                    assignee_id: $('#tkt-split-assignee').val() || null,
                    link_tickets: $('#tkt-split-link').is(':checked') ? 1 : 0,
                },
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Ticket dividido');
                    closeModal();
                    window.location = TKA.urls.index + '?ticket=' + resp.ticket_id;
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo dividir el ticket';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text('Dividir ticket');
                },
            });
        });
    }

    // ── Modal 22: Reputación y autenticación ──────────────────
    function openReputationModal() {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-shield-halved', kicker: 'Entregabilidad · reputación',
            title: 'Reputación y autenticación', width: 'sm',
            body: '<div id="tkt-rep-body"><div class="tkt-skeleton"></div></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-rep-save">Guardar</button>' +
                '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));
        if (!TKA.urls.reputation) return;

        $backdrop.on('click', '#tkt-rep-save', function () {
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: TKA.urls.reputation,
                method: 'PATCH',
                data: {
                    notify_managers: $backdrop.find('#tkt-rep-notify').is(':checked') ? 1 : 0,
                    auto_suppress: $backdrop.find('#tkt-rep-suppress').is(':checked') ? 1 : 0,
                },
            }).done(function (resp) {
                if (window.toastr) toastr.success(resp.message || 'Guardado'); else window.alert(resp.message || 'Guardado');
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo guardar.';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });

        $.getJSON(TKA.urls.reputation).done(function (d) {
            var auth = d.auth || {}, rates = d.rates || {}, settings = d.settings || {};
            // Un registro que falta no es un detalle estético: sin SPF/DKIM el
            // correo acaba en spam, y un DMARC en p=none no protege de nada.
            function authRow(label, ok, value, warn) {
                return '<div class="tkt-authrow"><span class="k">' + escapeHtml(label) + '</span>' +
                    '<span class="v"><span class="tkt-rchip' + (ok && !warn ? ' ok' : (ok ? '' : ' strong')) + '">' +
                        (ok ? (warn ? 'revisar' : 'correcto') : 'no publicado') + '</span>' +
                        (value ? '<span class="mono d">' + escapeHtml(value) + '</span>' : '') + '</span></div>';
            }
            var dmarcWarn = auth.dmarc && auth.dmarc.policy === 'none';
            var html = '<div class="tkt-headline' + (auth.spf && auth.spf.found && auth.dkim && auth.dkim.found ? '' : ' bad') + '">' +
                    '<div class="t">' + escapeHtml(d.domain || '—') + '</div>' +
                    '<div class="s">Dominio desde el que sale el correo (' + escapeHtml(d.from || '—') + ')</div></div>' +
                (settings.suppressed ? '<div class="tkt-note warn"><i class="fa-solid fa-triangle-exclamation"></i> Envío saliente pausado automáticamente: la tasa de rebote superó el umbral crítico. Se reanuda solo al recuperarse.</div>' : '') +
                '<div class="tkt-authrows">' +
                    authRow('SPF', !!(auth.spf && auth.spf.found), auth.spf && auth.spf.value, false) +
                    authRow('DKIM', !!(auth.dkim && auth.dkim.found),
                        auth.dkim && auth.dkim.selectors && auth.dkim.selectors.length
                            ? auth.dkim.selectors.length + ' selector(es): ' + auth.dkim.selectors.join(', ') : null, false) +
                    authRow('DMARC', !!(auth.dmarc && auth.dmarc.found),
                        auth.dmarc && auth.dmarc.policy ? 'p=' + auth.dmarc.policy : null, dmarcWarn) +
                '</div>' +
                (dmarcWarn ? '<div class="tkt-note"><i class="fa-solid fa-triangle-exclamation"></i> DMARC está en <span class="mono">p=none</span>: solo informa, no bloquea la suplantación. Se recomienda <span class="mono">quarantine</span>.</div>' : '') +
                (auth.dkim && !auth.dkim.found ? '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> No se encontró DKIM entre los selectores habituales. Si usáis uno propio, la comprobación automática no lo detecta.</div>' : '') +
                '<div class="tkt-cap">Últimos ' + (rates.window_days || 30) + ' días</div>' +
                '<div class="tkt-side-rows">' +
                    sideRow('enviados', String(rates.sent != null ? rates.sent : '—'), { mono: true }) +
                    sideRow('tasa de rebote', rates.bounce_rate != null ? rates.bounce_rate + ' %' : 'sin envíos', { mono: true, strong: true }) +
                    sideRow('supresiones', String(rates.suppressed != null ? rates.suppressed : '—'), { mono: true, last: true }) +
                '</div>' +
                '<div class="tkt-cap">Si la tasa de rebote cruza el umbral crítico</div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-rep-notify"' + (settings.notify_managers ? ' checked' : '') + '> Avisar a los managers por email</label>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-rep-suppress"' + (settings.auto_suppress ? ' checked' : '') + '> Suprimir automáticamente el envío saliente hasta que se recupere</label>' +
                '<div id="tkt-rep-kpi"></div>';
            $backdrop.find('#tkt-rep-body').html(html);

            // KPI de apertura/clic/latencia: ya las calcula
            // TicketMailsController::stats() para la bandeja de emails.
            $.getJSON(TKA.urls.emailsIndex).done(function (res) {
                var st = res && res.stats;
                if (!st) return;
                $backdrop.find('#tkt-rep-kpi').html(
                    '<div class="tkt-cap">Últimos 50 correos salientes</div><div class="tkt-kpi4">' +
                        '<div><div class="v">' + st.bounce_rate + '%</div><div class="l">Rebote</div></div>' +
                        '<div><div class="v">' + st.opened_rate + '%</div><div class="l">Apertura</div></div>' +
                        '<div><div class="v">' + st.clicked_rate + '%</div><div class="l">Clic</div></div>' +
                        '<div><div class="v">' + (st.avg_latency != null ? st.avg_latency + 's' : '—') + '</div><div class="l">Latencia</div></div>' +
                    '</div><a href="' + TKA.urls.emailsIndex + '" class="tkt-btn tkt-w-100">Abrir bandeja de correos</a>'
                );
            });
        }).fail(function () {
            $backdrop.find('#tkt-rep-body').html('<div class="tkt-empty-box">No se pudo consultar la reputación del dominio.</div>');
        });
    }

    // ── Modal 23: Bandeja compartida (colisión) ───────────────
    function openCollisionModal(t, presence) {
        // TKA.state.presenceUsers (Echo .here()) incluye al propio usuario —
        // el banner ya se filtra al pintarse, pero este modal recibe la
        // lista tal cual desde el listener del botón.
        var people = (presence || []).filter(function (u) { return u.id !== TKA.state.currentUserId; });
        var rows = people.length
            ? people.map(function (pr) {
                return '<div class="tkt-mailitem"><span class="av">' + escapeHtml(initials(pr.name)) + '</span>' +
                    '<span class="who"><span class="n">' + escapeHtml(pr.name) + '</span>' +
                    '<span class="s">' + escapeHtml(pr.typing ? 'Está redactando una respuesta' : 'Viendo el ticket') + '</span></span>' +
                    '<span class="tkt-live"><span class="dot"></span>' + (pr.typing ? 'escribiendo' : 'viendo') + '</span>' +
                    '<button type="button" class="tkt-btn tkt-btn-sm" data-nudge="' + escapeHtml(String(pr.id)) + '">Avisar</button></div>';
              }).join('')
            : '<div class="tkt-empty-box">Ahora mismo nadie más está en este ticket.</div>';

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-users', kicker: 'Bandeja · colisión',
            title: 'Bandeja compartida', titleChip: t.ticket_number, width: 'sm',
            body: '<div class="tkt-mailitems">' + rows + '</div>' +
                '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> La presencia se actualiza mientras la pestaña está abierta; al cerrarla el resto deja de verte.</div>',
            foot: (people.length ? '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-collision-take">Tomar el control</button>' : '') +
                '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        // "Avisar a X": notificación puntual al agente elegido, no cambia
        // nada del ticket — puede pulsarse varias veces sin más efecto que
        // el que ya aplica el cooldown del propio backend.
        $backdrop.on('click', '[data-nudge]', function () {
            var $btn = $(this).prop('disabled', true);
            if (!t.url_presence_nudge) { $btn.prop('disabled', false); return; }
            $.post(t.url_presence_nudge, { to_user_id: $(this).data('nudge') })
                .done(function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Aviso enviado'); else window.alert('Aviso enviado');
                })
                .fail(function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo avisar.';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                })
                .always(function () { $btn.prop('disabled', false); });
        });

        // "Tomar el control": me asigno el ticket, igual que el selector de
        // agente de la ficha — misma ruta/autorización, solo con el destino
        // fijado a mí mismo en vez de elegirlo de un <select>.
        $backdrop.on('click', '#tkt-collision-take', function () {
            if (!TKA.state.currentUserId) return;
            patchTicket(t, 'assignee_id', TKA.state.currentUserId);
        });
    }

    // ── Modal 25: Cliente 360 ─────────────────────────────────
    function openCustomer360Modal(t, customer) {
        if (!customer) { if (window.toastr) toastr.info('Este ticket no tiene un cliente asociado'); return; }
        var integraciones = (customer.integrations || []).map(function (i) {
            return '<span class="tkt-rchip">' + escapeHtml(i.label || i.name || i) + '</span>';
        }).join('');

        // "45 min" por debajo de la hora, "2h 15m" a partir de ahí — mismo
        // criterio de legibilidad que el resto de duraciones del panel.
        var firstResponse = '—';
        if (customer.avg_first_response_minutes != null) {
            var mins = Math.round(customer.avg_first_response_minutes);
            firstResponse = mins < 60 ? (mins + ' min') : (Math.floor(mins / 60) + 'h ' + (mins % 60) + 'm');
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-address-card', kicker: 'Cliente · 360',
            titleChip: customer.name || '',
            title: 'Cliente 360', width: 'sm',
            body: '<div class="tkt-headline"><div class="t">' + escapeHtml(customer.name || '—') + '</div>' +
                    '<div class="s">' + escapeHtml(customer.company || 'Sin empresa asociada') + '</div></div>' +
                  '<div class="tkt-side-rows">' +
                    sideRow('email', customer.email || '—', { mono: true }) +
                    sideRow('teléfono', customer.phone || '—', { mono: true }) +
                    sideRow('cliente desde', customer.customer_since_year || '—', { mono: true }) +
                    sideRow('idioma', customer.language || '—', { mono: true }) +
                    sideRow('id externo', customer.external_id || '—', { mono: true, last: true }) +
                  '</div>' +
                  (integraciones ? '<div class="tkt-cap">Integraciones</div><div class="tkt-chiprow">' + integraciones + '</div>' : '') +
                  '<div class="tkt-cap">Historial de soporte</div>' +
                  '<div class="tkt-stats three">' +
                    '<div class="tkt-stat"><span class="n">' + (customer.tickets_count != null ? customer.tickets_count : '—') + '</span><span class="l">tickets</span></div>' +
                    '<div class="tkt-stat"><span class="n">' + (customer.avg_csat != null ? customer.avg_csat : '—') + '</span><span class="l">CSAT medio</span></div>' +
                    '<div class="tkt-stat"><span class="n">' + escapeHtml(firstResponse) + '</span><span class="l">1ª respuesta</span></div>' +
                  '</div>' +
                  (customer.is_banned ? '<div class="tkt-note"><i class="fa-solid fa-ban"></i> Este contacto está bloqueado.</div>' : '') +
                  '<div id="tkt-c360-orders"></div>',
            foot: (customer.url_c360 ? '<a class="tkt-btn tkt-btn-primary" href="' + escapeHtml(customer.url_c360) + '">Abrir ficha completa</a>' : '') +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        // Pedidos PrestaShop: bajo demanda (llama al bridge en vivo), no
        // viaja con los datos ya cargados del ticket.
        if (t.url_customer_orders) {
            $.getJSON(t.url_customer_orders).done(function (resp) {
                if (!resp || !resp.available || !resp.orders || !resp.orders.length) return;
                var rows = resp.orders.map(function (o) {
                    return '<div class="tkt-mailitem"><span class="av light"><i class="fa-solid fa-bag-shopping"></i></span>' +
                        '<span class="who"><span class="n">' + escapeHtml(o.reference || '—') + '</span>' +
                        '<span class="s">' + escapeHtml(o.state || '') + (o.placed_at ? ' · ' + escapeHtml(formatDateShort(o.placed_at)) : '') + '</span></span>' +
                        (o.total != null ? '<span class="mono">' + escapeHtml(String(o.total)) + ' ' + escapeHtml(o.currency_sign || '€') + '</span>' : '') +
                        '</div>';
                }).join('');
                $backdrop.find('#tkt-c360-orders').html('<div class="tkt-cap">Pedidos PrestaShop</div><div class="tkt-mailitems">' + rows + '</div>');
            });
        }
    }

    // ── Modal 34: Identidades del cliente ─────────────────────
    function openIdentitiesModal(t, identities) {
        var list = identities || [];
        var rows = list.length
            ? list.map(function (idn) {
                return '<div class="tkt-mailitem"><span class="av light"><i class="' + escapeHtml(idn.icon) + '"></i></span>' +
                    '<span class="who"><span class="n">' + escapeHtml(idn.value) + '</span>' +
                    '<span class="s">' + escapeHtml(idn.label + ' · ' + idn.source +
                        (idn.hits ? ' · ' + idn.hits + (idn.hits === 1 ? ' correo' : ' correos') : '')) + '</span></span>' +
                    (idn.is_primary ? '<span class="tkt-rchip ok">Principal</span>' : '') + '</div>';
              }).join('')
            : '<div class="tkt-empty-box">Este contacto no tiene ningún canal registrado todavía.</div>';

        openModal(modalShell({
            icon: 'fa-solid fa-fingerprint', kicker: 'Cliente · identidad',
            title: 'Identidades del cliente', width: 'sm',
            body: '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Un mismo cliente puede escribir por formulario, email, WhatsApp o portal: todo se une bajo una sola ficha.</div>' +
                '<div class="tkt-mailitems">' + rows + '</div>' +
                '<div class="tkt-note"><i class="fa-solid fa-triangle-exclamation"></i> Las direcciones marcadas como "detectado en el hilo" no están en la ficha: si son del mismo cliente, conviene unificarlas desde el contacto.</div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));
    }

    // ── Modal 26: Resumen IA del hilo ─────────────────────────
    function openAiSummaryModal(t) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-wand-magic-sparkles', iconClass: 'ok', kicker: 'IA · resumen',
            title: 'Resumen IA del hilo', titleChip: t.ticket_number, width: 'sm',
            body: '<div id="tkt-sum-body"><div class="tkt-skeleton"></div></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-sum-copy" disabled>Copiar resumen</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-sum-regen">Regenerar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));
        var current = null;

        function load() {
            $backdrop.find('#tkt-sum-body').html('<div class="tkt-skeleton"></div>');
            $backdrop.find('#tkt-sum-copy').prop('disabled', true);
            $.getJSON(t.url_summary).done(function (res) {
                if (!res || !res.summary) {
                    // El servicio devuelve null si no hay clave de API o no hay
                    // contexto suficiente: se dice, no se inventa un resumen.
                    $backdrop.find('#tkt-sum-body').html('<div class="tkt-empty-box">No hay resumen disponible para este hilo. El servicio de IA no está configurado o el hilo es demasiado corto.</div>');
                    return;
                }
                current = res.summary;
                $backdrop.find('#tkt-sum-body').html(
                    '<div class="tkt-tpl-preview">' + escapeHtml(res.summary) + '</div><div class="tkt-side-rows">' +
                        sideRow('mensajes resumidos', String(res.messages_count != null ? res.messages_count : '—'), { mono: true }) +
                        sideRow('idioma detectado', res.language || '—', { mono: true, last: true }) + '</div>');
                $backdrop.find('#tkt-sum-copy').prop('disabled', false);
            }).fail(function () {
                $backdrop.find('#tkt-sum-body').html('<div class="tkt-empty-box">No se pudo generar el resumen.</div>');
            });
        }

        $backdrop.on('click', '#tkt-sum-regen', load);
        $backdrop.on('click', '#tkt-sum-copy', function () {
            if (!current || !navigator.clipboard) return;
            navigator.clipboard.writeText(current).then(function () {
                if (window.toastr) toastr.success('Resumen copiado');
            });
        });
        load();
    }

    // ── Modal 27: Etiquetado automático ───────────────────────
    function openAiTaggingModal(t, suggestion) {
        if (!suggestion) { if (window.toastr) toastr.info('No hay sugerencias de clasificación para este ticket'); return; }
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-tags', kicker: 'IA · clasificación',
            title: 'Etiquetado automático', titleChip: t.ticket_number, width: 'sm',
            body: '<div class="tkt-side-rows">' +
                    sideRow('Categoría sugerida', (suggestion.category && suggestion.category.name) || '—', { strong: true }) +
                    sideRow('Prioridad sugerida', suggestion.priority ? priorityLabel(suggestion.priority) : '—', { strong: true, last: true }) +
                  '</div><div class="tkt-side-rows">' +
                    sideRow('Categoría actual', t.category_name || '—') +
                    sideRow('Prioridad actual', priorityLabel(t.priority), { last: true }) +
                  '</div>' +
                  '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> La clasificación se calcula al entrar el correo, antes de asignar agente. Aplicarla sobrescribe los valores actuales.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-tag-apply">Aplicar sugerencias</button>' +
                  '<a class="tkt-btn" href="' + escapeHtml(TKA.urls.automationsIndex || '#') + '">Ajustar reglas</a>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('click', '#tkt-tag-apply', function () {
            var $btn = $(this).prop('disabled', true).text('Aplicando…');
            $.ajax({
                url: t.url_ai_apply, method: 'POST', headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Sugerencias aplicadas');
                    closeModal(); window.location.reload();
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudieron aplicar las sugerencias';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text('Aplicar sugerencias');
                },
            });
        });
    }

    // ── Modal 32: Portal del cliente ──────────────────────────
    function openPortalModal(t) {
        var d = TKA.state.currentDetail;
        var thread = (d && d.thread) || [];
        var visible = thread.filter(function (m) { return !m.is_internal; });
        var bubbles = visible.length
            ? visible.map(function (m) {
                var body = String(m.body || '').replace(/<[^>]+>/g, '').trim();
                return '<div class="tkt-portal-msg' + (m.from_agent ? ' agent' : '') + '">' +
                    '<div class="who">' + escapeHtml(m.from_agent ? 'Soporte' : (t.customer ? t.customer.name : 'Cliente')) +
                        ' · ' + escapeHtml(m.created_at_human || '') + '</div>' +
                    '<div class="body">' + escapeHtml(body || '(sin texto)') + '</div></div>';
              }).join('')
            : '<div class="tkt-empty-box">El cliente todavía no vería ningún mensaje en este ticket.</div>';
        var hidden = thread.length - visible.length;

        openModal(modalShell({
            icon: 'fa-regular fa-window-maximize', kicker: 'Portal · vista cliente',
            title: 'Portal del cliente', titleChip: t.ticket_number, width: 'sm',
            body: '<div class="tkt-portal"><div class="tkt-portal-head">Ticket ' + escapeHtml(t.ticket_number) + ' · ' + escapeHtml(t.subject || '') + '</div>' + bubbles + '</div>' +
                  (hidden > 0 ? '<div class="tkt-note"><i class="fa-solid fa-eye-slash"></i> ' + hidden + (hidden === 1 ? ' nota interna' : ' notas internas') + ' no se muestran al cliente.</div>' : ''),
            foot: (t.url_shared_ticket ? '<a class="tkt-btn tkt-btn-primary" href="' + escapeHtml(t.url_shared_ticket) + '" target="_blank" rel="noopener">Abrir la vista real</a>' : '') +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));
    }

    // Los modales 16/28/29/30 leen la misma foto de configuración: se pide
    // una vez y se guarda, en vez de cuatro veces seguidas al abrirlos.
    var settingsSnapshot = null;

    function withSettings(cb) {
        if (settingsSnapshot) { cb(settingsSnapshot); return; }
        if (!TKA.urls.settingsSnapshot) { cb(null); return; }
        $.getJSON(TKA.urls.settingsSnapshot).done(function (d) { settingsSnapshot = d; cb(d); }).fail(function () { cb(null); });
    }

    // ── Modal 16: Buzones de entrada ──────────────────────────
    // ── Modal 16: Buzones de entrada ──────────────────────────

    // "hace 2 min" del mockup. Se calcula en el navegador desde el ISO y no con
    // diffForHumans en el servidor porque la app corre con locale 'en' por
    // defecto en Docker y devolvería "2 minutes ago" en una pantalla en español.
    function mbxSince(iso) {
        if (!iso) return null;
        var ts = new Date(iso).getTime();
        if (isNaN(ts)) return null;
        var secs = Math.round((Date.now() - ts) / 1000);
        if (secs < 60) return 'hace un momento';
        var mins = Math.round(secs / 60);
        if (mins < 60) return 'hace ' + mins + ' min';
        var hours = Math.round(mins / 60);
        if (hours < 24) return 'hace ' + hours + ' h';
        var days = Math.round(hours / 24);
        if (days < 30) return 'hace ' + days + (days === 1 ? ' día' : ' días');
        return formatDateShort(iso);
    }

    // Estado del buzón, derivado SOLO de datos reales: los dos interruptores
    // (con ambos apagados FetchTicketEmailsJob se salta el buzón entero),
    // last_error y last_checked_at. Nada de "conectado" por defecto.
    function mbxState(b) {
        var proto = (b.encryption ? String(b.encryption).toUpperCase() + ' · ' : '') + 'IMAP';
        if (!b.create_tickets && !b.create_replies) return { text: 'En pausa · ' + proto, cls: 'idle' };
        if (b.last_error) return { text: 'Con error · ' + proto, cls: 'err' };
        if (!b.last_checked_at) return { text: 'Sin lecturas · ' + proto, cls: 'idle' };
        return { text: 'Conectado · ' + proto, cls: 'ok' };
    }

    function openMailboxesModal() {
        var boxes = [];
        var canManage = false;
        var currentId = null;

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-inbox', kicker: 'Ajustes · correo',
            title: 'Buzones de entrada', width: 'sm',
            body: '<div id="tkt-mbx-body"><div class="tkt-skeleton"></div></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-mbx-save" disabled>Guardar</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-mbx-test" disabled>Probar conexión</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        function current() {
            return boxes.filter(function (b) { return String(b.id) === String(currentId); })[0] || null;
        }

        // Enlace a la pantalla completa: alta, credenciales, SMTP y borrado NO se
        // tocan desde aquí a propósito (este modal solo cambia comportamiento).
        function moreLink(label) {
            if (!TKA.urls.emailChannels) return '';
            return '<div class="tkt-mbx-more"><a href="' + escapeHtml(TKA.urls.emailChannels) + '">' + escapeHtml(label) + '</a></div>';
        }

        function render() {
            var $body = $backdrop.find('#tkt-mbx-body');

            if (!boxes.length) {
                $backdrop.find('#tkt-mbx-save, #tkt-mbx-test').prop('disabled', true);
                $body.html('<div class="tkt-empty-box">No hay ningún buzón de correo entrante configurado.</div>' +
                    moreLink('Configurar buzones'));
                return;
            }

            if (!current()) currentId = boxes[0].id;
            var b = current();
            var st = mbxState(b);

            var options = boxes.map(function (m) {
                var label = m.name || m.username || 'Buzón';
                if (m.name && m.username) label = m.name + ' — ' + m.username;
                return '<option value="' + escapeHtml(String(m.id)) + '"' +
                    (String(m.id) === String(currentId) ? ' selected' : '') + '>' + escapeHtml(label) + '</option>';
            }).join('');

            var imap = [b.host, b.port].filter(Boolean).join(':') || '—';
            if (b.encryption) imap += ' · ' + String(b.encryption).toUpperCase();

            var smtp = [b.smtp_host, b.smtp_port].filter(Boolean).join(':');
            if (smtp && b.smtp_encryption) smtp += ' · ' + String(b.smtp_encryption).toUpperCase();

            var lastRead = mbxSince(b.last_checked_at) || 'sin lecturas todavía';

            $body.html(
                '<div class="tkt-field-row">' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-mbx-pick">Buzón</label>' +
                        '<select class="tkt-select" id="tkt-mbx-pick">' + options + '</select></div>' +
                    '<div class="tkt-field"><label class="tkt-label">Estado</label>' +
                        '<div class="tkt-mbx-state ' + st.cls + '">' + escapeHtml(st.text) + '</div></div>' +
                '</div>' +
                '<div class="tkt-side-card"><div class="tkt-side-card-body tight">' +
                    sideRow('IMAP', imap, { mono: true }) +
                    // La fila SMTP solo aparece si el canal tiene servidor de
                    // salida propio; sin él las respuestas salen por el mailer
                    // global y enseñar "—" haría pensar que está mal configurado.
                    (smtp ? sideRow('SMTP', smtp, { mono: true }) : '') +
                    sideRow('Última lectura', lastRead, { last: !b.last_error }) +
                    (b.last_error ? sideRow('Último error', String(b.last_error).slice(0, 120), { mono: true, last: true }) : '') +
                '</div></div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-mbx-tickets"' +
                    (b.create_tickets ? ' checked' : '') + (canManage ? '' : ' disabled') + '> Crear tickets con los correos entrantes</label>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-mbx-replies"' +
                    (b.create_replies ? ' checked' : '') + (canManage ? '' : ' disabled') + '> Añadir respuestas al ticket existente</label>' +
                (canManage ? '' : '<div class="tkt-note">Solo lectura: hace falta el permiso de ajustes de tickets para cambiar el comportamiento de un buzón.</div>') +
                '<div id="tkt-mbx-result"></div>' +
                moreLink('Configurar buzones (credenciales, alta y borrado)')
            );

            $backdrop.find('#tkt-mbx-save').prop('disabled', !canManage);
            $backdrop.find('#tkt-mbx-test').prop('disabled', !canManage);
        }

        function note(cls, text) {
            $backdrop.find('#tkt-mbx-result').html('<div class="tkt-note ' + cls + '">' + escapeHtml(text) + '</div>');
        }

        // Solo lectura con lo que ya sirve settings-snapshot: si las rutas nuevas
        // todavía no están registradas, el modal sigue enseñando los buzones en
        // vez de quedarse en blanco (mismos nombres de campo en las dos fuentes).
        function loadFromSnapshot() {
            withSettings(function (d) {
                boxes = (d && d.mailboxes) || [];
                canManage = false;
                render();
            });
        }

        if (TKA.urls.mailboxes) {
            $.getJSON(TKA.urls.mailboxes)
                .done(function (d) {
                    boxes = (d && d.mailboxes) || [];
                    canManage = !!(d && d.can_manage);
                    render();
                })
                .fail(loadFromSnapshot);
        } else {
            loadFromSnapshot();
        }

        $backdrop.on('change', '#tkt-mbx-pick', function () {
            currentId = $(this).val();
            render();
        });

        $backdrop.on('click', '#tkt-mbx-save', function () {
            var b = current();
            if (!b || !TKA.urls.mailboxBehaviorTemplate) return;

            var tickets = $backdrop.find('#tkt-mbx-tickets').is(':checked');
            var replies = $backdrop.find('#tkt-mbx-replies').is(':checked');
            var $btn = $(this).prop('disabled', true).text('Guardando…');

            $.ajax({
                // POST y no PUT: un PUT real por AJAX devuelve 405 en este entorno
                // Docker aunque route:list lo registre.
                url: TKA.urls.mailboxBehaviorTemplate.replace('__MBX__', encodeURIComponent(b.id)),
                method: 'POST',
                data: { create_tickets: tickets ? 1 : 0, create_replies: replies ? 1 : 0 },
                headers: { Accept: 'application/json' },
                success: function (res) {
                    var msg = (res && res.message) || 'Buzón guardado.';
                    if (window.toastr) toastr.success(msg); else window.alert(msg);
                    if (res && res.mailbox) {
                        boxes = boxes.map(function (m) { return String(m.id) === String(b.id) ? res.mailbox : m; });
                    }
                    render();
                    // Apagar los dos interruptores deja el buzón sin leer: el
                    // aviso se queda en el modal, no solo en un toast que se va.
                    if (!tickets && !replies) note('warn', msg);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo guardar el buzón.';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
                complete: function () {
                    $btn.prop('disabled', !canManage).text('Guardar');
                },
            });
        });

        $backdrop.on('click', '#tkt-mbx-test', function () {
            var b = current();
            if (!b || !TKA.urls.mailboxTestTemplate) return;

            var $btn = $(this).prop('disabled', true).text('Probando…');
            note('', 'Conectando con ' + [b.host, b.port].filter(Boolean).join(':') + '…');

            $.ajax({
                url: TKA.urls.mailboxTestTemplate.replace('__MBX__', encodeURIComponent(b.id)),
                method: 'POST',
                headers: { Accept: 'application/json' },
                success: function (res) {
                    note('ok', (res && res.message) || 'El servidor acepta conexiones.');
                },
                error: function (xhr) {
                    // La prueba es un chequeo TCP: no valida credenciales, así que
                    // el fallo se cuenta tal cual lo devuelve el servidor.
                    note('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo probar la conexión.');
                },
                complete: function () {
                    $btn.prop('disabled', !canManage).text('Probar conexión');
                },
            });
        });
    }

    // ── Modal 21: Editor de plantilla ─────────────────────────
    function openTemplateEditorModal(reply, onSaved) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-pen-to-square', kicker: 'Plantillas · editar',
            titleChip: reply.title || '',
            title: 'Editor de plantilla', width: 'lg',
            body: '<div class="tkt-field-row">' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-tpled-title">Nombre<span class="req">*</span></label>' +
                        '<input type="text" class="tkt-input" id="tkt-tpled-title" value="' + escapeHtml(reply.title || '') + '"></div>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-tpled-code">Atajo</label>' +
                        '<input type="text" class="tkt-input" id="tkt-tpled-code" value="' + escapeHtml(reply.short_code || '') + '" placeholder="/doc"></div>' +
                  '</div>' +
                  '<div class="tkt-field"><label class="tkt-label" for="tkt-tpled-body">Contenido<span class="req">*</span>' +
                    '<span class="hint">variables: {{cliente}} {{ticket}} {{agente}}</span></label>' +
                    '<textarea class="tkt-input" id="tkt-tpled-body" style="min-height:160px">' + escapeHtml(reply.content || '') + '</textarea></div>' +
                  '<div class="tkt-cap">Vista previa</div>' +
                  '<div class="tkt-tpl-preview" id="tkt-tpled-preview">' + escapeHtml(reply.content || '') + '</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-tpled-save">Guardar plantilla</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-tpled-back">Volver</button>',
        }));

        $backdrop.on('input', '#tkt-tpled-body', function () { $backdrop.find('#tkt-tpled-preview').text(this.value); });

        $backdrop.on('click', '#tkt-tpled-save', function () {
            var title = ($('#tkt-tpled-title').val() || '').trim();
            var content = ($('#tkt-tpled-body').val() || '').trim();
            if (!title || !content) { if (window.toastr) toastr.error('El nombre y el contenido son obligatorios'); return; }
            var $btn = $(this).prop('disabled', true).text('Guardando…');
            $.ajax({
                // POST + _method=PUT: un PUT real por AJAX devuelve 405 en este
                // entorno Docker aunque route:list lo registre.
                url: TKA.urls.cannedUpdateTemplate.replace('__REPLY__', reply.id),
                method: 'POST',
                data: { _method: 'PUT', title: title, content: content, short_code: ($('#tkt-tpled-code').val() || '').trim() || null },
                headers: { Accept: 'application/json' },
                success: function () {
                    if (window.toastr) toastr.success('Plantilla guardada');
                    var local = (TKA.state.cannedReplies || []).find(function (r) { return String(r.id) === String(reply.id); });
                    if (local) { local.title = title; local.content = content; }
                    closeModal(); if (onSaved) onSaved();
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo guardar la plantilla';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text('Guardar plantilla');
                },
            });
        });
        $backdrop.on('click', '#tkt-tpled-back', function () { closeModal(); if (onSaved) onSaved(); });
    }

    // ── Modal 28: Calendario y SLA ────────────────────────────
    // Etiqueta de un plazo en minutos. Los objetivos van de "15 min" a "5 d", así
    // que una sola unidad fija (todo en horas) o bien pierde precisión o bien pinta
    // "120 h" donde el agente espera "5 d".
    function slaFmtMinutes(m) {
        if (m == null) return '—';
        m = Number(m);
        if (!isFinite(m) || m < 0) return '—';
        if (m < 60) return m + ' min';
        if (m < 1440) {
            var h = Math.round(m / 60 * 10) / 10;
            return h + ' h';
        }
        var d = Math.floor(m / 1440);
        var restH = Math.round((m % 1440) / 60);
        return d + ' d' + (restH ? ' ' + restH + ' h' : '');
    }

    function slaFmtHours(h) {
        return h == null ? '—' : slaFmtMinutes(Number(h) * 60);
    }

    // Vencimiento: fecha corta + "dentro de / hace" que ya calcula el backend.
    function slaDueText(row) {
        if (!row || !row.at) return 'sin plazo';
        return formatDateShort(row.at) + (row.human ? ' · ' + row.human : '');
    }

    function openSlaCalendarModal() {
        var ticket = null;
        if (TKA.state.selected) {
            ticket = (TKA.state.tickets || []).find(function (x) { return x.id === TKA.state.selected; }) || null;
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-calendar',
            kicker: 'SLA · calendario',
            title: 'Calendario y SLA',
            titleChip: ticket ? ticket.ticket_number : null,
            width: 'xl',
            body: '<div id="tkt-slacal-body"><div class="tkt-skeleton"></div><div class="tkt-skeleton"></div></div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        if (!TKA.urls.slaCalendar) {
            $backdrop.find('#tkt-slacal-body').html('<div class="tkt-empty-box">El calendario de SLA no está disponible en esta pantalla.</div>');
            return;
        }

        var params = ticket ? { ticket: ticket.id } : {};

        $.getJSON(TKA.urls.slaCalendar, params).done(function (d) {
            renderSlaCalendar($backdrop, d);
        }).fail(function () {
            $backdrop.find('#tkt-slacal-body').html('<div class="tkt-note danger"><i class="fa-solid fa-triangle-exclamation"></i> No se ha podido leer la configuración de SLA.</div>');
        });
    }

    function renderSlaCalendar($backdrop, d) {
        d = d || {};

        var panes = [];
        if (d.ticket) panes.push({ key: 'ticket', label: 'Este ticket', html: slaPaneTicket(d.ticket) });
        panes.push({ key: 'targets', label: 'Objetivos', html: slaPaneTargets(d) });
        panes.push({ key: 'hours', label: 'Horario y pausas', html: slaPaneHours(d) });

        $backdrop.find('#tkt-slacal-body').html(
            '<div class="tkt-seg-tabs" id="tkt-slacal-tabs">' +
                panes.map(function (p, i) {
                    return '<button type="button" class="' + (i === 0 ? 'on' : '') + '" data-slacal-tab="' + p.key + '">' + escapeHtml(p.label) + '</button>';
                }).join('') +
            '</div>' +
            panes.map(function (p, i) {
                return '<div class="tkt-slacal-pane' + (i === 0 ? ' on' : '') + '" data-slacal-pane="' + p.key + '">' + p.html + '</div>';
            }).join('')
        );

        // Pie contextual: el botón principal lleva a donde se editan los plazos,
        // que es la acción que sigue al 90 % de las visitas a este modal.
        if (d.links && d.links.sla_policies) {
            $backdrop.find('.tkt-modal-foot').prepend(
                '<a class="tkt-btn tkt-btn-primary" href="' + escapeHtml(d.links.sla_policies) + '">Editar políticas de SLA</a>'
            );
        }

        $backdrop.on('click', '[data-slacal-tab]', function () {
            var key = $(this).data('slacal-tab');
            $backdrop.find('[data-slacal-tab]').removeClass('on');
            $(this).addClass('on');
            $backdrop.find('[data-slacal-pane]').removeClass('on');
            $backdrop.find('[data-slacal-pane="' + key + '"]').addClass('on');
        });

        bindSlaPauseToggles($backdrop, d);
    }

    // ── Pestaña 1: el reloj de ESTE ticket ────────────────────
    function slaPaneTicket(t) {
        var head = t.paused
            ? '<div class="tkt-headline bad">' +
                  '<div class="t">Reloj de SLA pausado</div>' +
                  '<div class="s">Pausado ' + escapeHtml(t.paused_since_human || '') +
                      ' · ' + slaFmtMinutes(t.current_pause_minutes) + ' de esta pausa' +
                      (t.accumulated_pause_minutes ? ' · ' + slaFmtMinutes(t.accumulated_pause_minutes) + ' acumulados antes' : '') +
                  '</div>' +
              '</div>'
            : '<div class="tkt-headline">' +
                  '<div class="t">Reloj de SLA en marcha</div>' +
                  '<div class="s">' + (t.accumulated_pause_minutes
                      ? slaFmtMinutes(t.accumulated_pause_minutes) + ' pausados en total hasta ahora'
                      : 'Nunca se ha pausado') + '</div>' +
              '</div>';

        // Sin política no hay vencimientos: decirlo es más útil que pintar tres
        // filas con guiones, que se leen como "aún no ha vencido".
        if (!t.policy) {
            head += '<div class="tkt-note warn"><i class="fa-solid fa-circle-info"></i> Este ticket no tiene ninguna política de SLA asignada, así que no tiene plazos que vigilar.</div>';
        }

        var rows =
            sideRow('política', t.policy ? t.policy.name : 'ninguna') +
            sideRow('prioridad', t.priority_label || '—') +
            sideRow('estado', (t.status ? t.status.name : '—') + (t.status && t.status.stops_sla ? ' · pausa el reloj' : ''), { last: true });

        var dueRows = '';
        if (t.due) {
            dueRows =
                '<div class="tkt-cap">Vencimientos</div>' +
                '<div class="tkt-side-rows">' +
                    sideRow('1ª respuesta', t.first_response_at
                        ? 'respondida ' + formatDateShort(t.first_response_at)
                        : slaDueText(t.due.first_response) + (t.due.first_response.breached ? ' · incumplido' : ''), { mono: true }) +
                    sideRow('siguiente respuesta', slaDueText(t.due.next_response) + (t.due.next_response.breached ? ' · incumplido' : ''), { mono: true }) +
                    sideRow('resolución', slaDueText(t.due.resolution) + (t.due.resolution.breached ? ' · incumplido' : ''), { mono: true, last: !t.paused }) +
                    // El vencimiento efectivo solo se separa del nominal mientras
                    // hay una pausa en curso: fuera de ese caso repetir la fila
                    // sería ruido.
                    (t.paused && t.effective_resolution_due_at
                        ? sideRow('resolución (con la pausa)', formatDateShort(t.effective_resolution_due_at), { mono: true, strong: true, last: true })
                        : '') +
                '</div>';
        }

        return head + '<div class="tkt-side-rows">' + rows + '</div>' + dueRows;
    }

    // ── Pestaña 2: objetivos por prioridad ────────────────────
    // "1ª respuesta / resolución" de una política, con la unidad que corresponda:
    // minutos si son los que lee el reloj, horas declaradas si es lo único que hay.
    function slaPolicyPair(p) {
        return p.clock_enforced
            ? slaFmtMinutes(p.first_response_minutes) + ' / ' + slaFmtMinutes(p.resolution_minutes)
            : slaFmtHours(p.declared_hours.first_response) + ' / ' + slaFmtHours(p.declared_hours.resolution);
    }

    function slaPaneTargets(d) {
        var list = d.policies || [];

        if (!list.length) {
            return '<div class="tkt-empty-box">No hay ninguna política de SLA activa.</div>';
        }

        var out = '';

        // Resumen "Objetivos por prioridad" del mockup. Sale de la columna
        // `priority` de las propias políticas (cada una está etiquetada con una),
        // no de un cálculo inventado; las políticas sin prioridad asignada no
        // aparecen aquí, solo en su ficha de abajo.
        var conPrioridad = list.filter(function (p) { return !!p.priority_label; });

        if (conPrioridad.length) {
            out += '<div class="tkt-cap">Objetivos por prioridad</div>' +
                '<div class="tkt-side-rows">' +
                    conPrioridad.map(function (p, i) {
                        return sideRow(p.priority_label, slaPolicyPair(p), { mono: true, last: i === conPrioridad.length - 1 });
                    }).join('') +
                '</div>' +
                '<div class="tkt-modal-note">1ª respuesta / resolución, según la política de cada prioridad.</div>';
        }

        // El aviso de "estos plazos no los aplica el reloj" va UNA vez por panel:
        // repetirlo en las cuatro fichas (que es el caso real hoy) lo convierte en
        // ruido y deja de leerse.
        var sinEfecto = list.filter(function (p) { return !p.clock_enforced; });

        if (sinEfecto.length) {
            out += '<div class="tkt-note warn"><i class="fa-solid fa-triangle-exclamation"></i> ' +
                (sinEfecto.length === list.length ? 'Ninguna de estas políticas fija' : sinEfecto.length + ' de estas políticas no fijan') +
                ' vencimientos: tienen los plazos en las columnas heredadas (en horas) y el cálculo lee las de minutos, que están vacías. ' +
                'Vuelve a guardarlas desde la pantalla de políticas para que empiecen a contar.</div>';
        }

        return out + list.map(function (p) {
            var chips = '';
            if (p.is_default) chips += '<span class="tkt-rchip ok">predeterminada</span> ';
            if (p.priority_label) chips += '<span class="tkt-rchip">' + escapeHtml(p.priority_label) + '</span> ';
            if (p.channel) chips += '<span class="tkt-rchip">' + escapeHtml(p.channel) + '</span> ';
            if (!p.clock_enforced) chips += '<span class="tkt-rchip strong">sin efecto</span>';

            var body;

            if (p.clock_enforced) {
                // Objetivos base + los efectivos por prioridad, que es el mismo
                // cálculo que hace el motor (base × multiplicador).
                body =
                    '<div class="tkt-side-rows">' +
                        sideRow('1ª respuesta', slaFmtMinutes(p.first_response_minutes), { mono: true }) +
                        sideRow('respuestas siguientes', slaFmtMinutes(p.next_response_minutes), { mono: true }) +
                        sideRow('resolución', slaFmtMinutes(p.resolution_minutes), { mono: true, last: true }) +
                    '</div>' +
                    (p.targets_by_priority && p.targets_by_priority.length
                        ? '<div class="tkt-cap tkt-slacal-cap">Por prioridad · 1ª respuesta / resolución</div>' +
                          '<div class="tkt-side-rows">' +
                              p.targets_by_priority.map(function (row, i) {
                                  return sideRow(
                                      row.label + ' (×' + row.multiplier + ')',
                                      slaFmtMinutes(row.first_response_minutes) + ' / ' + slaFmtMinutes(row.resolution_minutes),
                                      { mono: true, last: i === p.targets_by_priority.length - 1 }
                                  );
                              }).join('') +
                          '</div>'
                        : '');
            } else {
                // Caso real de esta instalación: la política declara horas en las
                // columnas heredadas y el motor lee las de minutos, que están
                // vacías. El "(declarada)" de cada etiqueta es lo que evita leer
                // "resolución 24 h" como un plazo que se aplica; el porqué está en
                // el aviso único de la cabecera del panel.
                body =
                    '<div class="tkt-side-rows">' +
                        sideRow('1ª respuesta (declarada)', slaFmtHours(p.declared_hours.first_response), { mono: true }) +
                        sideRow('respuestas siguientes (declarada)', slaFmtHours(p.declared_hours.next_response), { mono: true }) +
                        sideRow('resolución (declarada)', slaFmtHours(p.declared_hours.resolution), { mono: true, last: true }) +
                    '</div>';
            }

            // Horario propio de la política: es el ÚNICO que usa el reloj de
            // tickets. Va aquí, en la política, y no en la pestaña de horario, que
            // muestra el calendario de la empresa (otro alcance).
            var hoursText;
            if (!p.business_hours_only) {
                hoursText = 'cuenta 24/7';
            } else if (p.business_hours) {
                hoursText = 'horario propio definido';
            } else {
                hoursText = 'L-V 09:00–17:00 (por defecto del cálculo)';
            }

            return '<div class="tkt-side-card">' +
                '<div class="tkt-side-card-head">' + escapeHtml(p.name) + '<span class="tkt-spacer">' + chips + '</span></div>' +
                '<div class="tkt-side-card-body">' +
                    body +
                    '<div class="tkt-side-rows">' +
                        sideRow('horario', hoursText, { mono: true }) +
                        sideRow('zona horaria', p.timezone, { mono: true, last: !p.enable_escalation }) +
                        (p.enable_escalation
                            ? sideRow('escala al', (p.escalation_threshold_percent || 0) + ' % consumido', { mono: true, last: true })
                            : '') +
                    '</div>' +
                '</div>' +
            '</div>';
        }).join('');
    }

    // ── Pestaña 3: horario de la empresa, festivos y pausas ───
    function slaPaneHours(d) {
        var bh = d.business_hours || {};
        var hol = d.holidays || {};
        var pause = d.pause || {};
        var links = d.links || {};
        var out = '';

        // ── Pausa automática al esperar al cliente ──
        // Es el único ajuste editable del modal: el flag stops_sla_timer del
        // catálogo de estados, que es lo que dispara pauseSla()/resumeSla().
        out += '<div class="tkt-cap">Pausar el SLA al esperar al cliente</div>';

        // Los estados cerrados (Resuelto/Cerrado) no entran: un ticket cerrado ya
        // está fuera del control de SLA (checkBreaches filtra por closed_at), así
        // que ofrecer ahí el interruptor solo añade ruido. Excepción: si alguno lo
        // tiene puesto de verdad, se muestra — ocultar estado real sería peor.
        var todos = pause.statuses || [];
        var statuses = todos.filter(function (s) { return !s.is_closed || s.stops_sla; });
        var ocultos = todos.length - statuses.length;

        if (!statuses.length) {
            out += '<div class="tkt-empty-box">No hay estados en el catálogo.</div>';
        } else {
            var alguno = statuses.some(function (s) { return s.stops_sla; });

            out += '<div class="tkt-side-rows tkt-slacal-toggles">' +
                statuses.map(function (s) {
                    return '<button type="button" class="tkt-toggle-row tkt-slacal-toggle' + (s.stops_sla ? ' on' : '') + '"' +
                            ' data-status-id="' + s.id + '" data-stops="' + (s.stops_sla ? '1' : '0') + '"' +
                            (pause.can_manage ? '' : ' disabled') + '>' +
                            '<i class="fa-solid ' + (s.stops_sla ? 'fa-toggle-on' : 'fa-toggle-off') + '"></i>' +
                            '<span class="n">' + escapeHtml(s.name) + '</span>' +
                            '<span class="s">' + (s.stops_sla ? 'pausa' : 'no pausa') + '</span>' +
                        '</button>';
                }).join('') +
            '</div>';

            // Siempre en el HTML (oculto si ya hay alguno encendido) para que el
            // toggle pueda mostrarlo/ocultarlo sin repintar el panel entero.
            out += '<div class="tkt-note warn tkt-slacal-nopause' + (alguno ? ' off' : '') + '">' +
                '<i class="fa-solid fa-triangle-exclamation"></i> Ningún estado pausa el reloj ahora mismo: el SLA sigue corriendo también mientras se espera al cliente.</div>';

            out += '<div class="tkt-modal-note">Se aplica a los próximos cambios de estado; los tickets que ya están en ese estado no se pausan hacia atrás. ' +
                'La reanudación es automática al salir del estado o al responder el cliente por el portal, y devuelve a los plazos el tiempo esperado.' +
                (ocultos ? ' No se lista' + (ocultos === 1 ? ' 1 estado de cierre, donde' : 'n ' + ocultos + ' estados de cierre, donde') + ' el SLA ya no corre.' : '') +
                '</div>';

            if (!pause.can_manage) {
                out += '<div class="tkt-note"><i class="fa-solid fa-lock"></i> Solo lectura: hace falta el permiso de ajustes del módulo para cambiarlo.</div>';
            } else if (links.statuses) {
                out += '<a class="tkt-btn tkt-w-100" href="' + escapeHtml(links.statuses) + '">Editar el catálogo de estados</a>';
            }
        }

        // ── Horario de atención de la empresa ──
        out += '<div class="tkt-cap tkt-slacal-cap">Horario de atención de la empresa</div>';

        if (!bh.configured) {
            out += '<div class="tkt-empty-box">No hay ningún horario de atención configurado.</div>';
        } else {
            out += '<div class="tkt-side-rows">' +
                (bh.days || []).map(function (day, i) {
                    return sideRow(
                        day.name,
                        day.is_open && day.opens_at ? day.opens_at + ' – ' + day.closes_at : 'cerrado',
                        { mono: true, last: i === bh.days.length - 1 }
                    );
                }).join('') +
            '</div>';

            // Alcance real: esta rejilla NO la mira el reloj de SLA de tickets
            // (cada política lleva el suyo, ver pestaña Objetivos). Decirlo evita
            // que alguien "arregle" un plazo tocando aquí.
            out += '<div class="tkt-modal-note">Zona horaria ' + escapeHtml(bh.timezone || '—') + '. ' +
                'Este calendario rige el SLA de conversaciones' +
                (bh.used_by_escalation ? ' y el escalado de tickets' : '') +
                '; los plazos de los tickets usan el horario de su propia política.</div>';
        }

        if (links.business_hours) {
            out += '<a class="tkt-btn tkt-w-100" href="' + escapeHtml(links.business_hours) + '">Editar el horario de atención</a>';
        }

        // ── Festivos ──
        if (hol.available !== false) {
            out += '<div class="tkt-cap tkt-slacal-cap">Festivos</div>';

            if (!hol.total) {
                out += '<div class="tkt-empty-box">No hay festivos dados de alta.</div>';
            } else if (!hol.upcoming || !hol.upcoming.length) {
                out += '<div class="tkt-empty-box">' + hol.total + ' festivos dados de alta, ninguno próximo.</div>';
            } else {
                out += '<div class="tkt-side-rows">' +
                    hol.upcoming.map(function (h, i) {
                        return sideRow(
                            h.name,
                            h.date + (h.is_recurring ? ' · anual' : ''),
                            { mono: true, last: i === hol.upcoming.length - 1 }
                        );
                    }).join('') +
                '</div>';

                if (hol.total > hol.upcoming.length) {
                    out += '<div class="tkt-modal-note">' + hol.total + ' festivos en total.</div>';
                }
            }

            if (hol.total && !hol.applies_to_ticket_sla) {
                out += '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Ninguna política activa cuenta en horas hábiles, así que los festivos no descuentan tiempo de los plazos de los tickets.</div>';
            }

            if (links.holidays) {
                out += '<a class="tkt-btn tkt-w-100" href="' + escapeHtml(links.holidays) + '">Ver festivos</a>';
            }
        }

        return out;
    }

    // Alterna stops_sla_timer de un estado sin salir del modal. Se repinta solo la
    // fila tocada con lo que devuelve el servidor, no todo el panel: el resto de
    // pestañas puede tener scroll y estado (pestaña activa) que se perdería.
    function bindSlaPauseToggles($backdrop, d) {
        if (!d.pause || !d.pause.can_manage || !TKA.urls.slaPauseStatus) return;

        $backdrop.on('click', '.tkt-slacal-toggle', function () {
            var $btn = $(this);
            if ($btn.prop('disabled')) return;

            var siguiente = $btn.data('stops') === 1 || $btn.data('stops') === '1' ? 0 : 1;

            $btn.prop('disabled', true);

            $.ajax({
                url: TKA.urls.slaPauseStatus,
                method: 'POST',
                headers: { Accept: 'application/json' },
                data: { status_id: $btn.data('status-id'), stops_sla_timer: siguiente },
                success: function (res) {
                    var stops = !!(res && res.status && res.status.stops_sla);
                    $btn.data('stops', stops ? 1 : 0)
                        .attr('data-stops', stops ? '1' : '0')
                        .toggleClass('on', stops)
                        .prop('disabled', false);
                    $btn.find('i').attr('class', 'fa-solid ' + (stops ? 'fa-toggle-on' : 'fa-toggle-off'));
                    $btn.find('.s').text(stops ? 'pausa' : 'no pausa');

                    // El aviso "ningún estado pausa el reloj" deja de ser cierto en
                    // cuanto se enciende uno.
                    var quedaAlguno = $backdrop.find('.tkt-slacal-toggle.on').length > 0;
                    $backdrop.find('.tkt-slacal-nopause').toggleClass('off', quedaAlguno);

                    if (window.toastr && res && res.message) toastr.success(res.message);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se ha podido cambiar la pausa del SLA';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false);
                },
            });
        });
    }

    // ── Modal 29: Reglas de escalado ──────────────────────────
    // Estado del editor mientras el modal está abierto. Guarda solo la ESTRUCTURA
    // de las filas (qué campo y qué operador tiene cada una); los valores se leen
    // del DOM al guardar, así repintar una fila no borra lo tecleado en las otras.
    var TKT_ESC = {
        canManage: false,
        rules: [],
        catalog: null,
        tab: 'list',
        draft: null,
        conds: [],
        acts: [],
        preview: null,
        errors: null,
    };

    // Las URLs pueden venir ya en TKA.urls (bootstrap) o directamente de los
    // data-* de #tkt-data. Se aceptan las dos para que el modal funcione aunque
    // el bootstrap todavía no las exponga.
    function escUrl(key, attr) {
        return TKA.urls[key] || $('#tkt-data').attr(attr) || null;
    }

    function openEscalationModal() {
        var listUrl = escUrl('automationsList', 'data-automations-list-url');

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-arrow-up-right-dots', kicker: 'Automatización · escalado',
            title: 'Reglas de escalado', width: 'sm',
            body: '<div id="tkt-esc-body"><div class="tkt-skeleton"></div></div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        // Sin endpoint (rutas aún no registradas) se conserva el comportamiento
        // anterior: listar lo que trae el settings-snapshot y salir a Ajustes.
        if (!listUrl) { escLegacyList($backdrop); return; }

        TKT_ESC.tab = 'list';
        TKT_ESC.preview = null;
        TKT_ESC.errors = null;
        escResetDraft();

        $.getJSON(listUrl).done(function (d) {
            TKT_ESC.canManage = !!(d && d.can_manage);
            TKT_ESC.rules = (d && d.rules) || [];
            TKT_ESC.catalog = (d && d.catalog) || null;
            escRender($backdrop);
        }).fail(function () {
            $backdrop.find('#tkt-esc-body').html('<div class="tkt-empty-box">No se pudieron cargar las reglas de escalado.</div>');
        });

        escBind($backdrop);
    }

    // ── Fallback: el modal de solo lectura de antes ───────────────
    function escLegacyList($backdrop) {
        $backdrop.find('.tkt-modal-foot').html(
            '<a class="tkt-btn tkt-btn-primary" href="' + escapeHtml(TKA.urls.automationsIndex || '#') + '">Crear o editar reglas</a>' +
            '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>');
        withSettings(function (d) {
            var list = (d && d.automations) || [];
            if (!list.length) { $backdrop.find('#tkt-esc-body').html('<div class="tkt-empty-box">No hay reglas de automatización definidas.</div>'); return; }
            $backdrop.find('#tkt-esc-body').html('<div class="tkt-mailitems">' + list.map(function (a) {
                return '<div class="tkt-mailitem"><span class="av light"><i class="fa-solid fa-gears"></i></span>' +
                    '<span class="who"><span class="n">' + escapeHtml(a.name) + '</span>' +
                    '<span class="s">' + escapeHtml([a.trigger_event, a.run_count ? a.run_count + ' ejecuciones' : null, a.last_run_at_human].filter(Boolean).join(' · ')) + '</span></span>' +
                    '<span class="tkt-rchip' + (a.is_active ? ' ok' : '') + '">' + (a.is_active ? 'activa' : 'pausada') + '</span></div>';
            }).join('') + '</div>');
        });
    }

    // ── Catálogos ─────────────────────────────────────────────────
    function escCatalogList(kind) {
        if (kind === 'priorities') return (TKT_ESC.catalog && TKT_ESC.catalog.priorities) || [];
        if (kind === 'statuses') return TKA.state.statuses || [];
        if (kind === 'groups') return TKA.state.groups || [];
        if (kind === 'agents') return TKA.state.agentsFull || [];
        if (kind === 'categories') return TKA.state.categories || [];
        return [];
    }

    function escFieldSpec(field) {
        var fields = (TKT_ESC.catalog && TKT_ESC.catalog.fields) || [];
        for (var i = 0; i < fields.length; i++) if (fields[i].field === field) return fields[i];
        return null;
    }

    function escActionSpec(type) {
        var actions = (TKT_ESC.catalog && TKT_ESC.catalog.actions) || [];
        for (var i = 0; i < actions.length; i++) if (actions[i].type === type) return actions[i];
        return null;
    }

    function escOpLabel(op) {
        return (TKT_ESC.catalog && TKT_ESC.catalog.operators && TKT_ESC.catalog.operators[op]) || op;
    }

    function escTriggerLabel(event) {
        var triggers = (TKT_ESC.catalog && TKT_ESC.catalog.triggers) || [];
        for (var i = 0; i < triggers.length; i++) if (triggers[i].value === event) return triggers[i].label;
        return null;
    }

    // Nombre legible de un valor: la prioridad/estado/equipo/agente por su id,
    // con los catálogos que la pantalla ya tiene cargados (sin ir al servidor).
    function escValueLabel(kind, value) {
        if (value === null || typeof value === 'undefined') return '';
        if (Array.isArray(value)) return value.map(function (v) { return escValueLabel(kind, v); }).join(', ');
        var list = escCatalogList(kind);
        for (var i = 0; i < list.length; i++) if (String(list[i].id) === String(value)) return list[i].name;
        return String(value);
    }

    // ── Resumen legible de una regla ──────────────────────────────
    function escConditionText(cond) {
        // Las reglas escritas a mano en Ajustes traen a veces 'operator' en vez
        // de 'op' (el motor solo lee 'op': esas condiciones no se evalúan nunca).
        var op = cond.op || cond.operator;
        var spec = escFieldSpec(cond.field);
        var label = spec ? spec.label : cond.field;
        if (op === 'is_null') return label + ' está vacío';
        if (op === 'is_not_null') return label + ' tiene valor';
        var valor = spec && spec.input === 'bool'
            ? (cond.value ? 'sí' : 'no')
            : (spec && spec.options ? escValueLabel(spec.options, cond.value) : String(cond.value));
        return label + ' ' + escOpLabel(op) + ' ' + valor;
    }

    function escActionText(action) {
        var spec = escActionSpec(action.type);
        if (!spec) return action.type;
        if (spec.input === 'none') return spec.label;
        return spec.label + ' ' + (spec.options ? escValueLabel(spec.options, action.value) : String(action.value));
    }

    function escRuleSummary(rule) {
        var conds = (rule.conditions || []).map(escConditionText);
        var acts = (rule.actions || []).map(escActionText);
        var si = escTriggerLabel(rule.trigger_event) || rule.trigger_event;
        return (conds.length ? si + ' y ' + conds.join(' y ') : si) + ' → ' + (acts.join(', ') || 'nada');
    }

    // ── Render ────────────────────────────────────────────────────
    function escRender($backdrop) {
        $backdrop.find('.tkt-modal-title').text(TKT_ESC.tab === 'form' ? 'Nueva regla' : 'Reglas de escalado');
        $backdrop.find('#tkt-esc-body').html(TKT_ESC.tab === 'form' ? escFormHtml() : escListHtml());
        $backdrop.find('.tkt-modal-foot').html(TKT_ESC.tab === 'form' ? escFormFootHtml() : escListFootHtml());
        initSelect2($backdrop.find('#tkt-esc-body'));
    }

    function escListHtml() {
        if (!TKT_ESC.rules.length) {
            return '<div class="tkt-empty-box">No hay reglas de automatización definidas.</div>' + escEvalNoteHtml();
        }

        var html = '<div class="tkt-mailitems">' + TKT_ESC.rules.map(function (r) {
            var huerfana = !escTriggerLabel(r.trigger_event);
            var meta = [
                escTriggerLabel(r.trigger_event) || r.trigger_event,
                r.run_count ? r.run_count + ' ejecuciones' : null,
                r.last_run_at_human,
            ].filter(Boolean).join(' · ');
            var chipCls = 'tkt-rchip' + (r.is_active ? ' ok' : '');
            var estado = r.is_active ? 'activa' : 'pausada';
            // El estado es el botón de activar/pausar para quien puede gestionar;
            // para el resto es una etiqueta y nada más.
            var chip = TKT_ESC.canManage
                ? '<button type="button" class="' + chipCls + ' tkt-esc-toggle" data-esc-toggle="' + r.id + '" ' +
                  'title="' + (r.is_active ? 'Pausar la regla' : 'Activar la regla') + '">' + estado + '</button>'
                : '<span class="' + chipCls + '">' + estado + '</span>';

            return '<div class="tkt-mailitem tkt-esc-item"><span class="av light"><i class="fa-solid fa-gears"></i></span>' +
                '<span class="who"><span class="n">' + escapeHtml(r.name) + '</span>' +
                '<span class="s">' + escapeHtml(meta) + '</span>' +
                '<span class="s tkt-esc-rule-sum">' + escapeHtml(escRuleSummary(r)) + '</span></span>' +
                (huerfana ? '<span class="tkt-rchip" title="Su disparador no existe en el motor: nunca se ejecuta">sin disparador</span>' : '') +
                chip + '</div>';
        }).join('') + '</div>';

        if (TKT_ESC.canManage) {
            html += '<div class="tkt-cap">Toca el estado de una regla para activarla o pausarla.</div>';
        }

        // Solo se avisa de reglas huérfanas si de verdad las hay.
        var huerfanas = TKT_ESC.rules.filter(function (r) { return !escTriggerLabel(r.trigger_event); }).length;
        if (huerfanas) {
            html += '<div class="tkt-note warn"><i class="fa-solid fa-triangle-exclamation"></i> ' +
                'Hay ' + huerfanas + ' regla(s) con un disparador que el motor de tickets no conoce: están guardadas pero no se ejecutan nunca.</div>';
        }

        return html + escEvalNoteHtml();
    }

    function escEvalNoteHtml() {
        // El pie del mockup decía "Evaluado por helpdesk:mark-overdue cada 15
        // minutos": ese comando no existe (el real es ticket:autooverdue) y
        // además no evalúa reglas, solo marca incumplimientos de SLA. Lo que se
        // cuenta aquí es lo que de verdad pasa.
        return '<div class="tkt-note"><i class="fa-solid fa-gauge-high"></i><div>' +
            'Las reglas se evalúan en cuanto ocurre el evento elegido (en la cola <span class="tkt-esc-mono">default</span>), no por reloj. ' +
            'El aviso <span class="tkt-esc-mono">SlaBreachMail</span> a los managers ya sale solo al incumplirse el SLA: no hace falta ninguna regla.' +
            '</div></div>';
    }

    function escListFootHtml() {
        return (TKT_ESC.canManage ? '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-esc-new">Crear regla</button>' : '') +
            '<a class="tkt-btn" href="' + escapeHtml(TKA.urls.automationsIndex || '#') + '">Ver todas en Ajustes</a>' +
            '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>';
    }

    // ── Formulario "Si… Entonces…" ────────────────────────────────
    function escResetDraft() {
        TKT_ESC.draft = { name: '', trigger_event: null, is_active: true };
        TKT_ESC.conds = [];
        TKT_ESC.acts = [{ type: 'set_priority', value: null }];
    }

    function escFormHtml() {
        var triggers = (TKT_ESC.catalog && TKT_ESC.catalog.triggers) || [];

        var html = '';

        if (TKT_ESC.errors && TKT_ESC.errors.length) {
            html += '<div class="tkt-note warn"><i class="fa-solid fa-triangle-exclamation"></i><div>' +
                TKT_ESC.errors.map(escapeHtml).join('<br>') + '</div></div>';
        }

        html += '<div class="tkt-field"><label class="tkt-label">Nombre de la regla</label>' +
            '<input type="text" class="tkt-input" id="tkt-esc-name" maxlength="255" placeholder="Escalar los urgentes sin agente"' +
            ' value="' + escapeHtml(TKT_ESC.draft.name || '') + '"></div>';

        html += '<div class="tkt-field"><label class="tkt-label">Si</label>' +
            '<select class="tkt-select" id="tkt-esc-trigger">' + triggers.map(function (t) {
                return '<option value="' + escapeHtml(t.value) + '"' + (t.value === TKT_ESC.draft.trigger_event ? ' selected' : '') + '>' + escapeHtml(t.label) + '</option>';
            }).join('') + '</select></div>';

        html += '<div class="tkt-field"><label class="tkt-label">Y se cumple<button type="button" class="tkt-label-action" id="tkt-esc-add-cond">+ añadir condición</button></label>' +
            '<div id="tkt-esc-conds">' + (TKT_ESC.conds.length
                ? TKT_ESC.conds.map(function (c, i) { return escCondHtml(i, c); }).join('')
                : '<div class="tkt-empty-box">Sin condiciones: la regla vale para cualquier ticket.</div>') +
            '</div></div>';

        html += '<div class="tkt-field"><label class="tkt-label">Entonces<button type="button" class="tkt-label-action" id="tkt-esc-add-act">+ añadir acción</button></label>' +
            '<div id="tkt-esc-acts">' + TKT_ESC.acts.map(function (a, i) { return escActHtml(i, a); }).join('') + '</div></div>';

        html += '<label class="tkt-check"><input type="checkbox" id="tkt-esc-active"' +
            (TKT_ESC.draft.is_active ? ' checked' : '') + '> Activar la regla al crearla</label>';

        if (TKT_ESC.preview) {
            html += '<div class="tkt-note ' + (TKT_ESC.preview.matched ? 'ok' : '') + '"><i class="fa-solid fa-flask"></i><div>' +
                escapeHtml(TKT_ESC.preview.text) + '</div></div>';
        }

        return html + escEvalNoteHtml();
    }

    function escFormFootHtml() {
        return '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-esc-save">Crear regla</button>' +
            '<button type="button" class="tkt-btn" id="tkt-esc-test">Probar regla</button>' +
            '<button type="button" class="tkt-btn" id="tkt-esc-cancel">Cancelar</button>';
    }

    function escCondHtml(i, cond) {
        var fields = (TKT_ESC.catalog && TKT_ESC.catalog.fields) || [];
        var spec = escFieldSpec(cond.field) || fields[0];
        if (!spec) return '';
        var op = cond.op && spec.ops.indexOf(cond.op) >= 0 ? cond.op : spec.ops[0];

        return '<div class="tkt-esc-rule" data-cond="' + i + '">' +
            '<div class="tkt-esc-rule-head"><span class="tkt-cap">Condición ' + (i + 1) + '</span>' +
            '<button type="button" class="tkt-btn-icon tkt-esc-rule-del" data-cond-del="' + i + '" title="Quitar la condición"><i class="fa-solid fa-xmark"></i></button></div>' +
            '<div class="tkt-duo">' +
                '<div class="tkt-field"><select class="tkt-select" data-cond-field="' + i + '">' + fields.map(function (f) {
                    return '<option value="' + escapeHtml(f.field) + '"' + (f.field === spec.field ? ' selected' : '') + '>' + escapeHtml(f.label) + '</option>';
                }).join('') + '</select></div>' +
                '<div class="tkt-field"><select class="tkt-select" data-cond-op="' + i + '">' + spec.ops.map(function (o) {
                    return '<option value="' + escapeHtml(o) + '"' + (o === op ? ' selected' : '') + '>' + escapeHtml(escOpLabel(o)) + '</option>';
                }).join('') + '</select></div>' +
            '</div>' +
            '<div data-cond-value="' + i + '">' + escValueControlHtml(spec, op, 'cond-val-' + i, cond.value) + '</div>' +
            '</div>';
    }

    function escActHtml(i, act) {
        var actions = (TKT_ESC.catalog && TKT_ESC.catalog.actions) || [];
        var spec = escActionSpec(act.type) || actions[0];
        if (!spec) return '';

        return '<div class="tkt-esc-rule" data-act="' + i + '">' +
            '<div class="tkt-esc-rule-head"><span class="tkt-cap">Acción ' + (i + 1) + '</span>' +
            (TKT_ESC.acts.length > 1
                ? '<button type="button" class="tkt-btn-icon tkt-esc-rule-del" data-act-del="' + i + '" title="Quitar la acción"><i class="fa-solid fa-xmark"></i></button>'
                : '') +
            '</div>' +
            '<div class="tkt-field"><select class="tkt-select" data-act-type="' + i + '">' + actions.map(function (a) {
                return '<option value="' + escapeHtml(a.type) + '"' + (a.type === spec.type ? ' selected' : '') + '>' + escapeHtml(a.label) + '</option>';
            }).join('') + '</select></div>' +
            '<div data-act-value="' + i + '">' + escValueControlHtml(spec, null, 'act-val-' + i, act.value) + '</div>' +
            '</div>';
    }

    // Control del valor según lo que admite el campo/acción. Los operadores
    // is_null/is_not_null no llevan valor: preguntan por la ausencia.
    function escValueControlHtml(spec, op, cls, actual) {
        if (op === 'is_null' || op === 'is_not_null') return '';
        if (spec.input === 'none') return '';

        if (op === 'in') {
            // "La prioridad es Alta o Urgente" del mockup: varias a la vez.
            var marcados = Array.isArray(actual) ? actual.map(String) : [];
            return '<div class="tkt-esc-checks">' + escCatalogList(spec.options).map(function (o) {
                return '<label class="tkt-check"><input type="checkbox" class="' + cls + '" value="' + escapeHtml(String(o.id)) + '"' +
                    (marcados.indexOf(String(o.id)) >= 0 ? ' checked' : '') + '> ' + escapeHtml(o.name) + '</label>';
            }).join('') + '</div>';
        }

        if (spec.input === 'bool') {
            return '<select class="tkt-select ' + cls + '"><option value="1">Sí</option>' +
                '<option value="0"' + (String(actual) === '0' ? ' selected' : '') + '>No</option></select>';
        }

        if (spec.input === 'number') {
            return '<input type="number" min="0" step="1" class="tkt-input ' + cls + '" value="' + escapeHtml(String(actual == null ? 0 : actual)) + '">';
        }

        if (spec.input === 'textarea') {
            return '<textarea class="tkt-input ' + cls + '" rows="2" maxlength="2000" placeholder="Texto de la nota interna">' +
                escapeHtml(String(actual == null ? '' : actual)) + '</textarea>';
        }

        if (spec.input === 'text') {
            return '<input type="text" class="tkt-input ' + cls + '" maxlength="255" placeholder="Escribe el valor"' +
                ' value="' + escapeHtml(String(actual == null ? '' : actual)) + '">';
        }

        var list = escCatalogList(spec.options);
        if (!list.length) {
            return '<div class="tkt-empty-box">No hay opciones disponibles para esta elección.</div>';
        }

        return '<select class="tkt-select ' + cls + '">' + list.map(function (o) {
            return '<option value="' + escapeHtml(String(o.id)) + '"' + (String(o.id) === String(actual) ? ' selected' : '') + '>' + escapeHtml(o.name) + '</option>';
        }).join('') + '</select>';
    }

    // ── Lectura del formulario ────────────────────────────────────
    function escReadValue($scope, spec, op, cls) {
        if (op === 'is_null' || op === 'is_not_null') return null;
        if (spec.input === 'none') return null;
        if (op === 'in') {
            return $scope.find('.' + cls + ':checked').map(function () { return this.value; }).get();
        }
        return $scope.find('.' + cls).val();
    }

    function escReadForm($backdrop) {
        var conditions = [];
        $backdrop.find('[data-cond]').each(function () {
            var i = $(this).data('cond');
            var $row = $(this);
            var field = $row.find('[data-cond-field]').val();
            var op = $row.find('[data-cond-op]').val();
            var spec = escFieldSpec(field);
            if (!spec) return;
            conditions.push({ field: field, op: op, value: escReadValue($row, spec, op, 'cond-val-' + i) });
        });

        var actions = [];
        $backdrop.find('[data-act]').each(function () {
            var i = $(this).data('act');
            var $row = $(this);
            var type = $row.find('[data-act-type]').val();
            var spec = escActionSpec(type);
            if (!spec) return;
            actions.push({ type: type, value: escReadValue($row, spec, null, 'act-val-' + i) });
        });

        return {
            name: $.trim($backdrop.find('#tkt-esc-name').val() || ''),
            trigger_event: $backdrop.find('#tkt-esc-trigger').val(),
            conditions: conditions,
            actions: actions,
            is_active: $backdrop.find('#tkt-esc-active').is(':checked') ? 1 : 0,
        };
    }

    // Vuelca a TKT_ESC lo que hay ahora mismo en el formulario (nombre,
    // disparador, filas y sus valores) antes de repintar: sin esto, añadir una
    // condición borraba todo lo tecleado antes.
    function escSyncStructure($backdrop) {
        var leido = escReadForm($backdrop);

        TKT_ESC.draft = {
            name: leido.name,
            trigger_event: leido.trigger_event,
            is_active: !!leido.is_active,
        };
        TKT_ESC.conds = leido.conditions;
        TKT_ESC.acts = leido.actions;
    }

    function escApiError(xhr, porDefecto) {
        var msgs = [];
        if (xhr && xhr.responseJSON && xhr.responseJSON.errors) {
            $.each(xhr.responseJSON.errors, function (k, list) { msgs.push(list[0]); });
        } else if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
            msgs.push(xhr.responseJSON.message);
        }
        if (!msgs.length) msgs.push(xhr && xhr.status === 403 ? 'Hace falta permiso de ajustes del módulo.' : porDefecto);
        return msgs;
    }

    // ── Eventos ───────────────────────────────────────────────────
    function escBind($backdrop) {
        $backdrop.on('click', '#tkt-esc-new', function () {
            TKT_ESC.tab = 'form';
            TKT_ESC.errors = null;
            TKT_ESC.preview = null;
            escResetDraft();
            escRender($backdrop);
        });

        $backdrop.on('click', '#tkt-esc-cancel', function () {
            TKT_ESC.tab = 'list';
            TKT_ESC.errors = null;
            TKT_ESC.preview = null;
            escRender($backdrop);
        });

        // Activar / pausar una regla del listado.
        $backdrop.on('click', '[data-esc-toggle]', function () {
            var id = $(this).data('esc-toggle');
            var tpl = escUrl('automationsToggleTemplate', 'data-automations-toggle-url-template');
            if (!tpl) return;
            var $chip = $(this).prop('disabled', true);
            $.ajax({
                url: tpl.replace('__AUTOMATION__', id),
                method: 'POST',
                headers: { Accept: 'application/json' },
            }).done(function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Regla actualizada.');
                for (var i = 0; i < TKT_ESC.rules.length; i++) {
                    if (TKT_ESC.rules[i].id === id) TKT_ESC.rules[i] = resp.rule;
                }
                escRender($backdrop);
            }).fail(function (xhr) {
                $chip.prop('disabled', false);
                var msg = escApiError(xhr, 'No se pudo cambiar el estado de la regla.')[0];
                if (window.toastr) toastr.error(msg); else window.alert(msg);
            });
        });

        // Añadir / quitar filas. Se sincroniza la estructura antes de repintar
        // para conservar lo ya elegido en las demás filas.
        $backdrop.on('click', '#tkt-esc-add-cond', function () {
            escSyncStructure($backdrop);
            var fields = (TKT_ESC.catalog && TKT_ESC.catalog.fields) || [];
            if (TKT_ESC.conds.length >= 5 || !fields.length) return;
            TKT_ESC.conds.push({ field: fields[0].field, op: fields[0].ops[0] });
            escRender($backdrop);
        });

        $backdrop.on('click', '#tkt-esc-add-act', function () {
            escSyncStructure($backdrop);
            var actions = (TKT_ESC.catalog && TKT_ESC.catalog.actions) || [];
            if (TKT_ESC.acts.length >= 5 || !actions.length) return;
            TKT_ESC.acts.push({ type: actions[0].type });
            escRender($backdrop);
        });

        $backdrop.on('click', '[data-cond-del]', function () {
            escSyncStructure($backdrop);
            TKT_ESC.conds.splice($(this).data('cond-del'), 1);
            escRender($backdrop);
        });

        $backdrop.on('click', '[data-act-del]', function () {
            escSyncStructure($backdrop);
            TKT_ESC.acts.splice($(this).data('act-del'), 1);
            escRender($backdrop);
        });

        // Cambiar de campo cambia los operadores posibles y el control del valor:
        // se repinta solo esa fila, así el resto del formulario no se pierde.
        $backdrop.on('change', '[data-cond-field]', function () {
            var i = $(this).data('cond-field');
            var spec = escFieldSpec($(this).val());
            if (!spec) return;
            var $row = $backdrop.find('[data-cond="' + i + '"]');
            $row.replaceWith(escCondHtml(i, { field: spec.field, op: spec.ops[0] }));
            initSelect2($backdrop.find('[data-cond="' + i + '"]'));
        });

        $backdrop.on('change', '[data-cond-op]', function () {
            var i = $(this).data('cond-op');
            var $row = $backdrop.find('[data-cond="' + i + '"]');
            var spec = escFieldSpec($row.find('[data-cond-field]').val());
            if (!spec) return;
            $row.find('[data-cond-value="' + i + '"]').html(escValueControlHtml(spec, $(this).val(), 'cond-val-' + i));
            initSelect2($row);
        });

        $backdrop.on('change', '[data-act-type]', function () {
            var i = $(this).data('act-type');
            var spec = escActionSpec($(this).val());
            if (!spec) return;
            var $row = $backdrop.find('[data-act="' + i + '"]');
            $row.find('[data-act-value="' + i + '"]').html(escValueControlHtml(spec, null, 'act-val-' + i));
            initSelect2($row);
        });

        // Probar regla: prueba en seco de las condiciones contra los últimos
        // tickets. No ejecuta ninguna acción ni guarda nada.
        $backdrop.on('click', '#tkt-esc-test', function () {
            var url = escUrl('automationsPreview', 'data-automations-preview-url');
            if (!url) return;
            var datos = escReadForm($backdrop);
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: url, method: 'POST', headers: { Accept: 'application/json' },
                data: { conditions: datos.conditions },
            }).done(function (resp) {
                escSyncStructure($backdrop);
                TKT_ESC.errors = null;
                TKT_ESC.preview = {
                    matched: resp.matched,
                    text: resp.matched
                        ? 'Las condiciones coinciden con ' + resp.matched + ' de los últimos ' + resp.scanned + ' tickets' +
                          (resp.sample.length ? ' (' + resp.sample.map(function (s) { return s.ticket_number; }).join(', ') + ')' : '') +
                          '. La prueba no ejecuta ninguna acción.'
                        : 'Ninguno de los últimos ' + resp.scanned + ' tickets cumple estas condiciones.',
                };
                escRender($backdrop);
            }).fail(function (xhr) {
                $btn.prop('disabled', false);
                escSyncStructure($backdrop);
                TKT_ESC.errors = escApiError(xhr, 'No se pudo probar la regla.');
                TKT_ESC.preview = null;
                escRender($backdrop);
            });
        });

        $backdrop.on('click', '#tkt-esc-save', function () {
            var url = escUrl('automationsStore', 'data-automations-store-url');
            if (!url) return;
            var datos = escReadForm($backdrop);
            if (!datos.name) {
                escSyncStructure($backdrop);
                TKT_ESC.errors = ['Ponle un nombre a la regla.'];
                escRender($backdrop);
                return;
            }
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: url, method: 'POST', headers: { Accept: 'application/json' }, data: datos,
            }).done(function (resp) {
                if (window.toastr) toastr.success((resp && resp.message) || 'Regla creada.');
                TKT_ESC.rules.push(resp.rule);
                TKT_ESC.tab = 'list';
                TKT_ESC.errors = null;
                TKT_ESC.preview = null;
                escResetDraft();
                escRender($backdrop);
            }).fail(function (xhr) {
                $btn.prop('disabled', false);
                escSyncStructure($backdrop);
                TKT_ESC.errors = escApiError(xhr, 'No se pudo crear la regla.');
                escRender($backdrop);
            });
        });
    }

    // ── Modal 30: Ticket recurrente ───────────────────────────
    // ── Modal 30: Tickets recurrentes ─────────────────────────
    function openRecurringModal() {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-repeat',
            kicker: 'Tickets · recurrentes',
            title: 'Tickets recurrentes',
            width: 'sm',
            body: '<div class="tkt-skeleton"></div><div class="tkt-skeleton"></div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        // Lista + catálogos de los desplegables, ambos de la misma petición.
        var lista = [];
        var catalogos = null;
        // Recurrencia que se está editando (null = alta nueva o pantalla lista).
        var editando = null;

        function urlDe(plantilla, id) {
            return String(plantilla || '').replace('__REC__', id);
        }

        function porId(id) {
            return lista.find(function (r) { return String(r.id) === String(id); }) || null;
        }

        // El pie cambia entre pantalla de lista y pantalla de formulario, así que
        // se repintan las dos zonas del modal ya abierto en vez de cerrarlo y
        // volver a abrirlo (perdería el scroll y parpadearía).
        function pintar(bodyHtml, footHtml) {
            $backdrop.find('.tkt-modal-body').html(bodyHtml);
            $backdrop.find('.tkt-modal-foot').html(footHtml);
            initSelect2($backdrop.find('.tkt-modal-body'));
        }

        // ── Pantalla 1: la lista ──────────────────────────────
        function filaHtml(r) {
            var meta = [
                r.frequency_label,
                r.next_run_at_human ? 'próxima ' + r.next_run_at_human : 'sin próxima ejecución',
                r.tickets_created ? r.tickets_created + ' creados' : null,
            ].filter(Boolean).join(' · ');

            return '<div class="tkt-mailitem tkt-rec-row' + (r.is_active ? '' : ' off') + '">' +
                '<span class="av light"><i class="fa-solid fa-repeat"></i></span>' +
                '<span class="who"><span class="n">' + escapeHtml(r.name || r.subject) + '</span>' +
                    '<span class="s">' + escapeHtml(meta) + '</span></span>' +
                (r.is_active ? '' : chip('Pausada', 'tkt-chip-muted')) +
                '<span class="tkt-rec-actions">' +
                    '<button type="button" class="tkt-btn-icon sm" data-rec-toggle="' + r.id + '" ' +
                        'title="' + (r.is_active ? 'Pausar' : 'Reanudar') + '" ' +
                        'aria-label="' + (r.is_active ? 'Pausar' : 'Reanudar') + ' ' + escapeHtml(r.name || '') + '">' +
                        '<i class="fa-solid ' + (r.is_active ? 'fa-pause' : 'fa-play') + '"></i></button>' +
                    '<button type="button" class="tkt-btn-icon sm" data-rec-edit="' + r.id + '" ' +
                        'title="Editar" aria-label="Editar ' + escapeHtml(r.name || '') + '">' +
                        '<i class="fa-solid fa-pen"></i></button>' +
                '</span></div>';
        }

        function renderLista() {
            editando = null;

            var cuerpo = lista.length
                ? '<div class="tkt-mailitems">' + lista.map(filaHtml).join('') + '</div>'
                : '<div class="tkt-empty-box">No hay ninguna recurrencia programada. Una recurrencia crea un ticket cada día, semana o mes sin que nadie tenga que acordarse.</div>';

            pintar(
                cuerpo,
                '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-rec-new">Programar recurrencia</button>' +
                '<a class="tkt-btn" href="' + escapeHtml(TKA.urls.recurring || '#') + '">Gestionar en Ajustes</a>' +
                '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>'
            );
        }

        // ── Pantalla 2: el formulario ─────────────────────────
        function opcionesFrecuencia(r) {
            var todas = (catalogos && catalogos.frequencies) || [
                { value: 'daily', label: 'Diaria' },
                { value: 'weekly', label: 'Semanal' },
                { value: 'monthly', label: 'Mensual' },
            ];
            // 'custom' solo se ofrece si la recurrencia YA lo era: el modal no
            // edita expresiones cron (el backend rechaza crearlas desde aquí),
            // pero tampoco puede obligar a cambiarle la frecuencia a una que ya
            // la tiene solo para tocarle el asunto.
            return todas.filter(function (f) {
                return f.value !== 'custom' || (r && r.frequency === 'custom');
            }).map(function (f) {
                return '<option value="' + f.value + '"' +
                    (r && r.frequency === f.value ? ' selected' : '') + '>' + escapeHtml(f.label) + '</option>';
            }).join('');
        }

        function agentesConAsignado(r) {
            var agentes = ((catalogos && catalogos.agents) || TKA.state.agentsFull || []).slice();
            // El agente asignado puede no estar en el catálogo (se le retiró el
            // rol, o dejó de estar disponible): sin esto el <select> lo perdería
            // en silencio al primer guardado.
            if (r && r.assignee_id && !agentes.some(function (a) { return String(a.id) === String(r.assignee_id); })) {
                agentes.unshift({ id: r.assignee_id, name: r.assignee_name || ('Usuario #' + r.assignee_id) });
            }
            return agentes;
        }

        function renderEditor(r) {
            editando = r || null;

            var categorias = (catalogos && catalogos.categories) || TKA.state.categories || [];
            var prioridades = (catalogos && catalogos.priorities) || [];
            var plantillas = TKA.state.ticketTemplates || [];

            var cuerpo = '' +
                // La plantilla solo rellena campos: no queda guardada en la
                // recurrencia (no hay columna), así que se ofrece únicamente al
                // crear, donde ahorra teclear, y no al editar, donde machacaría
                // lo que ya hay.
                (!r && plantillas.length
                    ? '<div class="tkt-field"><label class="tkt-label" for="tkt-rec-tpl">Plantilla de ticket' +
                        '<span class="hint">solo rellena los campos</span></label>' +
                        '<select class="tkt-select" id="tkt-rec-tpl"><option value="">Empezar en blanco…</option>' +
                        optionsHtml(plantillas, 'id', '') + '</select></div>'
                    : '') +

                '<div class="tkt-field"><label class="tkt-label" for="tkt-rec-name">Nombre<span class="req">*</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-rec-name" maxlength="255" ' +
                    'placeholder="Control diario de pedidos sin salir" value="' + escapeHtml(r ? r.name : '') + '"></div>' +

                '<div class="tkt-field"><label class="tkt-label" for="tkt-rec-subject">Asunto del ticket<span class="req">*</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-rec-subject" maxlength="255" ' +
                    'value="' + escapeHtml(r ? r.subject : '') + '"></div>' +

                '<div class="tkt-field"><label class="tkt-label" for="tkt-rec-desc">Descripción</label>' +
                    '<textarea class="tkt-input" id="tkt-rec-desc" rows="3" maxlength="5000">' +
                    escapeHtml(r ? (r.description || '') : '') + '</textarea></div>' +

                '<div class="tkt-field-row">' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-rec-freq">Frecuencia<span class="req">*</span></label>' +
                        '<select class="tkt-select" id="tkt-rec-freq">' + opcionesFrecuencia(r) + '</select></div>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-rec-next">' +
                        (r ? 'Próxima ejecución' : 'Primera ejecución') + '</label>' +
                        '<input type="datetime-local" class="tkt-input" id="tkt-rec-next" ' +
                        'value="' + escapeHtml(r ? (r.next_run_at || '') : '') + '"></div>' +
                '</div>' +

                '<div class="tkt-field-row">' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-rec-cat">Categoría</label>' +
                        '<select class="tkt-select" id="tkt-rec-cat"><option value="">Sin categoría</option>' +
                        optionsHtml(categorias, 'id', r ? r.category_id : '') + '</select></div>' +
                    (prioridades.length
                        ? '<div class="tkt-field"><label class="tkt-label" for="tkt-rec-pri">Prioridad</label>' +
                            '<select class="tkt-select" id="tkt-rec-pri"><option value="">Sin prioridad</option>' +
                            optionsHtml(prioridades, 'id', r ? r.priority_id : '') + '</select></div>'
                        : '') +
                '</div>' +

                '<div class="tkt-field"><label class="tkt-label" for="tkt-rec-agent">Agente asignado</label>' +
                    '<select class="tkt-select" id="tkt-rec-agent"><option value="">Sin asignar</option>' +
                    optionsHtml(agentesConAsignado(r), 'id', r ? r.assignee_id : '') + '</select></div>' +

                (r && r.frequency === 'custom'
                    ? '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Esta recurrencia se rige por una expresión cron (' +
                        escapeHtml(r.cron_expression || '—') + '). Se cambia desde Ajustes; aquí se conserva tal cual.</div>'
                    : '') +

                // Solo con datos reales: en una recurrencia nueva no hay próxima
                // ejecución ni contador que enseñar, así que no se pinta la caja.
                (r
                    ? '<div class="tkt-kv-grid">' +
                        '<span>próxima ejecución</span><span>' + escapeHtml(r.next_run_at_label || 'sin programar') + '</span>' +
                        '<span>creados</span><span>' + (r.tickets_created || 0) + ' tickets</span>' +
                        (r.last_run_at_human ? '<span>última</span><span>' + escapeHtml(r.last_run_at_human) + '</span>' : '') +
                      '</div>'
                    : '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> El ticket se crea con el asunto y la descripción de arriba. Si no indicas la primera ejecución, se programa a partir de la frecuencia elegida.</div>');

            pintar(
                cuerpo,
                '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-rec-save">Guardar recurrencia</button>' +
                (r ? '<button type="button" class="tkt-btn" id="tkt-rec-pause" data-rec-toggle="' + r.id + '">' +
                    (r.is_active ? 'Pausar' : 'Reanudar') + '</button>' : '') +
                '<button type="button" class="tkt-btn" id="tkt-rec-back">' + (r ? 'Volver' : 'Cancelar') + '</button>'
            );
        }

        // ── Datos ─────────────────────────────────────────────
        function cargar(alTerminar) {
            $.getJSON(TKA.urls.recurringOps)
                .done(function (d) {
                    lista = (d && d.data) || [];
                    catalogos = (d && d.catalogs) || null;
                    (alTerminar || renderLista)();
                })
                .fail(function () {
                    pintar(
                        '<div class="tkt-empty-box">No se pudieron cargar las recurrencias.</div>',
                        '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>'
                    );
                });
        }

        function guardar(r) {
            var $save = $backdrop.find('#tkt-rec-save');
            var datos = {
                name: $.trim($backdrop.find('#tkt-rec-name').val()),
                subject: $.trim($backdrop.find('#tkt-rec-subject').val()),
                frequency: $backdrop.find('#tkt-rec-freq').val(),
            };

            if (!datos.name || !datos.subject) {
                if (window.toastr) toastr.error('El nombre y el asunto son obligatorios');
                return;
            }

            // Los opcionales solo viajan si tienen valor: así el backend no
            // recibe cadenas vacías donde espera un id o una fecha.
            [['description', '#tkt-rec-desc'], ['category_id', '#tkt-rec-cat'],
                ['priority_id', '#tkt-rec-pri'], ['assignee_id', '#tkt-rec-agent'],
                ['next_run_at', '#tkt-rec-next']].forEach(function (par) {
                var valor = $.trim($backdrop.find(par[1]).val() || '');
                if (valor) datos[par[0]] = valor;
            });

            $save.prop('disabled', true);
            $.ajax({
                url: r ? urlDe(TKA.urls.recurringUpdateTemplate, r.id) : TKA.urls.recurringOps,
                method: 'POST',
                data: datos,
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Recurrencia guardada');
                    cargar();
                },
                error: function (xhr) {
                    $save.prop('disabled', false);
                    var json = xhr.responseJSON || {};
                    var primerError = json.errors ? json.errors[Object.keys(json.errors)[0]][0] : null;
                    var msg = primerError || json.message || 'No se pudo guardar la recurrencia';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        }

        function alternar(id) {
            var r = porId(id);
            if (!r) return;

            $backdrop.find('[data-rec-toggle="' + id + '"]').prop('disabled', true);
            $.ajax({
                url: urlDe(TKA.urls.recurringToggleTemplate, id),
                method: 'POST',
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Recurrencia actualizada');
                    // Se recarga y se vuelve a la lista: pausar desde el
                    // formulario cambia además la próxima ejecución (el backend
                    // la reprograma al reanudar), y hay que verla al día.
                    cargar();
                },
                error: function (xhr) {
                    $backdrop.find('[data-rec-toggle="' + id + '"]').prop('disabled', false);
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo cambiar el estado de la recurrencia';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                },
            });
        }

        // ── Eventos (delegados: el cuerpo se repinta entero) ──
        $backdrop.on('click', '#tkt-rec-new', function () { renderEditor(null); });
        $backdrop.on('click', '#tkt-rec-back', function () { renderLista(); });
        $backdrop.on('click', '[data-rec-edit]', function () { renderEditor(porId($(this).data('rec-edit'))); });
        $backdrop.on('click', '[data-rec-toggle]', function () { alternar($(this).data('rec-toggle')); });
        $backdrop.on('click', '#tkt-rec-save', function () { guardar(editando); });

        $backdrop.on('change', '#tkt-rec-tpl', function () {
            var elegida = $(this).val();
            var tpl = (TKA.state.ticketTemplates || []).find(function (p) { return String(p.id) === String(elegida); });
            if (!tpl) return;
            if (!$.trim($backdrop.find('#tkt-rec-name').val())) $backdrop.find('#tkt-rec-name').val(tpl.name || '');
            if (tpl.subject) $backdrop.find('#tkt-rec-subject').val(tpl.subject);
            // El cuerpo de la plantilla puede venir con HTML y la descripción del
            // ticket recurrente es texto plano.
            if (tpl.body) $backdrop.find('#tkt-rec-desc').val($('<div>').html(tpl.body).text());
            if (tpl.category_id) $backdrop.find('#tkt-rec-cat').val(tpl.category_id).trigger('change');
        });

        // Sin las URLs nuevas cableadas en el blade, el modal se comporta como
        // antes (lista de solo lectura) en vez de quedarse en blanco.
        if (!TKA.urls.recurringOps) {
            withSettings(function (d) {
                var soloLectura = (d && d.recurring) || [];
                pintar(
                    soloLectura.length
                        ? '<div class="tkt-mailitems">' + soloLectura.map(function (r) {
                            return '<div class="tkt-mailitem"><span class="av light"><i class="fa-solid fa-repeat"></i></span>' +
                                '<span class="who"><span class="n">' + escapeHtml(r.name || r.subject) + '</span>' +
                                '<span class="s">' + escapeHtml([r.frequency,
                                    r.next_run_at_human ? 'próxima ' + r.next_run_at_human : null,
                                    r.tickets_created ? r.tickets_created + ' creados' : null].filter(Boolean).join(' · ')) +
                                '</span></span></div>';
                        }).join('') + '</div>'
                        : '<div class="tkt-empty-box">No hay ninguna recurrencia activa.</div>',
                    '<a class="tkt-btn tkt-btn-primary" href="' + escapeHtml(TKA.urls.recurring || '#') + '">Programar recurrencia</a>' +
                    '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>'
                );
            });
            return;
        }

        cargar();
    }

    // ── Modal 31: Notificaciones ──────────────────────────────
    function openNotificationsModal() {
        // Estado REAL del permiso del navegador. El mockup enseña además Slack
        // y Teams, pero este proyecto no tiene esas integraciones, así que no
        // se pintan casillas que no harían nada.
        var perm = (typeof Notification !== 'undefined') ? Notification.permission : 'unsupported';
        var permLabel = { granted: 'Activadas', denied: 'Bloqueadas por el navegador', default: 'Sin conceder', unsupported: 'No compatible' }[perm];

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-bell', kicker: 'Ajustes · notificaciones',
            title: 'Notificaciones', width: 'sm',
            body: '<div class="tkt-side-rows">' + sideRow('Notificaciones del navegador', permLabel, { strong: true, last: true }) + '</div>' +
                  (perm === 'default' ? '<button type="button" class="tkt-btn tkt-w-100" id="tkt-notif-ask">Permitir notificaciones</button>' : '') +
                  (perm === 'denied' ? '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> El navegador tiene bloqueadas las notificaciones de este sitio. Hay que reactivarlas desde su configuración, no se puede pedir de nuevo desde aquí.</div>' : '') +
                  '<div class="tkt-cap tkt-cap-spaced">Avisarme cuando</div>' +
                  '<div id="tkt-notif-prefs"><div class="tkt-empty-box">Cargando tus preferencias de aviso…</div></div>' +
                  '<div id="tkt-notif-always"></div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-notif-save" disabled>Guardar preferencias</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-notif-open">Abrir el panel de avisos</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        // ── Pintado de la lista de eventos ────────────────────────
        // Una fila por evento, con una casilla por canal gobernable. El servidor
        // manda sólo los canales que la via() de esa notificación consulta de
        // verdad: donde el canal está cableado a fuego no viene, y así no se
        // enseña un interruptor que el agente mueve sin efecto.
        function prefsHtml(events) {
            if (!events.length) {
                return '<div class="tkt-empty-box">Ningún aviso de ticket admite ajuste por ahora.</div>';
            }

            return events.map(function (ev) {
                var canales = ev.channels.map(function (ch) {
                    var id = 'tkt-notif-' + ev.key.replace(/\./g, '-') + '-' + ch.key;

                    return '<label class="tkt-check sm tkt-notif-ch" for="' + id + '">' +
                        '<input type="checkbox" id="' + id + '"' +
                            ' data-notif-type="' + escapeHtml(ev.key) + '"' +
                            ' data-notif-channel="' + escapeHtml(ch.key) + '"' +
                            (ch.enabled ? ' checked' : '') + '>' +
                        escapeHtml(ch.label) +
                    '</label>';
                }).join('');

                return '<div class="tkt-notif-row">' +
                    '<div class="tkt-notif-main">' +
                        '<span class="tkt-option-title">' + escapeHtml(ev.label) + '</span>' +
                        (ev.description ? '<span class="tkt-option-sub">' + escapeHtml(ev.description) + '</span>' : '') +
                    '</div>' +
                    '<div class="tkt-notif-channels">' + canales + '</div>' +
                '</div>';
            }).join('');
        }

        // Los avisos sin interruptor se dicen en voz alta en lugar de callarlos:
        // si no, el agente busca el ajuste de "responde el cliente" y no lo
        // encuentra en ninguna parte porque esa via() no pregunta.
        function alwaysHtml(items) {
            if (!items || !items.length) return '';

            return '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i>' +
                '<span>Estos avisos llegan siempre y no se pueden desactivar: ' +
                items.map(function (i) { return escapeHtml(i.charAt(0).toLowerCase() + i.slice(1)); }).join('; ') +
                '.</span></div>';
        }

        function cargar() {
            if (!TKA.urls.notifPrefs) {
                $backdrop.find('#tkt-notif-prefs').html('<div class="tkt-empty-box">Las preferencias de aviso no están disponibles en esta instalación.</div>');

                return;
            }

            $.getJSON(TKA.urls.notifPrefs).done(function (res) {
                var catalogo = res || {};
                $backdrop.find('#tkt-notif-prefs').html(prefsHtml(catalogo.events || []));
                $backdrop.find('#tkt-notif-always').html(alwaysHtml(catalogo.always_on));
                // Sólo se habilita al mover algo: guardar sin cambios escribiría
                // filas iguales a lo que ya hay.
                $backdrop.find('#tkt-notif-save').prop('disabled', true);
            }).fail(function () {
                $backdrop.find('#tkt-notif-prefs').html('<div class="tkt-empty-box">No se han podido cargar tus preferencias de aviso.</div>');
            });
        }

        cargar();

        $backdrop.on('change', '#tkt-notif-prefs input[type="checkbox"]', function () {
            $backdrop.find('#tkt-notif-save').prop('disabled', false);
        });

        $backdrop.on('click', '#tkt-notif-save', function () {
            var $btn = $(this);

            var preferences = $backdrop.find('#tkt-notif-prefs input[type="checkbox"]').map(function () {
                return {
                    notification_type: $(this).data('notif-type'),
                    channel: $(this).data('notif-channel'),
                    // 1/0 y no true/false: jQuery serializa el booleano como
                    // la cadena "true"/"false" en form-urlencoded, y la regla
                    // `boolean` de Laravel solo acepta true/false reales, 1, 0,
                    // "1" y "0" — con la cadena devolvía 422 y no se guardaba
                    // ninguna preferencia.
                    enabled: this.checked ? 1 : 0,
                };
            }).get();

            if (!preferences.length) return;

            $btn.prop('disabled', true);

            // El CSRF lo añade el $.ajaxSetup global del layout, igual que en el
            // resto de llamadas de este archivo.
            $.ajax({
                url: TKA.urls.notifPrefsUpdate,
                method: 'POST',
                headers: { Accept: 'application/json' },
                data: { preferences: preferences },
            }).done(function (res) {
                var msg = (res && res.message) ? res.message : 'Preferencias de aviso guardadas.';
                if (window.toastr) toastr.success(msg); else window.alert(msg);
                closeModal();
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'No se han podido guardar las preferencias.';
                if (window.toastr) toastr.error(msg); else window.alert(msg);
                $btn.prop('disabled', false);
            });
        });

        $backdrop.on('click', '#tkt-notif-ask', function () {
            Notification.requestPermission().then(function () { closeModal(); openNotificationsModal(); });
        });
        // "Abrir el panel de avisos" apuntaba a openNoticesPanel(), que no
        // se llegó a escribir nunca: el botón cerraba el modal y no hacía
        // nada más. El panel de avisos de esta app es el centro de
        // notificaciones del propio panel, así que lleva ahí.
        $backdrop.on('click', '#tkt-notif-open', function () {
            closeModal();
            if (TKA.urls.notificationsIndex) {
                window.location = TKA.urls.notificationsIndex;
            } else if (window.toastr) {
                toastr.info('El panel de avisos no está disponible en esta instalación.');
            }
        });
    }

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
            title: 'Plantillas de ticket', width: 'sm',
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

    // ── Modal 09: Cancelar envío programado ───────────────────
    function openCancelScheduledModal(t, mail) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-calendar-xmark',
            kicker: 'Envíos programados',
            title: 'Cancelar envío programado',
            titleChip: t.ticket_number,
            width: 'sm',
            body: '<div class="tkt-headline">' +
                    '<div class="t">' + escapeHtml(mail.subject || '(sin asunto)') + '</div>' +
                    '<div class="s">Para ' + escapeHtml(mail.to || '—') +
                        (mail.scheduled_at_human ? ' · saldrá el ' + escapeHtml(mail.scheduled_at_human) : '') + '</div>' +
                  '</div>' +
                  '<div class="tkt-pick-list">' +
                    '<button type="button" class="tkt-pick" data-cancel-mode="draft">' +
                        '<span class="av light"><i class="fa-regular fa-file-lines"></i></span>' +
                        '<span class="who"><span class="n">Guardar como borrador</span>' +
                        '<span class="s">Se conserva el contenido y deja de tener hora de envío</span></span></button>' +
                    '<button type="button" class="tkt-pick" data-cancel-mode="delete">' +
                        '<span class="av light"><i class="fa-regular fa-trash-can"></i></span>' +
                        '<span class="who"><span class="n">Eliminar definitivamente</span>' +
                        '<span class="s">El correo desaparece del ticket</span></span></button>' +
                  '</div>' +
                  '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Si la automatización que lo generó sigue activa, podría volver a programarse.</div>',
            foot: '<button type="button" class="tkt-btn" data-modal-close>Volver</button>',
        }));

        $backdrop.on('click', '[data-cancel-mode]', function () {
            var mode = $(this).data('cancel-mode');
            $backdrop.find('[data-cancel-mode]').prop('disabled', true);
            $.ajax({
                url: mail.url_cancel_scheduled,
                method: 'POST',
                data: { mode: mode },
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Envío cancelado');
                    closeModal();
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo cancelar el envío';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $backdrop.find('[data-cancel-mode]').prop('disabled', false);
                },
            });
        });
    }

    // ── Modal 10: Vincular email a ticket ─────────────────────
    function openLinkMailModal(t, mail) {
        var chosen = null;

        function resultsHtml(list, emptyText) {
            if (!list.length) return '<div class="tkt-empty-box">' + escapeHtml(emptyText) + '</div>';
            return list.map(function (o) {
                return '<button type="button" class="tkt-pick' + (chosen && chosen.id === o.id ? ' on' : '') + '" data-target="' + o.id + '">' +
                    '<span class="tkt-shortcode mono">' + escapeHtml(o.ticket_number) + '</span>' +
                    '<span class="who"><span class="n">' + escapeHtml(o.subject || '(sin asunto)') + '</span>' +
                    '<span class="s">' + escapeHtml([o.status_name || o.status_slug, o.customer ? o.customer.name : null].filter(Boolean).join(' · ')) + '</span></span>' +
                    (chosen && chosen.id === o.id ? '<i class="fa-solid fa-check"></i>' : '') +
                '</button>';
            }).join('');
        }

        // Candidatos por defecto: otros tickets del MISMO cliente, que es de
        // donde salen casi todos los correos mal clasificados.
        function sameCustomer() {
            if (!t.customer) return [];
            return TKA.state.tickets.filter(function (x) {
                return x.id !== t.id && x.customer && x.customer.id === t.customer.id;
            });
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-link',
            kicker: 'Tickets · vinculación',
            title: 'Vincular email a ticket',
            width: 'sm',
            body: '<div class="tkt-headline">' +
                    '<div class="t">' + escapeHtml(mail.subject || '(sin asunto)') + '</div>' +
                    '<div class="s">' + escapeHtml((mail.direction === 'inbound' ? 'De ' : 'Para ') + (mail.direction === 'inbound' ? (mail.from || '—') : (mail.to || '—'))) + '</div>' +
                  '</div>' +
                  '<div class="tkt-field"><input type="search" class="tkt-input" id="tkt-link-search" placeholder="Buscar por número, cliente o asunto…" aria-label="Buscar ticket"></div>' +
                  '<div class="tkt-cap">Coincidencias del mismo cliente</div>' +
                  '<div class="tkt-pick-list" id="tkt-link-list">' + resultsHtml(sameCustomer(), 'Sin otros tickets de este cliente en la página actual.') + '</div>' +
                  '<label class="tkt-check"><input type="checkbox" id="tkt-link-thread"> Mover también el resto del hilo</label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-link-confirm" disabled>Vincular</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('input', '#tkt-link-search', function () {
            var q = this.value.trim().toLowerCase();
            var list = q
                ? TKA.state.tickets.filter(function (x) {
                    return x.id !== t.id && (
                        String(x.ticket_number).toLowerCase().indexOf(q) !== -1 ||
                        String(x.subject || '').toLowerCase().indexOf(q) !== -1 ||
                        String(x.customer ? x.customer.name : '').toLowerCase().indexOf(q) !== -1);
                  })
                : sameCustomer();
            $backdrop.find('#tkt-link-list').html(resultsHtml(list, 'Ningún ticket coincide con la búsqueda.'));
        });

        $backdrop.on('click', '[data-target]', function () {
            var id = parseInt($(this).data('target'), 10);
            chosen = TKA.state.tickets.find(function (x) { return x.id === id; }) || null;
            $backdrop.find('[data-target]').removeClass('on').find('.fa-check').remove();
            $(this).addClass('on');
            $backdrop.find('#tkt-link-confirm').prop('disabled', !chosen);
        });

        $backdrop.on('click', '#tkt-link-confirm', function () {
            if (!chosen) return;
            var $btn = $(this).prop('disabled', true).text('Vinculando…');
            $.ajax({
                url: mail.url_link,
                method: 'POST',
                data: { ticket_id: chosen.id, move_thread: $('#tkt-link-thread').is(':checked') ? 1 : 0 },
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Correo vinculado');
                    closeModal();
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo vincular el correo';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text('Vincular');
                },
            });
        });
    }

    // ── Modal 12: Vista previa rápida ─────────────────────────
    // Se abre con la barra espaciadora sobre la fila seleccionada, sin salir
    // de la lista. J/K siguen navegando con la vista previa abierta.
    function openQuickPreviewModal(t) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-eye',
            kicker: 'Ticket · vista rápida',
            title: 'Vista previa rápida',
            titleChip: t.ticket_number,
            width: 'sm',
            body: '<div class="tkt-side-rows">' +
                    sideRow('Cliente', t.customer ? t.customer.name : 'Sin cliente') +
                    sideRow('Asunto', t.subject || '(sin asunto)') +
                    sideRow('Estado', t.status_name || t.status_slug || '—', { strong: true }) +
                    sideRow('Prioridad', priorityLabel(t.priority), { strong: true }) +
                    sideRow('SLA', t.sla_text || '—', { mono: true, last: true }) +
                  '</div>' +
                  (t.last_message_snippet
                    ? '<div class="tkt-field"><label class="tkt-label">Último mensaje</label>' +
                      '<div class="tkt-tpl-preview">' + escapeHtml(t.last_message_snippet) + '</div></div>'
                    : '') +
                  '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Navega con <span class="mono">J</span> / <span class="mono">K</span> sin cerrar la vista previa.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-qp-open">Abrir completo</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-qp-reply">Responder</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        $backdrop.on('click', '#tkt-qp-open', function () { closeModal(); selectTicket(t); });
        $backdrop.on('click', '#tkt-qp-reply', function () { closeModal(); selectTicket(t); openComposeModal(t); });
    }

    // Modal de confirmación para reenviar el último correo del ticket —
    // mismos datos que antes mostraba el window.confirm() (destinatario,
    // asunto), solo que en formato modal. 'mail' aquí es last_outbound_mail
    // ({to, subject, url_resend}), no el bloque 'mail' general del ticket.
    // ── Modal 05: Detalle de entrega ──────────────────────────
    var TRACE_ICON = { queued: 'fa-inbox', sent: 'fa-paper-plane', delivered: 'fa-circle-check', bounced: 'fa-arrow-rotate-left', failed: 'fa-triangle-exclamation', opened: 'fa-envelope-open', clicked: 'fa-arrow-pointer' };

    function openDeliveryModal(t, mail, trace) {
        var events = trace || [];
        var headline = mail.status === 'delivered' ? 'Entregado al servidor destino'
            : (mail.status === 'bounced' ? 'Rebotado por el servidor destino'
            : (mail.status === 'failed' ? 'No se pudo entregar'
            : (mail.status === 'sent' ? 'Aceptado por el servidor de correo' : 'En cola de envío')));
        var when = mail.delivered_at_human || mail.sent_at_human || mail.created_at_human || '';

        var timeline = events.length
            ? '<div class="tkt-timeline">' + events.map(function (ev) {
                return '<div class="tkt-timeline-item">' +
                    '<div class="tkt-timeline-dot done"><i class="fa-solid ' + (TRACE_ICON[ev.type] || 'fa-circle') + '"></i></div>' +
                    '<div class="tkt-timeline-body">' +
                        '<div class="t">' + escapeHtml(ev.label || ev.type) + '</div>' +
                        '<div class="s mono" title="' + escapeHtml(ev.at || '') + '">' + escapeHtml(ev.at_human || ev.at || '') + '</div>' +
                    '</div></div>';
              }).join('') + '</div>'
            : '<div class="tkt-empty-box">Sin eventos de trazabilidad: este correo no tiene seguimiento asociado en el log de emails.</div>';

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-route',
            kicker: 'Correo · entrega',
            title: 'Detalle de entrega',
            titleChip: t.ticket_number,
            width: 'sm',
            body: '<div class="tkt-headline' + (mail.status === 'bounced' || mail.status === 'failed' ? ' bad' : '') + '">' +
                    '<div class="t">' + escapeHtml(headline) + '</div>' +
                    (when ? '<div class="s mono">' + escapeHtml(when) + '</div>' : '') +
                  '</div>' +
                  (mail.delivery_error ? '<div class="tkt-note"><i class="fa-solid fa-triangle-exclamation"></i> ' + escapeHtml(mail.delivery_error) + '</div>' : '') +
                  '<div class="tkt-cap">Recorrido</div>' + timeline +
                  '<div class="tkt-side-rows">' +
                    sideRow('Message-ID', mail.message_id || '—', { mono: true }) +
                    sideRow('Estado BD', mail.status || '—', { mono: true }) +
                    sideRow('Destinatario', mail.to || '—', { mono: true, last: true }) +
                  '</div>',
            foot: (mail.message_id ? '<button type="button" class="tkt-btn" data-copy="' + escapeHtml(mail.message_id) + '">Copiar Message-ID</button>' : '') +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        $backdrop.on('click', '[data-copy]', function () {
            if (!navigator.clipboard) return;
            navigator.clipboard.writeText($(this).data('copy')).then(function () {
                if (window.toastr) toastr.success('Message-ID copiado');
            });
        });
    }

    // ── Modal 06: Email rebotado ──────────────────────────────
    function openBounceModal(t, mail) {
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-arrow-rotate-left',
            iconClass: 'danger',
            kicker: 'Correo · rebote',
            title: 'Email rebotado',
            titleChip: t.ticket_number,
            width: 'sm',
            body: '<div class="tkt-headline bad">' +
                    '<div class="t mono">' + escapeHtml(mail.delivery_error || 'Rebote sin detalle del servidor') + '</div>' +
                    '<div class="s">El envío a <strong>' + escapeHtml(mail.to || '—') + '</strong> no llegó a su destino.</div>' +
                  '</div>' +
                  '<div class="tkt-side-rows">' +
                    sideRow('Asunto', mail.subject || '—') +
                    sideRow('Estado', mail.status || '—', { mono: true, last: true }) +
                  '</div>' +
                  '<div class="tkt-field"><label class="tkt-label" for="tkt-bounce-to">Corregir destinatario<span class="req">*</span></label>' +
                    '<input type="email" class="tkt-input" id="tkt-bounce-to" value="' + escapeHtml(mail.to || '') + '"></div>' +
                  '<label class="tkt-check"><input type="checkbox" id="tkt-bounce-contact" checked> Actualizar el email del contacto</label>' +
                  '<label class="tkt-check"><input type="checkbox" id="tkt-bounce-suppress" checked> Añadir la dirección anterior a la lista de supresión</label>' +
                  '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Se dejará una nota interna en el ticket con el cambio de destinatario.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-bounce-confirm">Corregir y reenviar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Descartar</button>',
        }));

        $backdrop.on('click', '#tkt-bounce-confirm', function () {
            var to = ($('#tkt-bounce-to').val() || '').trim();
            if (!to) {
                if (window.toastr) toastr.error('Indica el destinatario corregido');
                return;
            }
            if (to === mail.to) {
                if (window.toastr) toastr.error('El destinatario es el mismo que rebotó: corrígelo antes de reenviar');
                return;
            }
            var $btn = $(this).prop('disabled', true).text('Reenviando…');
            $.ajax({
                url: mail.url_fix_bounce,
                method: 'POST',
                data: {
                    to: to,
                    update_contact: $('#tkt-bounce-contact').is(':checked') ? 1 : 0,
                    suppress_old: $('#tkt-bounce-suppress').is(':checked') ? 1 : 0,
                },
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Destinatario corregido y correo reenviado');
                    closeModal();
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo corregir el rebote';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text('Corregir y reenviar');
                },
            });
        });
    }

    // ── Modal 07: Reenviar email ──────────────────────────────
    function openResendMailModal(t, mail) {
        var attCount = (mail.attachments || []).length;
        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-rotate-right',
            kicker: 'Correo · reenvío',
            title: 'Reenviar email',
            titleChip: t.ticket_number,
            width: 'sm',
            body: '<div class="tkt-headline">' +
                    '<div class="t">' + escapeHtml(mail.subject || '(sin asunto)') + '</div>' +
                    '<div class="s">' + escapeHtml(mail.sent_at_human || mail.created_at_human || '') +
                        (attCount ? ' · ' + attCount + (attCount === 1 ? ' adjunto' : ' adjuntos') : '') + '</div>' +
                  '</div>' +
                  '<div class="tkt-field"><label class="tkt-label" for="tkt-resend-to">Destinatario<span class="req">*</span></label>' +
                    '<input type="email" class="tkt-input" id="tkt-resend-to" value="' + escapeHtml(mail.to || '') + '"></div>' +
                  '<div class="tkt-pick-list">' +
                    '<label class="tkt-pick as-option on"><input type="radio" name="tkt-resend-mode" value="asis" checked>' +
                        '<span class="who"><span class="n">Reenviar tal cual</span><span class="s">Misma copia, con sus adjuntos</span></span></label>' +
                    '<label class="tkt-pick as-option"><input type="radio" name="tkt-resend-mode" value="edit">' +
                        '<span class="who"><span class="n">Editar antes de reenviar</span><span class="s">Abre el redactor con este contenido</span></span></label>' +
                    (attCount ? '<label class="tkt-pick as-option"><input type="radio" name="tkt-resend-mode" value="noatt">' +
                        '<span class="who"><span class="n">Reenviar sin adjuntos</span><span class="s">Solo el texto del mensaje</span></span></label>' : '') +
                  '</div>' +
                  '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Se generará un nuevo Message-ID enlazado al hilo original mediante <span class="mono">In-Reply-To</span>.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-resend-confirm">Reenviar ahora</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        $backdrop.on('change', '[name="tkt-resend-mode"]', function () {
            $backdrop.find('.tkt-pick.as-option').removeClass('on');
            $(this).closest('.tkt-pick').addClass('on');
            $backdrop.find('#tkt-resend-confirm').text($(this).val() === 'edit' ? 'Abrir el redactor' : 'Reenviar ahora');
        });

        $backdrop.on('click', '#tkt-resend-confirm', function () {
            var mode = $backdrop.find('[name="tkt-resend-mode"]:checked').val();
            var to = ($('#tkt-resend-to').val() || '').trim();
            if (!to) {
                if (window.toastr) toastr.error('Indica el destinatario');
                return;
            }

            // "Editar antes de reenviar" no manda nada: abre el redactor ya
            // relleno, y el envío sale por el flujo normal del modal 01.
            if (mode === 'edit') {
                composeDraft = {
                    ticketId: t.id, to: to, cc: '', bcc: '',
                    subject: mail.subject || '', body: mail.body_text || '',
                    from: (TKA.state.senders || [])[0] || '',
                    attachToThread: true, scheduledAt: null, cancelIfReplies: false, files: [],
                };
                closeModal();
                openComposeModal(t);
                return;
            }

            var $btn = $(this).prop('disabled', true).text('Reenviando…');
            $.ajax({
                url: mail.url_resend,
                method: 'POST',
                data: { to: to, without_attachments: mode === 'noatt' ? 1 : 0 },
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Correo reenviado');
                    closeModal();
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo reenviar el correo';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text('Reenviar ahora');
                },
            });
        });
    }

    // ── Modal 49: Previsualizar adjunto ───────────────────────
    var PREVIEWABLE = /\.(png|jpe?g|gif|webp|svg|pdf)$/i;

    function openFilePreviewModal(t, file) {
        var isImage = /\.(png|jpe?g|gif|webp|svg)$/i.test(file.name || '');
        var isPdf = /\.pdf$/i.test(file.name || '');
        var body;

        if (isImage) {
            body = '<div class="tkt-preview-stage"><img src="' + escapeHtml(file.url_download) + '" alt="' + escapeHtml(file.name) + '"></div>';
        } else if (isPdf) {
            body = '<div class="tkt-preview-stage"><iframe src="' + escapeHtml(file.url_download) + '" title="' + escapeHtml(file.name) + '"></iframe></div>';
        } else {
            // Sin visor para este tipo: se dice claramente en vez de dejar un
            // marco en blanco que parece roto.
            body = '<div class="tkt-empty-box">Este tipo de archivo no se puede previsualizar en el navegador. Descárgalo para abrirlo.</div>';
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-file',
            kicker: 'Adjunto · previsualización',
            titleChip: file.name || '',
            title: 'Previsualizar adjunto',
            width: 'lg',
            body: body +
                '<div class="tkt-side-rows">' +
                    sideRow('tamaño', file.size ? formatFileSize(file.size) : '—', { mono: true }) +
                    sideRow('origen', file.source === 'customer' ? 'Enviado por el cliente' : 'Enviado por un agente') +
                    sideRow('fecha', file.created_at_human || '—') +
                    sideRow('ticket', t.ticket_number, { mono: true, last: true }) +
                '</div>',
            foot: '<a class="tkt-btn tkt-btn-primary" href="' + escapeHtml(file.url_download) + '" download>Descargar</a>' +
                  '<a class="tkt-btn" href="' + escapeHtml(file.url_download) + '" target="_blank" rel="noopener">Abrir en pestaña nueva</a>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cerrar</button>',
        }));

        return $backdrop;
    }

    // ── Modal 50: Traducir respuesta ──────────────────────────
    var TRANSLATE_LANGS = [
        { code: 'es', label: 'Español' }, { code: 'en', label: 'Inglés' },
        { code: 'pt', label: 'Portugués' }, { code: 'fr', label: 'Francés' },
        { code: 'de', label: 'Alemán' }, { code: 'it', label: 'Italiano' },
    ];

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
            if (window.toastr) toastr.info('Escribe primero el texto que quieres traducir');
            return;
        }
        // Idioma detectado del cliente, si el contacto lo tiene guardado.
        var detected = (TKA.state.currentDetail && TKA.state.currentDetail.customer && TKA.state.currentDetail.customer.language) || null;

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-language',
            kicker: 'Idioma · traducir',
            title: 'Traducir respuesta',
            titleChip: t.ticket_number,
            width: 'sm',
            body: '<div class="tkt-field"><label class="tkt-label" for="tkt-tr-target">Idioma del cliente' +
                    (detected ? '<span class="hint">detectado: ' + escapeHtml(detected) + '</span>' : '') + '</label>' +
                    '<select class="tkt-input" id="tkt-tr-target" data-no-select2>' + TRANSLATE_LANGS.map(function (l) {
                        return '<option value="' + l.code + '"' + (detected && detected.indexOf(l.code) === 0 ? ' selected' : '') + '>' + escapeHtml(l.label) + '</option>';
                    }).join('') + '</select></div>' +
                  '<div class="tkt-field"><label class="tkt-label">Tu texto</label>' +
                    '<div class="tkt-tpl-preview">' + escapeHtml(source) + '</div></div>' +
                  '<div class="tkt-field"><label class="tkt-label">Se enviará al cliente</label>' +
                    '<div class="tkt-tpl-preview" id="tkt-tr-out">Pulsa "Traducir" para ver el resultado.</div></div>' +
                  '<label class="tkt-check"><input type="checkbox" id="tkt-tr-keep"> Adjuntar la versión original al pie</label>' +
                  '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> La traducción sustituye el cuerpo del redactor; el texto original solo se conserva si marcas la casilla.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-tr-use" disabled>Usar traducción</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-tr-run">Traducir</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-tr-back">Cancelar</button>',
        }));

        var translated = null;

        $backdrop.on('click', '#tkt-tr-run', function () {
            var $btn = $(this).prop('disabled', true).text('Traduciendo…');
            $.ajax({
                url: t.url_translate_text,
                method: 'POST',
                data: { text: source, target_lang: $('#tkt-tr-target').val() },
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    $btn.prop('disabled', false).text('Volver a traducir');
                    if (!resp || !resp.translated) {
                        $backdrop.find('#tkt-tr-out').text('El motor de traducción no devolvió resultado para este texto.');
                        return;
                    }
                    translated = resp.text;
                    $backdrop.find('#tkt-tr-out').text(translated);
                    $backdrop.find('#tkt-tr-use').prop('disabled', false);
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).text('Traducir');
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo traducir el texto';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
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
            kicker: 'Ticket · responder',
            title: 'Redactar email',
            titleChip: t.ticket_number,
            width: 'lg',
            body: '' +
                '<div class="tkt-field">' +
                    '<label class="tkt-label" for="tkt-compose-to">Para<span class="req">*</span>' +
                        '<button type="button" class="tkt-label-action" id="tkt-compose-ccbcc">+ CC · CCO</button>' +
                    '</label>' +
                    '<input type="email" class="tkt-input" id="tkt-compose-to" value="' + escapeHtml(composeDraft.to) + '"></div>' +
                '<div class="tkt-field-row" id="tkt-compose-ccbcc-row"' + (hasCcBcc ? '' : ' hidden') + '>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-compose-cc">CC <span class="hint">separados por coma</span></label><input type="text" class="tkt-input" id="tkt-compose-cc" value="' + escapeHtml(composeDraft.cc) + '"></div>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-compose-bcc">CCO</label><input type="text" class="tkt-input" id="tkt-compose-bcc" value="' + escapeHtml(composeDraft.bcc) + '"></div>' +
                '</div>' +
                '<div class="tkt-field"><label class="tkt-label" for="tkt-compose-subject">Asunto<span class="req">*</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-compose-subject" value="' + escapeHtml(composeDraft.subject) + '"></div>' +
                '<div class="tkt-field-row">' +
                    '<div class="tkt-field"><label class="tkt-label">Plantilla</label>' +
                        '<button type="button" class="tkt-input tkt-input-btn" id="tkt-compose-tpl"><span>Elegir plantilla…</span><i class="fa-solid fa-chevron-down"></i></button></div>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-compose-from">Remitente</label>' +
                        (senders.length > 1
                            ? '<select class="tkt-input" id="tkt-compose-from" data-no-select2>' + senders.map(function (a) {
                                return '<option value="' + escapeHtml(a) + '"' + (a === composeDraft.from ? ' selected' : '') + '>' + escapeHtml(a) + '</option>';
                              }).join('') + '</select>'
                            : '<input type="text" class="tkt-input" id="tkt-compose-from" value="' + escapeHtml(composeDraft.from) + '" readonly title="Única dirección de envío configurada">') +
                    '</div>' +
                '</div>' +
                '<div class="tkt-field">' +
                    '<label class="tkt-label" for="tkt-compose-body">Mensaje<span class="req">*</span>' +
                        '<span class="hint">variables: {{cliente}} {{ticket}}</span></label>' +
                    '<div class="tkt-composer">' +
                        '<div class="tkt-composer-bar">' +
                            '<button type="button" data-wrap="**" title="Negrita"><i class="fa-solid fa-bold"></i></button>' +
                            '<button type="button" data-wrap="_" title="Cursiva"><i class="fa-solid fa-italic"></i></button>' +
                            '<button type="button" data-prefix="- " title="Lista"><i class="fa-solid fa-list-ul"></i></button>' +
                            '<button type="button" id="tkt-compose-link" title="Enlace"><i class="fa-solid fa-link"></i></button>' +
                            '<button type="button" id="tkt-compose-translate" class="right" title="Traducir antes de enviar"><i class="fa-solid fa-language"></i></button>' +
                            '<button type="button" id="tkt-compose-attach-open" title="Adjuntar archivos"><i class="fa-solid fa-paperclip"></i></button>' +
                        '</div>' +
                        '<textarea class="tkt-composer-area" id="tkt-compose-body" placeholder="Escribe la respuesta…">' + escapeHtml(composeDraft.body) + '</textarea>' +
                    '</div>' +
                '</div>' +
                '<div id="tkt-compose-files">' + composeFilesHtml() + '</div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-compose-thread"' + (composeDraft.attachToThread ? ' checked' : '') + '> Adjuntar el email al hilo del ticket</label>' +
                (composeDraft.scheduledAt
                    ? '<div class="tkt-note ok" id="tkt-compose-sched-note"><i class="fa-regular fa-clock"></i> Programado para <strong>' + escapeHtml(composeScheduleLabel()) + '</strong>' +
                        '<button type="button" class="tkt-link-btn" id="tkt-compose-sched-clear">quitar</button></div>'
                    : ''),
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-compose-confirm">' + (composeDraft.scheduledAt ? 'Programar envío' : 'Enviar ahora') + '</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-compose-schedule">Programar</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-compose-draft">Borrador</button>',
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
                if (window.toastr) toastr.success('Borrador guardado en este navegador');
            } catch (e) {
                if (window.toastr) toastr.error('No se pudo guardar el borrador');
            }
            closeModal();
        });

        $backdrop.on('click', '#tkt-compose-confirm', function () {
            readComposeDraft();
            if (!composeDraft.to || !composeDraft.subject || !composeDraft.body) {
                if (window.toastr) toastr.error('Rellena destinatario, asunto y mensaje');
                return;
            }

            var scheduled = composeDraft.scheduledAt;
            var $btn = $(this).prop('disabled', true).text(scheduled ? 'Programando…' : 'Enviando…');
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
                    if (window.toastr) toastr.success((resp && resp.message) || (scheduled ? 'Email programado' : 'Email enviado'));
                    try { window.localStorage.removeItem('tkt-draft-' + t.id); } catch (e) { /* sin localStorage */ }
                    composeDraft = null;
                    closeModal();
                    fetchDetailData(t);
                },
                error: function (xhr) {
                    var msg = (xhr.responseJSON && (xhr.responseJSON.message || (xhr.responseJSON.errors && Object.values(xhr.responseJSON.errors)[0][0]))) || 'No se pudo enviar el email';
                    if (window.toastr) toastr.error(msg); else window.alert(msg);
                    $btn.prop('disabled', false).text(scheduled ? 'Programar envío' : 'Enviar ahora');
                },
            });
        });
    }

    function composeFilesHtml() {
        if (!composeDraft || !composeDraft.files.length) return '';
        return '<div class="tkt-att-strip compact"><span class="tkt-cap">Adjuntos · ' + composeDraft.files.length + '</span>' +
            composeDraft.files.map(function (f, i) {
                return '<span class="tkt-att-pill"><i class="' + fileIconClass(f.name) + '"></i>' + escapeHtml(f.name) +
                    '<span class="mono">' + formatFileSize(f.size) + '</span>' +
                    '<button type="button" data-file-remove="' + i + '" title="Quitar"><i class="fa-solid fa-xmark"></i></button></span>';
            }).join('') + '</div>';
    }

    // ── Modal 02: Programar envío ─────────────────────────────
    function openScheduleModal(t) {
        function slot(hoursFromNow, atHour) {
            var d = new Date();
            if (atHour != null) {
                d.setHours(atHour, 0, 0, 0);
                if (d <= new Date()) d.setDate(d.getDate() + 1);
            } else {
                d.setHours(d.getHours() + hoursFromNow, 0, 0, 0);
            }
            return d;
        }
        var tomorrow = slot(null, 8);
        var endOfDay = slot(null, 18);
        var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
        var iso = function (d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes()); };
        var human = function (d) { return pad(d.getDate()) + ' ' + MONTH_SHORT[d.getMonth()] + ' · ' + pad(d.getHours()) + ':' + pad(d.getMinutes()); };

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-clock',
            kicker: 'Ticket · programar envío',
            title: 'Programar envío',
            titleChip: t.ticket_number,
            width: 'sm',
            body: '' +
                '<div class="tkt-pick-list">' +
                    '<button type="button" class="tkt-pick" data-slot="' + iso(tomorrow) + '">' +
                        '<span class="av light"><i class="fa-solid fa-mug-hot"></i></span>' +
                        '<span class="who"><span class="n">Mañana por la mañana</span><span class="s">' + human(tomorrow) + '</span></span></button>' +
                    '<button type="button" class="tkt-pick" data-slot="' + iso(endOfDay) + '">' +
                        '<span class="av light"><i class="fa-solid fa-business-time"></i></span>' +
                        '<span class="who"><span class="n">Hoy al final del día</span><span class="s">' + human(endOfDay) + '</span></span></button>' +
                    '<button type="button" class="tkt-pick" id="tkt-sched-custom">' +
                        '<span class="av light"><i class="fa-regular fa-calendar"></i></span>' +
                        '<span class="who"><span class="n">Fecha personalizada</span><span class="s">Elegir día y hora</span></span></button>' +
                '</div>' +
                '<div class="tkt-field-row" id="tkt-sched-custom-row" hidden>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-sched-date">Fecha</label><input type="date" class="tkt-input" id="tkt-sched-date"></div>' +
                    '<div class="tkt-field"><label class="tkt-label" for="tkt-sched-time">Hora</label><input type="time" class="tkt-input" id="tkt-sched-time" value="18:00"></div>' +
                '</div>' +
                '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> El envío respeta el horario laboral configurado. Fuera de horario se difiere al siguiente día hábil.</div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-sched-cancel"' + (composeDraft && composeDraft.cancelIfReplies ? ' checked' : '') + '> Cancelar si el cliente responde antes</label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-sched-confirm">Programar</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-sched-cancel-btn">Cancelar</button>',
        }));

        function pick(value) {
            composeDraft.scheduledAt = value;
            composeDraft.cancelIfReplies = $('#tkt-sched-cancel').is(':checked');
            closeModal();
            openComposeModal(t);
        }

        $backdrop.on('click', '[data-slot]', function () { pick($(this).data('slot')); });
        $backdrop.on('click', '#tkt-sched-custom', function () {
            document.getElementById('tkt-sched-custom-row').hidden = false;
            $('#tkt-sched-date').trigger('focus');
        });
        $backdrop.on('click', '#tkt-sched-confirm', function () {
            var date = $('#tkt-sched-date').val();
            var time = $('#tkt-sched-time').val() || '18:00';
            if (!date) {
                if (window.toastr) toastr.error('Elige una fecha o uno de los atajos de arriba');
                return;
            }
            pick(date + 'T' + time);
        });
        $backdrop.on('click', '#tkt-sched-cancel-btn', function () { closeModal(); openComposeModal(t); });
    }

    // ── Modal 03: Plantillas de email ─────────────────────────
    function openTemplatesModal(t) {
        var all = TKA.state.cannedReplies || [];
        var selected = null;

        function listHtml(filter) {
            var q = String(filter || '').trim().toLowerCase();
            var list = all.filter(function (r) {
                return !q || String(r.title).toLowerCase().indexOf(q) !== -1 ||
                    String(r.short_code || '').toLowerCase().indexOf(q) !== -1;
            });
            if (!list.length) return '<div class="tkt-empty-box">Ninguna plantilla coincide con la búsqueda.</div>';
            return list.map(function (r) {
                return '<button type="button" class="tkt-pick' + (selected && selected.id === r.id ? ' on' : '') + '" data-tpl="' + r.id + '">' +
                    // short_code se guarda unas veces con "/" y otras sin él,
                    // así que se normaliza en vez de prefijar a ciegas (si no,
                    // salían atajos como "//bienvenida").
                    '<span class="tkt-shortcode mono">' + (r.short_code ? escapeHtml('/' + String(r.short_code).replace(/^\/+/, '')) : '—') + '</span>' +
                    '<span class="who"><span class="n">' + escapeHtml(r.title) + '</span></span>' +
                    (selected && selected.id === r.id ? '<i class="fa-solid fa-check"></i>' : '') +
                '</button>';
            }).join('');
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-file-lines',
            kicker: 'Respuestas · plantillas',
            title: 'Plantillas de email',
            width: 'sm',
            body: '<div class="tkt-field"><input type="search" class="tkt-input" id="tkt-tpl-search" placeholder="Buscar plantilla…" aria-label="Buscar plantilla"></div>' +
                '<div class="tkt-pick-list" id="tkt-tpl-list">' + listHtml('') + '</div>' +
                '<div class="tkt-tpl-preview" id="tkt-tpl-preview">Elige una plantilla para ver su contenido.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-tpl-insert" disabled>Insertar plantilla</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-tpl-edit">Editar</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-tpl-back">Volver</button>',
        }));

        $backdrop.on('input', '#tkt-tpl-search', function () {
            $backdrop.find('#tkt-tpl-list').html(listHtml(this.value));
        });
        $backdrop.on('click', '[data-tpl]', function () {
            var id = $(this).data('tpl');
            selected = all.find(function (r) { return String(r.id) === String(id); }) || null;
            $backdrop.find('#tkt-tpl-list').html(listHtml($('#tkt-tpl-search').val()));
            $backdrop.find('#tkt-tpl-preview').text(selected ? selected.content : '');
            $backdrop.find('#tkt-tpl-insert').prop('disabled', !selected);
        });
        $backdrop.on('click', '#tkt-tpl-insert', function () {
            if (!selected) return;
            // Se AÑADE al final en vez de reemplazar: si el agente ya había
            // escrito algo, sustituirlo en silencio le borraría el trabajo.
            composeDraft.body = composeDraft.body
                ? composeDraft.body.replace(/\s*$/, '') + '\n\n' + selected.content
                : selected.content;
            closeModal();
            openComposeModal(t);
        });
        $backdrop.on('click', '#tkt-tpl-back', function () { closeModal(); openComposeModal(t); });
        $backdrop.on('click', '#tkt-tpl-edit', function () {
            if (!selected) return;
            closeModal();
            openTemplateEditorModal(selected, function () { openTemplatesModal(t); });
        });
    }

    // ── Modal 04: Adjuntar archivos ───────────────────────────
    function openAttachModal(t) {
        var staged = composeDraft.files.slice();
        var MAX_BYTES = 10 * 1024 * 1024;

        function stagedHtml() {
            if (!staged.length) return '';
            return '<div class="tkt-cap">Seleccionados · ' + staged.length + '</div>' +
                '<div class="tkt-file-rows">' + staged.map(function (f, i) {
                    return '<div class="tkt-file-row"><i class="' + fileIconClass(f.name) + '"></i>' +
                        '<span class="n">' + escapeHtml(f.name) + '</span>' +
                        '<span class="mono">' + formatFileSize(f.size) + '</span>' +
                        '<button type="button" data-staged-remove="' + i + '" title="Quitar"><i class="fa-solid fa-xmark"></i></button></div>';
                }).join('') + '</div>';
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-paperclip',
            kicker: 'Ticket · adjuntos',
            title: 'Adjuntar archivos',
            titleChip: t.ticket_number,
            width: 'sm',
            body: '<label class="tkt-dropzone" id="tkt-dropzone">' +
                    '<i class="fa-solid fa-cloud-arrow-up"></i>' +
                    '<span class="t">Arrastra archivos aquí</span>' +
                    '<span class="s">PDF, DOCX, XLSX, PNG · máx. 10 MB por archivo</span>' +
                    '<input type="file" id="tkt-attach-input" multiple hidden>' +
                  '</label>' +
                  '<div id="tkt-attach-staged">' + stagedHtml() + '</div>' +
                  '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Los adjuntos se guardan en el ticket junto al email enviado.</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-attach-confirm">Adjuntar ' + (staged.length || '') + (staged.length === 1 ? ' archivo' : ' archivos') + '</button>' +
                  '<button type="button" class="tkt-btn" id="tkt-attach-back">Volver</button>',
        }));

        function refresh() {
            $backdrop.find('#tkt-attach-staged').html(stagedHtml());
            $backdrop.find('#tkt-attach-confirm').text('Adjuntar ' + (staged.length || '') + (staged.length === 1 ? ' archivo' : ' archivos'));
        }

        function add(fileList) {
            Array.prototype.forEach.call(fileList, function (f) {
                if (f.size > MAX_BYTES) {
                    if (window.toastr) toastr.error(f.name + ' supera los 10 MB');
                    return;
                }
                staged.push(f);
            });
            refresh();
        }

        $backdrop.on('change', '#tkt-attach-input', function () { add(this.files); this.value = ''; });
        $backdrop.on('click', '[data-staged-remove]', function () {
            staged.splice(parseInt($(this).data('staged-remove'), 10), 1);
            refresh();
        });
        // Arrastrar y soltar de verdad, que es lo que promete el copy.
        $backdrop.on('dragover', '#tkt-dropzone', function (e) { e.preventDefault(); $(this).addClass('over'); });
        $backdrop.on('dragleave drop', '#tkt-dropzone', function () { $(this).removeClass('over'); });
        $backdrop.on('drop', '#tkt-dropzone', function (e) {
            e.preventDefault();
            add(e.originalEvent.dataTransfer.files);
        });
        $backdrop.on('click', '#tkt-attach-confirm', function () {
            composeDraft.files = staged;
            closeModal();
            openComposeModal(t);
        });
        $backdrop.on('click', '#tkt-attach-back', function () { closeModal(); openComposeModal(t); });
    }

    // Picker simplificado por prompt() (el mockup abre un modal de
    // participantes con buscador) — backend real, tres preguntas mínimas.
    function openFollowupModal(t) {
        var d = TKA.state.currentDetail;
        var yaProgramados = ((d && d.followups) || []).filter(function (f) { return f.state === 'pending'; });

        // Plantillas de secuencia del mockup: en vez de teclear tres fechas,
        // se elige el ritmo y se calculan los pasos. Los días son relativos a
        // ahora, que es como se piensa un seguimiento ("recuérdamelo en dos
        // días y otra vez a la semana").
        var RITMOS = [
            { key: 'single', label: 'Un solo recordatorio', sub: 'en 2 días', dias: [2] },
            { key: 'short', label: 'Seguimiento corto', sub: 'a los 2 y 5 días', dias: [2, 5] },
            { key: 'long', label: 'Seguimiento largo', sub: 'a los 3, 7 y 14 días', dias: [3, 7, 14] },
        ];

        var pad = function (n) { return String(n).padStart(2, '0'); };
        var enDias = function (dias) {
            var f = new Date();
            f.setDate(f.getDate() + dias);
            f.setHours(9, 0, 0, 0);
            return f;
        };
        var fmtLocal = function (f) {
            return f.getFullYear() + '-' + pad(f.getMonth() + 1) + '-' + pad(f.getDate()) +
                'T' + pad(f.getHours()) + ':' + pad(f.getMinutes());
        };
        var fmtHumano = function (f) {
            return f.toLocaleString('es-ES', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
        };

        function pasosHtml(ritmo) {
            return ritmo.dias.map(function (dias, i) {
                var fecha = enDias(dias);
                return '<div class="tkt-step-row">' +
                    '<span class="tkt-step-num">' + (i + 1) + '</span>' +
                    '<span class="tkt-step-when">' + escapeHtml(fmtHumano(fecha)) + '</span>' +
                    '<span class="tkt-step-rel">' + (dias === 1 ? 'mañana' : 'en ' + dias + ' días') + '</span>' +
                    '<input type="hidden" data-step-at="' + fmtLocal(fecha) + '">' +
                '</div>';
            }).join('');
        }

        var $backdrop = openModal(modalShell({
            icon: 'fa-regular fa-bell',
            kicker: 'Automatización · seguimientos',
            titleChip: t.ticket_number,
            title: 'Secuencia de seguimiento',
            width: 'sm',
            body: '' +
                (yaProgramados.length
                    ? '<div class="tkt-note"><i class="fa-solid fa-circle-info"></i> Este ticket ya tiene ' +
                        yaProgramados.length + (yaProgramados.length === 1 ? ' paso pendiente' : ' pasos pendientes') +
                        '. Los nuevos se añaden a los que hay.</div>'
                    : '') +
                '<div class="tkt-field"><label class="tkt-label">Ritmo</label>' +
                    RITMOS.map(function (r, i) {
                        return '<label class="tkt-option' + (i === 0 ? ' on' : '') + '" data-ritmo="' + r.key + '">' +
                            '<input type="radio" name="tkt-followup-ritmo" value="' + r.key + '"' +
                            (i === 0 ? ' checked' : '') + ' class="tkt-m0">' +
                            '<span><span class="tkt-option-title">' + r.label + '</span>' +
                            '<br><span class="tkt-option-sub">' + r.sub + '</span></span></label>';
                    }).join('') +
                '</div>' +
                '<div class="tkt-field"><label class="tkt-label">Pasos</label>' +
                    '<div id="tkt-followup-steps">' + pasosHtml(RITMOS[0]) + '</div></div>' +
                '<div class="tkt-field"><label class="tkt-label">Nota <span class="hint">opcional, se repite en cada paso</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-followup-note" maxlength="1000" placeholder="Motivo del recordatorio…"></div>' +
                // Plantilla del mockup: si se elige una, cada paso manda ese
                // correo de verdad al cliente (además del recordatorio interno
                // de siempre) al vencer -- ver SendDueTicketFollowupsCommand.
                // optionsHtml() no sirve aquí: da por hecho item.name, y
                // TicketCannedReply usa item.title.
                '<div class="tkt-field"><label class="tkt-label">Plantilla <span class="hint">opcional — si se elige, se envía al cliente</span></label>' +
                    '<select class="tkt-select" id="tkt-followup-template" data-no-select2><option value="">Sin plantilla (solo recordatorio interno)</option>' +
                        (TKA.state.cannedReplies || []).map(function (r) {
                            return '<option value="' + r.id + '">' + escapeHtml(r.title) + '</option>';
                        }).join('') +
                    '</select></div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-followup-stop" checked> ' +
                    'Detener la secuencia si el cliente responde</label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-followup-confirm">Programar</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
            footTwoCol: false,
        }));

        $backdrop.on('click', '[data-ritmo]', function () {
            $backdrop.find('[data-ritmo]').removeClass('on');
            $(this).addClass('on').find('input').prop('checked', true);
            var ritmo = RITMOS.find(function (r) { return r.key === $(this).data('ritmo'); }.bind(this));
            $backdrop.find('#tkt-followup-steps').html(pasosHtml(ritmo));
        });

        $backdrop.on('click', '#tkt-followup-confirm', function () {
            var plantilla = $('#tkt-followup-template').val() || null;
            var pasos = $backdrop.find('[data-step-at]').map(function () {
                return { scheduled_at: $(this).data('step-at'), note: $('#tkt-followup-note').val() || null, canned_reply_id: plantilla };
            }).get();

            if (!pasos.length) { if (window.toastr) toastr.error('Elige un ritmo'); return; }

            $.ajax({
                url: t.url_followups_store,
                method: 'POST',
                data: {
                    steps: pasos,
                    cancel_if_customer_replies: $('#tkt-followup-stop').is(':checked') ? 1 : 0,
                },
                headers: { Accept: 'application/json' },
                success: function (resp) {
                    if (window.toastr) toastr.success((resp && resp.message) || 'Secuencia programada');
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
            title: 'Conversación paralela',
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
    var NOTE_COLOR_LABELS = { yellow: 'Amarillo', blue: 'Azul', green: 'Verde', red: 'Rojo', purple: 'Morado', orange: 'Naranja' };

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

    // Agentes mencionados en las notas del ticket (@nombre). El backend no
    // guarda las menciones en una tabla aparte, así que se extraen del propio
    // texto — que es donde el autocompletado del editor las escribe.
    // El mockup rotula "0 / 2000", pero StoreTicketNoteRequest valida
    // max:5000: se usa el límite real para que el contador no prometa un
    // tope distinto del que aplica el servidor.
    var NOTE_MAX_LENGTH = 5000;

    function mentionsFrom(notes) {
        var found = {};
        (notes || []).forEach(function (n) {
            // El backend resuelve las menciones con el MISMO MentionService
            // que decide a quién notificar, así que estos son los agentes que
            // de verdad recibieron el aviso. El parseo en crudo queda como
            // respaldo: enseñaba el "@loquesea" literal aunque no existiera
            // ningún agente con ese nombre.
            if (n.mentions) {
                // Lista presente aunque venga vacía: el backend YA analizó
                // esta nota y no encontró a nadie. Volver al parseo en crudo
                // aquí convertía cualquier correo del texto
                // ("compras@construcinsa.mx") en una mención inventada.
                n.mentions.forEach(function (m) {
                    var prev = found[m.name];
                    // "mencionado hace 22 min": la mención no tiene fecha
                    // propia, la hereda de la nota donde se escribió; con
                    // varias, la más reciente.
                    found[m.name] = {
                        count: (prev ? prev.count : 0) + 1,
                        when: prev && prev.when ? prev.when : n.created_at_human,
                    };
                });
                return;
            }

            var m = String(n.body || '').match(/@[\wÁÉÍÓÚáéíóúÑñ.\-]+/g);
            if (m) m.forEach(function (x) {
                var prev = found[x];
                found[x] = { count: (prev ? prev.count : 0) + 1, when: prev && prev.when ? prev.when : n.created_at_human };
            });
        });
        return Object.keys(found).map(function (k) {
            return { name: k, count: found[k].count, when: found[k].when };
        });
    }

    function renderNotasPane($c, notes, t) {
        var menciones = mentionsFrom(notes);
        var list = (notes || []).map(function (n) {
            var colorDots = NOTE_COLORS.map(function (c) {
                // role/tabindex/aria-label + manejador de teclado propio —
                // antes era un <span> plano sin ninguno de los tres,
                // inalcanzable por teclado y sin nombre accesible (solo
                // color de fondo, indistinguible para un lector de
                // pantalla).
                return '<span class="tkt-note-color-dot' + (n.color === c ? ' on' : '') + '" data-note-color="' + n.id + '" data-color="' + c + '" role="button" tabindex="0" aria-label="' + NOTE_COLOR_LABELS[c] + '" style="background:var(--tkt-note-' + c + ',' + c + ')"></span>';
            }).join('');
            return '<div class="tkt-note-card' + (n.is_pinned ? ' pinned' : '') + '"><span class="tkt-person-avatar" style="width:22px;height:22px;font-size:9px">' + initials(n.author_name) + '</span>' +
                '<div class="tkt-fill">' +
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
                '<div class="tkt-relative">' +
                    '<textarea id="tkt-new-note" class="tkt-w-100" style="padding:9px 10px;border:1px solid var(--tkt-border);border-radius:7px;font-size:11.5px;min-height:66px" placeholder="Escribe una nota que solo verá el equipo… (usa @ para mencionar)"></textarea>' +
                    '<div class="tkt-drop" id="tkt-note-mention-drop" style="top:100%;left:0;right:0;max-height:160px;overflow:auto" hidden></div>' +
                '</div>' +
                // Barra del mockup: las tres acciones sobre el texto a la
                // izquierda y el contador a la derecha. "Mencionar" inserta
                // la @ y dispara el mismo autocompletado que ya escuchaba
                // por teclado, que hasta ahora no se anunciaba en ninguna
                // parte de la interfaz.
                '<div class="tkt-note-tools">' +
                    '<button type="button" class="tkt-comp-tool" id="tkt-note-macro" title="Insertar una macro"><i class="fa-solid fa-bolt"></i> Macro</button>' +
                    '<label class="tkt-comp-tool" title="Adjuntar un archivo al ticket"><i class="fa-solid fa-paperclip"></i> Adjuntar<input type="file" id="tkt-note-attach" multiple hidden></label>' +
                    '<button type="button" class="tkt-comp-tool" id="tkt-note-mention" title="Mencionar a un compañero"><i class="fa-solid fa-at"></i> Mencionar</button>' +
                    '<label class="tkt-note-pin-toggle"><input type="checkbox" id="tkt-new-note-pin"> Fijar</label>' +
                    '<span class="tkt-note-count mono" id="tkt-note-counter">0 / ' + NOTE_MAX_LENGTH + '</span>' +
                '</div>' +
                '<div class="tkt-note-actions">' +
                    '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-note-save"><i class="fa-regular fa-note-sticky"></i> Guardar nota interna</button>' +
                    '<button type="button" class="tkt-btn" id="tkt-note-discard">Descartar</button>' +
                '</div>' +
            '</div></div>' +
            '<div class="tkt-side-card"><div class="tkt-side-card-head">Notas del ticket<span class="tkt-spacer mono tkt-faint">' + (notes ? notes.length : 0) + '</span></div><div class="tkt-side-card-body">' +
                (list || '<div class="tkt-empty-box">Sin notas todavía.</div>') +
            '</div></div>' +
            // Card "Menciones" del mockup: a quién se ha llamado en las notas.
            // Solo aparece si hay alguna: una card vacía permanente sería ruido.
            (menciones.length
                ? '<div class="tkt-side-card"><div class="tkt-side-card-head">Menciones' +
                    '<span class="tkt-spacer mono tkt-faint">' + menciones.length + '</span></div>' +
                  '<div class="tkt-side-card-body"><div class="tkt-chiprow">' +
                    menciones.map(function (m) {
                        return '<span class="tkt-rchip">' + escapeHtml(m.name) +
                            (m.count > 1 ? ' ·' + m.count : '') +
                            (m.when ? '<span class="tkt-rchip-when">' + escapeHtml(m.when) + '</span>' : '') +
                        '</span>';
                    }).join('') +
                  '</div></div></div>'
                : '')
        );

        var $noteInput = $c.find('#tkt-new-note');
        var $counter = $c.find('#tkt-note-counter');

        $noteInput.attr('maxlength', NOTE_MAX_LENGTH).on('input', function () {
            var used = this.value.length;
            $counter.text(used + ' / ' + NOTE_MAX_LENGTH).toggleClass('near', used > NOTE_MAX_LENGTH * 0.9);
        });

        $c.find('#tkt-note-mention').on('click', function () {
            // Inserta la @ en la posición del cursor y deja el foco dentro,
            // para que el autocompletado ya enganchado reaccione igual que
            // si se hubiera tecleado.
            var el = $noteInput.get(0);
            if (!el) return;
            var pos = el.selectionStart != null ? el.selectionStart : el.value.length;
            el.value = el.value.slice(0, pos) + '@' + el.value.slice(pos);
            el.focus();
            el.setSelectionRange(pos + 1, pos + 1);
            $noteInput.trigger('input').trigger('keyup');
        });

        $c.find('#tkt-note-macro').on('click', function () { openComposerMacros($(this)); });

        $c.find('#tkt-note-attach').on('change', function () {
            uploadTicketAttachments(t, this.files);
            this.value = '';
        });

        $c.find('#tkt-note-discard').on('click', function () {
            if (!$noteInput.val()) return;
            openConfirmModal({
                icon: 'fa-regular fa-note-sticky',
                title: 'Descartar la nota',
                message: 'Se perderá lo que has escrito. No se puede deshacer.',
                confirmLabel: 'Descartar',
                danger: true,
                onConfirm: function () {
                    $noteInput.val('').trigger('input');
                    $c.find('#tkt-new-note-pin').prop('checked', false);
                },
            });
        });

        $c.find('#tkt-note-save').on('click', function () { createNote(t); });
        $c.find('[data-note-pin]').on('click', function () { toggleNotePin(t, $(this).data('note-pin')); });
        $c.find('[data-note-delete]').on('click', function () { deleteNote(t, $(this).data('note-delete')); });
        bindMentionAutocomplete($c.find('#tkt-new-note'), $c.find('#tkt-note-mention-drop'));
        $c.find('[data-note-color]').on('click', function () { setNoteColor(t, $(this).data('note-color'), $(this).data('color')); });
        $c.find('[data-note-color]').on('keydown', function (ev) {
            if (ev.key !== 'Enter' && ev.key !== ' ' && ev.key !== 'Spacebar') return;
            ev.preventDefault();
            setNoteColor(t, $(this).data('note-color'), $(this).data('color'));
        });
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
        // Modal 27: la sugerencia de clasificación se revisa en su propio
        // modal antes de aplicarse, no se aplica a ciegas desde el panel.
        $c.off('click.tagging').on('click.tagging', '#tkt-open-tagging', function () { openAiTaggingModal(t, aiSuggestion); });
        var chips = (t.tags || []).map(function (tag) {
            // data-tag-remove vive SOLO en el icono fa-xmark, no en el chip
            // completo — antes un click accidental en cualquier parte del
            // texto de la etiqueta la eliminaba al instante.
            return '<span class="tkt-tag">' + escapeHtml(tag) + ' <i class="fa-solid fa-xmark" data-tag-remove="' + escapeHtml(tag) + '" role="button" tabindex="0" aria-label="Eliminar etiqueta ' + escapeHtml(tag) + '"></i></span>';
        }).join('');

        // "+ añadir" del mockup: el campo sale al pulsarlo, en vez de tener
        // un input permanente ocupando sitio bajo unas etiquetas que la
        // mayoría del tiempo solo se leen.
        var html = '<div class="tkt-side-card"><div class="tkt-side-card-head">Etiquetas' +
                (t.tags && t.tags.length ? '<span class="tkt-spacer mono tkt-faint">' + t.tags.length + '</span>' : '') +
            '</div><div class="tkt-side-card-body">' +
                '<div class="tkt-chiprow">' + chips +
                    '<button type="button" class="tkt-tag-add-btn" id="tkt-tag-add-btn"><i class="fa-solid fa-plus"></i> añadir</button>' +
                '</div>' +
                '<input type="text" id="tkt-tag-add" class="tkt-w-100 tkt-tag-input" placeholder="Nueva etiqueta y Enter…" hidden>' +
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
            html += '<button type="button" class="tkt-btn tkt-w-100" id="tkt-open-tagging">Revisar clasificación sugerida</button>';
            html += '</div></div>';
        }

        html += '<a href="' + TKA.urls.automationsIndex + '" class="tkt-btn"><i class="fa-solid fa-gears"></i> Reglas de etiquetado automático</a>';

        $c.html(html);

        // Mismo patrón de confirmación que ya usa el borrado de notas
        // (openConfirmModal, aviso de que no se puede deshacer) — antes un
        // solo click eliminaba la etiqueta al instante, sin fricción.
        $c.find('[data-tag-remove]').on('click', function () {
            var tag = $(this).data('tag-remove');
            openConfirmModal({
                icon: 'fa-solid fa-tag',
                danger: true,
                title: 'Eliminar etiqueta',
                message: 'Se eliminará la etiqueta "' + tag + '" de este ticket. No se puede deshacer.',
                confirmLabel: 'Eliminar',
                onConfirm: function () { patchTags(t, null, tag); },
            });
        });
        $c.find('[data-tag-remove]').on('keydown', function (ev) {
            if (ev.key !== 'Enter' && ev.key !== ' ' && ev.key !== 'Spacebar') return;
            ev.preventDefault();
            $(this).trigger('click');
        });
        $c.find('#tkt-tag-add-btn').on('click', function () {
            $(this).hide();
            $c.find('#tkt-tag-add').prop('hidden', false).trigger('focus');
        });
        $c.find('#tkt-tag-add').on('keydown', function (ev) {
            if (ev.key !== 'Enter' || !this.value.trim()) return;
            patchTags(t, this.value.trim(), null);
        });
        $c.find('[data-ai-apply]').on('click', function () { applyAiSuggestion(t, $(this).data('ai-apply')); });
        $c.find('#tkt-open-tagging').on('click', function () { openAiTaggingModal(t, aiSuggestion); });
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
        // Mismo copy que la pestaña "Archivos" del centro (renderFilesPane)
        // para el mismo caso — antes decían cosas distintas ("Sin archivos
        // adjuntos." aquí vs. "Este ticket no tiene archivos adjuntos." allá).
        $c.html('<div class="tkt-side-card"><div class="tkt-side-card-head">Archivos del ticket<span class="tkt-spacer mono tkt-faint">' + (files ? files.length : 0) + '</span></div><div class="tkt-side-card-body">' + (list || '<div class="tkt-empty-box">Este ticket no tiene archivos adjuntos.</div>') + '</div></div>');
    }

    function renderHistorialPane($c, activity, related) {
        var all = activity || [];
        var total = (TKA.state.currentDetail && TKA.state.currentDetail.activity_total_count) || all.length;
        var histFilter = 'all';

        // Los tres filtros del mockup. "Envíos" son los eventos de correo,
        // "Cambios" el resto (estado, prioridad, asignación…).
        function histRows() {
            var list = all.filter(function (a) {
                if (histFilter === 'all') return true;
                var tipo = activityStepType(a.description);
                return histFilter === 'mail' ? tipo === 'sent' : tipo !== 'sent';
            }).slice(0, 8);

            if (!list.length) return '<div class="tkt-empty-box">Sin eventos en este filtro.</div>';

            return list.map(function (a, i) {
                return stepHtml({
                    type: activityStepType(a.description),
                    title: escapeHtml(a.description),
                    detail: a.causer ? escapeHtml(a.causer) : '',
                    time: a.created_at_human || '',
                    last: i === list.length - 1,
                });
            }).join('');
        }

        var recent = '<div class="tkt-seg-tabs" id="tkt-hist-tabs">' +
                '<button type="button" class="on" data-hfilter="all">Todo</button>' +
                '<button type="button" data-hfilter="mail">Envíos</button>' +
                '<button type="button" data-hfilter="change">Cambios</button>' +
                // "Auditoría" del mockup: sale de la pantalla a la bitácora
                // del módulo Activity ya acotada a ESTE ticket. No es un
                // cuarto filtro de la lista de arriba, por eso va suelto.
                (TKA.urls.activityAudit
                    ? '<a class="tkt-seg-out" href="' + TKA.urls.activityAudit +
                        '?subject_type=' + encodeURIComponent('Modules\\HelpdeskTickets\\Models\\Ticket') +
                        '&subject_id=' + encodeURIComponent(TKA.state.currentTicket ? TKA.state.currentTicket.id : '') +
                        '" target="_blank" rel="noopener"><i class="fa-solid fa-shield-halved"></i> Auditoría</a>'
                    : '') +
            '</div>' +
            '<div id="tkt-hist-rows">' + histRows() + '</div>' +
            // "Ver las 23 acciones del ticket →": lleva a la pestaña
            // Actividad del detalle, que ya lista el historial completo.
            (total > Math.min(all.length, 8)
                ? '<button type="button" class="tkt-link-btn tkt-w-100" data-goto-activity>Ver las ' + total + ' acciones del ticket →</button>'
                : '');
        var relatedHtml = (related || []).map(function (r) {
            return '<div class="tkt-line">' +
                '<a href="' + TKA.urls.index + '?ticket=' + r.id + '" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:9px;flex:1;min-width:0">' +
                    '<span class="mono tkt-meta-xs">' + escapeHtml(r.ticket_number) + '</span>' +
                    '<span class="tkt-trunc tkt-fill">' + escapeHtml(r.subject) + '</span>' +
                    (r.link_type ? chip(LINK_TYPE_LABELS[r.link_type] || r.link_type, 'tkt-chip-info') : '') +
                    chip(r.status_name || STATUS_LABEL_FALLBACK[r.status_slug] || '—', statusChipClass(r.status_slug)) +
                '</a>' +
                (r.url_unlink ? '<button type="button" class="tkt-btn-icon" data-unlink="' + r.id + '" data-unlink-url="' + r.url_unlink + '" title="Desvincular"><i class="fa-solid fa-link-slash"></i></button>' : '') +
            '</div>';
        }).join('');

        $c.html(
            '<div class="tkt-side-card"><div class="tkt-side-card-head">Historial reciente' +
                '<span class="tkt-spacer mono tkt-faint">' + Math.min(all.length, 8) + ' de ' + total + '</span></div>' +
                '<div class="tkt-side-card-body">' + (all.length ? recent : '<div class="tkt-empty-box">Sin actividad.</div>') + '</div></div>' +
            '<div class="tkt-side-card"><div class="tkt-side-card-head">Tickets relacionados</div><div class="tkt-side-card-body">' +
                (relatedHtml || '<div class="tkt-empty-box">No hay otros tickets de este cliente.</div>') +
                '<button type="button" class="tkt-btn" id="tkt-link-ticket"><i class="fa-solid fa-link"></i> Vincular ticket</button>' +
            '</div></div>'
        );

        $c.find('#tkt-link-ticket').on('click', function () { linkTicketPrompt(TKA.state.currentTicket); });
        $c.find('[data-goto-activity]').on('click', function () { $('[data-dtab="activity"]').trigger('click'); });
        $c.find('[data-hfilter]').on('click', function () {
            histFilter = $(this).data('hfilter');
            $c.find('[data-hfilter]').removeClass('on');
            $(this).addClass('on');
            $c.find('#tkt-hist-rows').html(histRows());
        });
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
            return '<label class="tkt-option' + (i === 0 ? ' on' : '') + ' tkt-pointer" data-link-type-option="' + lt.value + '" >' +
                '<input type="radio" name="tkt-link-type" value="' + lt.value + '"' + (i === 0 ? ' checked' : '') + ' class="tkt-m0">' +
                '<span><span class="tkt-option-title">' + escapeHtml(lt.title) + '</span><br><span class="tkt-option-sub">' + escapeHtml(lt.sub) + '</span></span>' +
            '</label>';
        }).join('');

        var $backdrop = openModal(modalShell({
            icon: 'fa-solid fa-link',
            kicker: t.ticket_number,
            title: 'Vincular ticket',
            width: 'sm',
            body: '' +
                '<div class="tkt-field"><label class="tkt-label">Ticket a vincular<span class="req">*</span><span class="hint">busca por número o asunto</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-link-target" placeholder="Nº de ticket, asunto o ID…">' +
                    '<div class="tkt-pick-list sm" id="tkt-link-results"></div></div>' +
                '<div style="display:flex;flex-direction:column;gap:6px">' + options + '</div>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-link-confirm">Vincular</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        bindTicketSearch($backdrop, { input: '#tkt-link-target', results: '#tkt-link-results', excludeId: t.id });

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
        var causas = TKA.state.closeRootCauses || [];
        var optionsHtml = reasons.map(function (r, i) {
            var sub = CLOSE_REASON_SUB[r.key];
            return '<label class="tkt-option' + (i === 0 ? ' on' : '') + '" data-reason-option="' + escapeHtml(r.key) + '">' +
                '<input type="radio" name="tkt-close-reason" value="' + escapeHtml(r.key) + '"' + (i === 0 ? ' checked' : '') + ' class="tkt-m0">' +
                '<span><span class="tkt-option-title">' + escapeHtml(r.label) + '</span>' +
                (sub ? '<br><span class="tkt-option-sub">' + escapeHtml(sub) + '</span>' : '') + '</span></label>';
        }).join('');

        var $modal = openModal(modalShell({
            icon: 'fa-solid fa-lock',
            kicker: 'Ticket · cierre',
            titleChip: t.ticket_number || String(t.id),
            title: 'Motivo de cierre',
            body:
                '<div class="tkt-field">' +
                    '<label class="tkt-label">Motivo (opcional)</label>' +
                    optionsHtml +
                '</div>' +
                '<div class="tkt-field" id="tkt-close-other-wrap" hidden>' +
                    '<label class="tkt-label">Detalle</label>' +
                    '<input type="text" class="tkt-input" id="tkt-close-other-input" maxlength="100" placeholder="Describe el motivo…">' +
                '</div>' +
                // Causa raíz: el motivo dice CÓMO acabó el ticket, esto dice
                // POR QUÉ existió. Es lo que se agrupa en los informes para
                // ver qué genera trabajo repetido.
                (causas.length
                    ? '<div class="tkt-field">' +
                        '<label class="tkt-label">Causa raíz</label>' +
                        '<select class="tkt-fselect tkt-w-100" id="tkt-close-root-cause">' +
                            '<option value="">Sin clasificar</option>' +
                            causas.map(function (c) {
                                return '<option value="' + escapeHtml(c.key) + '">' + escapeHtml(c.label) + '</option>';
                            }).join('') +
                        '</select></div>'
                    : '') +
                '<div class="tkt-field">' +
                    '<label class="tkt-label">Resumen para el informe</label>' +
                    '<textarea class="tkt-input" id="tkt-close-summary" rows="2" maxlength="2000" ' +
                        'placeholder="Qué pasó y cómo se resolvió (opcional)"></textarea>' +
                '</div>' +
                // Cerrar disparaba SIEMPRE la encuesta de satisfacción. En un
                // cierre por spam o duplicado, preguntar sobra.
                '<label class="tkt-check">' +
                    '<input type="checkbox" id="tkt-close-skip-survey"> No enviar la encuesta de satisfacción' +
                '</label>',
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
            var payload = {
                reason: reason,
                root_cause: $modal.find('#tkt-close-root-cause').val() || '',
                summary: $modal.find('#tkt-close-summary').val() || '',
                skip_survey: $modal.find('#tkt-close-skip-survey').is(':checked') ? 1 : 0,
            };
            closeModal();
            runLifecycleAction(t, 'close', payload);
        });
    }

    // ═══════════ Bulk actions ═══════════
    function renderBulkBar() {
        var ids = Object.keys(TKA.state.bulk).map(Number);
        $('#tkt-bulk-count').text(ids.length + (ids.length === 1 ? ' seleccionado' : ' seleccionados'));
        $('#tkt-bulk-bar').toggleClass('on', ids.length > 0);
        syncSelectAllCheckbox();
    }

    // Resincroniza #tkt-select-all con el estado real de selección — antes
    // solo se actualizaba al pulsar el propio maestro; desmarcar UNA fila
    // individual tras "Seleccionar todo" lo dejaba marcado como si las 11
    // siguieran seleccionadas. Único punto de entrada (llamado desde
    // renderBulkBar(), que ya se invoca tras cualquier mutación de
    // TKA.state.bulk: fila individual, maestro o "Quitar selección").
    function syncSelectAllCheckbox() {
        var $master = $('#tkt-select-all');
        if (!$master.length) return;
        var visible = visibleTickets();
        if (!visible.length) {
            $master.prop('checked', false);
            $master[0].indeterminate = false;
            return;
        }
        var selectedCount = visible.filter(function (t) { return TKA.state.bulk[t.id]; }).length;
        $master[0].checked = selectedCount === visible.length;
        $master[0].indeterminate = selectedCount > 0 && selectedCount < visible.length;
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
            kicker: 'Tickets · selección',
            titleChip: ids.length + (ids.length === 1 ? ' ticket' : ' tickets'),
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

    /**
     * "Vincular a un ticket" del mockup (modal 13, ve-mail-bulk): mueve el
     * hilo COMPLETO de cada ticket seleccionado a uno elegido, igual que
     * mergeTicketPrompt() pero para varios orígenes a la vez. Reusa
     * bindTicketSearch(), el mismo buscador con autocompletado.
     */
    function openBulkLinkToTicketModal() {
        var ids = Object.keys(TKA.state.bulk).map(Number);
        if (!ids.length) return;

        var $modal = openModal(modalShell({
            icon: 'fa-solid fa-link',
            kicker: ids.length + (ids.length === 1 ? ' ticket seleccionado' : ' tickets seleccionados'),
            title: 'Vincular a un ticket',
            width: 'sm',
            body: '<div class="tkt-field"><label class="tkt-label">Ticket destino<span class="req">*</span><span class="hint">busca por número o asunto</span></label>' +
                    '<input type="text" class="tkt-input" id="tkt-bulk-link-target" placeholder="Nº de ticket, asunto o ID…">' +
                    '<div class="tkt-pick-list sm" id="tkt-bulk-link-results"></div></div>' +
                '<div class="tkt-note danger">Se moverá el hilo completo (mensajes, correos, adjuntos, notas, comentarios, tiempos, seguidores y enlaces) de cada ticket seleccionado al destino. Los orígenes se cierran y se borran. No se puede deshacer.</div>' +
                '<label class="tkt-check"><input type="checkbox" id="tkt-bulk-link-ack"> Entiendo que esta acción no se puede deshacer</label>',
            foot: '<button type="button" class="tkt-btn tkt-btn-primary" id="tkt-bulk-link-confirm" disabled>Vincular</button>' +
                  '<button type="button" class="tkt-btn" data-modal-close>Cancelar</button>',
        }));

        var targetId = null;
        bindTicketSearch($modal, {
            input: '#tkt-bulk-link-target',
            results: '#tkt-bulk-link-results',
            onPick: function (id) { targetId = id; },
        });

        $modal.on('change', '#tkt-bulk-link-ack', function () {
            $('#tkt-bulk-link-confirm').prop('disabled', !this.checked);
        });

        $modal.on('click', '#tkt-bulk-link-confirm', function () {
            if (!targetId) {
                if (window.toastr) toastr.error('Elige un ticket destino de la lista'); else window.alert('Elige un ticket destino de la lista');
                return;
            }
            closeModal();
            runBulkAction('link_to_ticket', { merge_into_id: targetId });
        });
    }

    // Aplica el modo Lista/Kanban al DOM (botón activo, columnas visibles,
    // render del tablero) sin tocar la URL — usado tanto por bindEvents()
    // (clic del usuario, que sí persiste vía history.replaceState) como por
    // bootstrap() (aplicar el modo inicial ya reflejado en ?view=/
    // data-initial-view, donde reescribir la URL sería un no-op).
    function applyViewMode(mode) {
        $('#tkt-mode-switch [data-mode]').removeClass('on');
        $('#tkt-mode-switch [data-mode="' + mode + '"]').addClass('on');
        $('#tkt-split-wrap').toggle(mode === 'list');
        $('#tkt-kanban').toggleClass('on', mode === 'kanban');
        if (mode === 'kanban') renderKanban();
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
            applyViewMode($(this).data('mode'));
            // Persiste el modo en la URL (?view=) — mismo patrón de
            // deep-link que ya usa selectTicket() para ?ticket=; el blade ya
            // leía request('view','list') en data-initial-view (bootstrap()
            // lo consume abajo), solo faltaba que el JS lo escribiera de
            // vuelta al cambiar de modo. Antes recargar la página siempre
            // volvía a Lista aunque se hubiera cambiado a Kanban.
            if (window.history && window.history.replaceState) {
                var params = new URLSearchParams(window.location.search);
                var mode = $(this).data('mode');
                if (mode === 'kanban') params.set('view', mode); else params.delete('view');
                var qs = params.toString();
                window.history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
            }
        });

        // "Plantillas" de la barra superior: en el mockup abre el modal 44
        // (crear desde plantilla), no navega a la pantalla de gestión — esa
        // sigue accesible desde el propio modal.
        $('#tkt-templates-open').on('click', function (e) { e.preventDefault(); openTicketTemplatesModal(); });

        $('#tkt-sync').on('click', syncCurrentCustomer);
        $('#tkt-export-open').on('click', openExportModal);

        // Cabecera de la lista: el orden se elige en un <select> (mockup) en
        // vez de alternar un único criterio con un enlace. El servidor ya
        // pagina y ordena, así que se recarga con ?sort= en vez de reordenar
        // en cliente, que solo afectaría a la página visible.
        $('#tkt-sort').on('change', function () {
            var base = $(this).data('base-url') || '';
            window.location = base + (base.indexOf('?') === -1 ? '?' : '&') + 'sort=' + encodeURIComponent(this.value);
        });
        $('#tkt-list-refresh').on('click', function () { window.location.reload(); });
        $('#tkt-list-export').on('click', openExportModal);
        $('#tkt-list-trash').on('click', function () {
            var ids = Object.keys(TKA.state.bulk);
            if (!ids.length) {
                if (window.toastr) toastr.info('Selecciona antes los tickets que quieres enviar a la papelera');
                return;
            }
            runBulkAction('delete');
        });
        bindStaticModal('#tkt-filters-modal-open', '#tkt-filters-modal-backdrop', '#tkt-filters-modal-close');

        // "Guardar vista" del modal de filtros: guarda lo escrito en el
        // formulario, no lo que hay en la URL (todavía sin aplicar).
        $('#htk-f-save-view').on('click', function () {
            var values = {};
            $('#htk-filters-form').serializeArray().forEach(function (f) {
                if (f.value !== '') values[f.name] = f.value;
            });
            $('#tkt-filters-modal-backdrop').removeClass('on');
            saveCurrentView(values);
        });

        // Los chips de filtro mandan el formulario entero, así que un filtro
        // sin elegir viaja como "source=&tag=&category=…" y deja la URL llena
        // de parámetros vacíos. Se quitan justo antes de enviar: el resultado
        // es el mismo y el enlace queda compartible.
        $('#tkt-filter-form').on('submit', function () {
            $(this).find('select, input').each(function () {
                if (!this.value) this.disabled = true;
            });
        });

        // Chip de rango de fechas: en el mockup el periodo se resume en un
        // único botón, así que los dos <input type="date"> reales viven en un
        // popover que se abre al pulsarlo y se cierra al pinchar fuera.
        $('#tkt-daterange-open').on('click', function (e) {
            e.stopPropagation();
            var pop = document.getElementById('tkt-daterange-pop');
            if (!pop) return;
            pop.hidden = !pop.hidden;
            this.setAttribute('aria-expanded', pop.hidden ? 'false' : 'true');
        });
        $(document).on('click', function (e) {
            var pop = document.getElementById('tkt-daterange-pop');
            if (!pop || pop.hidden) return;
            if (pop.contains(e.target)) return;
            pop.hidden = true;
            $('#tkt-daterange-open').attr('aria-expanded', 'false');
        });

        $('#tkt-save-view').on('click', saveCurrentView);

        // Riel de operación. Estos siete modales se referenciaban por su
        // NOMBRE y se resolvían con eval() dentro del closure, porque cuando
        // se escribió el riel varias de las funciones todavía no existían y
        // un ReferenceError en la primera cortaba los 120 renglones
        // siguientes del arranque ("seleccionar todo", acciones masivas,
        // atajos). Ya están todas escritas, así que se enganchan
        // directamente: sin eval (que además cae con una CSP estricta) y con
        // el fallo visible en el sitio si alguna se renombra.
        [
            ['#tkt-pill-queue', openQueueModal],
            ['#tkt-pill-mailboxes', openMailboxesModal],
            ['#tkt-pill-escalation', openEscalationModal],
            ['#tkt-pill-recurring', openRecurringModal],
            ['#tkt-pill-workload', openWorkloadModal],
            // En el mockup "Entregabilidad" es data-open="reputation": un
            // único modal con la autenticación del dominio Y las tasas.
            ['#tkt-pill-deliverability', openReputationModal],
            ['#tkt-pill-notices', openNotificationsModal],
        ].forEach(function (pair) {
            $(pair[0]).on('click', pair[1]);
        });

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

        // "Exportar" de la barra de selección: el modal ya preselecciona el
        // alcance "Selección" cuando hay tickets marcados.
        $('#tkt-bulk-export').on('click', openExportModal);

        var BULK_DIRECT_LABELS = {
            resolve: { title: 'Marcar como resuelto', message: '¿Marcar como resuelto?', confirmLabel: 'Resolver', danger: false },
            close: { title: 'Cerrar tickets', message: '¿Cerrar los tickets seleccionados?', confirmLabel: 'Cerrar', danger: false },
            delete: { title: 'Eliminar tickets', message: 'Esta acción no se puede deshacer.', confirmLabel: 'Eliminar', danger: true },
            // "Reintentar envío (solo fallidos)" del mockup: no hace falta
            // valor adicional (a diferencia de assign/add_tag/change_status),
            // así que entra por el mismo camino directo que resolver/cerrar,
            // no por BULK_EXTRA_CONFIG. Los tickets sin correo saliente
            // fallido simplemente no cuentan (ver BulkTicketsController).
            retry_failed_mail: { title: 'Reintentar envío', message: 'Solo se reintentan los correos de salida marcados como fallidos.', confirmLabel: 'Reintentar', danger: false },
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
        $('#tkt-bulk-link-ticket').on('click', openBulkLinkToTicketModal);

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
                openNewTicketModal();
            } else if (ev.key === ' ') {
                // Vista previa rápida (modal 12) sobre la fila seleccionada.
                // Solo con la lista enfocada: dentro de un modal el espacio
                // tiene que seguir activando el botón que tenga el foco.
                if ($('#tkt-modal-backdrop').length) return;
                var current = TKA.state.tickets.find(function (x) { return x.id === TKA.state.selected; });
                if (!current) return;
                ev.preventDefault();
                openQuickPreviewModal(current);
            }
        });
    }

    function moveSelection(delta) {
        var rows = visibleTickets();
        if (!rows.length) return;
        var idx = rows.findIndex(function (t) { return t.id === TKA.state.selected; });
        var next = rows[Math.min(Math.max(idx + delta, 0), rows.length - 1)] || rows[0];
        // Si la vista previa rápida está abierta, J/K la repintan con el
        // ticket nuevo en vez de cerrarla — es lo que promete su propio copy.
        var previewOpen = $('#tkt-modal-backdrop #tkt-qp-open').length > 0;
        selectTicket(next);
        var $row = $('.tkt-ticket-row[data-id="' + next.id + '"]');
        if ($row.length) $row[0].scrollIntoView({ block: 'nearest' });
        if (previewOpen) {
            closeModal();
            openQuickPreviewModal(next);
        }
    }

    $(document).ready(bootstrap);
})(window.jQuery);
