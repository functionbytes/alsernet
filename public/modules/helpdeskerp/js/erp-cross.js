/*!
 * HelpdeskErp · extensión "cross": pane "Actividad" de la ficha de cliente
 * de Gestión (window.ErpCustomerWorkspace.registerPane).
 *
 * Línea de tiempo única del cliente: pedidos, albaranes, devoluciones/abonos,
 * facturas y movimientos de puntos de Gestión, pedidos y carritos de la
 * tienda y conversaciones del helpdesk. Agrupada por día, con un icono por
 * tipo y filtros por tipo (chips).
 *
 * Datos: GET /panel/helpdesk/customers/{id}/erp/timeline (manager.helpdesk.
 * erp.cross.timeline). Cada fuente llega con su estado: bloqueada (falta el
 * GRANT en Oracle), buscando (pedidos), sin conexión o sin permiso.
 *
 * Cada elemento abre su detalle sin apilar modales:
 *   - pedido de Gestión → [data-erp-order-open] (erp-chat.js; la ficha se cierra)
 *   - pedido de la tienda → cierra la ficha y window.openPsOrderWorkspace(id)
 *   - albarán / factura / resto → hoja .erc-sheet dentro de la ficha (ErpChat.sheet)
 *   - conversación → nueva pestaña
 *
 * Se carga ANTES que erp-customer.js (orden alfabético de modals/parts):
 * el pane se registra al terminar de cargar la página.
 * Fuente: modules/HelpdeskErp/public/js/ — copiar a public/modules/helpdeskerp/js/ tras editar.
 */
