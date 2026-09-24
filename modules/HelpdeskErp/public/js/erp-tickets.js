/*!
 * HelpdeskErp · Tienda (PrestaShop) y Gestión (ERP) en la vista de ticket.
 *
 * Se carga en /panel/helpdesk/tickets a través de tickets/bridge.blade.php
 * (listener ErpTicketsViewBridge, sin tocar HelpdeskTickets). Hace tres cosas:
 *
 *  1. Mantiene un panel derecho "virtual" del inbox (.bv-right.erc-tkt-host,
 *     fuera de pantalla) con el cliente del ticket abierto: ErpChat,
 *     HDCommerce y PscStore leen de ahí el id del cliente. Al cambiar de
 *     ticket se rehace entero (jQuery cachea .data()) y, si el agente puede
 *     usar la tienda, se rellena con el tab oculto de la tienda que sirve
 *     GET /panel/helpdesk/erp/tickets/{ticket}/host.
 *  2. Añade al panel lateral del ticket la pestaña "Tienda y Gestión": resumen
 *     de Gestión (alertas, puntos, últimos pedidos) y de la tienda (últimos
 *     pedidos), con botones que abren los mismos modales que el inbox
 *     (ficha de cliente ERP, pedidos ERP, workspace de pedido, ficha y
 *     pedidos de la tienda). Se engancha envolviendo renderActiveSidePane()
 *     de tickets-app (global) y añadiendo un botón al riel #tkt-side-rail.
 *  3. Cierra los .bv-modal (✕, fondo, Escape), porque en tickets no está el
 *     JS del inbox que lo hace, y pasa a la respuesta del ticket lo que esos
 *     modales "insertan en el chat".
 *
 * Gestión es SOLO LECTURA. Fuente: modules/HelpdeskErp/public/js/ — copiar a
 * public/modules/helpdeskerp/js/ tras editar.
 */
