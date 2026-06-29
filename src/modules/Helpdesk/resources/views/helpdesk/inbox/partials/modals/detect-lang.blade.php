{{-- Modal: Detectar idioma — sugerencia IA (#90 ve-detect-lang) --}}
<div class="bv-modal" data-bv-modal-name="detect-lang">
    <div class="bv-modal-dialog sm">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-language"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">IA · DETECCIÓN</span>
                <div class="bv-modal-title">Idioma diferente detectado</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <p id="dlDetectedText" style="font-size:12px;line-height:1.55;margin:0 0 10px"></p>

            <div class="bv-quote-block" id="dlSampleQuote" style="display:none"></div>

            <div class="bv-ai-sparkle">
                <div class="bv-ai-sparkle__ic"><i class="fas fa-wand-magic-sparkles"></i></div>
                <div>
                    <div class="bv-ai-sparkle__lbl">SUGERENCIA IA</div>
                    Activar traducción automática bidireccional para esta conversación
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-dl-activate">Activar traducción</button>
            <button class="btn-secondary" id="bv-dl-incoming-only">Solo entrantes</button>
            <button class="btn-secondary" data-bv-close>No, gracias</button>
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
        var fromCode  = (data && data.fromCode)  || '';
        var toCode    = (data && data.toCode)    || 'es';

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

    function activateTranslation(mode) {
        var convId = getConvId();
        if (!convId) { return; }
        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/translation',
            method: 'POST',
            data: { mode: mode },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('detect-lang');
            if (window.toastr) { toastr.success('Traducción activada'); }
            $(document).trigger('bv:translation:activated', [convId, mode]);
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al activar traducción';
            if (window.toastr) { toastr.error(msg); }
        });
    }

    $(document).on('click', '#bv-dl-activate',      function () { activateTranslation('bidirectional'); });
    $(document).on('click', '#bv-dl-incoming-only', function () { activateTranslation('incoming'); });

}(window.jQuery));
</script>
@endpush
@endonce
