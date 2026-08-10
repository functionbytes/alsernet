/**
 * Helpdesk · Emails enviados — bandeja global (todos los tickets).
 *
 * Patrón: hidratación SSR→JS igual que tickets.js (ver #htk-data / #eml-data),
 * pero el listado se re-consulta al servidor (AJAX a la misma ruta index con
 * Accept: application/json) en vez de filtrar en cliente, porque aquí la
 * paginación es real y server-side (puede haber miles de filas).
 *
 * Modal de redactar/responder/reenviar: mismo componente bv-modal que
 * tickets.js (data-bv-modal-name + data-htk-close), con un mini open/close
 * propio aquí porque esta pantalla no comparte el namespace HTK de tickets.js.
 */
(function () {
    'use strict';

    var EML = { state: {}, urls: {} };

    function escapeHtml(s) {
        return $('<div>').text(s === null || s === undefined ? '' : s).html();
    }

    function csrfHeaders() {
        return { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') };
    }

    // ═══════════ Bootstrap ═══════════
    function bootstrap() {
        var $data = $('#eml-data');
        if ($data.length === 0) { return; }

        EML.state = {
            mails: $data.data('mails') || [],
            stats: $data.data('stats') || {},
            categories: $data.data('categories') || [],
            agents: $data.data('agents') || [],
            view: $data.data('initialView') || 'outbound',
            mode: 'list',
            search: '',
            category: '',
            agent: '',
            origin: '',
            tag: '',
            from: '',
            to: '',
            selected: null,
            selectedTicketId: null,
            bulk: new Set(),
            userId: $data.data('userId'),
            userName: $data.data('userName'),
            presenceChannel: null,
            presenceTicketId: null,
        };

        EML.urls = {
            index: $data.data('indexUrl'),
            store: $data.data('storeUrl'),
            bulk: $data.data('bulkUrl'),
            export: $data.data('exportUrl'),
            templates: $data.data('templatesUrl'),
            views: $data.data('viewsUrl'),
            viewsStore: $data.data('viewsStoreUrl'),
            typingTemplate: $data.data('typingUrlTemplate'),
        };

        populateComposeCategories();
        bindEvents();
        renderTabs();
        renderTagFilterOptions();
        renderList();
        renderBulkBar();
        loadSavedViews();
        $('#eml-tab-scheduled-count').text(EML.state.stats.scheduled || '');
        $('#eml-tab-internal-count').text(EML.state.stats.internal || '');
    }

    // ═══════════ Vistas guardadas (chips de la barra de KPIs) ═══════════
    function loadSavedViews() {
        if (!EML.urls.views) { return; }

        $.getJSON(EML.urls.views).done(function (resp) {
            EML.state.savedViews = resp.views || [];
            renderSavedViews();
        });
    }

    function renderSavedViews() {
        var $container = $('#eml-saved-views');
        $container.find('.eml-saved-view-chip').remove();

        (EML.state.savedViews || []).forEach(function (v) {
            var $chip = $('<span class="eml-chip-filter eml-saved-view-chip" data-eml-saved="' + v.id + '"></span>')
                .append($('<span>').text(v.name))
                .append($('<button type="button" data-eml-saved-remove="' + v.id + '"><i class="fa-solid fa-xmark"></i></button>'));
            $chip.insertBefore('#eml-saved-add');
        });
    }

    function applyFilters(filters) {
        EML.state.view = filters.view || 'outbound';
        EML.state.search = filters.search || '';
        EML.state.category = filters.category || '';
        EML.state.agent = filters.agent || '';
        EML.state.origin = filters.origin || '';
        EML.state.tag = filters.tag || '';
        EML.state.from = filters.from || '';
        EML.state.to = filters.to || '';

        $('#eml-search').val(EML.state.search);
        $('#eml-filter-category').val(EML.state.category);
        $('#eml-filter-agent').val(EML.state.agent);
        $('#eml-filter-origin').val(EML.state.origin);
        $('#eml-filter-tag').val(EML.state.tag);
        $('#eml-filter-from').val(EML.state.from);
        $('#eml-filter-to').val(EML.state.to);

        renderTabs();
        refetch();
    }

    function currentFilters() {
        return {
            view: EML.state.view,
            search: EML.state.search,
            category: EML.state.category,
            agent: EML.state.agent,
            origin: EML.state.origin,
            tag: EML.state.tag,
            from: EML.state.from,
            to: EML.state.to,
        };
    }

    // Las etiquetas del filtro se derivan de lo que ya está cargado (no hay
    // catálogo de tags en servidor para TicketMail, es un array JSON libre)
    // — evita mostrar un filtro con opciones que nunca tienen resultados.
    function renderTagFilterOptions() {
        var $sel = $('#eml-filter-tag');
        var current = EML.state.tag;
        var tags = new Set();
        EML.state.mails.forEach(function (m) { (m.tags || []).forEach(function (t) { tags.add(t); }); });

        $sel.find('option:not(:first)').remove();
        Array.from(tags).sort().forEach(function (t) {
            $sel.append($('<option>').val(t).text(t));
        });
        $sel.val(current);
    }

    function populateComposeCategories() {
        var $cat = $('#eml-compose-category');
        EML.state.categories.forEach(function (c) {
            $cat.append($('<option>').val(c.id).text(c.name));
        });
    }

    // ═══════════ Fetch (filtros/tabs/búsqueda → servidor) ═══════════
    function refetch() {
        $.ajax({
            url: EML.urls.index,
            method: 'GET',
            dataType: 'json',
            data: {
                view: EML.state.view,
                search: EML.state.search || undefined,
                category: EML.state.category || undefined,
                agent: EML.state.agent || undefined,
                origin: EML.state.origin || undefined,
                tag: EML.state.tag || undefined,
                from: EML.state.from || undefined,
                to: EML.state.to || undefined,
            },
        }).done(function (resp) {
            EML.state.mails = resp.data || [];
            EML.state.bulk.clear();
            renderTagFilterOptions();
            renderList();
            renderBulkBar();
            if (resp.stats) { renderKpis(resp.stats); }
        }).fail(function () {
            if (window.toastr) { toastr.error('No se pudo cargar el listado.'); }
        });
    }

    // ═══════════ Render: tabs ═══════════
    function renderTabs() {
        $('.eml-app-tab').removeClass('on').each(function () {
            if ($(this).data('emlView') === EML.state.view) { $(this).addClass('on'); }
        });
    }

    // Los 4 números de la barra de KPIs (enviados/rebotados/tasa/programados)
    // se hidratan por SSR en el primer render, pero quedaban congelados tras
    // enviar/reenviar/acciones masivas porque refetch() nunca los tocaba
    // (bug real encontrado en QA manual). El contador junto al tab
    // "Programados" comparte la misma fuente.
    function renderKpis(stats) {
        EML.state.stats = stats;
        $('#eml-kpi-total').text(stats.total);
        $('#eml-kpi-bounced').text(stats.bounced);
        $('#eml-kpi-bounce-rate').text(stats.bounce_rate + '%');
        $('#eml-kpi-opened-rate').text((stats.opened_rate ?? 0) + '%');
        $('#eml-kpi-latency').text(stats.avg_latency !== null && stats.avg_latency !== undefined ? stats.avg_latency + 's' : '—');
        $('#eml-kpi-scheduled').text(stats.scheduled);
        $('#eml-tab-scheduled-count').text(stats.scheduled || '');
        $('#eml-tab-internal-count').text(stats.internal || '');
        $('#eml-queue-hint').text('cola: emails · ' + (stats.queue_waiting ?? 0) + ' en espera');
    }

    // ═══════════ Render: lista ═══════════
    function statusLabel(m) { return m.status_label || m.status; }

    // status_color viene del backend como palabra bootstrap-ish (accessor
    // TicketMail::getStatusColorAttribute) — se traduce a clase CSS aquí, en
    // un único sitio, para no repetir el mismo diccionario en PHP y en JS.
    function pillClass(colorWord) {
        return ({
            success: 'eml-pill-ok',
            warning: 'eml-pill-warn',
            danger: 'eml-pill-danger',
            info: 'eml-pill-info',
        })[colorWord] || 'eml-pill-muted';
    }

    var ORIGIN_LABELS = {
        presta: 'PrestaShop', prestashop: 'PrestaShop',
        email: 'Email', widget: 'Widget',
        whatsapp: 'WhatsApp', wa: 'WhatsApp',
        facebook: 'Facebook', fb: 'Facebook',
        instagram: 'Instagram', ig: 'Instagram',
        portal: 'Portal', web: 'Web',
    };

    function originLabel(slug) {
        if (!slug) { return '—'; }
        return ORIGIN_LABELS[slug] || (slug.charAt(0).toUpperCase() + slug.slice(1));
    }

    function renderMailRow(m) {
        var checked = EML.state.bulk.has(m.id) ? 'checked' : '';
        var onClass = EML.state.selected === m.id ? ' on' : '';

        return '<div class="eml-mail-row' + onClass + '" data-id="' + m.id + '">' +
            '<input type="checkbox" class="eml-row-check" data-id="' + m.id + '" ' + checked + '>' +
            '<div class="eml-avatar">' + escapeHtml(m.initials) + '</div>' +
            '<div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:3px">' +
                '<span class="eml-subject">' + escapeHtml(m.subject || '(sin asunto)') + '</span>' +
                '<div class="eml-snippet">Para: ' + escapeHtml(m.to) + (m.snippet ? ' · ' + escapeHtml(m.snippet) : '') + '</div>' +
                '<div style="display:flex;align-items:center;gap:6px;margin-top:2px;flex-wrap:wrap">' +
                    '<span class="eml-pill ' + pillClass(m.status_color) + '">' + escapeHtml(statusLabel(m)) + '</span>' +
                    (m.ticket_number ? '<span class="eml-tag-mono">' + escapeHtml(m.ticket_number) + '</span>' : '') +
                    '<span style="font-size:10px;color:var(--eml-text-muted)">' + escapeHtml(originLabel(m.origin)) + '</span>' +
                    (m.has_attachments ? '<i class="fa-solid fa-paperclip" style="font-size:10px;color:var(--eml-text-muted)"></i>' : '') +
                    '<span style="margin-left:auto;font-family:var(--eml-font-mono);font-size:10px;color:var(--eml-text-muted)">' + escapeHtml(m.time_short || '') + '</span>' +
                '</div>' +
            '</div>' +
        '</div>';
    }

    function renderList() {
        var $list = $('#eml-list');
        var rows = EML.state.mails;

        $('#eml-count').text(rows.length + ' resultado' + (rows.length !== 1 ? 's' : ''));
        $list.toggleClass('mode-compact', EML.state.mode === 'compact');

        applyModeVisibility();
        if (EML.state.mode === 'kanban') {
            renderKanban();
            return;
        }

        if (!rows.length) {
            $list.html('<div class="eml-empty-state"><div style="font-size:13px;color:var(--eml-text-soft)">Sin resultados en este filtro.</div></div>');
            return;
        }

        if (EML.state.mode === 'thread') {
            $list.html(renderThreadGroups(rows));
            return;
        }

        $list.html(rows.map(renderMailRow).join(''));
    }

    // Modo Hilos: una fila por ticket con el mensaje más reciente + contador.
    function renderThreadGroups(rows) {
        var byTicket = new Map();
        rows.forEach(function (m) {
            var key = m.ticket_id || ('no-ticket-' + m.id);
            if (!byTicket.has(key)) { byTicket.set(key, []); }
            byTicket.get(key).push(m);
        });

        var groups = Array.from(byTicket.values()).map(function (list) {
            list.sort(function (a, b) { return (b.created_at || '').localeCompare(a.created_at || ''); });
            return list;
        });
        groups.sort(function (a, b) { return (b[0].created_at || '').localeCompare(a[0].created_at || ''); });

        return groups.map(function (list) {
            var head = list[0];
            var row = renderMailRow(head);
            if (list.length > 1) {
                row = row.replace('</span></div>', '</span><span class="eml-thread-count">' + list.length + ' en el hilo</span></div>');
            }
            return '<div class="eml-thread-group">' + row + '</div>';
        }).join('');
    }

    // Modo Kanban: agrupa por estado las filas ya cargadas (sin drag&drop en
    // esta fase — las columnas reflejan la vista actual, no todo el sistema).
    var KANBAN_COLUMNS = [
        { status: 'scheduled', label: 'Programados', dot: '#f59e0b' },
        { status: 'pending', label: 'En cola', dot: '#a1a1aa' },
        { status: 'sent', label: 'Enviados', dot: '#2563eb' },
        { status: 'delivered', label: 'Entregados', dot: '#16a34a' },
        { status: 'bounced', label: 'Rebotados', dot: '#dc2626' },
        { status: 'failed', label: 'Fallidos', dot: '#dc2626' },
    ];

    function renderKanban() {
        var rows = EML.state.mails;
        var $kanban = $('#eml-kanban');

        $kanban.html(KANBAN_COLUMNS.map(function (col) {
            var items = rows.filter(function (m) { return m.status === col.status; });
            if (!items.length) { return ''; }

            return '<div class="eml-kcol">' +
                '<div class="eml-kcol-head"><span class="eml-kcol-dot" style="background:' + col.dot + '"></span><span>' + escapeHtml(col.label) + '</span><span class="eml-mono" style="color:var(--eml-text-muted)">' + items.length + '</span></div>' +
                '<div class="eml-kcol-drop">' + items.map(function (m) {
                    return '<div class="eml-kcard" data-id="' + m.id + '">' +
                        '<div style="font-size:12px;font-weight:600;line-height:1.4">' + escapeHtml(m.subject || '(sin asunto)') + '</div>' +
                        '<div style="font-size:11px;color:var(--eml-text-soft);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + escapeHtml(m.to) + '</div>' +
                        '<div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">' +
                            (m.ticket_number ? '<span class="eml-tag-mono">' + escapeHtml(m.ticket_number) + '</span>' : '') +
                            '<span style="font-size:10px;color:var(--eml-text-muted)">' + escapeHtml(originLabel(m.origin)) + '</span>' +
                        '</div>' +
                    '</div>';
                }).join('') + '</div>' +
            '</div>';
        }).join(''));
    }

    function applyModeVisibility() {
        var isKanban = EML.state.mode === 'kanban';
        $('.eml-split-scroll').toggle(!isKanban);
        $('#eml-kanban').toggleClass('on', isKanban);
    }

    // ═══════════ Bulk ═══════════
    function renderBulkBar() {
        var n = EML.state.bulk.size;
        $('#eml-bulk-bar').toggleClass('on', n > 0);
        $('#eml-bulk-count').text(n + ' seleccionado' + (n !== 1 ? 's' : ''));
        $('#eml-select-all').prop('checked', n > 0 && n === EML.state.mails.length)
            .prop('indeterminate', n > 0 && n < EML.state.mails.length);
    }

    function bulkAction(action) {
        if (EML.state.bulk.size === 0) { return; }

        var ids = Array.from(EML.state.bulk);

        $.ajax({
            url: EML.urls.bulk,
            method: 'POST',
            dataType: 'json',
            headers: csrfHeaders(),
            data: { mail_ids: ids, action: action },
        }).done(function (resp) {
            if (window.toastr) { toastr.success(resp.message || 'Acción ejecutada'); }
            EML.state.bulk.clear();
            refetch();
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo ejecutar la acción';
            if (window.toastr) { toastr.error(msg); }
        });
    }

    function exportSelection() {
        var ids = Array.from(EML.state.bulk);
        var url = EML.urls.export;
        if (ids.length) {
            url += (url.indexOf('?') === -1 ? '?' : '&') + ids.map(function (id) { return 'ids[]=' + id; }).join('&');
        }
        window.location.href = url;
    }

    // ═══════════ Detalle ═══════════
    function openDetail(id) {
        var mail = EML.state.mails.find(function (m) { return m.id === id; });
        if (!mail) { return; }

        EML.state.selected = id;
        EML.state.selectedTicketId = mail.ticket_id;
        renderList();

        $('#eml-detail-empty').hide();
        $('#eml-detail').show().html('<div class="eml-empty-state"><div style="font-size:12px;color:var(--eml-text-muted)">Cargando…</div></div>');

        $.getJSON(mail.url_data).done(function (resp) {
            renderDetail(resp.data);
            fetchAiSummary(mail);
            joinTicketPresence(mail.ticket_id);
        }).fail(function () {
            if (window.toastr) { toastr.error('No se pudo cargar el email.'); }
        });
    }

    // ═══════════ Colaboración en vivo ═══════════
    // Reusa el canal de presencia y los eventos TicketTyping/TicketViewing
    // que ya existen para la ficha de ticket (routes/channels.php +
    // TicketMessagingController::typing()) — no se inventa infraestructura
    // nueva, solo se conecta esta pantalla al mismo canal 'ticket.{id}'.
    function joinTicketPresence(ticketId) {
        if (typeof window.Echo === 'undefined' || !ticketId) { return; }
        if (EML.state.presenceTicketId === ticketId) { return; }

        leaveTicketPresence();
        EML.state.presenceTicketId = ticketId;

        try {
            EML.state.presenceChannel = window.Echo.join('ticket.' + ticketId)
                .here(function (users) { renderCollisionBanner(users); })
                .joining(function () { renderCollisionBanner(); })
                .leaving(function () { renderCollisionBanner(); })
                .listen('.typing', function (e) {
                    if (e.userId === EML.state.userId) { return; }
                    var $ind = $('#eml-typing-indicator');
                    if (e.isTyping) {
                        $ind.addClass('on').text(e.userName + ' está redactando una respuesta a este ticket…');
                    } else {
                        $ind.removeClass('on');
                    }
                });
        } catch (e) {
            // Sin Echo/Reverb levantado en este entorno: la pantalla sigue
            // funcionando igual, solo sin el aviso de colaboración en vivo.
            EML.state.presenceChannel = null;
        }
    }

    function leaveTicketPresence() {
        if (EML.state.presenceTicketId && typeof window.Echo !== 'undefined') {
            try { window.Echo.leave('ticket.' + EML.state.presenceTicketId); } catch (e) { /* ignore */ }
        }
        EML.state.presenceChannel = null;
        EML.state.presenceTicketId = null;
    }

    function renderCollisionBanner(users) {
        if (users) { EML.state.presenceUsers = users; }
        var others = (EML.state.presenceUsers || []).filter(function (u) { return u.id !== EML.state.userId; });

        if (!others.length) {
            $('#eml-collision-banner').removeClass('on');
            return;
        }

        var names = others.map(function (u) { return u.name; }).join(', ');
        var verb = others.length > 1 ? 'están viendo' : 'está viendo';
        $('#eml-collision-text').html('<strong>' + escapeHtml(names) + '</strong> ' + verb + ' este ticket.');
        $('#eml-collision-banner').addClass('on');
    }

    function emitTyping(isTyping) {
        var ticketId = EML.state.selectedTicketId;
        if (!ticketId || !EML.urls.typingTemplate) { return; }

        $.post(EML.urls.typingTemplate.replace('__TICKET__', ticketId), {
            _token: $('meta[name="csrf-token"]').attr('content'),
            is_typing: isTyping ? 1 : 0,
        });
    }

    // Bajo demanda y silencioso: si no hay LLM configurado el endpoint
    // devuelve summary=null y el banner simplemente no aparece.
    function fetchAiSummary(mail) {
        if (!mail.url_summary) { return; }

        $.getJSON(mail.url_summary).done(function (resp) {
            if (!resp.summary || EML.state.selected !== mail.id) { return; }

            $('#eml-ai-summary').show().html(
                '<i class="fa-solid fa-list-ul"></i>' +
                '<div class="eml-ai-summary-text"><strong>Resumen IA · </strong>' + escapeHtml(resp.summary) + '</div>'
            );
        });
    }

    function renderDetail(d) {
        EML.state.lastDetail = d;
        var html = '' +
            '<div class="eml-detail-head">' +
                '<div style="display:flex;align-items:flex-start;gap:12px">' +
                    '<div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:5px">' +
                        '<div style="font-size:9.5px;font-weight:700;color:var(--eml-text-muted);text-transform:uppercase;letter-spacing:.06em">Emails · Enviados</div>' +
                        '<div style="font-size:16px;font-weight:700;letter-spacing:-.02em">' + escapeHtml(d.subject || '(sin asunto)') + '</div>' +
                        '<div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap">' +
                            '<span class="eml-pill ' + pillClass(d.status_color) + '">' + escapeHtml(statusLabel(d)) + '</span>' +
                            (d.ticket_number ? '<span class="eml-tag-mono">' + escapeHtml(d.ticket_number) + '</span>' : '') +
                            '<span style="font-size:11px;color:var(--eml-text-muted)">' + escapeHtml(d.created_at_human || '') + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div style="display:flex;gap:7px">' +
                        '<button type="button" class="eml-btn eml-btn-primary" style="width:auto" id="eml-detail-reply"><i class="fas fa-reply"></i> Responder</button>' +
                        '<button type="button" class="eml-btn eml-btn-outline" style="width:auto" id="eml-detail-resend" data-url="' + escapeHtml(d.url_resend || '') + '"><i class="fas fa-rotate-right"></i> Reenviar</button>' +
                    '</div>' +
                '</div>' +
                '<div class="eml-detail-tabs">' +
                    '<div class="eml-detail-tab on" data-eml-dtab="msg">Mensaje</div>' +
                    '<div class="eml-detail-tab" data-eml-dtab="thread">Hilo <span class="eml-mono">' + (d.thread ? d.thread.length : 0) + '</span></div>' +
                    '<div class="eml-detail-tab" data-eml-dtab="trace">Trazabilidad <span class="eml-mono">' + (d.trace ? d.trace.length : 0) + '</span></div>' +
                    '<div class="eml-detail-tab" data-eml-dtab="act">Actividad <span class="eml-mono">' + (d.activity ? d.activity.length : 0) + '</span></div>' +
                    '<div class="eml-detail-tab" data-eml-dtab="rel">Relacionados <span class="eml-mono">' + (d.related ? d.related.length : 0) + '</span></div>' +
                    '<div class="eml-detail-tab" data-eml-dtab="files">Archivos <span class="eml-mono">' + (d.attachments ? d.attachments.length : 0) + '</span></div>' +
                '</div>' +
            '</div>' +
            '<div class="eml-collision-banner" id="eml-collision-banner">' +
                '<i class="fa-solid fa-users"></i>' +
                '<div style="flex:1" id="eml-collision-text"></div>' +
            '</div>' +
            '<div class="eml-typing-indicator" id="eml-typing-indicator"></div>' +
            '<div class="eml-ai-summary" id="eml-ai-summary" style="display:none"></div>' +
            '<div class="eml-detail-body" data-eml-dpane="msg">' + renderMessagePane(d) + '</div>' +
            '<div class="eml-detail-body" data-eml-dpane="thread" style="display:none">' + renderThreadPane(d) + '</div>' +
            '<div class="eml-detail-body" data-eml-dpane="trace" style="display:none">' + renderTracePane(d) + '</div>' +
            '<div class="eml-detail-body" data-eml-dpane="act" style="display:none">' + renderActivityPane(d) + '</div>' +
            '<div class="eml-detail-body" data-eml-dpane="rel" style="display:none">' + renderRelatedPane(d) + '</div>' +
            '<div class="eml-detail-body" data-eml-dpane="files" style="display:none">' + renderFilesPane(d) + '</div>';

        $('#eml-detail').html(html);
        renderSidePanel(d);
    }

    function renderMessagePane(d) {
        var textBody = d.body_text || (d.body_html ? stripTags(d.body_html) : '');
        // "Fuente" muestra las cabeceras que de verdad archivamos — no un MIME
        // crudo sintético que no tenemos (principio de honestidad de datos).
        var sourceLines = [
            'Message-ID: ' + (d.message_id || '—'),
            d.in_reply_to ? 'In-Reply-To: ' + d.in_reply_to : null,
            'From: ' + (d.from || ''),
            'To: ' + (d.to || ''),
            d.cc ? 'Cc: ' + d.cc : null,
            'Subject: ' + (d.subject || ''),
            'Content-Type: text/html; charset=UTF-8',
        ].filter(Boolean).join('\n');

        var attachments = d.attachments || [];

        return '' +
            '<div class="eml-headers-grid">' +
                '<span>De</span><span class="eml-mono">' + escapeHtml(d.from || '') + '</span>' +
                '<span>Para</span><span class="eml-mono">' + escapeHtml(d.to || '') + '</span>' +
                (d.cc ? '<span>CC</span><span class="eml-mono">' + escapeHtml(d.cc) + '</span>' : '') +
                (d.message_id ? '<span>Message-ID</span><span class="eml-mono" style="font-size:10px">' + escapeHtml(d.message_id) + '</span>' : '') +
                (d.in_reply_to ? '<span>In-Reply-To</span><span class="eml-mono" style="font-size:10px">' + escapeHtml(d.in_reply_to) + '</span>' : '') +
            '</div>' +
            '<div class="eml-body-frame">' +
                '<div class="eml-body-frame-head">' +
                    '<span>Cuerpo del mensaje</span>' +
                    '<span class="eml-body-view-switch">' +
                        '<span class="eml-body-view-btn on" data-eml-bodyview="html">HTML</span>' +
                        '<span class="eml-body-view-btn" data-eml-bodyview="text">Texto</span>' +
                        '<span class="eml-body-view-btn" data-eml-bodyview="source">Fuente</span>' +
                    '</span>' +
                '</div>' +
                '<div class="eml-body-frame-content" data-eml-bodypane="html">' + (d.body_html || '<em>Sin contenido</em>') + '</div>' +
                '<div class="eml-body-frame-content mono" data-eml-bodypane="text" style="display:none">' + escapeHtml(textBody || 'Sin contenido') + '</div>' +
                '<div class="eml-body-frame-content mono" data-eml-bodypane="source" style="display:none">' + escapeHtml(sourceLines) + '</div>' +
                (attachments.length ? '<div class="eml-attach-strip"><span class="eml-attach-label">Adjuntos · ' + attachments.length + '</span>' +
                    attachments.map(function (a) {
                        return '<span class="eml-attach-chip"><i class="fa-regular fa-file"></i> ' + escapeHtml(a.name || 'archivo') + (a.size ? ' <span class="eml-mono">' + formatBytes(a.size) + '</span>' : '') + '</span>';
                    }).join('') + '</div>' : '') +
            '</div>';
    }

    function stripTags(html) {
        return $('<div>').html(html).text();
    }

    function formatBytes(bytes) {
        if (!bytes) { return ''; }
        var kb = bytes / 1024;
        return kb >= 1024 ? (kb / 1024).toFixed(1) + ' MB' : Math.round(kb) + ' KB';
    }

    function renderThreadPane(d) {
        if (!d.thread || !d.thread.length) {
            return '<div class="eml-empty-state"><div style="font-size:12px;color:var(--eml-text-muted)">Sin más mensajes en este ticket.</div></div>';
        }

        return d.thread.map(function (t) {
            var mine = t.id === d.id;
            return '<div class="eml-timeline-item">' +
                '<div class="eml-timeline-dot' + (t.status === 'sent' || t.status === 'delivered' ? ' done' : '') + '"></div>' +
                '<div style="flex:1;min-width:0">' +
                    '<div style="font-size:11.5px;' + (mine ? 'font-weight:700' : '') + '">' + escapeHtml(t.subject || '(sin asunto)') + '</div>' +
                    '<div style="font-size:11px;color:var(--eml-text-muted)">' + escapeHtml(statusLabel(t)) + ' · ' + escapeHtml(t.created_at_human || '') + '</div>' +
                '</div>' +
            '</div>';
        }).join('');
    }

    var TRACE_DOT_DONE = ['sent', 'opened'];

    function renderTracePane(d) {
        var events = d.trace || [];

        if (!events.length) {
            return '<div class="eml-empty-state"><div style="font-size:12px;color:var(--eml-text-muted)">Sin traza de entrega registrada para este email.</div></div>';
        }

        return '<div style="background:#fff;border:1px solid var(--eml-border);border-radius:9px;padding:14px 15px">' +
            events.map(function (e) {
                var time = e.at ? new Date(e.at).toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit', second: '2-digit'}) : '';
                return '<div class="eml-timeline-item">' +
                    '<div class="eml-timeline-dot' + (TRACE_DOT_DONE.indexOf(e.type) !== -1 ? ' done' : '') + '"></div>' +
                    '<div style="flex:1;min-width:0">' +
                        '<div style="font-size:12px;font-weight:600">' + escapeHtml(e.label) + '</div>' +
                        (e.detail ? '<div style="font-size:10.5px;color:var(--eml-text-muted)">' + escapeHtml(e.detail) + '</div>' : '') +
                    '</div>' +
                    '<span class="eml-mono" style="font-size:10px;color:var(--eml-text-muted)">' + escapeHtml(time) + '</span>' +
                '</div>';
            }).join('') +
        '</div>';
    }

    function renderFilesPane(d) {
        if (!d.attachments || !d.attachments.length) {
            return '<div class="eml-empty-state"><div style="font-size:12px;color:var(--eml-text-muted)">Sin adjuntos.</div></div>';
        }

        return '<div style="display:flex;flex-direction:column;gap:8px">' + d.attachments.map(function (a) {
            return '<div style="display:flex;align-items:center;gap:8px;padding:9px 11px;background:#fff;border:1px solid var(--eml-border);border-radius:8px">' +
                '<i class="fa-solid fa-paperclip" style="color:var(--eml-text-muted)"></i>' +
                '<span style="font-size:12px">' + escapeHtml(a.name || 'archivo') + '</span>' +
            '</div>';
        }).join('') + '</div>';
    }

    function renderActivityPane(d) {
        var items = d.activity || [];

        if (!items.length) {
            return '<div class="eml-empty-state"><div style="font-size:12px;color:var(--eml-text-muted)">Sin actividad registrada en este ticket.</div></div>';
        }

        return items.map(function (a) {
            return '<div class="eml-timeline-item">' +
                '<div class="eml-timeline-dot done"></div>' +
                '<div style="flex:1;min-width:0">' +
                    '<div style="font-size:12.5px">' + escapeHtml(a.label || '') + (a.causer ? ' · <strong>' + escapeHtml(a.causer) + '</strong>' : '') + '</div>' +
                    '<div style="font-size:10.5px;color:var(--eml-text-muted)">' + escapeHtml(a.at_human || '') + '</div>' +
                '</div>' +
            '</div>';
        }).join('');
    }

    function renderRelatedPane(d) {
        var items = d.related || [];

        if (!items.length) {
            return '<div class="eml-empty-state"><div style="font-size:12px;color:var(--eml-text-muted)">Sin otros tickets de este cliente.</div></div>';
        }

        return items.map(function (t) {
            return '<a href="' + escapeHtml(t.url_full || '#') + '" style="display:flex;align-items:center;gap:10px;padding:11px 13px;background:#fff;border:1px solid var(--eml-border);border-radius:9px;text-decoration:none;color:inherit">' +
                '<span class="eml-tag-mono">' + escapeHtml(t.ticket_number || '') + '</span>' +
                '<span style="font-size:12.5px;font-weight:600;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + escapeHtml(t.subject || '') + '</span>' +
                '<span class="eml-pill eml-pill-muted">' + escapeHtml(t.status || '—') + '</span>' +
            '</a>';
        }).join('<div style="height:8px"></div>');
    }

    function renderSidePanel(d) {
        var t = d.ticket;
        if (!t) {
            $('#eml-side-panel').html('<div style="font-size:12px;color:var(--eml-text-muted)">Sin ticket asociado.</div>');
            return;
        }

        var c = t.customer;
        // Etiquetas del EMAIL seleccionado (TicketMail.tags), no del ticket —
        // updateTags() edita la fila concreta, coherente con "Etiquetas" del
        // panel lateral del mockup (aplicadas al correo que se está viendo).
        var tags = d.tags || [];

        // ── Cliente ─────────────────────────────────────────────────────
        var subtitle = [c && c.company, c && c.customer_since_year ? 'cliente desde ' + c.customer_since_year : null]
            .filter(Boolean).join(' · ');

        var customerBlock = '<div class="eml-side-block">' +
            '<div class="eml-side-title">Cliente</div>' +
            (c && c.url_c360
                ? '<a href="' + escapeHtml(c.url_c360) + '" class="eml-side-customer-btn">' +
                    '<span class="eml-avatar">' + escapeHtml(initialsOf(c.name)) + '</span>' +
                    '<span style="flex:1;display:flex;flex-direction:column;gap:2px;min-width:0">' +
                        '<span style="font-size:12.5px;font-weight:700">' + escapeHtml(c.name || '—') + '</span>' +
                        (subtitle ? '<span style="font-size:11px;color:var(--eml-text-soft);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + escapeHtml(subtitle) + '</span>' : '') +
                    '</span>' +
                    '<i class="fa-solid fa-chevron-right" style="font-size:10px;color:var(--eml-text-muted)"></i>' +
                '</a>'
                : '<div class="eml-side-customer-btn" style="cursor:default">' +
                    '<span class="eml-avatar">' + escapeHtml(initialsOf(c ? c.name : '')) + '</span>' +
                    '<span style="flex:1;display:flex;flex-direction:column;gap:2px;min-width:0">' +
                        '<span style="font-size:12.5px;font-weight:700">' + escapeHtml(c ? c.name : '—') + '</span>' +
                        (subtitle ? '<span style="font-size:11px;color:var(--eml-text-soft);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + escapeHtml(subtitle) + '</span>' : '') +
                    '</span>' +
                '</div>') +
            (c ? '<div class="eml-side-kv" style="padding:9px 10px;background:var(--eml-bg);border:1px solid var(--eml-border);border-radius:8px">' +
                '<span>Email</span><span>' + escapeHtml(c.email || '—') + '</span>' +
                '<span>Teléfono</span><span>' + escapeHtml(c.phone || '—') + '</span>' +
                (c.language ? '<span>Idioma</span><span>' + escapeHtml(c.language) + '</span>' : '') +
                (c.external_id ? '<span>Cliente ID</span><span>' + escapeHtml(c.external_id) + '</span>' : '') +
            '</div>' : '') +
            (c && (c.tickets_count !== null || c.avg_csat !== null)
                ? '<div class="eml-side-badges">' +
                    (c.tickets_count !== null ? '<span class="eml-side-badge">' + c.tickets_count + ' ticket' + (c.tickets_count === 1 ? '' : 's') + '</span>' : '') +
                    (c.avg_csat !== null && c.avg_csat > 0 ? '<span class="eml-side-badge">CSAT ' + c.avg_csat.toFixed(1) + '</span>' : '') +
                    (c.is_banned ? '<span class="eml-side-badge" style="background:var(--eml-danger-bg);color:var(--eml-danger-fg)">Bloqueado</span>' : '') +
                '</div>' : '') +
        '</div>';

        // ── Integraciones del cliente (solo las realmente conectadas) ────
        var integrationsBlock = '';
        if (c && c.integrations && c.integrations.length) {
            integrationsBlock = '<div class="eml-side-block">' +
                '<div class="eml-side-title">Integraciones del cliente</div>' +
                c.integrations.map(function (i) {
                    return '<div class="eml-side-integration">' +
                        '<span style="flex:1;font-size:12px;font-weight:600">' + escapeHtml(i.label) + '</span>' +
                        '<span style="font-size:9.5px;font-weight:700;color:var(--eml-success-fg)">CONECTADO</span>' +
                    '</div>';
                }).join('') +
            '</div>';
        }

        // ── Formulario: siempre el estado honesto (no hay captura real) ──
        var formBlock = '<div class="eml-side-block">' +
            '<div class="eml-side-title">Datos del formulario</div>' +
            '<div style="padding:10px 11px;background:var(--eml-bg);border:1px dashed var(--eml-border-strong);border-radius:8px;font-size:11.5px;color:var(--eml-text-muted);line-height:1.5">' +
                'Este ticket no proviene de un formulario con datos estructurados. Origen: <span class="eml-mono">' + escapeHtml(originLabel(d.origin)) + '</span>.' +
            '</div>' +
        '</div>';

        var assignedBlock = '<div class="eml-side-block">' +
            '<div class="eml-side-title">Asignado a</div>' +
            '<div class="eml-side-customer-btn" style="cursor:default">' +
                '<span class="eml-avatar">' + escapeHtml(initialsOf(t.assignee)) + '</span>' +
                '<span style="flex:1;font-size:12.5px;font-weight:600">' + escapeHtml(t.assignee || 'Sin asignar') + '</span>' +
            '</div>' +
        '</div>';

        var tagsBlock = '<div class="eml-side-block">' +
            '<div class="eml-side-title">Etiquetas</div>' +
            '<div class="eml-tags-editable" id="eml-tags-editable">' +
                tags.map(function (tag) {
                    return '<span class="eml-tag-chip">' + escapeHtml(tag) + '<button type="button" data-eml-tag-remove="' + escapeHtml(tag) + '"><i class="fa-solid fa-xmark"></i></button></span>';
                }).join('') +
                '<button type="button" class="eml-tag-add" id="eml-tag-add">+ añadir</button>' +
            '</div>' +
        '</div>';

        var detailsBlock = '<div class="eml-side-block">' +
            '<div class="eml-side-title">Detalles</div>' +
            '<div class="eml-side-kv">' +
                '<span>Creado</span><span>' + escapeHtml(t.created_at_human || '—') + '</span>' +
                '<span>Actualizado</span><span>' + escapeHtml(t.updated_at_human || '—') + '</span>' +
                '<span>Origen</span><span>' + escapeHtml(originLabel(d.origin)) + '</span>' +
                '<span>Categoría</span><span>' + escapeHtml(t.category || '—') + '</span>' +
                (t.sla ? '<span>SLA</span><span style="' + (t.sla.color === 'danger' ? 'color:var(--eml-danger-fg)' : t.sla.color === 'warning' ? 'color:var(--eml-warning-fg)' : '') + '">' + escapeHtml(t.sla.label) + '</span>' : '') +
            '</div>' +
        '</div>';

        // ── Trazabilidad del envío (condensada — el detalle completo vive
        // en el tab "Trazabilidad"; aquí solo lo que cabe en 3 líneas) ────
        var opens = (d.trace || []).find(function (e) { return e.type === 'opened'; });
        var traceBlock = '<div class="eml-side-block">' +
            '<div class="eml-side-title">Trazabilidad del envío</div>' +
            '<div class="eml-side-kv" style="padding:10px 11px;background:var(--eml-bg);border:1px solid var(--eml-border);border-radius:8px">' +
                '<span>Estado</span><span style="font-weight:700">' + escapeHtml(statusLabel(d)) + '</span>' +
                (opens ? '<span>Aperturas</span><span>' + escapeHtml(opens.label.replace(/^[^·]+·\s*/, '')) + ' · ' + escapeHtml(opens.detail || '') + '</span>' : '') +
                (d.from ? '<span>Buzón</span><span>' + escapeHtml(d.from) + '</span>' : '') +
            '</div>' +
        '</div>';

        var actionsBlock = '<div class="eml-side-block">' +
            '<div class="eml-side-title">Acciones</div>' +
            (d.url_resend ? '<button type="button" class="eml-side-action-btn" id="eml-side-resend" data-url="' + escapeHtml(d.url_resend) + '"><i class="fa-solid fa-rotate-right" style="color:var(--eml-text-muted)"></i> Reenviar email</button>' : '') +
            '<button type="button" class="eml-side-action-btn" id="eml-action-schedule"><i class="fa-regular fa-clock" style="color:var(--eml-text-muted)"></i> Programar seguimiento</button>' +
            '<button type="button" class="eml-side-action-btn" id="eml-action-translate"><i class="fa-solid fa-language" style="color:var(--eml-text-muted)"></i> Traducir mensaje</button>' +
            '<button type="button" class="eml-side-action-btn" data-eml-open="templates"><i class="fa-solid fa-bolt" style="color:var(--eml-text-muted)"></i> Plantillas y macros</button>' +
            (t.url_full ? '<a href="' + escapeHtml(t.url_full) + '" class="eml-side-action-btn"><i class="fa-solid fa-arrow-up-right-from-square" style="color:var(--eml-text-muted)"></i> Ver ficha completa</a>' : '') +
        '</div>';

        $('#eml-side-panel').html(customerBlock + integrationsBlock + formBlock + assignedBlock + tagsBlock + detailsBlock + traceBlock + actionsBlock);
    }

    function patchTags(payload) {
        var mail = EML.state.mails.find(function (m) { return m.id === EML.state.selected; });
        if (!mail || !mail.url_tags) { return; }

        $.ajax({ url: mail.url_tags, method: 'PATCH', dataType: 'json', headers: csrfHeaders(), data: payload })
            .done(function (resp) {
                if (EML.state.lastDetail) { EML.state.lastDetail.tags = resp.tags; }
                mail.tags = resp.tags;
                renderSidePanel(EML.state.lastDetail);
            })
            .fail(function () {
                if (window.toastr) { toastr.error('No se pudo actualizar la etiqueta'); }
                renderSidePanel(EML.state.lastDetail);
            });
    }

    // ═══════════ Plantillas y macros (bv-modal, reusa el catálogo de Macro) ═══════════
    function openTemplatesModal() {
        var $list = $('#eml-templates-list').html('<div style="padding:20px;text-align:center;color:var(--eml-text-muted);font-size:12px">Cargando…</div>');
        $('[data-bv-modal-name="eml-templates"]').addClass('on');

        $.getJSON(EML.urls.templates, {ticket_id: EML.state.selectedTicketId}).done(function (resp) {
            var templates = resp.templates || [];

            if (!templates.length) {
                $list.html('<div style="padding:20px;text-align:center;color:var(--eml-text-muted);font-size:12px">No hay plantillas disponibles.</div>');
                return;
            }

            $list.html(templates.map(function (tpl) {
                return '<button type="button" class="eml-template-item" data-eml-template-body="' + escapeHtml(tpl.body) + '" style="display:flex;flex-direction:column;gap:2px;align-items:flex-start;padding:9px 11px;border-radius:7px;border:1px solid transparent;width:100%;text-align:left">' +
                    '<div style="font-size:12px;font-weight:600">' + escapeHtml(tpl.name) + '</div>' +
                    (tpl.description ? '<div style="font-size:10.5px;color:var(--eml-text-muted)">' + escapeHtml(tpl.description) + '</div>' : '') +
                '</button>';
            }).join(''));
        });
    }

    function initialsOf(name) {
        name = (name || '').trim();
        if (!name) { return '—'; }
        var parts = name.split(/\s+/);
        return ((parts[0][0] || '') + (parts[1] ? parts[1][0] : '')).toUpperCase();
    }

    // ═══════════ Compose modal (bv-modal) ═══════════
    function openComposeModal() {
        if (!EML.state.selectedTicketId) {
            if (window.toastr) { toastr.warning('Selecciona primero un email de la lista para responder en su ticket.'); }
            return;
        }

        var mail = EML.state.mails.find(function (m) { return m.id === EML.state.selected; });

        $('#eml-compose-form')[0].reset();
        $('#eml-compose-error').hide();
        $('#eml-compose-ticket-id').val(EML.state.selectedTicketId);
        $('#eml-compose-ticket-chip').text(mail ? mail.ticket_number : '');
        $('#eml-compose-to').val(mail ? mail.to : '');
        $('#eml-compose-subject').val(mail ? ('Re: ' + mail.subject) : '');

        $('[data-bv-modal-name="eml-compose"]').addClass('on');
    }

    function closeComposeModal() {
        $('.bv-modal').removeClass('on');
    }

    function submitCompose(e) {
        e.preventDefault();

        var formEl = $('#eml-compose-form')[0];
        var formData = new FormData(formEl);

        var cc = ($('#eml-compose-cc').val() || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean);
        var bcc = ($('#eml-compose-bcc').val() || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean);
        formData.delete('cc_raw');
        formData.delete('bcc_raw');
        cc.forEach(function (email) { formData.append('cc[]', email); });
        bcc.forEach(function (email) { formData.append('bcc[]', email); });

        var $submit = $('#eml-compose-submit').prop('disabled', true);

        $.ajax({
            url: EML.urls.store,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: csrfHeaders(),
        }).done(function (resp) {
            if (window.toastr) { toastr.success(resp.message || 'Email enviado'); }
            closeComposeModal();
            refetch();
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo enviar el email';
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
            }
            $('#eml-compose-error').text(msg).show();
        }).always(function () {
            $submit.prop('disabled', false);
        });
    }

    // ═══════════ Eventos ═══════════
    function bindEvents() {
        $(document).on('input', '#eml-search', debounce(function () {
            EML.state.search = $(this).val();
            refetch();
        }, 350));

        $(document).on('click', '.eml-app-tab', function () {
            EML.state.view = $(this).data('emlView');
            renderTabs();
            refetch();
        });

        $(document).on('change', '#eml-filter-category', function () { EML.state.category = $(this).val(); refetch(); });
        $(document).on('change', '#eml-filter-agent', function () { EML.state.agent = $(this).val(); refetch(); });
        $(document).on('change', '#eml-filter-origin', function () { EML.state.origin = $(this).val(); refetch(); });
        $(document).on('change', '#eml-filter-tag', function () { EML.state.tag = $(this).val(); refetch(); });
        $(document).on('change', '#eml-filter-from', function () { EML.state.from = $(this).val(); refetch(); });
        $(document).on('change', '#eml-filter-to', function () { EML.state.to = $(this).val(); refetch(); });

        $(document).on('click', '#eml-filter-clear', function () {
            EML.state.category = '';
            EML.state.agent = '';
            EML.state.origin = '';
            EML.state.tag = '';
            EML.state.from = '';
            EML.state.to = '';
            EML.state.search = '';
            $('#eml-filter-category, #eml-filter-agent, #eml-filter-origin, #eml-filter-tag').val('');
            $('#eml-filter-from, #eml-filter-to, #eml-search').val('');
            refetch();
        });

        $(document).on('click', '.eml-mode-btn', function () {
            EML.state.mode = $(this).data('emlMode');
            $('.eml-mode-btn').removeClass('on');
            $(this).addClass('on');
            renderList();
        });

        $(document).on('click', '.eml-kcard', function () {
            openDetail(parseInt($(this).data('id'), 10));
        });

        $(document).on('click', '.eml-mail-row', function () {
            openDetail(parseInt($(this).data('id'), 10));
        });

        $(document).on('click', '.eml-row-check', function (e) {
            e.stopPropagation();
            var id = parseInt($(this).data('id'), 10);
            if ($(this).is(':checked')) { EML.state.bulk.add(id); } else { EML.state.bulk.delete(id); }
            renderBulkBar();
        });

        $(document).on('change', '#eml-select-all', function () {
            if ($(this).is(':checked')) {
                EML.state.mails.forEach(function (m) { EML.state.bulk.add(m.id); });
            } else {
                EML.state.bulk.clear();
            }
            renderList();
            renderBulkBar();
        });

        $(document).on('click', '#eml-bulk-clear', function () { EML.state.bulk.clear(); renderList(); renderBulkBar(); });
        $(document).on('click', '#eml-bulk-resend', function () { bulkAction('resend'); });
        $(document).on('click', '#eml-bulk-cancel', function () { bulkAction('cancel_scheduled'); });
        $(document).on('click', '#eml-bulk-export', exportSelection);

        $(document).on('click', '[data-eml-open="compose"]', openComposeModal);
        $(document).on('click', '#eml-detail-reply', openComposeModal);
        $(document).on('click', '#eml-detail-resend, #eml-side-resend', function () {
            var url = $(this).data('url');
            if (!url) { return; }
            $.ajax({ url: url, method: 'POST', dataType: 'json', headers: csrfHeaders() })
                .done(function (resp) {
                    if (window.toastr) { toastr.success(resp.message || 'Email reenviado'); }
                    refetch();
                })
                .fail(function () {
                    if (window.toastr) { toastr.error('No se pudo reenviar el email'); }
                });
        });

        // "Programar seguimiento": abre el mismo compose modal ya construido
        // (Responder), con el campo de programación enfocado — no es una
        // acción nueva, es el mismo flujo de "Responder" con el foco puesto
        // donde el agente lo necesita.
        $(document).on('click', '#eml-action-schedule', function () {
            openComposeModal();
            var tomorrow = new Date(Date.now() + 24 * 60 * 60 * 1000);
            var pad = function (n) { return String(n).padStart(2, '0'); };
            var value = tomorrow.getFullYear() + '-' + pad(tomorrow.getMonth() + 1) + '-' + pad(tomorrow.getDate()) + 'T09:00';
            $('#eml-compose-scheduled').val(value).trigger('focus');
        });

        $(document).on('click', '[data-eml-bodyview]', function () {
            var view = $(this).data('emlBodyview');
            $('.eml-body-view-btn').removeClass('on');
            $(this).addClass('on');
            $('[data-eml-bodypane]').hide();
            $('[data-eml-bodypane="' + view + '"]').show();
        });

        $(document).on('click', '[data-eml-dtab]', function () {
            var tab = $(this).data('emlDtab');
            $('.eml-detail-tab').removeClass('on');
            $(this).addClass('on');
            $('[data-eml-dpane]').hide();
            $('[data-eml-dpane="' + tab + '"]').show();
        });

        $(document).on('click', '[data-eml-tag-remove]', function () {
            patchTags({remove: $(this).data('emlTagRemove')});
        });

        $(document).on('click', '#eml-tag-add', function () {
            var $btn = $(this);
            var $input = $('<input type="text" class="eml-finput" style="width:90px;padding:2px 7px;font-size:11px" placeholder="etiqueta">');
            $btn.replaceWith($input);
            $input.trigger('focus');
            $input.on('keydown', function (e) {
                if (e.key === 'Enter') {
                    var val = $input.val().trim();
                    if (val) { patchTags({add: val}); } else { renderSidePanel(EML.state.lastDetail); }
                } else if (e.key === 'Escape') {
                    renderSidePanel(EML.state.lastDetail);
                }
            });
            $input.on('blur', function () { renderSidePanel(EML.state.lastDetail); });
        });

        $(document).on('click', '#eml-action-translate', function () {
            var mail = EML.state.mails.find(function (m) { return m.id === EML.state.selected; });
            if (!mail || !mail.url_translate) { return; }

            var $btn = $(this).prop('disabled', true);
            $.ajax({ url: mail.url_translate, method: 'POST', dataType: 'json', headers: csrfHeaders(), data: {target: 'es'} })
                .done(function (resp) {
                    if (!resp.success) { if (window.toastr) { toastr.error(resp.message); } return; }
                    $('[data-eml-bodypane="text"]').show().siblings('[data-eml-bodypane]').hide();
                    $('.eml-body-view-btn').removeClass('on').filter('[data-eml-bodyview="text"]').addClass('on');
                    $('[data-eml-bodypane="text"]').prepend('<div style="padding:8px 0;margin-bottom:8px;border-bottom:1px dashed var(--eml-border);color:var(--eml-text-muted);font-size:10px;text-transform:uppercase;letter-spacing:.06em">Traducción (' + escapeHtml(resp.target) + ')</div>' + escapeHtml(resp.translated));
                })
                .fail(function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo traducir el mensaje';
                    if (window.toastr) { toastr.error(msg); }
                })
                .always(function () { $btn.prop('disabled', false); });
        });

        $(document).on('click', '#eml-saved-views .eml-chip-filter[data-eml-saved]', function (e) {
            if ($(e.target).is('button, i')) { return; }

            $('#eml-saved-views .eml-chip-filter').removeClass('on');
            $(this).addClass('on');

            var id = $(this).data('emlSaved');
            if (id === '__all') {
                applyFilters({});
                return;
            }

            var view = (EML.state.savedViews || []).find(function (v) { return v.id === id; });
            if (view) { applyFilters(view.filters || {}); }
        });

        $(document).on('click', '#eml-saved-add', function () {
            var name = window.prompt('Nombre de la vista (p. ej. "Rebotes de hoy"):');
            if (!name) { return; }

            $.ajax({
                url: EML.urls.viewsStore,
                method: 'POST',
                dataType: 'json',
                headers: csrfHeaders(),
                data: {name: name, filters: currentFilters()},
            }).done(function (resp) {
                EML.state.savedViews = (EML.state.savedViews || []).concat([resp.view]);
                renderSavedViews();
                if (window.toastr) { toastr.success('Vista guardada.'); }
            }).fail(function () {
                if (window.toastr) { toastr.error('No se pudo guardar la vista.'); }
            });
        });

        $(document).on('click', '[data-eml-saved-remove]', function (e) {
            e.stopPropagation();
            var id = $(this).data('emlSavedRemove');

            $.ajax({
                url: EML.urls.views.replace(/\/views$/, '/views/' + id),
                method: 'DELETE',
                dataType: 'json',
                headers: csrfHeaders(),
            }).done(function () {
                EML.state.savedViews = (EML.state.savedViews || []).filter(function (v) { return v.id !== id; });
                renderSavedViews();
            }).fail(function () {
                if (window.toastr) { toastr.error('No se pudo eliminar la vista.'); }
            });
        });

        $(document).on('click', '[data-eml-open="templates"]', openTemplatesModal);
        $(document).on('click', '.eml-template-item', function () {
            var body = $(this).data('emlTemplateBody');
            $('[data-bv-modal-name="eml-templates"]').removeClass('on');

            // Si el agente abrió "Plantillas y macros" directamente desde el
            // panel lateral (sin pasar antes por "Responder"), el compose
            // modal aún no existe en pantalla — sin esto la plantilla se
            // insertaba en un campo oculto que nadie llegaba a ver ni enviar.
            if (!$('[data-bv-modal-name="eml-compose"]').hasClass('on')) {
                openComposeModal();
            }
            $('#eml-compose-body').val(body);
        });

        var typingTimer;
        $(document).on('input', '#eml-compose-body', function () {
            clearTimeout(typingTimer);
            emitTyping(true);
            typingTimer = setTimeout(function () { emitTyping(false); }, 2500);
        });

        $('#eml-compose-form').on('submit', submitCompose);

        $(document).on('click', '.bv-modal', function (e) {
            if ($(e.target).is('.bv-modal')) { closeComposeModal(); }
        });
        $(document).on('click', '[data-htk-close]', closeComposeModal);
    }

    function debounce(fn, wait) {
        var t;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, wait);
        };
    }

    $(document).ready(bootstrap);
})();
