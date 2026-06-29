{{-- Modal: Resumen IA de conversación (#87 ve-ai-summary) --}}
<div class="bv-modal" data-bv-modal-name="ai-summary">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-wand-magic-sparkles"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">IA · RESUMEN</span>
                <div class="bv-modal-title">Resumen de la conversación</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div id="aiSummaryLoading" class="bv-cv-loading-msg">
                <i class="fas fa-spinner fa-spin"></i>
            </div>

            <div id="aiSummaryContent" style="display:none">
                <div class="bv-ai-sparkle">
                    <div class="bv-ai-sparkle__ic"><i class="fas fa-wand-magic-sparkles"></i></div>
                    <div>
                        <div class="bv-ai-sparkle__lbl">GENERADO POR IA</div>
                        <span id="aiSummaryMeta">Resumen generado</span>
                    </div>
                </div>

                <div class="bv-ai-section">
                    <div class="bv-ai-section__label">Problema</div>
                    <p id="aiSummaryProblem" class="bv-ai-section__text"></p>
                </div>

                <div class="bv-ai-section">
                    <div class="bv-ai-section__label">Acciones realizadas</div>
                    <ul id="aiSummaryActions" class="bv-ai-section__list"></ul>
                </div>

                <div class="bv-ai-section">
                    <div class="bv-ai-section__label">Próximo paso sugerido</div>
                    <p id="aiSummaryNext" class="bv-ai-section__text"></p>
                </div>
            </div>

            <div id="aiSummaryError" class="bv-cv-loading-msg" style="display:none">
                <i class="fas fa-triangle-exclamation"></i>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-ai-summary-paste" disabled>Pegar en notas internas</button>
            <button class="btn-secondary" id="bv-ai-summary-regen">Regenerar resumen</button>
            <button class="btn-secondary" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
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
</script>
@endpush
@endonce
