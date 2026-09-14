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
