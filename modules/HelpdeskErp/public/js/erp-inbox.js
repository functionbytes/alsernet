/*!
 * HelpdeskErp · pestañas de Gestión del panel derecho del inbox
 * (Gestión, Finanzas y Fidelización).
 *
 * Todo sale de ErpChat.overview() (una sola ida al manager, en paralelo, con
 * caché en cliente y en servidor). Aquí solo se pinta: los modales (pedidos,
 * ficha, finanzas, fidelización) se abren por el contrato de atributos
 * [data-erp-open] / [data-erp-order-open], que implementa quien posee cada
 * modal.
 *
 * - El panel derecho se sustituye entero al cambiar de conversación: todo va
 *   delegado en document y se relee ErpChat.customerId() en cada acción.
 * - window.ErpChat (erp-chat.js) se lee siempre en tiempo de ejecución, nunca
 *   al cargar, así que el orden de los <script defer> no importa.
 * - Pedidos en carga (escaneo de Oracle, ~35 s): ErpChat escucha el evento en
 *   vivo y sondea hasta 3 veces; al terminar recarga SOLO los pedidos (el
 *   resto sale de la caché del servidor) y aquí se repinta con
 *   erp:overview-loaded y erp:orders-ready.
 * - Origen, almacén, catálogo y estado se pintan con la descripción que manda
 *   el manager (*_description); el código queda como respaldo.
 * - El ERP es SOLO LECTURA: no hay ninguna escritura salvo el reintento de
 *   búsqueda del cliente ([data-bv-erp-relink]), que no toca el ERP.
 *
 * Fuente: modules/HelpdeskErp/public/js/ — copiar a public/modules/helpdeskerp/js/ tras editar.
 */
