/**
 * Gestión (ERP) en el chat · ayudas para el agente. SOLO LECTURA.
 *
 * 1. Pista de pedido detectado: lee los últimos mensajes del cliente y, si
 *    nombran un pedido REAL de este cliente en Gestión (nº de pedido de 4-6
 *    cifras junto a "pedido", "nº", "#"…, o un número de 5+ cifras que
 *    coincide con un pedido de la lista), muestra una pista compacta sobre el
 *    composer con el estado y "Abrir pedido" ([data-erp-order-open], lo
 *    resuelve erp-chat.js). Usa PscChat.pushHint si existe; si no, su propia
 *    tira .erc-assist-strip. Nunca pinta un número que no case con un pedido.
 * 2. Variables {{erp_*}} en respuestas rápidas: envuelve
 *    window.bvReplacePlaceholders (conversations-thread.js) y además escucha
 *    el "input" del composer, así funcionan tanto al elegir una respuesta
 *    rápida como al pegar/escribir el marcador. Sin dato, el marcador se queda
 *    tal cual (misma convención que el core) y se rellena cuando llega el
 *    resumen de Gestión.
 * 3. Bloque "Respuestas rápidas" al final de la pestaña Gestión
 *    (#bv-erp-orders > #erc-assist-replies), fuera de [data-erc-body] para que
 *    los repintados de erp-inbox.js no lo borren.
 *
 * Eventos que escucha: pane:loaded (cambio de conversación),
 * inbox:incoming-message (mensaje nuevo en el hilo abierto),
 * erp:overview-loaded / erp:orders-ready (ErpChat).
 */
