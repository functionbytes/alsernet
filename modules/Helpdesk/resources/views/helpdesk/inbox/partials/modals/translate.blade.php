{{-- Modal: Traducción de conversación (#46 ve-translate) --}}
<div class="bv-modal" data-bv-modal-name="translate">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-language"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.translate_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.translate_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.translate_text_label') }}</label>
                <textarea id="translateInput" class="bv-form-input bv-x55" rows="3" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.translate_text_placeholder') }}"></textarea>
            </div>

            <div class="bv-frow bv-x56">
                <div class="bv-form-field bv-x33">
                    <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.translate_from') }}</label>
                    <select id="translateFrom" class="bv-form-input">
                        <option value="auto">{{ __('helpdesk::helpdesk.inbox.modals.translate_auto_detect') }}</option>
                        <option value="es">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_es') }}</option>
                        <option value="en">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_en') }}</option>
                        <option value="fr">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_fr') }}</option>
                        <option value="de">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_de') }}</option>
                        <option value="it">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_it') }}</option>
                        <option value="pt">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_pt') }}</option>
                        <option value="nl">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_nl') }}</option>
                        <option value="ru">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_ru') }}</option>
                        <option value="zh">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_zh') }}</option>
                        <option value="ar">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_ar') }}</option>
                        <option value="ja">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_ja') }}</option>
                    </select>
                </div>
                <div class="bv-x70">
                    <button type="button" id="translateSwapLangs" class="btn-secondary bv-x71" title="{{ __('helpdesk::helpdesk.inbox.modals.translate_swap') }}">
                        <i class="fas fa-right-left"></i>
                    </button>
                </div>
                <div class="bv-form-field bv-x33">
                    <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.translate_to') }}</label>
                    <select id="translateTo" class="bv-form-input">
                        <option value="es" selected>{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_es') }}</option>
                        <option value="en">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_en') }}</option>
                        <option value="fr">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_fr') }}</option>
                        <option value="de">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_de') }}</option>
                        <option value="it">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_it') }}</option>
                        <option value="pt">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_pt') }}</option>
                        <option value="nl">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_nl') }}</option>
                        <option value="ru">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_ru') }}</option>
                        <option value="zh">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_zh') }}</option>
                        <option value="ar">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_ar') }}</option>
                        <option value="ja">{{ __('helpdesk::helpdesk.inbox.modals.translate_lang_ja') }}</option>
                    </select>
                </div>
            </div>

            <div id="translateResult" style="display:none;margin-top:10px;padding:10px 12px;background:var(--bv-bg-subtle,#f9fafb);border-radius:6px">
                <div class="bv-x72">{{ __('helpdesk::helpdesk.inbox.modals.translate_result_label') }}</div>
                <div class="bv-x73" id="translateResultText"></div>
                <div class="bv-x74">
                    <button type="button" id="bv-translate-copy" class="btn-secondary bv-x75">
                        <i class="far fa-copy me-1"></i> {{ __('helpdesk::helpdesk.inbox.modals.translate_copy') }}
                    </button>
                    <button type="button" id="bv-translate-insert" class="btn-secondary bv-x75">
                        <i class="fas fa-arrow-down-to-line me-1"></i> {{ __('helpdesk::helpdesk.inbox.modals.translate_insert_composer') }}
                    </button>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-translate-go">{{ __('helpdesk::helpdesk.inbox.modals.translate_go') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.order_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
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
</script>
@endpush
@endonce
