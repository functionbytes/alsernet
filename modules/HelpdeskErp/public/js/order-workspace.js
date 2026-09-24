/*!
 * HelpdeskErp · workspace de pedido de Gestión (modal "erp-order-workspace").
 *
 * SOLO LECTURA. Mismo esqueleto que el workspace de pedido de PrestaShop:
 * líneas + totales en la columna principal y pestañas laterales Info, Envío,
 * Pagos, Historial y Cliente. Albarán y factura se abren como hoja interna
 * (ErpChat.sheet) dentro del cuerpo del modal: nunca un modal sobre otro.
 *
 * Datos: ErpChat.orderDetail(orderId) → {order, history, shipping,
 * delivery_notes} por la ruta manager.helpdesk.erp.chat.order (usa el id del
 * cliente del HELPDESK). Si esa ruta no responde (sin conexión, sin cliente en
 * el panel) se usa como respaldo la ruta vieja /panel/helpdesk/erp/orders/
 * {erpId}/{orderId}, que devuelve solo el pedido.
 *
 * API pública (la usan erp-inbox.js y ErpChat.openOrder):
 *   window.openErpOrderWorkspace(erpCustomerId, orderId)
 *     erpCustomerId = id del cliente en Gestión (solo para la ruta de respaldo)
 *     orderId       = id central del pedido (p. ej. 10102138690)
 * [data-erp-order-open] lo resuelve erp-chat.js (ErpChat.openOrder), que acaba
 * llamando a esta función: aquí no se duplica ese handler.
 *
 * Cruce con la tienda (extensión "cross"): al pintar un pedido se pregunta a
 * …/erp/orders/{id}/shop si tiene pedido de PrestaShop fiable (identificador
 * de origen confirmado por el bridge, o mismo día + importe único). Solo si
 * hay coincidencia, y el workspace de pedido de la tienda está cargado
 * (window.openPsOrderWorkspace), sale "Ver pedido en la tienda": cierra este
 * modal y abre el de PrestaShop (nunca un modal sobre otro).
 *
 * Se carga ANTES que erp-chat.js (orden de los @push): window.ErpChat se lee
 * siempre en tiempo de ejecución, nunca al cargar el fichero.
 *
 * Fuente: modules/HelpdeskErp/public/js/ — copiar a public/modules/helpdeskerp/js/ tras editar.
 */
