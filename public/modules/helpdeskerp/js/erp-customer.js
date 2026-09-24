/*!
 * HelpdeskErp · Ficha de cliente de Gestión (bv-modal "erp-customer-workspace").
 *
 * Menú de secciones (izquierda; arriba y compacto en móvil) + panel de la
 * sección. Cada panel carga su sección con ErpChat.section() y pinta su
 * estado con ErpChat.stateHtml() (bloqueado, sin conexión, no disponible…).
 *
 * Apertura:
 *   [data-erp-open="customer"][data-erp-pane="<pane>"]   (delegación en document)
 *   window.ErpCustomerWorkspace.open(pane)
 * Panes: summary, personal, contact, addresses, consents, quotas, cards
 * (alias: resumen, datos, lopd, catalogs, accounts…).
 *
 * Extensiones:
 *   ErpCustomerWorkspace.registerPane({key, title, sub, render, icon?, group?, perm?})
 *     sub:    string | fn(overviewResp) → string
 *     render: fn(ctx) → html | Promise<html>   (ctx: {customerId, erpId,
 *             overview, section(name, force) → Promise<resp>, ErpChat, $pane})
 *     perm:   clave de ErpChat.can() (opcional)
 *
 * Nunca un modal sobre otro: al abrir otro modal de Gestión desde aquí
 * (pedidos, finanzas, fidelización, pedido) esta ficha se cierra.
 *
 * El ERP es SOLO LECTURA. Depende de window.ErpChat (erp-chat.js).
 * Fuente: modules/HelpdeskErp/public/js/ — copiar a public/modules/helpdeskerp/js/ tras editar.
 */
