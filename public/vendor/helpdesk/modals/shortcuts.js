/*!
 * Helpdesk · modal "shortcuts" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/shortcuts.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox. Sin
 * interpolacion Blade: la config llega por atributos data-* del markup.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 */
(function ($) {
    'use strict';
    $(document).on('click', '#bv-sc-print', function () {
        window.print();
    });
}(window.jQuery));
