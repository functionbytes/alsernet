/*!
 * Helpdesk · modal "detect-lang" del inbox.
 *
 * Extraido de resources/views/helpdesk/inbox/partials/modals/detect-lang.blade.php,
 * donde vivia inline y se re-descargaba en cada carga del inbox. Sin
 * interpolacion Blade: la config llega por el payload del evento bv:modal:open.
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

    $(document).on('bv:modal:open', function (e, name, data) {
        if (name !== 'detect-lang') { return; }
        var detected  = (data && data.detected)  || 'Desconocido';
        var working   = (data && data.working)   || 'Español';
        var sample    = (data && data.sample)    || '';
        var fromCode  = (data && data.fromCode)  || 'auto';
        var toCode    = (data && data.toCode)    || 'es';

        // Guardados en el propio botón: activateTranslation() los lee al
        // click sin depender de que `data` siga vivo en ese momento.
        $('#bv-dl-activate').data('from-code', fromCode).data('to-code', toCode);

        $('#dlDetectedText').html(
            'El cliente está escribiendo en <b>' + $('<span>').text(detected).html() + '</b>' +
            ' pero tu idioma de trabajo es <b>' + $('<span>').text(working).html() + '</b>.'
        );

        $('#bv-dl-activate').text('Activar traducción ' + (fromCode || '??').toUpperCase() + ' ↔ ' + toCode.toUpperCase());

        if (sample) {
            $('#dlSampleQuote').text('"' + sample + '"').show();
        } else {
            $('#dlSampleQuote').hide();
        }
    });

    // Reusa la misma lógica de sesión que el botón "Activar traducción" del
    // panel Traducir (window.bvApplyTranslationSettings, expuesta desde
    // conversations.js) — antes esto pegaba a POST .../translation, una ruta
    // que nunca existió (404 siempre), así que el botón no hacía nada.
    function activateTranslation(mode) {
        var convId = getConvId();
        if (!convId) { return; }

        var fromCode = $('#bv-dl-activate').data('from-code') || 'auto';
        var toCode = $('#bv-dl-activate').data('to-code') || 'es';

        if (typeof window.bvApplyTranslationSettings === 'function') {
            window.bvApplyTranslationSettings(mode === 'incoming' ? 'incoming' : 'both', fromCode, toCode);
        }

        closeBvModal('detect-lang');
        if (window.toastr) { toastr.success('Traducción activada'); }
        $(document).trigger('bv:translation:activated', [convId, mode]);
    }

    $(document).on('click', '#bv-dl-activate',      function () { activateTranslation('bidirectional'); });
    $(document).on('click', '#bv-dl-incoming-only', function () { activateTranslation('incoming'); });

}(window.jQuery));
