{{-- Modal: Programar reporte recurrente (#81 ve-schedule-report) --}}
<div class="bv-modal" data-bv-modal-name="schedule-report">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-chart-line"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">REPORTES · RECURRENTE</span>
                <div class="bv-modal-title">Programar reporte</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">Tipo de reporte</label>
                <select id="srType" class="bv-form-input">
                    <option value="agent_activity">Resumen de actividad de agentes</option>
                    <option value="sla_compliance">SLA · cumplimiento por canal</option>
                    <option value="csat_weekly">CSAT semanal</option>
                    <option value="tickets_resolved">Tickets resueltos vs creados</option>
                </select>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">Frecuencia</label>
                <div class="bv-report-freq" id="srFreqBar">
                    <button type="button" class="bv-report-freq__btn" data-freq="daily">Diario</button>
                    <button type="button" class="bv-report-freq__btn on" data-freq="weekly">Semanal</button>
                    <button type="button" class="bv-report-freq__btn" data-freq="monthly">Mensual</button>
                    <button type="button" class="bv-report-freq__btn" data-freq="quarterly">Trimestral</button>
                </div>
            </div>

            <div class="bv-frow">
                <div class="bv-form-field" id="srDayField">
                    <label class="bv-form-label">Día</label>
                    <select id="srDay" class="bv-form-input">
                        <option value="1">Lunes</option>
                        <option value="2">Martes</option>
                        <option value="3">Miércoles</option>
                        <option value="4">Jueves</option>
                        <option value="5">Viernes</option>
                        <option value="6">Sábado</option>
                        <option value="0">Domingo</option>
                    </select>
                </div>
                <div class="bv-form-field">
                    <label class="bv-form-label">Hora</label>
                    <input id="srTime" type="time" class="bv-form-input" value="09:00">
                </div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">Enviar a <span class="bv-form-hint">separados por coma</span></label>
                <input id="srRecipients" type="text" class="bv-form-input" placeholder="correo@empresa.com, otro@empresa.com">
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">Formato</label>
                <select id="srFormat" class="bv-form-input">
                    <option value="pdf_link">PDF + enlace al dashboard</option>
                    <option value="csv">CSV adjunto</option>
                    <option value="xlsx">Excel adjunto</option>
                </select>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-sr-save">Programar reporte</button>
            <button class="btn-secondary" id="bv-sr-test">Enviar prueba ahora</button>
            <button class="btn-secondary" data-bv-close>Cancelar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
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
</script>
@endpush
@endonce
