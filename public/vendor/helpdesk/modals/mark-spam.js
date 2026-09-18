/*!
 * Helpdesk · modal "mark-spam" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/mark-spam.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox. Sin
 * interpolacion Blade: la config llega por atributos data-* del markup.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 */
(function ($) {
    'use strict';

    $(document).on('click', '#bv-mark-spam-confirm', function () {
        var url = $('#bv-btn-mark-spam').data('spam-url');
        if (!url) {
            if (window.toastr) toastr.warning('No hay conversación activa');
            return;
        }

        var $btn = $(this).prop('disabled', true);

        $.ajax({
            url: url,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
            },
        })
        .done(function () {
            $('[data-bv-modal-name="mark-spam"]').removeClass('on');
            if ($('.bv-modal.on').length === 0) $('body').css('overflow', '');
            if (window.toastr) toastr.success('Conversación marcada como spam.');
            $('.bv-conv.on').fadeOut(300, function () { $(this).remove(); });
        })
        .fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al marcar como spam';
            if (window.toastr) toastr.error(msg);
        })
        .always(function () {
            $btn.prop('disabled', false);
        });
    });

}(window.jQuery));