(function ($) {
    'use strict';

    if (!$ || window.ErpCustomerWorkspace) { return; }

    var NAME = 'erp-customer-workspace';
    var SEL = '[data-bv-modal-name="' + NAME + '"]';
    var RETRY = 'erc-cw';

    var ALIASES = {
        resumen: 'summary', overview: 'summary', dashboard: 'summary',
        datos: 'personal', fiscal: 'personal', profile: 'personal',
        contacto: 'contact', phones: 'contact',
        direcciones: 'addresses',
        lopd: 'consents', consentimientos: 'consents', catalogs: 'consents',
        cuotas: 'quotas',
        accounts: 'cards', tarjetas: 'cards', bank: 'cards',
    };

    var CATALOG_NAMES = { 1: 'Caza' };
    var OPEN_PERM = { orders: 'orders', order: 'orders', finance: 'finance', loyalty: 'loyalty', customer: 'view' };

    var E = null;            // window.ErpChat
    var current = 'summary';
    var cid = null;          // cliente del helpdesk con el que se abrió
    var cache = {};          // sección → resp (solo el cliente actual)
    var seq = 0;             // descarta respuestas de un render anterior
    var hiddenPanes = {};    // key → true (403 del backend)
    var panes = [];          // definiciones en orden
    var byKey = {};

    /* ── Utilidades ───────────────────────────────────────────────── */

    function $modal() { return $(SEL); }
    function $pane() { return $('#ercCwPane'); }
    function isOpen() { return $modal().hasClass('on'); }
    function esc(s) { return E.esc(s); }
    function escAttr(s) { return E.escAttr(s); }
    function blank(v) { return v == null || (typeof v === 'string' && v.trim() === ''); }
    function isOff(v) { return v === false || v === 0 || v === '0'; }
    function isOn(v) { return v === true || v === 1 || v === '1'; }

    function canSensitive() {
        if (E.can('sensitive')) { return true; }
        return String($modal().attr('data-erc-cw-sensitive') || '') === '1';
    }

    function can(perm) {
        if (!perm) { return true; }
        if (perm === 'sensitive') { return canSensitive(); }
        return E.can(perm);
    }

    function fullName(d) {
        return [d && d.label, d && d.surnames].filter(function (v) { return !blank(v); })
            .map(function (v) { return String(v).trim(); }).join(' ');
    }

    function card(title, meta, body, foot) {
        return '<div class="erc-card">' +
            '<div class="erc-card-head">' + esc(title) + (meta ? '<span class="erc-meta">' + esc(meta) + '</span>' : '') + '</div>' +
            '<div class="erc-card-body">' + body + '</div>' +
            (foot ? '<div class="erc-card-foot">' + foot + '</div>' : '') +
        '</div>';
    }

    function row(k, v, mono, cls) {
        return '<div class="erc-row' + (cls ? ' ' + cls : '') + '"><span class="k">' + esc(k) + '</span>' +
            '<span class="v' + (mono ? ' mono' : '') + '">' + (blank(v) ? '—' : esc(v)) + '</span></div>';
    }

    // Como ErpChat.render.kv, pero pinta "—" en vez de ocultar el dato vacío:
    // en la ficha el agente tiene que ver que el campo está en blanco.
    function kvAlways(k, v, mono, wide) {
        return '<div' + (wide ? ' class="is-wide"' : '') + '><span class="k">' + esc(k) + '</span>' +
            '<span class="v' + (mono && !blank(v) ? ' mono' : '') + '">' + (blank(v) ? '—' : esc(v)) + '</span></div>';
    }

    function yesNo(b, yes, no) {
        return b ? (yes || 'Sí') : (no || 'No');
    }

    function paneHead(title, sub, resp) {
        var at = resp && resp.fetched_at ? 'Actualizado ' + E.relative(resp.fetched_at) : '';
        return '<div class="erc-cw-pane-hd"><div class="tt"><span class="t">' + esc(title) + '</span>' +
            (sub ? '<span class="s">' + esc(sub) + '</span>' : '') + '</div>' +
            (at ? '<span class="at">' + esc(at) + '</span>' : '') + '</div>';
    }

    function state(resp, label) {
        return E.stateHtml($.extend({}, resp || {}, { retry: RETRY }), label);
    }

    function ok(resp) { return resp && resp.state === 'ok'; }

    /* ── Datos (caché por cliente mientras la ficha está abierta) ─── */

    function syncCustomer() {
        var now = E.customerId();
        if (now !== cid) {
            cid = now;
            cache = {};
            hiddenPanes = {};
        }
    }

    function section(name, force) {
        if (!force && cache[name]) { return Promise.resolve(cache[name]); }
        return E.section(name, force ? { force: 1 } : {}).then(function (resp) {
            // Los fallos transitorios no se guardan: el siguiente render reintenta.
            if (resp && resp.state !== 'down' && resp.state !== 'loading') { cache[name] = resp; }
            else { delete cache[name]; }
            return resp;
        });
    }

    function ov() { return E.cachedOverview(cid) || null; }

    function ovSection(resp, key) {
        return resp && resp.data && resp.data.sections ? resp.data.sections[key] || null : null;
    }

    function summaryData(resp) {
        var s = ovSection(resp || ov(), 'summary');
        return ok(s) ? s.data || {} : null;
    }

    // El summary sale del overview; si el overview no lo trae, se pide suelto.
    function summary(force) {
        var s = ovSection(ov(), 'summary');
        if (!force && ok(s)) { return Promise.resolve(s); }
        return section('summary', force);
    }

    /* ── Cabecera ─────────────────────────────────────────────────── */

    function renderHead(resp) {
        var d = summaryData(resp);
        var erpId = resp && resp.data ? resp.data.erp_id : null;
        var $t = $('#ercCwTitle');
        var $m = $('#ercCwMeta');

        if (!d) {
            $t.text(erpId ? 'Cliente de Gestión' : 'Cliente');
            $m.html(erpId ? chip('Gestión', erpId) : '');
            return;
        }

        $t.html(esc(fullName(d) || 'Cliente de Gestión') + (erpId || d.id ? ' <span class="erc-cw-id">· ' + esc(erpId || d.id) + '</span>' : ''));
        $m.html(
            chip('NIF', d.cif) + chip('Tarjeta', d.card) + chip('Categoría', dsc(d.category_description, d.category)) +
            (isOff(d.available) ? '<span class="erc-cw-chip erc-cw-chip--off"><span class="v">Dado de baja</span></span>' : '')
        );
    }

    function chip(k, v) {
        if (blank(v)) { return ''; }
        return '<span class="erc-cw-chip"><span class="k">' + esc(k) + '</span><span class="v">' + esc(v) + '</span></span>';
    }

    /* ── Menú ─────────────────────────────────────────────────────── */

    function visiblePanes() {
        return panes.filter(function (p) { return can(p.perm) && !hiddenPanes[p.key]; });
    }

    function renderNav(resp) {
        var groups = [];
        var byGroup = {};
        visiblePanes().forEach(function (p) {
            var g = p.group || 'Más';
            if (!byGroup[g]) { byGroup[g] = []; groups.push(g); }
            byGroup[g].push(p);
        });

        $('#ercCwNav').html(groups.map(function (g) {
            return '<div class="erc-cw-group"><span class="erc-cw-label">' + esc(g) + '</span>' +
                byGroup[g].map(function (p) {
                    var sub = '';
                    try { sub = typeof p.sub === 'function' ? p.sub(resp || ov()) : (p.sub || ''); } catch (e) { sub = ''; }
                    return '<button type="button" class="erc-cw-item' + (p.key === current ? ' is-on' : '') + '" data-erc-cw-pane="' + escAttr(p.key) + '"' +
                        (p.key === current ? ' aria-current="true"' : '') + '>' +
                        '<span class="ic"><i class="' + escAttr(p.icon || 'fas fa-circle') + '"></i></span>' +
                        '<span class="erc-cw-item-body"><span class="t">' + esc(p.title) + '</span>' +
                            (sub ? '<span class="s">' + esc(sub) + '</span>' : '') + '</span>' +
                        '<i class="fas fa-chevron-right erc-cw-chev"></i>' +
                    '</button>';
                }).join('') +
            '</div>';
        }).join(''));
    }

    /* ── Pane: Resumen ────────────────────────────────────────────── */

    function actionButton(action) {
        if (!action || !action.open) { return ''; }
        if (!can(OPEN_PERM[action.open] || 'view')) { return ''; }
        if (action.open === 'order') {
            return action.order_id ? '<button type="button" class="act" data-erp-order-open="' + escAttr(action.order_id) + '">Ver pedido</button>' : '';
        }
        return '<button type="button" class="act" data-erp-open="' + escAttr(action.open) + '"' +
            (action.pane ? ' data-erp-pane="' + escAttr(action.pane) + '"' : '') + '>Ver</button>';
    }

    function alertsHtml(alerts) {
        if (!alerts || !alerts.length) { return ''; }
        return '<div class="erc-alerts">' + alerts.map(function (a) {
            var lvl = ['warn', 'info', 'good'].indexOf(a.level) >= 0 ? a.level : 'info';
            return '<div class="erc-alert erc-alert--' + lvl + '"><span class="dot"></span><span class="txt">' + esc(a.text) + '</span>' + actionButton(a.action) + '</div>';
        }).join('') + '</div>';
    }

    function kpi(label, value, detail, blocked, good) {
        return '<div class="erc-kpi' + (blocked ? ' erc-kpi--blocked' : '') + '"><span class="l">' + esc(label) + '</span>' +
            '<span class="n">' + esc(value) + '</span>' +
            (detail ? '<span class="d' + (good ? ' is-good' : '') + '">' + esc(detail) + '</span>' : '') + '</div>';
    }

    // KPI de una sección del overview según su estado.
    function kpiFor(sec, label, fn) {
        if (!sec || sec.state === 'forbidden') { return ''; }
        if (sec.state === 'blocked') { return kpi(label, '—', 'Pendiente de permiso en Oracle', true); }
        if (sec.state === 'loading') { return kpi(label, '…', 'Buscando en Gestión…'); }
        if (sec.state === 'down') { return kpi(label, '—', 'Sin conexión', true); }
        if (sec.state !== 'ok') { return kpi(label, '—', 'No disponible'); }
        return fn(sec.data, sec);
    }

    function orderItem(o) {
        var when = o.served_date || o.date;
        return '<button type="button" class="erc-item" data-erp-order-open="' + escAttr(o.id) + '">' +
            '<span class="ic"><i class="fas fa-box"></i></span>' +
            '<span class="info"><span class="top"><span class="ref">Nº ' + esc(o.number || o.order_id || o.id) + '</span>' + E.render.statusPill(o.status, o.status_description) + '</span>' +
                '<span class="m">' + esc(E.date(o.date, true)) + (o.served_date ? ' · servido ' + esc(E.relative(when)) : '') + '</span></span>' +
            '<span class="end"><span class="act">Ver</span></span>' +
        '</button>';
    }

    // Más recientes primero (el manager no garantiza el orden de la lista).
    function sortOrders(list) {
        var arr = Array.isArray(list) ? list.slice() : [];
        var t = function (o) { var d = E.parseDate(o && o.date); return d ? d.getTime() : 0; };
        return arr.sort(function (a, b) { return t(b) - t(a); });
    }

    function summaryHtml(resp) {
        var data = resp.data || {};
        var d = summaryData(resp);
        var sSum = ovSection(resp, 'summary');
        var sOrders = ovSection(resp, 'orders');
        var sPoints = ovSection(resp, 'loyalty_points');
        var sBalance = ovSection(resp, 'balance');
        var sVouchers = ovSection(resp, 'vouchers');
        var links = data.links || {};

        var kpis = [
            kpiFor(sOrders, 'Pedidos', function (list, sec) {
                var arr = sortOrders(list);
                var pg = sec.pagination || {};
                var n = pg.has_more ? (pg.count || arr.length) + '+' : String(pg.count != null ? pg.count : arr.length);
                var last = arr[0];
                return kpi('Pedidos', n, last ? 'último ' + E.relative(last.date) : 'sin pedidos', false, false);
            }),
            kpiFor(sPoints, 'Puntos', function (p) {
                var bal = p && p.balance != null ? p.balance : 0;
                return kpi('Puntos', String(bal), p && p.main_card ? 'tarjeta ' + p.main_card : 'fidelización', false, bal > 0);
            }),
            kpiFor(sBalance, 'Pendiente de cobro', function (b) {
                var pend = b && b.balance ? E.num(b.balance.pending) : null;
                return kpi('Pendiente de cobro', E.money(pend), pend ? 'facturado ' + E.money(b.balance.invoiced) : 'al día', false, !pend);
            }),
            kpiFor(sVouchers, 'Vales activos', function (v) {
                var st = v && v.statistics && v.statistics.vouchers ? v.statistics.vouchers : {};
                return kpi('Vales activos', String(st.active || 0), st.amount_total ? E.money(st.amount_total) : 'sin saldo', false, !!st.active);
            }),
        ].join('');

        var key = '';
        if (d) {
            var phone = (d.phones || []).filter(function (p) { return !isOff(p.available); })[0];
            var addr = (d.addresses || []).filter(function (a) { return !isOff(a.available); })[0];
            var lopd = d.lopd || {};
            key = card('Datos clave', 'solo lectura',
                row('Cliente', fullName(d)) +
                row('NIF / CIF', d.cif, true) +
                row('Email', d.email, true) +
                row('Teléfono', phone ? phone.number : null, true) +
                (can('addresses') ? row('Dirección', addr ? E.render.addressText(addr) : null) : '') +
                row('Información comercial', lopd.no_commercial_info ? 'No la quiere' : (lopd.accepted ? 'La acepta' : 'Sin LOPD')) +
                row('Cliente web (PrestaShop)', links.prestashop_customer_id || d.code_internet, true) +
                row('Alta en Gestión', d.created ? E.date(d.created, true) : null) +
                E.render.obs(d.observations));
        } else if (sSum) {
            key = card('Datos clave', null, state(sSum, 'Datos del cliente'));
        }

        var ordersCard = '';
        if (sOrders && sOrders.state !== 'forbidden') {
            var list = sortOrders(sOrders.data);
            var body = sOrders.state !== 'ok' ? state(sOrders, 'Pedidos')
                : (list.length ? '<div class="erc-list">' + list.slice(0, 4).map(orderItem).join('') + '</div>'
                    : E.stateHtml({ state: 'empty', icon: 'fas fa-box', message: 'Este cliente no tiene pedidos en Gestión.' }, 'Sin pedidos'));
            ordersCard = card('Últimos pedidos', list.length ? 'de ' + list.length + ' cargados' : null, body,
                can('orders') && list.length ? '<button type="button" class="erc-btn erc-btn--outline" data-erp-open="orders">Ver todos los pedidos</button>' : '');
        }

        var access = [];
        if (can('orders')) { access.push(link('orders', null, 'Pedidos', 'Listado con filtros y detalle')); }
        if (can('finance')) { access.push(link('finance', 'balance', 'Finanzas', 'Saldo, facturas, cobros, deudas y albaranes')); }
        if (can('loyalty')) { access.push(link('loyalty', 'points', 'Fidelización', 'Puntos, vales y bonos')); }

        return paneHead('Resumen', 'Lo importante del cliente en Gestión', sSum || resp) +
            alertsHtml(data.alerts) +
            (kpis ? '<div class="erc-kpis">' + kpis + '</div>' : '') +
            '<div class="erc-cw-split">' + key + ordersCard + '</div>' +
            (access.length ? '<div class="erc-sec-label">Abrir en Gestión</div><div class="erc-cw-links">' + access.join('') + '</div>' : '');
    }

    function link(open, pane, title, sub) {
        return '<button type="button" class="erc-cw-link" data-erp-open="' + escAttr(open) + '"' + (pane ? ' data-erp-pane="' + escAttr(pane) + '"' : '') + '>' +
            '<span class="t">' + esc(title) + '</span><span class="s">' + esc(sub) + '</span></button>';
    }

    function paneSummary(ctx, force) {
        return E.overview(force).then(function (resp) {
            return overviewOr(resp, function () { return summaryHtml(resp); });
        });
    }

    // Si el overview entero falla (sin vínculo, sin permiso, sin conexión…)
    // se pinta su estado en lugar del panel.
    function overviewOr(resp, fn) {
        if (!resp || !resp.data || !resp.data.sections) {
            return paneHead('Resumen', null, null) + state(resp || { state: 'down' }, 'Resumen del cliente');
        }
        return fn();
    }

    /* ── Pane: Datos personales y fiscales ────────────────────────── */

    function panePersonal(ctx, force) {
        return section('personal', force).then(function (resp) {
            var head = paneHead('Datos personales y fiscales', 'Ficha del cliente en Gestión', resp);
            if (!ok(resp)) { return head + state(resp, 'Datos personales'); }
            var d = resp.data || {};
            var pa = d.public_administration || {};
            var paFilled = ['accounting_office', 'managing_body', 'processing_unit', 'proposing_body'].some(function (k) { return !blank(pa[k]); });

            var ident = '<div class="erc-kv">' +
                kvAlways('Nombre', d.label) +
                kvAlways('Apellidos', d.surnames) +
                kvAlways('Razón social', d.business_name, false, true) +
                kvAlways('NIF / CIF', d.cif, true) +
                kvAlways('Tarjeta', d.card, true) +
                kvAlways('Sexo', d.gender) +
                kvAlways('Fecha de nacimiento', d.birth_date ? E.date(d.birth_date, true) : null) +
                kvAlways('Idioma', dsc(d.language_description, d.language), true) +
                kvAlways('Nacionalidad', dsc(d.nationality_description, d.nationality), true) +
                kvAlways('Categoría', dsc(d.category_description, d.category), true) +
                kvAlways('Estado', isOff(d.available) ? 'Dado de baja' : 'Activo') +
            '</div>';

            var fiscal = '<div class="erc-kv">' +
                kvAlways('Tipo de cliente', dsc(d.customer_type_description, d.customer_type), true) +
                kvAlways('Régimen fiscal', dsc(d.fiscal_regime_description, d.fiscal_regime), true) +
                kvAlways('Régimen de país', dsc(d.country_regime_description, d.country_regime), true) +
                kvAlways('Cliente web (PrestaShop)', d.code_internet, true) +
                kvAlways('Alta', d.created ? E.date(d.created, true) : null) +
                kvAlways('Última modificación', d.updated ? E.date(d.updated, true) : null) +
            '</div>';

            var admin = paFilled
                ? '<div class="erc-kv">' +
                    kvAlways('Oficina contable', pa.accounting_office, true) +
                    kvAlways('Órgano gestor', pa.managing_body, true) +
                    kvAlways('Unidad tramitadora', pa.processing_unit, true) +
                    kvAlways('Órgano proponente', pa.proposing_body, true) +
                  '</div>'
                : '<div class="erc-note erc-note--info"><span class="txt">No es una administración pública (sin códigos DIR3).</span></div>';

            return head +
                card('Identificación', 'solo lectura', ident) +
                '<div class="erc-cw-split">' +
                    card('Datos fiscales', null, fiscal +
                        '<div class="erc-note erc-note--info"><span class="txt">Gestión devuelve códigos internos para idioma, nacionalidad, tipo de cliente y régimen.</span></div>') +
                    card('Administración pública', null, admin) +
                '</div>' +
                (blank(d.observations) ? '' : E.render.obs(d.observations));
        });
    }

    /* ── Pane: Contacto ───────────────────────────────────────────── */

    function copyBtn(value) {
        return '<button type="button" class="erc-btn erc-btn--outline erc-btn--sm" data-erc-cw-copy="' + escAttr(value) + '">Copiar</button>';
    }

    function paneContact(ctx, force) {
        return section('contact', force).then(function (resp) {
            var head = paneHead('Contacto', 'Email, persona de contacto y teléfonos', resp);
            if (!ok(resp)) { return head + state(resp, 'Contacto'); }
            var d = resp.data || {};
            var phones = (d.phones || []).slice().sort(function (a, b) { return (isOff(a.available) ? 1 : 0) - (isOff(b.available) ? 1 : 0); });

            var main = '<div class="erc-cw-copyrow"><span class="k">Email</span><span class="v">' + (blank(d.email) ? '—' : esc(d.email)) + '</span>' +
                    (blank(d.email) ? '' : copyBtn(d.email)) + '</div>' +
                row('Persona de contacto', d.contact_person);

            var list = phones.length ? phones.map(function (p) {
                var off = isOff(p.available);
                var tags = (isOn(p.sms_enabled) ? '<span class="erc-tag erc-tag--progress">Admite SMS</span>' : '<span class="erc-tag erc-tag--closed">Sin SMS</span>') +
                    (off ? '<span class="erc-tag erc-tag--blocked">Inactivo</span>' : '');
                return '<div class="erc-cw-phone' + (off ? ' erc-cw-phone--off' : '') + '">' +
                    '<div class="info"><div class="num">' + esc(p.number || '—') + '</div>' +
                        '<div class="tags">' + tags + '</div>' +
                        '<span class="m">' + (blank(p.schedule) ? 'Sin horario indicado' : 'Horario: ' + esc(p.schedule)) + '</span>' +
                        (blank(p.observations) ? '' : '<span class="m">' + esc(p.observations) + '</span>') +
                    '</div>' +
                    (blank(p.number) ? '' : copyBtn(p.number)) +
                '</div>';
            }).join('') : E.stateHtml({ state: 'empty', icon: 'fas fa-phone', message: 'No hay teléfonos registrados en Gestión.' }, 'Sin teléfonos');

            return head +
                card('Datos de contacto', 'solo lectura', main) +
                card('Teléfonos', phones.length ? String(phones.length) : null, list) +
                '<div class="erc-note erc-note--info"><span class="txt">Son datos personales: se copian al portapapeles, no se insertan en el chat.</span></div>';
        });
    }

    /* ── Pane: Direcciones ────────────────────────────────────────── */

    function paneAddresses(ctx, force) {
        return section('addresses', force).then(function (resp) {
            var head = paneHead('Direcciones', 'Facturación y envío', resp);
            if (!ok(resp)) { return head + state(resp, 'Direcciones'); }
            var list = ((resp.data || {}).addresses || []).slice();
            if (!list.length) {
                return head + E.stateHtml({ state: 'empty', icon: 'fas fa-location-dot', message: 'Este cliente no tiene direcciones en Gestión.' }, 'Sin direcciones');
            }
            var rank = function (a) {
                if (isOff(a.available)) { return 3; }
                if (a.default_shipping) { return 0; }
                if (a.default_billing) { return 1; }
                return 2;
            };
            list.sort(function (a, b) { return rank(a) - rank(b); });

            return head + card('Direcciones guardadas', list.length + (list.length === 1 ? ' dirección' : ' direcciones'),
                '<div class="erc-cw-addrs">' + list.map(function (a) {
                    var text = E.render.addressText(a);
                    return '<div class="erc-cw-addr">' + E.render.address(a) +
                        (text ? '<button type="button" class="erc-btn erc-btn--outline erc-btn--sm" data-erc-cw-insert="' + escAttr(text) + '">Insertar dirección en el chat</button>' : '') +
                    '</div>';
                }).join('') + '</div>');
        });
    }

    /* ── Pane: Consentimientos (LOPD + catálogos) ─────────────────── */

    // Descripción del manager (*_description) con el código como respaldo.
    function dsc(description, code) {
        var d = description == null ? '' : String(description).trim();
        if (d === '') { return code; }
        return (code == null || String(code).trim() === '' || String(code) === d) ? d : d + ' (' + code + ')';
    }

    function catalogName(id, description) {
        var n = (description != null && String(description).trim() !== '') ? String(description).trim() : CATALOG_NAMES[String(id)];
        return n ? n + ' (' + id + ')' : 'Catálogo ' + id;
    }

    function paneConsents(ctx, force) {
        return Promise.all([summary(force), section('catalogs', force)]).then(function (r) {
            var sResp = r[0];
            var cResp = r[1];
            var head = paneHead('Consentimientos', 'LOPD y catálogos suscritos', cResp && cResp.fetched_at ? cResp : sResp);

            var lopdHtml;
            if (!ok(sResp)) {
                lopdHtml = state(sResp, 'LOPD');
            } else {
                var l = (sResp.data || {}).lopd || {};
                lopdHtml =
                    row('Acepta la LOPD', yesNo(l.accepted), false, l.accepted ? 'erc-row--good' : '') +
                    row('Fecha de aceptación', l.accepted_at ? E.date(l.accepted_at, true) : null) +
                    row('Información comercial', l.no_commercial_info ? 'No la quiere' : 'La acepta') +
                    row('Cesión de datos a terceros', l.no_data_to_third_parties ? 'No autoriza' : 'Autoriza') +
                    row('Interés legítimo', yesNo(l.legitimate_interest)) +
                    (l.no_commercial_info
                        ? '<div class="erc-note erc-note--info"><span class="txt">No acepta información comercial: no le ofrezcas promociones.</span></div>'
                        : '');
            }

            var catHtml;
            var meta = null;
            if (!ok(cResp)) {
                catHtml = state(cResp, 'Catálogos');
            } else {
                var cats = ((cResp.data || {}).catalogs || []).slice();
                var st = ((cResp.data || {}).statistics || {}).catalogs || {};
                meta = cats.length ? (st.active != null ? st.active : cats.length) + ' activos de ' + (st.total != null ? st.total : cats.length) : null;
                cats.sort(function (a, b) { return (a.unsubscribed_at ? 1 : 0) - (b.unsubscribed_at ? 1 : 0); });
                catHtml = cats.length ? '<div class="erc-list">' + cats.map(function (c) {
                    var off = !!c.unsubscribed_at || isOff(c.available);
                    return '<div class="erc-item' + (off ? ' erc-item--muted' : '') + '">' +
                        '<span class="ic"><i class="fas fa-book-open"></i></span>' +
                        '<span class="info"><span class="top"><span class="t">' + esc(catalogName(c.catalog_id, c.catalog_description)) + '</span></span>' +
                            '<span class="m">Alta ' + esc(c.subscribed_at ? E.date(c.subscribed_at, true) : '—') +
                            (c.unsubscribed_at ? ' · Baja ' + esc(E.date(c.unsubscribed_at, true)) : '') + '</span></span>' +
                        '<span class="end">' + (off ? '<span class="erc-tag erc-tag--blocked">Baja</span>' : '<span class="erc-tag erc-tag--done">Suscrito</span>') + '</span>' +
                    '</div>';
                }).join('') + '</div>'
                    : E.stateHtml({ state: 'empty', icon: 'fas fa-book-open', message: 'No está suscrito a ningún catálogo.' }, 'Sin catálogos');
            }

            return head + '<div class="erc-cw-split">' +
                card('Protección de datos (LOPD)', null, lopdHtml) +
                card('Catálogos', meta, catHtml) +
            '</div>';
        });
    }

    /* ── Pane: Cuotas ─────────────────────────────────────────────── */

    function paneQuotas(ctx, force) {
        return section('quotas', force).then(function (resp) {
            var head = paneHead('Cuotas', 'Servicios contratados con cuota', resp);
            if (!ok(resp)) { return head + state(resp, 'Cuotas'); }
            var d = resp.data || {};
            var list = d.quotas || [];
            var st = (d.statistics || {}).quotas || {};

            var stats = '<div class="erc-stats">' +
                '<div class="erc-stat"><span class="n">' + esc(st.total != null ? st.total : list.length) + '</span><span class="l">Cuotas</span></div>' +
                '<div class="erc-stat"><span class="n' + (st.active ? ' is-good' : ' is-muted') + '">' + esc(st.active || 0) + '</span><span class="l">Activas</span></div>' +
                '<div class="erc-stat"><span class="n">' + esc(E.money(st.amount_total || 0)) + '</span><span class="l">Importe</span></div>' +
            '</div>';

            if (!list.length) {
                return head + stats + E.stateHtml({ state: 'empty', icon: 'fas fa-receipt', message: 'Este cliente no tiene cuotas en Gestión.' }, 'Sin cuotas');
            }

            return head + stats + card('Cuotas del cliente', String(list.length), '<div class="erc-list">' + list.map(function (q) {
                var active = !!q.is_active;
                var dates = ['Contratada ' + (q.contract_date ? E.date(q.contract_date, true) : '—')];
                if (q.end_date) { dates.push((active ? 'Hasta ' : 'Finalizó ') + E.date(q.end_date, true)); }
                return '<div class="erc-item' + (active ? '' : ' erc-item--muted') + '">' +
                    '<span class="ic"><i class="fas fa-receipt"></i></span>' +
                    '<span class="info"><span class="top"><span class="ref">Cuota ' + esc(q.id) + '</span>' +
                        (active ? '<span class="erc-tag erc-tag--done">Activa</span>' : '<span class="erc-tag erc-tag--closed">Finalizada</span>') + '</span>' +
                        '<span class="t">' + esc(q.article ? 'Artículo ' + q.article : 'Sin artículo') + '</span>' +
                        '<span class="m">' + esc(dates.join(' · ')) + '</span></span>' +
                    '<span class="end"><span class="amt' + (active ? '' : ' is-muted') + '">' + esc(E.money(q.amount)) + '</span></span>' +
                '</div>';
            }).join('') + '</div>');
        });
    }

    /* ── Pane: Tarjetas y cuentas (sensible) ──────────────────────── */

    // Nunca se enseña un número completo: si llega sin enmascarar, se
    // enmascara aquí (primeros 4 + últimos 4).
    function maskIban(v) {
        if (blank(v)) { return null; }
        var s = String(v).replace(/\s+/g, '');
        if (/[*•xX]{2,}/.test(s)) { return String(v); }
        if (s.length <= 8) { return '•••• ' + s.slice(-4); }
        return s.slice(0, 4) + ' •••• •••• ' + s.slice(-4);
    }

    function maskCard(v) {
        if (blank(v)) { return null; }
        var s = String(v).replace(/\s+/g, '');
        if (/[*•xX]{2,}/.test(s)) { return String(v); }
        return '•••• ' + s.slice(-4);
    }

    function paneCards(ctx, force) {
        return Promise.all([section('cards', force), section('accounts', force)]).then(function (r) {
            var cResp = r[0];
            var aResp = r[1];
            if (cResp.state === 'forbidden' && aResp.state === 'forbidden') {
                hiddenPanes.cards = true;
                renderNav();
                return paneHead('Tarjetas y cuentas', null, null) + state(cResp, 'Tarjetas y cuentas');
            }
            var head = paneHead('Tarjetas y cuentas', 'Datos bancarios enmascarados', cResp.fetched_at ? cResp : aResp);

            var cardsHtml;
            var cardsMeta = null;
            if (!ok(cResp)) {
                cardsHtml = state(cResp, 'Tarjetas');
            } else {
                var cl = (cResp.data || {}).cards || [];
                cardsMeta = cl.length ? String(cl.length) : null;
                cardsHtml = cl.length ? cl.map(function (c) {
                    var off = isOff(c.available);
                    var meta = [c.bank, c.holder, c.expires ? 'caduca ' + c.expires : null, c.limit != null ? 'límite ' + E.money(c.limit) : null]
                        .filter(function (v) { return !blank(v); }).join(' · ');
                    return '<div class="erc-cw-bank' + (c.is_main ? ' erc-cw-bank--main' : '') + (off ? ' erc-cw-bank--off' : '') + '">' +
                        '<span class="ic"><i class="far fa-credit-card"></i></span>' +
                        '<div class="info"><div class="num">' + esc(maskCard(c.number) || '—') + '</div>' +
                            (meta ? '<span class="m">' + esc(meta) + '</span>' : '') + '</div>' +
                        (c.is_main ? '<span class="erc-tag erc-tag--done">Principal</span>' : '') +
                        (off ? '<span class="erc-tag erc-tag--blocked">Inactiva</span>' : '') +
                    '</div>';
                }).join('') : E.stateHtml({ state: 'empty', icon: 'far fa-credit-card', message: 'Sin tarjetas registradas.' }, 'Sin tarjetas');
            }

            var accHtml;
            var accMeta = null;
            if (!ok(aResp)) {
                accHtml = state(aResp, 'Cuentas');
            } else {
                var al = (aResp.data || {}).accounts || [];
                accMeta = al.length ? String(al.length) : null;
                accHtml = al.length ? al.map(function (a) {
                    var off = isOff(a.available);
                    var meta = [a.bank, a.bic ? 'BIC ' + a.bic : null].filter(function (v) { return !blank(v); }).join(' · ');
                    return '<div class="erc-cw-bank' + (off ? ' erc-cw-bank--off' : '') + '">' +
                        '<span class="ic"><i class="fas fa-building-columns"></i></span>' +
                        '<div class="info"><div class="num">' + esc(maskIban(a.iban) || '—') + '</div>' +
                            (meta ? '<span class="m">' + esc(meta) + '</span>' : '') + '</div>' +
                        (off ? '<span class="erc-tag erc-tag--blocked">Inactiva</span>' : '') +
                    '</div>';
                }).join('') : E.stateHtml({ state: 'empty', icon: 'fas fa-building-columns', message: 'Sin cuentas bancarias registradas.' }, 'Sin cuentas');
            }

            return head + '<div class="erc-cw-split">' +
                card('Tarjetas', cardsMeta, cardsHtml) +
                card('Cuentas bancarias', accMeta, accHtml) +
            '</div>' +
            '<div class="erc-note erc-note--info"><span class="txt">Datos sensibles: no los compartas por el chat.</span></div>';
        });
    }

    /* ── Registro de panes ────────────────────────────────────────── */

    function addPane(def) {
        if (!def || !def.key || typeof def.render !== 'function' || byKey[def.key]) { return false; }
        panes.push(def);
        byKey[def.key] = def;
        return true;
    }

    function countSub(n, one, many, none) {
        if (n == null) { return none; }
        return n ? n + ' ' + (n === 1 ? one : many) : none;
    }

    addPane({
        key: 'summary', group: 'Cliente', icon: 'fas fa-chart-simple', title: 'Resumen', render: paneSummary,
        sub: function (resp) {
            var alerts = resp && resp.data && resp.data.alerts ? resp.data.alerts.length : 0;
            return alerts ? alerts + (alerts === 1 ? ' aviso' : ' avisos') : 'Avisos y cifras';
        },
    });
    addPane({
        key: 'personal', group: 'Cliente', icon: 'fas fa-id-card', title: 'Datos personales y fiscales', render: panePersonal,
        sub: function (resp) { var d = summaryData(resp); return d && d.cif ? 'NIF ' + d.cif : 'Identificación y régimen'; },
    });
    addPane({
        key: 'contact', group: 'Cliente', icon: 'fas fa-phone', title: 'Contacto', render: paneContact,
        sub: function (resp) { var d = summaryData(resp); return d ? countSub((d.phones || []).length, 'teléfono', 'teléfonos', 'Email y teléfonos') : 'Email y teléfonos'; },
    });
    addPane({
        key: 'addresses', group: 'Cliente', icon: 'fas fa-location-dot', title: 'Direcciones', perm: 'addresses', render: paneAddresses,
        sub: function (resp) {
            var s = ovSection(resp, 'addresses');
            var list = ok(s) && s.data ? s.data.addresses || [] : null;
            return list ? countSub(list.length, 'guardada', 'guardadas', 'Sin direcciones') : 'Facturación y envío';
        },
    });
    addPane({
        key: 'consents', group: 'Cuenta', icon: 'fas fa-shield-halved', title: 'Consentimientos', render: paneConsents,
        sub: function (resp) {
            var d = summaryData(resp);
            if (!d || !d.lopd) { return 'LOPD y catálogos'; }
            return d.lopd.no_commercial_info ? 'Sin información comercial' : (d.lopd.accepted ? 'LOPD aceptada' : 'LOPD sin aceptar');
        },
    });
    addPane({ key: 'quotas', group: 'Cuenta', icon: 'fas fa-receipt', title: 'Cuotas', perm: 'finance', render: paneQuotas, sub: 'Servicios con cuota' });
    addPane({ key: 'cards', group: 'Cuenta', icon: 'far fa-credit-card', title: 'Tarjetas y cuentas', perm: 'sensitive', render: paneCards, sub: 'Datos bancarios' });

    /* ── Render ───────────────────────────────────────────────────── */

    function resolvePane(p) {
        var key = String(p || '').trim().toLowerCase();
        key = ALIASES[key] || key;
        var def = byKey[key];
        if (!def || !can(def.perm) || hiddenPanes[key]) { return 'summary'; }
        return key;
    }

    function ctxFor($p) {
        var resp = ov();
        return {
            customerId: cid,
            erpId: resp && resp.data ? resp.data.erp_id || null : E.erpId(cid),
            overview: resp,
            section: section,
            ErpChat: E,
            $pane: $p,
        };
    }

    function renderPane(force) {
        syncCustomer();
        var def = byKey[current] || byKey.summary;
        var token = ++seq;
        var $p = $pane();

        $p.html(paneHead(def.title, null, null) + E.skeleton(3, 'card')).scrollTop(0);
        renderNav();

        var out;
        try { out = def.render(ctxFor($p), !!force); } catch (e) { out = null; }

        Promise.resolve(out).then(function (html) {
            if (token !== seq || !isOpen()) { return; }
            $p.html(typeof html === 'string' && html ? html : state({ state: 'unavailable', message: 'No se pudo pintar esta sección.' }, def.title));
            renderNav();
        }, function () {
            if (token !== seq) { return; }
            $p.html(state({ state: 'down' }, def.title));
        });
    }

    // Cabecera + menú a partir del overview (una sola petición, cacheada 5 min).
    function loadHead(force) {
        return E.overview(force).then(function (resp) {
            if (!isOpen()) { return resp; }
            renderHead(resp);
            renderNav(resp);
            return resp;
        });
    }

    function openWorkspace(pane) {
        if (!E) { return; }
        syncCustomer();
        if (!cid) {
            E.toast('warning', 'Selecciona una conversación con cliente.');
            return;
        }
        current = resolvePane(pane);

        $('#ercCwTitle').text('Cliente');
        $('#ercCwMeta').empty();

        var cached = ov();
        if (cached) { renderHead(cached); }

        // Nunca un modal sobre otro: si la ficha se abre desde otro modal
        // (p. ej. el listado de pedidos), ese se cierra antes.
        $('.bv-modal.on').not($modal()).each(function () {
            var other = $(this).attr('data-bv-modal-name');
            if (other && window.HDCommerce && typeof window.HDCommerce.close === 'function') { window.HDCommerce.close(other); }
            else { $(this).removeClass('on'); }
        });

        if (window.HDCommerce && typeof window.HDCommerce.open === 'function') {
            window.HDCommerce.open(NAME);
        } else {
            $modal().addClass('on');
            $('body').css('overflow', 'hidden');
            $(document).trigger('bv:modal:open', [NAME]);
        }

        // El pane de cabecera sale del overview: si ya está en caché, no hay
        // petición extra; el resto de panes piden su sección.
        loadHead(false).then(function (resp) {
            if (!resp || !resp.data || !resp.data.sections) {
                // Sin vínculo / sin permiso: la ficha entera muestra el estado.
                if (resp && ['unlinked', 'forbidden', 'nocustomer', 'unavailable'].indexOf(resp.state) >= 0) {
                    seq++;
                    $('#ercCwNav').empty();
                    $pane().html(state(resp, 'Ficha del cliente'));
                    return;
                }
            }
            // La cabecera ya tiene el summary: si el pane era de resumen se
            // pintó con la misma respuesta y no hace falta repetir.
        });
        renderPane(false);
    }

    function closeWorkspace() {
        if (!isOpen()) { return; }
        E.sheet.close($modal().find('.bv-modal-body'));
        if (window.HDCommerce && typeof window.HDCommerce.close === 'function') {
            window.HDCommerce.close(NAME);
        } else {
            $modal().removeClass('on');
            if (!$('.bv-modal.on').length) { $('body').css('overflow', ''); }
        }
    }

    // Actualizar = saltar las cachés (cliente y servidor) del pane actual.
    // El resumen pide overview(true) y la cabecera se refresca con
    // erp:overview-loaded.
    function refresh() {
        renderPane(true);
    }

    /* ── Handlers ─────────────────────────────────────────────────── */

    function bindHandlers() {
        // Abrir la ficha desde cualquier sitio. Dentro de la propia ficha
        // solo cambia de pane.
        $(document).on('click', '[data-erp-open="customer"]', function (e) {
            e.preventDefault();
            var pane = $(this).attr('data-erp-pane');
            if (isOpen() && E.customerId() === cid) {
                current = resolvePane(pane);
                renderPane(false);
                return;
            }
            openWorkspace(pane);
        });

        $(document).on('click', SEL + ' [data-erc-cw-pane]', function () {
            var key = String($(this).attr('data-erc-cw-pane') || '');
            if (!byKey[key] || key === current) { return; }
            current = key;
            renderPane(false);
        });

        $(document).on('click', '#ercCwRefresh', function () { refresh(); });

        $(document).on('click', SEL + ' [data-erc-cw-copy]', function (e) {
            e.preventDefault();
            E.copy(String($(this).attr('data-erc-cw-copy') || ''));
        });

        $(document).on('click', SEL + ' [data-erc-cw-insert]', function (e) {
            e.preventDefault();
            var text = String($(this).attr('data-erc-cw-insert') || '');
            if (!text) { return; }
            closeWorkspace();
            E.insert(text);
        });

        // Nunca un modal sobre otro: los accesos a pedidos, finanzas,
        // fidelización o un pedido cierran esta ficha. El dueño de cada
        // modal lo abre con su propio handler delegado.
        // Se espera un tick: si el modal destino no existe (módulo o pieza
        // sin cargar), la ficha sigue abierta en vez de quedarse todo cerrado.
        // Las aperturas asíncronas (pedido sin id ERP en caché) las recoge
        // el listener de bv:modal:open.
        $(document).on('click', SEL + ' [data-erp-open]:not([data-erp-open="customer"]), ' + SEL + ' [data-erp-order-open]', function () {
            setTimeout(function () {
                if ($('.bv-modal.on').not($modal()).length) { closeWorkspace(); }
            }, 0);
        });

        // Si otro modal se abre por cualquier otra vía, esta ficha se cierra.
        $(document).on('bv:modal:open', function (e, name) {
            if (name && name !== NAME && isOpen()) { closeWorkspace(); }
        });

        $(document).on('erp:retry', function (e, target) {
            if (target !== RETRY || !isOpen()) { return; }
            if (current === 'summary') { E.invalidate(cid); }
            renderPane(true);
        });

        // Cada overview nuevo (Actualizar, "Reintentar" del panel, pedidos
        // listos…) refresca cabecera, menú y, si está a la vista, el resumen.
        // Se pinta con la respuesta recibida: nunca se vuelve a pedir aquí.
        $(document).on('erp:overview-loaded erp:orders-ready', function (e, resp, id) {
            if (!isOpen() || String(id) !== String(cid)) { return; }
            if (resp && resp.data && resp.data.sections) {
                renderHead(resp);
                renderNav(resp);
                if (current === 'summary') {
                    seq++;
                    $pane().html(overviewOr(resp, function () { return summaryHtml(resp); }));
                    renderNav(resp);
                }
            }
        });
    }

    /* ── API pública ──────────────────────────────────────────────── */

    window.ErpCustomerWorkspace = {
        open: function (pane) { openWorkspace(pane); },
        close: function () { closeWorkspace(); },
        refresh: function () { if (isOpen()) { refresh(); } },
        current: function () { return current; },
        // def = {key, title, sub: string|fn(overviewResp), render: fn(ctx, force) → html|Promise<html>, icon?, group?, perm?}
        registerPane: function (def) {
            if (!def || !def.key || typeof def.render !== 'function') { return false; }
            var added = addPane({
                key: String(def.key),
                title: def.title || String(def.key),
                sub: def.sub || '',
                icon: def.icon || 'fas fa-puzzle-piece',
                group: def.group || 'Más',
                perm: def.perm || null,
                render: def.render,
            });
            if (added && E && isOpen()) { renderNav(); }
            return added;
        },
    };

    function boot(erp) {
        if (E || !erp) { return; }
        E = erp;
        bindHandlers();
    }

    if (window.ErpChat) { boot(window.ErpChat); }
    else { $(document).one('erp:ready', function (e, erp) { boot(erp || window.ErpChat); }); }
})(window.jQuery);