(function ($) {
    'use strict';

    if (!$) { return; }
    // Idempotente: si el <script> se incluyera más de una vez, no re-registrar handlers.
    if (window.__hdErpInboxLoaded) { return; }
    window.__hdErpInboxLoaded = true;

    var COLLAPSE_KEY = 'erc.panel.collapse';
    var TABS = ['orders', 'finance', 'loyalty'];
    var PANEL_ORDERS = 5;
    var PANEL_MOVES = 5;
    var MAX_LOADING_ROUNDS = 4; // respuesta inicial + los 3 sondeos de ErpChat
    var ORIGINS = { 4: 'Internet' };
    var ALERT_LEVELS = { warn: 1, info: 1, good: 1 };

    var lastRendered = {};   // customerId -> resp pintada (evita repintar lo mismo)
    var loadingRounds = {};  // customerId -> nº de respuestas con pedidos en carga

    function E() { return window.ErpChat || null; }

    function $tab(key) { return $('.erc-panel[data-erc-tab="' + key + '"]'); }

    function $body(key) {
        var $t = $tab(key);
        var $b = $t.find('[data-erc-body]').first();
        return $b.length ? $b : $t;
    }

    function hasErpPanel() {
        return !!$('.bv-right').first().data('has-erp') && $tab('orders').length > 0;
    }

    function syncTabs() {
        if (typeof window.bvSyncRightTabVisibility === 'function') { window.bvSyncRightTabVisibility(); }
    }

    function sec(resp, key) {
        return resp && resp.data && resp.data.sections ? (resp.data.sections[key] || null) : null;
    }

    function isOk(s) { return !!(s && s.state === 'ok'); }

    function localName() {
        return String($('.bv-right').first().data('customer-name') || '').trim();
    }

    /* ── Plegables con recuerdo (localStorage, siempre con try/catch) ── */

    function readCollapse() {
        try { return JSON.parse(window.localStorage.getItem(COLLAPSE_KEY) || '{}') || {}; } catch (e) { return {}; }
    }

    function writeCollapse(state) {
        try { window.localStorage.setItem(COLLAPSE_KEY, JSON.stringify(state)); } catch (e) { /* sin persistencia */ }
    }

    function setOpen(key, open) {
        $('.erc-panel [data-erc-toggle="' + key + '"]').attr('aria-expanded', open ? 'true' : 'false');
        $('.erc-panel [data-erc-collapse="' + key + '"]').prop('hidden', !open);
    }

    function applyCollapse() {
        var state = readCollapse();
        Object.keys(state).forEach(function (key) { setOpen(key, !!state[key]); });
    }

    $(document).on('click', '.erc-panel [data-erc-toggle]', function (e) {
        e.preventDefault();
        var key = String($(this).attr('data-erc-toggle') || '');
        if (!key) { return; }
        var open = $(this).attr('aria-expanded') !== 'true';
        setOpen(key, open);
        var state = readCollapse();
        state[key] = open;
        writeCollapse(state);
    });

    /* ── Piezas comunes ──────────────────────────────────────────── */

    function cardHead(key, title, sub, count) {
        var C = E();
        return '<button type="button" class="erc-card-head erc-card-head--toggle" data-erc-toggle="' + C.escAttr(key) + '" aria-expanded="true">' +
            '<span class="erc-card-head-tt">' +
                '<span>' + C.esc(title) + '</span>' +
                (sub ? '<span class="s">' + C.esc(sub) + '</span>' : '') +
            '</span>' +
            (count != null && count !== '' ? '<span class="erc-count">' + C.esc(count) + '</span>' : '') +
            '<i class="fas fa-chevron-down erc-chevron' + (count != null && count !== '' ? ' erc-chevron--after' : ' erc-chevron--push') + '"></i>' +
        '</button>';
    }

    function card(key, title, sub, count, bodyHtml, footHtml) {
        return '<div class="erc-card">' +
            cardHead(key, title, sub, count) +
            '<div class="erc-collapse" data-erc-collapse="' + E().escAttr(key) + '">' +
                '<div class="erc-card-body">' + bodyHtml + '</div>' +
                (footHtml ? '<div class="erc-card-foot">' + footHtml + '</div>' : '') +
            '</div>' +
        '</div>';
    }

    // Estado de una sección con el "Reintentar" apuntando al resumen.
    function sectionState(s, label) {
        var st = $.extend({}, s || { state: 'down' });
        st.retry = 'overview';
        // Dentro de una tarjeta que ya lleva el título: el bloqueo no lo repite.
        return E().stateHtml(st, st.state === 'blocked' ? 'Bloqueado en Gestión' : label);
    }

    function healthHtml(resp) {
        var C = E();
        var at = (resp && resp.data && resp.data.fetched_at) || (resp && resp.fetched_at) || null;
        var down = resp && resp.data && resp.data.sections
            ? Object.keys(resp.data.sections).some(function (k) { var s = resp.data.sections[k]; return s && s.state === 'down'; })
            : false;
        return '<div class="erc-health' + (down ? ' erc-health--stale' : '') + '">' +
            '<span class="dot"></span>' +
            '<span class="txt">' + (down ? 'Gestión no responde en parte · ' : 'Datos de Gestión · ') + C.esc(at ? C.relative(at) : 'ahora') + '</span>' +
            '<button type="button" class="erc-link-btn" data-erc-refresh>Actualizar</button>' +
        '</div>';
    }

    function unlinkedHtml(resp) {
        var C = E();
        var url = String($tab('orders').attr('data-relink-url') || '');
        var html = C.stateHtml({ state: 'unlinked', message: (resp && resp.message) || 'Este cliente no está vinculado con Gestión.' });
        if (url) {
            html += '<div class="rsp-erp-missing" data-relink-url="' + C.escAttr(url) + '">' +
                '<div class="rsp-erp-missing-text">' +
                    '<i class="fa-solid fa-circle-question" aria-hidden="true"></i>' +
                    '<span>Si crees que sí está en Gestión, pide que se busque de nuevo.</span>' +
                '</div>' +
                '<div class="rsp-erp-missing-actions">' +
                    '<button type="button" class="rsp-erp-btn" data-bv-erp-relink>Reintentar</button>' +
                '</div>' +
            '</div>';
        }
        return html;
    }

    function fatalHtml(resp) {
        var r = resp || {};
        if (r.state === 'unlinked') { return unlinkedHtml(r); }
        return E().stateHtml({ state: r.state || 'down', message: r.message, reason: r.reason, retry: 'overview' });
    }

    function orderRef(o) { return o.number || o.order_id || o.id || '—'; }

    // Origen, almacén y catálogo: la descripción del manager
    // (origin_description…) y, sin ella, el código como respaldo.
    function orderOrigin(o) {
        if (o.origin == null || o.origin === '') { return ''; }
        var C = E();
        if (typeof o.origin === 'object') { return C.codeLabel(o.origin.description, o.origin.id, 'Origen'); }
        var code = String(o.origin).trim();
        return C.codeLabel(o.origin_description || ORIGINS[code], code, 'Origen');
    }

    function orderCatalog(o) {
        return E().codeLabel(o.catalog_description, o.catalog, 'Catálogo');
    }

    function sortByDateDesc(list, field) {
        var C = E();
        return list.slice().sort(function (a, b) {
            var da = C.parseDate(a[field]);
            var db = C.parseDate(b[field]);
            return (db ? db.getTime() : 0) - (da ? da.getTime() : 0);
        });
    }

    function orderRowHtml(o) {
        var C = E();
        var meta = [C.date(o.date, true), orderOrigin(o), orderCatalog(o)];
        if (o.served_date) { meta.push('servido ' + C.date(o.served_date, false)); }
        var obs = o.observations ? String(o.observations).replace(/\s+/g, ' ').trim() : '';
        if (obs.length > 70) { obs = obs.substring(0, 70) + '…'; }
        return '<div class="erc-item erc-item--row" role="button" tabindex="0" data-erp-order-open="' + C.escAttr(o.id) + '">' +
            '<span class="ic"><i class="fas fa-clipboard-list"></i></span>' +
            '<span class="info">' +
                '<span class="top"><span class="ref">#' + C.esc(orderRef(o)) + '</span>' + C.render.statusPill(o.status, o.status_description) + '</span>' +
                '<span class="m">' + C.esc(meta.filter(Boolean).join(' · ')) + '</span>' +
                (obs ? '<span class="m erc-item-obs">' + C.esc(obs) + '</span>' : '') +
            '</span>' +
            '<span class="end"><span class="act">Abrir</span></span>' +
        '</div>';
    }

    /* ── Pestaña Gestión ─────────────────────────────────────────── */

    function alertsHtml(alerts) {
        var C = E();
        var list = Array.isArray(alerts) ? alerts : [];
        if (!list.length) { return ''; }
        return '<div class="erc-alerts">' + list.slice(0, 4).map(function (a) {
            var level = ALERT_LEVELS[a.level] ? a.level : 'info';
            var act = '';
            var ac = a.action || null;
            if (ac && ac.open === 'order' && ac.order_id) {
                act = '<button type="button" class="act" data-erp-order-open="' + C.escAttr(ac.order_id) + '">Ver</button>';
            } else if (ac && ac.open) {
                act = '<button type="button" class="act" data-erp-open="' + C.escAttr(ac.open) + '"' +
                    (ac.pane ? ' data-erp-pane="' + C.escAttr(ac.pane) + '"' : '') + '>Ver</button>';
            }
            return '<div class="erc-alert erc-alert--' + level + '">' +
                '<span class="dot"></span><span class="txt">' + C.esc(a.text || '') + '</span>' + act +
            '</div>';
        }).join('') + '</div>';
    }

    function firstPhone(summary) {
        var phones = (summary && Array.isArray(summary.phones)) ? summary.phones : [];
        var p = phones.filter(function (x) { return x && x.number && x.available !== false; })[0] || phones[0];
        return p && p.number ? String(p.number) : '';
    }

    function customerCardHtml(resp) {
        var C = E();
        var d = resp.data || {};
        var s = sec(resp, 'summary');
        var orders = sec(resp, 'orders');
        var lp = sec(resp, 'loyalty_points');
        var bal = sec(resp, 'balance');
        var sm = isOk(s) ? (s.data || {}) : {};
        var name = [sm.label, sm.surnames].filter(Boolean).join(' ').trim() || localName() || 'Cliente';
        var meta = [];
        if (sm.cif) { meta.push('NIF ' + sm.cif); }
        if (sm.card) { meta.push('Tarjeta ' + sm.card); }

        var tags = '';
        if (isOk(s) && (sm.available === false || sm.available === 0 || sm.available === '0')) {
            tags += '<span class="erc-tag erc-tag--blocked">De baja</span>';
        }
        if (sm.category != null && sm.category !== '') {
            tags += '<span class="erc-tag erc-tag--closed">Categoría ' + C.esc(C.codeLabel(sm.category_description, sm.category)) + '</span>';
        }
        if (sm.lopd && sm.lopd.no_commercial_info) {
            tags += '<span class="erc-tag erc-tag--closed">Sin publicidad</span>';
        }

        var psId = (d.links && d.links.prestashop_customer_id) || sm.code_internet || '';
        var phone = firstPhone(sm);

        // Métricas
        var nOrders = '—';
        if (isOk(orders)) {
            var cnt = Array.isArray(orders.data) ? orders.data.length : 0;
            nOrders = String(cnt) + (orders.pagination && orders.pagination.has_more ? '+' : '');
        } else if (orders && orders.state === 'loading') {
            nOrders = '…';
        }
        var nPoints = isOk(lp) && lp.data ? String(C.num(lp.data.balance) == null ? '—' : C.num(lp.data.balance)) : '—';
        var third = { n: '—', l: 'Último pedido', cls: 'is-muted' };
        var pending = isOk(bal) && bal.data && bal.data.balance ? C.num(bal.data.balance.pending) : null;
        if (pending !== null && pending > 0) {
            third = { n: C.money(pending), l: 'Pendiente', cls: '' };
        } else if (isOk(orders) && Array.isArray(orders.data) && orders.data.length) {
            var last = sortByDateDesc(orders.data, 'date')[0];
            third = { n: C.relative(last.date), l: 'Último pedido', cls: 'is-good' };
        }

        return '<div class="erc-card"><div class="erc-card-body">' +
            '<div class="erc-cust">' +
                '<span class="erc-avatar">' + C.esc(C.initials(name)) + '</span>' +
                '<span class="erc-cust-body">' +
                    '<span class="nm">' + C.esc(name) + '</span>' +
                    (meta.length ? '<span class="s">' + C.esc(meta.join(' · ')) + '</span>' : '') +
                    (d.erp_id ? '<span class="id">Gestión #' + C.esc(d.erp_id) + '</span>' : '') +
                '</span>' +
            '</div>' +
            (tags ? '<div class="erc-cust-tags">' + tags + '</div>' : '') +
            (isOk(s) ? '' : sectionState(s, 'Ficha del cliente')) +
            (psId ? '<div class="erc-row"><span class="k">Cliente PrestaShop</span><span class="v mono">#' + C.esc(psId) + '</span></div>' : '') +
            (sm.email ? '<div class="erc-row"><span class="k">Email</span><span class="v mono">' + C.esc(sm.email) + '</span></div>' : '') +
            (phone ? '<div class="erc-row"><span class="k">Teléfono</span><span class="v mono">' + C.esc(phone) + '</span></div>' : '') +
            '<div class="erc-stats">' +
                '<div class="erc-stat"><span class="n">' + C.esc(nOrders) + '</span><span class="l">Pedidos</span></div>' +
                '<div class="erc-stat"><span class="n' + (nPoints === '—' ? ' is-muted' : ' is-good') + '">' + C.esc(nPoints) + '</span><span class="l">Puntos</span></div>' +
                '<div class="erc-stat"><span class="n' + (third.cls ? ' ' + third.cls : '') + '">' + C.esc(third.n) + '</span><span class="l">' + C.esc(third.l) + '</span></div>' +
            '</div>' +
            '<button type="button" class="erc-btn erc-btn--outline" data-erp-open="customer" data-erp-pane="summary">Ficha del cliente</button>' +
        '</div></div>';
    }

    function ordersLoadingHtml(cid) {
        var rounds = loadingRounds[cid] || 0;
        var exhausted = rounds >= MAX_LOADING_ROUNDS;
        return '<div class="erc-state erc-state--loading">' +
            '<i class="fas fa-spinner fa-spin"></i>' +
            '<div class="body">' +
                '<span class="t">Buscando pedidos en Gestión…</span>' +
                '<span class="s">' + (exhausted
                    ? 'Oracle está tardando más de lo normal.'
                    : 'La consulta a Oracle tarda unos segundos. La lista se actualizará sola.') + '</span>' +
            '</div>' +
            (exhausted ? '<button type="button" class="erc-link-btn" data-erp-retry="overview">Buscar de nuevo</button>' : '') +
        '</div>';
    }

    function ordersCardHtml(resp, cid) {
        var C = E();
        var o = sec(resp, 'orders');
        if (!o || o.state === 'forbidden') { return ''; }

        if (o.state === 'loading') {
            return card('orders', 'Pedidos en Gestión', 'Buscando…', '…', ordersLoadingHtml(cid));
        }
        if (!isOk(o)) {
            return card('orders', 'Pedidos en Gestión', '', '—', sectionState(o, 'Pedidos'));
        }

        var list = sortByDateDesc(Array.isArray(o.data) ? o.data : [], 'date');
        var more = o.pagination && o.pagination.has_more;
        var count = String(list.length) + (more ? '+' : '');
        if (!list.length) {
            return card('orders', 'Pedidos en Gestión', '', '0',
                C.stateHtml({ state: 'empty', icon: 'fas fa-clipboard-list', message: 'Este cliente no tiene pedidos en Gestión.' }, 'Sin pedidos'));
        }

        var sub = 'último ' + C.date(list[0].date, false);
        var body = '<div class="erc-list">' + list.slice(0, PANEL_ORDERS).map(orderRowHtml).join('') + '</div>';
        var foot = '<button type="button" class="erc-btn erc-btn--outline" data-erp-open="orders">Ver todos los pedidos</button>';
        return card('orders', 'Pedidos en Gestión', sub, count, body, foot);
    }

    function pickAddress(list) {
        var arr = (list || []).filter(Boolean);
        var active = arr.filter(function (a) { return a.available !== false && a.available !== 0 && a.available !== '0'; });
        return active.filter(function (a) { return a.default_shipping; })[0] ||
            active.filter(function (a) { return a.default_billing; })[0] ||
            active[0] || arr[0] || null;
    }

    function addressCardHtml(resp) {
        var C = E();
        var a = sec(resp, 'addresses');
        if (!a || a.state === 'forbidden') { return ''; }

        var summary = sec(resp, 'summary');
        var list = isOk(a) && a.data && Array.isArray(a.data.addresses) ? a.data.addresses : null;
        // Sin la sección de direcciones, la ficha resumida también trae las suyas.
        if (!list && isOk(summary) && summary.data && Array.isArray(summary.data.addresses)) { list = summary.data.addresses; }

        if (!list) { return card('addr', 'Dirección de envío', '', '', sectionState(a, 'Direcciones')); }
        if (!list.length) {
            return card('addr', 'Dirección de envío', '', '0',
                C.stateHtml({ state: 'empty', icon: 'fas fa-location-dot', message: 'Sin direcciones en Gestión.' }, 'Sin direcciones'));
        }

        var pick = pickAddress(list);
        var sub = pick && pick.default_shipping ? 'por defecto' : (list.length > 1 ? 'la primera activa' : '');
        var body = C.render.address(pick) +
            '<div class="erc-addr-actions">' +
                '<button type="button" class="erc-link" data-erc-copy-address>Copiar dirección</button>' +
                (list.length > 1 ? '<button type="button" class="erc-link" data-erp-open="customer" data-erp-pane="addresses">Ver las ' + list.length + '</button>' : '') +
            '</div>';
        return card('addr', 'Dirección de envío', sub, String(list.length), body);
    }

    function ordersTabHtml(resp, cid) {
        return alertsHtml(resp.data.alerts) +
            customerCardHtml(resp) +
            ordersCardHtml(resp, cid) +
            addressCardHtml(resp) +
            healthHtml(resp);
    }

    /* ── Pestaña Finanzas ────────────────────────────────────────── */

    function riskHtml(risk) {
        var C = E();
        if (!risk) { return ''; }
        var cur = C.num(risk.current);
        var max = C.num(risk.max_allowed);
        if (cur === null && max === null) { return ''; }
        if (max === null || max <= 0) {
            return '<div class="erc-row"><span class="k">Riesgo actual</span><span class="v mono">' + C.esc(C.money(cur)) + '</span></div>';
        }
        var pct = Math.max(0, (cur || 0) / max * 100);
        var mod = pct > 100 ? ' erc-risk--over' : (pct >= 70 ? ' erc-risk--mid' : '');
        return '<div class="erc-risk' + mod + '">' +
            '<div class="erc-risk-head"><span>Riesgo</span><b>' + C.esc(C.money(cur)) + '</b></div>' +
            '<span class="erc-risk-track"><span class="' + C.widthClass(Math.min(pct, 100)) + '"></span></span>' +
            '<span class="erc-risk-cap">' + C.esc(Math.round(pct) + ' % de ' + C.money(max) + ' permitidos') + '</span>' +
        '</div>';
    }

    function balanceCardHtml(resp) {
        var C = E();
        var b = sec(resp, 'balance');
        if (!b || b.state === 'forbidden') { return ''; }
        if (!isOk(b)) { return card('fin-balance', 'Saldo y riesgo', '', '', sectionState(b, 'Saldo y riesgo')); }

        var d = b.data || {};
        var bal = d.balance || {};
        var pending = C.num(bal.pending);
        var body = '<div class="erc-kpis erc-kpis--3">' +
                '<div class="erc-kpi"><span class="l">Facturado</span><span class="n">' + C.esc(C.money(bal.invoiced)) + '</span></div>' +
                '<div class="erc-kpi"><span class="l">Cobrado</span><span class="n">' + C.esc(C.money(bal.collected)) + '</span></div>' +
                '<div class="erc-kpi"><span class="l">Pendiente</span><span class="n">' + C.esc(C.money(pending)) + '</span>' +
                    (pending !== null && pending <= 0 ? '<span class="d is-good">Al corriente</span>' : '') + '</div>' +
            '</div>' +
            riskHtml(d.risk || (isOk(sec(resp, 'debts')) && sec(resp, 'debts').data ? sec(resp, 'debts').data.risk : null));
        var foot = '<button type="button" class="erc-btn erc-btn--outline" data-erp-open="finance" data-erp-pane="balance">Ver saldo</button>';
        return card('fin-balance', 'Saldo y riesgo', '', '', body, foot);
    }

    function debtsCardHtml(resp) {
        var C = E();
        var dsec = sec(resp, 'debts');
        if (!dsec || dsec.state === 'forbidden') { return ''; }
        if (!isOk(dsec)) { return card('fin-debts', 'Deudas pendientes', '', '', sectionState(dsec, 'Deudas')); }

        var d = dsec.data || {};
        var list = Array.isArray(d.debts) ? d.debts : [];
        var stats = (d.statistics && d.statistics.debts) || {};
        var total = C.num(stats.amount_total);
        if (total === null) { total = list.reduce(function (acc, x) { return acc + (C.num(x.amount) || 0); }, 0); }
        var count = stats.total != null ? stats.total : list.length;
        var balanceBlocked = !isOk(sec(resp, 'balance'));

        if (!list.length) {
            return card('fin-debts', 'Deudas pendientes', '', '0',
                '<div class="erc-note erc-note--good"><span class="txt">Sin deudas pendientes.</span></div>' +
                (balanceBlocked ? riskHtml(d.risk) : ''));
        }

        var rows = sortByDateDesc(list, 'delivery_date').slice(0, 3).map(function (x) {
            return '<div class="erc-item erc-item--row is-link" role="button" tabindex="0" data-erp-open="finance" data-erp-pane="debts">' +
                '<span class="ic"><i class="fas fa-file-invoice"></i></span>' +
                '<span class="info">' +
                    '<span class="top"><span class="ref">Albarán ' + C.esc(x.delivery_number || x.delivery_id || x.id || '—') + '</span></span>' +
                    '<span class="m">' + C.esc([x.delivery_date ? C.date(x.delivery_date, true) : '', x.payment_method || ''].filter(Boolean).join(' · ')) + '</span>' +
                '</span>' +
                '<span class="end"><span class="amt">' + C.esc(C.money(x.amount)) + '</span></span>' +
            '</div>';
        }).join('');
        var body = '<div class="erc-row"><span class="k">Total pendiente</span><span class="v mono">' + C.esc(C.money(total)) + '</span></div>' +
            (balanceBlocked ? riskHtml(d.risk) : '') +
            '<div class="erc-list">' + rows + '</div>';
        var foot = '<button type="button" class="erc-btn erc-btn--outline" data-erp-open="finance" data-erp-pane="debts">Ver deudas</button>';
        return card('fin-debts', 'Deudas pendientes', '', String(count), body, foot);
    }

    function financeLinksHtml() {
        var C = E();
        var fin = C.can('finance');
        var docs = fin || C.can('orders');
        var btns = [];
        if (fin) { btns.push(['invoices', 'Ver facturas']); }
        if (docs) { btns.push(['delivery-notes', 'Ver albaranes']); }
        if (docs) { btns.push(['returns', 'Ver devoluciones']); }
        if (fin) { btns.push(['payments', 'Ver cobros']); }
        if (!btns.length) { return ''; }
        return '<div class="erc-card"><div class="erc-card-body">' +
            '<span class="erc-sec-label">Documentos</span>' +
            '<div class="erc-foot erc-foot--2">' + btns.map(function (b) {
                return '<button type="button" class="erc-btn erc-btn--outline" data-erp-open="finance" data-erp-pane="' + b[0] + '">' + C.esc(b[1]) + '</button>';
            }).join('') + '</div>' +
        '</div></div>';
    }

    function financeTabHtml(resp) {
        var C = E();
        if (!C.can('finance') && !C.can('orders')) {
            return C.stateHtml({ state: 'forbidden' }, 'Finanzas');
        }
        return balanceCardHtml(resp) + debtsCardHtml(resp) + financeLinksHtml() + healthHtml(resp);
    }

    /* ── Pestaña Fidelización ────────────────────────────────────── */

    function pointsCardHtml(resp) {
        var C = E();
        var lp = sec(resp, 'loyalty_points');
        if (!lp || lp.state === 'forbidden') { return ''; }
        if (!isOk(lp)) { return card('loy-points', 'Puntos', '', '', sectionState(lp, 'Puntos')); }

        var d = lp.data || {};
        var moves = sortByDateDesc(Array.isArray(d.movements) ? d.movements : [], 'date');
        var balance = C.num(d.balance);
        var body = '<div class="erc-points">' +
                '<span class="n">' + C.esc(balance === null ? '—' : balance) + '</span>' +
                '<span class="l">' + C.esc('puntos' + (d.main_card ? ' · tarjeta ' + d.main_card : '')) + '</span>' +
            '</div>';

        if (moves.length) {
            body += '<span class="erc-sec-label">Últimos movimientos</span><div class="erc-timeline">' +
                moves.slice(0, PANEL_MOVES).map(function (m, i) {
                    var pts = C.num(m.points) || 0;
                    var label = pts >= 0 ? 'Puntos ganados' : 'Puntos canjeados';
                    var ref = m.delivery_id ? 'Albarán ' + m.delivery_id : '';
                    var shop = m.warehouse_description ? C.codeLabel(m.warehouse_description, m.warehouse) : '';
                    return '<div class="erc-tl-row' + (i === 0 ? ' is-current' : '') + '">' +
                        '<span class="dot"></span>' +
                        '<span class="body">' +
                            '<span class="t">' + C.esc(label) + '</span>' +
                            '<span class="m">' + C.esc([C.date(m.date, true), shop, ref].filter(Boolean).join(' · ')) + '</span>' +
                        '</span>' +
                        '<span class="v ' + (pts >= 0 ? 'is-good' : 'is-muted') + '">' + (pts >= 0 ? '+' : '−') + C.esc(Math.abs(pts)) + '</span>' +
                    '</div>';
                }).join('') + '</div>';
        } else {
            body += '<span class="erc-muted erc-small">Sin movimientos de puntos.</span>';
        }

        var foot = '<button type="button" class="erc-btn erc-btn--outline" data-erp-open="loyalty" data-erp-pane="points">Ver movimientos</button>';
        return card('loy-points', 'Puntos', moves.length ? 'último ' + C.date(moves[0].date, false) : '', '', body, foot);
    }

    function isPast(iso) {
        var d = E().parseDate(iso);
        if (!d) { return false; }
        var today = new Date();
        today.setHours(0, 0, 0, 0);
        return d.getTime() < today.getTime();
    }

    function soon(iso) {
        var d = E().parseDate(iso);
        if (!d) { return false; }
        return (d.getTime() - Date.now()) <= 7 * 86400000;
    }

    function isOff(v) { return v === false || v === 0 || v === '0'; }

    function vchHtml(kind, v) {
        var C = E();
        var code = kind === 'voucher' ? 'Vale ' + (v.voucher_id || v.id || '') : 'Bono ' + (v.id || '');
        var tag = v.valid_until && soon(v.valid_until)
            ? '<span class="erc-tag erc-tag--pending">Caduca pronto</span>'
            : '<span class="erc-tag erc-tag--progress">Vigente</span>';
        var until = v.valid_until ? 'hasta el ' + C.date(v.valid_until, true) : 'sin caducidad';
        var min = kind === 'bonus' && C.num(v.minimum_purchase) ? 'Compra mínima ' + C.money(v.minimum_purchase) : '';
        return '<div class="erc-vch erc-vch--available">' +
            '<div class="erc-vch-hd"><span class="erc-vch-code">' + C.esc(code) + '</span>' + tag + '</div>' +
            '<div class="erc-vch-value"><b>' + C.esc(C.money(v.amount)) + '</b><span>' + C.esc(until) + '</span></div>' +
            (min ? '<div class="erc-vch-min">' + C.esc(min) + '</div>' : '') +
        '</div>';
    }

    function vouchersCardHtml(resp) {
        var C = E();
        var vs = sec(resp, 'vouchers');
        var bs = sec(resp, 'bonuses');
        var vHidden = !vs || vs.state === 'forbidden';
        var bHidden = !bs || bs.state === 'forbidden';
        if (vHidden && bHidden) { return ''; }

        var foot = '<button type="button" class="erc-btn erc-btn--outline" data-erp-open="loyalty" data-erp-pane="vouchers">Ver bonos y vales</button>';

        // Las dos bloqueadas (hoy, sin GRANT): un único aviso ámbar.
        if (!isOk(vs) && !isOk(bs) && (vHidden || vs.state === 'blocked') && (bHidden || bs.state === 'blocked')) {
            return card('loy-vch', 'Vales y bonos vigentes', '', '', sectionState({ state: 'blocked' }, 'Vales y bonos'), foot);
        }

        var body = '';
        var active = [];
        if (!vHidden) {
            if (isOk(vs)) {
                ((vs.data && vs.data.vouchers) || []).forEach(function (v) {
                    if (isOff(v.available) || v.cancelled_at || isPast(v.valid_until)) { return; }
                    active.push(['voucher', v]);
                });
            } else {
                body += sectionState(vs, 'Vales');
            }
        }
        if (!bHidden) {
            if (isOk(bs)) {
                ((bs.data && bs.data.bonuses) || []).forEach(function (b) {
                    if (isOff(b.available) || b.consumed_at || isPast(b.valid_until)) { return; }
                    active.push(['bonus', b]);
                });
            } else {
                body += sectionState(bs, 'Bonos');
            }
        }

        active.sort(function (a, b) {
            var da = C.parseDate(a[1].valid_until);
            var db = C.parseDate(b[1].valid_until);
            return (da ? da.getTime() : Infinity) - (db ? db.getTime() : Infinity);
        });

        if (active.length) {
            body = active.slice(0, 4).map(function (x) { return vchHtml(x[0], x[1]); }).join('') + body;
        } else if (!body) {
            body = C.stateHtml({ state: 'empty', icon: 'fas fa-ticket', message: 'No tiene vales ni bonos vigentes.' }, 'Sin vales ni bonos');
        } else {
            body = '<span class="erc-muted erc-small">Sin vales ni bonos vigentes en lo que se ha podido consultar.</span>' + body;
        }
        return card('loy-vch', 'Vales y bonos vigentes', '', active.length ? String(active.length) : '', body, foot);
    }

    function loyaltyTabHtml(resp) {
        var C = E();
        if (!C.can('loyalty')) { return C.stateHtml({ state: 'forbidden' }, 'Fidelización'); }
        return pointsCardHtml(resp) + vouchersCardHtml(resp) + healthHtml(resp);
    }

    /* ── Orquestación ────────────────────────────────────────────── */

    function skeletonAll() {
        var C = E();
        if (!C) { return; }
        TABS.forEach(function (key) {
            $body(key).html(C.skeleton(1, 'card') + C.skeleton(2));
            $tab(key).removeAttr('data-erc-cid');
        });
    }

    // Deja la pestaña con un único .bv-tab-empty: el core
    // (syncRightTabVisibility) oculta entonces su botón. Para permisos que el
    // agente no tiene, no para fallos de Gestión.
    function hideTab(key) {
        $tab(key).html('<div class="bv-tab-empty"><i class="fas fa-user-lock"></i>' +
            '<div class="bv-tab-empty-title">Sin permiso</div>' +
            '<div class="bv-tab-empty-sub">No tienes permiso para ver esta sección de Gestión.</div></div>');
    }

    function renderAll(resp, cid) {
        var C = E();
        if (!C || !hasErpPanel() || !resp) { return; }
        if (String(C.customerId() || '') !== String(cid || '')) { return; }
        if (lastRendered[cid] === resp && $tab('orders').attr('data-erc-cid') === String(cid)) { return; }
        lastRendered[cid] = resp;

        var orders = sec(resp, 'orders');
        if (orders && orders.state === 'loading') {
            loadingRounds[cid] = (loadingRounds[cid] || 0) + 1;
        } else if (orders) {
            delete loadingRounds[cid];
        }

        if (resp.state === 'forbidden' && (!resp.data || !resp.data.sections)) {
            // Sin helpdeskerp.view: las tres pestañas desaparecen.
            TABS.forEach(function (key) { hideTab(key); });
        } else if (!resp.data || !resp.data.sections) {
            var fatal = fatalHtml(resp);
            TABS.forEach(function (key) { $body(key).html(fatal); });
        } else {
            $body('orders').html(ordersTabHtml(resp, cid));
            if (C.can('finance') || C.can('orders')) { $body('finance').html(financeTabHtml(resp)); } else { hideTab('finance'); }
            if (C.can('loyalty')) { $body('loyalty').html(loyaltyTabHtml(resp)); } else { hideTab('loyalty'); }
        }
        TABS.forEach(function (key) { $tab(key).attr('data-erc-cid', String(cid)); });
        applyCollapse();
        syncTabs();
    }

    function load(force) {
        var C = E();
        if (!C || !hasErpPanel()) { return; }
        var cid = C.customerId();
        if (!cid) { return; }

        if (!force) {
            var cached = C.cachedOverview(cid);
            if (cached) { renderAll(cached, cid); }
        }
        C.overview(!!force).then(function (resp) { renderAll(resp, cid); });
    }

    // Abrir una pestaña ERP: pinta lo que haya en caché y pide lo que falte.
    // Captura nativa: el core también escucha en captura para cambiar de
    // pestaña, y un listener en document sobrevive al cambio de conversación.
    document.addEventListener('click', function (e) {
        if (!$(e.target).closest('.bv-right-tab[data-bv-tab^="erp-"]').length) { return; }
        load(false);
    }, true);

    // Precarga: el escaneo de pedidos en Oracle tarda ~35 s, así que conviene
    // lanzarlo en cuanto se abre la conversación y no al pulsar la pestaña.
    function prewarm() {
        if (!hasErpPanel()) { return; }
        setTimeout(function () { load(false); }, 400);
    }

    $(prewarm);
    document.addEventListener('pane:loaded', function () { setTimeout(prewarm, 0); });

    $(document).on('erp:overview-loaded erp:orders-ready', function (e, resp, cid) {
        renderAll(resp, cid);
    });

    $(document).on('click', '.erc-panel [data-erc-refresh]', function (e) {
        e.preventDefault();
        var C = E();
        if (!C) { return; }
        $(this).prop('disabled', true).text('Actualizando…');
        var cid = C.customerId();
        C.overview(true).then(function (resp) {
            delete lastRendered[cid];
            renderAll(resp, cid);
        });
    });

    // "Reintentar" de un estado sin conexión o "Buscar de nuevo" de los
    // pedidos: ErpChat ya relanza overview(true); aquí solo se da respuesta
    // visual inmediata.
    $(document).on('erp:retry', function (e, target, $btn) {
        if (target !== 'overview' || !$btn || !$btn.closest('.erc-panel').length) { return; }
        $btn.prop('disabled', true).text('Buscando…');
    });

    $(document).on('click', '.erc-panel [data-erc-copy-address]', function (e) {
        e.preventDefault();
        var C = E();
        if (!C) { return; }
        var resp = C.cachedOverview();
        var a = sec(resp, 'addresses');
        var s = sec(resp, 'summary');
        var list = isOk(a) && a.data && Array.isArray(a.data.addresses) ? a.data.addresses
            : (isOk(s) && s.data && Array.isArray(s.data.addresses) ? s.data.addresses : []);
        var pick = pickAddress(list);
        if (pick) { C.copy(C.render.addressText(pick)); }
    });

    // Filas con role="button": Enter o espacio las abren como un clic.
    $(document).on('keydown', '.erc-panel .erc-item[role="button"]', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') { return; }
        e.preventDefault();
        this.click();
    });
})(window.jQuery);

