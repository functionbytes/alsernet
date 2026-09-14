/*!
 * Helpdesk · modal "{name}" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/{name}.blade.php,
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

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'internal-note') { return; }
        $('#internalNoteText').val('').focus();
        $('#internalNoteMentions').val('');
        $('#internalNotePriority').val('important');
        $('#internalNoteExpiry').val('7');
        $('#internalNotePinned').prop('checked', true);
        $('#internalNotePush').prop('checked', false);
    });

    $(document).on('click', '#bv-internal-note-save', function () {
        var text = $('#internalNoteText').val().trim();
        if (!text) {
            if (window.toastr) { toastr.warning('La nota no puede estar vacía'); }
            $('#internalNoteText').focus();
            return;
        }
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }

        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando…');

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/notes',
            method: 'POST',
            data: {
                content:    text,
                mentions:   $('#internalNoteMentions').val().trim(),
                priority:   $('#internalNotePriority').val(),
                expires_in: $('#internalNoteExpiry').val(),
                pinned:     $('#internalNotePinned').is(':checked') ? 1 : 0,
                push_team:  $('#internalNotePush').is(':checked') ? 1 : 0,
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function (resp) {
            closeBvModal('internal-note');
            if (window.toastr) { toastr.success('Nota guardada'); }
            $(document).trigger('bv:note:created', [convId, resp]);
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al guardar la nota';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Guardar nota');
        });
    });

}(window.jQuery));
