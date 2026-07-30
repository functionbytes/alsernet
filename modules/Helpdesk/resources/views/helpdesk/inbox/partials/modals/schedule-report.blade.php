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
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/schedule-report.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/schedule-report.js')) }}"></script>
@endpush
@endonce
