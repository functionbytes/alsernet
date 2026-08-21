{{-- Modal: Detectar idioma — sugerencia IA (#90 ve-detect-lang) --}}
<div class="bv-modal" data-bv-modal-name="detect-lang">
    <div class="bv-modal-dialog sm">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-language"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.detect_lang_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.detect_lang_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <p class="bv-x22" id="dlDetectedText"></p>

            <div class="bv-quote-block" id="dlSampleQuote" style="display:none"></div>

            <div class="bv-ai-sparkle">
                <div class="bv-ai-sparkle__ic"><i class="fas fa-wand-magic-sparkles"></i></div>
                <div>
                    <div class="bv-ai-sparkle__lbl">{{ __('helpdesk::helpdesk.inbox.modals.detect_lang_suggestion_label') }}</div>
                    {{ __('helpdesk::helpdesk.inbox.modals.detect_lang_suggestion_text') }}
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-dl-activate">{{ __('helpdesk::helpdesk.inbox.modals.detect_lang_activate') }}</button>
            <button class="btn-secondary" id="bv-dl-incoming-only">{{ __('helpdesk::helpdesk.inbox.modals.detect_lang_incoming_only') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.detect_lang_decline') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
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
</script>
@endpush
@endonce
