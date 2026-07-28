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
