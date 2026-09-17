/**
 * Puente Engagement -> widget de LiveChat (public/widget/spa.blade.php).
 * Extraido de los <script> inline de esa vista.
 *
 * El stub de cola (window.chat = window.chat || function...) debe ejecutar
 * de forma SINCRONA, antes de que cargue el SDK externo (<script async>
 * cargado justo despues en la vista) — por eso este fichero se sirve con un
 * <script src> normal (sin async/defer) en la misma posicion del documento
 * que ocupaban los dos <script> inline originales.
 *
 * Depende de window.HelpdeskEngagementBridgeConfig = { token, apiUrl },
 * sembrado por un bootstrap inline minimo en la propia vista (token del
 * sitio + URL base — lo unico que este fichero no puede resolver por su
 * cuenta).
 */
(function (w, d) {
    'use strict';

    w.chat = w.chat || function () { (w.chat.q = w.chat.q || []).push(arguments); };

    var config = w.HelpdeskEngagementBridgeConfig || {};

    w.chat('init', {
        token: config.token,
        apiUrl: config.apiUrl,
        consent: true,
    });

    w.chat('on', 'trigger:fired', function (e) {
        var action = (e && e.action) ? e.action : {};
        var widget = w.HelpdeskWidget;
        if (!widget) return;

        switch (action.type) {
            case 'open_chat':
                widget.open();
                break;
            case 'show_message_in_chat':
                widget.botMessage(action.text || action.message || '');
                widget.open();
                break;
            case 'prefill_form':
                widget.prefill(action.fields || {});
                widget.open();
                break;
            case 'request_rating':
                widget.requestRating();
                widget.open();
                break;
            case 'inject_recommendations':
                widget.showRecommendations(action.products || []);
                widget.open();
                break;
        }
    });
})(window, document);
