/*!
 * HelpdeskErp · núcleo de Gestión (ERP) dentro del chat — window.ErpChat
 *
 * Base compartida por el panel derecho y los modales de Gestión: acceso a
 * las rutas manager.helpdesk.erp.chat.* (siempre con el id del cliente del
 * helpdesk, nunca el id ERP), formato (dinero, fechas), estados estándar
 * (bloqueado, cargando, sin conexión…), renderizadores puros de albarán,
 * factura, líneas, totales y direcciones, hojas internas de modal y
 * apertura de modales por atributos [data-erp-open].
 *
 * Eventos en document:
 *   erp:overview-loaded  [resp, customerId]   cada vez que llega el resumen
 *   erp:orders-ready     [resp, customerId]   el escaneo Oracle de pedidos terminó (resp es el
 *                                             resumen con SOLO los pedidos pedidos de nuevo)
 *   erp:retry            [target, $btn]       pulsaron un "Reintentar" de stateHtml()
 *   erp:open             [name, pane, extra]  ErpChat.open() (además del click sintético)
 *
 * Pedidos en carga (escaneo Oracle ~35 s): se escucha '.erp.orders.ready' en el
 * canal privado del contacto (helpdesk.erp.customer.{id}) y en el histórico por
 * email (erp-orders-ready.{md5}), con hasta 3 sondeos de respaldo. Al llegar se
 * recarga SOLO la sección de pedidos (GET …/overview/orders), no las 8.
 *
 * El ERP es SOLO LECTURA: aquí no hay ninguna escritura.
 *
 * Fuente: modules/HelpdeskErp/public/js/ — copiar a public/modules/helpdeskerp/js/ tras editar.
 */
