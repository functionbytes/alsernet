{{-- Modal: Sentimiento del cliente (#89 ve-sentiment) --}}
<div class="bv-modal" data-bv-modal-name="sentiment">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-face-smile"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">IA · ANÁLISIS</span>
                <div class="bv-modal-title">Sentimiento del cliente</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div id="sentimentLoading" class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>

            <div id="sentimentContent" style="display:none">

                <div class="bv-sent-bar">
                    <i class="far fa-face-frown bv-sent-bar__icon-neg"></i>
                    <div class="bv-sent-bar__track">
                        <div class="bv-sent-bar__fill" id="sentFill" style="width:50%"></div>
                    </div>
                    <i class="far fa-face-smile bv-sent-bar__icon-pos"></i>
                    <span class="bv-sent-bar__score" id="sentScore"></span>
                </div>

                <p class="bv-sent-desc" id="sentDescription"></p>

                <div class="bv-form-label" style="margin-bottom:8px">Timeline de sentimiento</div>
                <div id="sentTimeline" class="bv-sent-timeline"></div>

            </div>

            <div id="sentimentError" class="bv-cv-loading-msg" style="display:none">
                <i class="fas fa-triangle-exclamation"></i>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-sentiment-coaching" disabled>Marcar para coaching</button>
            <button class="btn-secondary" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function ($) {
    'use strict';

    var _sentConvId = null;

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

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'sentiment') { return; }
        _sentConvId = getConvId();
        if (!_sentConvId) { return; }

        $('#sentimentContent').hide();
        $('#sentimentError').hide();
        $('#sentimentLoading').show();
        $('#bv-sentiment-coaching').prop('disabled', true);

        $.ajax({
            url: '/panel/helpdesk/conversations/' + _sentConvId + '/ai/sentiment',
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (resp) {
            var d = resp.data || resp;
            var score = parseFloat(d.score || 0);
            var pct   = Math.round(((score + 1) / 2) * 100);

            $('#sentFill').css('width', pct + '%');
            $('#sentScore').text((score >= 0 ? '+' : '') + score.toFixed(2));
            $('#sentDescription').text(d.description || '');

            var timeline = d.timeline || [];
            var html = timeline.map(function (t) {
                var neg = parseFloat(t.score || 0) < 0;
                return '<div class="bv-sent-row">' +
                    '<span class="bv-sent-row__ts">' + escHtml(t.time || t.ts) + '</span>' +
                    '<span class="bv-sent-row__act">' + escHtml(t.label || t.action) + '</span>' +
                    '<span class="bv-sent-row__val' + (neg ? ' bv-sent-row__val--neg' : '') + '">' +
                        (parseFloat(t.score) >= 0 ? '+' : '') + parseFloat(t.score || 0).toFixed(2) +
                    '</span>' +
                    '</div>';
            }).join('');
            $('#sentTimeline').html(html);

            $('#sentimentLoading').hide();
            $('#sentimentContent').show();
            $('#bv-sentiment-coaching').prop('disabled', false);
        }).fail(function () {
            $('#sentimentLoading').hide();
            $('#sentimentError').show();
        });
    });

    $(document).on('click', '#bv-sentiment-coaching', function () {
        if (!_sentConvId) { return; }
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Marcando…');
        $.ajax({
            url: '/panel/helpdesk/conversations/' + _sentConvId + '/coaching',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('sentiment');
            if (window.toastr) { toastr.success('Conversación marcada para coaching'); }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al marcar para coaching';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Marcar para coaching');
        });
    });

}(window.jQuery));
</script>
@endpush
@endonce
