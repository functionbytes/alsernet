{{-- Modal: Programar reporte recurrente (#81 ve-schedule-report) --}}
<div class="bv-modal" data-bv-modal-name="schedule-report">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-chart-line"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_field_type') }}</label>
                <select id="srType" class="bv-form-input">
                    <option value="agent_activity">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_type_agent_activity') }}</option>
                    <option value="sla_compliance">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_type_sla_compliance') }}</option>
                    <option value="csat_weekly">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_type_csat_weekly') }}</option>
                    <option value="tickets_resolved">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_type_tickets_resolved') }}</option>
                </select>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_field_frequency') }}</label>
                <div class="bv-report-freq" id="srFreqBar">
                    <button type="button" class="bv-report-freq__btn" data-freq="daily">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_freq_daily') }}</button>
                    <button type="button" class="bv-report-freq__btn on" data-freq="weekly">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_freq_weekly') }}</button>
                    <button type="button" class="bv-report-freq__btn" data-freq="monthly">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_freq_monthly') }}</button>
                    <button type="button" class="bv-report-freq__btn" data-freq="quarterly">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_freq_quarterly') }}</button>
                </div>
            </div>

            <div class="bv-frow">
                <div class="bv-form-field" id="srDayField">
                    <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_field_day') }}</label>
                    <select id="srDay" class="bv-form-input">
                        <option value="1">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_day_monday') }}</option>
                        <option value="2">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_day_tuesday') }}</option>
                        <option value="3">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_day_wednesday') }}</option>
                        <option value="4">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_day_thursday') }}</option>
                        <option value="5">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_day_friday') }}</option>
                        <option value="6">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_day_saturday') }}</option>
                        <option value="0">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_day_sunday') }}</option>
                    </select>
                </div>
                <div class="bv-form-field">
                    <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_field_time') }}</label>
                    <input id="srTime" type="time" class="bv-form-input" value="09:00">
                </div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_field_recipients') }} <span class="bv-form-hint">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_recipients_hint') }}</span></label>
                <input id="srRecipients" type="text" class="bv-form-input" placeholder="correo@empresa.com, otro@empresa.com">
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_field_format') }}</label>
                <select id="srFormat" class="bv-form-input">
                    <option value="pdf_link">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_format_pdf_link') }}</option>
                    <option value="csv">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_format_csv') }}</option>
                    <option value="xlsx">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_format_xlsx') }}</option>
                </select>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-sr-save">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_submit') }}</button>
            <button class="btn-secondary" id="bv-sr-test">{{ __('helpdesk::helpdesk.inbox.modals.schedule_report_test') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
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
