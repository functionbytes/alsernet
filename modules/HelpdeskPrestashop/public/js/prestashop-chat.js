/*!
 * HelpdeskPrestashop · piezas de "PrestaShop dentro del chat" que no viven
 * en el tab Tienda:
 *
 *  - Listado de pedidos del cliente (psOrdersModal): buscador, chips de
 *    estado con conteo que filtran en cliente, "Enviar resumen al chat".
 *  - Workspace de cliente (bv-modal ps-customer-workspace): menú de
 *    secciones + dashboard, ficha, pedidos, carrito, cupones, direcciones,
 *    devoluciones, mensajes de la tienda (pieza 15) y lista de deseos.
 *  - Avisos en vivo sobre el composer (pieza 12), a partir de ps.cart.updated.
 *  - Detección de referencia/EAN en el último mensaje del cliente (pieza 24).
 *
 * Todo lee el contexto ya cargado por right-panel-prestashop-tabs.js a
 * través de window.PscStore (una sola llamada al bridge). jQuery sin
 * frameworks; solo cambia clases, nunca estilos.
 *
 * Fuente en modules/HelpdeskPrestashop/public/js/ — copiar a
 * public/modules/helpdeskprestashop/js/ tras editar.
 */
(function () {
    var S = null;
    var MONTHS = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    var HINT_MAX = 2;
    var HINT_TTL = 5 * 60 * 1000;

    function store() { return S || (S = window.PscStore); }
    function esc(s) { return store().esc(s); }
    function escAttr(s) { return store().escAttr(s); }
    function money(n) { return store().money(n); }

    /* ── Listado de pedidos (psOrdersModal) ───────────────────── */

    var ORD_FILTERS = [
        { key: 'all', label: 'Todos' },
        { key: 'pending', label: 'Pendientes' },
        { key: 'prep', label: 'Preparándose' },
        { key: 'shipped', label: 'Enviados' },
        { key: 'closed', label: 'Cerrados' },
    ];
    var ordFilter = 'all';

    function ordKindFor(o) {
        var k = store().orderKind(o);
        return k === 'cancelled' ? 'closed' : k;
    }

    function ordRowHtml(o) {
        var P = store();
        var kind = P.orderKind(o);
        var lines = o.lines || [];
        var units = lines.reduce(function (s, l) { return s + (parseInt(l.quantity, 10) || 0); }, 0);
        var tr = (o.tracking || [])[0];
        var returned = lines.reduce(function (s, l) { return s + (parseInt(l.quantity_returned, 10) || 0); }, 0);
        var meta = [P.date(o.placed_at, true), units ? units + (units === 1 ? ' artículo' : ' artículos') : null];
        if (tr && tr.carrier_name) { meta.push(tr.carrier_name + (tr.tracking_number ? ' #' + tr.tracking_number : '')); }
        else if (o.payment_method) { meta.push(o.payment_method); }
        if (returned) { meta.push(returned + (returned === 1 ? ' devolución' : ' devoluciones')); }

        var act = (kind === 'shipped' && tr && tr.tracking_url)
            ? '<a class="act" href="' + escAttr(tr.tracking_url) + '" target="_blank" rel="noopener">Seguir envío</a>'
            : '<span class="act">Abrir</span>';

        return '<div class="psc-ord-row is-' + kind + '" data-ps-order-open data-order-id="' + escAttr(o.id) + '" data-ord-kind="' + ordKindFor(o) + '">' +
            '<div class="info">' +
                '<span class="psc-ord-top"><span class="ref">#' + esc(o.reference || o.id) + '</span>' +
                '<span class="psc-tag ' + P.orderKindTag(kind) + '">' + esc(P.orderStateName(o)) + '</span></span>' +
                (P.orderFirstLine(o) ? '<span class="t">' + esc(P.orderFirstLine(o)) + '</span>' : '') +
                '<span class="m">' + esc(meta.filter(Boolean).join(' · ')) + '</span>' +
            '</div>' +
            '<span class="psc-ord-end"><span class="amt">' + money(P.orderTotal(o)) + '</span>' + act + '</span>' +
        '</div>';
    }

    function renderOrdersModal() {
        var P = store();
        var ctx = P.ctx() || {};
        var orders = ctx.orders || [];
        var customer = ctx.customer || {};
        var name = [customer.firstname, customer.lastname].filter(Boolean).join(' ');
        $('#psOrdersModalTitle').text(name ? 'Pedidos de ' + name : 'Pedidos');
        $('#psOrdersModalCount').text(customer.orders_count || orders.length || '');

        var $body = $('#psOrdersModalBody');
        if (ctx.bridge === 'down' && !orders.length) {
            $('#psOrdChips').empty();
            $body.html(P.warnHtml('No se han podido cargar los pedidos. Inténtalo de nuevo.', true));
            return;
        }
        if (!orders.length) {
            $('#psOrdChips').empty();
            $body.html(P.emptyHtml('fa-box', 'Sin pedidos', 'Este cliente no ha comprado todavía'));
            return;
        }

        var counts = { all: orders.length };
        orders.forEach(function (o) { var k = ordKindFor(o); counts[k] = (counts[k] || 0) + 1; });
        $('#psOrdChips').html(ORD_FILTERS.filter(function (f) { return f.key === 'all' || counts[f.key]; }).map(function (f) {
            return '<button type="button" class="psc-chip' + (ordFilter === f.key ? ' is-on' : '') + '" data-psc-ord-filter="' + f.key + '">' +
                f.label + ' ' + (counts[f.key] || 0) + '</button>';
        }).join(''));

        var total = customer.orders_count || orders.length;
        $body.html(
            '<div class="psc-ord-list">' + orders.map(ordRowHtml).join('') + '</div>' +
            '<div class="psc-ord-noresults bv-hidden">' + P.emptyHtml('fa-magnifying-glass', 'Sin resultados', 'Prueba con otro número o producto') + '</div>' +
            (total > orders.length
                ? '<div class="psc-note psc-note--info">Mostrando los ' + orders.length + ' últimos de ' + total + '. Los anteriores están en el back-office.</div>'
                : '')
        );
        applyOrdersFilter();
    }

    function applyOrdersFilter() {
        var term = String($('#psOrdSearch').val() || '').trim().toLowerCase();
        var visible = 0;
        $('#psOrdersModalBody .psc-ord-row').each(function () {
            var $r = $(this);
            var okKind = ordFilter === 'all' || $r.data('ord-kind') === ordFilter;
            var okTerm = !term || $r.text().toLowerCase().indexOf(term) !== -1;
            $r.toggleClass('bv-hidden', !(okKind && okTerm));
            if (okKind && okTerm) { visible++; }
        });
        $('#psOrdersModalBody .psc-ord-noresults').toggleClass('bv-hidden', visible > 0);
    }

    window.openPsOrdersModal = function () {
        ordFilter = 'all';
        $('#psOrdSearch').val('');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('psOrdersModal')).show();
        $('#psOrdersModalBody').html('<div class="psc-skel"></div><div class="psc-skel"></div><div class="psc-loading">Cargando pedidos…</div>');
        store().load(false, renderOrdersModal);
    };

    $(document).on('click', '[data-psc-open-orders]', function () { window.openPsOrdersModal(); });

    $(document).on('click', '[data-psc-ord-filter]', function () {
        ordFilter = $(this).data('psc-ord-filter');
        $('[data-psc-ord-filter]').removeClass('is-on');
        $(this).addClass('is-on');
        applyOrdersFilter();
    });

    $(document).on('input', '#psOrdSearch', applyOrdersFilter);

    // "Seguir envío" abre la web del transportista sin abrir el pedido.
    $(document).on('click', '#psOrdersModalBody .psc-ord-row a.act', function (e) { e.stopPropagation(); });

    $(document).on('click', '#psOrdSummary', function () {
        var P = store();
        var orders = ((P.ctx() || {}).orders || []).filter(function (o) {
            return !$('#psOrdersModalBody .psc-ord-row[data-order-id="' + o.id + '"]').hasClass('bv-hidden');
        }).slice(0, 5);
        if (!orders.length) { return; }
        P.insert('Estos son tus últimos pedidos:\n' + orders.map(function (o) {
            return '• #' + (o.reference || o.id) + ' — ' + P.orderStateName(o) + ' — ' + money(P.orderTotal(o));
        }).join('\n'));
        bootstrap.Modal.getInstance(document.getElementById('psOrdersModal')).hide();
    });

    /* ── Workspace de cliente (bv-modal ps-customer-workspace) ── */

    var wsPane = 'dashboard';

    function wsNavItem(key, icon, title, sub) {
        return '<button type="button" class="ps-ws-item' + (wsPane === key ? ' is-on' : '') + '" data-ps-ws-item="' + key + '">' +
            '<span class="ic"><i class="fas ' + icon + '"></i></span>' +
            '<span class="ps-ws-item-body"><span class="t">' + esc(title) + '</span><span class="s">' + esc(sub) + '</span></span>' +
            '<i class="fas fa-chevron-right psc-chevron"></i>' +
        '</button>';
    }

    function renderWsNav(ctx) {
        var P = store();
        var c = ctx.customer || {};
        var orders = ctx.orders || [];
        var cart = P.cart();
        var available = P.vouchersAvailable();
        var openRmas = (ctx.returns || []).filter(function (r) { return P.returnKind(r.state_name) === 'open'; }).length;
        var ids = P.defaultAddressIds();
        var shipping = (ctx.addresses || []).filter(function (a) { return String(a.id) === String(ids.shipping); })[0];

        $('#psWsNav').html(
            '<div class="ps-ws-group"><span class="ps-ws-label">Cliente</span>' +
                wsNavItem('dashboard', 'fa-chart-simple', 'Dashboard', 'Resumen de actividad') +
                wsNavItem('profile', 'fa-id-card', 'Ficha', (c.group && c.group.name) || 'Datos de la cuenta') +
                // Secciones de extensiones (editar ficha, grupo, acceso, RGPD…):
                // van con el cliente; al final del menú quedaban bajo el pliegue.
                WS_EXTRA.map(function (p) { return wsNavItem(p.key, p.icon, p.title, typeof p.sub === 'function' ? p.sub(ctx) : (p.sub || '')); }).join('') +
            '</div>' +
            '<div class="ps-ws-group"><span class="ps-ws-label">Tienda</span>' +
                wsNavItem('orders', 'fa-box', 'Pedidos', orders.length ? (c.orders_count || orders.length) + ' · último ' + P.date(orders[0].placed_at, false) : 'Sin pedidos') +
                wsNavItem('cart', 'fa-cart-shopping', 'Carrito', cart ? ((cart.products_count || (cart.items || []).length) + ' artículos · ' + money(cart.totals && cart.totals.total)) : 'Sin carrito activo') +
                wsNavItem('vouchers', 'fa-tag', 'Cupones', available.length ? available.length + (available.length === 1 ? ' disponible' : ' disponibles') : 'Sin cupones disponibles') +
                wsNavItem('addresses', 'fa-location-dot', 'Direcciones', shipping ? 'Envío · ' + (shipping.alias || '') : ((ctx.addresses || []).length + ' guardadas')) +
                wsNavItem('returns', 'fa-rotate-left', 'Devoluciones', openRmas ? openRmas + ' abierta' + (openRmas === 1 ? '' : 's') : ((ctx.returns || []).length + ' en total')) +
            '</div>' +
            '<div class="ps-ws-group"><span class="ps-ws-label">Conversación</span>' +
                wsNavItem('messages', 'fa-comments', 'Mensajes de la tienda', (ctx.messages || []).length ? (ctx.messages.length + ' mensajes') : 'Sin mensajes') +
                wsNavItem('wishlist', 'fa-heart', 'Lista de deseos', (ctx.wishlist || []).length ? (ctx.wishlist.length + ' productos') : 'Vacía') +
            '</div>'
        );
    }

    function wsCard(title, meta, body) {
        return '<div class="psc-card">' +
            '<div class="psc-card-head">' + esc(title) + (meta ? '<span class="psc-meta">' + esc(meta) + '</span>' : '') + '</div>' +
            '<div class="psc-card-body">' + body + '</div>' +
        '</div>';
    }

    function wsRow(k, v, mono) {
        return '<div class="psc-row"><span class="k">' + esc(k) + '</span><span class="v' + (mono ? ' mono' : '') + '">' + v + '</span></div>';
    }

    // Gasto por mes de los últimos 6 meses con los pedidos que trae el
    // contexto (no hay endpoint de agregados en el bridge).
    function monthlyBars(orders) {
        var now = new Date();
        var buckets = [];
        for (var i = 5; i >= 0; i--) {
            var d = new Date(now.getFullYear(), now.getMonth() - i, 1);
            buckets.push({ y: d.getFullYear(), m: d.getMonth(), total: 0 });
        }
        orders.forEach(function (o) {
            var d = store().parse(o.placed_at);
            if (!d) { return; }
            buckets.forEach(function (b) {
                if (b.y === d.getFullYear() && b.m === d.getMonth()) { b.total += store().orderTotal(o); }
            });
        });
        var max = Math.max.apply(null, buckets.map(function (b) { return b.total; }));
        if (max <= 0) {
            var last = orders[0] && orders[0].placed_at;
            return store().emptyHtml('fa-chart-simple', 'Sin compras en los últimos 6 meses',
                last ? 'La última fue el ' + store().date(last, true) + '.' : null);
        }
        return '<div class="psc-bars">' + buckets.map(function (b) {
            var pct = max > 0 ? Math.max(4, Math.round((b.total / max) * 20) * 5) : 4;
            var top = max > 0 && b.total === max;
            return '<div class="col' + (top ? ' is-top' : '') + '" title="' + escAttr(money(b.total)) + '">' +
                '<span class="bar psc-h-' + pct + (top ? ' is-top' : (b.total > 0 ? ' is-mid' : '')) + '"></span>' +
                '<span class="lbl">' + MONTHS[b.m] + '</span></div>';
        }).join('') + '</div>';
    }

    function paneDashboard(ctx) {
        var P = store();
        var c = ctx.customer || {};
        var orders = ctx.orders || [];
        var count = c.orders_count || orders.length;
        var avg = count ? (c.ltv || 0) / count : 0;
        var pending = orders.filter(function (o) { return P.orderKind(o) === 'pending'; }).length;
        var last = c.last_order_at || (orders[0] && orders[0].placed_at);
        var cart = P.cart();
        var openRmas = (ctx.returns || []).filter(function (r) { return P.returnKind(r.state_name) === 'open'; }).length;
        var refunded = (ctx.refunds || []).reduce(function (s, x) { return s + P.refundAmount(x); }, 0);
        var available = P.vouchersAvailable();

        var kpis = '<div class="psc-kpis">' +
            '<div class="psc-kpi"><span class="l">Gasto total</span><span class="n">' + money(c.ltv || 0) + '</span><span class="d">desde ' + esc(P.date(c.registered_at, true)) + '</span></div>' +
            '<div class="psc-kpi"><span class="l">Pedidos</span><span class="n">' + count + '</span><span class="d' + (pending ? '' : ' is-good') + '">' + (pending ? pending + ' pendiente' + (pending === 1 ? '' : 's') + ' de pago' : 'sin pendientes') + '</span></div>' +
            '<div class="psc-kpi"><span class="l">Ticket medio</span><span class="n">' + money(avg) + '</span><span class="d">' + count + ' pedidos</span></div>' +
            '<div class="psc-kpi"><span class="l">Última compra</span><span class="n">' + esc(P.date(last, false)) + '</span><span class="d">' + esc(P.relative(last)) + '</span></div>' +
        '</div>';

        var state = wsRow('Carrito en vivo', cart ? money(cart.totals && cart.totals.total) : '—', true) +
            wsRow('Devoluciones abiertas', String(openRmas), true) +
            wsRow('Reembolsado', money(refunded), true) +
            wsRow('Cupones disponibles', String(available.length), true) +
            wsRow('Lista de deseos', (ctx.wishlist || []).length + ' productos', false);

        var activity = P.activity().slice(0, 6).map(function (e) {
            return '<div class="psc-act-row"><span class="ic' + (e.good ? ' is-good' : '') + '"><i class="fas ' + e.icon + '"></i></span>' +
                '<span><span class="t">' + esc(e.text) + '</span><span class="m">' + esc(P.relative(e.at)) + '</span></span></div>';
        }).join('') || P.emptyHtml(null, 'Sin movimientos', null);

        return kpis +
            wsCard('Gasto por mes', 'últimos 6 meses · ' + orders.length + ' pedidos cargados', monthlyBars(orders)) +
            '<div class="psc-split">' + wsCard('Estado actual', null, state) + wsCard('Últimos movimientos', null, activity) + '</div>';
    }

    function paneProfile(ctx) {
        var P = store();
        var c = ctx.customer || {};
        var adminUrl = $('#bv-ps-orders').data('ps-admin-url');
        var yes = function (b) { return b ? 'Sí' : 'No'; };
        var rows = wsRow('Email', esc(c.email || '—'), true) +
            (P.phone() ? wsRow('Teléfono', esc(P.phone()), true) : '') +
            wsRow('ID · grupo', 'PS-' + esc(c.id) + ' · ' + esc((c.group && c.group.name) || '—'), true) +
            wsRow('Cliente desde', esc(P.date(c.registered_at, true)), false) +
            (c.birthday && c.birthday !== '0000-00-00' ? wsRow('Cumpleaños', esc(P.date(c.birthday, false)), false) : '') +
            wsRow('Newsletter', yes(c.newsletter), false) +
            wsRow('Ofertas de socios', yes(c.optin), false) +
            wsRow('Cuenta', c.is_guest ? 'Invitado' : (c.active === false ? 'Desactivada' : 'Activa'), false) +
            (c.company ? wsRow('Empresa', esc(c.company) + (c.siret ? ' · ' + esc(c.siret) : ''), false) : '') +
            (parseFloat(c.outstanding_allow_amount) > 0 ? wsRow('Crédito B2B', money(c.outstanding_allow_amount) + ' · ' + (c.max_payment_days || 0) + ' días', true) : '');

        return wsCard('Cliente en PrestaShop', 'solo lectura', rows +
            '<div class="psc-note psc-note--info">El email no se edita aquí: es la clave de vinculación con el contacto.</div>' +
            (WS_PANES['account-edit'] ? '<button type="button" class="psc-btn psc-btn--primary" data-psc-open-customer="account-edit">Editar datos en PrestaShop</button>' : '') +
            (adminUrl && c.id
                ? '<a class="psc-btn psc-btn--outline" target="_blank" rel="noopener" href="' + escAttr(adminUrl + '/index.php?controller=AdminCustomers&viewcustomer&id_customer=' + c.id) + '">Abrir en el back-office</a>'
                : ''));
    }

    function paneOrders(ctx) {
        var orders = ctx.orders || [];
        if (!orders.length) { return store().emptyHtml('fa-box', 'Sin pedidos', 'Este cliente no ha comprado todavía'); }
        return wsCard('Pedidos realizados', String((ctx.customer || {}).orders_count || orders.length),
            orders.map(ordRowHtml).join('') +
            '<button type="button" class="psc-btn psc-btn--outline" data-psc-open-orders>Abrir listado con filtros</button>');
    }

    function paneCart(ctx) {
        var P = store();
        var cart = P.cart();
        if (!cart) { return P.emptyHtml('fa-cart-shopping', 'Sin carrito activo', 'El cliente no tiene ningún carrito abierto en la tienda'); }
        var t = cart.totals || {};
        var items = (cart.items || []).map(function (it) {
            return '<div class="ps-cart-line ps-cart-line--ro">' +
                '<span class="psc-thumb psc-thumb--md">' + (it.image_url ? '<img src="' + escAttr(it.image_url) + '" alt="" loading="lazy">' : 'foto') + '</span>' +
                '<span class="ps-cart-line-body"><span class="nm">' + esc(it.name) + '</span>' +
                '<span class="s">' + esc([it.reference, money(it.unit_price) + ' / ud'].filter(Boolean).join(' · ')) + '</span></span>' +
                '<span class="ps-cart-line-qty">×' + (parseInt(it.quantity, 10) || 1) + '</span>' +
                '<span class="ps-cart-line-amt">' + money(it.total_wt) + '</span>' +
            '</div>';
        }).join('');
        var vouchers = (cart.vouchers || []).map(function (v) { return '<span class="ps-cart-voucher-chip">' + esc(v.code || v.name) + '</span>'; }).join('');
        var addr = function (a, lbl) {
            return a ? wsRow(lbl, esc(a.name) + ' · ' + esc(a.line) + ' · ' + esc(a.city), false) : '';
        };
        return wsCard('Artículos del carrito', 'CART-#' + cart.id + ' · ' + P.relative(cart.updated_at), items) +
            '<div class="psc-split">' +
                wsCard('Totales', null,
                    wsRow('Subtotal', money(t.products != null ? t.products : t.total), true) +
                    (parseFloat(t.discounts) > 0 ? wsRow('Descuentos', '−' + money(t.discounts), true) : '') +
                    wsRow('Envío', money(t.shipping || 0), true) +
                    '<div class="psc-row psc-row--total"><span class="k">Total</span><span class="v">' + money(t.total) + '</span></div>') +
                wsCard('Cupones y direcciones', null,
                    (vouchers ? '<div class="ps-cart-vouchers-applied">' + vouchers + '</div>' : wsRow('Cupones aplicados', 'Ninguno', false)) +
                    addr(cart.delivery_address, 'Envío') + addr(cart.invoice_address, 'Facturación')) +
            '</div>' +
            '<button type="button" class="psc-btn psc-btn--primary" data-psc-open-cart>Gestionar carrito (cantidades, cupones, dirección)</button>';
    }

    function paneVouchers(ctx) {
        var vouchers = ctx.vouchers || [];
        var create = vchLimit() > 0 ? '<button type="button" class="psc-btn psc-btn--outline" data-psc-voucher-create>Crear vale de compensación</button>' : '';
        if (!vouchers.length) { return store().emptyHtml('fa-tag', 'Sin cupones', 'Este cliente no tiene cupones en PrestaShop') + create; }
        return wsCard('Cupones del cliente', String(vouchers.length), vouchers.map(function (v) {
            return store().voucherCardHtml(v, { showApply: true });
        }).join('') + create);
    }

    function paneAddresses(ctx) {
        var P = store();
        var addresses = ctx.addresses || [];
        if (!addresses.length) { return P.emptyHtml('fa-location-dot', 'Sin direcciones', 'Este cliente no tiene direcciones guardadas'); }
        var ids = P.defaultAddressIds();
        return wsCard('Direcciones guardadas', addresses.length + ' guardadas', addresses.map(function (a) {
            var kind = String(a.id) === String(ids.shipping) ? 'shipping' : (String(a.id) === String(ids.billing) ? 'billing' : null);
            return P.addressCardHtml(a, kind);
        }).join(''));
    }

    function paneReturns(ctx) {
        var returns = ctx.returns || [];
        if (!returns.length) { return store().emptyHtml('fa-rotate-left', 'Sin devoluciones', 'No hay devoluciones registradas en PrestaShop'); }
        return wsCard('Devoluciones', String(returns.length), returns.map(store().returnCardHtml).join(''));
    }

    // Pieza 15: hilos y notas de la tienda — evita responder dos veces por
    // canales distintos.
    function paneMessages(ctx) {
        var P = store();
        var messages = ctx.messages || [];
        if (!messages.length) { return P.emptyHtml('fa-comments', 'Sin mensajes en la tienda', 'El cliente no ha escrito por el formulario de contacto ni en sus pedidos'); }
        return wsCard('Hilos de la tienda', messages.length + ' mensajes', messages.map(function (m) {
            var text = String(m.message || '').replace(/<[^>]*>/g, '').trim();
            return '<div class="psc-note-item">' +
                '<div class="psc-note-item-hd"><b>' + esc(m.subject || 'Mensaje general') + '</b>' +
                    (m.status ? '<span class="psc-tag ' + (m.status === 'closed' ? 'psc-tag--closed' : 'psc-tag--pending') + '">' + esc(m.status === 'closed' ? 'Respondido' : m.status) + '</span>' : '') +
                    '<span class="by">' + esc(P.date(m.created_at, true)) + '</span></div>' +
                '<div class="txt">“' + esc(text.length > 280 ? text.slice(0, 280) + '…' : text) + '”</div>' +
                (m.order_id ? '<div class="psc-rma-acts"><button type="button" data-ps-order-open data-order-id="' + escAttr(m.order_id) + '">Ver pedido</button></div>' : '') +
            '</div>';
        }).join(''));
    }

    function paneWishlist(ctx) {
        var P = store();
        var items = ctx.wishlist || [];
        if (!items.length) { return P.emptyHtml('fa-heart', 'Lista de deseos vacía', 'Este cliente no tiene productos guardados'); }
        return wsCard('Lista de deseos', items.length + ' productos', items.slice(0, 12).map(function (p) {
            var qty = parseInt(p.quantity != null ? p.quantity : p.stock, 10);
            var stock = qty > 5 ? 'En stock' : (qty > 0 ? qty + ' uds' : 'Sin stock');
            return '<div class="ps-cart-line ps-cart-line--ro">' +
                '<span class="psc-thumb psc-thumb--md">' + (p.image ? '<img src="' + escAttr(p.image) + '" alt="" loading="lazy">' : 'foto') + '</span>' +
                '<span class="ps-cart-line-body"><span class="nm">' + esc(p.name) + '</span><span class="s">' + esc([p.reference || p.sku, stock].filter(Boolean).join(' · ')) + '</span></span>' +
                '<span class="ps-cart-line-amt">' + money(p.price_with_tax != null ? p.price_with_tax : p.price) + '</span>' +
            '</div>';
        }).join('') + '<button type="button" class="psc-btn psc-btn--primary" data-psc-open-wishlist>Enviar uno al chat</button>');
    }

    var WS_PANES = {
        dashboard: paneDashboard, profile: paneProfile, orders: paneOrders, cart: paneCart,
        vouchers: paneVouchers, addresses: paneAddresses, returns: paneReturns,
        messages: paneMessages, wishlist: paneWishlist,
    };
    // Secciones añadidas por extensiones (PscChat.registerPane).
    var WS_EXTRA = [];

    function renderWorkspace() {
        var P = store();
        var ctx = P.ctx() || {};
        var c = ctx.customer || {};
        if (!c.found) {
            $('#psWsNav').empty();
            $('#psWsPane').html(ctx.bridge === 'down'
                ? P.warnHtml('No se ha podido cargar la ficha del cliente.', true)
                : P.emptyHtml('fa-user-slash', 'Contacto sin cliente en PrestaShop', 'Vincúlalo desde el tab Tienda con “Buscar y vincular”.'));
            return;
        }
        var name = [c.firstname, c.lastname].filter(Boolean).join(' ');
        $('#psWsTitle').html(esc(name) + ' <span class="psc-ws-id">· PS-' + esc(c.id) + '</span>');
        renderWsNav(ctx);
        $('#psWsPane').html(WS_PANES[wsPane](ctx));
    }

    // API para extensiones (js/ext/*.js): añadir secciones al workspace de
    // cliente. def = { key, icon: 'fa-…', title, sub: string|fn(ctx), render: fn(ctx) → html }
    window.PscChat = window.PscChat || {};
    window.PscChat.registerPane = function (def) {
        if (!def || !def.key || typeof def.render !== 'function' || WS_PANES[def.key]) { return; }
        WS_PANES[def.key] = def.render;
        WS_EXTRA.push(def);
    };
    window.PscChat.openCustomer = function (pane) { window.openPsCustomerWorkspace(pane); };
    window.PscChat.rerenderCustomer = function () { if ($('[data-bv-modal-name="ps-customer-workspace"]').hasClass('on')) { renderWorkspace(); } };
    window.PscChat.card = wsCard;
    window.PscChat.row = wsRow;
    window.PscChat.orderRowHtml = ordRowHtml;

    window.openPsCustomerWorkspace = function (pane) {
        wsPane = WS_PANES[pane] ? pane : 'dashboard';
        window.HDCommerce.open('ps-customer-workspace');
        $('#psWsNav').html('<div class="psc-skel"></div><div class="psc-skel"></div>');
        $('#psWsPane').html('<div class="psc-skel"></div><div class="psc-loading">Cargando ficha…</div>');
        store().load(false, renderWorkspace);
    };

    $(document).on('click', '[data-psc-open-customer]', function () {
        window.openPsCustomerWorkspace($(this).data('psc-open-customer'));
    });

    $(document).on('click', '[data-ps-ws-item]', function () {
        wsPane = $(this).data('ps-ws-item');
        $('[data-ps-ws-item]').removeClass('is-on');
        $(this).addClass('is-on');
        $('#psWsPane').html(WS_PANES[wsPane](store().ctx() || {})).scrollTop(0);
    });

    $(document).on('click', '[data-psc-open-wishlist]', function () {
        window.HDCommerce.close('ps-customer-workspace');
        window.HDCommerce.open('ps-wishlist-send');
    });

    // Los botones que abren modales Bootstrap desde el workspace lo cierran
    // antes (evita el apilado de backdrops).
    $(document).on('click', '[data-bv-modal-name="ps-customer-workspace"] [data-psc-open-cart], [data-bv-modal-name="ps-customer-workspace"] [data-psc-open-orders]', function () {
        window.HDCommerce.close('ps-customer-workspace');
    });

    $(document).on('click', '#psWsSummary', function () {
        var P = store();
        var ctx = P.ctx() || {};
        var c = ctx.customer || {};
        if (!c.found) { return; }
        var parts = ['Resumen de tu cuenta: ' + (c.orders_count || 0) + ' pedidos'];
        var last = (ctx.orders || [])[0];
        if (last) { parts.push('el último, #' + (last.reference || last.id) + ', está «' + P.orderStateName(last) + '»'); }
        var available = P.vouchersAvailable();
        if (available.length) { parts.push('tienes ' + available.length + (available.length === 1 ? ' cupón disponible' : ' cupones disponibles') + ' (' + available.map(function (v) { return v.code; }).join(', ') + ')'); }
        P.insert(parts.join('; ') + '.');
        window.HDCommerce.close('ps-customer-workspace');
    });

    /* ── Vale de compensación (pieza 04) ───────────────────────── */

    function vchModal() { return $('[data-bv-modal-name="ps-voucher-create"]'); }
    function vchLimit() { return parseFloat(vchModal().data('limit')) || 0; }
    function vchAmount() { return Math.round((parseFloat(String($('#psVchAmount').val() || '').replace(',', '.')) || 0) * 100) / 100; }
    function vchDays() { return parseInt($('#psVchDays button.is-on').data('days'), 10) || 30; }
    function vchReason() { return $('input[name="psVchReason"]:checked'); }

    function vchRefresh() {
        var amount = vchAmount();
        var limit = vchLimit();
        var over = amount > limit;
        var valid = amount >= 1 && amount <= 500;
        $('#psVchCreate').toggleClass('bv-hidden', over).prop('disabled', !valid).toggleClass('is-disabled', !valid);
        $('#psVchApproval').toggleClass('bv-hidden', !(over && valid));
        var until = new Date(Date.now() + vchDays() * 86400000);
        var c = ((store().ctx() || {}).customer) || {};
        var name = [c.firstname, c.lastname].filter(Boolean).join(' ') || 'el cliente';
        $('#psVchPreview').html(over && valid
            ? 'Supera tu límite de <b>' + esc(money(limit)) + '</b>: se enviará una solicitud de aprobación como nota interna.'
            : 'Un código <b>GES-…</b> de ' + (valid ? '<b>' + esc(money(amount)) + '</b>' : 'importe fijo') + ', un solo uso, a nombre de ' + esc(name) +
              ', válido hasta el ' + esc(store().date(until.toISOString(), true)) + '. Se insertará en el chat sin enviarlo.');
    }

    window.openPsVoucherCreate = function () {
        if (vchLimit() <= 0) { if (window.toastr) { toastr.warning('No tienes permiso para crear vales.'); } return; }
        $('#psVchAmount').val('');
        $('#psVchError').addClass('bv-hidden');
        $('#psVchDays button').removeClass('is-on').first().addClass('is-on');
        $('input[name="psVchReason"]').first().prop('checked', true).trigger('change');
        window.HDCommerce.close('ps-customer-workspace');
        window.HDCommerce.open('ps-voucher-create');
        vchRefresh();
        setTimeout(function () { $('#psVchAmount').trigger('focus'); }, 50);
    };

    $(document).on('click', '[data-psc-voucher-create]', function () { window.openPsVoucherCreate(); });
    $(document).on('input', '#psVchAmount', vchRefresh);
    $(document).on('click', '#psVchDays button', function () {
        $('#psVchDays button').removeClass('is-on');
        $(this).addClass('is-on');
        vchRefresh();
    });
    $(document).on('change', 'input[name="psVchReason"]', function () {
        $('input[name="psVchReason"]').closest('.psc-radio-opt').removeClass('is-on');
        $(this).closest('.psc-radio-opt').addClass('is-on');
    });

    function vchError(msg) {
        $('#psVchError').removeClass('bv-hidden').find('.psc-note-txt').text(msg);
    }

    $(document).on('click', '#psVchCreate', function () {
        var customerId = window.HDCommerce.customerId();
        if (!customerId || $(this).is(':disabled')) { return; }
        var url = String(vchModal().data('url-template')).replace('__ID__', customerId);
        var $btn = $(this).prop('disabled', true).text('Creando…');
        $('#psVchError').addClass('bv-hidden');
        $.ajax({
            url: url, method: 'POST', dataType: 'json',
            data: {
                amount: vchAmount(), validity_days: vchDays(), reason: vchReason().val(),
                conversation_id: window.HDCommerce.conversationId() || '',
            },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': window.HDCommerce.csrf() },
        }).done(function (r) {
            var v = (r && r.data) || {};
            store().insert('Te hemos generado un vale de ' + money(v.amount) + ' con el código ' + v.code +
                ', válido hasta el ' + store().date(v.date_to, true) + '. Se aplica en tu próxima compra en la tienda.');
            if (window.toastr) { toastr.success('Vale ' + v.code + ' creado e insertado en el chat.'); }
            window.HDCommerce.close('ps-voucher-create');
            store().refresh();
        }).fail(function (xhr) {
            var j = xhr.responseJSON || {};
            if (j.needs_approval) { vchModal().data('limit', j.limit); vchRefresh(); }
            vchError(window.HDCommerce.errorMessage(xhr, 'No se ha podido crear el vale.'));
        }).always(function () { $btn.prop('disabled', false).text('Crear vale'); vchRefresh(); });
    });

    // Por encima del límite: la solicitud queda como nota interna de la
    // conversación (visible para supervisores), no se crea nada en la tienda.
    $(document).on('click', '#psVchApproval', function () {
        var sendUrl = $('.bv-composer').data('bv-send-url');
        if (!sendUrl) { vchError('Abre una conversación para pedir la aprobación.'); return; }
        var c = ((store().ctx() || {}).customer) || {};
        var body = '[PS] Solicitud de aprobación · vale de compensación de ' + money(vchAmount()) +
            ' para ' + ([c.firstname, c.lastname].filter(Boolean).join(' ') || 'el cliente') + (c.id ? ' (PS-' + c.id + ')' : '') +
            ' · motivo: ' + vchReason().closest('.psc-radio-opt').text().trim() + ' · validez ' + vchDays() + ' días.';
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: sendUrl, method: 'POST', dataType: 'json',
            data: { body: body, is_internal: 1, action: 'send' },
            headers: { 'X-CSRF-TOKEN': window.HDCommerce.csrf(), 'Accept': 'application/json' },
        }).done(function () {
            if (window.toastr) { toastr.success('Solicitud de aprobación guardada como nota interna.'); }
            window.HDCommerce.close('ps-voucher-create');
        }).fail(function (xhr) {
            vchError(window.HDCommerce.errorMessage(xhr, 'No se pudo guardar la solicitud.'));
        }).always(function () { $btn.prop('disabled', false); });
    });

    /* ── Avisos en vivo sobre el composer (pieza 12) ──────────── */

    var lastCartSnapshot = null;

    function cartSnapshot() {
        var cart = store().cart();
        var map = {};
        ((cart && cart.items) || []).forEach(function (it) {
            map[it.product_id + ':' + (it.attribute_id || 0)] = { name: it.name, qty: parseInt(it.quantity, 10) || 0 };
        });
        return map;
    }

    function describeCartChange(before, after) {
        var added = [];
        var removed = [];
        Object.keys(after).forEach(function (k) {
            var diff = after[k].qty - ((before[k] && before[k].qty) || 0);
            if (diff > 0) { added.push({ name: after[k].name, qty: diff }); }
        });
        Object.keys(before).forEach(function (k) {
            if (!after[k]) { removed.push(before[k].name); }
        });
        if (added.length) {
            return 'Acaba de añadir <b>' + added[0].qty + (added[0].qty === 1 ? ' ud' : ' uds') + '</b> de ' + esc(added[0].name) +
                (added.length > 1 ? ' y ' + (added.length - 1) + ' más' : '');
        }
        if (removed.length) { return 'Acaba de quitar ' + esc(removed[0]) + ' del carrito'; }
        return 'Acaba de actualizar su carrito';
    }

    function ensureHintStrip() {
        var $composer = $('.bv-composer').first();
        if (!$composer.length) { return null; }
        var $strip = $composer.prev('.psc-live-strip');
        if (!$strip.length) {
            $strip = $('<div class="psc-live-strip"></div>');
            $composer.before($strip);
        }
        return $strip;
    }

    function pushHint(html, actLabel, actAttr) {
        var $strip = ensureHintStrip();
        if (!$strip) { return; }
        var $hint = $('<div class="psc-live-hint psc-live-hint--toast">' +
            '<span class="dot"></span><span class="txt">' + html + '</span>' +
            (actLabel ? '<button type="button" class="act" ' + actAttr + '>' + esc(actLabel) + '</button>' : '') +
            '<button type="button" class="x" data-psc-hint-close aria-label="Descartar"><i class="fas fa-xmark"></i></button>' +
        '</div>');
        $strip.append($hint);
        var $all = $strip.children('.psc-live-hint');
        if ($all.length > HINT_MAX) { $all.first().remove(); }
        setTimeout(function () { $hint.remove(); }, HINT_TTL);
    }

    $(document).on('click', '[data-psc-hint-close]', function () { $(this).closest('.psc-live-hint').remove(); });

    // API para extensiones: aviso en vivo sobre el composer (máx. 2, se
    // descartan solos a los 5 min). html debe venir ya escapado.
    window.PscChat = window.PscChat || {};
    window.PscChat.pushHint = function (html, actLabel, actAttr) { pushHint(html, actLabel, actAttr || ''); };

    $(document).on('psc:cart-updated', function () {
        var before = lastCartSnapshot || {};
        // right-panel ya lanzó la recarga forzada; se espera a que llegue.
        store().load(false, function () {
            var after = cartSnapshot();
            lastCartSnapshot = after;
            pushHint(describeCartChange(before, after), 'Ver', 'data-psc-open-cart');
        });
    });

    function bindSnapshot() {
        if (!window.PscStore) { return; }
        store().onChange(function () { lastCartSnapshot = cartSnapshot(); });
    }

    /* ── Detección de referencia en el mensaje del cliente (24) ── */

    // Referencias de la tienda: C112972, WC101186-1, CACAIMB-9, N401959N-S…
    // (letras+dígitos, con al menos 3 dígitos) o un EAN de 8/13 dígitos.
    var REF_RE = /\b(?=[A-Z0-9-]*\d{3})(?=[A-Z0-9-]*[A-Z])[A-Z][A-Z0-9]{3,}(?:-[A-Z0-9]{1,4})?\b|\b\d{13}\b|\b\d{8}\b/g;
    var refChecked = {};

    function lastCustomerText() {
        var $b = $('.bv-msg.in .bv-bubble').last();
        if (!$b.length) { return ''; }
        return $b.clone().find('.bv-bubble-translation, .bv-bubble-reactions, .bv-bubble-meta').remove().end().text();
    }

    function detectReference() {
        if (!window.PscStore) { return; }
        var base = window.HDCommerce && window.HDCommerce.base();
        if (!base || !$('.bv-composer').length) { return; }
        var matches = (lastCustomerText().toUpperCase().match(REF_RE) || []).filter(function (r) {
            return !/^\d{9,12}$/.test(r);
        });
        var ref = matches[0];
        var key = (window.HDCommerce.conversationId() || '') + ':' + ref;
        $('.psc-ref-card').remove();
        if (!ref || refChecked[key] === false) { return; }

        $.ajax({
            url: base + '/ps/products', method: 'GET', dataType: 'json', data: { q: ref },
            headers: { 'Accept': 'application/json' },
        }).done(function (r) {
            var p = (r.products || [])[0];
            if (!p) { refChecked[key] = false; return; }
            renderRefCard(ref, p);
        });
    }

    function renderRefCard(ref, p) {
        var $strip = ensureHintStrip();
        if (!$strip) { return; }
        var stock = parseInt(p.stock != null ? p.stock : p.quantity, 10);
        var stockTxt = p.in_stock === false || stock === 0 ? 'Sin stock'
            : (stock > 99 ? 'Más de 99 en stock' : (stock > 0 ? stock + ' en stock' : 'En stock'));
        var price = p.price_with_tax != null ? p.price_with_tax : p.price;
        var $card = $('<div class="psc-ref-card">' +
            '<span class="psc-thumb psc-thumb--md">' + (p.image ? '<img src="' + escAttr(p.image) + '" alt="" loading="lazy">' : 'foto') + '</span>' +
            '<span class="psc-ref-body">' +
                '<span class="lbl">Referencia detectada · ' + esc(ref) + '</span>' +
                '<span class="t">' + esc(p.name) + '</span>' +
                '<span class="s">' + esc(stockTxt + (price != null ? ' · ' + money(price) : '')) + '</span>' +
            '</span>' +
            '<button type="button" class="act" data-psc-ref-insert title="Insertar stock y precio en la respuesta">Insertar</button>' +
            '<button type="button" class="x" data-psc-ref-close aria-label="Descartar"><i class="fas fa-xmark"></i></button>' +
        '</div>');
        $card.data('psc-product', p).data('psc-stock', stockTxt);
        $strip.append($card);
    }

    $(document).on('click', '[data-psc-ref-close]', function () { $(this).closest('.psc-ref-card').remove(); });

    $(document).on('click', '[data-psc-ref-insert]', function () {
        var $card = $(this).closest('.psc-ref-card');
        var p = $card.data('psc-product') || {};
        var price = p.price_with_tax != null ? p.price_with_tax : p.price;
        store().insert(p.name + (p.sku ? ' (' + p.sku + ')' : '') + ': ' + String($card.data('psc-stock')).toLowerCase() +
            (price != null ? ', ' + money(price) : '') + '.');
        $card.remove();
    });

    document.addEventListener('pane:loaded', function () {
        $('.psc-live-strip').remove();
        lastCartSnapshot = null;
        setTimeout(detectReference, 300);
    });
    window.addEventListener('inbox:incoming-message', function () { setTimeout(detectReference, 300); });

    $(function () {
        bindSnapshot();
        setTimeout(detectReference, 600);
    });
})();
