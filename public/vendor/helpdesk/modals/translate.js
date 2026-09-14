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

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'translate') { return; }
        // Pre-fill with selected text if any
        var sel = window.getSelection ? window.getSelection().toString().trim() : '';
        if (sel) { $('#translateInput').val(sel); }
        $('#translateResult').hide();
        $('#translateInput').focus();
    });

    $(document).on('click', '#translateSwapLangs', function () {
        var from = $('#translateFrom').val();
        var to   = $('#translateTo').val();
        if (from !== 'auto') {
            $('#translateFrom').val(to);
            $('#translateTo').val(from);
        }
    });

    $(document).on('click', '#bv-translate-go', function () {
        var text = $('#translateInput').val().trim();
        if (!text) {
            if (window.toastr) { toastr.warning('Escribe el texto a traducir'); }
            return;
        }
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Traduciendo…');
        $('#translateResult').hide();

        $.ajax({
            url: '/panel/helpdesk/translate',
            method: 'POST',
            data: { text: text, from: $('#translateFrom').val(), to: $('#translateTo').val() },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function (resp) {
            if (resp.success && resp.translated) {
                $('#translateResultText').text(resp.translated);
                $('#translateResult').show();
            } else {
                if (window.toastr) { toastr.error(resp.message || 'Sin resultado de traducción'); }
            }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al traducir';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Traducir');
        });
    });

    $(document).on('click', '#bv-translate-copy', function () {
        var text = $('#translateResultText').text();
        if (!text) { return; }
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function () {
                if (window.toastr) { toastr.success('Copiado al portapapeles'); }
            });
        } else {
            var $tmp = $('<textarea>').val(text).appendTo('body').select();
            document.execCommand('copy');
            $tmp.remove();
            if (window.toastr) { toastr.success('Copiado al portapapeles'); }
        }
    });

    $(document).on('click', '#bv-translate-insert', function () {
        var text = $('#translateResultText').text();
        if (!text) { return; }
        var $ta = $('.bv-composer textarea, .bv-composer [contenteditable]').first();
        if ($ta.is('[contenteditable]')) {
            $ta.focus();
            document.execCommand('insertText', false, text);
        } else {
            var cur = $ta.val();
            $ta.val(cur ? cur + '\n' + text : text);
            $ta.trigger('input');
        }
        closeBvModal('translate');
        if (window.toastr) { toastr.success('Traducción insertada'); }
    });

}(window.jQuery));
