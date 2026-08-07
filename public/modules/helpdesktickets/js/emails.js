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
            search: '',
            category: '',
            agent: '',
            from: '',
            to: '',
            selected: null,
            selectedTicketId: null,
            bulk: new Set(),
        };

        EML.urls = {
            index: $data.data('indexUrl'),
            store: $data.data('storeUrl'),
            bulk: $data.data('bulkUrl'),
            export: $data.data('exportUrl'),
        };

        populateComposeCategories();
        bindEvents();
        renderTabs();
        renderList();
        renderBulkBar();
        $('#eml-tab-scheduled-count').text(EML.state.stats.scheduled || '');
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
                from: EML.state.from || undefined,
                to: EML.state.to || undefined,
            },
        }).done(function (resp) {
            EML.state.mails = resp.data || [];
            EML.state.bulk.clear();
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
        $('#eml-kpi-scheduled').text(stats.scheduled);
        $('#eml-tab-scheduled-count').text(stats.scheduled || '');
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

    function renderList() {
        var $list = $('#eml-list');
        var rows = EML.state.mails;

        $('#eml-count').text(rows.length + ' resultado' + (rows.length !== 1 ? 's' : ''));

        if (!rows.length) {
            $list.html('<div class="eml-empty-state"><div style="font-size:13px;color:var(--eml-text-soft)">Sin resultados en este filtro.</div></div>');
            return;
        }

        $list.html(rows.map(function (m) {
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
        }).join(''));
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
        }).fail(function () {
            if (window.toastr) { toastr.error('No se pudo cargar el email.'); }
        });
    }

    function renderDetail(d) {
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
                    '<div class="eml-detail-tab" data-eml-dtab="files">Archivos <span class="eml-mono">' + (d.attachments ? d.attachments.length : 0) + '</span></div>' +
                '</div>' +
            '</div>' +
            '<div class="eml-detail-body" data-eml-dpane="msg">' + renderMessagePane(d) + '</div>' +
            '<div class="eml-detail-body" data-eml-dpane="thread" style="display:none">' + renderThreadPane(d) + '</div>' +
            '<div class="eml-detail-body" data-eml-dpane="files" style="display:none">' + renderFilesPane(d) + '</div>';

        $('#eml-detail').html(html);
        renderSidePanel(d);
    }

    function renderMessagePane(d) {
        return '' +
            '<div class="eml-headers-grid">' +
                '<span>De</span><span class="eml-mono">' + escapeHtml(d.from || '') + '</span>' +
                '<span>Para</span><span class="eml-mono">' + escapeHtml(d.to || '') + '</span>' +
                (d.cc ? '<span>CC</span><span class="eml-mono">' + escapeHtml(d.cc) + '</span>' : '') +
                (d.message_id ? '<span>Message-ID</span><span class="eml-mono" style="font-size:10px">' + escapeHtml(d.message_id) + '</span>' : '') +
            '</div>' +
            '<div class="eml-body-frame">' +
                '<div class="eml-body-frame-head">Cuerpo del mensaje</div>' +
                '<div class="eml-body-frame-content">' + (d.body_html || '<em>Sin contenido</em>') + '</div>' +
            '</div>';
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

    function renderSidePanel(d) {
        var t = d.ticket;
        if (!t) {
            $('#eml-side-panel').html('<div style="font-size:12px;color:var(--eml-text-muted)">Sin ticket asociado.</div>');
            return;
        }

        $('#eml-side-panel').html(
            '<div class="eml-side-block">' +
                '<div class="eml-side-title">Cliente</div>' +
                '<div style="font-size:13px;font-weight:700">' + escapeHtml(t.customer ? t.customer.name : '—') + '</div>' +
                (t.customer ? '<div style="font-size:11.5px;color:var(--eml-text-soft)">' + escapeHtml(t.customer.email || '') + '</div>' : '') +
            '</div>' +
            '<div class="eml-side-block">' +
                '<div class="eml-side-title">Ticket</div>' +
                '<div class="eml-side-kv">' +
                    '<span>Número</span><span>' + escapeHtml(t.ticket_number || '—') + '</span>' +
                    '<span>Estado</span><span>' + escapeHtml(t.status || '—') + '</span>' +
                    '<span>Asignado</span><span>' + escapeHtml(t.assignee || '—') + '</span>' +
                '</div>' +
                (t.url_full ? '<a href="' + escapeHtml(t.url_full) + '" class="eml-btn eml-btn-outline" style="margin-top:6px"><i class="fa-solid fa-arrow-up-right-from-square"></i> Ver ficha completa</a>' : '') +
            '</div>'
        );
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
        $(document).on('change', '#eml-filter-from', function () { EML.state.from = $(this).val(); refetch(); });
        $(document).on('change', '#eml-filter-to', function () { EML.state.to = $(this).val(); refetch(); });

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
        $(document).on('click', '#eml-detail-resend', function () {
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

        $(document).on('click', '[data-eml-dtab]', function () {
            var tab = $(this).data('emlDtab');
            $('.eml-detail-tab').removeClass('on');
            $(this).addClass('on');
            $('[data-eml-dpane]').hide();
            $('[data-eml-dpane="' + tab + '"]').show();
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
