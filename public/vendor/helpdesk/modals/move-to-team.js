/*!
 * Helpdesk · modal "move-to-team" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/move-to-team.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox. Sin
 * interpolacion Blade: la config llega por atributos data-* del markup.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 */
(function ($) {
    'use strict';

    $(document).on('click', '[data-bv-modal-name="move-to-team"] .bv-opt', function () {
        $('[data-bv-modal-name="move-to-team"] .bv-opt').removeClass('on');
        $(this).addClass('on');
    });

    $(document).on('input', '#move-team-search', function () {
        var q = $(this).val().toLowerCase();
        $('[data-bv-modal-name="move-to-team"] .bv-opt').each(function () {
            var name = $(this).find('.name').text().toLowerCase();
            $(this).toggle(!q || name.includes(q));
        });
    });

    // El guardado (click en #move-team-btn) lo maneja conversations-panel.js
    // vía ajaxConversationUpdate(), junto con el resto de modales de acción
    // rápida (assign, priority, tags). Tenerlo también aquí duplicaba la
    // petición: una PUT real desde este archivo y una POST con
    // X-HTTP-Method-Override desde el handler centralizado, ambas contra el
    // mismo endpoint y con el mismo payload.

}(window.jQuery));
