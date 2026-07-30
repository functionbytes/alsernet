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

    var _summaryText = '';

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

    function loadSummary() {
        var convId = getConvId();
        if (!convId) {
            $('#aiSummaryLoading').hide();
            $('#aiSummaryError').show().find('i').after(' Sin conversación activa');
            return;
        }

        $('#aiSummaryContent').hide();
        $('#aiSummaryError').hide();
        $('#aiSummaryLoading').show();
        $('#bv-ai-summary-paste').prop('disabled', true);
        _summaryText = '';

        $.ajax({
            url: '/panel/helpdesk/conversations/' + convId + '/ai/summary',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function (resp) {
            var d = resp.data || resp;
            $('#aiSummaryProblem').text(d.problem || '');
            $('#aiSummaryNext').text(d.next_step || '');
            var actions = d.actions || [];
            var actHtml = actions.map(function (a) {
                return '<li class="bv-ai-section__list-item">' + escHtml(a) + '</li>';
            }).join('');
            $('#aiSummaryActions').html(actHtml);
            $('#aiSummaryMeta').text(d.meta || 'Resumen generado');
            _summaryText = [
                'Problema:\n' + (d.problem || ''),
                '\nAcciones:\n' + actions.map(function (a) { return '• ' + a; }).join('\n'),
                '\nPróximo paso:\n' + (d.next_step || '')
            ].join('');
            $('#aiSummaryLoading').hide();
            $('#aiSummaryContent').show();
            $('#bv-ai-summary-paste').prop('disabled', false);
        }).fail(function (xhr) {
            $('#aiSummaryLoading').hide();
            $('#aiSummaryError').show();
        });
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'ai-summary') { return; }
        loadSummary();
    });

    $(document).on('click', '#bv-ai-summary-regen', loadSummary);

    $(document).on('click', '#bv-ai-summary-paste', function () {
        if (!_summaryText) { return; }
        closeBvModal('ai-summary');
        var $noteArea = $('.bv-composer__note-area, #internalNoteText');
        if ($noteArea.length) {
            $noteArea.val(_summaryText).focus();
        } else {
            $(document).trigger('bv:modal:open', ['internal-note']);
            setTimeout(function () { $('#internalNoteText').val(_summaryText).focus(); }, 100);
        }
        if (window.toastr) { toastr.success('Resumen pegado en la nota'); }
    });

}(window.jQuery));
