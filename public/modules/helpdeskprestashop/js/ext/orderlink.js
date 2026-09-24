/* HelpdeskPrestashop · extensión "orderlink" — inbox.
   Liga cada pedido de PrestaShop con la conversación en la que se trató:
   - al abrir el pedido en el workspace (psc:order-rendered) → "opened";
   - al insertar su tarjeta o su seguimiento en el chat → "card_sent";
   - las escrituras sobre el pedido las liga el servidor ("action") con la
     cabecera X-Ps-Conversation que ya añade opsmap.js.
   Muestra los pedidos ligados arriba del tab Tienda (#ps-ext-wrap) como
   chips "Pedido #REF · estado" que abren el workspace, con "Desligar".
   El mapeo de estados usa este vínculo para cambiar ESA conversación. */
(function ($) {
    'use strict';

    if (!$ || window.__psOrderlink) { return; }
    window.__psOrderlink = true;

    var BASE = '/panel/helpdesk/ps/ext/orderlink/conversations/';
    var sent = {};          // "conv:order:source" ya enviados en esta sesión
    var lastLinks = {};     // conv → { data, canUnlink }

    function C() { return window.HDCommerce || null; }
    function S() { return window.PscStore || null; }
    function W() { return window.PscOrderWorkspace || null; }

    function convId() {
        var c = C();
        var id = c && typeof c.conversationId === 'function' ? c.conversationId() : null;
        return id && /^\d+$/.test(String(id)) ? String(id) : null;
    }
    function csrf() {
        var c = C();
        if (c && typeof c.csrf === 'function') { return c.csrf(); }
        return $('meta[name="csrf-token"]').attr('content') || '';
    }
    function esc(s) {
        var st = S();
        if (st && st.esc) { return st.esc(s); }
        return $('<div>').text(s == null ? '' : String(s)).html();
    }
    function escAttr(s) {
        var st = S();
        if (st && st.escAttr) { return st.escAttr(s); }
        return esc(s).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    /* ── Registrar vínculo ───────────────────────────────────── */

    function record(order, source) {
        var conv = convId();
        var id = order && order.id ? String(order.id) : '';
        if (!conv || !/^\d+$/.test(id)) { return; }

        var key = conv + ':' + id + ':' + source;
        if (sent[key]) { return; }
        sent[key] = true;

        var ref = order.reference ? String(order.reference).replace(/^#/, '') : '';
        var data = { ps_order_id: id, source: source };
        if (ref && /^[A-Za-z0-9_\-]+$/.test(ref)) { data.ps_order_reference = ref; }

        $.ajax({
            url: BASE + conv,
            method: 'POST',
            dataType: 'json',
            data: data,
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
        }).done(function (r) {
            // Solo repinta si el agente sigue en la misma conversación.
            if (r && r.success && r.data && convId() === conv && $('#ps-ext-wrap').length) {
                load(conv);
            }
        }).fail(function () {
            // Silencioso: ligar es un apoyo, no la acción del agente. Se
            // permite reintentar en la próxima apertura.
            delete sent[key];
        });
    }

    $(document).on('psc:order-rendered', function (e, order) {
        record(order, 'opened');
    });

    // Tarjeta del pedido (pieza 18) y seguimiento (pieza 01) al composer.
    // order-workspace.js inserta el texto en su propio handler; aquí solo se
    // anota el vínculo con el pedido abierto en el workspace.
    $(document).on('click', '#powInsertCard, #powTrackToChat', function () {
        var w = W();
        var order = w && typeof w.order === 'function' ? w.order() : null;
        if (order) { record(order, 'card_sent'); }
    });

    /* ── Chips en el tab Tienda ──────────────────────────────── */

    function listedOrder(id) {
        var st = S();
        var ctx = (st && typeof st.ctx === 'function' && st.ctx()) || {};
        return (ctx.orders || []).filter(function (o) { return String(o.id) === String(id); })[0] || null;
    }

    function stateHtml(link) {
        var st = S();
        var o = listedOrder(link.ps_order_id);
        if (!st || !o) { return ''; }
        var name = st.orderStateName(o);
        var tag = st.orderKindTag(st.orderKind(o));
        return '<span class="psc-orderlink-sep">·</span><span class="psc-tag ' + escAttr(tag) + '">' + esc(name) + '</span>';
    }

    function refOf(link) {
        if (link.reference) { return String(link.reference); }
        var o = listedOrder(link.ps_order_id);
        return (o && o.reference) ? String(o.reference) : String(link.ps_order_id);
    }

    function render(conv) {
        var $wrap = $('#ps-ext-wrap');
        $wrap.find('[data-psc-orderlink]').remove();
        if (!$wrap.length || convId() !== conv) { return; }

        var state = lastLinks[conv];
        var links = (state && state.data) || [];
        if (!links.length) { return; }

        var items = links.map(function (l) {
            var meta = l.source_label ? esc(l.source_label) : '';
            if (l.linked_by) { meta += (meta ? ' · ' : '') + esc(l.linked_by); }
            return '<div class="psc-orderlink-item">' +
                '<button type="button" class="psc-orderlink-chip" data-psc-orderlink-open="' + escAttr(l.ps_order_id) + '">' +
                    '<span class="psc-orderlink-ref">Pedido #' + esc(refOf(l)) + '</span>' + stateHtml(l) +
                '</button>' +
                (meta ? '<span class="psc-orderlink-meta">' + meta + '</span>' : '') +
                (state.canUnlink
                    ? '<button type="button" class="psc-orderlink-unlink" data-psc-orderlink-unlink="' + escAttr(l.id) + '">Desligar</button>'
                    : '') +
            '</div>';
        }).join('');

        $wrap.prepend(
            '<div class="psc-card psc-orderlink" data-psc-orderlink>' +
                '<div class="psc-card-head">' +
                    '<span class="psc-card-head-tt"><span>Pedidos de esta conversación</span>' +
                    '<span class="s">El mapeo de estados cambia esta conversación cuando el pedido cambia en la tienda</span></span>' +
                    '<span class="psc-count">' + esc(links.length) + '</span>' +
                '</div>' +
                '<div class="psc-card-body"><div class="psc-orderlink-list">' + items + '</div></div>' +
            '</div>'
        );
    }

    function load(conv) {
        $.ajax({
            url: BASE + conv,
            method: 'GET',
            dataType: 'json',
            headers: { 'Accept': 'application/json' },
        }).done(function (r) {
            if (!r || !r.success) { return; }
            lastLinks[conv] = { data: r.data || [], canUnlink: !!r.can_unlink };
            render(conv);
        });
    }

    $(document).on('psc:store-rendered', function (e, ctx) {
        var conv = convId();
        $('#ps-ext-wrap').find('[data-psc-orderlink]').remove();
        if (!conv || !ctx || !ctx.customer || !ctx.customer.found) { return; }
        // Pinta ya lo que hubiera (sin parpadeo) y refresca del servidor.
        if (lastLinks[conv]) { render(conv); }
        load(conv);
    });

    $(document).on('click', '[data-psc-orderlink-open]', function () {
        var id = String($(this).data('psc-orderlink-open') || '');
        if (/^\d+$/.test(id) && typeof window.openPsOrderWorkspace === 'function') {
            window.openPsOrderWorkspace(id);
        }
    });

    $(document).on('click', '[data-psc-orderlink-unlink]', function () {
        var $btn = $(this);
        var conv = convId();
        var linkId = String($btn.data('psc-orderlink-unlink') || '');
        if (!conv || !/^\d+$/.test(linkId) || $btn.prop('disabled')) { return; }

        $btn.prop('disabled', true).text('Desligando…');
        $.ajax({
            url: BASE + conv + '/' + linkId + '/unlink',
            method: 'POST',
            dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
        }).done(function (r) {
            if (r && r.success) {
                // Si el agente vuelve a abrir el pedido, se liga de nuevo.
                Object.keys(sent).forEach(function (k) { if (k.indexOf(conv + ':') === 0) { delete sent[k]; } });
                if (window.toastr) { toastr.success('Pedido desligado de esta conversación.'); }
                load(conv);
            } else {
                $btn.prop('disabled', false).text('Desligar');
                if (window.toastr) { toastr.warning((r && r.message) || 'No se pudo desligar el pedido.'); }
            }
        }).fail(function (xhr) {
            $btn.prop('disabled', false).text('Desligar');
            var c = C();
            var msg = c && typeof c.errorMessage === 'function' ? c.errorMessage(xhr, 'No se pudo desligar el pedido.') : 'No se pudo desligar el pedido.';
            if (window.toastr) { toastr.error(msg); }
        });
    });
})(window.jQuery);