(function ($) {
    'use strict';

    if (!$ || window.__ercTicketsLoaded) { return; }

    var $cfg = $('#ercTktConfig');
    if (!$cfg.length) { return; }
    window.__ercTicketsLoaded = true;

    var TAB = 'erc';
    var ORDERS_SHOWN = 5;

    var CFG = {
        erp: String($cfg.attr('data-erp')) === '1',
        ps: String($cfg.attr('data-ps')) === '1',
        hostUrl: String($cfg.attr('data-host-url') || ''),
    };

    var st = {
        customerId: null,   // cliente del .bv-right actual
        ticketId: null,
        token: 0,
        psState: 'idle',    // idle | loading | ok | off | forbidden | error | nocustomer
    };

    $('body').addClass('erc-tkt-has-bridge');

    /* ── Utilidades ────────────────────────────────────────────────── */

    function E() { return window.ErpChat || null; }
    function P() { return window.PscStore || null; }
    function S() { return window.TKA && window.TKA.state ? window.TKA.state : null; }

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function escAttr(s) { return esc(s).replace(/`/g, '&#96;'); }

    function toast(kind, msg) {
        if (window.toastr && typeof window.toastr[kind] === 'function') { window.toastr[kind](msg); }
    }

    function currentTicket() {
        var s = S();
        return s ? s.currentTicket || null : null;
    }

    function ticketCustomer() {
        var t = currentTicket();
        var s = S();
        var d = s ? s.currentDetail : null;
        if (d && d.customer && t && t.customer && String(d.customer.id) === String(t.customer.id)) {
            return d.customer;
        }
        return t ? t.customer || null : null;
    }

    function paneActive() {
        var s = S();
        return !!(s && s.sideTab === TAB);
    }

    function $pane() { return $('#tkt-side-content'); }

    function skeleton(n) {
        var C = E();
        if (C && typeof C.skeleton === 'function') { return C.skeleton(n || 3); }
        return '<div class="erc-tkt-skel"><div class="tkt-skeleton"></div></div>';
    }

    // Pedido que el cliente escribió en el formulario del ticket (tickets-app).
    function formOrderRef() {
        try {
            if (typeof window.ticketOrderRef === 'function') { return window.ticketOrderRef(); }
        } catch (e) { /* sin detalle todavía */ }
        return null;
    }

    function sameRef(a, b) {
        if (a == null || b == null) { return false; }
        var x = String(a).trim().replace(/^#/, '').toUpperCase();
        var y = String(b).trim().replace(/^#/, '').toUpperCase();
        return x !== '' && x === y;
    }

    /* ── Panel derecho virtual (.bv-right) ─────────────────────────── */

    function closeAllModals() {
        $('.bv-modal.on').each(function () { closeBvModal($(this)); });
        var el = document.getElementById('psOrdersModal');
        if (el && window.bootstrap && window.bootstrap.Modal) {
            var m = window.bootstrap.Modal.getInstance(el);
            if (m) { m.hide(); }
        }
    }

    function buildHost(cust) {
        $('.erc-tkt-host').remove();
        $('.erc-tkt-moved').remove();
        if (!cust || !cust.id) { return; }

        var $h = $('<div class="bv-right erc-tkt-host" aria-hidden="true"></div>');
        $h.attr({
            'data-customer-id': String(cust.id),
            'data-customer-name': cust.name || '',
            'data-customer-email': cust.email || '',
            'data-customer-phone': cust.phone || cust.whatsapp_phone || '',
            'data-customer-city': cust.city || '',
            'data-customer-country': cust.country || 'ES',
            'data-customer-language': cust.language || '',
        });
        $('body').append($h);
    }

    // Rehace el host cuando cambia el cliente del ticket abierto.
    function syncHost() {
        var t = currentTicket();
        var cust = t ? t.customer || null : null;
        var cid = cust && cust.id ? String(cust.id) : null;
        var tid = t ? String(t.id) : null;

        if (cid === st.customerId) {
            st.ticketId = tid;
            return;
        }

        closeAllModals();
        st.customerId = cid;
        st.ticketId = tid;
        st.token += 1;
        st.psState = cid ? (CFG.ps ? 'loading' : 'off') : 'nocustomer';
        buildHost(ticketCustomer());

        if (cid && CFG.ps && CFG.hostUrl && tid) {
            loadHost(tid, cid, st.token);
        } else if (cid && CFG.ps) {
            st.psState = 'off';
        }
    }

    function loadHost(ticketId, cid, token) {
        $.ajax({
            url: CFG.hostUrl.replace('__TICKET__', encodeURIComponent(ticketId)),
            method: 'GET',
            dataType: 'json',
            headers: { Accept: 'application/json' },
            timeout: 20000,
        }).done(function (r) {
            if (token !== st.token) { return; }
            var ps = (r && r.ps) || {};
            var c = r && r.customer;

            if (c && String(c.id) === cid) {
                var $h = $('.erc-tkt-host').first();
                var attrs = {
                    'customer-name': c.name, 'customer-email': c.email, 'customer-phone': c.phone,
                    'customer-city': c.city, 'customer-state': c.state, 'customer-country': c.country,
                    'customer-zip': c.zip, 'customer-language': c.language, 'customer-timezone': c.timezone,
                    'update-url': c.update_url,
                };
                Object.keys(attrs).forEach(function (k) {
                    var v = attrs[k] == null ? '' : String(attrs[k]);
                    $h.attr('data-' + k, v);
                    $h.data(k, v);
                });
            }

            if (ps.state === 'ok' && ps.html) {
                var $h2 = $('.erc-tkt-host').first();
                $h2.append(ps.html);
                // Los modales Bootstrap del tab (lista de pedidos) van al body:
                // el host está marcado aria-hidden.
                $h2.find('.modal').each(function () {
                    $(this).addClass('erc-tkt-moved').appendTo(document.body);
                });
                st.psState = 'ok';
            } else {
                st.psState = ps.state || 'error';
            }
            renderPsIfActive();
        }).fail(function (xhr) {
            if (token !== st.token) { return; }
            st.psState = xhr && xhr.status === 403 ? 'forbidden' : 'error';
            renderPsIfActive();
        });
    }

    /* ── Pestaña "Tienda y Gestión" ─────────────────────────────────── */

    function card(id, title, sub) {
        return '<div class="tkt-side-card erc-tkt-card" id="' + id + '">' +
            '<div class="tkt-side-card-head">' + esc(title) +
                (sub ? '<span class="erc-tkt-sub">' + esc(sub) + '</span>' : '') +
            '</div>' +
            '<div class="tkt-side-card-body"><div class="erc-tkt-body" data-erc-tkt-body>' + skeleton(3) + '</div></div>' +
        '</div>';
    }

    function renderPane() {
        var $c = $pane();
        var t = currentTicket();
        if (!$c.length || !t) { return; }

        if (!t.customer) {
            $c.html('<div class="tkt-empty-box">Este ticket no tiene un cliente asociado: no hay tienda ni Gestión que consultar.</div>');
            return;
        }

        var html = '<div class="erc-tkt" data-erc-tkt-customer="' + escAttr(t.customer.id) + '">' +
            '<div id="ercTktRef"></div>' +
            (CFG.erp ? card('ercTktErp', 'Gestión (ERP)', 'Solo lectura') : '') +
            (CFG.ps ? card('ercTktPs', 'Tienda (PrestaShop)') : '') +
            '</div>';
        $c.html(html);

        renderErp();
        renderPs();
    }

    function $body(id) { return $('#' + id + ' [data-erc-tkt-body]'); }

    /* ── Gestión ─────────────────────────────────────────────────── */

    var lastErp = null;   // {cid, resp}

    function renderErp() {
        if (!CFG.erp || !$('#ercTktErp').length) { return; }
        var C = E();
        var $b = $body('ercTktErp');
        if (!C) {
            $b.html('<div class="erc-tkt-muted">Gestión no está disponible en esta pantalla ahora mismo.</div>');
            return;
        }
        var cid = st.customerId;
        var cached = C.cachedOverview(cid);
        if (cached) { drawErp(cached); } else { $b.html(skeleton(3)); }

        C.overview(false).then(function (resp) {
            if (cid !== st.customerId || !paneActive()) { return; }
            drawErp(resp);
        });
    }

    function erpOrderRow(o) {
        var C = E();
        var meta = [C.date(o.date, true)];
        if (o.served_date) { meta.push('servido ' + C.date(o.served_date, false)); }
        return '<div class="erc-item erc-item--row is-link" role="button" tabindex="0" data-erp-order-open="' + escAttr(o.id) + '">' +
            '<span class="info">' +
                '<span class="top"><span class="ref">#' + esc(o.number || o.order_id || o.id) + '</span>' + C.render.statusPill(o.status, o.status_description) + '</span>' +
                '<span class="m">' + esc(meta.filter(Boolean).join(' · ')) + '</span>' +
            '</span>' +
            '<span class="end"><span class="act">Abrir</span></span>' +
        '</div>';
    }

    function alertAction(a) {
        var C = E();
        var act = a && a.action;
        if (!act || !act.open) { return ''; }
        if (act.open === 'order') {
            return act.order_id && C.can('orders') ? '<button type="button" class="act" data-erp-order-open="' + escAttr(act.order_id) + '">Ver pedido</button>' : '';
        }
        var perm = { finance: 'finance', loyalty: 'loyalty', orders: 'orders', customer: 'view' }[act.open] || 'view';
        if (!C.can(perm)) { return ''; }
        return '<button type="button" class="act" data-erp-open="' + escAttr(act.open) + '"' +
            (act.pane ? ' data-erp-pane="' + escAttr(act.pane) + '"' : '') + '>Ver</button>';
    }

    function drawErp(resp) {
        var C = E();
        var $b = $body('ercTktErp');
        if (!C || !$b.length) { return; }
        lastErp = { cid: st.customerId, resp: resp };

        var state = resp ? resp.state : 'down';
        var foot = '<div class="erc-foot">' +
            '<button type="button" class="erc-btn erc-btn--outline" data-erc-tkt-refresh="erp">Actualizar</button>' +
            '</div>';

        if (state === 'forbidden') {
            $b.html('<div class="erc-tkt-muted">No tienes acceso a los datos de Gestión de este cliente. Solo se pueden consultar los clientes de tus bandejas.</div>');
            return;
        }
        if (state !== 'ok' || !resp.data) {
            var obj = state === 'down' ? $.extend({}, resp, { retry: 'overview' }) : resp;
            $b.html(C.stateHtml(obj || 'down') + (state === 'unlinked' || state === 'down' ? '' : foot));
            renderRefNote();
            return;
        }

        var data = resp.data || {};
        var secs = data.sections || {};
        var sum = secs.summary && secs.summary.state === 'ok' ? secs.summary.data || {} : null;
        var html = '';

        if (sum) {
            var name = [sum.label, sum.surnames].filter(Boolean).join(' ').trim() || 'Cliente de Gestión';
            html += '<div class="erc-cust">' +
                '<span class="erc-avatar erc-avatar--sm">' + esc(C.initials(name)) + '</span>' +
                '<span class="erc-cust-body">' +
                    '<span class="nm">' + esc(name) + '</span>' +
                    (sum.email || sum.cif ? '<span class="s">' + esc([sum.email, sum.cif].filter(Boolean).join(' · ')) + '</span>' : '') +
                    '<span class="id">Gestión ' + esc(data.erp_id || sum.id || '') + '</span>' +
                '</span>' +
            '</div>';
        } else if (secs.summary) {
            html += C.stateHtml(secs.summary, 'Ficha');
        }

        var alerts = Array.isArray(data.alerts) ? data.alerts.slice(0, 3) : [];
        if (alerts.length) {
            html += '<div class="erc-alerts">' + alerts.map(function (a) {
                var lvl = ['warn', 'info', 'good'].indexOf(a.level) >= 0 ? a.level : 'info';
                return '<div class="erc-alert erc-alert--' + lvl + '"><span class="dot"></span><span class="txt">' + esc(a.text) + '</span>' + alertAction(a) + '</div>';
            }).join('') + '</div>';
        }

        var pts = secs.loyalty_points;
        var ord = secs.orders;
        var ordList = ord && ord.state === 'ok' && Array.isArray(ord.data) ? ord.data : [];
        var ordCount = ord && ord.pagination && ord.pagination.count != null ? ord.pagination.count : ordList.length;
        var showPts = pts && pts.state === 'ok' && pts.data && C.can('loyalty');
        var showOrd = ord && ord.state === 'ok' && C.can('orders');
        if (showPts || showOrd) {
            html += '<div class="erc-stats erc-stats--2">' +
                (showOrd ? '<div class="erc-stat"><span class="n">' + esc(ordCount + (ord.pagination && ord.pagination.has_more ? '+' : '')) + '</span><span class="l">Pedidos</span></div>' : '') +
                (showPts ? '<div class="erc-stat"><span class="n is-good">' + esc(C.num(pts.data.balance) != null ? C.num(pts.data.balance) : '—') + '</span><span class="l">Puntos</span></div>' : '') +
            '</div>';
        }

        if (C.can('orders') && ord && ord.state !== 'forbidden') {
            html += '<div class="erc-sec-label">Últimos pedidos</div>';
            if (ord.state === 'ok') {
                html += ordList.length
                    ? '<div class="erc-list">' + ordList.slice(0, ORDERS_SHOWN).map(erpOrderRow).join('') + '</div>'
                    : C.stateHtml({ state: 'empty', message: 'Este cliente no tiene pedidos en Gestión.', icon: 'fas fa-box-open' }, 'Sin pedidos');
            } else {
                html += C.stateHtml(ord.state === 'down' ? $.extend({}, ord, { retry: 'overview' }) : ord, 'Pedidos');
            }
        }

        html += '<div class="erc-foot">' +
            '<button type="button" class="erc-btn erc-btn--primary" data-erp-open="customer" data-erp-pane="summary">Ficha de cliente en Gestión</button>' +
            (C.can('orders') ? '<button type="button" class="erc-btn erc-btn--outline" data-erp-open="orders">Todos los pedidos de Gestión</button>' : '') +
            (C.can('finance') ? '<button type="button" class="erc-btn erc-btn--outline" data-erp-open="finance" data-erp-pane="balance">Finanzas</button>' : '') +
            (C.can('loyalty') ? '<button type="button" class="erc-btn erc-btn--outline" data-erp-open="loyalty" data-erp-pane="points">Fidelización</button>' : '') +
            '<button type="button" class="erc-btn erc-btn--outline" data-erc-tkt-refresh="erp">Actualizar</button>' +
        '</div>';

        $b.html(html);
        renderRefNote();
    }

    /* ── Tienda ──────────────────────────────────────────────────── */

    function renderPsIfActive() {
        if (paneActive() && $('#ercTktPs').length) { renderPs(); }
    }

    function renderPs() {
        if (!CFG.ps || !$('#ercTktPs').length) { return; }
        var $b = $body('ercTktPs');

        switch (st.psState) {
            case 'loading':
            case 'idle':
                $b.html(skeleton(3));
                return;
            case 'forbidden':
                $b.html('<div class="erc-tkt-muted">No tienes acceso a los datos de la tienda de este cliente. Solo se pueden consultar los clientes de tus bandejas.</div>');
                return;
            case 'off':
                $b.html('<div class="erc-tkt-muted">La tienda no está disponible en esta pantalla.</div>');
                return;
            case 'error':
                $b.html('<div class="erc-note erc-note--warn"><span class="txt">No se pudo cargar la tienda de este cliente.</span><button type="button" class="act" data-erc-tkt-refresh="ps-host">Reintentar</button></div>');
                return;
            case 'nocustomer':
                $b.html('');
                return;
        }

        var store = P();
        if (!store) {
            $b.html('<div class="erc-tkt-muted">La tienda no está disponible en esta pantalla.</div>');
            return;
        }

        var ctx = store.ctx();
        var cid = st.customerId;
        if (ctx && ctx.bridge === 'ok' && ctx.__ercCid === cid) {
            drawPs(ctx);
        } else {
            $b.html(skeleton(3));
        }
        loadStore(store, false, cid);
    }

    // PscStore.load() puede responder con la petición que ya estaba en curso
    // para el ticket anterior. Con el contexto sano se pide otra vez sin
    // forzar: si ya es el del cliente actual sale de caché al instante; si
    // no, se piden los datos de este cliente.
    function loadStore(store, force, cid) {
        function done(fresh) {
            if (fresh) { fresh.__ercCid = cid; }
            if (cid !== st.customerId || !paneActive()) { return; }
            drawPs(fresh || {});
        }
        store.load(force, function (first) {
            if (cid !== st.customerId) { return; }
            if (!first || first.bridge !== 'ok') { done(first); return; }
            store.load(false, done);
        });
    }

    function drawPs(ctx) {
        var $b = $body('ercTktPs');
        var store = P();
        if (!$b.length || !store) { return; }

        var customer = ctx.customer || null;
        var orders = Array.isArray(ctx.orders) ? ctx.orders : [];
        var found = !!(customer && customer.found);

        if (ctx.bridge === 'down' && !found) {
            $b.html('<div class="erc-note erc-note--warn"><span class="txt">La tienda no responde ahora mismo.</span><button type="button" class="act" data-erc-tkt-refresh="ps">Reintentar</button></div>');
            return;
        }

        if (!found) {
            $b.html('<div class="erc-tkt-muted">Este cliente no aparece en la tienda o todavía no está vinculado. Puedes vincularlo desde la ficha 360 del cliente.</div>' +
                '<div class="erc-foot"><button type="button" class="erc-btn erc-btn--outline" data-erc-tkt-refresh="ps">Actualizar</button></div>');
            renderRefNote();
            return;
        }

        var name = [customer.firstname, customer.lastname].filter(Boolean).join(' ').trim() || customer.email || 'Cliente de la tienda';
        var total = customer.orders_count != null ? customer.orders_count : orders.length;
        var html = '<div class="erc-cust">' +
                '<span class="erc-avatar erc-avatar--sm">' + esc(store.initials ? store.initials(name) : name.charAt(0)) + '</span>' +
                '<span class="erc-cust-body">' +
                    '<span class="nm">' + esc(name) + '</span>' +
                    (customer.email ? '<span class="s">' + esc(customer.email) + '</span>' : '') +
                    '<span class="id">Tienda ' + esc(customer.id || '') + ' · ' + esc(total) + (Number(total) === 1 ? ' pedido' : ' pedidos') + '</span>' +
                '</span>' +
            '</div>';

        if (ctx.bridge === 'down') {
            html += '<div class="erc-note erc-note--warn"><span class="txt">La tienda no responde: se muestran los últimos datos guardados.</span><button type="button" class="act" data-erc-tkt-refresh="ps">Reintentar</button></div>';
        }

        html += '<div class="erc-sec-label">Últimos pedidos</div>';
        var rowFn = window.PscChat && typeof window.PscChat.orderRowHtml === 'function' ? window.PscChat.orderRowHtml : null;
        if (!orders.length) {
            html += '<div class="erc-tkt-muted">Este cliente no tiene pedidos en la tienda.</div>';
        } else if (rowFn) {
            html += '<div class="erc-tkt-ps-orders">' + orders.slice(0, ORDERS_SHOWN).map(function (o) {
                try { return rowFn(o); } catch (e) { return ''; }
            }).join('') + '</div>';
        }

        html += '<div class="erc-foot">' +
            '<button type="button" class="erc-btn erc-btn--primary" data-psc-open-customer="dashboard">Ficha de cliente en la tienda</button>' +
            (orders.length ? '<button type="button" class="erc-btn erc-btn--outline" data-psc-open-orders>Todos los pedidos de la tienda</button>' : '') +
            '<button type="button" class="erc-btn erc-btn--outline" data-erc-tkt-refresh="ps">Actualizar</button>' +
        '</div>';

        $b.html(html);
        renderRefNote();
    }

    /* ── Pedido indicado en el formulario del ticket ─────────────── */

    function renderRefNote() {
        var $n = $('#ercTktRef');
        if (!$n.length) { return; }
        var ref = formOrderRef();
        if (!ref) { $n.empty(); return; }

        var btns = [];

        var store = P();
        var ctx = store && CFG.ps && st.psState === 'ok' ? store.ctx() : null;
        if (ctx && ctx.__ercCid === st.customerId && Array.isArray(ctx.orders)) {
            var ps = ctx.orders.filter(function (o) { return sameRef(o.id, ref) || sameRef(o.reference, ref); })[0];
            if (ps) {
                btns.push('<button type="button" class="erc-btn erc-btn--primary" data-ps-order-open data-order-id="' + escAttr(ps.id) + '">Abrir el pedido en la tienda</button>');
            }
        }

        var C = E();
        var resp = lastErp && lastErp.cid === st.customerId ? lastErp.resp : null;
        var ord = resp && resp.data && resp.data.sections ? resp.data.sections.orders : null;
        if (C && C.can('orders') && ord && ord.state === 'ok' && Array.isArray(ord.data)) {
            var erp = ord.data.filter(function (o) { return sameRef(o.id, ref) || sameRef(o.number, ref) || sameRef(o.order_id, ref); })[0];
            if (erp) {
                btns.push('<button type="button" class="erc-btn ' + (btns.length ? 'erc-btn--outline' : 'erc-btn--primary') + '" data-erp-order-open="' + escAttr(erp.id) + '">Abrir el pedido en Gestión</button>');
            }
        }

        $n.html('<div class="erc-tkt-ref">' +
            '<span>El cliente indicó en el formulario el pedido <span class="erc-mono">' + esc(ref) + '</span>.' +
            (btns.length ? '' : ' No aparece entre sus últimos pedidos.') + '</span>' +
            (btns.length ? '<div class="erc-foot">' + btns.join('') + '</div>' : '') +
        '</div>');
    }

    /* ── Pestaña "Cliente": acceso a la nueva pestaña ─────────────── */

    function appendTeaser() {
        var $c = $pane();
        var t = currentTicket();
        if (!t || !t.customer || !$c.find('.tkt-side-card').length || $c.find('.erc-tkt-teaser').length) { return; }
        var what = [CFG.ps ? 'la tienda' : null, CFG.erp ? 'Gestión' : null].filter(Boolean).join(' y ');
        var $card = $('<div class="tkt-side-card erc-tkt-teaser">' +
            '<div class="tkt-side-card-head">Tienda y Gestión</div>' +
            '<div class="tkt-side-card-body">' +
                '<p class="erc-tkt-muted">Pedidos, ficha de cliente y fidelización de este cliente en ' + esc(what) + '.</p>' +
                '<button type="button" class="erc-btn erc-btn--outline" data-erc-tkt-go>Ver tienda y Gestión</button>' +
            '</div>' +
        '</div>');
        var $first = $c.find('.tkt-side-card').first();
        $card.insertAfter($first);
    }

    /* ── Enganche con tickets-app ─────────────────────────────────── */

    function goTab() {
        if (typeof window.selectSideTab === 'function') { window.selectSideTab(TAB); }
    }

    function addRailButton() {
        var $rail = $('#tkt-side-rail');
        if (!$rail.length || $rail.find('[data-side="' + TAB + '"]').length) { return; }
        var label = CFG.ps && CFG.erp ? 'Tienda y Gestión' : (CFG.ps ? 'Tienda' : 'Gestión');
        var $btn = $('<button type="button" class="tkt-icon-tab erc-tkt-rail" data-side="' + TAB + '"></button>')
            .attr({ title: label, 'aria-label': label })
            .append('<i class="fa-solid fa-store" aria-hidden="true"></i>');
        var $after = $rail.find('[data-side="cliente"]');
        if ($after.length) { $btn.insertAfter($after); } else { $btn.insertBefore($rail.find('#tkt-side-toggle')); }
    }

    function install() {
        var orig = window.renderActiveSidePane;
        if (typeof orig !== 'function') { return false; }
        if (orig.__ercTickets) { return true; }

        var wrapped = function () {
            try { syncHost(); } catch (e) { /* el panel del ticket va primero */ }
            var s = S();
            if (s && s.sideTab === TAB) {
                if (s.currentTicket) { renderPane(); }
                return undefined;
            }
            var r = orig.apply(this, arguments);
            if (s && s.sideTab === 'cliente') {
                try { appendTeaser(); } catch (e) { /* opcional */ }
            }
            return r;
        };
        wrapped.__ercTickets = true;
        window.renderActiveSidePane = wrapped;
        addRailButton();
        return true;
    }

    if (!install()) {
        // tickets-app no está en esta página: nada que enganchar.
        return;
    }

    // El riel lo enlaza initTicketsApp() al arrancar ($(document).ready), que
    // corre después de este script (defer). Si ya hubiera arrancado, el botón
    // no tendría handler: se enlaza aquí solo en ese caso.
    $(function () {
        setTimeout(function () {
            var btn = $('#tkt-side-rail [data-side="' + TAB + '"]').get(0);
            var ev = btn && $._data ? $._data(btn, 'events') : null;
            if (btn && !(ev && ev.click && ev.click.length)) {
                $(btn).on('click', goTab);
            }
        }, 0);
    });

    /* ── Eventos ──────────────────────────────────────────────────── */

    $(document).on('click', '[data-erc-tkt-go]', goTab);

    $(document).on('click', '[data-erc-tkt-refresh]', function () {
        var what = String($(this).attr('data-erc-tkt-refresh'));
        if (what === 'erp') {
            var C = E();
            if (!C) { return; }
            $body('ercTktErp').html(skeleton(3));
            C.overview(true).then(function (resp) {
                if (paneActive()) { drawErp(resp); }
            });
        } else if (what === 'ps') {
            var store = P();
            if (!store) { return; }
            $body('ercTktPs').html(skeleton(3));
            loadStore(store, true, st.customerId);
        } else if (what === 'ps-host') {
            var t = currentTicket();
            if (!t || !st.customerId) { return; }
            $('.erc-tkt-host').find('[data-bv-tab-content]').remove();
            $('.erc-tkt-moved').remove();
            st.token += 1;
            st.psState = 'loading';
            renderPs();
            loadHost(String(t.id), st.customerId, st.token);
        }
    });

    $(document).on('erp:overview-loaded erp:orders-ready', function (e, resp, cid) {
        if (!paneActive() || !cid || String(cid) !== String(st.customerId) || !$('#ercTktErp').length) { return; }
        drawErp(resp);
    });

    $(document).on('keydown', '.erc-tkt .erc-item[role="button"]', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); }
    });

    if (P() && typeof P().onChange === 'function') {
        // Otra pieza recargó el contexto de la tienda (p. ej. tras una acción
        // en el workspace de pedido): se repinta si es el de este cliente.
        // Sin volver a llamar a load(): con la tienda caída, cada load() es una
        // petición nueva y otro onChange, y se entraría en bucle.
        P().onChange(function () {
            if (!paneActive() || !$('#ercTktPs').length || st.psState !== 'ok') { return; }
            var ctx = P().ctx();
            if (!ctx) { return; }
            if (ctx.__ercCid === st.customerId) { drawPs(ctx); return; }
            // Contexto recién recargado por otra pieza: se confirma que es el de
            // este cliente (con la tienda sana sale de caché, sin petición).
            if (ctx.bridge === 'ok') { loadStore(P(), false, st.customerId); }
        });
    }

    /* ── Cierre de los .bv-modal (en tickets no está conversations.js) ── */

    function closeBvModal($m) {
        if (!$m || !$m.length) { return; }
        var name = String($m.attr('data-bv-modal-name') || '');
        if (name && window.HDCommerce && typeof window.HDCommerce.close === 'function') {
            window.HDCommerce.close(name);
        } else {
            $m.removeClass('on');
            if (!$('.bv-modal.on').length) { $('body').css('overflow', ''); }
        }
    }

    $(document).on('click', '.bv-modal [data-bv-close]', function (e) {
        e.preventDefault();
        closeBvModal($(this).closest('.bv-modal'));
    });

    $(document).on('click', '.bv-modal', function (e) {
        if (e.target === this) { closeBvModal($(this)); }
    });

    $(document).on('keydown', function (e) {
        if (e.key !== 'Escape') { return; }
        var $top = $('.bv-modal.on').last();
        if (!$top.length) { return; }
        // Hojas internas de la tienda: las cierra su propio JS.
        if ($top.find('.ps-sheet:not(.bv-hidden)').length) { return; }
        closeBvModal($top);
    });

    /* ── "Insertar en el chat" → respuesta del ticket ─────────────── */

    function copyText(text) {
        var C = E();
        if (C && typeof C.copy === 'function') { C.copy(text); return; }
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function () { toast('success', 'Copiado'); });
        }
    }

    $(document).on('input', '.erc-tkt-composer', function () {
        var $bridge = $(this);
        var text = String($bridge.val() || '').trim();
        if (!text) { return; }
        $bridge.val('');

        var $reply = $('#tkt-reply-body');
        if ($reply.length && $reply.is(':visible') && !$reply.prop('disabled') && !$reply.prop('readonly')) {
            var current = String($reply.val() || '').replace(/\s+$/, '');
            var next = current ? current + '\n\n' + text : text;
            $reply.val(next).trigger('input');
            setTimeout(function () {
                closeAllModals();
                $reply.trigger('focus');
                var el = $reply.get(0);
                if (el && el.setSelectionRange) { el.setSelectionRange(next.length, next.length); }
            }, 0);
            toast('success', 'Añadido a la respuesta del ticket.');
        } else {
            copyText(text);
        }
    });

    // Los modales del inbox hablan del "chat": aquí el texto va a la respuesta.
    var LABELS = [
        [/^Insertar (.+) en el chat$/i, 'Insertar $1 en la respuesta'],
        [/^Insertar en el chat$/i, 'Insertar en la respuesta'],
        [/^Enviar seguimiento al chat$/i, 'Añadir seguimiento a la respuesta'],
        [/^Enviar por el chat$/i, 'Añadir a la respuesta'],
        [/^Avisar al cliente en el chat$/i, 'Añadir aviso a la respuesta'],
        [/^Recomendar en (el )?chat$/i, 'Añadir recomendación a la respuesta'],
        [/^Enviar (.+) al chat$/i, 'Añadir $1 a la respuesta'],
    ];

    function relabel(root) {
        $(root).find('button').each(function () {
            if (this.children.length > 1) { return; }
            var text = $.trim($(this).text());
            if (!/chat/i.test(text)) { return; }
            for (var i = 0; i < LABELS.length; i++) {
                if (LABELS[i][0].test(text)) {
                    var next = text.replace(LABELS[i][0], LABELS[i][1]);
                    if (this.children.length === 1) {
                        $(this).contents().filter(function () { return this.nodeType === 3 && $.trim(this.nodeValue); }).last().replaceWith(' ' + next);
                    } else {
                        $(this).text(next);
                    }
                    return;
                }
            }
        });
    }

    var relabelQueued = false;
    function queueRelabel() {
        if (relabelQueued) { return; }
        relabelQueued = true;
        setTimeout(function () {
            relabelQueued = false;
            $('.bv-modal').each(function () { relabel(this); });
        }, 60);
    }

    // Modales Bootstrap del tab de la tienda (lista de pedidos): llegan por
    // AJAX con cada cliente, se reetiquetan al mostrarse.
    $(document).on('show.bs.modal', '.erc-tkt-moved', function () { relabel(this); });

    $(function () {
        queueRelabel();
        if (window.MutationObserver) {
            $('.bv-modal').each(function () {
                new MutationObserver(queueRelabel).observe(this, { childList: true, subtree: true });
            });
        }
    });
})(window.jQuery);