/**
 * Reintento manual de la búsqueda del cliente en gestión.
 *
 * El aviso "Este remitente no está en gestión" lo pinta right-panel.blade.php
 * (y la pestaña Gestión cuando el resumen dice 'unlinked') cuando la búsqueda
 * automática ya corrió y falló. Aquí solo se relanza el trabajo saltándose el
 * enfriamiento.
 *
 * Delegación en document porque el panel derecho se sustituye entero al cambiar
 * de conversación (SPA pane).
 */
(function ($) {
    'use strict';

    if (!$) { return; }
    if (window.__hdErpRelinkLoaded) { return; }
    window.__hdErpRelinkLoaded = true;

    $(document).on('click', '[data-bv-erp-relink]', function () {
        var $btn = $(this);
        var $box = $btn.closest('.rsp-erp-missing');
        var url = $box.data('relink-url');

        if (!url || $btn.prop('disabled')) { return; }

        $btn.prop('disabled', true).text('Buscando…');

        $.ajax({
            url: url,
            method: 'POST',
            timeout: 15000,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || ''
            }
        }).done(function (resp) {
            if (window.toastr) {
                window.toastr.success((resp && resp.message) || 'Buscando el cliente en gestión…');
            }

            // El trabajo es asíncrono: no se puede pintar el resultado aquí. Se
            // deja constancia de que se pidió y se invita a recargar, en vez de
            // fingir que ya está resuelto.
            $box.find('.rsp-erp-missing-text span').text('Búsqueda enviada. Recarga en unos segundos para ver el resultado.');
            $btn.text('Enviado');
        }).fail(function (xhr, textStatus) {
            var msg;

            if (textStatus === 'timeout') {
                msg = 'La petición tardó demasiado.';
            } else if (xhr && xhr.status === 429) {
                msg = 'Demasiados reintentos seguidos, espera un minuto.';
            } else {
                msg = (xhr && xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo pedir la búsqueda.';
            }

            if (window.toastr) { window.toastr.warning(msg); }

            $btn.prop('disabled', false).text('Reintentar');
        });
    });

}(window.jQuery));
