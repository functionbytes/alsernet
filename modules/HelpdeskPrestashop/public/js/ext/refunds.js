/*!
 * HelpdeskPrestashop · extensión "refunds" del inbox.
 *
 *  - Pieza 08 · Reembolso parcial: hoja #psRefundsSheet dentro del workspace
 *    de pedido. Las líneas y lo que queda por reembolsar salen del puente
 *    (refunds.order_refundable); el reembolso lo emite el handler del core de
 *    PrestaShop, que genera la nota de crédito (y el vale, si se elige).
 *  - Pieza 35 · Resolver devolución: cambio de estado real de la RMA, desde
 *    las tarjetas del tab Devoluciones (mini-modal ps-refunds-rma) o desde el
 *    workspace de pedido (hoja #psRefundsRmaSheet). El mensaje para el cliente
 *    se escribe en el composer sin enviar y queda como nota interna.
 *  - Pieza 36 · Etiqueta de retorno: la tienda no genera etiquetas de
 *    devolución por transportista, así que se ofrecen las instrucciones de
 *    retorno configuradas (config/ext/refunds.php).
 *
 * No edita right-panel-prestashop-tabs.js ni order-workspace.js: se engancha
 * por psc:order-rendered y observando el contenedor del tab Devoluciones.
 * Fuente en modules/HelpdeskPrestashop/public/js/ext/ — copiar a
 * public/modules/helpdeskprestashop/js/ext/ tras editar.
 */
