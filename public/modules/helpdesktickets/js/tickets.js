/**
 * HelpdeskTickets · Manager Tickets Page
 * jQuery + AJAX (no Livewire/Alpine/React)
 *
 * Features:
 *   - filter pills (open / urgent / mine / unassigned / pending / resolved / all)
 *   - vista lista <-> kanban
 *   - bulk select + bulk actions
 *   - detalle lateral (modal) con tabs
 *   - popover de contacto con tabs (lazy-load ERP + PS + Timeline)
 *   - loading skeletons CSS animados
 *   - mensajes de error específicos por tipo
 *   - optimistic UI para notas
 *   - keyboard shortcuts (Esc, R, Cmd+K, Tab)
 *   - browser notifications en asignación
 *   - customer timeline tab
 *   - search por email/phone/NIF
 *   - bulk warm cache
 *   - service worker offline-first
 *   - IndexedDB cache (1h TTL)
 *   - predictive prefetch heurístico
 *   - dev debug panel (local env)
 */
(function ($) {
    'use strict';

    if (!$ || !$.fn) { return; }

    var HTK = window.HelpdeskTickets = window.HelpdeskTickets || {};

    // ═══════════ [1] Loading skeletons helper ═══════════
    function renderSkeleton($container, lines) {
        lines = lines || 4;
        var widths = ['w-100', 'w-75', 'w-100', 'w-50'];
        var html = '';
        for (var i = 0; i < lines; i++) {
            html += '<div class="htk-skeleton ' + widths[i % widths.length] + '"></div>';
        }
        $container.html(html);
    }

    // ═══════════ [2] Mensajes de error específicos ═══════════
    function getErrorMessage(meta) {
        if (!meta || !meta.error_type) { return null; }
        var msgs = {
            connection: 'No se pudo conectar al ERP. Reintenta en unos minutos.',
            timeout: 'El ERP está respondiendo lento. Mostrando datos cacheados.',
            not_configured: 'ERP no configurado en este entorno.',
            unauthorized: 'Sin permisos para ver datos del ERP.',
            offline: 'Sin conexión. Mostrando datos en caché.',
            unknown: 'Error inesperado al consultar el ERP.',
        };
        return msgs[meta.error_type] || meta.error_message || 'Error desconocido';
    }

    // ═══════════ localStorage cache (1 min TTL) ═══════════
    var LS_KEY_PREFIX = 'htk_ctx_';
    var LS_TTL_MS = 60 * 1000;

    function lsGet(email, source) {
        try {
            var raw = localStorage.getItem(LS_KEY_PREFIX + source + '_' + email);
            if (!raw) { return null; }
            var parsed = JSON.parse(raw);
            if (Date.now() - parsed.t > LS_TTL_MS) {
                localStorage.removeItem(LS_KEY_PREFIX + source + '_' + email);
                return null;
            }
            return parsed.d;
        } catch (e) { return null; }
    }

    function lsPut(email, source, data) {
        try {
            localStorage.setItem(LS_KEY_PREFIX + source + '_' + email, JSON.stringify({
                t: Date.now(),
                d: data,
            }));
        } catch (e) { /* quota o disabled */ }
    }

    // ═══════════ [10] IndexedDB cache (1h TTL) ═══════════
    var IdbCache = (function () {
        var DB_NAME = 'htk-cache';
        var STORE = 'context';
        var TTL_MS = 60 * 60 * 1000;
        var dbPromise = null;

        function getDb() {
            if (dbPromise) { return dbPromise; }
            dbPromise = new Promise(function (resolve, reject) {
                var req = indexedDB.open(DB_NAME, 1);
                req.onupgradeneeded = function () {
                    var db = req.result;
                    if (!db.objectStoreNames.contains(STORE)) {
                        db.createObjectStore(STORE, { keyPath: 'key' });
                    }
                };
                req.onsuccess = function () { resolve(req.result); };
                req.onerror = function () { reject(req.error); };
            });
            return dbPromise;
        }

        return {
            get: function (key) {
                return getDb().then(function (db) {
                    return new Promise(function (resolve) {
                        var tx = db.transaction(STORE, 'readonly');
                        var req = tx.objectStore(STORE).get(key);
                        req.onsuccess = function () {
                            var entry = req.result;
                            if (!entry) { return resolve(null); }
                            if (Date.now() - entry.t > TTL_MS) { return resolve(null); }
                            resolve(entry.d);
                        };
                        req.onerror = function () { resolve(null); };
                    });
                }).catch(function () { return null; });
            },
            put: function (key, data) {
                return getDb().then(function (db) {
                    return new Promise(function (resolve) {
                        var tx = db.transaction(STORE, 'readwrite');
                        tx.objectStore(STORE).put({ key: key, t: Date.now(), d: data });
                        tx.oncomplete = function () { resolve(); };
                        tx.onerror = function () { resolve(); };
                    });
                }).catch(function () {});
            },
        };
    }());

    // ═══════════ [11] Predictive prefetch heurístico ═══════════
    var ContactPattern = (function () {
        var KEY = 'htk-pattern';
        var data = {};
        try { data = JSON.parse(localStorage.getItem(KEY) || '{}'); } catch (e) { data = {}; }
        var lastEmail = null;

        return {
            record: function (email) {
                if (lastEmail && lastEmail !== email) {
                    var pair = lastEmail + '|' + email;
                    data[pair] = (data[pair] || 0) + 1;
                    // GC: keep top 500 pairs
                    var keys = Object.keys(data);
                    if (keys.length > 1000) {
                        var sorted = Object.entries(data).sort(function (a, b) { return b[1] - a[1]; });
                        data = {};
                        sorted.slice(0, 500).forEach(function (e) { data[e[0]] = e[1]; });
                    }
                    try { localStorage.setItem(KEY, JSON.stringify(data)); } catch (e) { /* quota */ }
                }
                lastEmail = email;
            },
            predict: function (email, n) {
                n = n || 3;
                var prefix = email + '|';
                return Object.entries(data)
                    .filter(function (e) { return e[0].startsWith(prefix); })
                    .sort(function (a, b) { return b[1] - a[1]; })
                    .slice(0, n)
                    .map(function (e) { return e[0].split('|')[1]; });
            },
        };
    }());

    // ═══════════ [9] Service Worker registration ═══════════
    // SW scope must be at or below the script's path.
    // The fetch handler uses URL pattern matching to only cache helpdesk API calls.
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/modules/helpdesktickets/sw-helpdesk.js').catch(function (e) {
            console.warn('HTK SW registration failed', e);
        });
    }

    // ═══════════ Prefetch on hover ═══════════
    (function () {
        var prefetchedEmails = new Set();
        var hoverTimer = null;

        $(document).on('mouseenter', '[data-prefetch-email]', function () {
            var email = $(this).attr('data-prefetch-email');
            if (!email || prefetchedEmails.has(email)) { return; }

            clearTimeout(hoverTimer);
            hoverTimer = setTimeout(function () {
                prefetchedEmails.add(email);
                $.get('/api/helpdeskErp/customers/' + encodeURIComponent(email) + '/context').fail(function () {});
                $.get('/api/helpdeskprestashop/customers/' + encodeURIComponent(email) + '/context').fail(function () {});
            }, 300);
        });

        $(document).on('mouseleave', '[data-prefetch-email]', function () {
            clearTimeout(hoverTimer);
        });
    }());

    HTK.state = {
        view: 'list',
        filter: 'open',
        selected: null,
        bulk: new Set(),
        bulkModalMode: false,
        currentUserId: null,
        tickets: [],
        statuses: [],
        counts: {},
    };

    // ═══════════ Helpers ═══════════
    function chLabel(c) {
        return ({wa: 'WhatsApp', fb: 'Facebook', ig: 'Instagram', widget: 'Widget', email: 'Email'})[c] || c;
    }

    function stLabel(slug) {
        return ({open: 'Abierto', progress: 'En curso', pending: 'En espera', resolved: 'Resuelto', closed: 'Cerrado'})[slug] || slug;
    }

    function prLabel(p) {
        return ({low: 'Baja', normal: 'Normal', high: 'Alta', urgent: 'Urgente'})[p] || p;
    }

    function initials(n) {
        if (!n) { return '—'; }
        return n.split(' ').filter(Boolean).slice(0, 2).map(function (s) { return s[0]; }).join('').toUpperCase();
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) { return ''; }
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function colorFor(id) {
        if (!id && id !== 0) { return 1; }
        return ((parseInt(id, 10) % 8) + 1);
    }

    function timeAgo(iso) {
        if (!iso) { return '—'; }
        var d = new Date(iso);
        var diff = (Date.now() - d.getTime()) / 1000;
        if (diff < 60) { return 'hace ' + Math.floor(diff) + 's'; }
        if (diff < 3600) { return 'hace ' + Math.floor(diff / 60) + ' min'; }
        if (diff < 86400) { return 'hace ' + Math.floor(diff / 3600) + ' h'; }
        if (diff < 604800) { return 'hace ' + Math.floor(diff / 86400) + ' d'; }
        return d.toLocaleDateString();
    }

    function formatDate(iso) {
        if (!iso) { return '—'; }
        try { return new Date(iso).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' }); } catch (e) { return iso; }
    }

    // Cuenta atrás compacta hasta la fecha de vencimiento del SLA.
    function slaCountdown(iso) {
        if (!iso) { return ''; }
        var due = new Date(iso).getTime();
        var diff = Math.round((due - Date.now()) / 60000);
        var overdue = diff < 0;
        var mins = Math.abs(diff);
        var label;
        if (mins < 60) { label = mins + 'm'; }
        else if (mins < 1440) { label = Math.floor(mins / 60) + 'h ' + (mins % 60) + 'm'; }
        else { label = Math.floor(mins / 1440) + 'd ' + Math.floor((mins % 1440) / 60) + 'h'; }
        return overdue ? ('hace ' + label) : ('en ' + label);
    }

    // Badge de estado SLA (Bootstrap 5.3 + FA6). 'none' => oculto.
    function slaBadge(status, iso) {
        var map = {
            on_track: { cls: 'bg-success', text: 'En plazo' },
            warning: { cls: 'bg-warning text-dark', text: 'Por vencer' },
            breached: { cls: 'bg-danger', text: 'Vencido' }
        };
        var cfg = map[status];
        if (!cfg) { return ''; }
        var cd = slaCountdown(iso);
        var cdHtml = cd ? (' <span class="htk-sla-cd">' + escapeHtml(cd) + '</span>') : '';
        return '<span class="badge ' + cfg.cls + ' htk-sla-badge" data-sla-due="' + escapeHtml(iso || '') + '">' +
            '<i class="fas fa-gauge me-1"></i>' + cfg.text + cdHtml + '</span>';
    }

    // ═══════════ Filtering ═══════════
    function passesFilter(t) {
        var f = HTK.state.filter;
        if (f === 'all') { return true; }
        if (f === 'mine') { return t.assignee && t.assignee.id == HTK.state.currentUserId; }
        if (f === 'unassigned') { return !t.assignee; }
        if (f === 'urgent') { return t.priority === 'urgent' || t.sla_kind === 'breach'; }
        if (f === 'pending') { return t.status_slug === 'pending'; }
        if (f === 'resolved') { return t.status_slug === 'resolved'; }
        if (f === 'open') { return t.status_slug === 'open' || t.status_slug === 'progress'; }
        return t.status_slug === f;
    }

    function recountFilters() {
        var counts = {open: 0, urgent: 0, mine: 0, unassigned: 0, pending: 0, resolved: 0, all: HTK.state.tickets.length};
        HTK.state.tickets.forEach(function (t) {
            if (t.status_slug === 'open' || t.status_slug === 'progress') { counts.open++; }
            if (t.priority === 'urgent' || t.sla_kind === 'breach') { counts.urgent++; }
            if (t.assignee && t.assignee.id == HTK.state.currentUserId) { counts.mine++; }
            if (!t.assignee) { counts.unassigned++; }
            if (t.status_slug === 'pending') { counts.pending++; }
            if (t.status_slug === 'resolved') { counts.resolved++; }
        });
        HTK.state.counts = counts;
        Object.keys(counts).forEach(function (k) {
            $('.htk-pill[data-filter="' + k + '"] .c').text(counts[k]);
        });
    }

    // ═══════════ Render: tabla lista ═══════════
    function renderList() {
        var rows = HTK.state.tickets.filter(passesFilter);
        var $tbody = $('#htk-tbody').empty();

        if (rows.length === 0) {
            $tbody.append(
                '<tr><td colspan="9">' +
                '<div class="htk-empty-state">' +
                '<div class="ic"><i class="fas fa-ticket"></i></div>' +
                '<div class="t">No hay tickets</div>' +
                '<div class="s">No se encontraron tickets que coincidan con el filtro actual</div>' +
                '</div></td></tr>'
            );
            $('#htk-count').text(0);
            return;
        }

        rows.forEach(function (t) {
            var isActive = HTK.state.selected === t.id;
            var checked = HTK.state.bulk.has(t.id) ? 'checked' : '';
            var unread = t.unread_count > 0 ? '<span class="unread"></span>' : '';
            var channel = t.source || 'email';
            var tagsHtml = (t.tags || []).slice(0, 2).map(function (tag) {
                return '<span class="htk-chip">' + escapeHtml(tag) + '</span>';
            }).join('');

            var custInitials = initials(t.customer ? t.customer.name : '—');
            var custColor = colorFor(t.customer ? t.customer.id : 0);

            var assigneeHtml = t.assignee
                ? '<div class="htk-av htk-av-xs c' + colorFor(t.assignee.id) + '" title="' + escapeHtml(t.assignee.name) + '">' + initials(t.assignee.name) + '</div>'
                : '<span style="color:#a1a1aa;font-size:10.5px">—</span>';

            var slaKind = t.sla_kind || 'ok';
            var slaTxt = t.sla_text || '—';
            var slaBadgeHtml = slaBadge(t.sla_status, t.sla_due_at);

            var custEmail = t.customer ? (t.customer.email || '') : '';
            var $tr = $(
                '<tr data-id="' + t.id + '" class="' + (isActive ? 'active' : '') + '" data-prefetch-email="' + escapeHtml(custEmail) + '">' +
                '<td style="width:32px;padding-left:18px"><input type="checkbox" class="htk-checkbox" data-id="' + t.id + '" ' + checked + '></td>' +
                '<td style="width:90px"><span class="htk-id">' + escapeHtml(t.ticket_number) + '</span></td>' +
                '<td>' +
                '<div class="htk-subj">' + unread + escapeHtml(t.subject || t.title || '—') + '</div>' +
                '<div class="htk-meta">' +
                '<span class="htk-tag-ch ' + channel + '"><span class="htk-ch-dot ' + channel + '"></span>' + chLabel(channel) + '</span>' +
                tagsHtml +
                '</div>' +
                '</td>' +
                '<td style="width:200px">' +
                '<div class="htk-cust">' +
                '<div class="htk-av htk-av-sm c' + custColor + '">' + custInitials + '</div>' +
                '<div class="info">' +
                '<div class="nm">' + escapeHtml(t.customer ? t.customer.name : '—') + '</div>' +
                '<div class="em">' + escapeHtml(t.customer ? t.customer.email : '') + '</div>' +
                '</div>' +
                '</div>' +
                '</td>' +
                '<td style="width:90px"><span class="htk-badge ' + (t.status_slug || 'open') + '">' + escapeHtml(t.status_name || stLabel(t.status_slug)) + '</span></td>' +
                '<td style="width:100px"><span class="htk-prio ' + t.priority + '"><span class="d"></span>' + prLabel(t.priority) + '</span></td>' +
                '<td style="width:130px"><div class="htk-sla-cell">' + slaBadgeHtml + '<span class="htk-sla ' + slaKind + '"><i class="far fa-clock"></i>' + escapeHtml(slaTxt) + '</span></div></td>' +
                '<td style="width:60px;text-align:center">' + assigneeHtml + '</td>' +
                '<td style="width:120px;color:#a1a1aa;font-size:11px;font-family:JetBrains Mono,monospace">' + timeAgo(t.updated_at) + '</td>' +
                '</tr>'
            );

            $tbody.append($tr);
        });

        $('#htk-count').text(rows.length);
    }

    // ═══════════ Render: kanban ═══════════
    function renderKanban() {
        var rows = HTK.state.tickets.filter(passesFilter);
        var cols = HTK.state.statuses.length
            ? HTK.state.statuses
            : [
                {slug: 'open', name: 'Abiertos'},
                {slug: 'progress', name: 'En curso'},
                {slug: 'pending', name: 'En espera'},
                {slug: 'resolved', name: 'Resueltos'},
                {slug: 'closed', name: 'Cerrados'},
            ];

        var html = cols.map(function (c) {
            var items = rows.filter(function (t) { return t.status_slug === c.slug; });
            var cardsHtml = items.map(function (t) {
                var channel = t.source || 'email';
                var assigneeMini = t.assignee
                    ? '<div class="htk-av c' + colorFor(t.assignee.id) + '" style="width:18px;height:18px;font-size:8px;border-radius:4px">' + initials(t.assignee.name) + '</div>'
                    : '';
                return (
                    '<div class="htk-card ' + (HTK.state.selected === t.id ? 'active' : '') + '" data-id="' + t.id + '">' +
                    '<div class="row1">' +
                    '<span class="id">' + escapeHtml(t.ticket_number) + '</span>' +
                    '<span class="htk-prio ' + t.priority + '" title="' + prLabel(t.priority) + '"><span class="d"></span></span>' +
                    (t.unread_count > 0 ? '<span style="width:6px;height:6px;border-radius:50%;background:#2563eb"></span>' : '') +
                    slaBadge(t.sla_status, t.sla_due_at) +
                    '<span class="htk-sla ' + (t.sla_kind || 'ok') + '">' + escapeHtml(t.sla_text || '—') + '</span>' +
                    '</div>' +
                    '<div class="ts">' + escapeHtml(t.subject || t.title || '—') + '</div>' +
                    '<div class="row2">' +
                    '<span class="htk-tag-ch ' + channel + '" style="padding:1px 5px;font-size:9.5px"><span class="htk-ch-dot ' + channel + '"></span>' + chLabel(channel) + '</span>' +
                    '<span class="from">' + escapeHtml(t.customer ? t.customer.name : '—') + '</span>' +
                    assigneeMini +
                    '</div>' +
                    '</div>'
                );
            }).join('') || '<div style="font-size:11px;color:#a1a1aa;text-align:center;padding:14px">Sin tickets</div>';

            return (
                '<div class="htk-col" data-status="' + c.slug + '">' +
                '<div class="htk-col-h">' +
                '<span class="nm"><span class="d ' + c.slug + '"></span>' + escapeHtml(c.name) + '</span>' +
                '<span class="c">' + items.length + '</span>' +
                '</div>' +
                '<div class="htk-cards">' + cardsHtml + '</div>' +
                '</div>'
            );
        }).join('');

        $('#htk-kanban').html(html);
        $('#htk-count').text(rows.length);
    }

    function renderAll() {
        $('.htk-pill').each(function () {
            $(this).toggleClass('active', $(this).data('filter') === HTK.state.filter);
        });
        $('.htk-view-btn').each(function () {
            $(this).toggleClass('active', $(this).data('view') === HTK.state.view);
        });
        $('#htk-list-wrap').toggle(HTK.state.view === 'list');
        $('#htk-kanban').toggle(HTK.state.view === 'kanban');

        recountFilters();

        if (HTK.state.view === 'list') {
            renderList();
        } else {
            renderKanban();
        }
        renderBulk();
    }

    // ═══════════ Bulk bar ═══════════
    function renderBulk() {
        var $bar = $('#htk-bulk');
        $bar.toggleClass('show', HTK.state.bulk.size > 0);
        $('#htk-bulk-count').text(HTK.state.bulk.size);

        var visible = HTK.state.tickets.filter(passesFilter);
        var allChecked = visible.length > 0 && visible.every(function (t) {
            return HTK.state.bulk.has(t.id);
        });
        $('#htk-select-all').prop('checked', allChecked)
            .prop('indeterminate', !allChecked && HTK.state.bulk.size > 0);
    }

    // ═══════════ Detalle ═══════════
    function openDetail(id) {
        var t = HTK.state.tickets.find(function (x) { return x.id == id; });
        if (!t) { return; }
        HTK.state.selected = id;

        var channel = t.source || 'email';

        $('#htkd-id').text(t.ticket_number);
        $('#htkd-channel').html('<span class="htk-tag-ch ' + channel + '"><span class="htk-ch-dot ' + channel + '"></span>' + chLabel(channel) + '</span>');
        $('#htkd-status').html('<span class="htk-badge ' + (t.status_slug || 'open') + '">' + escapeHtml(t.status_name || stLabel(t.status_slug)) + '</span>');
        $('#htkd-priority').html('<span class="htk-prio ' + t.priority + '"><span class="d"></span>' + prLabel(t.priority) + '</span>');
        $('#htkd-sla').html(slaBadge(t.sla_status, t.sla_due_at) + '<span class="htk-sla ' + (t.sla_kind || 'ok') + '"><i class="far fa-clock"></i>' + escapeHtml(t.sla_text || '—') + '</span>');
        $('#htkd-subj').text(t.subject || t.title || '—');

        var custColor = colorFor(t.customer ? t.customer.id : 0);
        $('#htkd-cust').html(
            '<div class="htk-av htk-av-md c' + custColor + '">' + initials(t.customer ? t.customer.name : '—') + '</div>' +
            '<div class="nm">' + escapeHtml(t.customer ? t.customer.name : '—') + '</div>' +
            '<i class="fas fa-chevron-right" style="margin-left:auto;color:#a1a1aa"></i>'
        );

        var tagsHtml = (t.tags || []).map(function (tag) {
            return '<span class="htk-chip removable">' + escapeHtml(tag) + '<span class="x"><i class="fas fa-xmark"></i></span></span>';
        }).join('') || '<span style="color:#a1a1aa;font-size:11px">Sin etiquetas</span>';
        $('#htkd-tags').html(tagsHtml);

        var assigneeHtml = t.assignee
            ? '<div class="htk-av htk-av-xs c' + colorFor(t.assignee.id) + '">' + initials(t.assignee.name) + '</div>' +
              '<span class="nm">' + escapeHtml(t.assignee.name) + '</span>' +
              '<i class="fas fa-chevron-right" style="margin-left:auto;color:#a1a1aa"></i>'
            : '<span style="color:#a1a1aa;font-size:11px">Sin asignar</span><span style="margin-left:auto;font-size:11px;color:#52525b">+ Asignar</span>';
        $('#htkd-assignee').html(assigneeHtml);

        $('#htkd-meta').html(
            '<div class="htk-d-meta-row"><span class="l">Creado</span><span class="v">' + (t.created_at_human || '—') + '</span></div>' +
            '<div class="htk-d-meta-row"><span class="l">Actualizado</span><span class="v">' + (timeAgo(t.updated_at)) + '</span></div>' +
            '<div class="htk-d-meta-row"><span class="l">Origen</span><span class="v">' + chLabel(channel) + '</span></div>' +
            (t.category_name ? '<div class="htk-d-meta-row"><span class="l">Categoría</span><span class="v">' + escapeHtml(t.category_name) + '</span></div>' : '')
        );

        loadConversation(t);
        renderActivity(t);

        $('#htkd-detail-link').attr('href', t.url_full || t.url || '#');

        $('#htkd-overlay').addClass('show');
        $('#htkd').addClass('show');
        renderAll();
    }

    function loadConversation(t) {
        var $conv = $('#htkd-conv');
        $conv.html('<div class="htk-empty-state"><div class="ic"><i class="fas fa-comments"></i></div><div class="t">Cargando conversación…</div></div>');

        if (!t.url_messages) {
            $conv.html(buildMockConversation(t));
            return;
        }

        $.get(t.url_messages)
            .done(function (resp) {
                var items = (resp && resp.data) ? resp.data : (Array.isArray(resp) ? resp : []);
                if (!items.length) {
                    $conv.html('<div class="htk-empty-state"><div class="ic"><i class="far fa-comment"></i></div><div class="t">Sin mensajes</div><div class="s">Aún no hay mensajes en este ticket</div></div>');
                    return;
                }
                $conv.html(items.map(function (m) {
                    return renderMessage(m, t);
                }).join(''));
            })
            .fail(function () {
                $conv.html(buildMockConversation(t));
            });
    }

    function renderMessage(m, t) {
        if (m.type === 'system' || m.is_event) {
            return '<div class="htk-event"><b>' + escapeHtml(m.body || m.event || '') + '</b></div>';
        }

        var isOut = m.is_internal === false && m.author_role !== 'customer' && m.user_id;
        var isNote = !!m.is_internal;
        var dirClass = isOut ? 'out' : '';
        var bubbleClass = isNote ? 'note' : (isOut ? 'out' : 'in');

        var avName = m.author_name || (t.customer ? t.customer.name : '—');
        var avColor = colorFor(m.user_id || (t.customer ? t.customer.id : 0));

        return (
            '<div class="htk-msg ' + dirClass + '">' +
            '<div class="htk-av htk-av-sm c' + avColor + '">' + initials(avName) + '</div>' +
            '<div class="body">' +
            '<div class="bubble ' + bubbleClass + '">' + escapeHtml(m.body || '') + '</div>' +
            '<div class="meta-mini">' +
            (isNote ? '<i class="fas fa-lock"></i> Nota interna · ' : '') +
            escapeHtml(avName) + ' · ' + timeAgo(m.created_at) +
            '</div>' +
            '</div>' +
            '</div>'
        );
    }

    function buildMockConversation(t) {
        return (
            '<div class="htk-event"><b>Ticket creado · ' + (t.created_at_human || '—') + '</b></div>' +
            '<div class="htk-msg">' +
            '<div class="htk-av htk-av-sm c' + colorFor(t.customer ? t.customer.id : 0) + '">' + initials(t.customer ? t.customer.name : '—') + '</div>' +
            '<div class="body">' +
            '<div class="bubble in">' + escapeHtml(t.description || t.subject || '—') + '</div>' +
            '<div class="meta-mini">' + escapeHtml(t.customer ? t.customer.name : '—') + ' · ' + timeAgo(t.created_at) + '</div>' +
            '</div>' +
            '</div>'
        );
    }

    function renderActivity(t) {
        var items = [
            {ic: 'fa-circle-plus', cls: 'success', body: 'Ticket creado desde <b>' + chLabel(t.source || 'email') + '</b>', tm: t.created_at_human || '—'},
        ];
        if (t.assignee) {
            items.unshift({ic: 'fa-user', cls: 'info', body: 'Asignado a <b>' + escapeHtml(t.assignee.name) + '</b>', tm: timeAgo(t.assigned_at)});
        }
        if (t.tags && t.tags.length) {
            items.unshift({ic: 'fa-tag', cls: 'success', body: 'Etiquetas: <b>' + (t.tags || []).map(escapeHtml).join(', ') + '</b>', tm: '—'});
        }
        if (t.priority === 'urgent' || t.priority === 'high') {
            items.unshift({ic: 'fa-arrow-up', cls: 'warning', body: 'Prioridad <b>' + prLabel(t.priority) + '</b>', tm: '—'});
        }

        $('#htkd-activity').html(items.map(function (it) {
            return (
                '<div class="htk-act-row">' +
                '<div class="htk-act-ic ' + it.cls + '"><i class="fas ' + it.ic + '"></i></div>' +
                '<div class="htk-act-body">' + it.body + '<div class="tm">' + it.tm + '</div></div>' +
                '</div>'
            );
        }).join(''));
    }

    function closeDetail() {
        $('#htkd-overlay').removeClass('show');
        $('#htkd').removeClass('show');
        HTK.state.selected = null;
        renderAll();
    }

    function setDetailTab(name) {
        $('.htk-d-tab').each(function () {
            $(this).toggleClass('on', $(this).data('tab') === name);
        });
        $('[data-htkd-pane]').each(function () {
            $(this).toggle($(this).data('htkdPane') === name);
        });
    }

    // ═══════════ Contact popover ═══════════
    function openContact(id) {
        var t = HTK.state.tickets.find(function (x) { return x.id == id; });
        if (!t || !t.customer) { return; }
        var c = t.customer;
        var color = colorFor(c.id);
        $('#htk-cnt-av').attr('class', 'htk-av c' + color).text(initials(c.name));
        $('#htk-cnt-name').text(c.name);
        $('#htk-cnt-email').text(c.email || '—');

        $('#htk-contact-pop')
            .data('email', c.email || '')
            .addClass('show');

        // Resetear lazy-load state al abrir nuevo contacto
        $('.cnt-tab').removeData('ps-loaded').removeData('erp-loaded').removeData('timeline-loaded');

        var related = HTK.state.tickets.filter(function (x) { return x.customer && x.customer.id == c.id; });
        var html = related.slice(0, 8).map(function (x) {
            return (
                '<a class="cnt-ticket" href="' + (x.url || '#') + '">' +
                '<span class="id">' + escapeHtml(x.ticket_number) + '</span>' +
                '<span class="t">' + escapeHtml(x.subject || x.title || '—') + '</span>' +
                '<span class="htk-badge ' + (x.status_slug || 'open') + '">' + escapeHtml(x.status_name || stLabel(x.status_slug)) + '</span>' +
                '</a>'
            );
        }).join('') || '<span style="font-size:11px;color:#a1a1aa">Sin tickets relacionados</span>';
        $('#htk-cnt-tickets-list').html(html);
        $('#htk-cnt-tickets-count').text(related.length);

        // [11] Registrar patrón + prefetch predictivo
        ContactPattern.record(c.email || '');
        var predicted = ContactPattern.predict(c.email || '', 2);
        predicted.forEach(function (email) {
            $.get('/api/helpdeskErp/customers/' + encodeURIComponent(email) + '/context').fail(function () {});
            $.get('/api/helpdeskprestashop/customers/' + encodeURIComponent(email) + '/context').fail(function () {});
        });
    }

    function closeContact() {
        $('#htk-contact-pop').removeClass('show');
    }

    function setContactTab(name) {
        $('.cnt-tab').each(function () {
            $(this).toggleClass('on', $(this).data('cntTab') === name);
        });
        $('.cnt-pane').each(function () {
            $(this).toggle($(this).data('cntPane') === name);
        });
    }

    // ═══════════ Reply tabs ═══════════
    function switchReplyTab(tab) {
        var $reply = $('#htkd-reply-box');
        $reply.toggleClass('is-note', tab === 'note');
        $('#htkd-text-pane').toggle(tab !== 'macro');
        $('#htkd-macro-pane').toggle(tab === 'macro');
        if (tab === 'macro') { loadMacros(); }
        if (tab !== 'macro') {
            var ph = tab === 'note'
                ? 'Nota interna (solo visible para el equipo)…'
                : 'Escribe tu respuesta… usa / para macros, @ para mencionar';
            $('#htkd-reply-text').attr('placeholder', ph).focus();
        }
    }

    // ═══════════ Modal system ═══════════
    function openHtkModal(name, bulk) {
        closeAllHtkModals();
        HTK.state.bulkModalMode = !!bulk;
        $('[data-bv-modal-name="' + name + '"]').addClass('on');
        if (name === 'htk-assign')   { populateAssignModal(HTK.state.bulkModalMode); }
        if (name === 'htk-priority') { syncPriorityModal(); }
        if (name === 'htk-bulk-tag') { $('#htk-bulk-tag-input').val('').focus(); }
    }

    function closeAllHtkModals() {
        $('.bv-modal').removeClass('on');
    }

    function populateAssignModal(bulk) {
        var t = HTK.state.tickets.find(function (x) { return x.id == HTK.state.selected; });
        var agents = $('#htk-data').data('agents') || [];
        // En modo bulk no hay "asignación actual" única que preseleccionar
        // (los tickets elegidos pueden tener assignees distintos).
        var currentId = (!bulk && t && t.assignee) ? t.assignee.id : null;

        // La acción bulk "assign" exige un agent_id (no admite desasignar en
        // lote), así que el botón "Sin asignar" solo aparece en modo individual.
        var unassignItem = bulk ? '' : ('<button type="button" class="bv-opt' + (!currentId ? ' on' : '') + '" data-agent-id="">' +
            '<div style="width:32px;height:32px;border-radius:8px;background:#f4f4f5;display:grid;place-items:center;flex-shrink:0">' +
            '<i class="fas fa-user-slash" style="font-size:12px;color:#71717a"></i></div>' +
            '<div class="body"><div class="name">Sin asignar</div><div class="sub">Quitar la asignación actual</div></div>' +
            '<i class="fas fa-check check"></i></button>');

        var agentItems = agents.map(function (a) {
            var on = a.id == currentId ? ' on' : '';
            return '<button type="button" class="bv-opt' + on + '" data-agent-id="' + a.id + '">' +
                '<div class="htk-av htk-av-xs c' + colorFor(a.id) + '" style="flex-shrink:0">' + initials(a.name) + '</div>' +
                '<div class="body"><div class="name">' + escapeHtml(a.name) + '</div></div>' +
                '<i class="fas fa-check check"></i></button>';
        }).join('');

        $('#htk-assign-list').html(unassignItem + agentItems);
        $('#htk-assign-search').val('').focus();
    }

    function syncPriorityModal() {
        var t = HTK.state.tickets.find(function (x) { return x.id == HTK.state.selected; });
        var cur = t ? (t.priority || 'normal') : 'normal';
        $('[data-bv-modal-name="htk-priority"] .bv-r-prio-btn').removeClass('on');
        $('[data-bv-modal-name="htk-priority"] .bv-r-prio-btn[data-prio="' + cur + '"]').addClass('on');
    }

    function execQuickAction(action) {
        if (!HTK.state.selected) { return; }
        var t = HTK.state.tickets.find(function (x) { return x.id == HTK.state.selected; });
        if (!t || !t.url) { return; }

        var url = t.url + '/' + action;
        $('#htkd-confirm').removeClass('show');
        $('#htkd-confirm-ok').prop('disabled', true);

        $.ajax({
            url: url, method: 'POST',
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json'},
        })
            .done(function (resp) {
                if (window.toastr) { toastr.success(resp.message || 'Acción completada'); }
                if (resp.ticket) { refreshTicketInState(resp.ticket); }
                closeDetail();
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Error al ejecutar la acción';
                if (window.toastr) { toastr.error(msg); }
            })
            .always(function () {
                $('#htkd-confirm-ok').prop('disabled', false);
            });
    }

    // ═══════════ QA — Update field ═══════════
    function doUpdateTicket(data, successMsg) {
        if (!HTK.state.selected) { return; }
        var t = HTK.state.tickets.find(function (x) { return x.id == HTK.state.selected; });
        if (!t || !t.url) { return; }

        $.ajax({
            url: t.url,
            method: 'PUT',
            data: data,
            dataType: 'json',
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json'},
        })
            .done(function (resp) {
                if (window.toastr) { toastr.success(successMsg || 'Actualizado'); }
                var fresh = resp.ticket || resp.data || resp;
                if (fresh && fresh.id) { refreshTicketInState(fresh); openDetail(fresh.id); }
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo actualizar';
                if (window.toastr) { toastr.error(msg); }
            });
    }

    function refreshTicketInState(fresh) {
        var idx = HTK.state.tickets.findIndex(function (x) { return x.id == fresh.id; });
        if (idx === -1) { return; }
        var existing = HTK.state.tickets[idx];
        if (fresh.priority)  { existing.priority = fresh.priority; }
        if (fresh.status)    { existing.status_slug = fresh.status.slug || existing.status_slug; existing.status_name = fresh.status.name || existing.status_name; }
        if (fresh.assignee !== undefined) { existing.assignee = fresh.assignee; }
        renderAll();
    }

    // ═══════════ Macros ═══════════
    var macrosCache = null;

    function loadMacros() {
        if (macrosCache) { renderMacroList(macrosCache); return; }
        var url = $('#htk-data').data('macrosUrl');
        if (!url) { return; }
        $('#htkd-macro-list').html('<div class="htk-macros-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>');
        $.get(url)
            .done(function (resp) {
                macrosCache = resp.macros || [];
                renderMacroList(macrosCache);
            })
            .fail(function () {
                $('#htkd-macro-list').html('<div class="htk-macros-loading">No se pudieron cargar las macros</div>');
            });
    }

    function renderMacroList(list) {
        if (!list.length) {
            $('#htkd-macro-list').html('<div class="htk-macros-loading">No hay macros disponibles</div>');
            return;
        }
        var html = list.map(function (m) {
            return '<div class="htk-macro-item" data-id="' + m.id + '" data-name="' + escapeHtml(m.name) + '">' +
                '<div class="nm">' + escapeHtml(m.name) + '</div>' +
                (m.description ? '<div class="desc">' + escapeHtml(m.description) + '</div>' : '') +
                '</div>';
        }).join('');
        $('#htkd-macro-list').html(html);
    }

    function applyMacro(macroId, macroName) {
        if (!HTK.state.selected) { return; }
        var t = HTK.state.tickets.find(function (x) { return x.id == HTK.state.selected; });
        if (!t || !t.url) { return; }

        var url = t.url + '/macros/' + macroId + '/apply';
        $.ajax({
            url: url, method: 'POST',
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json'},
        })
            .done(function (resp) {
                if (window.toastr) { toastr.success(resp.message || 'Macro aplicada: ' + macroName); }
                switchReplyTab('reply');
                $('.htk-reply-tab[data-reply-tab="reply"]').addClass('on');
                $('.htk-reply-tab[data-reply-tab="macro"]').removeClass('on');
                loadConversation(t);
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo aplicar la macro';
                if (window.toastr) { toastr.error(msg); }
            });
    }

    // ═══════════ Reply ═══════════
    function sendReply() {
        if (!HTK.state.selected) { return; }
        var t = HTK.state.tickets.find(function (x) { return x.id == HTK.state.selected; });
        if (!t || !t.url_message_store) { return; }

        var $textarea = $('#htkd-reply-text');
        var body = $.trim($textarea.val());
        if (!body) {
            if (window.toastr) { toastr.warning('Escribe un mensaje antes de enviar'); }
            return;
        }

        var isInternal = $('.htk-reply-tab.on').data('replyTab') === 'note';
        var $btn = $('#htkd-reply-send').prop('disabled', true);

        $.ajax({
            url: t.url_message_store,
            method: 'POST',
            data: {body: body, is_internal: isInternal ? 1 : 0},
            dataType: 'json',
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        })
            .done(function (resp) {
                $textarea.val('');
                if (window.toastr) { toastr.success(resp.message || 'Mensaje enviado'); }
                loadConversation(t);
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo enviar el mensaje';
                if (window.toastr) { toastr.error(msg); }
            })
            .always(function () {
                $btn.prop('disabled', false);
            });
    }

    // ═══════════ Bulk actions ═══════════
    function bulkAction(action, extra) {
        if (HTK.state.bulk.size === 0) { return; }

        if (action === 'delete') {
            var n = HTK.state.bulk.size;
            if (!confirm('¿Eliminar ' + n + ' ticket' + (n !== 1 ? 's' : '') + '? Esta acción no se puede deshacer.')) {
                return;
            }
        }

        var ids = Array.from(HTK.state.bulk);
        var url = $('#htk-data').data('bulkUrl');
        if (!url) { return; }

        $.ajax({
            url: url,
            method: 'POST',
            dataType: 'json',
            data: $.extend({action: action, ticket_ids: ids}, extra || {}),
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        })
            .done(function (resp) {
                if (window.toastr) { toastr.success(resp.message || 'Acción ejecutada'); }
                if (window.toastr && resp.skipped_ticket_ids && resp.skipped_ticket_ids.length) {
                    toastr.warning(resp.skipped_ticket_ids.length + ' ticket(s) omitidos: sin permiso para esta acción.');
                }

                if (action === 'delete') {
                    HTK.state.bulk.forEach(function (id) {
                        var idx = HTK.state.tickets.findIndex(function (x) { return x.id === id; });
                        if (idx !== -1) { HTK.state.tickets.splice(idx, 1); }
                    });
                    if (HTK.state.selected && HTK.state.bulk.has(HTK.state.selected)) {
                        HTK.state.selected = null;
                        $('#htkd-overlay, #htkd-panel').removeClass('open');
                    }
                }

                HTK.state.bulk.clear();
                renderAll();
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo ejecutar la acción';
                if (window.toastr) { toastr.error(msg); }
            });
    }

    // ═══════════ Filtros modal ═══════════
    function applyFiltersModal() {
        var form = $('#htk-filters-form').serialize();
        window.location.search = form;
    }

    // ═══════════ [6] Customer timeline ═══════════
    function loadTimeline(email) {
        var $data = $('#htk-cnt-timeline-data');
        renderSkeleton($data, 6);

        $.get('/api/helpdeskErp/customers/' + encodeURIComponent(email) + '/timeline', { limit: 30 })
            .done(function (resp) {
                var events = resp.data || [];
                if (!events.length) {
                    $data.html('<p class="text-muted text-center py-3" style="font-size:12px">Sin eventos en el historial</p>');
                    return;
                }

                var iconMap = {
                    erp_order: 'fa-truck',
                    erp_invoice: 'fa-file-invoice',
                    ps_order: 'fa-shopping-cart',
                    ps_cart: 'fa-shopping-basket',
                    helpdesk_conversation: 'fa-comments',
                };
                var colorMap = {
                    erp_order: '#10b981',
                    erp_invoice: '#3b82f6',
                    ps_order: '#f59e0b',
                    ps_cart: '#8b5cf6',
                    helpdesk_conversation: '#ef4444',
                };

                var html = '<div class="htk-timeline">';
                events.forEach(function (e) {
                    var icon = iconMap[e.type] || 'fa-circle';
                    var color = colorMap[e.type] || '#64748b';
                    html += '<div class="htk-timeline-item">' +
                        '<div class="htk-timeline-icon" style="background:' + escapeHtml(color) + '"><i class="fas ' + icon + '"></i></div>' +
                        '<div class="htk-timeline-body">' +
                        '<div class="htk-timeline-title">' + escapeHtml(e.title) + '</div>' +
                        '<div class="htk-timeline-date">' + formatDate(e.date) + '</div>' +
                        '</div></div>';
                });
                html += '</div>';
                $data.html(html);
            })
            .fail(function () {
                $data.html('<p class="text-muted text-center py-3" style="font-size:12px">No se pudo cargar el historial</p>');
            });
    }

    // ═══════════ [7] Search por phone/NIF — debounce handler ═══════════
    var searchTimer = null;

    // ═══════════ [3] Optimistic UI para notas ═══════════
    /**
     * Añade una nota de forma optimista. El endpoint POST /orders/{id}/notes
     * puede no existir aún — si falla, revierte el elemento con fadeOut.
     */
    function optimisticAddNote(orderId, note) {
        var tempId = 'tmp_' + Date.now();
        var $row = $('<div class="htk-note-row" data-id="' + tempId + '">' +
            '<span class="badge bg-secondary me-1">enviando...</span>' +
            escapeHtml(note) +
            '</div>');
        $('#htk-notes-list').prepend($row);

        return $.ajax({
            url: '/api/helpdeskprestashop/orders/' + orderId + '/notes',
            method: 'POST',
            data: { note: note },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        }).done(function (resp) {
            $row.find('.badge').remove();
            $row.attr('data-id', (resp.data && resp.data.id) || tempId);
        }).fail(function () {
            $row.fadeOut(300, function () { $(this).remove(); });
            if (window.toastr) { toastr.error('No se pudo añadir la nota.'); }
        });
    }

    HTK.optimisticAddNote = optimisticAddNote;

    // ═══════════ [5] Browser notifications ═══════════
    function maybeRequestNotificationPermission() {
        if (!('Notification' in window)) { return; }
        if (Notification.permission === 'default') {
            $(document).one('click', function () {
                Notification.requestPermission();
            });
        }
    }

    function notifyTicketAssigned(ticket) {
        if (!('Notification' in window) || Notification.permission !== 'granted') { return; }
        var n = new Notification('Nuevo ticket asignado', {
            body: ticket.subject || ('Ticket #' + ticket.id),
            icon: '/favicon.ico',
            tag: 'ticket-' + ticket.id,
        });
        n.onclick = function () {
            window.focus();
            if (ticket.url) { window.location.href = ticket.url; }
            n.close();
        };
    }

    HTK.notifyTicketAssigned = notifyTicketAssigned;

    // ═══════════ [8] Bulk warm cache ═══════════
    $(document).on('click', '#htk-bulk-warm', function () {
        var emails = [];
        $('[data-prefetch-email]:visible').each(function () {
            var e = $(this).attr('data-prefetch-email');
            if (e && emails.indexOf(e) === -1) { emails.push(e); }
        });
        emails = emails.slice(0, 50);

        if (!emails.length) {
            if (window.toastr) { toastr.info('Sin emails para pre-cargar.'); }
            return;
        }

        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            url: '/api/helpdeskErp/cache/warm',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ emails: emails }),
            headers: { 'X-CSRF-TOKEN': csrfToken },
        }).done(function (resp) {
            if (window.toastr) { toastr.success((resp.queued || emails.length) + ' contextos ERP en cola.'); }
        }).fail(function () {
            // Endpoint puede no existir aún — ignorar silenciosamente
        });

        $.ajax({
            url: '/api/helpdeskprestashop/cache/warm',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ emails: emails }),
            headers: { 'X-CSRF-TOKEN': csrfToken },
        }).fail(function () { /* ignorar */ });
    });

    // ═══════════ [4] Keyboard shortcuts ═══════════
    $(document).on('keydown', function (e) {
        var $popover = $('#htk-contact-pop.show');

        // Esc — cerrar modal → popover → detalle (en ese orden)
        if (e.key === 'Escape') {
            if ($('.bv-modal.on').length) {
                closeAllHtkModals();
            } else if ($popover.length) {
                closeContact();
            } else if ($('#htkd').hasClass('show')) {
                closeDetail();
            }
            return;
        }

        // Solo dentro del popover
        if (!$popover.length) { return; }

        // R — refresh tab activa del popover
        if ((e.key === 'r' || e.key === 'R') && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
            var $activeTab = $popover.find('.cnt-tab.on');
            if ($activeTab.length) {
                $activeTab.removeData('ps-loaded').removeData('erp-loaded').removeData('timeline-loaded');
                $activeTab.trigger('click');
            }
            return;
        }

        // Cmd/Ctrl+K — focus search
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            var $search = $('#htk-search-input');
            if ($search.length) { $search.focus(); }
            return;
        }

        // Tab / Shift+Tab — navegar tabs del popover
        if (e.key === 'Tab') {
            var $tabs = $popover.find('.cnt-tab');
            var $current = $popover.find('.cnt-tab.on');
            var idx = $tabs.index($current);
            var nextIdx = e.shiftKey ? idx - 1 : idx + 1;
            if (nextIdx < 0) { nextIdx = $tabs.length - 1; }
            if (nextIdx >= $tabs.length) { nextIdx = 0; }
            e.preventDefault();
            $tabs.eq(nextIdx).trigger('click');
        }
    });

    // ═══════════ Init ═══════════
    function bindEvents() {
        $('.htk-pill').on('click', function () {
            HTK.state.filter = $(this).data('filter');
            HTK.state.bulk.clear();
            renderAll();
        });

        $('.htk-view-btn').on('click', function () {
            HTK.state.view = $(this).data('view');
            renderAll();
        });

        $(document).on('click', '#htk-tbody tr', function (e) {
            if ($(e.target).closest('.htk-checkbox').length) { return; }
            openDetail($(this).data('id'));
        });

        $('#htk-select-all').on('change', function () {
            var checked = $(this).is(':checked');
            HTK.state.tickets.filter(passesFilter).forEach(function (t) {
                if (checked) { HTK.state.bulk.add(t.id); } else { HTK.state.bulk.delete(t.id); }
            });
            renderAll();
        });

        $(document).on('change', '#htk-tbody .htk-checkbox', function () {
            var id = $(this).data('id');
            if ($(this).is(':checked')) { HTK.state.bulk.add(id); } else { HTK.state.bulk.delete(id); }
            renderBulk();
        });

        $(document).on('click', '.htk-card', function () {
            openDetail($(this).data('id'));
        });

        $('#htkd-close, #htkd-overlay').on('click', closeDetail);
        $(document).on('click', '.htk-d-tab', function () {
            setDetailTab($(this).data('tab'));
        });

        $('#htkd-cust').on('click', function () {
            if (HTK.state.selected) { openContact(HTK.state.selected); }
        });
        $('#htk-cnt-close').on('click', closeContact);

        // Lazy-load por tab del popover
        $(document).on('click', '.cnt-tab', function () {
            var $btn = $(this);
            var tab  = $btn.data('cntTab');
            setContactTab(tab);

            var email = $('#htk-contact-pop').data('email') || $('#htk-cnt-email').text().trim();
            if (!email || email === '—') { return; }

            if (tab === 'pedidos' && !$btn.data('ps-loaded')) {
                $btn.data('ps-loaded', true);
                loadPrestashopContext(email);
            }

            if (tab === 'erp' && !$btn.data('erp-loaded')) {
                $btn.data('erp-loaded', true);
                loadErpContext(email);
            }

            // [6] Timeline lazy-load
            if (tab === 'timeline' && !$btn.data('timeline-loaded')) {
                $btn.data('timeline-loaded', true);
                loadTimeline(email);
            }
        });

        $(document).on('click', '.htk-reply-tab', function () {
            var tab = $(this).data('replyTab');
            $('.htk-reply-tab').removeClass('on');
            $(this).addClass('on');
            switchReplyTab(tab);
        });
        $('#htkd-reply-send').on('click', sendReply);

        // ── QA buttons → modales ──
        $(document).on('click', '.htk-d-qa', function (e) {
            e.stopPropagation();
            var action = $(this).data('action');
            if (action === 'assign')   { openHtkModal('htk-assign'); }
            if (action === 'priority') { openHtkModal('htk-priority'); }
            if (action === 'resolve')  { openHtkModal('htk-resolve'); }
            if (action === 'close')    { openHtkModal('htk-close-ticket'); }
        });

        // Cerrar modal: backdrop click
        $(document).on('click', '.bv-modal', function (e) {
            if ($(e.target).is('.bv-modal')) { closeAllHtkModals(); }
        });
        // Cerrar modal: botón data-htk-close
        $(document).on('click', '[data-htk-close]', function () {
            closeAllHtkModals();
        });

        // Assign — seleccionar opción
        $(document).on('click', '[data-bv-modal-name="htk-assign"] .bv-opt', function () {
            $(this).closest('.bv-opt-list').find('.bv-opt').removeClass('on');
            $(this).addClass('on');
        });
        // Assign — buscar
        $(document).on('input', '#htk-assign-search', function () {
            var q = $(this).val().toLowerCase();
            $('[data-bv-modal-name="htk-assign"] .bv-opt').each(function () {
                $(this).toggle(!q || $(this).find('.name').text().toLowerCase().indexOf(q) !== -1);
            });
        });
        // Assign — aplicar (individual o bulk según cómo se abrió el modal)
        $('#htk-assign-apply').on('click', function () {
            var $sel = $('[data-bv-modal-name="htk-assign"] .bv-opt.on');
            var agentId = $sel.data('agentId');

            if (HTK.state.bulkModalMode) {
                if (!agentId) { return; }
                bulkAction('assign', {agent_id: agentId});
            } else {
                doUpdateTicket({assignee_id: agentId || null}, 'Asignado correctamente');
            }

            closeAllHtkModals();
        });

        // Priority — seleccionar botón
        $(document).on('click', '[data-bv-modal-name="htk-priority"] .bv-r-prio-btn', function () {
            $(this).siblings().removeClass('on');
            $(this).addClass('on');
        });
        // Priority — aplicar
        $('#htk-priority-apply').on('click', function () {
            var prio = $('[data-bv-modal-name="htk-priority"] .bv-r-prio-btn.on').data('prio');
            if (prio) { doUpdateTicket({priority: prio}, 'Prioridad actualizada'); }
            closeAllHtkModals();
        });

        // Resolver — aplicar
        $('#htk-resolve-apply').on('click', function () {
            execQuickAction('resolve');
            closeAllHtkModals();
        });
        // Cerrar — aplicar
        $('#htk-close-apply').on('click', function () {
            execQuickAction('close');
            closeAllHtkModals();
        });

        // Etiquetar (bulk) — aplicar
        $('#htk-bulk-tag-apply').on('click', function () {
            var tag = $.trim($('#htk-bulk-tag-input').val());
            if (!tag) { $('#htk-bulk-tag-input').focus(); return; }
            bulkAction('add_tag', {tag: tag});
            closeAllHtkModals();
        });
        $('#htk-bulk-tag-input').on('keydown', function (e) {
            if (e.key === 'Enter') { $('#htk-bulk-tag-apply').click(); }
        });

        $(document).on('input', '#htkd-macro-search', function () {
            var q = $(this).val().toLowerCase();
            $('#htkd-macro-list .htk-macro-item').each(function () {
                $(this).toggle($(this).data('name').toLowerCase().indexOf(q) !== -1);
            });
        });

        $(document).on('click', '.htk-macro-item', function () {
            applyMacro($(this).data('id'), $(this).data('name'));
        });

        $('.htk-bulk-action').on('click', function () {
            var action = $(this).data('action');
            // assign/add_tag necesitan un dato adicional (agente/etiqueta):
            // se piden en un modal en vez de disparar la acción a ciegas.
            if (action === 'assign')  { openHtkModal('htk-assign', true); return; }
            if (action === 'add_tag') { openHtkModal('htk-bulk-tag'); return; }
            bulkAction(action);
        });

        $('#htk-apply-filters').on('click', applyFiltersModal);

        // [7] Search debounce
        $(document).on('input', '#htk-search-input', function () {
            clearTimeout(searchTimer);
            var q = $(this).val().trim();
            var type = $('#htk-search-type').val();
            if (q.length < 3) {
                $('#htk-search-results').empty();
                return;
            }
            searchTimer = setTimeout(function () {
                $.get('/api/helpdeskErp/customers/search', { q: q, type: type })
                    .done(function (resp) {
                        var html = (resp.data || []).map(function (c) {
                            return '<a href="#" class="htk-search-item" data-email="' + escapeHtml(c.email || '') + '">' +
                                '<strong>' + escapeHtml((c.label || c.name || '') + ' ' + (c.surnames || '')) + '</strong>' +
                                '<small class="text-muted ms-2">' + escapeHtml(c.email || '') + '</small></a>';
                        }).join('');
                        $('#htk-search-results').html(html || '<div class="htk-search-no-results">Sin resultados</div>');
                    })
                    .fail(function () { $('#htk-search-results').empty(); });
            }, 300);
        });

        $(document).on('click', '.htk-search-item', function (e) {
            e.preventDefault();
            var email = $(this).data('email');
            $('#htk-search-results').empty();
            $('#htk-search-input').val('');
            if (email) {
                // Cargar popover desde búsqueda: llenar campos mínimos y abrir
                $('#htk-cnt-name').text($(this).find('strong').text());
                $('#htk-cnt-email').text(email);
                $('#htk-cnt-av').attr('class', 'htk-av c1').text(initials($(this).find('strong').text()));
                $('#htk-contact-pop').data('email', email).addClass('show');
                $('.cnt-tab').removeData('ps-loaded').removeData('erp-loaded').removeData('timeline-loaded');
            }
        });
    }

    var ERP_MAX_ATTEMPTS = 3;
    var ERP_RETRY_MS = 35000;

    function showErpOrdersSpinner(message) {
        $('#htk-erp-orders-block').show();
        $('#htk-erp-orders-list').html(
            '<div class="text-center py-3 text-muted"><i class="fas fa-circle-notch fa-spin"></i> ' + escapeHtml(message) + '</div>'
        );
    }

    function renderErpContext(resp) {
        var $loading = $('#htk-cnt-erp-loading');
        var $empty   = $('#htk-cnt-erp-empty');
        var $data    = $('#htk-cnt-erp-data');

        $loading.hide();

        // [2] Mostrar error específico si viene del backend
        if (resp && resp.meta && resp.meta.error_type) {
            var errMsg = getErrorMessage(resp.meta);
            $empty.html('<div style="font-size:12px;color:#64748b;padding:12px 0;text-align:center"><i class="fas fa-exclamation-triangle text-warning me-1"></i>' + escapeHtml(errMsg) + '</div>').show();
            if (resp.customer && resp.customer.found) {
                // Hay datos parciales — mostrar igualmente
            } else {
                return;
            }
        }

        if (!resp || !resp.customer || !resp.customer.found) {
            $empty.show();
            return;
        }

        var c = resp.customer;
        $('#htk-erp-name').text(c.name || '—');
        $('#htk-erp-nif').text(c.nif || '—');
        $('#htk-erp-credit').text(c.credit_limit != null ? fmtMoney(c.credit_limit) : '—');
        $('#htk-erp-balance').text(c.balance != null ? fmtMoney(c.balance) : '—');
        $('#htk-erp-terms').text(c.payment_terms || '—');
        $('#htk-erp-city').text(c.city || '—');

        var ordersLoading = resp.orders_loading || resp.ordersLoading;
        if (ordersLoading) {
            showErpOrdersSpinner('Cargando pedidos desde Oracle…');
        } else if (resp.orders && resp.orders.length) {
            var html = '';
            resp.orders.forEach(function (o) {
                var stateCode  = parseInt(o.status, 10);
                var stateBadge = stateCode ? '<span class="htk-badge open" style="font-size:10px">Estado ' + stateCode + '</span> ' : '';
                html += '<div class="cnt-row" style="flex-direction:column;align-items:flex-start;gap:3px;padding:6px 0;border-bottom:1px solid #f4f4f5">' +
                    '<div style="display:flex;gap:6px;align-items:center;width:100%">' +
                    '<span style="font-weight:600;font-size:12px">#' + escapeHtml(String(o.number || '')) + '</span>' +
                    stateBadge +
                    '</div>' +
                    '<div style="display:flex;gap:10px;color:#71717a;font-size:11px">' +
                    '<span><i class="fas fa-calendar-alt"></i> ' + escapeHtml((o.date || '').substring(0, 10)) + '</span>' +
                    (o.served_date ? '<span><i class="fas fa-check"></i> ' + escapeHtml(o.served_date.substring(0, 10)) + '</span>' : '') +
                    '</div>' +
                    (o.observations ? '<div style="font-size:11px;color:#52525b;font-style:italic">' + escapeHtml(o.observations) + '</div>' : '') +
                    '</div>';
            });
            $('#htk-erp-orders-list').html(html);
            $('#htk-erp-orders-block').show();
        }

        // Facturas ERP
        if (resp.invoices && resp.invoices.length) {
            var invHtml = '';
            resp.invoices.forEach(function (inv) {
                invHtml += '<div class="cnt-row" style="flex-direction:column;align-items:flex-start;gap:2px;padding:5px 0;border-bottom:1px solid #f4f4f5">' +
                    '<div style="display:flex;gap:6px;align-items:center;width:100%">' +
                    '<span style="font-weight:600;font-size:12px">' + escapeHtml((inv.series || '') + '-' + (inv.number || '')) + ' (' + (inv.year || '') + ')</span>' +
                    (inv.simplified ? '<span class="htk-badge open" style="font-size:10px">Simpl.</span>' : '') +
                    '</div>' +
                    '<span style="color:#71717a;font-size:11px">' + escapeHtml(inv.date || '—') + '</span>' +
                    '</div>';
            });
            if (!$('#htk-erp-invoices-block').length) {
                $('#htk-erp-orders-block').after('<div class="cnt-block" id="htk-erp-invoices-block"><div class="h">Últimas facturas</div><div id="htk-erp-invoices-list"></div></div>');
            }
            $('#htk-erp-invoices-list').html(invHtml);
            $('#htk-erp-invoices-block').show();
        }

        $data.show();
    }

    var ERP_BROADCAST_TIMEOUT_MS = 90000;

    function leaveErpBroadcastChannel(channelName) {
        if (typeof window.Echo === 'undefined') { return; }
        try { window.Echo.leave(channelName); } catch (e) { /* ignore */ }
    }

    function loadErpContext(email, attempt) {
        attempt = attempt || 1;

        var $loading = $('#htk-cnt-erp-loading');
        var $empty   = $('#htk-cnt-erp-empty');
        var $data    = $('#htk-cnt-erp-data');

        if (attempt === 1) {
            // [1] Skeleton en lugar de spinner mientras carga
            $loading.hide();
            $empty.hide();
            $data.show();
            renderSkeleton($data, 5);

            // [10] IDB cache — mostrar dato fresco inmediatamente si existe
            IdbCache.get('erp_' + email).then(function (idbData) {
                if (idbData) {
                    renderErpContext(idbData);
                } else {
                    // Fallback: localStorage (1min TTL)
                    var lsCached = lsGet(email, 'erp');
                    if (lsCached) { renderErpContext(lsCached); }
                }
            });
        }

        return $.get('/api/helpdeskErp/customers/' + encodeURIComponent(email) + '/context')
            .done(function (resp) {
                lsPut(email, 'erp', resp);
                IdbCache.put('erp_' + email, resp);
                renderErpContext(resp);

                var ordersLoading = resp && (resp.orders_loading || resp.ordersLoading);
                if (!ordersLoading) { return; }

                var channelName = resp.meta && resp.meta.broadcastChannel ? resp.meta.broadcastChannel : null;
                var eventName   = resp.meta && resp.meta.broadcastEvent   ? resp.meta.broadcastEvent   : '.erp.orders.ready';

                if (channelName && typeof window.Echo !== 'undefined') {
                    showErpOrdersSpinner('Cargando pedidos desde Oracle…');

                    var done = false;
                    var timeoutId = null;

                    try {
                        window.Echo.channel(channelName).listen(eventName, function () {
                            if (done) { return; }
                            done = true;
                            clearTimeout(timeoutId);
                            leaveErpBroadcastChannel(channelName);
                            loadErpContext(email);
                        });

                        timeoutId = setTimeout(function () {
                            if (done) { return; }
                            done = true;
                            leaveErpBroadcastChannel(channelName);
                            if (attempt < ERP_MAX_ATTEMPTS) {
                                showErpOrdersSpinner('Cargando pedidos desde Oracle… (intento ' + attempt + '/' + ERP_MAX_ATTEMPTS + ')');
                                setTimeout(function () { loadErpContext(email, attempt + 1); }, ERP_RETRY_MS);
                            }
                        }, ERP_BROADCAST_TIMEOUT_MS);
                    } catch (e) {
                        console.warn('HelpdeskTickets: Echo subscribe failed, falling back to polling', e);
                        leaveErpBroadcastChannel(channelName);
                        if (attempt < ERP_MAX_ATTEMPTS) {
                            showErpOrdersSpinner('Cargando pedidos desde Oracle… (intento ' + attempt + '/' + ERP_MAX_ATTEMPTS + ')');
                            setTimeout(function () { loadErpContext(email, attempt + 1); }, ERP_RETRY_MS);
                        }
                    }

                    return;
                }

                if (attempt < ERP_MAX_ATTEMPTS) {
                    showErpOrdersSpinner('Cargando pedidos desde Oracle… (intento ' + attempt + '/' + ERP_MAX_ATTEMPTS + ')');
                    setTimeout(function () { loadErpContext(email, attempt + 1); }, ERP_RETRY_MS);
                }
            })
            .fail(function () {
                $loading.hide();
                $empty.show();
            });
    }

    function bootstrap() {
        var $data = $('#htk-data');
        if ($data.length === 0) { return; }

        try {
            HTK.state.tickets = $data.data('tickets') || [];
            HTK.state.statuses = $data.data('statuses') || [];
            HTK.state.currentUserId = $data.data('userId');
            HTK.state.filter = $data.data('initialFilter') || 'open';
            HTK.state.view = $data.data('initialView') || 'list';
        } catch (err) {
            console.error('HelpdeskTickets: invalid data attributes', err);
            HTK.state.tickets = [];
        }

        bindEvents();
        renderAll();
        bindEcho($data);
        maybeRequestNotificationPermission();
        initDebugPanel();

        // Ticket preseleccionado (?ticket= en la URL, típicamente desde el
        // redirect de la URL corta /tickets/{id}): se antepuso al payload en
        // el servidor si no estaba ya en la página/filtro actual.
        var selectedId = parseInt($data.data('selectedId'), 10);
        if (selectedId) { openDetail(selectedId); }
    }

    function bindEcho($data) {
        if (typeof window.Echo === 'undefined') {
            var attempts = 0;
            var interval = setInterval(function () {
                if (typeof window.Echo !== 'undefined') {
                    clearInterval(interval);
                    bindEcho($data);
                } else if (++attempts > 30) {
                    clearInterval(interval);
                    console.warn('HelpdeskTickets: Echo not available, real-time updates disabled');
                }
            }, 100);
            return;
        }

        var urlBase = ($data.data('ticketsUrlBase') || '').replace(/\/$/, '');
        var known = ['open', 'progress', 'pending', 'resolved', 'closed'];

        function statusSlug(statusObj) {
            if (!statusObj) { return 'open'; }
            if (known.indexOf(statusObj.slug || '') !== -1) { return statusObj.slug; }
            return statusObj.is_open ? 'open' : 'closed';
        }

        function normalizeTicket(t) {
            return {
                id: t.id,
                ticket_number: t.ticket_number,
                subject: t.subject,
                title: t.subject,
                priority: t.priority || 'normal',
                source: t.source || 'email',
                tags: [],
                status_slug: statusSlug(t.status),
                status_name: t.status ? t.status.name : '',
                category_name: t.category ? t.category.name : null,
                customer: t.customer || null,
                assignee: t.assignee || null,
                created_at: t.created_at,
                created_at_human: 'hace un momento',
                updated_at: t.created_at,
                unread_count: 1,
                sla_kind: 'ok',
                sla_text: '—',
                sla_status: 'none',
                sla_due_at: null,
                url: urlBase + '/' + t.id,
                url_full: urlBase + '/' + t.id + '/full',
                url_message_store: urlBase + '/' + t.id + '/messages',
            };
        }

        // [5] Leer userId desde meta tag
        var userId = $('meta[name="user-id"]').attr('content') || HTK.state.currentUserId;

        window.Echo.private('helpdesk.tickets')
            .listen('.ticket.created', function (data) {
                var exists = HTK.state.tickets.find(function (x) { return x.id === data.ticket.id; });
                if (exists) { return; }
                HTK.state.tickets.unshift(normalizeTicket(data.ticket));
                renderAll();
                if (typeof toastr !== 'undefined') {
                    toastr.info(
                        '<strong>' + escapeHtml(data.ticket.ticket_number || '') + '</strong> — ' + escapeHtml(data.ticket.subject || ''),
                        'Nuevo ticket',
                        { timeOut: 6000, allowHtml: true }
                    );
                }
            })
            .listen('.ticket.updated', function (data) {
                var t = HTK.state.tickets.find(function (x) { return x.id === data.ticket.id; });
                if (!t) { return; }
                if (data.ticket.priority) { t.priority = data.ticket.priority; }
                if (data.ticket.updated_at) { t.updated_at = data.ticket.updated_at; }
                renderAll();
            })
            .listen('.ticket.status.changed', function (data) {
                var t = HTK.state.tickets.find(function (x) { return x.id === data.ticket_id; });
                if (!t || !data.new_status) { return; }
                t.status_slug = statusSlug(data.new_status);
                t.status_name = data.new_status.name;
                renderAll();
            })
            .listen('.message.received', function (data) {
                var t = HTK.state.tickets.find(function (x) { return x.id === data.ticket_id; });
                if (!t) { return; }
                t.unread_count = (t.unread_count || 0) + 1;
                if (data.ticket && data.ticket.last_message_at) {
                    t.updated_at = data.ticket.last_message_at;
                }
                renderAll();
            });

        // [5] Escuchar notificaciones de asignación desde canal privado de usuario
        if (userId) {
            window.Echo.private('App.Models.User.' + userId)
                .notification(function (notification) {
                    if (notification.type === 'ticket_assigned') {
                        notifyTicketAssigned(notification);
                    }
                });
        }
    }

    // ═══════════ Prestashop Context ═══════════
    function fmtMoney(amount, sign) {
        return parseFloat(amount || 0).toFixed(2) + ' ' + (sign || '€');
    }

    var SALE_TYPE_LABELS = {
        dni: 'DNI',
        escopeta: 'Licencia escopeta',
        rifle: 'Licencia rifle',
        corta: 'Arma corta',
    };

    function buildOrderCard(o, idx) {
        var bodyId = 'htk-order-body-' + idx;
        var color  = o.state && o.state.color ? o.state.color : '#888';
        var state  = o.state && o.state.name ? escapeHtml(o.state.name) : '—';
        var sign   = o.currency_sign || o.currency || '€';
        var date   = (o.placed_at || '').substring(0, 10);

        var html = '<div class="htk-order-card">' +
            '<div class="htk-order-head" data-target="' + bodyId + '">' +
            '<span class="ref">#' + escapeHtml(o.reference || '') + '</span>' +
            '<span class="state-dot" style="background:' + escapeHtml(color) + '"></span>' +
            '<span class="state-name">' + state + '</span>' +
            '<span class="total">' + fmtMoney((o.totals || {}).total, sign) + '</span>' +
            '<span class="date">' + date + '</span>' +
            '<i class="fas fa-chevron-down chev"></i>' +
            '</div>';

        if (o.requires_documentation) {
            var saleLabel = SALE_TYPE_LABELS[o.sale_type] || escapeHtml(o.sale_type || '');
            html += '<div class="htk-doc-alert">' +
                '<i class="fas fa-triangle-exclamation"></i>' +
                'Requiere documentación: <strong>' + saleLabel + '</strong>' +
                '</div>';
        }

        html += '<div class="htk-order-body" id="' + bodyId + '">';

        if (o.lines && o.lines.length) {
            html += '<table class="htk-order-lines">';
            o.lines.forEach(function (l) {
                var hasDisc = l.discount && parseFloat(l.discount) > 0;
                var refParts = [l.reference];
                if (l.ean13) { refParts.push(l.ean13); }
                var qtyHtml = l.quantity + '×';
                if (l.quantity_returned > 0) {
                    qtyHtml += ' <span style="color:#90bb13">(devuelto: ' + l.quantity_returned + ')</span>';
                }
                html += '<tr>' +
                    '<td class="qty">' + qtyHtml + '</td>' +
                    '<td class="name">' +
                    '<div>' + escapeHtml(l.name || '') + '</div>' +
                    '<div class="ref">' + escapeHtml(refParts.filter(Boolean).join(' · ')) + '</div>' +
                    (hasDisc ? '<div class="discount-line">−' + fmtMoney(l.discount, sign) + ' dto.</div>' : '') +
                    '</td>' +
                    '<td class="price">' + fmtMoney(l.total, sign) + '</td>' +
                    '</tr>';
            });
            html += '</table>';
        }

        var t = o.totals || {};
        html += '<div class="htk-order-totals">';
        if (t.products_excl) {
            html += '<div class="tr"><span>Subtotal (sin IVA)</span><span>' + fmtMoney(t.products_excl, sign) + '</span></div>';
        }
        if (t.products) {
            html += '<div class="tr"><span>Subtotal</span><span>' + fmtMoney(t.products, sign) + '</span></div>';
        }
        if (t.discount && parseFloat(t.discount) > 0) {
            html += '<div class="tr discount"><span>Descuento</span><span>−' + fmtMoney(t.discount, sign) + '</span></div>';
        }
        if (t.wrapping && parseFloat(t.wrapping) > 0) {
            html += '<div class="tr"><span>Embalaje regalo</span><span>' + fmtMoney(t.wrapping, sign) + '</span></div>';
        }
        if (t.shipping !== undefined) {
            html += '<div class="tr"><span>Envío</span><span>' + (parseFloat(t.shipping) === 0 ? 'Gratis' : fmtMoney(t.shipping, sign)) + '</span></div>';
        }
        if (t.tax && parseFloat(t.tax) > 0) {
            html += '<div class="tr tax"><span>IVA</span><span>' + fmtMoney(t.tax, sign) + '</span></div>';
        }
        html += '<div class="tr grand"><span>Total</span><span>' + fmtMoney(t.total, sign) + '</span></div>';
        html += '</div>';

        if (o.payment_method) {
            html += '<div class="htk-order-payment"><i class="fas fa-credit-card"></i>' + escapeHtml(o.payment_method) + '</div>';
        }

        if (o.tracking && o.tracking.length) {
            o.tracking.forEach(function (tr) {
                if (!tr.tracking_number) { return; }
                var trHtml = tr.tracking_url
                    ? '<a href="' + escapeHtml(tr.tracking_url) + '" target="_blank">' + escapeHtml(tr.tracking_number) + '</a>'
                    : escapeHtml(tr.tracking_number);
                var carrierName = tr.carrier_name || tr.carrier || '';
                var shippedAt  = tr.shipped_at ? ' · ' + escapeHtml(tr.shipped_at) : '';
                html += '<div class="htk-order-payment"><i class="fas fa-truck"></i>' +
                    (carrierName ? escapeHtml(carrierName) + ' · ' : '') +
                    trHtml + shippedAt +
                    '</div>';
            });
        }

        html += '</div></div>';
        return html;
    }

    function buildCartCard(cart) {
        var sign = cart.currency_sign || cart.currency || '€';
        var t    = cart.totals || {};

        var virtualBadge = cart.is_virtual
            ? ' <span class="htk-virtual-badge"><i class="fas fa-cloud"></i> Virtual</span>'
            : '';

        var html = '<div class="htk-cart-card">' +
            '<div class="htk-cart-head">' +
            '<i class="fas fa-cart-arrow-down" style="color:#d97706"></i>' +
            '<span class="cart-id">Carrito #' + cart.id + '</span>' +
            virtualBadge +
            '<span class="cart-date">' + (cart.updated_at || '').substring(0, 10) + '</span>' +
            '<span class="cart-total">' + fmtMoney(t.total || t.products, sign) + '</span>' +
            '</div>';

        if (cart.items && cart.items.length) {
            cart.items.forEach(function (it) {
                var hasDisc = it.has_discount && it.unit_price_original;
                html += '<div class="htk-cart-item">';
                if (it.image_url) {
                    html += '<img src="' + escapeHtml(it.image_url) + '" alt="" loading="lazy">';
                }
                html += '<div class="ci-info">' +
                    '<div class="ci-name">' + escapeHtml(it.name || '') + '</div>';
                if (it.manufacturer) {
                    html += '<div class="ci-variant" style="color:#a1a1aa">' + escapeHtml(it.manufacturer) + '</div>';
                }
                if (it.attributes_small) {
                    html += '<div class="ci-variant">' + escapeHtml(it.attributes_small) + '</div>';
                }
                if (it.flags && it.flags.length) {
                    var flagsHtml = '';
                    it.flags.slice(0, 2).forEach(function (flag) {
                        flagsHtml += '<span class="htk-ps-flag" style="background:' + escapeHtml(flag.color || '#e5e7eb') + ';color:' + escapeHtml(flag.color_text || '#374151') + '">' +
                            escapeHtml(flag.label || '') + '</span>';
                    });
                    html += '<div>' + flagsHtml + '</div>';
                }
                html += '<div class="ci-ref">' + escapeHtml(it.reference || '') + ' · ' + it.quantity + ' ud.</div>';
                if (it.delivery_range_string) {
                    html += '<div class="ci-variant" style="color:#a1a1aa;font-size:10px">' + escapeHtml(it.delivery_range_string) + '</div>';
                }
                html += '</div>';
                html += '<div class="ci-price">' +
                    '<div class="final">' + fmtMoney(it.total_wt, sign) + '</div>';
                if (hasDisc) {
                    html += '<div class="original">' + fmtMoney(it.unit_price_original * it.quantity, sign) + '</div>' +
                        '<div class="saving">−' + escapeHtml(it.discount_to_display || '') + '</div>';
                }
                html += '</div></div>';
            });
        }

        if (cart.vouchers && cart.vouchers.length) {
            cart.vouchers.forEach(function (v) {
                html += '<div class="htk-cart-voucher"><i class="fas fa-tag"></i>' +
                    escapeHtml(v.name) + (v.code ? ' (' + escapeHtml(v.code) + ')' : '') +
                    ' · −' + fmtMoney(v.value, sign) +
                    '</div>';
            });
        }

        if (t.shipping !== undefined || t.shipping_label) {
            var shippingLabel = t.shipping_label || (parseFloat(t.shipping) === 0 ? 'Gratis' : fmtMoney(t.shipping, sign));
            html += '<div class="htk-cart-voucher" style="color:#52525b;justify-content:space-between">' +
                '<span><i class="fas fa-truck"></i> Envío</span>' +
                '<span>' + escapeHtml(shippingLabel) + '</span>' +
                '</div>';
        }

        if (cart.fitting_services && cart.fitting_services.length) {
            html += '<div class="htk-fitting-block">' +
                '<div class="htk-fitting-title"><i class="fas fa-calendar-check"></i> Servicios de fitting</div>';
            cart.fitting_services.forEach(function (fs) {
                html += '<div class="htk-fitting-item">' +
                    '<div class="fi-name">' + escapeHtml(fs.name || '') + '</div>' +
                    '<div class="fi-detail">' +
                    '<i class="fas fa-calendar"></i> ' + escapeHtml(fs.day || '') +
                    ' · <i class="fas fa-clock"></i> ' + escapeHtml(fs.hour || '') +
                    ' · <i class="fas fa-location-dot"></i> ' + escapeHtml(fs.location || '') +
                    '</div>' +
                    '</div>';
            });
            html += '</div>';
        }

        html += '</div>';
        return html;
    }

    function renderPrestashopContext(resp) {
        var $loading = $('#htk-cnt-ps-loading');
        var $empty   = $('#htk-cnt-ps-empty');
        var $data    = $('#htk-cnt-ps-data');

        $loading.hide();

        if (!resp || !resp.customer || !resp.customer.found) {
            $empty.show();
            return;
        }

        var c    = resp.customer;
        var sign = ((resp.orders && resp.orders[0]) || (resp.carts && resp.carts[0]) || {}).currency_sign || '€';
        $('#htk-cnt-ps-ltv').text(c.ltv ? parseFloat(c.ltv).toFixed(2) + ' ' + sign : '—');
        $('#htk-cnt-ps-orders-count').text(c.orders_count || 0);
        $('#htk-cnt-ps-last-order').text(c.last_order_at ? c.last_order_at.substring(0, 10) : '—');
        $('#htk-cnt-ps-score').show();

        var ordersHtml = '';
        if (resp.orders && resp.orders.length) {
            resp.orders.forEach(function (o, i) {
                ordersHtml += buildOrderCard(o, i);
            });
        } else {
            ordersHtml = '<div style="font-size:12px;color:#71717a;padding:8px 0">Sin pedidos registrados.</div>';
        }
        $('#htk-cnt-ps-orders-list').html(ordersHtml);

        $(document).off('click.ordercard').on('click.ordercard', '.htk-order-head', function () {
            var $head = $(this);
            var $body = $('#' + $head.data('target'));
            var open  = $head.hasClass('open');
            $head.toggleClass('open', !open);
            if (open) { $body.slideUp(150); } else { $body.slideDown(150); }
        });

        if (resp.carts && resp.carts.length) {
            var cartsHtml = '';
            resp.carts.forEach(function (cart) {
                cartsHtml += buildCartCard(cart);
            });
            $('#htk-cnt-ps-carts-list').html(cartsHtml);
            $('#htk-cnt-ps-carts-block').show();
        }

        $data.show();
    }

    function loadPrestashopContext(email) {
        var $loading = $('#htk-cnt-ps-loading');
        var $empty   = $('#htk-cnt-ps-empty');
        var $data    = $('#htk-cnt-ps-data');
        $loading.show(); $empty.hide(); $data.hide();

        var cached = lsGet(email, 'ps');
        if (cached) { renderPrestashopContext(cached); }

        // [10] IDB cache para PS
        IdbCache.get('ps_' + email).then(function (idbData) {
            if (idbData && !cached) { renderPrestashopContext(idbData); }
        });

        return $.get('/api/helpdeskprestashop/customers/' + encodeURIComponent(email) + '/context')
            .done(function (resp) {
                lsPut(email, 'ps', resp);
                IdbCache.put('ps_' + email, resp);
                renderPrestashopContext(resp);
            })
            .fail(function () { $loading.hide(); $empty.show(); });
    }

    // ═══════════ [12] Dev debug panel ═══════════
    function initDebugPanel() {
        // Activar con localStorage.setItem('htk-debug', '1') o meta app-env=local
        var isLocal = document.querySelector('meta[name="app-env"][content="local"]') !== null
            || localStorage.getItem('htk-debug') === '1';
        if (!isLocal) { return; }

        var $panel = $('<div id="htk-debug-panel"></div>').appendTo('body');
        var stats = { hits: 0, misses: 0, requests: 0, totalMs: 0 };

        function render() {
            var avg = stats.requests > 0 ? Math.round(stats.totalMs / stats.requests) : 0;
            $panel.html(
                '<strong>HTK Debug</strong><br>' +
                'Requests: ' + stats.requests + '<br>' +
                'Cache hits (~&lt;50ms): ' + stats.hits + '<br>' +
                'Misses: ' + stats.misses + '<br>' +
                'Avg: ' + avg + 'ms'
            );
        }

        $(document).ajaxSend(function (e, jqxhr, settings) {
            if (!/\/api\/helpdesk/.test(settings.url)) { return; }
            settings._startedAt = Date.now();
            stats.requests++;
        });

        $(document).ajaxComplete(function (e, jqxhr, settings) {
            if (!/\/api\/helpdesk/.test(settings.url)) { return; }
            var ms = Date.now() - (settings._startedAt || Date.now());
            stats.totalMs += ms;
            if (ms < 50) { stats.hits++; } else { stats.misses++; }
            render();
        });

        render();
    }

    $(document).ready(bootstrap);

})(window.jQuery);
