/*!
 * Helpdesk · modal "close-conv" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/close-conv.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox. Sin
 * interpolacion Blade: la config llega por atributos data-* del markup.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 */
(function ($) {
    'use strict';

    $(document).on('click', '[data-bv-modal-name="close-conv"] .reason', function () {
        $('[data-bv-modal-name="close-conv"] .reason').removeClass('on');
        $(this).addClass('on');
        $(this).find('input[type="radio"]').prop('checked', true);
        $('#close-other-input').hide();
        if ($(this).data('reason') === 'other') {
            $('#close-other-input').show().focus();
        }
    });

}(window.jQuery));
