{{-- Modal: Sugerencias IA de respuesta (#88 ve-ai-suggest) --}}
<div class="bv-modal" data-bv-modal-name="ai-suggest">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-wand-magic-sparkles"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.ai_suggest_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.ai_suggest_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div id="aiSuggestLoading" class="bv-cv-loading-msg">
                <i class="fas fa-spinner fa-spin"></i>
            </div>

            <div id="aiSuggestContent" style="display:none">
                <div class="bv-quote-block" id="aiSuggestOriginalMsg"></div>

                <div class="bv-form-label bv-x10" id="aiSuggestCountLabel"></div>

                <div class="bv-ai-sug" id="aiSuggestList"></div>

                <div class="bv-ai-sparkle">
                    <div class="bv-ai-sparkle__ic"><i class="fas fa-circle-info"></i></div>
                    <div>
                        <div class="bv-ai-sparkle__lbl">{{ __('helpdesk::helpdesk.inbox.modals.ai_suggest_context_label') }}</div>
                        {{ __('helpdesk::helpdesk.inbox.modals.ai_suggest_context_text') }}
                    </div>
                </div>
            </div>

            <div id="aiSuggestError" class="bv-cv-loading-msg" style="display:none">
                <i class="fas fa-triangle-exclamation"></i>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-ai-suggest-insert" disabled>{{ __('helpdesk::helpdesk.inbox.modals.ai_suggest_insert') }}</button>
            <button class="btn-secondary" id="bv-ai-suggest-regen">{{ __('helpdesk::helpdesk.inbox.modals.ai_suggest_regenerate') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    var _selectedSuggestion = '';

    function getConvId() {
        return $('.bv-composer').data('bv-conversation-id') || null;
    }

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    function escHtml(s) {
        return $('<span>').text(s || '').html();
    }

    function loadSuggestions() {
        var convId = getConvId();
        if (!convId) { return; }

        _selectedSuggestion = '';
        $('#aiSuggestContent').hide();
        $('#aiSuggestError').hide();
        $('#aiSuggestLoading').show();
        $('#bv-ai-suggest-insert').prop('disabled', true);

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/ai/suggestions',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function (resp) {
            var d = resp.data || resp;
            var suggestions = d.suggestions || [];
            var lastMsg     = d.last_message || '';

            $('#aiSuggestOriginalMsg').text('"' + lastMsg + '"');
            $('#aiSuggestCountLabel').text(suggestions.length + ' opciones generadas');

            var html = suggestions.map(function (s) {
                return '<button type="button" class="bv-ai-sug__btn" data-text="' + $('<span>').text(s.text || s).html().replace(/"/g, '&quot;') + '">' +
                    (s.tone ? '<span class="bv-ai-sug__tag">' + escHtml(s.tone) + '</span>' : '') +
                    '<span class="bv-ai-sug__body">' + escHtml(s.text || s) + '</span>' +
                    '</button>';
            }).join('');
            $('#aiSuggestList').html(html);

            $('#aiSuggestLoading').hide();
            $('#aiSuggestContent').show();
        }).fail(function () {
            $('#aiSuggestLoading').hide();
            $('#aiSuggestError').show();
        });
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'ai-suggest') { return; }
        loadSuggestions();
    });

    $(document).on('click', '.bv-ai-sug__btn', function () {
        $('.bv-ai-sug__btn').removeClass('on');
        $(this).addClass('on');
        _selectedSuggestion = $(this).data('text');
        $('#bv-ai-suggest-insert').prop('disabled', false);
    });

    $(document).on('click', '#bv-ai-suggest-regen', loadSuggestions);

    $(document).on('click', '#bv-ai-suggest-insert', function () {
        if (!_selectedSuggestion) { return; }
        closeBvModal('ai-suggest');
        var $composer = $('.bv-composer__input, .bv-composer [contenteditable="true"]').first();
        if ($composer.length) {
            $composer.val(_selectedSuggestion).trigger('input').focus();
        }
        $(document).trigger('bv:ai:suggestion:inserted', [_selectedSuggestion]);
        if (window.toastr) { toastr.success('Sugerencia insertada'); }
    });

}(window.jQuery));
</script>
@endpush
@endonce