(function ($) {
    'use strict';

    if (!$ || window.__erpOrderWorkspaceLoaded) { return; }
    window.__erpOrderWorkspaceLoaded = true;

    var MODAL = 'erp-order-workspace';
    var cache = {};        // "cid:orderId" -> bundle normalizado (solo estado ok)
    var cur = null;        // {erpId, orderId, cid, token, bundle}
    var token = 0;
    var addrLoaded = false;
    var shopCache = {};    // "cid:orderId" -> {ps_order_id, reference, matched_by} | null (sin coincidencia)

    function E() { return window.ErpChat || null; }
    function $modal() { return $('[data-bv-modal-name="' + MODAL + '"]'); }
    function $body() { return $('#erpowBody'); }

    function esc(s) {
        var ec = E();
        if (ec) { return ec.esc(s); }
        return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function escAttr(s) { var ec = E(); return ec ? ec.escAttr(s) : esc(s); }
    function blank(v) { return v == null || (typeof v === 'string' && v.trim() === ''); }
    function num(v) { var ec = E(); return ec ? ec.num(v) : (isFinite(parseFloat(v)) ? parseFloat(v) : null); }
    function money(v) { return E().money(v); }
    function fdate(v) { return blank(v) ? '—' : E().date(v, true); }
    function fdt(v) { return blank(v) ? '—' : E().dateTime(v); }
    function toast(kind, msg) { var ec = E(); if (ec) { ec.toast(kind, msg); } else if (window.toastr) { window.toastr[kind](msg); } }

    // {id, description} | string → texto
    function desc(v) {
        if (v == null) { return ''; }
        if (typeof v === 'object') { return String(v.description || v.name || v.id || ''); }
        return String(v);
    }

    function isOff(v) { return v === false || v === 0 || v === '0'; }

    /* ── Apertura / cierre del modal ───────────────────────────────── */

    function openModal() {
        // Nunca un modal encima de otro: se cierra cualquier otro abierto
        // (lista de pedidos, ficha, finanzas… abiertos por otra vía).
        $('.bv-modal.on').not($modal()).each(function () {
            var other = String($(this).attr('data-bv-modal-name') || '');
            if (other && window.HDCommerce && typeof window.HDCommerce.close === 'function') { window.HDCommerce.close(other); }
            else { $(this).removeClass('on'); }
        });
        if (window.HDCommerce && typeof window.HDCommerce.open === 'function') {
            window.HDCommerce.open(MODAL);
            return;
        }
        $modal().addClass('on');
        $('body').css('overflow', 'hidden');
        $(document).trigger('bv:modal:open', [MODAL]);
    }

    function resetView() {
        var ec = E();
        if (ec) { ec.sheet.close($body()); }
        $('#erpowTabs .bv-po-tab').removeClass('on').attr('aria-selected', 'false')
            .filter('[data-erow-tab="info"]').addClass('on').attr('aria-selected', 'true');
        $modal().find('[data-erow-panel]').addClass('erc-hidden').filter('[data-erow-panel="info"]').removeClass('erc-hidden');
        $('#erpowGrid').addClass('erc-hidden');
        $('#erpowStatus').empty();
        $('#erpowTitle').text('Pedido');
        $('#erpowInsert, #erpowCopy').prop('disabled', true);
        addrLoaded = false;
    }

    function setState(html) {
        $('#erpowGrid').addClass('erc-hidden');
        $('#erpowState').removeClass('erc-hidden').html(html);
        $('#erpowInsert, #erpowCopy').prop('disabled', true);
    }

    function loadingHtml() {
        var ec = E();
        return '<div class="erc-ow-pad">' + ec.stateHtml({ state: 'loading' }, 'Pedido') +
            '<div class="erc-ow-skel">' + ec.skeleton(3) + '</div></div>';
    }

    /* ── Carga de datos ────────────────────────────────────────────── */

    // Respuesta del manager/servicio → {state, message, order, history, shipping, delivery_notes}
    function normalize(resp, legacy) {
        if (!resp || resp.success === false || !resp.data) {
            return { state: (resp && resp.state && resp.state !== 'ok') ? resp.state : 'down', message: resp ? resp.message : null, reason: resp ? resp.reason : null };
        }
        if (resp.state && resp.state !== 'ok') {
            return { state: resp.state, message: resp.message, reason: resp.reason };
        }
        var d = resp.data;
        if (legacy || !d.order) {
            return {
                state: 'ok', legacy: true, order: d,
                history: { state: 'unavailable', reason: 'endpoint_missing', data: null },
                shipping: { state: 'unavailable', reason: 'endpoint_missing', data: null },
                delivery_notes: [],
            };
        }
        var sh = d.shipping || { state: 'unavailable', data: null };
        var dn = Array.isArray(d.delivery_notes) ? d.delivery_notes
            : (sh.data && Array.isArray(sh.data.delivery_notes) ? sh.data.delivery_notes : []);
        return {
            state: 'ok', legacy: false, order: d.order,
            history: d.history || { state: 'unavailable', data: null },
            shipping: sh,
            delivery_notes: dn,
        };
    }

    function legacyRequest(erpId, orderId) {
        var ec = E();
        if (!erpId) { return Promise.resolve({ success: false, state: 'down', message: 'No se pudo identificar al cliente en Gestión.' }); }
        return ec.request('/panel/helpdesk/erp/orders/' + encodeURIComponent(erpId) + '/' + encodeURIComponent(orderId), {}, 45000);
    }

    // La ruta nueva solo cae a la vieja cuando no hay respuesta útil (sin
    // conexión o sin cliente en el panel). 403/404 NO: son decisiones del servidor.
    function fetchOrder(cid, erpId, orderId) {
        var ec = E();
        var primary = cid ? ec.orderDetail(orderId) : Promise.resolve({ success: false, state: 'nocustomer' });
        return primary.then(function (resp) {
            var st = resp && resp.state;
            if (resp && resp.success !== false && resp.data) { return normalize(resp, false); }
            if (st === 'down' || st === 'nocustomer' || !st) {
                return legacyRequest(erpId, orderId).then(function (r2) {
                    if (r2 && r2.success && r2.data) { return normalize(r2, true); }
                    return normalize(resp, false);
                });
            }
            return normalize(resp, false);
        });
    }

    function load(force) {
        var ec = E();
        if (!cur) { return; }
        var my = ++token;
        cur.token = my;
        var key = (cur.cid || 'x') + ':' + cur.orderId;

        if (!force && cache[key]) { render(cache[key]); return; }

        setState(loadingHtml());
        fetchOrder(cur.cid, cur.erpId, cur.orderId).then(function (b) {
            if (!cur || cur.token !== my) { return; }
            if (b.state !== 'ok') {
                var obj = { state: b.state, message: b.message, reason: b.reason, retry: 'erp-order' };
                if (b.state === 'unavailable' && !b.message) { obj.message = 'Este pedido no existe en Gestión o no pertenece a este cliente.'; }
                setState('<div class="erc-ow-pad">' + ec.stateHtml(obj, 'Pedido') + '</div>');
                return;
            }
            cache[key] = b;
            render(b);
        });
    }

    /* ── Cálculos ──────────────────────────────────────────────────── */

    // Una sola fórmula de importe por línea: la de ErpChat.render.
    function lineAmount(l) { var ec = E(); return ec && ec.render.lineAmount ? ec.render.lineAmount(l) : 0; }

    function lineBase(l) {
        var sub = num(l.subtotal != null ? l.subtotal : l.total_bi);
        if (sub !== null) { return sub; }
        var units = num(l.units);
        var price = num(l.price != null ? l.price : l.price_bi) || 0;
        var disc = num(l.discount_percent) || 0;
        return (units === null ? 1 : units) * price * (1 - disc / 100);
    }

    function round2(v) { return Math.round(v * 100) / 100; }

    function figures(o) {
        var lines = Array.isArray(o.lines) ? o.lines : [];
        var t = o.totals || {};
        var base = num(t.lines_total);
        var total = null;
        if (lines.length) {
            if (base === null) { base = round2(lines.reduce(function (a, l) { return a + lineBase(l); }, 0)); }
            total = round2(lines.reduce(function (a, l) { return a + lineAmount(l); }, 0));
        }
        var paid = num(t.payments_total);
        if (paid === null && Array.isArray(o.payments) && o.payments.length) {
            paid = round2(o.payments.reduce(function (a, p) { return a + (paymentAmount(p) || 0); }, 0));
        }
        var units = lines.reduce(function (a, l) { var u = num(l.units); return a + (u === null ? 1 : u); }, 0);
        return { lines: lines, base: base, total: total, tax: (total !== null && base !== null) ? round2(total - base) : null, paid: paid, units: units };
    }

    function paymentAmount(p) {
        var v = num(p.amount != null ? p.amount : (p.amount_collected != null ? p.amount_collected : (p.total != null ? p.total : p.import)));
        return v;
    }

    function statusOf(o) {
        if (isOff(o.status)) { return false; }
        if (!blank(o.status_code_description)) { return o.status_code_description; }
        if (!blank(o.status_code)) { return String(o.status_code); }
        if (!blank(o.status_description)) { return o.status_description; }
        if (typeof o.status === 'string' && /^\d+$/.test(o.status)) { return o.status; }
        return null;
    }

    function statusLabel(o) {
        var st = statusOf(o);
        return st === null ? 'Sin estado' : E().statusInfo(st).label;
    }

    function orderNumber(o) { return String(o.number || o.order_id || o.id || ''); }

    function customerName() {
        var ec = E();
        var ov = ec ? ec.cachedOverview() : null;
        var s = ov && ov.data && ov.data.sections && ov.data.sections.summary ? ov.data.sections.summary.data : null;
        if (s && (s.label || s.surnames)) { return [s.label, s.surnames].filter(function (v) { return !blank(v); }).join(' ').trim(); }
        if (window.HDCommerce && typeof window.HDCommerce.customer === 'function') { return window.HDCommerce.customer().name || ''; }
        return '';
    }

    function isInternet(o) { return /internet|web|online/i.test(desc(o.origin)); }

    /* ── Render ────────────────────────────────────────────────────── */

    function render(b) {
        var ec = E();
        var o = b.order || {};
        cur.bundle = b;
        var nro = orderNumber(o);
        var name = customerName();
        var st = statusOf(o);

        $('#erpowTitle').html('Pedido <span class="bv-po-chip">#' + esc(nro) + '</span>' +
            (name ? '<span class="bv-po-crumb erc-ow-crumb">' + esc(name) + '</span>' : ''));
        $('#erpowStatus').html(st === null ? '' : ec.render.statusPill(st));

        renderMeta(o, st);
        renderLines(o);
        renderInfo(o);
        renderEnvio(b);
        renderPagos(o);
        renderHistorial(b);
        renderCliente(false);

        $('#erpowState').addClass('erc-hidden').empty();
        $('#erpowGrid').removeClass('erc-hidden');
        $('#erpowInsert, #erpowCopy').prop('disabled', false);
        loadShop();

        // Si la pestaña Cliente está a la vista (reapertura), carga su dirección.
        if (!$('#erpowPanelCliente').hasClass('erc-hidden')) { renderCliente(true); }
    }

    function chip(label, value, kind) {
        if (blank(value)) { return ''; }
        return '<span class="erc-ow-chip' + (kind ? ' erc-ow-chip--' + kind : '') + '">' +
            (label ? '<span class="k">' + esc(label) + '</span>' : '') + esc(value) + '</span>';
    }

    function renderMeta(o, st) {
        var ec = E();
        var html = '<div class="erc-ow-meta-top">' +
                '<span class="erc-ow-meta-date">' + esc(fdt(o.date)) + '</span>' +
                (st === null ? '' : '<span class="erc-ow-meta-pill">' + ec.render.statusPill(st) + '</span>') +
            '</div>' +
            '<div class="erc-ow-chips">' +
                chip('Origen', desc(o.origin), isInternet(o) ? 'good' : '') +
                chip('Almacén', desc(o.warehouse)) +
                chip('Catálogo', desc(o.catalog)) +
                chip('Prioridad', desc(o.priority)) +
                (o.invoiced === true ? chip('', 'Facturado', 'good') : '') +
            '</div>' +
            '<div class="erc-ow-shop erc-hidden" id="erpowShop"></div>';
        $('#erpowMeta').html(html);
    }

    function renderLines(o) {
        var ec = E();
        var f = figures(o);
        var declared = o.statistics && o.statistics.lines ? num(o.statistics.lines.total) : null;
        var sum = f.lines.length + (f.lines.length === 1 ? ' línea' : ' líneas') + ' · ' +
            (f.units % 1 === 0 ? f.units : f.units.toFixed(2)) + (f.units === 1 ? ' unidad' : ' unidades');
        $('#erpowSummary').text(f.lines.length ? sum : 'Sin líneas');

        var linesHtml = ec.render.lines(f.lines);
        if (declared !== null && declared > f.lines.length) {
            linesHtml += '<div class="erc-note erc-note--info erc-ow-gap"><span class="txt">Gestión indica ' + esc(declared) +
                ' líneas y solo devuelve ' + esc(f.lines.length) + '. El resto no es visible para el usuario de lectura.</span></div>';
        }
        $('#erpowLines').html(linesHtml);

        var rows = [];
        if (f.base !== null) { rows.push({ label: 'Base imponible', value: f.base, kind: '' }); }
        if (f.tax !== null) { rows.push({ label: 'IVA', value: f.tax, kind: 'is-muted' }); }
        if (f.paid !== null && f.paid > 0) {
            rows.push({ label: 'Pagado', value: f.paid, kind: 'is-good' });
            if (f.total !== null && f.total - f.paid > 0.009) { rows.push({ label: 'Pendiente de cobro', value: round2(f.total - f.paid), kind: 'is-muted' }); }
        }
        if (f.total !== null) { rows.push({ label: 'Total con IVA', value: f.total, kind: 'is-total' }); }
        else if (f.base !== null) { rows[0].kind = 'is-total'; rows[0].label = 'Total sin IVA'; }
        $('#erpowTotals').html(rows.length ? ec.render.totals(rows) : '');
        $('#erpowObs').html(ec.render.obs(o.observations));
    }

    function card(title, sub, icon, body) {
        return '<div class="bv-po-card">' +
            '<div class="bv-po-card-h">' +
                (icon ? '<span class="bv-po-sec-ic"><i class="' + escAttr(icon) + '"></i></span>' : '') +
                '<div class="bv-po-card-ht"><span class="t">' + esc(title) + '</span>' + (sub ? '<span class="s">' + esc(sub) + '</span>' : '') + '</div>' +
            '</div>' + body +
        '</div>';
    }

    function kvCell(k, v, mono, wide) {
        return E().render.kv(k, blank(v) ? null : String(v), mono, wide);
    }

    function renderInfo(o) {
        var ec = E();
        var st = statusOf(o);
        var kv = '<div class="erc-kv">' +
            kvCell('Nº pedido', orderNumber(o), true) +
            kvCell('Id central', o.id, true) +
            kvCell('Estado', statusLabel(o)) +
            kvCell('Situación', isOff(o.status) ? 'Anulado' : (o.status === true ? 'Activo' : null)) +
            kvCell('Fecha pedido', blank(o.date) ? null : fdt(o.date)) +
            kvCell('Fecha prevista', blank(o.expected_date) ? null : fdate(o.expected_date)) +
            kvCell('Servido', blank(o.served_date) ? 'Pendiente' : fdate(o.served_date)) +
            kvCell('Prioridad', desc(o.priority)) +
            kvCell('Facturado', o.invoiced === true ? 'Sí' : (o.invoiced === false ? 'No' : null)) +
            kvCell('Factura solicitada', o.requested_invoice === true ? 'Sí' : (o.requested_invoice === false ? 'No' : null)) +
            kvCell('Almacén', desc(o.warehouse)) +
            kvCell('Origen', desc(o.origin)) +
            kvCell('Catálogo', desc(o.catalog)) +
            kvCell('Última modificación', blank(o.updated) ? null : fdt(o.updated), false, true) +
        '</div>';
        var note = '';
        if (st !== null && !isOff(o.status) && blank(o.served_date) && !blank(o.expected_date)) {
            var d = ec.parseDate(o.expected_date);
            if (d && d.getTime() < Date.now() - 86400000) {
                note = '<div class="erc-note erc-note--info"><span class="txt">La fecha prevista (' + esc(fdate(o.expected_date)) + ') ya pasó y el pedido no figura como servido.</span></div>';
            }
        }
        $('#erpowPanelInfo').html(card('Datos del pedido', 'Gestión · solo lectura', 'fas fa-circle-info', kv) + note);
    }

    function safeUrl(u) {
        if (blank(u)) { return null; }
        var s = String(u).trim();
        return /^https?:\/\//i.test(s) ? s : null;
    }

    function dnItems(list) {
        if (!list.length) { return ''; }
        return '<div class="erc-list">' + list.map(function (d) {
            return '<button type="button" class="erc-item is-link" data-erow-dn="' + escAttr(d.id) + '">' +
                '<span class="ic"><i class="fas fa-file-lines"></i></span>' +
                '<span class="info">' +
                    '<span class="top"><span class="ref">Albarán ' + esc(d.number || d.id) + '</span></span>' +
                    '<span class="m">' + esc(fdate(d.date)) + '</span>' +
                '</span>' +
                '<span class="end"><span class="act">Ver</span></span>' +
            '</button>';
        }).join('') + '</div>';
    }

    function renderEnvio(b) {
        var ec = E();
        var sh = b.shipping || {};
        var o = b.order || {};
        var dns = b.delivery_notes || [];
        var html = '';

        if (sh.state === 'ok' && sh.data) {
            var s = sh.data;
            var carrier = s.carrier ? desc(s.carrier) : '';
            var url = safeUrl(s.tracking_url);
            var track = '';
            if (!blank(s.tracking_number)) {
                track = url
                    ? '<a class="erc-link erc-mono" href="' + escAttr(url) + '" target="_blank" rel="noopener noreferrer">' + esc(s.tracking_number) + '</a>'
                    : '<span class="erc-mono">' + esc(s.tracking_number) + '</span>';
            }
            var body = '<div class="erc-ow-track' + (carrier || track ? '' : ' is-empty') + '">' +
                '<span class="ic"><i class="fas fa-truck"></i></span>' +
                '<span class="bd">' +
                    '<span class="c">' + esc(carrier || 'Transportista sin informar') + '</span>' +
                    '<span class="t">' + (track || 'Sin número de seguimiento') + '</span>' +
                '</span>' +
            '</div>' +
            '<div class="erc-kv erc-ow-gap">' +
                kvCell('Fecha de envío', blank(s.shipped_at) ? null : fdate(s.shipped_at)) +
                kvCell('Servido', blank(o.served_date) ? null : fdate(o.served_date)) +
            '</div>' +
            (url ? '<a class="erc-btn erc-btn--outline erc-ow-gap" href="' + escAttr(url) + '" target="_blank" rel="noopener noreferrer">Seguir el envío</a>' : '');
            html += card('Envío', 'Transportista y seguimiento', 'fas fa-truck', body);
        } else if (sh.state === 'unavailable' || !sh.state) {
            html += card('Envío', 'Transportista y seguimiento', 'fas fa-truck',
                '<div class="erc-note erc-note--info"><span class="txt">Gestión no expone el transportista de este pedido.</span></div>' +
                (blank(o.served_date) ? '' : '<div class="erc-kv erc-ow-gap">' + kvCell('Servido', fdate(o.served_date)) + '</div>'));
        } else {
            html += card('Envío', 'Transportista y seguimiento', 'fas fa-truck', ec.stateHtml($.extend({}, sh, { retry: 'erp-order' }), 'Envío'));
        }

        var dnBody = dns.length
            ? dnItems(dns)
            : (b.legacy
                ? '<div class="erc-note erc-note--info"><span class="txt">Los albaranes del pedido no están disponibles ahora mismo.</span></div>'
                : ec.stateHtml({ state: 'empty', icon: 'fas fa-file-lines', message: 'Aún no hay albaranes para este pedido.' }, 'Sin albaranes'));
        html += card('Albaranes', dns.length ? dns.length + (dns.length === 1 ? ' albarán' : ' albaranes') : 'Del pedido', 'fas fa-file-lines', dnBody);

        $('#erpowPanelEnvio').html(html);
    }

    function renderPagos(o) {
        var ec = E();
        var pays = Array.isArray(o.payments) ? o.payments : [];
        var body;
        if (!pays.length) {
            body = ec.stateHtml({ state: 'empty', icon: 'fas fa-credit-card', message: 'Sin cobros registrados en el pedido' }, 'Sin cobros');
        } else {
            body = '<div class="erc-list">' + pays.map(function (p) {
                var method = desc(p.method || p.payment_method || p.type || p.description) || 'Cobro';
                var amt = paymentAmount(p);
                var when = p.date || p.created || null;
                var extra = [when ? fdate(when) : '', !blank(p.voucher) ? 'Vale ' + p.voucher : ''].filter(Boolean).join(' · ');
                return '<div class="erc-item">' +
                    '<span class="ic"><i class="fas fa-coins"></i></span>' +
                    '<span class="info"><span class="t">' + esc(method) + '</span>' + (extra ? '<span class="m">' + esc(extra) + '</span>' : '') + '</span>' +
                    '<span class="end"><span class="amt is-good">' + esc(money(amt)) + '</span></span>' +
                '</div>';
            }).join('') + '</div>';
        }
        var f = figures(o);
        var tot = (f.total !== null || f.paid !== null) ? '<div class="erc-ow-gap">' + ec.render.totals([
            { label: 'Pagado', value: f.paid || 0, kind: 'is-good' },
            { label: 'Total del pedido', value: f.total !== null ? f.total : f.base, kind: 'is-total' },
        ]) + '</div>' : '';
        $('#erpowPanelPagos').html(card('Cobros del pedido', 'Registrados en Gestión', 'fas fa-credit-card', body + tot));
    }

    function renderHistorial(b) {
        var ec = E();
        var h = b.history || {};
        var o = b.order || {};
        var html = '';

        if (h.state === 'ok' && Array.isArray(h.data) && h.data.length) {
            var rows = h.data.slice().sort(function (a, c) {
                var da = ec.parseDate(a.date), dc = ec.parseDate(c.date);
                return (dc ? dc.getTime() : 0) - (da ? da.getTime() : 0);
            });
            html = card('Historial de estados', rows.length + (rows.length === 1 ? ' cambio' : ' cambios'), 'fas fa-clock-rotate-left',
                '<div class="erc-timeline">' + rows.map(function (r, i) {
                    var lbl = !blank(r.description) ? r.description : (r.status != null ? ec.statusInfo(r.status).label : 'Cambio de estado');
                    var meta = [fdt(r.date), blank(r.user) ? '' : r.user].filter(function (v) { return v && v !== '—'; }).join(' · ');
                    return '<div class="erc-tl-row' + (i === 0 ? ' is-current' : '') + '"><span class="dot"></span>' +
                        '<span class="body"><span class="t">' + esc(lbl) + '</span>' + (meta ? '<span class="m">' + esc(meta) + '</span>' : '') + '</span></div>';
                }).join('') + '</div>');
        } else if (h.state === 'ok') {
            html = card('Historial de estados', '', 'fas fa-clock-rotate-left',
                ec.stateHtml({ state: 'empty', icon: 'fas fa-clock-rotate-left', message: 'Gestión no registra cambios de estado para este pedido.' }, 'Sin cambios'));
        } else {
            var st = $.extend({}, h, { retry: 'erp-order' });
            if (!st.state) { st.state = 'unavailable'; }
            html = card('Historial de estados', '', 'fas fa-clock-rotate-left', ec.stateHtml(st, 'Historial'));
        }

        // Hitos que sí conocemos por las fechas del pedido y sus albaranes.
        var marks = [];
        if (!blank(o.date)) { marks.push({ t: 'Pedido creado', d: o.date }); }
        (b.delivery_notes || []).forEach(function (d) { if (!blank(d.date)) { marks.push({ t: 'Albarán ' + (d.number || d.id), d: d.date }); } });
        if (!blank(o.served_date)) { marks.push({ t: 'Servido', d: o.served_date }); }
        if (!blank(o.expected_date) && blank(o.served_date)) { marks.push({ t: 'Fecha prevista', d: o.expected_date, future: true }); }
        if (marks.length) {
            marks.sort(function (a, c) {
                var da = ec.parseDate(a.d), dc = ec.parseDate(c.d);
                return (dc ? dc.getTime() : 0) - (da ? da.getTime() : 0);
            });
            html += card('Hitos del pedido', 'Según las fechas de Gestión', 'fas fa-flag',
                '<div class="erc-timeline">' + marks.map(function (m, i) {
                    return '<div class="erc-tl-row' + (i === 0 && !m.future ? ' is-current' : '') + '"><span class="dot"></span>' +
                        '<span class="body"><span class="t">' + esc(m.t) + '</span><span class="m">' + esc(fdt(m.d)) + '</span></span></div>';
                }).join('') + '</div>');
        }
        $('#erpowPanelHistorial').html(html);
    }

    function renderCliente(withAddress) {
        var ec = E();
        var ov = ec.cachedOverview();
        var secs = ov && ov.data && ov.data.sections ? ov.data.sections : {};
        var s = secs.summary && secs.summary.state === 'ok' ? (secs.summary.data || {}) : {};
        var name = customerName();
        var phones = Array.isArray(s.phones) ? s.phones.filter(function (p) { return !isOff(p.available) && !blank(p.number); }) : [];
        var phone = phones.length ? [phones[0].prefix ? '+' + String(phones[0].prefix).replace(/^\+/, '') : '', phones[0].number].filter(Boolean).join(' ') : '';
        var hd = window.HDCommerce && typeof window.HDCommerce.customer === 'function' ? window.HDCommerce.customer() : {};
        var erpId = (ov && ov.data && ov.data.erp_id) || (cur ? cur.erpId : null);
        var psId = ov && ov.data && ov.data.links ? ov.data.links.prestashop_customer_id : null;

        var kv = '<div class="erc-cust">' +
                '<span class="erc-avatar">' + esc(ec.initials(name)) + '</span>' +
                '<span class="erc-cust-body"><span class="nm">' + esc(name || 'Cliente') + '</span>' +
                (erpId ? '<span class="id">Gestión ' + esc(erpId) + '</span>' : '') + '</span>' +
            '</div>' +
            '<div class="erc-kv erc-ow-gap">' +
                kvCell('Correo', s.email || hd.email, true, true) +
                kvCell('Teléfono', phone || hd.phone, true) +
                kvCell('Id tienda', psId || s.code_internet, true) +
                kvCell('Tarjeta', s.card, true) +
                kvCell('Idioma', s.language) +
            '</div>';

        var addrHtml;
        if (!ec.can('addresses')) {
            addrHtml = ec.stateHtml({ state: 'forbidden' }, 'Direcciones');
        } else {
            addrHtml = '<div id="erpowAddr">' + ec.skeleton(1) + '</div>';
        }

        $('#erpowPanelCliente').html(
            card('Cliente', 'Ficha en Gestión', 'far fa-address-card', kv) +
            card('Dirección de envío', 'Por defecto en Gestión', 'fas fa-location-dot', addrHtml)
        );

        if (withAddress && ec.can('addresses')) { loadAddress(secs.addresses); }
    }

    function pickShipping(data) {
        var list = data && Array.isArray(data.addresses) ? data.addresses : (Array.isArray(data) ? data : []);
        var active = list.filter(function (a) { return !isOff(a.available); });
        return active.filter(function (a) { return a.default_shipping; })[0] || list.filter(function (a) { return a.default_shipping; })[0] || active[0] || null;
    }

    function paintAddress(resp) {
        var ec = E();
        var $a = $('#erpowAddr');
        if (!$a.length) { return; }
        if (!resp || resp.state !== 'ok') {
            $a.html(ec.stateHtml($.extend({}, resp || { state: 'down' }, { retry: 'erp-order-address' }), 'Direcciones'));
            return;
        }
        var a = pickShipping(resp.data);
        $a.html(a ? ec.render.address(a) : ec.stateHtml({ state: 'empty', icon: 'fas fa-location-dot', message: 'Sin direcciones en Gestión.' }, 'Sin dirección'));
    }

    function loadAddress(fromOverview) {
        var ec = E();
        var my = cur ? cur.token : null;
        if (fromOverview && fromOverview.state === 'ok') { addrLoaded = true; paintAddress(fromOverview); return; }
        if (!ec.customerId()) { paintAddress({ state: 'nocustomer' }); return; }
        ec.section('addresses').then(function (resp) {
            if (!cur || cur.token !== my) { return; }
            addrLoaded = resp && resp.state === 'ok';
            paintAddress(resp);
        });
    }

    /* ── Cruce con la tienda (PrestaShop) ──────────────────────────── */

    function shopAvailable() { return typeof window.openPsOrderWorkspace === 'function'; }

    function paintShop(m) {
        var $s = $('#erpowShop');
        if (!$s.length) { return; }
        if (!m || !m.ps_order_id) { $s.addClass('erc-hidden').empty(); return; }
        var how = m.matched_by === 'date_amount'
            ? 'Mismo día e importe en la tienda'
            : 'Enlazado por el identificador de origen';
        $s.html(
            '<span class="erc-ow-shop-info">' +
                '<span class="t">En la tienda: <b>' + esc(m.reference || ('#' + m.ps_order_id)) + '</b></span>' +
                '<span class="m">' + esc(how) + '</span>' +
            '</span>' +
            '<button type="button" class="erc-btn erc-btn--outline erc-btn--sm" data-erow-shop="' + escAttr(m.ps_order_id) + '">Ver pedido en la tienda</button>'
        ).removeClass('erc-hidden');
    }

    function loadShop() {
        var ec = E();
        if (!cur || !cur.cid || !ec || !shopAvailable() || !ec.can('orders')) { paintShop(null); return; }
        var key = cur.cid + ':' + cur.orderId;
        if (Object.prototype.hasOwnProperty.call(shopCache, key)) { paintShop(shopCache[key]); return; }
        var base = ec.base(cur.cid);
        if (!base) { paintShop(null); return; }
        var my = cur.token;
        var orderId = cur.orderId;
        paintShop(null);
        ec.request(base + '/orders/' + encodeURIComponent(orderId) + '/shop', {}, 30000).then(function (resp) {
            var m = resp && resp.success !== false && resp.state === 'ok' && resp.data && resp.data.ps_order_id ? resp.data : null;
            // Solo se recuerda una respuesta firme (con o sin coincidencia);
            // una caída se vuelve a intentar en la próxima apertura.
            if (resp && resp.state === 'ok') { shopCache[key] = m; }
            if (!cur || cur.token !== my || cur.orderId !== orderId) { return; }
            paintShop(m);
        });
    }

    function closeSelf() {
        var ec = E();
        if (ec) { ec.sheet.close($body()); }
        if (window.HDCommerce && typeof window.HDCommerce.close === 'function') { window.HDCommerce.close(MODAL); }
        else {
            $modal().removeClass('on');
            if (!$('.bv-modal.on').length) { $('body').css('overflow', ''); }
        }
    }

    $(document).on('click', '[data-bv-modal-name="' + MODAL + '"] [data-erow-shop]', function (e) {
        e.preventDefault();
        var psId = String($(this).attr('data-erow-shop') || '');
        if (!psId || !shopAvailable()) { return; }
        // Nunca un modal sobre otro: primero se cierra el de Gestión.
        closeSelf();
        window.openPsOrderWorkspace(psId);
    });

    /* ── Hojas internas: albarán y factura ─────────────────────────── */

    function sheetLabel() { return 'Pedido #' + (cur && cur.bundle ? orderNumber(cur.bundle.order || {}) : ''); }

    function openDeliveryNote(id, number) {
        var ec = E();
        var my = cur ? cur.token : null;
        var $sheet = ec.sheet.open($body(), {
            id: 'erow-dn', icon: 'fas fa-file-lines', label: sheetLabel(),
            title: 'Albarán ' + (number || id),
            html: ec.skeleton(3),
        });
        ec.deliveryNote(id).then(function (resp) {
            if (!cur || cur.token !== my || !$sheet.closest('body').length) { return; }
            if (!resp || resp.state !== 'ok' || !resp.data) {
                ec.sheet.update($sheet, { html: ec.stateHtml($.extend({}, resp || {}, { retry: 'erp-order-dn:' + id }), 'Albarán') });
                return;
            }
            var d = resp.data;
            var foot = (d.invoice_id ? '<button type="button" class="btn-primary w-100" data-erow-invoice="' + escAttr(d.invoice_id) + '" data-erow-from-dn="' + escAttr(id) + '" data-erow-from-dn-number="' + escAttr(d.number || '') + '">Ver factura</button>' : '') +
                '<button type="button" class="btn-secondary w-100" data-erc-sheet-close>Volver al pedido</button>';
            ec.sheet.update($sheet, { title: 'Albarán ' + (d.number || number || id), html: ec.render.deliveryNote(d), foot: foot });
        });
    }

    function openInvoice(id, fromDn, fromDnNumber) {
        var ec = E();
        var my = cur ? cur.token : null;
        var back = fromDn
            ? '<button type="button" class="btn-secondary w-100" data-erow-dn="' + escAttr(fromDn) + '" data-erow-dn-number="' + escAttr(fromDnNumber || '') + '">Volver al albarán</button>'
            : '';
        var foot = back + '<button type="button" class="btn-secondary w-100" data-erc-sheet-close>Volver al pedido</button>';
        var $sheet = ec.sheet.open($body(), {
            id: 'erow-inv', icon: 'fas fa-file-invoice', label: sheetLabel(),
            title: 'Factura', html: ec.skeleton(3), foot: foot,
        });
        if (!ec.can('finance')) {
            ec.sheet.update($sheet, { html: ec.stateHtml({ state: 'forbidden' }, 'Factura') });
            return;
        }
        ec.invoice(id).then(function (resp) {
            if (!cur || cur.token !== my || !$sheet.closest('body').length) { return; }
            if (!resp || resp.state !== 'ok' || !resp.data) {
                ec.sheet.update($sheet, { html: ec.stateHtml($.extend({}, resp || {}, { retry: 'erp-order-inv:' + id }), 'Factura') });
                return;
            }
            var d = resp.data;
            var ref = [d.series, d.number].filter(function (v) { return !blank(v); }).join('-') + (d.year ? '/' + d.year : '');
            ec.sheet.update($sheet, { title: 'Factura ' + (ref || id), html: ec.render.invoice(d) });
        });
    }

    /* ── Acciones ──────────────────────────────────────────────────── */

    function summaryText() {
        var b = cur && cur.bundle;
        if (!b) { return ''; }
        var o = b.order || {};
        var f = figures(o);
        var out = ['Pedido ' + orderNumber(o) + ' · ' + statusLabel(o)];
        if (!blank(o.date)) { out.push('Fecha: ' + fdate(o.date)); }
        if (!blank(o.served_date)) { out.push('Servido: ' + fdate(o.served_date)); }
        else if (!blank(o.expected_date)) { out.push('Fecha prevista: ' + fdate(o.expected_date)); }

        if (f.lines.length) {
            out.push('Artículos:');
            f.lines.slice(0, 5).forEach(function (l) {
                var a = l.article || {};
                var u = num(l.units);
                var ref = a.reference || a.code || '';
                out.push('- ' + (u === null ? 1 : u) + ' × ' + (a.description || l.description || 'Artículo') + (ref ? ' (' + ref + ')' : ''));
            });
            if (f.lines.length > 5) { out.push('- y ' + (f.lines.length - 5) + ' artículo(s) más'); }
        }
        if (f.total !== null) { out.push('Total: ' + money(f.total) + ' (IVA incluido)'); }
        else if (f.base !== null) { out.push('Total sin IVA: ' + money(f.base)); }

        var sh = b.shipping && b.shipping.state === 'ok' ? (b.shipping.data || {}) : null;
        if (sh) {
            var carrier = sh.carrier ? desc(sh.carrier) : '';
            if (!blank(sh.tracking_number) || carrier) {
                out.push('Envío: ' + [carrier, blank(sh.tracking_number) ? '' : 'seguimiento ' + sh.tracking_number].filter(Boolean).join(' · '));
            }
            var url = safeUrl(sh.tracking_url);
            if (url) { out.push('Seguimiento: ' + url); }
        }
        return out.join('\n');
    }

    $(document).on('click', '#erpowInsert', function () {
        var ec = E();
        var txt = summaryText();
        if (!ec || !txt) { return; }
        if (ec.insert(txt) && $('.bv-composer-input').first().is(':visible')) {
            ec.toast('success', 'Resumen insertado en el chat');
            if (window.HDCommerce && typeof window.HDCommerce.close === 'function') { window.HDCommerce.close(MODAL); }
            else {
                $modal().removeClass('on');
                if (!$('.bv-modal.on').length) { $('body').css('overflow', ''); }
            }
        }
    });

    $(document).on('click', '#erpowCopy', function () {
        var ec = E();
        if (!ec || !cur || !cur.bundle) { return; }
        ec.copy(orderNumber(cur.bundle.order || {}));
    });

    $(document).on('click', '#erpowTabs .bv-po-tab', function () {
        var go = String($(this).attr('data-erow-tab') || '');
        $('#erpowTabs .bv-po-tab').removeClass('on').attr('aria-selected', 'false');
        $(this).addClass('on').attr('aria-selected', 'true');
        $modal().find('[data-erow-panel]').addClass('erc-hidden').filter('[data-erow-panel="' + go + '"]').removeClass('erc-hidden');
        if (go === 'cliente' && cur && cur.bundle && !addrLoaded && E().can('addresses')) {
            var ov = E().cachedOverview();
            loadAddress(ov && ov.data && ov.data.sections ? ov.data.sections.addresses : null);
        }
    });

    $(document).on('click', '[data-bv-modal-name="' + MODAL + '"] [data-erow-dn]', function (e) {
        e.preventDefault();
        openDeliveryNote(String($(this).attr('data-erow-dn')), String($(this).attr('data-erow-dn-number') || $(this).find('.ref').text().replace(/^Albarán\s*/, '') || ''));
    });

    $(document).on('click', '[data-bv-modal-name="' + MODAL + '"] [data-erow-invoice]', function (e) {
        e.preventDefault();
        openInvoice(String($(this).attr('data-erow-invoice')), $(this).attr('data-erow-from-dn') || null, $(this).attr('data-erow-from-dn-number') || '');
    });

    $(document).on('erp:retry', function (e, target) {
        if (!cur || !$modal().hasClass('on')) { return; }
        var t = String(target || '');
        if (t === 'erp-order') { load(true); }
        else if (t === 'erp-order-address') { addrLoaded = false; $('#erpowAddr').html(E().skeleton(1)); loadAddress(null); }
        else if (t.indexOf('erp-order-dn:') === 0) { openDeliveryNote(t.substring(13)); }
        else if (t.indexOf('erp-order-inv:') === 0) { openInvoice(t.substring(14)); }
    });

    /* ── API pública ───────────────────────────────────────────────── */

    // erpCustomerId = id del cliente en Gestión; orderId = id central del pedido.
    window.openErpOrderWorkspace = function (erpCustomerId, orderId) {
        if (!orderId) { return; }
        if (!E()) { toast('warning', 'Gestión no está disponible ahora mismo.'); return; }
        var ec = E();
        cur = {
            erpId: erpCustomerId ? String(erpCustomerId) : ec.erpId(),
            orderId: String(orderId),
            cid: ec.customerId(),
            token: 0,
            bundle: null,
        };
        resetView();
        openModal();
        load(false);
    };
})(window.jQuery);
