/*!
 * Helpdesk · modal "export-conv" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/export-conv.blade.php.
 * La config que antes se interpolaba con Blade (rutas, flags, datos de sesion,
 * textos traducidos) viaja ahora por atributos data-* del markup, que es lo que
 * permite servir este JS como fichero estatico cacheable.
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

    $(document).on('click', '#exportFormatList .bv-opt', function () {
        $('#exportFormatList .bv-opt').removeClass('on');
        $(this).addClass('on');
    });

    function exportPayload(convId) {
        return {
            conversation_id: convId,
            format:          $('#exportFormatList .bv-opt.on').data('bv-value') || 'pdf',
            include_notes:   $('#exportNotes').is(':checked') ? '1' : '0',
            include_meta:    $('#exportMeta').is(':checked') ? '1' : '0',
            include_attachments: $('#exportAttachments').is(':checked') ? '1' : '0',
            include_header:  $('#exportHeader').is(':checked') ? '1' : '0',
        };
    }

    $(document).on('click', '#bv-export-conv-go', function () {
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }
        var params = new URLSearchParams(exportPayload(convId));
        window.location.href = '/panel/helpdesk/exports/conversation-transcript?' + params.toString();
        closeBvModal('export-conv');
    });

    $(document).on('click', '#bv-export-conv-email', function () {
        var convId = getConvId();
        if (!convId) {
            if (window.toastr) { toastr.warning('Sin conversación activa'); }
            return;
        }
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enviando…');
        $.ajax({
            url: '/panel/helpdesk/exports/conversation-transcript/email',
            method: 'POST',
            data: exportPayload(convId),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function (resp) {
            closeBvModal('export-conv');
            if (window.toastr) { toastr.success(resp.message || 'Enviado por email'); }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'No se pudo enviar el archivo por email';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text($btn.data('label-default'));
        });
    });

}(window.jQuery));
