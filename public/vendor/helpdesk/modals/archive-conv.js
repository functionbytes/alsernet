/*!
 * Helpdesk · modal "archive-conv" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/archive-conv.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox. Sin
 * interpolacion Blade: la config llega por atributos data-* del markup.
 *
 * Convencion del modulo core: su JS se sirve desde public/vendor/helpdesk/ y no
 * tiene copia fuente aparte (igual que conversations.js y kb-suggestions.js).
 */
(function ($) {
    'use strict';

    function getConvId() {
        return $('.bv-composer').data('bv-conversation-id') || null;
    }

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    $(document).on('click', '#bv-archive-conv-archive', function () {
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/archive',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('archive-conv');
            if (window.toastr) { toastr.success('Conversación archivada'); }
            $('.bv-conv.on').fadeOut(300, function () { $(this).remove(); });
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al archivar';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () { $btn.prop('disabled', false); });
    });

    $(document).on('click', '#bv-archive-conv-delete', function () {
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('archive-conv');
            if (window.toastr) { toastr.success('Conversación eliminada'); }
            $('.bv-conv.on').fadeOut(300, function () { $(this).remove(); });
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al eliminar';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () { $btn.prop('disabled', false); });
    });

}(window.jQuery));
