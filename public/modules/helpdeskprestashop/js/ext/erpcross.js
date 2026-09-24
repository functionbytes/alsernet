/*!
 * HelpdeskPrestashop · extensión "erpcross" (la pone HelpdeskErp).
 *
 * Pedido de la tienda → Gestión: añade "Abrir en Gestión" a la tarjeta
 * "En Gestión (ERP)" (extensión erpbridge) del workspace de pedido cuando
 * erpbridge ha localizado el pedido en Gestión y devuelve su id central
 * (erp.central_id). Abre el workspace de pedido de Gestión
 * (ErpChat.openOrder → window.openErpOrderWorkspace) cerrando antes el de la
 * tienda: nunca un modal sobre otro.
 *
 * No toca erpbridge.js: lee su respuesta con el evento global ajaxSuccess
 * (que jQuery dispara DESPUÉS de sus callbacks .done, cuando la tarjeta ya
 * está pintada) y re-pinta el botón si la tarjeta se vuelve a pintar.
 *
 * Solo aparece si el módulo HelpdeskErp está cargado en la página
 * (window.ErpChat) y el agente puede ver pedidos de Gestión.
 * Fuente en modules/HelpdeskPrestashop/public/js/ext/; copiar a
 * public/modules/helpdeskprestashop/js/ext/ tras editar.
 */
(function ($) {
    'use strict';

    if (!$ || window.__psErpcrossLoaded) { return; }
    window.__psErpcrossLoaded = true;

    var PS_MODAL = 'ps-order-workspace';
    var URL_RE = /\/ps\/orders\/(\d+)\/erpbridge(?:[?#]|$)/;
    var found = {};   // id_order PS -> id central del pedido en Gestión

    function W() { return window.PscOrderWorkspace || null; }
    function E() { return window.ErpChat || null; }

    function canOpen() {
        var ec = E();
        return !!(ec && typeof ec.openOrder === 'function' && ec.can('orders'));
    }

    function escAttr(s) {
        var ec = E();
        if (ec) { return ec.escAttr(s); }
        return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function paint() {
        var w = W();
        if (!w || !canOpen()) { return; }
        var id = String(w.orderId() || '');
        var central = found[id];
        var $body = $('#psErpbridgeBody');
        if (!central || !$body.length || $body.find('[data-erpcross-open]').length) { return; }

        var btn = '<button type="button" class="psc-btn psc-btn--outline" data-erpcross-open="' + escAttr(central) + '">Abrir en Gestión</button>';
        var $acts = $body.find('.psc-erpbridge-actions').first();
        if ($acts.length) { $acts.prepend(btn); }
        else { $body.append('<div class="psc-erpbridge-actions">' + btn + '</div>'); }
    }

    $(document).ajaxSuccess(function (e, xhr, settings) {
        var m = settings && settings.url ? String(settings.url).match(URL_RE) : null;
        if (!m) { return; }
        var r = xhr && xhr.responseJSON;
        var d = r && r.data;
        var central = d && d.status === 'found' && d.erp && d.erp.central_id ? String(d.erp.central_id) : '';
        if (/^\d+$/.test(central)) { found[m[1]] = central; }
        else { delete found[m[1]]; }
        paint();
    });

    // erpbridge vuelve a pintar su tarjeta con cada pedido y al Reintentar.
    $(document).on('psc:order-rendered', function () { setTimeout(paint, 0); });

    $(document).on('click', '[data-erpcross-open]', function (e) {
        e.preventDefault();
        var central = String($(this).attr('data-erpcross-open') || '');
        var ec = E();
        if (!central || !ec) { return; }
        // Nunca un modal sobre otro: primero se cierra el pedido de la tienda.
        if (window.HDCommerce && typeof window.HDCommerce.close === 'function') { window.HDCommerce.close(PS_MODAL); }
        else { $('[data-bv-modal-name="' + PS_MODAL + '"]').removeClass('on'); }
        ec.openOrder(central);
    });
})(window.jQuery);
