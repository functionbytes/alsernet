/**
 * Extensión "livehints" — avisos en vivo sobre el composer cuando PrestaShop
 * avisa de algo que afecta al cliente de la conversación abierta:
 *
 *   .ps.hint.back_in_stock   Vuelve a haber stock de X · Avisar
 *   .ps.hint.price_dropped   Bajada de precio de X (antes → ahora) · Avisar
 *   .ps.hint.cart_abandoned  El cliente abandonó un carrito de 150 € · Ver
 *   .ps.hint.order_created   Nuevo pedido #REF de 89,90 € · Abrir
 *   .ps.hint.order_status    El pedido #REF ha pasado a Enviado · Abrir
 *   .ps.hint.order_returned  Nueva solicitud de devolución del pedido #REF · Ver
 *
 * Canal: el privado de la conversación (helpdesk.conversation.{id}), el mismo
 * al que ya se suscriben right-panel (.ps.cart.updated) y el hilo. El hilo
 * hace Echo.leave() del canal al cambiar de conversación, lo que borra
 * también estos .listen: por eso se marca el objeto canal y se vuelve a
 * enlazar en cada 'pane:loaded' si el canal ya no es el mismo.
 *
 * Los avisos se pintan con window.PscChat.pushHint (prestashop-chat.js):
 * máx. 2 visibles, se descartan solos a los 5 min. Todo texto va escapado.
 */
