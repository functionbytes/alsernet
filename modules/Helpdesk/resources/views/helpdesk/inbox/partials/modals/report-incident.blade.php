{{-- Modal: Reportar incidente técnico (#73 ve-report-incident) --}}
<div class="bv-modal" data-bv-modal-name="report-incident">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box bv-modal-icon-box--warning"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">SOPORTE · ESCALAMIENTO L3</span>
                <div class="bv-modal-title">Reportar incidente</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">Severidad <span class="bv-req">*</span></label>
                <div class="bv-prio-bar" id="incidentSeverityBar">
                    <button type="button" class="bv-prio-card" data-severity="low">
                        <div class="bv-prio-card__ic"><i class="fas fa-chevron-down"></i></div>
                        <span class="bv-prio-card__lbl">Baja</span>
                    </button>
                    <button type="button" class="bv-prio-card on" data-severity="medium">
                        <div class="bv-prio-card__ic"><i class="fas fa-minus"></i></div>
                        <span class="bv-prio-card__lbl">Media</span>
                    </button>
                    <button type="button" class="bv-prio-card" data-severity="high">
                        <div class="bv-prio-card__ic"><i class="fas fa-chevron-up"></i></div>
                        <span class="bv-prio-card__lbl">Alta</span>
                    </button>
                    <button type="button" class="bv-prio-card" data-severity="critical">
                        <div class="bv-prio-card__ic"><i class="fas fa-fire"></i></div>
                        <span class="bv-prio-card__lbl">Crítica</span>
                    </button>
                </div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">Asunto <span class="bv-req">*</span></label>
                <input id="incidentSubject" type="text" class="bv-form-input" placeholder="Resumen del incidente" autocomplete="off">
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">Categoría</label>
                <select id="incidentCategory" class="bv-form-input">
                    <option value="platform_bug">Bug en la plataforma</option>
                    <option value="service_down">Caída de servicio</option>
                    <option value="data_issue">Datos inconsistentes</option>
                    <option value="permissions">Permisos / seguridad</option>
                    <option value="integration">Integración rota</option>
                </select>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">Pasos para reproducir <span class="bv-req">*</span></label>
                <textarea id="incidentSteps" class="bv-form-input" rows="3" placeholder="1. Abrir conversación X&#10;2. Hacer click en…&#10;3. Resultado esperado vs real"></textarea>
            </div>

            <label class="bv-check">
                <input type="checkbox" id="incidentAttachLog" checked>
                Adjuntar log de la sesión actual
            </label>
            <label class="bv-check">
                <input type="checkbox" id="incidentAttachScreenshot">
                Adjuntar captura de pantalla
            </label>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-incident-submit">Enviar a soporte L3</button>
            <button class="btn-secondary" id="bv-incident-draft">Guardar borrador</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
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
</script>
@endpush
@endonce