(function ($) {
    'use strict';

    if (!$ || window.ErpAssist) { return; }

    var HINT_MAX_PER_SCAN = 2;
    var SCAN_MESSAGES = 3;
    var VAR_RE = /\{\{\s*(erp_[a-z_]+)\s*\}\}/g;
    var HAS_VAR_RE = /\{\{\s*erp_[a-z_]+\s*\}\}/;
    // Palabra clave + nº de 4-6 cifras: "pedido 47427", "pedido nº 47427",
    // "nº47427", "#47427", "número de pedido: 47427", "n° 47427".
    var KEYWORD_RE = /(?:pedidos?|n[º°]\.?|no\.|n[uú]m(?:ero)?\.?|#)(?:\s*(?:de|del|es|el|mi|n[º°]\.?|no\.|n[uú]m(?:ero)?\.?|pedido|:|#))*\s*[:#]?\s*(\d{4,6})(?!\d)/gi;

    var shown = {};       // conversación:pedido -> true (ya se enseñó la pista)
    var extLookups = {};  // cliente -> Promise<lista ampliada de pedidos>
    var scanTimer = null;

    function C() { return window.ErpChat || null; }

    function hasErpPanel() { return !!$('.bv-right').first().data('has-erp'); }

    function threadCtx() {
        var el = document.getElementById('hd-thread-ctx');
        if (!el) { return {}; }
        try { return JSON.parse(el.textContent || '{}') || {}; } catch (e) { return {}; }
    }

    function conversationKey() {
        var ctx = threadCtx();
        var c = C();
        return String(ctx['conversation.id'] || (c && c.customerId()) || '');
    }

    function pad(n) { return (n < 10 ? '0' : '') + n; }

    function shortDate(iso) {
        var c = C();
        var d = c ? c.parseDate(iso) : null;
        return d ? pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear() : '';
    }

    function statusLabel(code) {
        var c = C();
        return c ? c.statusInfo(code).label : String(code || '');
    }

    function sec(resp, key) {
        return resp && resp.data && resp.data.sections ? resp.data.sections[key] || null : null;
    }

    function okData(resp, key) {
        var s = sec(resp, key);
        return s && s.state === 'ok' && s.data ? s.data : null;
    }

    function ordersOf(resp) {
        var d = okData(resp, 'orders');
        return Array.isArray(d) ? d : [];
    }

    function latestOrder(orders) {
        var c = C();
        var best = null;
        var bestTs = -1;
        (orders || []).forEach(function (o) {
            var d = c ? c.parseDate(o.date) : null;
            var ts = d ? d.getTime() : 0;
            if (best === null || ts > bestTs) { best = o; bestTs = ts; }
        });
        return best;
    }

    /* ── 1. Detección de pedidos en el hilo ───────────────────────── */

    function customerTexts() {
        var out = [];
        $('.bv-msg.in .bv-bubble').not('.note').slice(-SCAN_MESSAGES).each(function () {
            var t = $(this).clone()
                .find('.bv-bubble-translation, .bv-bubble-reactions, .bv-bubble-reaction, .bv-bubble-meta, .bv-quoted-msg, .note-badge, .bv-link-preview')
                .remove().end().text();
            if (t && /\d{4}/.test(t)) { out.push(t); }
        });
        return out;
    }

    // Separadores que invalidan un número suelto: decimales, fechas, horas,
    // teléfonos con guiones, emails, rutas de URL, importes con "€"…
    function isGlued(ch) { return !!ch && /[\w.,\/:@\-€$%]/.test(ch); }

    /**
     * Candidatos del texto: {value, keyword}. Con palabra clave, 4-6 cifras;
     * sin ella, cualquier número suelto de 5+ cifras (sin años de 4 cifras).
     */
    function candidates(texts) {
        var found = {};
        texts.forEach(function (text) {
            var m;
            KEYWORD_RE.lastIndex = 0;
            while ((m = KEYWORD_RE.exec(text)) !== null) {
                found[m[1]] = { value: m[1], keyword: true };
            }
            var runRe = /\d+/g;
            while ((m = runRe.exec(text)) !== null) {
                var v = m[0];
                if (v.length < 5 || v.length > 12 || found[v]) { continue; }
                var prev = text.charAt(m.index - 1);
                var next = text.charAt(m.index + v.length);
                // "1.500" o "47.427": el punto de miles también descarta.
                if (isGlued(prev) || isGlued(next)) { continue; }
                found[v] = { value: v, keyword: false };
            }
        });
        return Object.keys(found).map(function (k) { return found[k]; });
    }

    function indexOrders(orders) {
        var idx = {};
        (orders || []).forEach(function (o) {
            ['number', 'order_id', 'id'].forEach(function (k) {
                var v = o && o[k] != null ? String(o[k]).trim() : '';
                if (/^\d+$/.test(v) && !idx[v]) { idx[v] = o; }
            });
        });
        return idx;
    }

    function matchOrders(cands, idx) {
        var out = [];
        var seen = {};
        cands.forEach(function (cd) {
            var o = idx[cd.value];
            if (!o) { return; }
            var id = String(o.id || '');
            if (!id || seen[id]) { return; }
            seen[id] = true;
            out.push(o);
        });
        return out;
    }

    // Lista ampliada (100 pedidos) solo cuando el cliente nombra con palabra
    // clave un nº que no está en la primera página del resumen.
    function extendedOrders(cid) {
        if (!extLookups[cid]) {
            extLookups[cid] = C().section('orders', { limit: 100, offset: 0 }).then(function (r) {
                return r && r.state === 'ok' && Array.isArray(r.data) ? r.data : [];
            });
        }
        return extLookups[cid];
    }

    function hintHtml(o) {
        var c = C();
        var served = shortDate(o.served_date);
        return 'Pedido de Gestión <b>' + c.esc(o.number || o.id) + '</b> · ' + c.esc(statusLabel(o.status)) +
            (served ? ' · servido el ' + c.esc(served) : '');
    }

    function ensureStrip() {
        var $composer = $('.bv-composer').first();
        if (!$composer.length) { return null; }
        var $strip = $composer.prevAll('.erc-assist-strip').first();
        if (!$strip.length) {
            $strip = $('<div class="erc-assist-strip" aria-live="polite"></div>');
            $composer.before($strip);
        }
        return $strip;
    }

    function pushHint(o) {
        var c = C();
        var attr = 'data-erp-order-open="' + c.escAttr(o.id) + '"';
        if (window.PscChat && typeof window.PscChat.pushHint === 'function') {
            window.PscChat.pushHint(hintHtml(o), 'Abrir pedido', attr);
            return;
        }
        var $strip = ensureStrip();
        if (!$strip) { return; }
        $strip.find('.erc-assist-hint[data-order="' + c.escAttr(o.id) + '"]').remove();
        $strip.append('<div class="erc-assist-hint" data-order="' + c.escAttr(o.id) + '">' +
            '<span class="dot"></span>' +
            '<span class="txt">' + hintHtml(o) + '</span>' +
            '<button type="button" class="act" ' + attr + '>Abrir pedido</button>' +
            '<button type="button" class="x" data-erc-assist-dismiss>Descartar</button>' +
        '</div>');
        var $all = $strip.children('.erc-assist-hint');
        if ($all.length > HINT_MAX_PER_SCAN) { $all.first().remove(); }
    }

    function showMatches(orders) {
        var conv = conversationKey();
        var n = 0;
        orders.forEach(function (o) {
            var key = conv + ':' + o.id;
            if (shown[key] || n >= HINT_MAX_PER_SCAN) { return; }
            shown[key] = true;
            n++;
            pushHint(o);
        });
    }

    function scan() {
        var c = C();
        if (!c || !hasErpPanel() || !c.can('view') || !c.can('orders')) { return; }
        var cid = c.customerId();
        if (!cid || !$('.bv-composer').length) { return; }

        var cands = candidates(customerTexts());
        if (!cands.length) { return; }

        c.overview().then(function (resp) {
            if (String(c.customerId() || '') !== String(cid)) { return; }
            var matched = matchOrders(cands, indexOrders(ordersOf(resp)));
            var matchedValues = {};
            matched.forEach(function (o) {
                ['number', 'order_id', 'id'].forEach(function (k) { if (o[k] != null) { matchedValues[String(o[k])] = true; } });
            });
            if (matched.length) { showMatches(matched); }

            var pending = cands.filter(function (cd) { return cd.keyword && !matchedValues[cd.value]; });
            var orders = sec(resp, 'orders');
            if (!pending.length || !orders || orders.state !== 'ok') { return; }
            var p = orders.pagination || {};
            if (!(p.has_more || p.hasMore)) { return; }

            extendedOrders(cid).then(function (list) {
                if (String(c.customerId() || '') !== String(cid)) { return; }
                showMatches(matchOrders(pending, indexOrders(list)));
            });
        });
    }

    function scheduleScan(ms) {
        clearTimeout(scanTimer);
        scanTimer = setTimeout(scan, ms);
    }

    $(document).on('click', '[data-erc-assist-dismiss]', function () {
        $(this).closest('.erc-assist-hint').remove();
    });

    /* ── 2. Variables {{erp_*}} en respuestas rápidas ─────────────── */

    function erpVars(resp) {
        var c = C();
        if (!c || !resp || !resp.data || !resp.data.sections) { return null; }
        var vars = {};

        if (c.can('orders')) {
            var last = latestOrder(ordersOf(resp));
            if (last) {
                vars.erp_ultimo_pedido = String(last.number || last.id || '');
                vars.erp_estado_ultimo_pedido = statusLabel(last.status);
                vars.erp_fecha_servido = shortDate(last.served_date) || 'todavía sin fecha de servido';
            }
        }

        if (c.can('loyalty')) {
            var lp = okData(resp, 'loyalty_points');
            if (lp && lp.balance != null && lp.balance !== '') { vars.erp_puntos = String(lp.balance); }
            var summary = okData(resp, 'summary');
            var card = (lp && lp.main_card) || (summary && summary.card) || '';
            if (card) { vars.erp_tarjeta = String(card); }
        }

        if (c.can('addresses')) {
            var addr = shippingAddress(resp);
            if (addr) {
                var txt = c.render.addressText(addr);
                if (txt) { vars.erp_direccion_envio = txt; }
            }
        }

        return vars;
    }

    function shippingAddress(resp) {
        var d = okData(resp, 'addresses');
        var list = d && Array.isArray(d.addresses) ? d.addresses : [];
        var active = list.filter(function (a) { return a && a.available !== false && a.available !== 0 && a.available !== '0'; });
        var pick = active.filter(function (a) { return a.default_shipping; })[0] || active[0] || null;
        return pick;
    }

    function currentVars() {
        var c = C();
        if (!c || !c.customerId()) { return null; }
        return erpVars(c.cachedOverview());
    }

    var waitingOverview = false;

    function requestOverviewForVars() {
        var c = C();
        if (waitingOverview || !c || !hasErpPanel() || !c.customerId()) { return; }
        waitingOverview = true;
        c.overview().then(function () { waitingOverview = false; });
    }

    function replaceErp(text) {
        if (typeof text !== 'string' || !HAS_VAR_RE.test(text)) { return text; }
        var vars = currentVars();
        if (!vars) { requestOverviewForVars(); return text; }
        return text.replace(VAR_RE, function (match, key) {
            return Object.prototype.hasOwnProperty.call(vars, key) && vars[key] !== '' ? vars[key] : match;
        });
    }

    function applyToComposer() {
        var ta = $('.bv-composer-input').get(0);
        if (!ta || !HAS_VAR_RE.test(ta.value || '')) { return; }
        var val = ta.value;
        var replaced = replaceErp(val);
        if (replaced === val) { return; }
        var pos = (ta.selectionStart || val.length) + (replaced.length - val.length);
        ta.value = replaced;
        try { ta.setSelectionRange(pos, pos); } catch (e) { /* textarea oculta */ }
        ta.dispatchEvent(new Event('input', { bubbles: true }));
    }

    // Envuelve el reemplazo del core (se instala en cuanto existe; idempotente).
    function installReplacer() {
        var orig = window.bvReplacePlaceholders;
        if (typeof orig !== 'function' || orig.__erpAssist) { return; }
        var wrapped = function (text) { return replaceErp(orig.apply(this, arguments)); };
        wrapped.__erpAssist = true;
        window.bvReplacePlaceholders = wrapped;
    }

    $(document).on('input', '.bv-composer-input', function () {
        installReplacer();
        if (HAS_VAR_RE.test(this.value || '')) { applyToComposer(); }
    });

    /* ── 3. Respuestas rápidas en la pestaña Gestión ──────────────── */

    function replies(resp) {
        var c = C();
        var out = [];
        var last = c.can('orders') ? latestOrder(ordersOf(resp)) : null;

        if (last) {
            var num = String(last.number || last.id);
            var date = shortDate(last.date);
            var served = shortDate(last.served_date);
            var expected = shortDate(last.expected_date);
            var status = statusLabel(last.status);
            out.push({
                title: 'Estado del último pedido',
                text: 'Tu pedido nº ' + num + (date ? ' del ' + date : '') + ' está en estado «' + status + '»' +
                    (served ? '; se sirvió el ' + served + '.' : (expected ? '; la fecha prevista es el ' + expected + '.' : '.')),
            });
            out.push({
                title: 'Confirmar nº de pedido',
                text: '¿Me confirmas el número del pedido por el que preguntas? El último que vemos es el nº ' + num + (date ? ' del ' + date : '') + '.',
            });
        }

        if (c.can('loyalty')) {
            var lp = okData(resp, 'loyalty_points');
            if (lp && lp.balance != null && lp.balance !== '') {
                var pts = parseInt(lp.balance, 10) || 0;
                out.push({
                    title: 'Puntos de fidelización',
                    text: 'Ahora mismo tienes ' + pts + (pts === 1 ? ' punto' : ' puntos') + ' en tu tarjeta de fidelización.',
                });
            }
        }

        if (c.can('addresses')) {
            var addr = shippingAddress(resp);
            var txt = addr ? c.render.addressText(addr) : '';
            if (txt) {
                out.push({
                    title: 'Dirección de envío',
                    text: 'La dirección de envío que tenemos registrada es: ' + txt + '. ¿Es correcta?',
                });
            }
        }

        return out.slice(0, 4);
    }

    var lastReplies = [];

    function renderReplies(resp, cid) {
        var c = C();
        var $tab = $('#bv-erp-orders');
        if (!c || !$tab.length || !resp) { return; }
        if (String(c.customerId() || '') !== String(cid || '')) { return; }

        // Pestaña sustituida por "Sin permiso" o sin cuerpo: nada que añadir
        // (si se añadiera, el core dejaría de ocultar el botón de la pestaña).
        if (!$tab.children('[data-erc-body]').length || !c.can('view') || !resp.data || !resp.data.sections) {
            $tab.children('#erc-assist-replies').remove();
            return;
        }

        var list = replies(resp);
        lastReplies = list;
        var $box = $tab.children('#erc-assist-replies');
        if (!list.length) { $box.remove(); return; }
        if (!$box.length) {
            $box = $('<div id="erc-assist-replies" class="erc-assist-replies"></div>');
            $tab.append($box);
        }

        var html = '<div class="erc-card">' +
            '<div class="erc-card-head"><span class="erc-card-head-tt">Respuestas rápidas<span class="s">Datos de Gestión, se insertan sin enviar</span></span></div>' +
            '<div class="erc-card-body"><div class="erc-assist-list">';
        list.forEach(function (r, i) {
            html += '<div class="erc-assist-reply">' +
                '<div class="erc-assist-reply-body">' +
                    '<span class="t">' + c.esc(r.title) + '</span>' +
                    '<span class="m">' + c.esc(r.text) + '</span>' +
                '</div>' +
                '<button type="button" class="erc-btn erc-btn--outline erc-btn--sm" data-erc-assist-insert="' + i + '">Insertar</button>' +
            '</div>';
        });
        html += '</div></div></div>';
        $box.html(html);
    }

    $(document).on('click', '[data-erc-assist-insert]', function () {
        var c = C();
        var r = lastReplies[parseInt($(this).attr('data-erc-assist-insert'), 10)];
        if (c && r) { c.insert(r.text); }
    });

    function renderFromCache() {
        var c = C();
        if (!c) { return; }
        var cid = c.customerId();
        var resp = cid ? c.cachedOverview(cid) : null;
        if (resp) { renderReplies(resp, cid); }
    }

    /* ── Eventos ──────────────────────────────────────────────────── */

    $(document).on('erp:overview-loaded erp:orders-ready', function (e, resp, cid) {
        // Tras erp-inbox.js, que pinta [data-erc-body] con el mismo evento.
        setTimeout(function () {
            renderReplies(resp, cid);
            applyToComposer();
            if (e.type === 'erp:orders-ready') { scheduleScan(50); }
        }, 0);
    });

    document.addEventListener('pane:loaded', function () {
        $('.erc-assist-strip').remove();
        shown = {};
        installReplacer();
        scheduleScan(700);
        setTimeout(renderFromCache, 500);
    });

    window.addEventListener('inbox:incoming-message', function () { scheduleScan(350); });

    // Al abrir la pestaña Gestión con el resumen ya en caché, erp-inbox.js
    // repinta sin evento: se añade el bloque justo después.
    document.addEventListener('click', function (e) {
        if (!$(e.target).closest('.bv-right-tab[data-bv-tab="erp-orders"]').length) { return; }
        setTimeout(renderFromCache, 60);
    }, true);

    $(function () {
        installReplacer();
        scheduleScan(900);
    });
    $(window).on('load', installReplacer);

    window.ErpAssist = {
        vars: function () { return currentVars(); },
        replace: replaceErp,
        scan: scan,
        // Para pruebas: candidatos y cruce con una lista de pedidos.
        _match: function (text, orders) { return matchOrders(candidates([text]), indexOrders(orders)); },
    };
})(window.jQuery);