(function () {
    'use strict';

    if (window.__pscLivehintsLoaded) { return; }
    window.__pscLivehintsLoaded = true;

    var $ = window.jQuery;
    if (!$) { return; }

    var MARK = '__pscLivehints';
    var acts = {};
    var seq = 0;

    function store() { return window.PscStore || null; }

    function esc(s) {
        if (store() && store().esc) { return store().esc(s); }
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function money(n) {
        if (store() && store().money) { return store().money(n); }
        return Number(n || 0).toFixed(2).replace('.', ',') + ' €';
    }

    function currentConv() {
        var C = window.HDCommerce;
        var id = C && typeof C.conversationId === 'function' ? C.conversationId() : null;
        return id ? String(id) : '';
    }

    function orderRef(d) { return '#' + (d.reference || d.order_id || ''); }

    function num(v) { var n = parseFloat(v); return isNaN(n) ? null : n; }

    // Acción del aviso: se guarda la función y el botón lleva solo un id.
    function act(fn) {
        var id = 'lh' + (++seq);
        acts[id] = fn;
        return 'data-psc-livehints-act="' + id + '"';
    }

    function insertText(text) {
        if (store() && store().insert && store().insert(text)) { return; }
        var $ta = $('.bv-composer textarea').first();
        if ($ta.length) { $ta.val(($ta.val() ? $ta.val() + '\n' : '') + text).trigger('input').focus(); }
    }

    function openOrder(id) {
        if (id && typeof window.openPsOrderWorkspace === 'function') { window.openPsOrderWorkspace(id); }
    }

    function openCart() {
        if (typeof window.openPsCartModal !== 'function') { return; }
        // El contexto cacheado puede no reflejar aún el carrito abandonado.
        if (store() && store().load) {
            store().load(true, function () { window.openPsCartModal(); });
        } else {
            window.openPsCartModal();
        }
    }

    var RENDER = {
        back_in_stock: function (d) {
            var name = d.name || 'un producto que le interesa';
            var why = d.source === 'stock_alert' ? 'tenía aviso de reposición' : 'lo tiene en su lista de deseos';
            var price = num(d.price);
            return {
                html: 'Vuelve a haber stock de <b>' + esc(name) + '</b> <span class="psc-livehints-why">(' + esc(why) + ')</span>',
                label: 'Avisar',
                attr: act(function () {
                    insertText('¡Buenas noticias! Vuelve a haber stock de ' + name + (price !== null ? ', a ' + money(price) : '') + '.');
                }),
                dismissOnAct: true,
            };
        },

        price_dropped: function (d) {
            var name = d.name || 'un producto que le interesa';
            var oldP = num(d.old_price);
            var newP = num(d.new_price);
            if (oldP === null || newP === null) { return null; }
            var net = d.tax_included ? '' : ' <span class="psc-livehints-why">(sin IVA)</span>';
            return {
                html: 'Bajada de precio de <b>' + esc(name) + '</b>: <span class="psc-livehints-was">' + esc(money(oldP)) + '</span> → <b>' + esc(money(newP)) + '</b>' + net,
                label: 'Avisar',
                attr: act(function () {
                    insertText('¡Buenas noticias! ' + name + ' ha bajado de precio' +
                        (d.tax_included ? ': antes ' + money(oldP) + ', ahora ' + money(newP) : '') + '.');
                }),
                dismissOnAct: true,
            };
        },

        cart_abandoned: function (d) {
            var total = num(d.total);
            var n = parseInt(d.items_count, 10);
            return {
                html: 'El cliente abandonó un carrito' + (total ? ' de <b>' + esc(money(total)) + '</b>' : '') +
                    (n > 0 ? ' <span class="psc-livehints-why">(' + n + (n === 1 ? ' producto' : ' productos') + ')</span>' : ''),
                label: 'Ver',
                attr: act(openCart),
            };
        },

        order_created: function (d) {
            if (!d.order_id) { return null; }
            var total = num(d.total);
            return {
                html: 'Nuevo pedido <b>' + esc(orderRef(d)) + '</b>' + (total !== null ? ' de <b>' + esc(money(total)) + '</b>' : ''),
                label: 'Abrir',
                attr: act(function () { openOrder(d.order_id); }),
            };
        },

        order_status: function (d) {
            if (!d.order_id) { return null; }
            return {
                html: 'El pedido <b>' + esc(orderRef(d)) + '</b> ha pasado a <b>' + esc(d.state_name || 'otro estado') + '</b>',
                label: 'Abrir',
                attr: act(function () { openOrder(d.order_id); }),
            };
        },

        order_returned: function (d) {
            if (!d.order_id) { return null; }
            return {
                html: 'Nueva solicitud de devolución del pedido <b>' + esc(orderRef(d)) + '</b>',
                label: 'Ver',
                attr: act(function () {
                    if (store() && store().reloadReturns) { store().reloadReturns(); }
                    openOrder(d.order_id);
                }),
            };
        },
    };

    function onHint(type, payload) {
        payload = payload || {};
        // El canal de una conversación anterior puede seguir vivo: solo se
        // pinta lo de la conversación que el agente tiene delante.
        if (String(payload.conversation_id || '') !== currentConv()) { return; }
        if (!window.PscChat || typeof window.PscChat.pushHint !== 'function') { return; }
        var r = RENDER[type] && RENDER[type](payload.data || {});
        if (!r) { return; }
        if (r.dismissOnAct) { r.attr += ' data-psc-livehints-dismiss'; }
        window.PscChat.pushHint(r.html, r.label, r.attr);
    }

    function subscribe() {
        if (!window.Echo || typeof window.Echo.private !== 'function') { return false; }
        var id = currentConv();
        if (!id) { return false; }
        var channel = window.Echo.private('helpdesk.conversation.' + id);
        if (!channel || channel[MARK]) { return true; }
        channel[MARK] = true;
        Object.keys(RENDER).forEach(function (type) {
            channel.listen('.ps.hint.' + type, function (payload) { onHint(type, payload); });
        });
        return true;
    }

    // Echo y HDCommerce pueden llegar después de este script (defer).
    function subscribeWithRetry(tries) {
        if (subscribe() || tries <= 0) { return; }
        setTimeout(function () { subscribeWithRetry(tries - 1); }, 500);
    }

    $(document).on('click', '[data-psc-livehints-act]', function () {
        var $btn = $(this);
        var fn = acts[$btn.attr('data-psc-livehints-act')];
        if (fn) { fn(); }
        if ($btn.is('[data-psc-livehints-dismiss]')) { $btn.closest('.psc-live-hint').remove(); }
    });

    document.addEventListener('pane:loaded', function () {
        acts = {};
        setTimeout(function () { subscribeWithRetry(6); }, 0);
    });

    $(function () { subscribeWithRetry(10); });
})();
