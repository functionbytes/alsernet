/**
 * Extensión "cartpay" (HelpdeskPrestashop).
 *
 * Pieza 02 · ps-order-payment — en la pestaña Pago del workspace de pedido,
 * si el pedido tiene importe pendiente, una tarjeta "Cobro pendiente". Para
 * pedidos por transferencia abre la hoja #psCartpayPaySheet con los datos
 * bancarios reales de la tienda y la referencia como concepto, y los inserta
 * en el composer SIN enviar. No hay enlace de pago: ningún módulo instalado
 * lo ofrece para un pedido ya creado.
 *
 * Pieza 32 · ps-cart-convert — bloque "Convertir o vaciar" al final del
 * cuerpo del modal de carrito (#psCartModal). right-panel-prestashop-tabs.js
 * repinta ese cuerpo entero tras cada acción, así que un MutationObserver
 * vuelve a añadir el bloque cada vez. Las dos acciones piden confirmación en
 * línea (nunca un modal encima de otro).
 *
 * Se sirve desde public/modules/helpdeskprestashop/js/ext/ — hay que copiarlo
 * allí tras editar.
 */
(function ($) {
    'use strict';

    if (!$) { return; }

    function flag(name) { return String($('#psCartpayCfg').data(name)) === '1'; }
    function store() { return window.PscStore || null; }
    function esc(s) {
        if (store()) { return store().esc(s); }
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function escAttr(s) { return store() ? store().escAttr(s) : esc(s); }
    function money(n) { return store() ? store().money(n) : String(n); }
    function csrf() {
        return window.HDCommerce ? window.HDCommerce.csrf() : $('meta[name="csrf-token"]').attr('content');
    }
    function errorMessage(xhr, fallback) {
        if (window.HDCommerce && window.HDCommerce.errorMessage) { return window.HDCommerce.errorMessage(xhr, fallback); }
        return (xhr && xhr.responseJSON && xhr.responseJSON.message) || fallback;
    }
    function notify(kind, text) {
        if (window.toastr && window.toastr[kind]) { window.toastr[kind](text); }
    }

    /* ── Pieza 02 · Cobro del pedido pendiente ─────────────────── */

    var _pay = null;

    function payText(d) {
        var bw = d.bank_wire || {};
        return [
            'Para completar el pago del pedido ' + d.reference + ' (' + money(d.pending) + ') puedes hacer una transferencia con estos datos:',
            'Titular: ' + bw.owner,
            'Cuenta: ' + bw.details,
            bw.address ? 'Banco: ' + bw.address : null,
            'Concepto: ' + (bw.concept || d.reference),
            'En cuanto recibamos la transferencia, preparamos tu pedido.',
        ].filter(Boolean).join('\n');
    }

    function ensurePaySheet() {
        var $sheet = $('#psCartpayPaySheet');
        var $host = $('[data-bv-modal-name="ps-order-workspace"] .bv-po-body');
        if ($sheet.length && $host.length && !$sheet.parent().is($host)) {
            $sheet.appendTo($host);
        }
        return $sheet;
    }

    function payCardHtml(d) {
        var sub = money(d.pending) + ' · ' + (d.payment || 'sin método');
        var body = d.bank_wire
            ? '<button type="button" class="btn-secondary bv-po-btn" id="psCartpayPayOpen">Enviar datos de pago al chat</button>'
            : '<div class="psc-note psc-note--info"><span class="psc-note-txt">El pago con ' + esc(d.payment || 'este método') +
              ' no permite reenviar un enlace ni datos de pago desde la tienda.</span></div>';

        return '<div class="bv-po-card psc-cartpay-card">' +
            '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-building-columns"></i></span>' +
                '<div class="bv-po-card-ht"><span class="t">Cobro pendiente</span><span class="s">' + esc(sub) + '</span></div></div>' +
            body +
        '</div>';
    }

    $(document).on('psc:order-rendered', function (e, order, api) {
        if (!flag('can-pay') || !order || !api) { return; }
        var orderId = order.id;
        _pay = null;
        $('#powPanelPago .psc-cartpay-card').remove();

        $.ajax({
            url: api.customerUrl('/ps/ext/cartpay/orders/' + encodeURIComponent(orderId) + '/payment'),
            method: 'GET',
            dataType: 'json',
            headers: { 'Accept': 'application/json' },
        }).done(function (r) {
            // El agente pudo abrir otro pedido mientras tanto.
            if (String(api.orderId()) !== String(orderId)) { return; }
            var d = r && r.data;
            if (!d || !d.is_open || d.is_paid || !(parseFloat(d.pending) > 0)) { return; }
            _pay = d;
            $('#powPanelPago .psc-cartpay-card').remove();
            $('#powPanelPago').prepend(payCardHtml(d));
        });
        // Un fallo aquí no se pinta: la pestaña Pago ya enseña los movimientos
        // y esta tarjeta es un atajo, no información nueva.
    });

    $(document).on('click', '#psCartpayPayOpen', function () {
        var api = window.PscOrderWorkspace;
        if (!_pay || !_pay.bank_wire || !api) { return; }
        var $sheet = ensurePaySheet();
        if (!$sheet.length) { return; }
        $('#psCartpayPayRef').text('#' + _pay.reference);
        $('#psCartpayPayAmount').text(money(_pay.pending));
        $('#psCartpayPayState').text([_pay.state_name, _pay.payment].filter(Boolean).join(' · '));
        $('#psCartpayPayText').text(payText(_pay));
        api.openSheet('#psCartpayPaySheet');
    });

    $(document).on('click', '#psCartpayPayInsert', function () {
        var api = window.PscOrderWorkspace;
        if (!_pay || !api) { return; }
        api.insert(payText(_pay));
        api.closeSheet('#psCartpayPaySheet');
    });

    $(document).on('click', '#psCartpayPayClose, #psCartpayPayCancel', function () {
        if (window.PscOrderWorkspace) { window.PscOrderWorkspace.closeSheet('#psCartpayPaySheet'); }
    });

    /* ── Pieza 32 · Convertir o vaciar carrito ─────────────────── */

    var _preview = null;
    var _cartId = null;
    var _busy = false;
    var _open = false; // bloque "Convertir o vaciar" desplegado
    var _sendMail = true; // casilla "Enviar el correo de confirmación" (sobrevive a los repintados)
    // Clave de idempotencia por intento: un reintento tras un corte de red
    // reutiliza la misma (el puente devuelve el resultado ya guardado); tras
    // un rechazo (422) se renueva, porque el puente guarda también las
    // respuestas de rechazo y, si no, el agente vería el mismo error aunque
    // ya hubiera corregido el carrito.
    var _attemptKey = null;

    function newAttemptKey() {
        if (window.crypto && window.crypto.randomUUID) { return window.crypto.randomUUID(); }
        return 'cartpay-' + Date.now() + '-' + Math.random().toString(36).slice(2);
    }

    function cartBase(cartId) {
        var base = window.HDCommerce ? window.HDCommerce.base() : null;
        return base ? base + '/ps/ext/cartpay/cart/' + encodeURIComponent(cartId) : null;
    }

    function $block() { return $('#psCartModalBody .psc-cartpay'); }

    function stateSub(s, allowed) {
        if (!allowed) { return 'Solo lo puede crear un responsable'; }
        return s.paid ? 'Se registra como cobrado' : 'Sin pagar · el cliente paga por transferencia';
    }

    function blockHtml(d) {
        var can = d.can || {};
        var blocking = d.blocking_messages || [];
        var ordered = (d.blocking || []).indexOf('already_ordered') !== -1;
        var firstAllowed = null;

        var states = (d.states || []).map(function (s) {
            var allowed = can.convert && (!s.paid || can.convert_paid);
            if (allowed && firstAllowed === null) { firstAllowed = s.key; }
            return { s: s, allowed: allowed };
        });

        var statesHtml = can.convert ? (
            '<div class="psc-field">' +
                '<span class="lbl">Estado del pedido a crear</span>' +
                '<div class="psc-cartpay-states">' + states.map(function (o) {
                    var on = o.s.key === firstAllowed;
                    return '<label class="psc-radio-opt' + (on ? ' is-on' : '') + (o.allowed ? '' : ' is-off') + '">' +
                        '<input type="radio" name="psCartpayState" value="' + escAttr(o.s.key) + '"' +
                            (on ? ' checked' : '') + (o.allowed ? '' : ' disabled') + '>' +
                        '<span class="psc-radio-body"><span class="t">' + esc(o.s.name) + '</span>' +
                        '<span class="s">' + esc(stateSub(o.s, o.allowed)) + '</span></span>' +
                    '</label>';
                }).join('') + '</div>' +
            '</div>'
        ) : '';

        var delivery = (d.addresses || {}).delivery;
        var rows = [
            ['Envío', d.carrier ? d.carrier.name + ' · ' + money(d.shipping) : money(d.shipping)],
            ['Pago', d.payment_method || '—'],
            ['Dirección', delivery ? [delivery.name, delivery.line, delivery.city].filter(Boolean).join(' · ') : 'Sin dirección'],
        ].map(function (r) {
            return '<div class="psc-cartpay-kv"><span class="k">' + esc(r[0]) + '</span><span class="v">' + esc(r[1]) + '</span></div>';
        }).join('');

        // Sin dirección: se ofrece el selector de direcciones del carrito que
        // ya existe (mismo que "Cambiar"); al elegir, el modal se repinta y
        // este bloque vuelve a comprobar el carrito.
        var addressFix = { no_delivery_address: 'delivery', no_invoice_address: 'invoice' };
        var blockingHtml = blocking.map(function (m, i) {
            var fix = addressFix[(d.blocking || [])[i]];
            return '<div class="psc-note psc-note--lock"><span class="psc-note-txt">' + esc(m) + '</span>' +
                (fix && window.openPsAddressesModal
                    ? '<button type="button" class="psc-note-act" data-cartpay-act="pick-address" data-type="' + fix + '">Elegir</button>'
                    : '') +
            '</div>';
        }).join('');

        var canConvertNow = can.convert && !blocking.length && firstAllowed !== null;
        // Plegado por defecto (una fila con el resumen): desplegado alargaba el
        // modal del carrito el doble y dejaba "Vaciar" siempre a la vista.
        var summary = ordered ? 'Este carrito ya es un pedido'
            : (blocking.length ? blocking[0] : (can.convert ? 'Listo para convertir en pedido' : 'Solo vaciar'));

        return '<button type="button" class="psc-cartpay-toggle" data-cartpay-act="toggle" aria-expanded="' + (_open ? 'true' : 'false') + '">' +
                '<span class="psc-cartpay-toggle-t">Convertir en pedido o vaciar</span>' +
                '<span class="psc-cartpay-toggle-s">' + esc(summary) + '</span>' +
                '<i class="fas fa-chevron-down psc-chevron"></i>' +
            '</button>' +
            '<div class="psc-cartpay-main' + (_open ? '' : ' bv-hidden') + '">' +
                '<div class="psc-cartpay-sum"><span class="k">Carrito</span>' +
                    '<span class="v">CART-#' + esc(d.cart_id) + ' · ' + esc(money(d.total)) + '</span></div>' +
                statesHtml +
                (can.convert ? '<div class="psc-cartpay-rows">' + rows + '</div>' : '') +
                blockingHtml +
                (can.convert ? mailHtml(d) : '') +
                '<div class="psc-cartpay-acts">' +
                    (can.convert
                        ? '<button type="button" class="psc-btn psc-btn--primary' + (canConvertNow ? '' : ' is-disabled') + '" data-cartpay-act="convert-ask"' + (canConvertNow ? '' : ' disabled') + '>Convertir en pedido</button>'
                        : '') +
                    (can.empty && !ordered
                        ? '<button type="button" class="psc-btn psc-btn--danger" data-cartpay-act="empty-ask">Vaciar carrito</button>'
                        : '') +
                '</div>' +
                (can.empty && !ordered ? '<p class="psc-cartpay-hint">Vaciar es irreversible y el cliente lo verá en su sesión abierta.</p>' : '') +
            '</div>' +
            '<div class="psc-cartpay-confirm bv-hidden"></div>';
    }

    // Interruptor real del correo order_conf: solo si el puente tiene el hook
    // actionEmailSendBefore (confirmation_email_optional). Marcado por defecto.
    function mailHtml(d) {
        if (d.confirmation_email_optional) {
            var on = _sendMail !== false;
            return '<div class="psc-field psc-cartpay-mail">' +
                    '<label class="psc-check"><input type="checkbox" name="psCartpaySendMail" value="1"' + (on ? ' checked' : '') + '>' +
                    '<span>Enviar el correo de confirmación</span></label>' +
                    '<span class="psc-cartpay-hint">Desmárcalo si el cliente ya tiene los datos por el chat: el pedido se crea igual y no recibe el correo «Confirmación de pedido».</span>' +
                '</div>';
        }
        return '<div class="psc-note psc-note--info"><span class="psc-note-txt">La tienda enviará al cliente el correo de confirmación del pedido: el puente de esta tienda todavía no permite omitirlo.</span></div>';
    }

    function sendMailChosen() {
        var d = _preview || {};
        if (!d.confirmation_email_optional) { return true; }
        var $cb = $block().find('input[name="psCartpaySendMail"]');
        return $cb.length ? $cb.is(':checked') : true;
    }

    function confirmHtml(kind) {
        var d = _preview || {};
        if (kind === 'convert') {
            var key = $block().find('input[name="psCartpayState"]:checked').val();
            var st = (d.states || []).filter(function (s) { return s.key === key; })[0];
            if (!st) { return null; }
            var mail = sendMailChosen();
            return '<div class="psc-note psc-note--info"><span class="psc-note-txt">' +
                    'Se creará un pedido de <b>' + esc(money(d.total)) + '</b> en estado <b>' + esc(st.name) + '</b>, con pago ' + esc(d.payment_method || 'por transferencia') +
                    (mail ? '. El cliente recibirá el correo de confirmación' : '. <b>No</b> se enviará el correo de confirmación') +
                    ' y el pedido pasará a Gestión como cualquier otro.' +
                '</span></div>' +
                '<div class="psc-cartpay-acts">' +
                    '<button type="button" class="psc-btn psc-btn--primary" data-cartpay-act="convert-go" data-state="' + escAttr(st.key) + '" data-send-mail="' + (mail ? '1' : '0') + '">Crear el pedido</button>' +
                    '<button type="button" class="psc-btn psc-btn--outline" data-cartpay-act="back">Volver</button>' +
                '</div>';
        }
        var items = parseInt(d.items, 10) || 0;
        var vouchers = parseInt(d.vouchers, 10) || 0;
        return '<div class="psc-note psc-note--lock"><span class="psc-note-txt">' +
                'Se quitarán ' + items + (items === 1 ? ' unidad' : ' unidades') +
                (vouchers ? ' y ' + vouchers + (vouchers === 1 ? ' cupón' : ' cupones') : '') +
                ' del carrito CART-#' + esc(d.cart_id) + '. No se puede deshacer y el cliente lo verá en su sesión abierta.' +
            '</span></div>' +
            '<div class="psc-cartpay-acts">' +
                '<button type="button" class="psc-btn psc-btn--danger" data-cartpay-act="empty-go">Sí, vaciar el carrito</button>' +
                '<button type="button" class="psc-btn psc-btn--outline" data-cartpay-act="back">Volver</button>' +
            '</div>';
    }

    function loadPreview(cartId) {
        var url = cartBase(cartId);
        var $b = $block();
        if (!url || !$b.length) { return; }
        $b.html('<div class="psc-cartpay-toggle is-loading"><span class="psc-cartpay-toggle-t">Convertir en pedido o vaciar</span><span class="psc-cartpay-toggle-s">Comprobando el carrito…</span></div>');

        $.ajax({ url: url + '/preview', method: 'GET', dataType: 'json', headers: { 'Accept': 'application/json' } })
            .done(function (r) {
                if (String(_cartId) !== String(cartId) || !$block().length) { return; }
                _preview = (r && r.data) || null;
                if (!_preview) { $block().remove(); return; }
                $block().html(blockHtml(_preview));
            })
            .fail(function (xhr) {
                if (String(_cartId) !== String(cartId) || !$block().length) { return; }
                // Error de carga: aviso ámbar dentro del bloque, el resto del
                // modal sigue funcionando.
                $block().html('<div class="ps-sec-label"><span>Convertir o vaciar</span><span class="ln"></span></div>' +
                    '<div class="psc-note psc-note--warn"><span class="psc-note-txt">' +
                    esc(errorMessage(xhr, 'No se pudo comprobar el carrito en PrestaShop.')) + '</span>' +
                    '<button type="button" class="psc-note-act" data-cartpay-act="retry">Reintentar</button></div>');
            });
    }

    function injectCartBlock() {
        var $body = $('#psCartModalBody .ps-cart-body');
        if (!$body.length || $body.find('.psc-cartpay').length) { return; }
        var cart = store() && store().cart ? store().cart() : null;
        if (!cart || !cart.id) { return; }
        if (String(_cartId) !== String(cart.id)) { _open = false; _sendMail = true; }
        _cartId = cart.id;
        _preview = null;
        $body.append('<div class="psc-cartpay" data-cart-id="' + escAttr(cart.id) + '"></div>');
        loadPreview(cart.id);
    }

    function reloadStoreAndCart() {
        if (!store()) { return; }
        store().load(true, function () {
            if ($('#psCartModal').hasClass('show') && window.openPsCartModal) { window.openPsCartModal(); }
            store().refresh();
        });
    }

    // Nota interna en la conversación: deja rastro visible para el resto del
    // equipo (el log de actividad ya lo guarda el servidor).
    function internalNote(text) {
        var sendUrl = $('.bv-composer').data('bv-send-url');
        if (!sendUrl) { return; }
        $.ajax({
            url: sendUrl, method: 'POST', dataType: 'json',
            data: { body: text, is_internal: 1, action: 'send' },
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        });
    }

    function conversationId() {
        return window.HDCommerce && window.HDCommerce.conversationId ? window.HDCommerce.conversationId() : null;
    }

    $(document).on('change', '#psCartModalBody input[name="psCartpaySendMail"]', function () {
        _sendMail = $(this).is(':checked');
    });

    $(document).on('change', '#psCartModalBody input[name="psCartpayState"]', function () {
        $block().find('.psc-radio-opt').removeClass('is-on');
        $(this).closest('.psc-radio-opt').addClass('is-on');
    });

    $(document).on('click', '#psCartModalBody [data-cartpay-act]', function () {
        var act = $(this).data('cartpay-act');
        var $b = $block();

        if (act === 'retry') { loadPreview(_cartId); return; }

        if (act === 'toggle') {
            _open = !_open;
            $(this).attr('aria-expanded', _open ? 'true' : 'false');
            $b.find('.psc-cartpay-main').toggleClass('bv-hidden', !_open);
            $b.find('.psc-cartpay-confirm').addClass('bv-hidden').empty();
            return;
        }

        if (act === 'pick-address') {
            if (window.openPsAddressesModal) { window.openPsAddressesModal('cart-picker', $(this).data('type')); }
            return;
        }

        if (act === 'convert-ask' || act === 'empty-ask') {
            var html = confirmHtml(act === 'convert-ask' ? 'convert' : 'empty');
            if (!html) { return; }
            _attemptKey = newAttemptKey();
            $b.find('.psc-cartpay-main').addClass('bv-hidden');
            $b.find('.psc-cartpay-confirm').html(html).removeClass('bv-hidden');
            return;
        }

        if (act === 'back') {
            $b.find('.psc-cartpay-confirm').addClass('bv-hidden').empty();
            $b.find('.psc-cartpay-main').removeClass('bv-hidden');
            return;
        }

        if (act === 'open-order') {
            var orderId = $(this).data('order-id');
            var modal = window.bootstrap ? window.bootstrap.Modal.getInstance(document.getElementById('psCartModal')) : null;
            if (modal) { modal.hide(); }
            if (orderId && window.openPsOrderWorkspace) { window.openPsOrderWorkspace(orderId); }
            return;
        }

        if ((act !== 'convert-go' && act !== 'empty-go') || _busy) { return; }

        var url = cartBase(_cartId);
        if (!url) { return; }
        var $btn = $(this);
        var label = $btn.text();
        var isConvert = act === 'convert-go';
        var cartId = _cartId;
        _busy = true;
        $b.find('.psc-cartpay-confirm [data-cartpay-act]').prop('disabled', true);
        $btn.text(isConvert ? 'Creando pedido…' : 'Vaciando…');

        $.ajax({
            url: url + (isConvert ? '/convert' : '/empty'),
            method: 'POST',
            dataType: 'json',
            data: isConvert
                ? { state: $btn.data('state'), conversation_id: conversationId(), send_confirmation: String($btn.data('send-mail')) === '0' ? 0 : 1 }
                : { conversation_id: conversationId() },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Idempotency-Key': _attemptKey || newAttemptKey() },
        }).done(function (r) {
            var d = (r && r.data) || {};
            _attemptKey = null;
            if (isConvert) {
                $block().html(
                    '<div class="ps-sec-label"><span>Convertir o vaciar</span><span class="ln"></span></div>' +
                    '<div class="psc-note psc-note--good"><span class="psc-note-txt">Pedido <b>' + esc(d.reference || d.order_id) + '</b> creado · ' +
                        esc(d.state_name || '') + ' · ' + esc(money(d.total)) +
                        (d.confirmation_email_sent === false ? ' · sin correo de confirmación' : '') + '</span></div>' +
                    (r && r.warning ? '<div class="psc-note psc-note--lock"><span class="psc-note-txt">' + esc(r.warning) + '</span></div>' : '') +
                    '<div class="psc-cartpay-acts"><button type="button" class="psc-btn psc-btn--primary" data-cartpay-act="open-order" data-order-id="' + escAttr(d.order_id) + '">Abrir el pedido</button></div>'
                );
                internalNote('[PS] Pedido ' + (d.reference || d.order_id) + ' creado desde el carrito CART-#' + cartId + ' · ' + (d.state_name || '') + ' · ' + money(d.total) +
                    (d.confirmation_email_sent === false ? ' · sin correo de confirmación' : ''));
                notify('success', 'Pedido ' + (d.reference || d.order_id) + ' creado en PrestaShop.');
                // Sin repintar el modal: el mensaje de éxito se quedaría sin
                // sitio (el carrito ya es pedido). Solo el tab Tienda.
                if (store()) { store().load(true, function () { store().refresh(); }); }
            } else {
                internalNote('[PS] Carrito CART-#' + cartId + ' vaciado desde el chat');
                notify('success', 'Carrito vaciado.');
                reloadStoreAndCart();
            }
        }).fail(function (xhr) {
            if (xhr && xhr.status === 422) { _attemptKey = newAttemptKey(); }
            var msg = errorMessage(xhr, isConvert ? 'No se pudo crear el pedido.' : 'No se pudo vaciar el carrito.');
            $btn.text(label);
            $block().find('.psc-cartpay-confirm [data-cartpay-act]').prop('disabled', false);
            $block().find('.psc-cartpay-confirm .psc-cartpay-err').remove();
            $block().find('.psc-cartpay-confirm').prepend(
                '<div class="psc-note psc-note--warn psc-cartpay-err"><span class="psc-note-txt">' + esc(msg) + '</span></div>'
            );
        }).always(function () { _busy = false; });
    });

    $(function () {
        if (!flag('can-convert') && !flag('can-empty')) { return; }
        var target = document.getElementById('psCartModalBody');
        if (!target || !window.MutationObserver) { return; }
        // Solo hijos directos: el cuerpo se repinta con .html() y así este
        // bloque (que va dentro de .ps-cart-body) no se observa a sí mismo.
        new MutationObserver(injectCartBlock).observe(target, { childList: true });
        injectCartBlock();
    });
})(window.jQuery);
