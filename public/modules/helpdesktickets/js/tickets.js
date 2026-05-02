/**
 * HelpdeskTickets · Manager Tickets Page
 * jQuery + AJAX (no Livewire/Alpine/React)
 *
 * Datos iniciales se leen desde #htk-data (atributo data-tickets, JSON).
 * Los partials se renderizan en cliente para soportar:
 *   - filter pills (open / urgent / mine / unassigned / pending / resolved / all)
 *   - vista lista <-> kanban
 *   - bulk select + bulk actions
 *   - detalle lateral (modal) con tabs
 *   - popover de contacto con tabs
 */
(function ($) {
    'use strict';

    if (!$ || !$.fn) {
        return;
    }

    var HTK = window.HelpdeskTickets = window.HelpdeskTickets || {};

    HTK.state = {
        view: 'list',
        filter: 'open',
        selected: null,
        bulk: new Set(),
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
        if (!n) return '—';
        return n.split(' ').filter(Boolean).slice(0, 2).map(function (s) { return s[0]; }).join('').toUpperCase();
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function colorFor(id) {
        if (!id && id !== 0) return 1;
        return ((parseInt(id, 10) % 8) + 1);
    }

    function timeAgo(iso) {
        if (!iso) return '—';
        var d = new Date(iso);
        var diff = (Date.now() - d.getTime()) / 1000;
        if (diff < 60) return 'hace ' + Math.floor(diff) + 's';
        if (diff < 3600) return 'hace ' + Math.floor(diff / 60) + ' min';
        if (diff < 86400) return 'hace ' + Math.floor(diff / 3600) + ' h';
        if (diff < 604800) return 'hace ' + Math.floor(diff / 86400) + ' d';
        return d.toLocaleDateString();
    }

    // ═══════════ Filtering ═══════════
    function passesFilter(t) {
        var f = HTK.state.filter;
        if (f === 'all') return true;
        if (f === 'mine') return t.assignee && t.assignee.id == HTK.state.currentUserId;
        if (f === 'unassigned') return !t.assignee;
        if (f === 'urgent') return t.priority === 'urgent' || t.sla_kind === 'breach';
        if (f === 'pending') return t.status_slug === 'pending';
        if (f === 'resolved') return t.status_slug === 'resolved';
        if (f === 'open') return t.status_slug === 'open' || t.status_slug === 'progress';
        return t.status_slug === f;
    }

    function recountFilters() {
        var counts = {open: 0, urgent: 0, mine: 0, unassigned: 0, pending: 0, resolved: 0, all: HTK.state.tickets.length};
        HTK.state.tickets.forEach(function (t) {
            if (t.status_slug === 'open' || t.status_slug === 'progress') counts.open++;
            if (t.priority === 'urgent' || t.sla_kind === 'breach') counts.urgent++;
            if (t.assignee && t.assignee.id == HTK.state.currentUserId) counts.mine++;
            if (!t.assignee) counts.unassigned++;
            if (t.status_slug === 'pending') counts.pending++;
            if (t.status_slug === 'resolved') counts.resolved++;
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

            var $tr = $(
                '<tr data-id="' + t.id + '" class="' + (isActive ? 'active' : '') + '">' +
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
                '<td style="width:110px"><span class="htk-sla ' + slaKind + '"><i class="far fa-clock"></i>' + escapeHtml(slaTxt) + '</span></td>' +
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
    }

    // ═══════════ Detalle ═══════════
    function openDetail(id) {
        var t = HTK.state.tickets.find(function (x) { return x.id == id; });
        if (!t) return;
        HTK.state.selected = id;

        var channel = t.source || 'email';

        $('#htkd-id').text(t.ticket_number);
        $('#htkd-channel').html('<span class="htk-tag-ch ' + channel + '"><span class="htk-ch-dot ' + channel + '"></span>' + chLabel(channel) + '</span>');
        $('#htkd-status').html('<span class="htk-badge ' + (t.status_slug || 'open') + '">' + escapeHtml(t.status_name || stLabel(t.status_slug)) + '</span>');
        $('#htkd-priority').html('<span class="htk-prio ' + t.priority + '"><span class="d"></span>' + prLabel(t.priority) + '</span>');
        $('#htkd-sla').html('<span class="htk-sla ' + (t.sla_kind || 'ok') + '"><i class="far fa-clock"></i>' + escapeHtml(t.sla_text || '—') + '</span>');
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

        // Cargar conversación via AJAX al endpoint de detalle del ticket
        loadConversation(t);

        renderActivity(t);

        $('#htkd-detail-link').attr('href', t.url || '#');

        // mostrar modal
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
        if (!t || !t.customer) return;
        var c = t.customer;
        var color = colorFor(c.id);
        $('#htk-cnt-av').attr('class', 'htk-av c' + color).text(initials(c.name));
        $('#htk-cnt-name').text(c.name);
        $('#htk-cnt-email').text(c.email || '—');
        $('#htk-contact-pop').addClass('show');

        // tickets relacionados (el cliente puede tener varios)
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

    // ═══════════ Reply ═══════════
    function sendReply() {
        if (!HTK.state.selected) return;
        var t = HTK.state.tickets.find(function (x) { return x.id == HTK.state.selected; });
        if (!t || !t.url_message_store) return;

        var $textarea = $('#htkd-reply-text');
        var body = $.trim($textarea.val());
        if (!body) {
            if (window.toastr) toastr.warning('Escribe un mensaje antes de enviar');
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
                if (window.toastr) toastr.success(resp.message || 'Mensaje enviado');
                loadConversation(t);
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo enviar el mensaje';
                if (window.toastr) toastr.error(msg);
            })
            .always(function () {
                $btn.prop('disabled', false);
            });
    }

    // ═══════════ Bulk actions ═══════════
    function bulkAction(action) {
        if (HTK.state.bulk.size === 0) return;
        var ids = Array.from(HTK.state.bulk);
        var url = $('#htk-data').data('bulkUrl');
        if (!url) return;

        $.ajax({
            url: url,
            method: 'POST',
            data: {action: action, ticket_ids: ids},
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
        })
            .done(function (resp) {
                if (window.toastr) toastr.success(resp.message || 'Acción ejecutada');
                HTK.state.bulk.clear();
                setTimeout(function () { window.location.reload(); }, 600);
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo ejecutar la acción';
                if (window.toastr) toastr.error(msg);
            });
    }

    // ═══════════ Filtros modal ═══════════
    function applyFiltersModal() {
        var form = $('#htk-filters-form').serialize();
        window.location.search = form;
    }

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
            if ($(e.target).closest('.htk-checkbox').length) return;
            openDetail($(this).data('id'));
        });

        $(document).on('change', '#htk-tbody .htk-checkbox', function () {
            var id = $(this).data('id');
            if ($(this).is(':checked')) {
                HTK.state.bulk.add(id);
            } else {
                HTK.state.bulk.delete(id);
            }
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
            if (HTK.state.selected) openContact(HTK.state.selected);
        });
        $('#htk-cnt-close').on('click', closeContact);
        $(document).on('click', '.cnt-tab', function () {
            setContactTab($(this).data('cntTab'));
        });

        $(document).on('click', '.htk-reply-tab', function () {
            $('.htk-reply-tab').removeClass('on');
            $(this).addClass('on');
        });
        $('#htkd-reply-send').on('click', sendReply);

        $('.htk-bulk-action').on('click', function () {
            bulkAction($(this).data('action'));
        });

        $('#htk-apply-filters').on('click', applyFiltersModal);

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                if ($('#htk-contact-pop').hasClass('show')) {
                    closeContact();
                } else if ($('#htkd').hasClass('show')) {
                    closeDetail();
                }
            }
        });
    }

    function bootstrap() {
        var $data = $('#htk-data');
        if ($data.length === 0) return;

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
    }

    $(bootstrap);
})(window.jQuery);