(function () {
    'use strict';

    var C = window.HDCommerce;
    if (!C || !window.jQuery) { return; }

    var RMA_MODAL = 'ps-refunds-rma';

    function $cfg() { return $('#psRefundsCfg'); }
    function store() { return window.PscStore || null; }
    function ws() { return window.PscOrderWorkspace || null; }
    function limit() { return parseFloat($cfg().data('limit')) || 0; }
    function canResolve() { return String($cfg().data('can-resolve')) === '1'; }
    function rmaStates() { return $cfg().data('states') || {}; }
    function stateKey(id) {
        var map = rmaStates();
        var key = null;
        Object.keys(map).forEach(function (k) { if (Number(map[k]) === Number(id)) { key = k; } });
        return key;
    }
    function money(n) {
        if (store()) { return store().money(n); }
        return (Number(n) || 0).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
    }
    function esc(s) { return store() ? store().esc(s) : $('<div>').text(s == null ? '' : String(s)).html(); }
    // esc() no escapa comillas: todo valor que vaya dentro de un atributo pasa por aquí.
    function escAttr(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function url(suffix) { return '/panel/helpdesk/customers/' + C.customerId() + '/ps/ext/refunds' + suffix; }
    function uuid() {
        if (window.crypto && window.crypto.randomUUID) { return window.crypto.randomUUID(); }
        return 'r-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 12);
    }
    function headers(extra) {
        return $.extend({ 'Accept': 'application/json', 'X-CSRF-TOKEN': C.csrf() }, extra || {});
    }
    function toast(kind, msg) { if (window.toastr) { toastr[kind](msg); } }
    function insertInComposer(text) {
        if (store() && store().insert(text)) {
            toast('info', 'Insertado en el composer. Revísalo antes de enviar.');
            return true;
        }
        toast('warning', 'Abre una conversación para escribir en el chat.');
        return false;
    }
    // Nota interna en la conversación abierta (mismo endpoint que el composer).
    function internalNote(body) {
        var sendUrl = $('.bv-composer').data('bv-send-url');
        if (!sendUrl) { return $.Deferred().reject().promise(); }
        return $.ajax({
            url: sendUrl, method: 'POST', dataType: 'json',
            data: { body: body, is_internal: 1, action: 'send' },
            headers: headers(),
        });
    }

    /* ── Montaje de las hojas dentro del workspace de pedido ─────── */

    function mountSheets() {
        var $body = $('[data-bv-modal-name="ps-order-workspace"] .bv-po-body');
        if (!$body.length) { return; }
        $('#psRefundsSheet, #psRefundsRmaSheet').each(function () {
            if (!$.contains($body.get(0), this)) { $(this).appendTo($body); }
        });
    }

    /* ── Pieza 08 · Reembolso parcial ────────────────────────────── */

    var refund = null; // { order, data, dest, key, prefill }
    var pendingRefund = null; // abrir la hoja al terminar de pintar el pedido

    function openRefundSheet(prefill) {
        var api = ws();
        var order = api && api.order();
        if (!order) { return; }
        mountSheets();
        refund = { order: order, data: null, dest: 'payment', key: uuid(), prefill: prefill || {} };
        $('#psRefundsRef').text('#' + (order.reference || order.id));
        $('#psRefundsError').addClass('bv-hidden');
        $('#psRefundsForm').addClass('bv-hidden');
        $('#psRefundsLoading').removeClass('bv-hidden');
        $('#psRefundsApproval').addClass('bv-hidden');
        setSubmit(false);
        $('#psRefundsDest button').removeClass('is-on').filter('[data-dest="payment"]').addClass('is-on');
        destHint();
        api.openSheet('#psRefundsSheet');

        $.ajax({ url: url('/orders/' + order.id), method: 'GET', dataType: 'json', headers: headers() })
            .done(function (r) {
                if (!r || !r.success) { refundError((r && r.message) || 'No se pudo consultar el pedido.'); return; }
                refund.data = r.data || {};
                renderRefund();
            })
            .fail(function (xhr) { refundError(C.errorMessage(xhr, 'No se pudo consultar el pedido en PrestaShop.')); })
            .always(function () { $('#psRefundsLoading').addClass('bv-hidden'); });
    }

    function renderRefund() {
        var d = refund.data;
        var lines = d.lines || [];
        var prefill = refund.prefill || {};

        $('#psRefundsUnpaid').toggleClass('bv-hidden', !!d.paid);
        $('#psRefundsLines').html(lines.length ? lines.map(function (l) {
            var left = parseInt(l.refundable, 10) || 0;
            var unit = parseFloat(l.unit_price_tax_incl) || 0;
            var want = Math.min(left, parseInt(prefill[String(l.order_detail_id)], 10) || 0);
            var qty = want || 1;
            var sub;
            if (left === 0) {
                sub = (parseInt(l.refunded, 10) || 0) > 0 ? 'Ya reembolsado' : 'Ya devuelto';
            } else {
                sub = [l.reference, left + ' por reembolsar', money(unit) + ' / ud.'].filter(Boolean).join(' · ');
            }
            return '<label class="ps-ret-line' + (left === 0 ? ' is-done' : '') + (want ? ' is-on' : '') + '"' +
                ' data-line="' + escAttr(l.order_detail_id) + '" data-max="' + left + '" data-unit="' + escAttr(unit) + '">' +
                '<input type="checkbox" class="psRefundsChk"' + (left === 0 ? ' disabled' : '') + (want ? ' checked' : '') + '>' +
                '<span class="nm"><span class="t">' + esc(l.name) + ' ×' + (parseInt(l.quantity, 10) || 1) + '</span><span class="s">' + esc(sub) + '</span></span>' +
                (left > 1
                    ? '<span class="ps-cart-qty-stepper stepper">' +
                        '<button type="button" class="ps-cart-qty-btn" data-refunds-qty="-1" aria-label="Una unidad menos">−</button>' +
                        '<input type="number" class="ps-qty-input psRefundsQty" value="' + qty + '" min="1" max="' + left + '">' +
                        '<button type="button" class="ps-cart-qty-btn" data-refunds-qty="1" aria-label="Una unidad más">+</button>' +
                      '</span>'
                    : '<input type="hidden" class="psRefundsQty" value="1">') +
                '<span class="psc-refunds-amt">' + money(unit * (left > 0 ? qty : 0)) + '</span>' +
            '</label>';
        }).join('') : (store() ? store().emptyHtml(null, 'Sin líneas', 'Este pedido no tiene líneas en PrestaShop') : ''));

        var ship = parseFloat(d.shipping_refundable) || 0;
        $('#psRefundsShipping').prop('checked', false);
        $('#psRefundsShippingWrap').toggleClass('bv-hidden', ship <= 0);
        $('#psRefundsShippingLbl').text('Devolver también los gastos de envío (' + money(ship) + ')');
        $('#psRefundsRestock').prop('checked', false);
        // Si el pedido no está entregado PrestaShop repone el stock por su
        // cuenta; la casilla solo tiene sentido con el pedido ya entregado.
        $('#psRefundsRestockWrap').toggleClass('bv-hidden', !d.delivered);

        var already = parseFloat(d.already_refunded) || 0;
        $('#psRefundsAlreadyRow').toggleClass('bv-hidden', already <= 0);
        $('#psRefundsAlready').text(money(already));
        $('#psRefundsLimit').text(limit() > 0 ? money(limit()) : 'Sin permiso');

        $('#psRefundsForm').removeClass('bv-hidden');
        recalcRefund();
    }

    function refundSelection() {
        var lines = [];
        var total = 0;
        $('#psRefundsLines .ps-ret-line').each(function () {
            var $l = $(this);
            var on = $l.find('.psRefundsChk').is(':checked');
            var max = parseInt($l.data('max'), 10) || 0;
            var qty = Math.min(max, Math.max(1, parseInt($l.find('.psRefundsQty').val(), 10) || 1));
            var unit = parseFloat($l.data('unit')) || 0;
            $l.toggleClass('is-on', on);
            $l.find('.psc-refunds-amt').text(money(unit * qty));
            if (!on || max === 0) { return; }
            lines.push({ order_detail_id: parseInt($l.data('line'), 10), quantity: qty });
            total += unit * qty;
        });
        var shipping = $('#psRefundsShipping').is(':checked');
        if (shipping) { total += parseFloat((refund && refund.data && refund.data.shipping_refundable) || 0) || 0; }
        return { lines: lines, shipping: shipping, total: Math.round(total * 100) / 100 };
    }

    function recalcRefund() {
        if (!refund || !refund.data) { return; }
        var sel = refundSelection();
        var over = limit() > 0 && sel.total > limit();
        var ok = !!refund.data.paid && limit() > 0 && sel.total > 0 && (sel.lines.length > 0 || sel.shipping) && !over;
        $('#psRefundsTotal').text(money(sel.total));
        $('#psRefundsApproval').toggleClass('bv-hidden', !(over && refund.data.paid));
        setSubmit(ok);
    }

    function setSubmit(on) { $('#psRefundsSubmit').prop('disabled', !on).toggleClass('is-disabled', !on); }

    function destHint() {
        var dest = refund ? refund.dest : 'payment';
        $('#psRefundsDestHint').text(dest === 'voucher'
            ? 'PrestaShop emite la nota de crédito y crea un vale por el importe, válido un año, que envía al cliente por email.'
            : 'Se emite la nota de crédito en PrestaShop. El abono en la pasarela (tarjeta, PayPal…) se hace aparte, desde la propia pasarela.');
    }

    function refundError(msg) {
        $('#psRefundsError').removeClass('bv-hidden').find('.psc-note-txt').text(msg);
        $('#psRefundsSheet .ps-sheet-body').scrollTop(0);
    }

    function closeRefundSheet() { if (ws()) { ws().closeSheet('#psRefundsSheet'); } }

    $(document).on('change', '#psRefundsLines .psRefundsChk, #psRefundsShipping', recalcRefund);
    $(document).on('input', '#psRefundsLines .psRefundsQty', recalcRefund);
    $(document).on('click', '#psRefundsLines .ps-cart-qty-btn', function (e) {
        e.preventDefault();
        var $l = $(this).closest('.ps-ret-line');
        var $in = $l.find('.psRefundsQty');
        var max = parseInt($l.data('max'), 10) || 1;
        $in.val(Math.min(max, Math.max(1, (parseInt($in.val(), 10) || 1) + (parseInt($(this).data('refunds-qty'), 10) || 0))));
        $l.find('.psRefundsChk').prop('checked', true);
        recalcRefund();
    });
    $(document).on('click', '#psRefundsDest button', function () {
        $('#psRefundsDest button').removeClass('is-on');
        $(this).addClass('is-on');
        if (refund) { refund.dest = $(this).data('dest') === 'voucher' ? 'voucher' : 'payment'; }
        destHint();
    });
    $(document).on('click', '#psRefundsClose, #psRefundsCancel', closeRefundSheet);
    $(document).on('click', '#psRefundsOpen', function () { openRefundSheet({}); });

    $(document).on('click', '#psRefundsSubmit', function () {
        if (!refund || $(this).is(':disabled')) { return; }
        var sel = refundSelection();
        var order = refund.order;
        var $btn = $(this).prop('disabled', true).text('Emitiendo…');
        var $sheet = $('#psRefundsSheet').addClass('is-busy');
        $('#psRefundsError').addClass('bv-hidden');

        $.ajax({
            url: url('/orders/' + order.id), method: 'POST', dataType: 'json',
            data: {
                lines: sel.lines,
                refund_shipping: sel.shipping ? 1 : 0,
                destination: refund.dest,
                restock: $('#psRefundsRestock').is(':checked') ? 1 : 0,
                conversation_id: C.conversationId() || '',
            },
            headers: headers({ 'Idempotency-Key': refund.key }),
        }).done(function (r) {
            if (!r || !r.success) { refund.key = uuid(); refundError((r && r.message) || 'PrestaShop ha rechazado el reembolso.'); return; }
            var d = r.data || {};
            var ref = order.reference || order.id;
            toast('success', 'Reembolso de ' + money(d.amount) + ' emitido' + (d.order_slip_id ? ' (albarán ' + d.order_slip_id + ')' : '') + '.');
            internalNote('[PS] Reembolso parcial de ' + money(d.amount) + ' · pedido #' + ref +
                (d.order_slip_id ? ' · albarán ' + d.order_slip_id : '') +
                (d.voucher ? ' · como vale ' + d.voucher.code : ' · al método de pago (abono en la pasarela aparte)'));
            if (d.voucher && d.voucher.code) {
                insertInComposer('Hemos emitido el reembolso de tu pedido #' + ref + ' como un vale de ' + money(d.voucher.amount) +
                    '. Tu código es ' + d.voucher.code + ' y puedes usarlo en tu próxima compra durante un año.');
            }
            closeRefundSheet();
            if (ws()) { ws().reload(); }
            if (store()) { store().load(true); }
        }).fail(function (xhr) {
            // El puente guarda la respuesta por clave de idempotencia: tras un
            // rechazo, el siguiente intento (quizá con otra selección) lleva
            // una clave nueva para no recibir el mismo rechazo repetido.
            refund.key = uuid();
            var r = xhr.responseJSON || {};
            if (r.needs_approval) { $('#psRefundsApproval').removeClass('bv-hidden'); }
            refundError(C.errorMessage(xhr, 'No se pudo emitir el reembolso.'));
        }).always(function () {
            $btn.text('Emitir reembolso');
            $sheet.removeClass('is-busy');
            recalcRefund();
        });
    });

    $(document).on('click', '#psRefundsApproval', function () {
        if (!refund) { return; }
        var sel = refundSelection();
        var order = refund.order;
        var detail = [];
        $('#psRefundsLines .ps-ret-line.is-on').each(function () {
            detail.push($(this).find('.nm .t').text() + ' (' + ($(this).find('.psRefundsQty').val() || 1) + ' ud.)');
        });
        if (sel.shipping) { detail.push('gastos de envío'); }
        var $btn = $(this).prop('disabled', true);
        internalNote('[PS] Solicitud de aprobación · reembolso parcial de ' + money(sel.total) + ' del pedido #' + (order.reference || order.id) +
            ' · ' + (refund.dest === 'voucher' ? 'como vale de la tienda' : 'al método de pago') + (detail.length ? ' · ' + detail.join(', ') : '') + '.')
            .done(function () { toast('success', 'Solicitud de aprobación guardada como nota interna.'); closeRefundSheet(); })
            .fail(function () { refundError('Abre una conversación para pedir la aprobación.'); })
            .always(function () { $btn.prop('disabled', false); });
    });

    /* ── Pieza 36 · Instrucciones de retorno ─────────────────────── */

    function returnAddress() {
        var a = $cfg().data('address');
        return Array.isArray(a) ? a : [];
    }

    function instructionsText(rmaId, orderRef) {
        var days = parseInt($cfg().data('validity'), 10) || 14;
        var steps = $cfg().data('steps');
        steps = Array.isArray(steps) ? steps : [];
        var fill = function (s) {
            return String(s).replace(/:rma/g, 'RMA-' + rmaId).replace(/:order/g, '#' + orderRef).replace(/:days/g, String(days));
        };
        return 'Instrucciones para devolver tu pedido #' + orderRef + ' (RMA-' + rmaId + '):\n' +
            steps.map(function (s, i) { return (i + 1) + '. ' + fill(s); }).join('\n') +
            '\n\nDirección de devolución:\n' + returnAddress().join('\n');
    }

    function labelBlockHtml() {
        var addr = returnAddress();
        var days = parseInt($cfg().data('validity'), 10) || 14;
        return '<div class="psc-refunds-label">' +
            '<div class="ps-sec-label"><span>Etiqueta de retorno</span><span class="ln"></span></div>' +
            '<div class="psc-row"><span class="k">Transportista</span><span class="v">' + esc($cfg().data('carrier') || '—') + '</span></div>' +
            '<div class="psc-row"><span class="k">Dirección de devolución</span><span class="v">' + esc(addr[0] || 'Sin configurar') + '</span></div>' +
            (addr.length > 1 ? '<div class="psc-refunds-addr">' + esc(addr.slice(1).join('\n')) + '</div>' : '') +
            '<div class="psc-row"><span class="k">Validez</span><span class="v">' + days + ' días</span></div>' +
            (addr.length
                ? '<button type="button" class="psc-btn psc-btn--outline" data-rma-instructions>Enviar instrucciones al chat</button>'
                : '<div class="psc-note psc-note--lock"><span class="psc-note-txt">Falta la dirección de devoluciones (HELPDESK_PS_RETURN_ADDRESS). Sin ella no se pueden enviar las instrucciones.</span></div>') +
            '<span class="psc-refunds-hint">La tienda no genera etiquetas de devolución por transportista: en su lugar se envían estas instrucciones de retorno.</span>' +
        '</div>';
    }

    /* ── Pieza 35 · Resolver devolución ──────────────────────────── */

    var rma = null; // { id, where, $root, data, key, dirty }

    function rmaRoot(where) {
        return where === 'sheet' ? $('#psRefundsRmaSheet') : $('[data-bv-modal-name="' + RMA_MODAL + '"]');
    }

    function openRma(id, where) {
        if (!id) { return; }
        if (where === 'sheet') { mountSheets(); }
        var $root = rmaRoot(where);
        rma = { id: id, where: where, $root: $root, data: null, key: uuid(), dirty: false, denyArmed: false };
        $root.find('[data-rma-chip]').text('RMA-' + id);
        $root.find('[data-rma-slot]').html('<div class="psc-skel"></div><div class="psc-skel"></div><div class="psc-loading">Consultando la devolución…</div>');
        $root.find('[data-rma-apply]').prop('disabled', true).addClass('is-disabled').removeClass('bv-hidden');
        $root.find('[data-rma-deny]').addClass('bv-hidden');
        if (where === 'sheet') { ws().openSheet('#psRefundsRmaSheet'); } else { C.open(RMA_MODAL); }

        $.ajax({ url: url('/rma/' + id), method: 'GET', dataType: 'json', headers: headers() })
            .done(function (r) {
                if (!rma || rma.id !== id) { return; }
                if (!r || !r.success) { rmaFatal((r && r.message) || 'No se pudo consultar la devolución.'); return; }
                rma.data = r.data || {};
                renderRma(!!r.can_resolve);
            })
            .fail(function (xhr) { if (rma && rma.id === id) { rmaFatal(C.errorMessage(xhr, 'No se pudo consultar la devolución en PrestaShop.')); } });
    }

    function rmaFatal(msg) {
        rma.$root.find('[data-rma-slot]').html('<div class="psc-note psc-note--warn"><span class="psc-note-txt">' + esc(msg) + '</span></div>');
    }

    function stateName(id) {
        var s = ((rma && rma.data && rma.data.states) || []).filter(function (x) { return Number(x.id) === Number(id); })[0];
        return s ? s.name : '—';
    }

    function stateTag(id) {
        var kind = store() ? store().returnKind(stateName(id)) : 'open';
        var cls = { open: 'psc-tag--pending', done: 'psc-tag--progress', blocked: 'psc-tag--blocked' }[kind] || 'psc-tag--pending';
        return '<span class="psc-tag ' + cls + '">' + esc(stateName(id)) + '</span>';
    }

    function renderRma(allowed) {
        var d = rma.data;
        var deniedId = Number(rmaStates().denied || 0);
        var options = (d.states || []).filter(function (s) { return Number(s.id) !== Number(d.state_id) && Number(s.id) !== deniedId; });
        var html = '<div class="psc-note psc-note--warn bv-hidden" data-rma-error><span class="psc-note-txt"></span></div>' +
            '<div class="psc-row"><span class="k">Motivo del cliente</span><span class="v">' + esc(d.reason || 'Sin motivo indicado') + '</span></div>' +
            '<div class="psc-row"><span class="k">Pedido</span><span class="v mono">#' + esc(d.order_reference || d.order_id) + '</span></div>' +
            '<div class="psc-row"><span class="k">Estado actual</span><span class="v">' + stateTag(d.state_id) + '</span></div>' +
            '<div class="psc-refunds-rma-items">' + (d.items || []).map(function (it) {
                return '<div class="psc-rma-line"><span class="th"></span><span class="nm">' + esc(it.name) + '</span>' +
                    '<span class="qty">×' + (parseInt(it.quantity, 10) || 1) + '</span></div>';
            }).join('') + '</div>';

        if (!allowed) {
            html += '<div class="psc-note psc-note--lock"><span class="psc-note-txt">No tienes permiso para resolver devoluciones.</span></div>';
        } else if (!options.length) {
            html += '<div class="psc-note psc-note--info"><span class="psc-note-txt">No hay otro estado al que pasar esta devolución.</span></div>';
        } else {
            html += '<div class="psc-field"><span class="lbl">Nuevo estado</span><select data-rma-state>' +
                options.map(function (s) { return '<option value="' + escAttr(s.id) + '">' + esc(s.name) + '</option>'; }).join('') +
                '</select></div>' +
                '<div class="psc-field"><span class="lbl">Mensaje para el cliente</span><textarea rows="3" maxlength="2000" data-rma-msg></textarea>' +
                '<span class="hint">Se escribe en el chat sin enviarlo y queda como nota interna.</span></div>' +
                '<label class="psc-check" data-rma-attach-wrap><input type="checkbox" data-rma-attach checked><span>Adjuntar instrucciones de retorno</span></label>' +
                '<label class="psc-check"><input type="checkbox" data-rma-notify><span>Avisar también por email con la plantilla de PrestaShop</span></label>';
        }
        html += '<div data-rma-label></div>' +
            (limit() > 0 ? '<button type="button" class="psc-btn psc-btn--outline bv-hidden" data-rma-refund>Reembolsar estas unidades</button>' : '');

        rma.$root.find('[data-rma-slot]').html(html);
        rma.$root.find('[data-rma-apply]').toggleClass('bv-hidden', !allowed || !options.length);
        rma.$root.find('[data-rma-deny]').toggleClass('bv-hidden', !allowed || Number(d.state_id) === deniedId || !deniedId);
        rma.dirty = false;
        rma.denyArmed = false;
        syncRmaState();
    }

    function selectedState() { return parseInt(rma.$root.find('[data-rma-state]').val(), 10) || 0; }

    function messageFor(key) {
        var d = rma.data;
        var id = d.id;
        var ref = '#' + (d.order_reference || d.order_id);
        switch (key) {
            case 'approved': return 'Hemos aprobado tu devolución RMA-' + id + ' del pedido ' + ref + '. A continuación te indicamos cómo enviarnos el paquete.';
            case 'received': return 'Hemos recibido el paquete de tu devolución RMA-' + id + '. Lo revisamos y te avisamos en cuanto emitamos el reembolso.';
            case 'completed': return 'Tu devolución RMA-' + id + ' del pedido ' + ref + ' está completada. Gracias por tu paciencia.';
            case 'denied': return 'No podemos aceptar tu devolución RMA-' + id + ' del pedido ' + ref + ' porque ';
            default: return 'Hemos actualizado tu devolución RMA-' + id + ' del pedido ' + ref + '.';
        }
    }

    function syncRmaState() {
        if (!rma || !rma.data) { return; }
        var d = rma.data;
        var sel = selectedState();
        var key = stateKey(sel);
        var labels = { approved: 'Aprobar devolución', received: 'Marcar paquete recibido', completed: 'Completar devolución' };
        rma.$root.find('[data-rma-apply]').text(labels[key] || 'Guardar estado')
            .prop('disabled', !sel).toggleClass('is-disabled', !sel);
        if (!rma.dirty) { rma.$root.find('[data-rma-msg]').val(sel ? messageFor(key) : ''); }
        rma.$root.find('[data-rma-attach-wrap]').toggleClass('bv-hidden', key !== 'approved');

        var currentKey = stateKey(d.state_id);
        var showLabel = key === 'approved' || currentKey === 'approved';
        rma.$root.find('[data-rma-label]').html(showLabel ? labelBlockHtml() : '');
        var refundable = ['received', 'completed'].indexOf(key) !== -1 || ['received', 'completed'].indexOf(currentKey) !== -1;
        rma.$root.find('[data-rma-refund]').toggleClass('bv-hidden', !refundable || !(d.items || []).length);
    }

    function rmaError(msg) {
        rma.$root.find('[data-rma-error]').removeClass('bv-hidden').find('.psc-note-txt').text(msg);
    }

    function closeRma() {
        if (!rma) { return; }
        if (rma.where === 'sheet') { if (ws()) { ws().closeSheet('#psRefundsRmaSheet'); } } else { C.close(RMA_MODAL); }
    }

    function submitRma(stateId, $btn) {
        var d = rma.data;
        var message = String(rma.$root.find('[data-rma-msg]').val() || '').trim();
        var key = stateKey(stateId);
        var attach = key === 'approved' && rma.$root.find('[data-rma-attach]').is(':checked') && returnAddress().length > 0;
        var notify = rma.$root.find('[data-rma-notify]').is(':checked');
        var fromName = stateName(d.state_id);
        var toName = stateName(stateId);
        var label = $btn.text();
        var current = rma;

        rma.$root.find('[data-rma-error]').addClass('bv-hidden');
        rma.$root.find('[data-rma-apply], [data-rma-deny]').prop('disabled', true);
        $btn.text('Guardando…');

        $.ajax({
            url: url('/rma/' + d.id + '/state'), method: 'POST', dataType: 'json',
            data: { state_id: stateId, notify: notify ? 1 : 0, message: message, conversation_id: C.conversationId() || '' },
            headers: headers({ 'Idempotency-Key': current.key + ':' + stateId }),
        }).done(function (r) {
            if (!r || !r.success) { current.key = uuid(); rmaError((r && r.message) || 'PrestaShop ha rechazado el cambio.'); return; }
            var res = r.data || {};
            toast('success', 'RMA-' + d.id + ' pasa a «' + (res.state_name || toName) + '».');
            var text = message;
            if (attach) { text = (text ? text + '\n\n' : '') + instructionsText(d.id, d.order_reference || d.order_id); }
            if (text) { insertInComposer(text); }
            internalNote('[PS] RMA-' + d.id + ' · pedido #' + (d.order_reference || d.order_id) + ': ' + fromName + ' → ' + (res.state_name || toName) +
                (res.notified ? ' · aviso por email enviado' : '') + (message ? ' · Mensaje: ' + message : ''));
            closeRma();
            afterRmaChange();
        }).fail(function (xhr) {
            current.key = uuid();
            rmaError(C.errorMessage(xhr, 'No se pudo cambiar el estado de la devolución.'));
        }).always(function () {
            $btn.text(label);
            current.$root.find('[data-rma-deny]').prop('disabled', false);
            if (rma === current) { syncRmaState(); }
        });
    }

    // Tras resolver: se recargan las devoluciones del contexto y se repinta lo
    // que esté a la vista (tab Devoluciones y/o workspace de pedido).
    function afterRmaChange() {
        if (store()) {
            store().reloadReturns(function () {
                if ($('#bv-ps-returns').is(':visible')) {
                    $('.bv-right-tab[data-bv-tab="ps-returns"]').first().trigger('click');
                }
                if (ws() && ws().order() && $('[data-bv-modal-name="ps-order-workspace"]').is(':visible')) { ws().reload(); }
            });
        }
    }

    $(document).on('change', '[data-rma-state]', syncRmaState);
    $(document).on('input', '[data-rma-msg]', function () { if (rma) { rma.dirty = true; } });
    $(document).on('click', '#psRefundsRmaSheet [data-rma-cancel]', closeRma);

    $(document).on('click', '[data-rma-apply]', function () {
        if (!rma || !rma.data || $(this).is(':disabled')) { return; }
        var sel = selectedState();
        if (!sel) { return; }
        submitRma(sel, $(this));
    });

    $(document).on('click', '[data-rma-deny]', function () {
        if (!rma || !rma.data || $(this).is(':disabled')) { return; }
        var deniedId = Number(rmaStates().denied || 0);
        var $msg = rma.$root.find('[data-rma-msg]');
        var text = String($msg.val() || '').trim();
        var template = messageFor('denied').trim();
        // Denegar exige un motivo real: la primera pulsación siempre pone la
        // plantilla de denegación (aunque el agente ya hubiera escrito otro
        // mensaje, p. ej. el de aprobar) y la segunda solo pasa si la completó.
        if (!rma.denyArmed || !text || text === template) {
            if (!rma.denyArmed || !text) { $msg.val(messageFor('denied')); }
            $msg.trigger('focus');
            rma.dirty = true;
            rma.denyArmed = true;
            rmaError('Escribe el motivo de la denegación para el cliente y vuelve a pulsar «Denegar con motivo».');
            return;
        }
        submitRma(deniedId, $(this));
    });

    $(document).on('click', '[data-rma-instructions]', function () {
        if (!rma || !rma.data) { return; }
        insertInComposer(instructionsText(rma.data.id, rma.data.order_reference || rma.data.order_id));
    });

    $(document).on('click', '[data-rma-refund]', function () {
        if (!rma || !rma.data) { return; }
        var prefill = {};
        (rma.data.items || []).forEach(function (it) { prefill[String(it.order_detail_id)] = parseInt(it.quantity, 10) || 1; });
        if (rma.where === 'sheet') {
            ws().closeSheet('#psRefundsRmaSheet');
            openRefundSheet(prefill);
            return;
        }
        // Desde el panel derecho: se cierra el mini-modal y se abre el
        // workspace del pedido con la hoja de reembolso ya rellenada.
        pendingRefund = { orderId: String(rma.data.order_id), prefill: prefill };
        C.close(RMA_MODAL);
        if (window.openPsOrderWorkspace) { window.openPsOrderWorkspace(rma.data.order_id); }
    });

    /* ── Workspace de pedido: tarjetas en la pestaña Pago ────────── */

    function orderReturns(orderId) {
        var ctx = (store() && store().ctx()) || {};
        return (ctx.returns || []).filter(function (r) { return String(r.order_id) === String(orderId); });
    }

    $(document).on('psc:order-rendered', function (e, order) {
        mountSheets();
        var $pago = $('#powPanelPago');
        $pago.find('.psc-refunds-card').remove();

        var cards = '';
        // Sin cobro no hay nada que reembolsar: la tarjeta no se ofrece.
        var W = window.PscOrderWorkspace;
        var st = W && W.stateOf ? W.stateOf(order) : {};
        var charged = st.paid || st.shipped || st.delivery;
        if (limit() > 0 && charged) {
            cards += '<div class="bv-po-card psc-refunds-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-receipt"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Reembolso parcial</span><span class="s">Nota de crédito o vale · hasta ' + esc(money(limit())) + '</span></div></div>' +
                '<button type="button" class="btn-secondary bv-po-btn" id="psRefundsOpen">Reembolso parcial</button>' +
            '</div>';
        }
        var rets = orderReturns(order.id);
        if (rets.length) {
            cards += '<div class="bv-po-card psc-refunds-card">' +
                '<div class="bv-po-card-h"><span class="bv-po-sec-ic"><i class="fas fa-rotate-left"></i></span>' +
                    '<div class="bv-po-card-ht"><span class="t">Devoluciones del pedido</span><span class="s">' + rets.length + ' RMA</span></div></div>' +
                rets.map(function (r) {
                    var kind = store() ? store().returnKind(r.state_name) : 'open';
                    var cls = { open: 'psc-tag--pending', done: 'psc-tag--progress', blocked: 'psc-tag--blocked' }[kind] || 'psc-tag--pending';
                    return '<div class="psc-refunds-rma-row">' +
                        '<span class="ref">RMA-' + esc(r.id) + '</span>' +
                        '<span class="psc-tag ' + cls + '">' + esc(r.state_name || 'Pendiente') + '</span>' +
                        (canResolve() ? '<button type="button" class="psc-refunds-link" data-refunds-rma-sheet="' + escAttr(r.id) + '">Resolver</button>' : '') +
                    '</div>';
                }).join('') +
            '</div>';
        }
        if (cards) { $pago.append(cards); }

        if (pendingRefund && pendingRefund.orderId === String(order.id)) {
            var p = pendingRefund.prefill;
            pendingRefund = null;
            openRefundSheet(p);
        }
    });

    $(document).on('click', '[data-refunds-rma-sheet]', function () {
        openRma(parseInt($(this).data('refunds-rma-sheet'), 10), 'sheet');
    });

    /* ── Tab Devoluciones del panel derecho: acciones en cada RMA ── */

    function decorateReturns() {
        var $tab = $('#bv-ps-returns');
        var approvedId = Number(rmaStates().approved || 0);
        var ctx = (store() && store().ctx()) || {};
        $tab.find('.psc-rma').not('.psc-refunds-done').each(function () {
            var $card = $(this).addClass('psc-refunds-done');
            var m = /RMA-(\d+)/.exec($card.find('.psc-rma-ref').text() || '');
            if (!m) { return; }
            var id = parseInt(m[1], 10);
            var ret = (ctx.returns || []).filter(function (r) { return Number(r.id) === id; })[0] || {};
            var btns = '';
            if (canResolve()) { btns += '<button type="button" data-refunds-rma-modal="' + escAttr(id) + '">Resolver</button>'; }
            if (Number(ret.state_id) === approvedId && returnAddress().length) {
                btns += '<button type="button" data-refunds-instr="' + escAttr(id) + '" data-order-ref="' + escAttr(ret.order_reference || ret.order_id || '') + '">Instrucciones de retorno</button>';
            }
            if (!btns) { return; }
            var $acts = $card.find('.psc-rma-acts');
            if (!$acts.length) { $acts = $('<div class="psc-rma-acts"></div>').appendTo($card); }
            $acts.append(btns);
        });
    }

    $(document).on('click', '[data-refunds-rma-modal]', function () {
        openRma(parseInt($(this).data('refunds-rma-modal'), 10), 'modal');
    });
    $(document).on('click', '[data-refunds-instr]', function () {
        insertInComposer(instructionsText($(this).data('refunds-instr'), $(this).data('order-ref')));
    });

    $(function () {
        var el = document.getElementById('bv-ps-returns');
        if (el && window.MutationObserver) {
            new MutationObserver(decorateReturns).observe(el, { childList: true, subtree: true });
        }
        decorateReturns();
        mountSheets();
    });
})();
