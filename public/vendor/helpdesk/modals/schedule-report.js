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

    var _srFreq = 'weekly';

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'schedule-report') { return; }
        _srFreq = 'weekly';
        $('#srFreqBar .bv-report-freq__btn').removeClass('on');
        $('#srFreqBar .bv-report-freq__btn[data-freq="weekly"]').addClass('on');
        $('#srType').val('agent_activity');
        $('#srRecipients').val('').focus();
        $('#srTime').val('09:00');
        $('#srFormat').val('pdf_link');
    });

    $(document).on('click', '#srFreqBar .bv-report-freq__btn', function () {
        $('#srFreqBar .bv-report-freq__btn').removeClass('on');
        $(this).addClass('on');
        _srFreq = $(this).data('freq');
        $('#srDayField').toggle(_srFreq === 'weekly' || _srFreq === 'monthly');
    });

    function getPayload() {
        return {
            report_type: $('#srType').val(),
            frequency:   _srFreq,
            day:         $('#srDay').val(),
            time:        $('#srTime').val(),
            recipients:  $('#srRecipients').val().trim(),
            format:      $('#srFormat').val(),
        };
    }

    $(document).on('click', '#bv-sr-save', function () {
        var data = getPayload();
        if (!data.recipients) {
            if (window.toastr) { toastr.warning('Indica al menos un destinatario'); }
            $('#srRecipients').focus();
            return;
        }
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando…');

        $.ajax({
            url: '/panel/helpdesk/reports/schedule',
            method: 'POST',
            data: data,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('schedule-report');
            if (window.toastr) { toastr.success('Reporte programado correctamente'); }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al programar reporte';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Programar reporte');
        });
    });

    $(document).on('click', '#bv-sr-test', function () {
        var data = getPayload();
        var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enviando…');

        $.ajax({
            url: '/panel/helpdesk/reports/test',
            method: 'POST',
            data: data,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            if (window.toastr) { toastr.success('Prueba enviada'); }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al enviar prueba';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false).text('Enviar prueba ahora');
        });
    });

}(window.jQuery));
