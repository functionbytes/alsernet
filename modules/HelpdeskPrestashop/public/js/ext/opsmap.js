/* HelpdeskPrestashop · extensión "opsmap" — inbox.
   Auditoría de acciones (pieza 40): las acciones contra la tienda que se
   lanzan desde el inbox no llevan la conversación en el cuerpo (el
   workspace de pedido, el carrito, las direcciones…). Se añade como
   cabecera X-Ps-Conversation a toda escritura hacia las rutas PrestaShop del
   panel, para que la auditoría ligue la acción a la conversación abierta.
   El servidor solo la acepta si la conversación es de ese mismo cliente. */
(function ($) {
    'use strict';

    if (!$ || window.__psOpsmapPrefilter) { return; }
    window.__psOpsmapPrefilter = true;

    var PS_URL = /\/panel\/helpdesk\/(customers\/\d+\/ps\/|ps\/)/;

    $.ajaxPrefilter(function (options, original, xhr) {
        var method = String(options.type || options.method || 'GET').toUpperCase();
        if (method === 'GET' || method === 'HEAD' || !PS_URL.test(String(options.url || ''))) { return; }

        var id = window.HDCommerce && typeof window.HDCommerce.conversationId === 'function'
            ? window.HDCommerce.conversationId()
            : null;

        if (id && /^\d+$/.test(String(id))) {
            xhr.setRequestHeader('X-Ps-Conversation', String(id));
        }
    });
})(window.jQuery);
