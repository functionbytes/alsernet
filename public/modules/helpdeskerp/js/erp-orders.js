/*!
 * HelpdeskErp · modal "Pedidos ERP" (bv-modal erp-orders).
 *
 * Lista completa de pedidos del cliente en Gestión:
 *   - chips por estado con contador y búsqueda por nº/observaciones/origen/
 *     almacén/catálogo (descripciones del manager, código como respaldo), sobre
 *     los pedidos ya cargados (la lista del manager solo trae cabeceras);
 *   - filtro de fechas en servidor: ErpChat.section('orders', {from, to});
 *   - "Cargar más" por offset;
 *   - estados loading (escaneo Oracle ~35 s: recarga sola hasta 3 veces o al
 *     recibir erp:orders-ready), blocked, down, unlinked…
 * Cada fila es [data-erp-order-open]: el workspace lo abre erp-chat.js; aquí
 * solo se cierra este modal para no apilar uno sobre otro.
 *
 * Se abre con cualquier [data-erp-open="orders"] (contrato de atributos).
 * Solo lectura. Depende de window.ErpChat (erp-chat.js, cargado antes).
 *
 * Fuente: modules/HelpdeskErp/public/js/ — copiar a public/modules/helpdeskerp/js/ tras editar.
 */
(function ($) {
    'use strict';

    if (!$ || window.__ercOrdersLoaded) { return; }
    window.__ercOrdersLoaded = true;

    var NAME = 'erp-orders';
    var PAGE = 20;
    var MAX_AUTO = 3;
    var ORIGINS = { 4: 'Internet' };

    var st = freshState();

    function freshState() {
        return {
            cid: null,
            items: [],
            offset: 0,
            hasMore: false,
            from: '',
            to: '',
            filter: 'all',
            term: '',
            token: 0,
            loading: false,
            lastState: null,
            autoTries: 0,
            timer: null,
        };
    }

    function E() { return window.ErpChat || null; }
    function $modal() { return $('[data-bv-modal-name="' + NAME + '"]'); }
    function $body() { return $('#ercOrdBody'); }
    function isOpen() { return $modal().hasClass('on'); }

    /* ── Abrir / cerrar ──────────────────────────────────────────── */

    function showModal(name) {
        if (window.HDCommerce && typeof window.HDCommerce.open === 'function') {
            window.HDCommerce.open(name);
            return;
        }
        $('[data-bv-modal-name="' + name + '"]').addClass('on');
        $('body').css('overflow', 'hidden');
        $(document).trigger('bv:modal:open', [name]);
    }

    function hideModal(name) {
        if (window.HDCommerce && typeof window.HDCommerce.close === 'function') {
            window.HDCommerce.close(name);
            return;
        }
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if (!$('.bv-modal.on').length) { $('body').css('overflow', ''); }
    }

    function customerName() {
        var C = E();
        var resp = C ? C.cachedOverview() : null;
        var s = resp && resp.data && resp.data.sections ? resp.data.sections.summary : null;
        if (s && s.state === 'ok' && s.data) {
            var n = [s.data.label, s.data.surnames].filter(Boolean).join(' ').trim();
            if (n) { return n; }
        }
        return String($('.bv-right').first().data('customer-name') || '').trim();
    }

    function open() {
        var C = E();
        if (!C) { return; }

        // Nunca un modal sobre otro: se cierran los demás modales de Gestión.
        $('.bv-modal.on[data-bv-modal-name^="erp-"]').each(function () {
            var n = String($(this).attr('data-bv-modal-name') || '');
            if (n && n !== NAME) { hideModal(n); }
        });

        clearTimeout(st.timer);
        st = freshState();
        st.cid = C.customerId();

        var name = customerName();
        $('#ercOrdTitle').text(name ? 'Pedidos de ' + name : 'Pedidos en Gestión');
        $('#ercOrdCount').text('');
        $('#ercOrdSearch').val('');
        $('#ercOrdFrom').val('');
        $('#ercOrdTo').val('');
        $('[data-erc-ord-dates-clear]').addClass('erc-hidden');
        $('#ercOrdChips').empty();
        $('[data-erc-ord-summary]').prop('disabled', true);

        showModal(NAME);
        load(true);
    }

    function close() {
        clearTimeout(st.timer);
        hideModal(NAME);
    }

    /* ── Datos ───────────────────────────────────────────────────── */

    function load(reset) {
        var C = E();
        if (!C) { return; }
        var cid = C.customerId();
        if (!cid) {
            $body().html(C.stateHtml('nocustomer'));
            return;
        }

        clearTimeout(st.timer);
        st.token += 1;
        var token = st.token;
        st.cid = cid;
        st.loading = true;

        if (reset) {
            st.items = [];
            st.offset = 0;
            st.hasMore = false;
            $('#ercOrdChips').empty();
            $body().html(C.skeleton(3) + '<div class="erc-loading">Cargando pedidos…</div>');
        } else {
            $body().find('[data-erc-ord-more]').prop('disabled', true).text('Cargando…');
        }

        var params = { limit: PAGE, offset: st.offset };
        if (st.from) { params.from = st.from; }
        if (st.to) { params.to = st.to; }

        C.section('orders', params).then(function (r) {
            if (token !== st.token || C.customerId() !== cid) { return; }
            st.loading = false;
            st.lastState = r.state;

            if (r.state === 'ok') {
                var arr = Array.isArray(r.data) ? r.data : [];
                var seen = {};
                st.items.forEach(function (o) { seen[String(o.id)] = true; });
                arr.forEach(function (o) {
                    if (o && !seen[String(o.id)]) { st.items.push(o); seen[String(o.id)] = true; }
                });
                st.offset += arr.length;
                st.hasMore = r.pagination ? !!r.pagination.has_more : arr.length >= PAGE;
                if (!arr.length) { st.hasMore = false; }
                st.autoTries = 0;
                render();
                return;
            }

            if (r.state === 'loading') {
                renderLoading(r);
                return;
            }

            // Fallo al pedir la página siguiente: se conserva lo cargado.
            if (!reset && st.items.length) {
                C.toast('warning', r.message || 'No se pudieron cargar más pedidos.');
                render();
                return;
            }

            var stObj = $.extend({}, r);
            if (stObj.state === 'down') { stObj.retry = 'erp-orders'; }
            $body().html(C.stateHtml(stObj, 'Pedidos'));
            $('#ercOrdCount').text('');
            $('[data-erc-ord-summary]').prop('disabled', true);
        });
    }

    function renderLoading(r) {
        var C = E();
        var exhausted = st.autoTries >= MAX_AUTO;
        $('#ercOrdChips').empty();
        $('[data-erc-ord-summary]').prop('disabled', true);
        $body().html(
            '<div class="erc-state erc-state--loading">' +
                '<i class="fas fa-spinner fa-spin"></i>' +
                '<div class="body">' +
                    '<span class="t">Buscando pedidos en Gestión…</span>' +
                    '<span class="s">' + (exhausted
                        ? 'Oracle está tardando más de lo normal.'
                        : 'La consulta a Oracle tarda unos segundos. La lista se cargará sola.') + '</span>' +
                '</div>' +
                (exhausted ? '<button type="button" class="erc-link-btn" data-erp-retry="erp-orders">Buscar de nuevo</button>' : '') +
            '</div>' +
            C.skeleton(2)
        );
        if (exhausted) { return; }

        st.autoTries += 1;
        var wait = Math.max(5, parseInt(r.retry_after, 10) || 35) * 1000;
        st.timer = setTimeout(function () {
            if (isOpen() && st.lastState === 'loading') { load(true); }
        }, wait);
    }

    /* ── Pintado ─────────────────────────────────────────────────── */

    // Origen, almacén y catálogo: descripción del manager (*_description) y,
    // sin ella, el código como respaldo ("Almacén 6").
    function orderOrigin(o) {
        if (o.origin == null || o.origin === '') { return ''; }
        var C = E();
        if (typeof o.origin === 'object') { return C.codeLabel(o.origin.description, o.origin.id, 'Origen'); }
        var code = String(o.origin).trim();
        return C.codeLabel(o.origin_description || ORIGINS[code], code, 'Origen');
    }

    function orderWarehouse(o) { return E().codeLabel(o.warehouse_description, o.warehouse, 'Almacén'); }

    function orderCatalog(o) { return E().codeLabel(o.catalog_description, o.catalog, 'Catálogo'); }

    function orderAmount(o) {
        var C = E();
        var keys = ['total', 'amount', 'total_with_taxes', 'lines_total'];
        for (var i = 0; i < keys.length; i++) {
            var v = C.num(o[keys[i]]);
            if (v !== null) { return v; }
        }
        return null;
    }

    function statusLabel(o) { return E().statusInfo(o.status, o.status_description).label; }

    function matches(o) {
        if (st.filter !== 'all' && statusLabel(o) !== st.filter) { return false; }
        if (!st.term) { return true; }
        var hay = [o.number, o.order_id, o.id, o.observations, orderOrigin(o), orderWarehouse(o), orderCatalog(o)]
            .filter(function (v) { return v != null && v !== ''; }).join(' ').toLowerCase();
        return hay.indexOf(st.term) !== -1;
    }

    function sorted() {
        var C = E();
        return st.items.slice().sort(function (a, b) {
            var da = C.parseDate(a.date);
            var db = C.parseDate(b.date);
            return (db ? db.getTime() : 0) - (da ? da.getTime() : 0);
        });
    }

    function rowHtml(o) {
        var C = E();
        var ref = o.number || o.order_id || o.id || '—';
        var meta = [C.date(o.date, true), orderOrigin(o), orderWarehouse(o), orderCatalog(o)];
        if (o.served_date) {
            meta.push('servido ' + C.date(o.served_date, true));
        } else if (o.expected_date) {
            meta.push('previsto ' + C.date(o.expected_date, true));
        }
        var obs = o.observations ? String(o.observations).replace(/\s+/g, ' ').trim() : '';
        if (obs.length > 110) { obs = obs.substring(0, 110) + '…'; }
        var amt = orderAmount(o);

        return '<div class="erc-item erc-item--row" role="button" tabindex="0" data-erp-order-open="' + C.escAttr(o.id) + '">' +
            '<span class="ic"><i class="fas fa-clipboard-list"></i></span>' +
            '<span class="info">' +
                '<span class="top"><span class="ref">#' + C.esc(ref) + '</span>' + C.render.statusPill(o.status, o.status_description) + '</span>' +
                (obs ? '<span class="t erc-item-obs">' + C.esc(obs) + '</span>' : '') +
                '<span class="m">' + C.esc(meta.filter(Boolean).join(' · ')) + '</span>' +
            '</span>' +
            '<span class="end">' +
                (amt !== null ? '<span class="amt">' + C.esc(C.money(amt)) + '</span>' : '') +
                '<span class="act">Abrir</span>' +
            '</span>' +
        '</div>';
    }

    function chipsHtml() {
        var C = E();
        var counts = {};
        var order = [];
        st.items.forEach(function (o) {
            var l = statusLabel(o);
            if (!counts[l]) { counts[l] = 0; order.push(l); }
            counts[l] += 1;
        });
        if (st.filter !== 'all' && !counts[st.filter]) { st.filter = 'all'; }
        var chips = [['all', 'Todos', st.items.length]].concat(order.map(function (l) { return [l, l, counts[l]]; }));
        if (chips.length <= 2 && st.filter === 'all') {
            // Un solo estado: los chips no filtran nada, basta el contador.
            return '';
        }
        return chips.map(function (c) {
            return '<button type="button" class="erc-chip' + (st.filter === c[0] ? ' is-on' : '') + '" data-erc-ord-filter="' + C.escAttr(c[0]) + '">' +
                C.esc(c[1]) + '<span class="n">' + C.esc(c[2]) + '</span></button>';
        }).join('');
    }

    function rangeText() {
        var C = E();
        if (st.from && st.to) { return 'del ' + C.date(st.from, true) + ' al ' + C.date(st.to, true); }
        if (st.from) { return 'desde el ' + C.date(st.from, true); }
        if (st.to) { return 'hasta el ' + C.date(st.to, true); }
        return '';
    }

    function summaryHtml(visible) {
        var C = E();
        var total = st.items.length;
        var parts = ['<b>' + C.esc(total) + (st.hasMore ? '+' : '') + '</b> ' + (total === 1 && !st.hasMore ? 'pedido' : 'pedidos')];
        var range = rangeText();
        if (range) { parts.push(C.esc(range)); }
        if (visible.length !== total) { parts.push(C.esc(visible.length + ' con este filtro')); }

        var amounts = visible.map(orderAmount).filter(function (v) { return v !== null; });
        if (amounts.length) {
            parts.push('importe ' + C.esc(C.money(amounts.reduce(function (a, b) { return a + b; }, 0))));
        }
        var last = sorted()[0];
        if (last && last.date) { parts.push('último ' + C.esc(C.relative(last.date))); }

        return '<div class="erc-ord-summary">' + parts.join(' · ') + '</div>' +
            (st.hasMore && (st.filter !== 'all' || st.term)
                ? '<div class="erc-note erc-note--info"><span class="txt">El filtro y la búsqueda se aplican a los pedidos cargados. Carga más para buscar en los anteriores.</span></div>'
                : '');
    }

    function render() {
        var C = E();
        $('#ercOrdCount').text(st.items.length ? String(st.items.length) + (st.hasMore ? '+' : '') : '0');

        if (!st.items.length) {
            $('#ercOrdChips').empty();
            $('[data-erc-ord-summary]').prop('disabled', true);
            $body().html(C.stateHtml({
                state: 'empty',
                icon: 'fas fa-clipboard-list',
                message: (st.from || st.to) ? 'No hay pedidos en esas fechas.' : 'Este cliente no tiene pedidos en Gestión.',
            }, 'Sin pedidos'));
            return;
        }

        $('#ercOrdChips').html(chipsHtml());

        var visible = sorted().filter(matches);
        var list = visible.length
            ? '<div class="erc-list">' + visible.map(rowHtml).join('') + '</div>'
            : C.stateHtml({ state: 'empty', icon: 'fas fa-magnifying-glass', message: 'Prueba con otro número u otro estado.' }, 'Sin resultados');

        $body().html(
            summaryHtml(visible) +
            list +
            (st.hasMore
                ? '<div class="erc-more"><button type="button" class="erc-btn erc-btn--outline erc-btn--sm" data-erc-ord-more>Cargar más</button></div>'
                : '')
        );
        $('[data-erc-ord-summary]').prop('disabled', !visible.length);
    }

    /* ── Eventos ─────────────────────────────────────────────────── */

    $(document).on('click', '[data-erp-open="orders"]', function (e) {
        e.preventDefault();
        open();
    });

    // Al abrir un pedido se cierra la lista (el workspace lo abre erp-chat.js,
    // que escucha el mismo clic; aquí no se previene nada).
    $(document).on('click', '#ercOrdBody [data-erp-order-open]', function () {
        close();
    });

    $(document).on('keydown', '#ercOrdBody .erc-item[role="button"]', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') { return; }
        e.preventDefault();
        this.click();
    });

    $(document).on('click', '[data-erc-ord-filter]', function () {
        st.filter = String($(this).attr('data-erc-ord-filter') || 'all');
        render();
    });

    $(document).on('input', '#ercOrdSearch', function () {
        st.term = String($(this).val() || '').trim().toLowerCase();
        if (st.items.length) { render(); }
    });

    $(document).on('click', '[data-erc-ord-more]', function () {
        if (st.loading) { return; }
        load(false);
    });

    $(document).on('click', '[data-erc-ord-dates]', function () {
        var C = E();
        var from = String($('#ercOrdFrom').val() || '');
        var to = String($('#ercOrdTo').val() || '');
        var re = /^\d{4}-\d{2}-\d{2}$/;
        if ((from && !re.test(from)) || (to && !re.test(to))) {
            C.toast('warning', 'Las fechas no son válidas.');
            return;
        }
        if (from && to && from > to) {
            C.toast('warning', 'La fecha "desde" no puede ser posterior a "hasta".');
            return;
        }
        st.from = from;
        st.to = to;
        st.filter = 'all';
        $('[data-erc-ord-dates-clear]').toggleClass('erc-hidden', !(from || to));
        load(true);
    });

    $(document).on('click', '[data-erc-ord-dates-clear]', function () {
        $('#ercOrdFrom').val('');
        $('#ercOrdTo').val('');
        $(this).addClass('erc-hidden');
        st.from = '';
        st.to = '';
        st.filter = 'all';
        load(true);
    });

    $(document).on('keydown', '#ercOrdFrom, #ercOrdTo', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); $('[data-erc-ord-dates]').trigger('click'); }
    });

    // "Reintentar" (sin conexión) o "Buscar de nuevo" (escaneo que no acaba).
    $(document).on('erp:retry', function (e, target) {
        if (target !== 'erp-orders' || !isOpen()) { return; }
        st.autoTries = 0;
        load(true);
    });

    // El escaneo de Oracle terminó: si el modal esperaba, recarga.
    $(document).on('erp:orders-ready', function (e, resp, cid) {
        if (!isOpen() || st.lastState !== 'loading' || String(cid) !== String(st.cid)) { return; }
        load(true);
    });

    $(document).on('click', '[data-erc-ord-summary]', function () {
        var C = E();
        var visible = sorted().filter(matches).slice(0, 5);
        if (!visible.length) { return; }
        var text = 'Estos son tus últimos pedidos:\n' + visible.map(function (o) {
            return '• Pedido ' + (o.number || o.order_id || o.id) + ' del ' + C.date(o.date, true) + ': ' + statusLabel(o).toLowerCase() +
                (o.served_date ? ' el ' + C.date(o.served_date, true) : '');
        }).join('\n');
        if (C.insert(text)) { close(); }
    });

    // Cambio de conversación con el modal abierto: la lista ya no es de este cliente.
    document.addEventListener('pane:loaded', function () {
        clearTimeout(st.timer);
        if (isOpen()) { close(); }
        st = freshState();
    });
})(window.jQuery);
