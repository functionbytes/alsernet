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

    var _incidentSeverity = 'medium';

    function closeBvModal(name) {
        $('[data-bv-modal-name="' + name + '"]').removeClass('on');
        if ($('.bv-modal.on').length === 0) { $('body').css('overflow', ''); }
    }

    $(document).on('bv:modal:open', function (e, name) {
        if (name !== 'report-incident') { return; }
        _incidentSeverity = 'medium';
        $('#incidentSubject').val('').focus();
        $('#incidentSteps').val('');
        $('#incidentCategory').val('platform_bug');
        $('#incidentAttachLog').prop('checked', true);
        $('#incidentAttachScreenshot').prop('checked', false);
        $('#incidentSeverityBar .bv-prio-card').removeClass('on');
        $('#incidentSeverityBar .bv-prio-card[data-severity="medium"]').addClass('on');
    });

    $(document).on('click', '#incidentSeverityBar .bv-prio-card', function () {
        $('#incidentSeverityBar .bv-prio-card').removeClass('on');
        $(this).addClass('on');
        _incidentSeverity = $(this).data('severity');
    });

    function submitIncident(isDraft) {
        var subject = $('#incidentSubject').val().trim();
        var steps   = $('#incidentSteps').val().trim();
        if (!subject || !steps) {
            if (window.toastr) { toastr.warning('El asunto y los pasos son obligatorios'); }
            return;
        }
        var $btn = isDraft ? $('#bv-incident-draft') : $('#bv-incident-submit');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Enviando…');

        $.ajax({
            url: '/panel/helpdesk/incidents',
            method: 'POST',
            data: {
                severity:          _incidentSeverity,
                subject:           subject,
                category:          $('#incidentCategory').val(),
                steps:             steps,
                attach_log:        $('#incidentAttachLog').is(':checked') ? 1 : 0,
                attach_screenshot: $('#incidentAttachScreenshot').is(':checked') ? 1 : 0,
                draft:             isDraft ? 1 : 0,
                conversation_id:   $('.bv-composer').data('bv-conversation-id') || null,
            },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' }
        }).done(function () {
            closeBvModal('report-incident');
            if (window.toastr) { toastr.success(isDraft ? 'Borrador guardado' : 'Incidente enviado a soporte L3'); }
        }).fail(function (xhr) {
            var msg = xhr?.responseJSON?.message || 'Error al enviar el incidente';
            if (window.toastr) { toastr.error(msg); }
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }

    $(document).on('click', '#bv-incident-submit', function () { submitIncident(false); });
    $(document).on('click', '#bv-incident-draft',  function () { submitIncident(true); });

}(window.jQuery));