(function ($) {
    'use strict';

    if (!$ || window.ErpChat) { return; }

    var CLIENT_TTL_MS = 5 * 60 * 1000;
    var MAX_POLLS = 3;
    // Canal privado por contacto del helpdesk (ErpOrdersReady::CUSTOMER_CHANNEL_PREFIX)
    // y nombre del evento (broadcastAs 'erp.orders.ready', con punto delante en Echo).
    var CUSTOMER_CHANNEL = 'helpdesk.erp.customer.';
    var ORDERS_EVENT = '.erp.orders.ready';
    var READY_DEDUPE_MS = 5000;

    // Estados reales de PEDIDOCLIESTADO (Oracle, 24-sep-2026). El manager ya
    // manda status_description; este mapa es el respaldo para el código solo.
    var ERP_STATUS = {
        0: 'Anulado', 1: 'Creación', 2: 'Revisión transportista', 3: 'Aceptación financiera',
        4: 'Pendiente de mercancía', 5: 'Listo para servir', 6: 'Sirviéndose', 7: 'Servido',
        8: 'Incidencia', 9: 'Aceptación financiera reservando', 10: 'Servido parcialmente', 11: 'Pendiente transferencia'
    };
    var ERP_STATUS_KIND = {
        0: 'blocked', 1: 'pending', 2: 'progress', 3: 'progress', 4: 'pending', 5: 'progress',
        6: 'progress', 7: 'done', 8: 'blocked', 9: 'progress', 10: 'progress', 11: 'pending'
    };
    var MONTHS = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    var SMALL_WORDS = { de: 1, del: 1, la: 1, las: 1, el: 1, los: 1, y: 1, e: 1, en: 1, a: 1, por: 1 };

    var overviews = {};      // customerId -> {promise, resp, at}
    var subscribed = {};     // canal -> true
    var polls = {};          // customerId -> nº de sondeos hechos
    var customerChannels = {}; // customerId -> canal helpdesk.erp.customer.{id} suscrito
    var readyInflight = {};  // customerId -> promesa de recarga de pedidos en curso
    var readyAt = {};        // customerId -> ms de la última recarga por pedidos listos

    /* ── Utilidades ────────────────────────────────────────────────── */

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function escAttr(s) { return esc(s).replace(/`/g, '&#96;'); }

    function isBlank(v) { return v == null || (typeof v === 'string' && v.trim() === ''); }

    function num(n) {
        if (n == null || n === '') { return null; }
        var v = typeof n === 'number' ? n : parseFloat(String(n).replace(',', '.'));
        return isFinite(v) ? v : null;
    }

    function money(n) {
        var v = num(n);
        if (v === null) { return '—'; }
        var neg = v < 0;
        var fixed = Math.abs(v).toFixed(2).split('.');
        var ints = fixed[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return (neg ? '−' : '') + ints + ',' + fixed[1] + ' €';
    }

    // "2025-09-16", "2025-09-16 20:06:39" o ISO → Date local (sin desfase UTC
    // para las fechas sin hora, que el ERP da en horario peninsular).
    function parseDate(iso) {
        if (isBlank(iso)) { return null; }
        var s = String(iso).trim();
        var m = /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/.exec(s);
        if (m && !/[zZ]|[+-]\d{2}:?\d{2}$/.test(s.substring(10))) {
            return new Date(+m[1], +m[2] - 1, +m[3], +(m[4] || 0), +(m[5] || 0), +(m[6] || 0));
        }
        var d = new Date(s);
        return isNaN(d.getTime()) ? null : d;
    }

    function pad(n) { return (n < 10 ? '0' : '') + n; }

    function date(iso, withYear) {
        var d = parseDate(iso);
        if (!d) { return '—'; }
        var out = d.getDate() + ' ' + MONTHS[d.getMonth()];
        if (withYear !== false) { out += ' ' + d.getFullYear(); }
        return out;
    }

    function dateTime(iso) {
        var d = parseDate(iso);
        if (!d) { return '—'; }
        var hasTime = /\d{2}:\d{2}/.test(String(iso)) && (d.getHours() || d.getMinutes());
        return date(iso, true) + (hasTime ? ' · ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) : '');
    }

    function startOfDay(d) { return new Date(d.getFullYear(), d.getMonth(), d.getDate()); }

    function relative(iso) {
        var d = parseDate(iso);
        if (!d) { return '—'; }
        var now = new Date();
        var diffMs = now.getTime() - d.getTime();
        var days = Math.round((startOfDay(now) - startOfDay(d)) / 86400000);

        if (diffMs >= 0 && diffMs < 60000) { return 'hace un momento'; }
        if (diffMs >= 0 && diffMs < 3600000 && days === 0) { return 'hace ' + Math.floor(diffMs / 60000) + ' min'; }
        if (diffMs >= 0 && days === 0 && /\d{2}:\d{2}/.test(String(iso)) && (d.getHours() || d.getMinutes())) {
            return 'hace ' + Math.floor(diffMs / 3600000) + ' h';
        }
        if (days === 0) { return 'hoy'; }
        if (days === 1) { return 'ayer'; }
        if (days === -1) { return 'mañana'; }
        if (days > 1 && days < 7) { return 'hace ' + days + ' días'; }
        if (days < -1 && days > -31) { return 'en ' + (-days) + ' días'; }
        return date(iso, d.getFullYear() !== now.getFullYear());
    }

    function widthClass(pct) {
        var v = num(pct);
        if (v === null || v < 0) { v = 0; }
        if (v > 100) { v = 100; }
        return 'erc-w-' + (Math.round(v / 5) * 5);
    }

    function initials(name) {
        var parts = String(name || '').trim().split(/\s+/).filter(Boolean);
        if (!parts.length) { return '—'; }
        return (parts[0].charAt(0) + (parts.length > 1 ? parts[1].charAt(0) : '')).toUpperCase();
    }

    // "POCOMACO" → "Pocomaco", "TIENDA DIEGO DE LEON" → "Tienda Diego de Leon".
    // Solo cambia textos en mayúsculas; "Caza" o "Aceptación financiera" quedan igual.
    function niceText(s) {
        var str = String(s == null ? '' : s).trim();
        if (!str || str !== str.toUpperCase() || !/[A-ZÁÉÍÓÚÑ]/.test(str)) { return str; }
        return str.toLowerCase().split(/(\s+)/).map(function (w, i) {
            if (!w.trim() || (i > 0 && SMALL_WORDS[w])) { return w; }
            return w.charAt(0).toUpperCase() + w.slice(1);
        }).join('');
    }

    // Texto de un código del ERP: la descripción del manager si la hay, si no
    // "<prefijo> <código>" como respaldo ("Almacén 6"); '' sin ninguno.
    function codeLabel(description, code, prefix) {
        if (description && typeof description === 'object') { return codeLabel(description.description, description.id, prefix); }
        if (!isBlank(description)) { return niceText(description); }
        if (code && typeof code === 'object') { return codeLabel(code.description, code.id, prefix); }
        if (isBlank(code) || typeof code === 'boolean') { return ''; }
        return (prefix ? prefix + ' ' : '') + String(code).trim();
    }

    function toast(kind, msg) {
        if (window.toastr && typeof window.toastr[kind] === 'function') { window.toastr[kind](msg); }
    }

    /* ── Configuración (erp-index.blade.php) ──────────────────────── */

    function cfg() {
        var $c = $('#ercConfig');
        return {
            base: String($c.data('base') || '/panel/helpdesk/customers').replace(/\/+$/, ''),
            perms: $c.data('perms') || {},
        };
    }

    function can(key) {
        var perms = cfg().perms;
        return !!(perms && perms[key]);
    }

    function customerId() {
        var id = $('.bv-right').first().data('customer-id');
        if (!id && window.HDCommerce && typeof window.HDCommerce.customerId === 'function') {
            id = window.HDCommerce.customerId();
        }
        return id ? String(id) : null;
    }

    function base(id) {
        var cid = id || customerId();
        return cid ? cfg().base + '/' + encodeURIComponent(cid) + '/erp' : null;
    }

    /* ── Peticiones ───────────────────────────────────────────────── */

    // Siempre resuelve (nunca rechaza) con un objeto {success, state, data,
    // message…}: los fallos HTTP se traducen a un estado pintable.
    function request(url, params, timeoutMs) {
        return new Promise(function (resolve) {
            if (!url) {
                resolve({ success: false, state: 'nocustomer', data: null, message: 'Selecciona una conversación con cliente.' });
                return;
            }
            $.ajax({
                url: url,
                method: 'GET',
                data: params || {},
                dataType: 'json',
                timeout: timeoutMs || 45000,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
                },
            }).done(function (resp) {
                resolve(resp && typeof resp === 'object' ? resp : { success: false, state: 'down', data: null, message: 'Respuesta no válida de Gestión.' });
            }).fail(function (xhr, textStatus) {
                var j = (xhr && xhr.responseJSON) || {};
                var status = xhr ? xhr.status : 0;
                var state = 'down';
                var message = j.message || 'Gestión no responde ahora mismo.';

                if (status === 403) { state = 'forbidden'; message = j.message || 'Sin permiso para ver esta sección.'; }
                else if (status === 404) { state = 'unavailable'; message = j.message || 'No encontrado en Gestión.'; }
                else if (status === 422) {
                    state = 'invalid';
                    var errs = j.errors ? Object.values(j.errors) : [];
                    message = (errs[0] && errs[0][0]) || j.message || 'Filtro no válido.';
                } else if (status === 429) { state = 'down'; message = 'Demasiadas consultas seguidas, espera un momento.'; }
                else if (textStatus === 'timeout') { message = 'Gestión tardó demasiado en responder.'; }

                resolve({ success: false, state: state, reason: j.reason || null, data: null, message: message, http_status: status });
            });
        });
    }

    function overview(force) {
        var id = customerId();
        if (!id) { return request(null); }
        // Un "Buscar de nuevo" manual vuelve a armar los sondeos automáticos.
        if (force) { delete polls[id]; }

        var entry = overviews[id];
        var fresh = entry && entry.resp && (Date.now() - entry.at) < CLIENT_TTL_MS;
        if (!force && entry && (entry.pending || fresh)) { return entry.promise; }

        var promise = request(base(id) + '/overview', force ? { force: 1 } : {}, 60000).then(function (resp) {
            var e = overviews[id];
            if (e && e.promise === promise) {
                e.pending = false;
                e.resp = resp;
                e.at = Date.now();
                // Un fallo de red no se queda en caché: el siguiente overview() reintenta.
                if (!resp.success) { delete overviews[id]; }
            }
            watchOrders(id, resp);
            subscribeCustomer(id, resp);
            $(document).trigger('erp:overview-loaded', [resp, id]);
            return resp;
        });

        overviews[id] = { promise: promise, pending: true, resp: null, at: 0 };
        return promise;
    }

    function ordersState(resp) {
        var o = resp && resp.data && resp.data.sections ? resp.data.sections.orders : null;
        return o ? o.state : null;
    }

    function cachedOverview(id) {
        var e = overviews[id || customerId()];
        return e && e.resp ? e.resp : null;
    }

    function invalidate(id) {
        if (id) { delete overviews[id]; } else { overviews = {}; }
    }

    function section(name, params) {
        return request(base() ? base() + '/sections/' + encodeURIComponent(name) : null, params || {});
    }

    function orderDetail(id) {
        return request(base() ? base() + '/orders/' + encodeURIComponent(id) : null);
    }

    function deliveryNote(id) {
        return request(base() ? base() + '/delivery-notes/' + encodeURIComponent(id) : null);
    }

    function invoice(id) {
        return request(base() ? base() + '/invoices/' + encodeURIComponent(id) : null);
    }

    /* ── Pedidos en carga: Reverb + sondeo de respaldo ─────────────── */

    // Recarga SOLO los pedidos (GET …/overview/orders): el servidor vuelve a
    // pedir la sección orders al manager y saca el resto de su caché, y
    // responde con el resumen completo (mismo formato), así las alertas y
    // estadísticas que dependen de los pedidos salen recalculadas. Si la ruta
    // ligera no existe o falla, cae a un overview() normal (sin force: tampoco
    // reenvía al manager lo que el servidor tiene en caché).
    function refreshOrders(id) {
        var url = base(id) ? base(id) + '/overview/orders' : null;
        return request(url, {}, 60000).then(function (resp) {
            if (!resp || !resp.success || !resp.data || !resp.data.sections) {
                delete overviews[id];
                return overview(false);
            }
            overviews[id] = { promise: Promise.resolve(resp), pending: false, resp: resp, at: Date.now() };
            watchOrders(id, resp);
            subscribeCustomer(id, resp);
            $(document).trigger('erp:overview-loaded', [resp, id]);
            return resp;
        });
    }

    function ordersReady(id) {
        if (readyInflight[id]) { return readyInflight[id]; }
        // El evento llega por dos canales (email y contacto): una sola recarga.
        if (readyAt[id] && (Date.now() - readyAt[id]) < READY_DEDUPE_MS && ordersState(cachedOverview(id)) !== 'loading') {
            return Promise.resolve(cachedOverview(id));
        }
        var p = refreshOrders(id).then(function (resp) {
            delete readyInflight[id];
            readyAt[id] = Date.now();
            var st = ordersState(resp);
            if (st && st !== 'loading') {
                delete polls[id];
                $(document).trigger('erp:orders-ready', [resp, id]);
            }
            return resp;
        });
        readyInflight[id] = p;
        return p;
    }

    // Canal privado del contacto (helpdesk.erp.customer.{id}): no depende de
    // que el email de Gestión coincida con el del helpdesk. Uno solo a la vez:
    // al cambiar de conversación se deja el anterior (y si sus pedidos seguían
    // cargando, se olvida su resumen para pedirlo de nuevo al volver).
    function subscribeCustomer(id, resp) {
        if (!id || !window.Echo || typeof window.Echo.private !== 'function') { return; }
        if (!resp || !resp.success || !resp.data || !resp.data.erp_id) { return; }
        var key = String(id);

        Object.keys(customerChannels).forEach(function (other) {
            if (other === key) { return; }
            try { window.Echo.leave(customerChannels[other]); } catch (e) { /* noop */ }
            delete customerChannels[other];
            if (ordersState(cachedOverview(other)) === 'loading') { delete overviews[other]; }
        });

        if (customerChannels[key]) { return; }
        var name = CUSTOMER_CHANNEL + key;
        customerChannels[key] = name;
        try {
            window.Echo.private(name).listen(ORDERS_EVENT, function () {
                if (customerId() === key) { ordersReady(key); } else { delete overviews[key]; }
            });
        } catch (e) {
            delete customerChannels[key];
        }
    }

    function watchOrders(id, resp) {
        var rt = resp && resp.data ? resp.data.realtime : null;
        if (!rt) { return; }

        if (rt.channel && !subscribed[rt.channel] && window.Echo && typeof window.Echo.private === 'function') {
            subscribed[rt.channel] = true;
            try {
                window.Echo.private(rt.channel).listen(rt.event || ORDERS_EVENT, function () {
                    try { window.Echo.leave(rt.channel); } catch (e) { /* noop */ }
                    delete subscribed[rt.channel];
                    if (customerId() === id) { ordersReady(id); } else { delete overviews[id]; }
                });
            } catch (e) {
                delete subscribed[rt.channel];
            }
        }

        // Respaldo sin Reverb (o si el email de Gestión no coincide con el del
        // helpdesk y el evento nunca llega): hasta 3 sondeos espaciados.
        polls[id] = (polls[id] || 0) + 1;
        if (polls[id] > MAX_POLLS) { return; }
        setTimeout(function () {
            if (customerId() !== id) { return; }
            var cur = cachedOverview(id);
            var st = cur && cur.data && cur.data.sections && cur.data.sections.orders ? cur.data.sections.orders.state : 'loading';
            if (st === 'loading') { ordersReady(id); }
        }, Math.max(5, parseInt(rt.retry_after, 10) || 35) * 1000);
    }

    /* ── Estados estándar ─────────────────────────────────────────── */

    function stateHtml(stateObj, label) {
        var st = typeof stateObj === 'string' ? { state: stateObj } : (stateObj || {});
        var state = st.state || 'down';
        var lbl = label ? esc(label) : '';
        var msg = st.message ? esc(st.message) : '';
        var retry = escAttr(st.retry || label || '');

        function box(kind, icon, title, sub, extra) {
            return '<div class="erc-state erc-state--' + kind + '">' +
                '<i class="' + icon + '"></i>' +
                '<div class="body">' +
                    (title ? '<span class="t">' + title + '</span>' : '') +
                    (sub ? '<span class="s">' + sub + '</span>' : '') +
                '</div>' + (extra || '') +
            '</div>';
        }

        switch (state) {
            case 'ok':
                return '';
            case 'blocked':
                return box('blocked', 'fas fa-lock', lbl || 'Sección bloqueada', 'Pendiente de permiso en Oracle');
            case 'loading':
                return box('loading', 'fas fa-spinner fa-spin', 'Buscando en Gestión…',
                    lbl ? lbl + ': la consulta a Oracle puede tardar unos segundos.' : 'La consulta a Oracle puede tardar unos segundos.');
            case 'unavailable':
                return box('unavailable', 'fas fa-circle-info', lbl || 'No disponible',
                    st.reason === 'endpoint_missing' ? 'Todavía no disponible en Gestión.' : (msg || 'No disponible en Gestión.'));
            case 'forbidden':
                return box('forbidden', 'fas fa-user-lock', lbl || 'Sin permiso', 'No tienes permiso para ver esta sección.');
            case 'unlinked':
                return box('unlinked', 'fas fa-link-slash', 'Sin cliente en Gestión', msg || 'Este cliente no está vinculado con Gestión.');
            case 'nocustomer':
                return box('unlinked', 'far fa-user', 'Sin cliente', 'Selecciona una conversación con cliente.');
            case 'invalid':
                return box('unavailable', 'fas fa-filter', lbl || 'Filtro no válido', msg);
            case 'empty':
                return box('empty', st.icon || 'fas fa-inbox', lbl || 'Sin datos', msg);
            default: // down
                return box('down', 'fas fa-plug-circle-xmark', lbl ? lbl + ': sin conexión' : 'Sin conexión con Gestión',
                    msg || 'Gestión no responde ahora mismo.',
                    '<button type="button" class="erc-link-btn" data-erp-retry="' + retry + '">Reintentar</button>');
        }
    }

    function skeleton(rows, kind) {
        var n = rows || 3;
        var out = '';
        for (var i = 0; i < n; i++) { out += '<span class="erc-skel' + (kind ? ' erc-skel--' + kind : '') + '"></span>'; }
        return out;
    }

    /* ── Composer ─────────────────────────────────────────────────── */

    // Escribe en el composer del inbox SIN enviar (en línea nueva si ya hay
    // texto). Sin composer, copia al portapapeles.
    function insert(text) {
        if (isBlank(text)) { return false; }
        var $ta = $('.bv-composer-input').first();

        if ($ta.length && $ta.is(':visible')) {
            var current = String($ta.val() || '').replace(/\s+$/, '');
            var next = current ? current + '\n\n' + text : String(text);
            $ta.val(next).trigger('input');
            var el = $ta.get(0);
            el.focus();
            if (el.setSelectionRange) { el.setSelectionRange(next.length, next.length); }
            return true;
        }

        return copy(text);
    }

    function copy(text) {
        var str = String(text);
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(str).then(function () { toast('success', 'Copiado'); },
                function () { legacyCopy(str) ? toast('success', 'Copiado') : toast('warning', 'No se pudo copiar'); });
            return true;
        }
        if (legacyCopy(str)) { toast('success', 'Copiado'); return true; }
        toast('warning', 'No se pudo copiar');
        return false;
    }

    function legacyCopy(str) {
        var $tmp = $('<textarea readonly class="erc-offscreen"></textarea>').val(str).appendTo('body');
        var ok = false;
        try {
            $tmp.get(0).select();
            ok = document.execCommand('copy');
        } catch (e) { ok = false; }
        $tmp.remove();
        return ok;
    }

    /* ── Abrir modales por contrato de atributos ──────────────────── */

    // Dispara un click real sobre un elemento con los atributos del contrato,
    // para que lo recoja el handler delegado de quien posea cada modal.
    function open(name, pane, extra) {
        var el = document.createElement('button');
        el.type = 'button';
        el.hidden = true;
        if (name === 'order') {
            el.setAttribute('data-erp-order-open', String(pane || ''));
        } else {
            el.setAttribute('data-erp-open', String(name || ''));
            if (pane) { el.setAttribute('data-erp-pane', String(pane)); }
        }
        document.body.appendChild(el);
        $(document).trigger('erp:open', [name, pane || null, extra || null]);
        el.click();
        document.body.removeChild(el);
    }

    function erpIdFor(id) {
        var resp = cachedOverview(id);
        if (resp && resp.data && resp.data.erp_id) { return String(resp.data.erp_id); }
        var lookup = $('.bv-right').first().data('lookup-erp-id');
        return lookup ? String(lookup) : null;
    }

    function openOrder(orderId) {
        if (!orderId) { return; }
        if (typeof window.openErpOrderWorkspace !== 'function') {
            toast('warning', 'El detalle de pedido de Gestión no está disponible.');
            return;
        }
        var erpId = erpIdFor();
        if (erpId) { window.openErpOrderWorkspace(erpId, String(orderId)); return; }
        overview().then(function (resp) {
            var eid = resp && resp.data ? resp.data.erp_id : null;
            if (eid) { window.openErpOrderWorkspace(String(eid), String(orderId)); }
            else { toast('warning', (resp && resp.message) || 'Este cliente no está vinculado con Gestión.'); }
        });
    }

    /* ── Renderizadores puros ─────────────────────────────────────── */

    // Estado del pedido: código de PEDIDOCLIESTADO ("7"), descripción
    // ("Servido") o booleano (detalle: activo/anulado). Con `description`
    // (status_description del manager) se usa esa etiqueta si el código no
    // está en el mapa.
    function statusInfo(codeOrDesc, description) {
        if (codeOrDesc == null || codeOrDesc === '') {
            return isBlank(description) ? { label: 'Sin estado', kind: 'closed' } : statusInfo(description);
        }
        if (typeof codeOrDesc === 'boolean') { return { label: codeOrDesc ? 'Activo' : 'Anulado', kind: codeOrDesc ? 'progress' : 'blocked' }; }
        var s = String(codeOrDesc).trim();
        if (/^\d+$/.test(s)) {
            var code = parseInt(s, 10);
            if (ERP_STATUS[code]) { return { label: ERP_STATUS[code], kind: ERP_STATUS_KIND[code] || 'closed' }; }
            return isBlank(description) ? { label: 'Estado ' + code, kind: 'closed' } : statusInfo(description);
        }
        var l = s.toLowerCase();
        var kind = 'progress';
        if (/cancel|anul|baja|rechaz|incidencia/.test(l)) { kind = 'blocked'; }
        else if (/parcial/.test(l)) { kind = 'progress'; }
        else if (/entreg|cerrad|finaliz|factur/.test(l)) { kind = 'closed'; }
        else if (/servid|enviad|expedid/.test(l)) { kind = 'done'; }
        else if (/pendient|creaci|nuevo/.test(l)) { kind = 'pending'; }
        return { label: s.charAt(0).toUpperCase() + s.slice(1).toLowerCase(), kind: kind };
    }

    function statusPill(codeOrDesc, description) {
        var info = statusInfo(codeOrDesc, description);
        return '<span class="erc-tag erc-tag--' + info.kind + '">' + esc(info.label) + '</span>';
    }

    function lineAmount(l) {
        var withTax = num(l.total_with_taxes);
        if (withTax !== null) { return withTax; }
        var units = num(l.units);
        var price = num(l.price != null ? l.price : l.price_bi);
        var disc = num(l.discount_percent) || 0;
        var tax = num(l.tax_percent) || 0;
        var sub = num(l.subtotal != null ? l.subtotal : l.total_bi);
        if (sub === null) { sub = (units === null ? 1 : units) * (price || 0) * (1 - disc / 100); }
        return sub * (1 + tax / 100);
    }

    function lines(list) {
        var arr = Array.isArray(list) ? list : [];
        if (!arr.length) {
            return stateHtml({ state: 'empty', message: 'Sin líneas o sin acceso a ellas desde Gestión.', icon: 'fas fa-box-open' }, 'Sin líneas');
        }
        return '<div class="erc-lines">' + arr.map(function (l) {
            var a = l.article || {};
            var name = a.description || l.description || 'Artículo';
            var ref = a.reference || a.code || l.code || '';
            var units = num(l.units);
            var disc = num(l.discount_percent) || 0;
            var tax = num(l.tax_percent);
            var amount = lineAmount(l);
            var price = num(l.price != null ? l.price : l.price_bi);
            var chips = '';
            if (disc > 0) { chips += '<span class="erc-lnchip erc-lnchip--disc">−' + esc(disc) + '%</span>'; }
            if (tax !== null && tax > 0) { chips += '<span class="erc-lnchip">IVA ' + esc(tax) + '%</span>'; }
            var sku = [ref, a.ean ? 'EAN ' + a.ean : '', price !== null ? money(price) + ' /ud sin IVA' : ''].filter(Boolean).join(' · ');
            return '<div class="erc-line' + (amount === 0 ? ' erc-line--free' : '') + '">' +
                '<span class="erc-thumb"><i class="fas fa-box"></i></span>' +
                '<div class="body">' +
                    '<span class="nm">' + esc(name) + '</span>' +
                    (sku ? '<span class="sku">' + esc(sku) + '</span>' : '') +
                    (chips ? '<span class="chips">' + chips + '</span>' : '') +
                '</div>' +
                '<span class="qty">×' + esc(units === null ? 1 : (units % 1 === 0 ? units : units.toFixed(2))) + '</span>' +
                '<span class="amt">' + (amount === 0 ? 'Gratis' : money(amount)) + '</span>' +
            '</div>';
        }).join('') + '</div>';
    }

    var TOTAL_LABELS = [
        ['lines_total_bi', 'Base imponible', ''],
        ['lines_total', 'Total líneas sin IVA', ''],
        ['payments_total', 'Pagado', 'is-good'],
        ['lines_total_with_taxes', 'Total con IVA', 'is-total'],
        ['total', 'Total', 'is-total'],
    ];

    // Acepta el objeto totals del manager o una lista [{label, value, kind}]
    // (kind: '', 'is-muted', 'is-good', 'is-total'). Con `lineList` y sin
    // total con IVA en el objeto (pedidos), el total con IVA se calcula de
    // las líneas para que cuadre con los importes que pinta lines().
    function totals(obj, lineList) {
        var rows = [];
        if (Array.isArray(obj)) {
            rows = obj;
        } else if (obj && typeof obj === 'object') {
            var hasTotal = false;
            TOTAL_LABELS.forEach(function (t) {
                if (obj[t[0]] == null) { return; }
                if (t[2] === 'is-total') {
                    if (hasTotal) { return; }
                    hasTotal = true;
                }
                rows.push({ label: t[1], value: obj[t[0]], kind: t[2] });
            });
            if (!hasTotal && Array.isArray(lineList) && lineList.length) {
                var sum = lineList.reduce(function (acc, l) { return acc + lineAmount(l); }, 0);
                rows.forEach(function (r) { if (r.label === 'Total líneas sin IVA') { r.label = 'Base imponible'; } });
                rows.push({ label: 'Total con IVA', value: Math.round(sum * 100) / 100, kind: 'is-total' });
                hasTotal = true;
            }
            // Sin total con IVA ni líneas: el total de líneas pasa a ser la fila destacada.
            if (!hasTotal) {
                for (var i = 0; i < rows.length; i++) {
                    if (rows[i].label === 'Total líneas sin IVA') { rows[i].kind = 'is-total'; break; }
                }
            }
        }
        if (!rows.length) { return ''; }
        var total = rows.filter(function (r) { return r.kind === 'is-total'; });
        var rest = rows.filter(function (r) { return r.kind !== 'is-total'; });
        return '<div class="erc-totals">' + rest.concat(total).map(function (r) {
            var v = typeof r.value === 'string' && /€|—/.test(r.value) ? r.value : money(r.value);
            return '<div class="erc-tot' + (r.kind ? ' ' + esc(r.kind) : '') + '"><span>' + esc(r.label) + '</span><span>' + esc(v) + '</span></div>';
        }).join('') + '</div>';
    }

    function addressLines(addr) {
        var a = addr || {};
        var street = [a.street || a.address, a.number].filter(function (v) { return !isBlank(v); }).map(function (v) { return String(v).trim(); }).join(' ');
        var city = [a.postal_code, a.city].filter(function (v) { return !isBlank(v); }).map(function (v) { return String(v).trim(); }).join(' ');
        var region = [a.province, a.country].filter(function (v) { return !isBlank(v); }).map(function (v) { return String(v).trim(); }).join(', ');
        return [street, city, region].filter(Boolean);
    }

    function addressText(addr) { return addressLines(addr).join(', '); }

    function address(addr) {
        var a = addr || {};
        var ln = addressLines(a);
        var kinds = [];
        if (a.default_shipping) { kinds.push('Envío por defecto'); }
        if (a.default_billing) { kinds.push('Facturación por defecto'); }
        var off = a.available === false || a.available === 0 || a.available === '0';
        var cls = 'erc-addr' + (a.default_shipping ? ' erc-addr--shipping' : '') + (off ? ' erc-addr--off' : '');
        return '<div class="' + cls + '">' +
            '<div class="erc-addr-hd">' +
                '<span class="erc-addr-kind">' + esc(kinds.length ? kinds.join(' · ') : 'Dirección') + '</span>' +
                (off ? '<span class="erc-tag erc-tag--blocked">Inactiva</span>' : '') +
            '</div>' +
            (ln.length ? ln.map(function (l) { return '<div class="erc-addr-line">' + esc(l) + '</div>'; }).join('') : '<div class="erc-addr-line erc-muted">Sin datos de dirección.</div>') +
            (a.observations ? '<div class="erc-addr-note">' + esc(a.observations) + '</div>' : '') +
        '</div>';
    }

    function kv(k, v, mono, wide) {
        if (isBlank(v)) { return ''; }
        return '<div' + (wide ? ' class="is-wide"' : '') + '><span class="k">' + esc(k) + '</span><span class="v' + (mono ? ' mono' : '') + '">' + esc(v) + '</span></div>';
    }

    function obs(text) {
        if (isBlank(text)) { return ''; }
        return '<div class="erc-obs"><span class="k">Observaciones</span><span class="v">' + esc(String(text).replace(/\r\n?/g, '\n').trim()) + '</span></div>';
    }

    function deliveryNoteHtml(data) {
        var d = data || {};
        return '<div class="erc-stack">' +
            '<div class="erc-card"><div class="erc-card-body">' +
                '<div class="erc-kv">' +
                    kv('Nº albarán', d.number, true) +
                    kv('Fecha', d.date ? date(d.date, true) : null) +
                    kv('Estado', d.status === false || d.status === 0 ? 'Anulado' : 'Activo') +
                    kv('Tipo', codeLabel(d.type_description, null)) +
                    kv('Almacén', codeLabel(d.warehouse_description, d.warehouse)) +
                    kv('Catálogo', codeLabel(d.catalog_description, d.catalog)) +
                    kv('Factura', d.invoice_id ? String(d.invoice_id) : 'Sin facturar', !!d.invoice_id) +
                    kv('Puntos', d.loyalty_points ? String(d.loyalty_points) : null, true) +
                '</div>' +
            '</div></div>' +
            lines(d.lines) +
            totals(d.totals, d.lines) +
            obs(d.observations) +
        '</div>';
    }

    function invoiceHtml(data) {
        var d = data || {};
        var ref = [d.series, d.number].filter(function (v) { return !isBlank(v); }).join('-') + (d.year ? '/' + d.year : '');
        var cust = d.customer || {};
        var custLines = [cust.name, cust.cif].filter(function (v) { return !isBlank(v); }).join(' · ');
        return '<div class="erc-stack">' +
            '<div class="erc-card"><div class="erc-card-body">' +
                '<div class="erc-kv">' +
                    kv('Factura', ref || d.id, true) +
                    kv('Fecha', d.date ? date(d.date, true) : null) +
                    kv('Tipo', d.simplified ? 'Simplificada' : 'Completa') +
                    kv('Forma de pago', d.payment_method) +
                    kv('Estado', d.status != null ? statusInfo(d.status).label : null) +
                    kv('Almacén', codeLabel(d.warehouse_description, d.warehouse)) +
                '</div>' +
            '</div></div>' +
            (custLines || cust.address ? '<div class="erc-card"><div class="erc-card-head">Facturado a</div><div class="erc-card-body">' +
                (custLines ? '<div class="erc-addr-line">' + esc(custLines) + '</div>' : '') +
                address({ street: cust.address, postal_code: cust.postal_code, city: cust.city, province: cust.province, country: cust.country }) +
            '</div></div>' : '') +
            lines(d.lines) +
            totals(d.totals) +
            obs(d.observations) +
        '</div>';
    }

    /* ── Hojas internas de modal ──────────────────────────────────── */

    // Pinta una .erc-sheet DENTRO del cuerpo del modal (nunca un modal sobre
    // otro). opts: {title, label, icon, html, foot, id}. Devuelve el $sheet.
    function sheetOpen($modalBody, opts) {
        var $host = $($modalBody).first();
        if (!$host.length) { return $(); }
        var o = opts || {};
        sheetClose($host);
        $host.addClass('erc-sheet-host is-erc-sheet-open');
        $host.closest('.bv-modal-dialog').addClass('is-erc-sheet-open');

        var $sheet = $(
            '<div class="erc-sheet" role="dialog" aria-modal="true">' +
                '<div class="erc-sheet-head">' +
                    '<span class="ic"><i class="' + escAttr(o.icon || 'fas fa-file-lines') + '"></i></span>' +
                    '<span class="tt">' +
                        (o.label ? '<span class="lbl">' + esc(o.label) + '</span>' : '') +
                        '<span class="ttl">' + esc(o.title || '') + '</span>' +
                    '</span>' +
                    '<button type="button" class="erc-icon-btn" data-erc-sheet-close aria-label="Cerrar"><i class="fas fa-xmark"></i></button>' +
                '</div>' +
                '<div class="erc-sheet-body"></div>' +
                '<div class="erc-sheet-foot"></div>' +
            '</div>'
        );
        if (o.id) { $sheet.attr('data-erc-sheet', o.id); }
        // html/foot los construye quien llama con esc(); se insertan tal cual.
        $sheet.find('.erc-sheet-body').html(o.html || '');
        $sheet.find('.erc-sheet-foot').html(o.foot != null ? o.foot
            : '<button type="button" class="erc-btn erc-btn--outline" data-erc-sheet-close>Volver</button>');
        $host.append($sheet);
        $host.scrollTop(0);
        return $sheet;
    }

    function sheetClose($modalBody) {
        var $hosts = $modalBody ? $($modalBody) : $('.erc-sheet-host');
        $hosts.find('> .erc-sheet').remove();
        $hosts.removeClass('is-erc-sheet-open');
        $hosts.each(function () {
            var $dlg = $(this).closest('.bv-modal-dialog');
            if (!$dlg.find('.erc-sheet').length) { $dlg.removeClass('is-erc-sheet-open'); }
        });
        $(document).trigger('erp:sheet-closed');
    }

    function sheetUpdate($sheet, opts) {
        var o = opts || {};
        if (o.title != null) { $sheet.find('.erc-sheet-head .ttl').text(o.title); }
        if (o.html != null) { $sheet.find('.erc-sheet-body').html(o.html); }
        if (o.foot != null) { $sheet.find('.erc-sheet-foot').html(o.foot); }
        return $sheet;
    }

    /* ── Handlers globales ────────────────────────────────────────── */

    $(document).on('click', '[data-erc-sheet-close]', function (e) {
        e.preventDefault();
        sheetClose($(this).closest('.erc-sheet-host'));
    });

    // ESC cierra antes la hoja que el modal (fase de captura).
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') { return; }
        var $open = $('.erc-sheet-host.is-erc-sheet-open').filter(function () { return $(this).closest('.bv-modal').hasClass('on'); });
        if (!$open.length) { return; }
        e.stopImmediatePropagation();
        e.preventDefault();
        sheetClose($open.last());
    }, true);

    $(document).on('click', '[data-erp-retry]', function (e) {
        e.preventDefault();
        var target = String($(this).attr('data-erp-retry') || '');
        $(document).trigger('erp:retry', [target, $(this)]);
        if (target === 'overview') { overview(true); }
    });

    // Detalle de pedido de Gestión desde cualquier sitio. Solo este handler
    // implementa [data-erp-order-open]: no lo dupliquéis en otros ficheros.
    $(document).on('click', '[data-erp-order-open]', function (e) {
        if (e.isDefaultPrevented()) { return; }
        e.preventDefault();
        openOrder(String($(this).attr('data-erp-order-open') || ''));
    });

    // Cambio de conversación (el panel derecho se sustituye entero): el resumen
    // del cliente anterior sigue en caché por id, así que no hay nada que limpiar
    // salvo las hojas abiertas en modales ya cerrados.
    $(document).on('bv:modal:open', function () {
        $('.bv-modal:not(.on) .erc-sheet-host.is-erc-sheet-open').each(function () { sheetClose($(this)); });
    });

    /* ── API pública ──────────────────────────────────────────────── */

    window.ErpChat = {
        version: 2,
        // contexto
        customerId: customerId,
        base: base,
        can: can,
        erpId: erpIdFor,
        // datos
        overview: overview,
        cachedOverview: cachedOverview,
        refreshOrders: ordersReady,
        invalidate: invalidate,
        section: section,
        orderDetail: orderDetail,
        deliveryNote: deliveryNote,
        invoice: invoice,
        request: request,
        // formato
        money: money,
        num: num,
        date: date,
        dateTime: dateTime,
        relative: relative,
        parseDate: parseDate,
        esc: esc,
        escAttr: escAttr,
        initials: initials,
        widthClass: widthClass,
        niceText: niceText,
        codeLabel: codeLabel,
        // estados
        stateHtml: stateHtml,
        skeleton: skeleton,
        statusInfo: statusInfo,
        // acciones
        insert: insert,
        copy: copy,
        open: open,
        openOrder: openOrder,
        toast: toast,
        // renderizadores puros (devuelven HTML escapado)
        render: {
            deliveryNote: deliveryNoteHtml,
            invoice: invoiceHtml,
            lines: lines,
            lineAmount: lineAmount,
            totals: totals,
            address: address,
            addressText: addressText,
            statusPill: statusPill,
            kv: kv,
            obs: obs,
        },
        sheet: {
            open: sheetOpen,
            close: sheetClose,
            update: sheetUpdate,
        },
        STATUS: ERP_STATUS,
    };

    $(document).trigger('erp:ready', [window.ErpChat]);
})(window.jQuery);
