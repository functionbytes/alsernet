/*!
 * HelpdeskPrestashop · extensión "orderedit" · pieza 11 (ps-order-reorder).
 *
 * "Repetir pedido" dentro del workspace de pedido: tarjeta en la pestaña
 * Estado → hoja #psOrdereditReorderSheet con las líneas del pedido a precio
 * de hoy (vista previa del bridge), y creación del carrito nuevo del cliente
 * en PrestaShop. "Crear y enviar enlace" hace que la tienda mande al correo de
 * la cuenta del cliente el enlace para pagar ese carrito (el mismo correo del
 * back-office). El enlace inicia sesión como el cliente, así que nunca pasa
 * por el chat: en el composer solo se deja un aviso, sin enviarlo.
 *
 * Fuente en modules/HelpdeskPrestashop/public/js/ext/ — copiar a
 * public/modules/helpdeskprestashop/js/ext/ tras editar.
 */
(function () {
    var SHEET = '#psOrdereditReorderSheet';
    var _preview = null;   // respuesta de la vista previa del pedido abierto
    var _orderId = null;   // pedido de la hoja (puede diferir si el agente cambia de pedido)
    var _canLink = false;
    var _linkSent = false; // la tienda envió el correo con el enlace del carrito
    var _created = null;
    var _seq = 0;          // descarta respuestas de un pedido anterior

    function W() { return window.PscOrderWorkspace; }
    function esc(s) { return W().esc(s == null ? '' : String(s)); }
    function money(n) { return W().money(n); }

    // La hoja vive en el blade de la extensión; se mueve una sola vez dentro
    // del cuerpo del workspace para que cubra el pedido como las demás hojas.
    function mountSheet() {
        var $sheet = $(SHEET);
        var $host = $('[data-bv-modal-name="ps-order-workspace"] .bv-po-body');
        if ($sheet.length && $host.length && !$sheet.parent().is($host)) {
            $sheet.appendTo($host);
        }
    }

    $(document).on('psc:order-rendered', function (e, order) {
        if (!$(SHEET).length || !order || !(order.lines || []).length) { return; }
        mountSheet();
        $('#psOrdereditCard').remove();
        $('#powPanelEstado').append(
            '<div class="bv-po-card" id="psOrdereditCard">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-rotate-right"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Repetir pedido</span><span class="s">Carrito nuevo con estas líneas a precio actual</span></div></div>' +
                '<button type="button" class="btn-secondary bv-po-btn psc-orderedit-card-btn" id="psOrdereditOpen">Repetir pedido</button>' +
            '</div>'
        );
    });

    $(document).on('click', '#psOrdereditOpen', function () {
        var order = W().order();
        if (!order) { return; }
        _orderId = order.id;
        _preview = null;
        _linkSent = false;
        _created = null;
        $('#psOrdereditRef').text('#' + (order.reference || order.id));
        showStep('loading');
        W().openSheet(SHEET);
        loadPreview();
    });

    function showStep(step) {
        $('#psOrdereditLoading').toggleClass('bv-hidden', step !== 'loading');
        $('#psOrdereditForm').toggleClass('bv-hidden', step !== 'form');
        $('#psOrdereditDone').toggleClass('bv-hidden', step !== 'done');
        $('#psOrdereditFormActs').toggleClass('bv-hidden', step === 'done');
        $('#psOrdereditDoneActs').toggleClass('bv-hidden', step !== 'done');
        $('#psOrdereditError').addClass('bv-hidden');
        if (step === 'loading') { setButtons(false); }
    }

    function showError(msg) {
        $('#psOrdereditError').removeClass('bv-hidden').find('.psc-note-txt').text(msg);
    }

    function url() { return W().customerUrl('/ps/orders/' + _orderId + '/reorder'); }

    function loadPreview() {
        var seq = ++_seq;
        $.ajax({ url: url(), method: 'GET', dataType: 'json', headers: { 'Accept': 'application/json' } })
            .done(function (r) {
                if (seq !== _seq) { return; }
                if (!r || !r.success || !r.data) {
                    $('#psOrdereditLoading').addClass('bv-hidden');
                    showError((r && r.message) || 'No se pudo cargar el pedido.');
                    return;
                }
                _preview = r.data;
                _canLink = !!r.can_link;
                renderForm();
            })
            .fail(function (xhr) {
                if (seq !== _seq) { return; }
                $('#psOrdereditLoading').addClass('bv-hidden');
                showError(W().errorMessage(xhr, 'No se pudo cargar el pedido.'));
            });
    }

    var STATUS = {
        discontinued: { tag: 'psc-tag--blocked', label: 'Descatalogado' },
        out_of_stock: { tag: 'psc-tag--blocked', label: 'Sin stock' },
        customized: { tag: 'psc-tag--closed', label: 'Personalizado' },
        gift: { tag: 'psc-tag--closed', label: 'Regalo' },
    };

    function addable(l) { return l.status === 'ok' || l.status === 'partial_stock'; }
    function maxQty(l) { return l.available_quantity != null ? Math.min(999, parseInt(l.available_quantity, 10) || 0) : 999; }

    function renderForm() {
        var lines = _preview.lines || [];
        $('#psOrdereditLines').html(lines.map(function (l) {
            var ok = addable(l);
            var thumb = '<span class="psc-thumb psc-thumb--md">' + (l.image_url ? '<img src="' + esc(l.image_url) + '" alt="" loading="lazy">' : 'foto') + '</span>';
            var sub = [];
            if (l.reference) { sub.push('<span class="mono">' + esc(l.reference) + '</span>'); }
            sub.push('×' + esc(l.ordered_quantity) + ' en el pedido');
            if (ok && l.price_changed) {
                sub.push('<span class="psc-orderedit-was">' + money(l.paid_unit) + '</span> <span class="psc-orderedit-now">' + money(l.current_unit) + '</span>');
            } else if (ok) {
                sub.push('<span class="mono">' + money(l.current_unit) + '</span>');
            }
            if (l.status === 'partial_stock') { sub.push('solo ' + esc(l.available_quantity) + ' en stock'); }
            if (l.status === 'customized') { sub.push('la personalización hay que volver a pedirla'); }
            if (l.status === 'gift') { sub.push('lo vuelve a añadir la promoción si sigue vigente'); }

            if (!ok) {
                var st = STATUS[l.status] || { tag: 'psc-tag--blocked', label: 'No disponible' };
                return '<div class="psc-orderedit-line is-off">' + thumb +
                    '<span class="nm"><span class="t">' + esc(l.name) + '</span><span class="s">' + sub.join(' · ') + '</span></span>' +
                    '<span class="psc-tag ' + st.tag + '">' + st.label + '</span>' +
                '</div>';
            }
            var min = Math.max(1, parseInt(l.minimal_quantity, 10) || 1);
            var qty = Math.max(min, parseInt(l.quantity, 10) || min);
            return '<label class="psc-orderedit-line is-pickable is-on" data-line="' + (parseInt(l.order_detail_id, 10) || 0) + '" data-unit="' + (parseFloat(l.current_unit) || 0) + '" data-min="' + min + '" data-max="' + maxQty(l) + '">' +
                '<input type="checkbox" class="psOrdereditChk" checked>' + thumb +
                '<span class="nm"><span class="t">' + esc(l.name) + '</span><span class="s">' + sub.join(' · ') + '</span></span>' +
                '<span class="psc-orderedit-qty">' +
                    '<button type="button" class="ps-cart-qty-btn" data-oe-qty="-1" aria-label="Una unidad menos">−</button>' +
                    '<input type="number" class="psOrdereditQty" value="' + qty + '" min="' + min + '" max="' + maxQty(l) + '">' +
                    '<button type="button" class="ps-cart-qty-btn" data-oe-qty="1" aria-label="Una unidad más">+</button>' +
                '</span>' +
                '<span class="psc-orderedit-amt"></span>' +
            '</label>';
        }).join(''));

        var changes = _preview.price_changes || 0;
        $('#psOrdereditPriceNote').toggleClass('bv-hidden', !changes).find('.psc-note-txt').text(
            changes === 1
                ? 'Un artículo cambió de precio desde ese pedido: se usa el precio actual.'
                : changes + ' artículos cambiaron de precio desde ese pedido: se usa el precio actual.'
        );
        $('#psOrdereditMirrorNote').toggleClass('bv-hidden', !(_preview.mirrored_carts > 0));
        $('#psOrdereditAddressNote').toggleClass('bv-hidden', !!_preview.has_address);
        $('#psOrdereditCreateLink').toggleClass('bv-hidden', !_canLink);
        showStep('form');
        recalc();
    }

    function recalc() {
        var total = 0, count = 0;
        $('#psOrdereditLines .psc-orderedit-line.is-pickable').each(function () {
            var $l = $(this);
            var on = $l.find('.psOrdereditChk').is(':checked');
            var qty = parseInt($l.find('.psOrdereditQty').val(), 10) || 0;
            var amt = qty * (parseFloat($l.data('unit')) || 0);
            $l.toggleClass('is-on', on);
            $l.find('.psc-orderedit-amt').text(on ? money(amt) : '—');
            if (on && qty > 0) { total += amt; count++; }
        });
        $('#psOrdereditTotal').text(money(total));
        setButtons(count > 0);
    }

    function setButtons(enabled) {
        $('#psOrdereditCreate, #psOrdereditCreateLink').prop('disabled', !enabled).toggleClass('is-disabled', !enabled);
    }

    $(document).on('change', '#psOrdereditLines .psOrdereditChk', recalc);
    $(document).on('input change', '#psOrdereditLines .psOrdereditQty', function () {
        var $l = $(this).closest('.psc-orderedit-line');
        var v = parseInt($(this).val(), 10);
        if (!isNaN(v)) {
            $(this).val(Math.min(parseInt($l.data('max'), 10) || 999, Math.max(parseInt($l.data('min'), 10) || 1, v)));
        }
        recalc();
    });
    $(document).on('click', '#psOrdereditLines .ps-cart-qty-btn', function (e) {
        e.preventDefault(); // dentro de un <label>: no alternar el checkbox
        var $l = $(this).closest('.psc-orderedit-line');
        var $in = $l.find('.psOrdereditQty');
        var next = (parseInt($in.val(), 10) || 1) + (parseInt($(this).data('oe-qty'), 10) || 0);
        $in.val(Math.min(parseInt($l.data('max'), 10) || 999, Math.max(parseInt($l.data('min'), 10) || 1, next)));
        recalc();
    });

    function selectedLines() {
        var out = [];
        $('#psOrdereditLines .psc-orderedit-line.is-pickable').each(function () {
            var $l = $(this);
            if (!$l.find('.psOrdereditChk').is(':checked')) { return; }
            var qty = parseInt($l.find('.psOrdereditQty').val(), 10) || 0;
            if (qty > 0) { out.push({ order_detail_id: parseInt($l.data('line'), 10), quantity: qty }); }
        });
        return out;
    }

    function create(withLink) {
        var lines = selectedLines();
        if (!lines.length || !_orderId) { return; }
        var $btn = $(withLink ? '#psOrdereditCreateLink' : '#psOrdereditCreate');
        var label = $btn.text();
        setButtons(false);
        $btn.text('Creando carrito…');
        $(SHEET).addClass('is-busy');
        $('#psOrdereditError').addClass('bv-hidden');
        var conv = window.HDCommerce && window.HDCommerce.conversationId ? window.HDCommerce.conversationId() : null;
        $.ajax({
            url: url(), method: 'POST', dataType: 'json',
            data: { lines: lines, with_link: withLink ? 1 : 0, conversation_id: conv || '' },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': W().csrf() },
        }).done(function (r) {
            if (!r || !r.success || !r.data) { showError((r && r.message) || 'No se pudo crear el carrito.'); setButtons(true); return; }
            _created = r.data;
            _linkSent = !!r.data.link_sent;
            renderDone(withLink);
            addConversationNote(withLink);
            if (_linkSent) { insertNotice(); }
            if (window.PscStore) { window.PscStore.load(true); }
        }).fail(function (xhr) {
            var r = xhr.responseJSON || {};
            showError(W().errorMessage(xhr, 'No se pudo crear el carrito.') + skippedText(r.skipped));
            setButtons(true);
        }).always(function () {
            $btn.text(label);
            $(SHEET).removeClass('is-busy');
        });
    }

    var REASONS = {
        discontinued: 'descatalogado', out_of_stock: 'sin stock', customized: 'personalizado', gift: 'regalo',
        minimal_quantity: 'por debajo de la cantidad mínima', not_added: 'la tienda no lo aceptó',
    };
    function skippedText(skipped) {
        if (!skipped || !skipped.length) { return ''; }
        return ' No añadidos: ' + skipped.map(function (s) { return s.name + ' (' + (REASONS[s.reason] || s.reason) + ')'; }).join(', ') + '.';
    }

    function renderDone(withLink) {
        var added = _created.added || [];
        $('#psOrdereditDoneText').text('Carrito #' + _created.cart_id + ' creado con ' + added.length +
            (added.length === 1 ? ' línea' : ' líneas') + ' · ' + money(_created.products_total) + '.');
        $('#psOrdereditDoneLines').html(added.map(function (a) {
            return '<div class="psc-orderedit-line">' +
                '<span class="nm"><span class="t">' + esc(a.name) + '</span><span class="s"><span class="mono">×' + esc(a.quantity) + ' · ' + money(a.unit_price) + '</span></span></span>' +
                '<span class="psc-orderedit-amt">' + money((parseFloat(a.unit_price) || 0) * (parseInt(a.quantity, 10) || 0)) + '</span>' +
            '</div>';
        }).join(''));
        var sk = skippedText(_created.skipped);
        $('#psOrdereditSkipped').toggleClass('bv-hidden', !sk).find('.psc-note-txt').text(sk.trim());
        $('#psOrdereditMailNote').toggleClass('bv-hidden', !withLink).find('.psc-note-txt').text(_linkSent
            ? 'La tienda ha enviado al correo de la cuenta del cliente el enlace para revisar y pagar el carrito.'
            : 'El carrito se ha creado, pero la tienda no ha podido enviar el correo con el enlace. Puedes indicarle al cliente que lo encontrará en su carrito al iniciar sesión.');
        $('#psOrdereditInsertLink').toggleClass('bv-hidden', !_linkSent);
        showStep('done');
    }

    function insertNotice() {
        if (!_linkSent || !_created) { return; }
        W().insert('Te he preparado un carrito con los productos de tu pedido' + (_created.source_reference ? ' #' + _created.source_reference : '') +
            ', con los precios actuales de la tienda. Te acabamos de enviar un correo con el enlace para revisarlo y completar la compra.');
    }

    // Rastro en la conversación (nota interna), como la devolución del
    // workspace: el agente siguiente ve qué carrito se creó y desde qué pedido.
    function addConversationNote(withLink) {
        var sendUrl = $('.bv-composer').data('bv-send-url');
        if (!sendUrl || !_created) { return; }
        $.ajax({
            url: sendUrl, method: 'POST', dataType: 'json',
            data: {
                body: 'Carrito #' + _created.cart_id + ' creado en PrestaShop repitiendo el pedido #' + (_created.source_reference || _orderId) +
                    ' (' + (_created.added || []).length + ' líneas, ' + money(_created.products_total) + ').' + skippedText(_created.skipped) +
                    (withLink ? (_linkSent ? ' Enlace de pago enviado por correo al cliente.' : ' No se pudo enviar el correo con el enlace.') : ''),
                is_internal: 1, action: 'send',
            },
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': W().csrf() },
        });
    }

    $(document).on('click', '#psOrdereditCreate', function () { if (!$(this).is(':disabled')) { create(false); } });
    $(document).on('click', '#psOrdereditCreateLink', function () { if (!$(this).is(':disabled')) { create(true); } });
    $(document).on('click', '#psOrdereditInsertLink', insertNotice);
    $(document).on('click', '#psOrdereditClose, #psOrdereditCancel, #psOrdereditBack', function () {
        _seq++;
        W().closeSheet(SHEET);
    });
})();