(function ($) {
    'use strict';

    if (!$ || window.__erpCrossLoaded) { return; }
    window.__erpCrossLoaded = true;

    var KEY = 'activity';
    var CW = 'erp-customer-workspace';
    var CW_SEL = '[data-bv-modal-name="' + CW + '"]';
    var RETRY = 'erx-timeline';

    var TYPES = {
        erp_order: { label: 'Pedidos', icon: 'fas fa-clipboard-list', src: 'erp' },
        erp_delivery_note: { label: 'Albaranes', icon: 'fas fa-file-lines', src: 'erp' },
        erp_return: { label: 'Devoluciones', icon: 'fas fa-rotate-left', src: 'erp' },
        erp_invoice: { label: 'Facturas', icon: 'fas fa-file-invoice', src: 'erp' },
        erp_points: { label: 'Puntos', icon: 'fas fa-star', src: 'erp' },
        ps_order: { label: 'Tienda', icon: 'fas fa-bag-shopping', src: 'ps' },
        ps_cart: { label: 'Carritos', icon: 'fas fa-cart-shopping', src: 'ps' },
        conversation: { label: 'Conversaciones', icon: 'far fa-comments', src: 'hd' },
    };
    var ORDER = ['erp_order', 'erp_delivery_note', 'erp_return', 'erp_invoice', 'erp_points', 'ps_order', 'ps_cart', 'conversation'];

    var SOURCES = {
        erp_orders: 'Pedidos de Gestión',
        erp_delivery_notes: 'Albaranes',
        erp_returns: 'Devoluciones',
        erp_invoices: 'Facturas',
        erp_points: 'Puntos',
        ps_orders: 'Pedidos de la tienda',
        ps_carts: 'Carritos',
        conversations: 'Conversaciones',
    };

    var filter = 'all';
    var items = [];          // items del último render (índice = data-erx-i)
    var itemsCid = null;

    function E() { return window.ErpChat || null; }
    function esc(s) { return E().esc(s); }
    function escAttr(s) { return E().escAttr(s); }
    function blank(v) { return v == null || (typeof v === 'string' && v.trim() === ''); }

    /* ── Fechas ────────────────────────────────────────────────────── */

    function dayLabel(day) {
        var ec = E();
        var d = ec.parseDate(day);
        if (!d) { return day; }
        var now = new Date();
        var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        var diff = Math.round((today - new Date(d.getFullYear(), d.getMonth(), d.getDate())) / 86400000);
        if (diff === 0) { return 'Hoy'; }
        if (diff === 1) { return 'Ayer'; }
        return ec.date(day, d.getFullYear() !== now.getFullYear() || diff > 300);
    }

    function timeOf(date) {
        var m = /\s(\d{2}):(\d{2})/.exec(String(date || ''));
        if (!m || (m[1] === '00' && m[2] === '00')) { return ''; }
        return m[1] + ':' + m[2];
    }

    /* ── Render ────────────────────────────────────────────────────── */

    function head(resp) {
        var at = resp && resp.fetched_at ? 'Actualizado ' + E().relative(resp.fetched_at) : '';
        return '<div class="erc-cw-pane-hd"><div class="tt"><span class="t">Actividad</span>' +
            '<span class="s">Gestión, tienda y conversaciones en una sola línea</span></div>' +
            (at ? '<span class="at">' + esc(at) + '</span>' : '') + '</div>';
    }

    function sourceNotes(sources) {
        var out = [];
        Object.keys(SOURCES).forEach(function (k) {
            var s = sources && sources[k];
            if (!s || s.state === 'ok' || s.state === 'forbidden') { return; }
            var label = SOURCES[k];
            var kind, txt;
            if (s.state === 'blocked') { kind = 'lock'; txt = 'Pendiente de permiso en Oracle'; }
            else if (s.state === 'down') { kind = 'lock'; txt = s.message || 'Sin conexión'; }
            else if (s.state === 'loading') { kind = 'wait'; txt = s.message || 'Buscando en Gestión…'; }
            else if (s.state === 'unlinked') { kind = 'mute'; txt = s.message || 'Sin vínculo'; }
            else { kind = 'mute'; txt = s.message || 'No disponible'; }
            out.push({ kind: kind, html: '<span class="erx-src erx-src--' + kind + '"><b>' + esc(label) + '</b> · ' + esc(txt) + '</span>', down: s.state === 'down' });
        });

        // Las fuentes sin vínculo/no disponibles repiten lo mismo: se agrupan.
        var muted = out.filter(function (o) { return o.kind === 'mute'; });
        var rest = out.filter(function (o) { return o.kind !== 'mute'; });
        if (!rest.length && !muted.length) { return ''; }
        var anyDown = rest.some(function (o) { return o.down; });

        return '<div class="erx-srcs">' +
            rest.map(function (o) { return o.html; }).join('') +
            muted.map(function (o) { return o.html; }).join('') +
            (anyDown ? '<button type="button" class="erc-btn erc-btn--outline erc-btn--sm" data-erp-retry="' + RETRY + '">Reintentar</button>' : '') +
        '</div>';
    }

    function chips(counts, total) {
        var out = '<button type="button" class="erc-chip' + (filter === 'all' ? ' is-on' : '') + '" data-erx-type="all">Todo<span class="n">' + esc(total) + '</span></button>';
        ORDER.forEach(function (t) {
            var n = counts[t] || 0;
            if (!n) { return; }
            out += '<button type="button" class="erc-chip' + (filter === t ? ' is-on' : '') + '" data-erx-type="' + escAttr(t) + '">' +
                esc(TYPES[t].label) + '<span class="n">' + esc(n) + '</span></button>';
        });
        return '<div class="erc-chips erx-chips" role="group" aria-label="Filtrar por tipo">' + out + '</div>';
    }

    function endHtml(it) {
        var ec = E();
        var out = '';
        if (it.points != null && it.type === 'erp_points') {
            var p = parseInt(it.points, 10);
            out += '<span class="amt ' + (p >= 0 ? 'is-good' : 'is-muted') + '">' + (p >= 0 ? '+' : '−') + esc(Math.abs(p)) + ' pts</span>';
        } else if (it.amount != null) {
            var a = ec.num(it.amount);
            out += '<span class="amt' + (a !== null && a < 0 ? ' is-muted' : '') + '">' + esc(ec.money(it.amount)) + '</span>';
        }
        if (it.status && (it.type === 'erp_order' || it.type === 'ps_order')) {
            out += ec.render.statusPill(it.status, it.status_description);
        } else if (it.status && it.type === 'conversation') {
            out += '<span class="erc-tag erc-tag--' + (it.status === 'Abierta' ? 'progress' : 'closed') + '">' + esc(it.status) + '</span>';
        }
        return out ? '<span class="end">' + out + '</span>' : '';
    }

    function itemHtml(it, i) {
        var t = TYPES[it.type] || { label: '', icon: 'fas fa-circle', src: 'hd' };
        var open = it.open || { kind: 'info' };
        var time = timeOf(it.date);
        // El backend manda fechas ISO dentro del subtítulo («Servido el 2025-11-11»): se leen en español.
        var subtitle = blank(it.subtitle) ? it.subtitle : String(it.subtitle).replace(/\b(\d{4}-\d{2}-\d{2})\b/g, function (d) {
            var ec = window.ErpChat; return ec && ec.date ? ec.date(d, true) : d;
        });
        var sub = [subtitle, time].filter(function (v) { return !blank(v); }).join(' · ');
        var inner =
            '<span class="ic erx-ic erx-ic--' + escAttr(t.src) + '"><i class="' + escAttr(t.icon) + '"></i></span>' +
            '<span class="info">' +
                '<span class="t">' + esc(it.title) + '</span>' +
                (sub ? '<span class="m">' + esc(sub) + '</span>' : '') +
            '</span>' + endHtml(it);
        var cls = 'erc-item is-link erx-item';
        var data = ' data-erx-i="' + i + '" data-erx-kind="' + escAttr(it.type) + '"';

        // Solo enlaces http(s) o rutas del propio panel.
        if (open.kind === 'url' && !/^(https?:\/\/|\/(?!\/))/i.test(String(open.url || ''))) {
            open = { kind: 'info' };
        }
        if (open.kind === 'url' && open.url) {
            return '<a class="' + cls + '"' + data + ' href="' + escAttr(open.url) + '" target="_blank" rel="noopener noreferrer">' + inner + '</a>';
        }
        if (open.kind === 'erp_order' && open.id) {
            return '<button type="button" class="' + cls + '"' + data + ' data-erp-order-open="' + escAttr(open.id) + '">' + inner + '</button>';
        }
        return '<button type="button" class="' + cls + '"' + data + ' data-erx-open>' + inner + '</button>';
    }

    function listHtml(list) {
        if (!list.length) {
            return E().stateHtml({ state: 'empty', icon: 'fas fa-clock-rotate-left', message: 'Sin actividad registrada para este cliente.' }, 'Sin actividad');
        }
        var html = '';
        var day = null;
        list.forEach(function (it, i) {
            if (it.day !== day) {
                if (day !== null) { html += '</div></div>'; }
                day = it.day;
                html += '<div class="erx-day" data-erx-day="' + escAttr(day) + '">' +
                    '<div class="erx-day-h"><span>' + esc(dayLabel(day)) + '</span></div><div class="erc-list">';
            }
            html += itemHtml(it, i);
        });
        return html + '</div></div>';
    }

    function applyFilter($root) {
        var $r = $root && $root.length ? $root : $('#ercCwPane');
        $r.find('.erx-chips .erc-chip').removeClass('is-on').filter('[data-erx-type="' + filter + '"]').addClass('is-on');
        $r.find('.erx-item').each(function () {
            var on = filter === 'all' || $(this).attr('data-erx-kind') === filter;
            $(this).toggleClass('erc-hidden', !on);
        });
        $r.find('.erx-day').each(function () {
            $(this).toggleClass('erc-hidden', !$(this).find('.erx-item:not(.erc-hidden)').length);
        });
        var visible = $r.find('.erx-item:not(.erc-hidden)').length;
        $r.find('.erx-filter-empty').toggleClass('erc-hidden', visible > 0 || !$r.find('.erx-item').length);
    }

    function paneHtml(resp) {
        var ec = E();
        if (!resp || resp.success === false || resp.state !== 'ok' || !resp.data) {
            return head(null) + ec.stateHtml($.extend({}, resp || { state: 'down' }, { retry: RETRY }), 'Actividad');
        }
        var d = resp.data;
        items = Array.isArray(d.items) ? d.items : [];
        var counts = d.counts || {};
        if (filter !== 'all' && !counts[filter]) { filter = 'all'; }

        return head(resp) +
            '<div class="erx-wrap">' +
                sourceNotes(d.sources) +
                (items.length ? chips(counts, items.length) : '') +
                '<div class="erx-list">' + listHtml(items) + '</div>' +
                '<div class="erx-filter-empty erc-hidden">' +
                    ec.stateHtml({ state: 'empty', icon: 'fas fa-filter', message: 'Nada de este tipo en la actividad del cliente.' }, 'Sin resultados') +
                '</div>' +
                (d.truncated ? '<div class="erc-note erc-note--info"><span class="txt">Se muestran los ' + esc(items.length) + ' movimientos más recientes.</span></div>' : '') +
            '</div>';
    }

    function render(ctx, force) {
        var ec = E();
        itemsCid = ctx && ctx.customerId ? String(ctx.customerId) : ec.customerId();
        var base = ec.base(itemsCid);
        return ec.request(base ? base + '/timeline' : null, force ? { force: 1 } : {}, 60000).then(function (resp) {
            var html = paneHtml(resp);
            // El pane se pinta después de resolver: se aplica el filtro al siguiente tick.
            setTimeout(function () { applyFilter($('#ercCwPane')); }, 0);
            return html;
        });
    }

    /* ── Detalle en hoja ───────────────────────────────────────────── */

    function $cwBody() { return $(CW_SEL).find('.bv-modal-body').first(); }

    function kvList(meta) {
        var keys = meta ? Object.keys(meta) : [];
        if (!keys.length) { return ''; }
        return '<div class="erc-kv">' + keys.map(function (k) {
            return E().render.kv(k, blank(meta[k]) ? null : String(meta[k]), /^(Id|Nº|Número|Albarán|Factura|Pedido|Tarjeta|Liquidación|Carrito)/.test(k));
        }).join('') + '</div>';
    }

    function backBtn() { return '<button type="button" class="btn-secondary w-100" data-erc-sheet-close>Volver a la actividad</button>'; }

    function openDeliveryNote(it, id, number) {
        var ec = E();
        var $sheet = ec.sheet.open($cwBody(), {
            id: 'erx-dn', icon: 'fas fa-file-lines', label: 'Actividad',
            title: 'Albarán ' + (number || ''), html: ec.skeleton(3), foot: backBtn(),
        });
        if (!ec.can('orders') && !ec.can('finance')) {
            ec.sheet.update($sheet, { html: ec.stateHtml({ state: 'forbidden' }, 'Albarán') });
            return;
        }
        ec.deliveryNote(id).then(function (resp) {
            if (!$sheet.closest('body').length) { return; }
            if (!resp || resp.state !== 'ok' || !resp.data) {
                ec.sheet.update($sheet, { html: ec.stateHtml($.extend({}, resp || {}, { retry: 'erx-dn:' + id }), 'Albarán') });
                return;
            }
            var d = resp.data;
            var foot = '';
            if (it && it.type === 'erp_return' && it.order_id && ec.can('orders')) {
                foot += '<button type="button" class="btn-primary w-100" data-erp-order-open="' + escAttr(it.order_id) + '">Ver pedido</button>';
            }
            ec.sheet.update($sheet, { title: 'Albarán ' + (d.number || number || ''), html: ec.render.deliveryNote(d), foot: foot + backBtn() });
        });
    }

    function openInvoice(id) {
        var ec = E();
        var $sheet = ec.sheet.open($cwBody(), {
            id: 'erx-inv', icon: 'fas fa-file-invoice', label: 'Actividad', title: 'Factura', html: ec.skeleton(3), foot: backBtn(),
        });
        if (!ec.can('finance')) {
            ec.sheet.update($sheet, { html: ec.stateHtml({ state: 'forbidden' }, 'Factura') });
            return;
        }
        ec.invoice(id).then(function (resp) {
            if (!$sheet.closest('body').length) { return; }
            if (!resp || resp.state !== 'ok' || !resp.data) {
                ec.sheet.update($sheet, { html: ec.stateHtml($.extend({}, resp || {}, { retry: 'erx-inv:' + id }), 'Factura') });
                return;
            }
            var d = resp.data;
            var ref = [d.series, d.number].filter(function (v) { return !blank(v); }).join('-') + (d.year ? '/' + d.year : '');
            ec.sheet.update($sheet, { title: 'Factura ' + (ref || id), html: ec.render.invoice(d) });
        });
    }

    function openInfo(it) {
        var ec = E();
        var t = TYPES[it.type] || { label: '', icon: 'fas fa-circle' };
        var body = '<div class="erc-stack">' +
            '<div class="erc-card"><div class="erc-card-body">' +
                '<div class="erx-sheet-top"><span class="t">' + esc(it.title) + '</span>' +
                    '<span class="m">' + esc(ec.dateTime(it.date)) + '</span></div>' +
                (it.amount != null ? '<div class="erc-row"><span class="k">Importe</span><span class="v mono">' + esc(ec.money(it.amount)) + '</span></div>' : '') +
                kvList(it.meta) +
            '</div></div></div>';
        var foot = '';
        if (it.order_id && ec.can('orders')) {
            foot += '<button type="button" class="btn-primary w-100" data-erp-order-open="' + escAttr(it.order_id) + '">Ver pedido</button>';
        }
        ec.sheet.open($cwBody(), { id: 'erx-info', icon: t.icon, label: 'Actividad', title: t.label || 'Detalle', html: body, foot: foot + backBtn() });
    }

    function openPsOrder(id) {
        var ec = E();
        if (typeof window.openPsOrderWorkspace !== 'function') {
            ec.toast('warning', 'La tienda no está disponible en este panel.');
            return;
        }
        // Nunca un modal sobre otro: primero se cierra la ficha de Gestión.
        if (window.ErpCustomerWorkspace && typeof window.ErpCustomerWorkspace.close === 'function') { window.ErpCustomerWorkspace.close(); }
        window.openPsOrderWorkspace(id);
    }

    function openItem(it) {
        var o = it.open || { kind: 'info' };
        if (o.kind === 'delivery_note' && o.id) { openDeliveryNote(it, String(o.id), o.number || ''); return; }
        if (o.kind === 'invoice' && o.id) { openInvoice(String(o.id)); return; }
        if (o.kind === 'ps_order' && o.id) { openPsOrder(String(o.id)); return; }
        openInfo(it);
    }

    /* ── Handlers ──────────────────────────────────────────────────── */

    $(document).on('click', CW_SEL + ' .erx-chips [data-erx-type]', function (e) {
        e.preventDefault();
        filter = String($(this).attr('data-erx-type') || 'all');
        applyFilter($('#ercCwPane'));
    });

    $(document).on('click', CW_SEL + ' .erx-item[data-erx-open]', function (e) {
        e.preventDefault();
        var ec = E();
        if (!ec || String(ec.customerId()) !== String(itemsCid)) { return; }
        var it = items[parseInt($(this).attr('data-erx-i'), 10)];
        if (it) { openItem(it); }
    });

    $(document).on('erp:retry', function (e, target) {
        var t = String(target || '');
        var W = window.ErpCustomerWorkspace;
        if (!W || !$(CW_SEL).hasClass('on')) { return; }
        if (t === RETRY && W.current() === KEY) { W.refresh(); }
        else if (t.indexOf('erx-dn:') === 0) { openDeliveryNote(null, t.substring(7), ''); }
        else if (t.indexOf('erx-inv:') === 0) { openInvoice(t.substring(8)); }
    });

    // Los pedidos de Gestión terminan de escanearse: si la actividad está a
    // la vista, se vuelve a pedir (los pedidos entraban como "buscando").
    $(document).on('erp:orders-ready', function (e, resp, id) {
        var W = window.ErpCustomerWorkspace;
        if (!W || !$(CW_SEL).hasClass('on') || W.current() !== KEY || String(id) !== String(itemsCid)) { return; }
        W.refresh();
    });

    /* ── Registro del pane ─────────────────────────────────────────── */

    var tries = 0;
    function register() {
        var W = window.ErpCustomerWorkspace;
        if (!W || typeof W.registerPane !== 'function' || !E()) {
            if (++tries < 40) { setTimeout(register, 250); }
            return;
        }
        W.registerPane({
            key: KEY,
            group: 'Cliente',
            icon: 'fas fa-clock-rotate-left',
            title: 'Actividad',
            sub: 'Pedidos, albaranes y conversaciones',
            perm: 'view',
            render: render,
        });
    }

    $(register);
})(window.jQuery);
