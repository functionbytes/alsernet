{{-- Modal: Reportar incidente técnico (#73 ve-report-incident) --}}
<div class="bv-modal" data-bv-modal-name="report-incident">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box bv-modal-icon-box--warning"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.report_incident_header_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.report_incident_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.report_incident_field_severity') }} <span class="bv-req">*</span></label>
                <div class="bv-prio-bar" id="incidentSeverityBar">
                    <button type="button" class="bv-prio-card" data-severity="low">
                        <div class="bv-prio-card__ic"><i class="fas fa-chevron-down"></i></div>
                        <span class="bv-prio-card__lbl">{{ __('helpdesk::helpdesk.inbox.modals.report_incident_severity_low') }}</span>
                    </button>
                    <button type="button" class="bv-prio-card on" data-severity="medium">
                        <div class="bv-prio-card__ic"><i class="fas fa-minus"></i></div>
                        <span class="bv-prio-card__lbl">{{ __('helpdesk::helpdesk.inbox.modals.report_incident_severity_medium') }}</span>
                    </button>
                    <button type="button" class="bv-prio-card" data-severity="high">
                        <div class="bv-prio-card__ic"><i class="fas fa-chevron-up"></i></div>
                        <span class="bv-prio-card__lbl">{{ __('helpdesk::helpdesk.inbox.modals.report_incident_severity_high') }}</span>
                    </button>
                    <button type="button" class="bv-prio-card" data-severity="critical">
                        <div class="bv-prio-card__ic"><i class="fas fa-fire"></i></div>
                        <span class="bv-prio-card__lbl">{{ __('helpdesk::helpdesk.inbox.modals.report_incident_severity_critical') }}</span>
                    </button>
                </div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.report_incident_field_subject') }} <span class="bv-req">*</span></label>
                <input id="incidentSubject" type="text" class="bv-form-input" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.report_incident_placeholder_subject') }}" autocomplete="off">
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.report_incident_field_category') }}</label>
                <select id="incidentCategory" class="bv-form-input">
                    <option value="platform_bug">{{ __('helpdesk::helpdesk.inbox.modals.report_incident_category_platform_bug') }}</option>
                    <option value="service_down">{{ __('helpdesk::helpdesk.inbox.modals.report_incident_category_service_down') }}</option>
                    <option value="data_issue">{{ __('helpdesk::helpdesk.inbox.modals.report_incident_category_data_issue') }}</option>
                    <option value="permissions">{{ __('helpdesk::helpdesk.inbox.modals.report_incident_category_permissions') }}</option>
                    <option value="integration">{{ __('helpdesk::helpdesk.inbox.modals.report_incident_category_integration') }}</option>
                </select>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.report_incident_field_steps') }} <span class="bv-req">*</span></label>
                <textarea id="incidentSteps" class="bv-form-input" rows="3" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.report_incident_placeholder_steps') }}"></textarea>
            </div>

            <label class="bv-check">
                <input type="checkbox" id="incidentAttachLog" checked>
                {{ __('helpdesk::helpdesk.inbox.modals.report_incident_check_attach_log') }}
            </label>
            <label class="bv-check">
                <input type="checkbox" id="incidentAttachScreenshot">
                {{ __('helpdesk::helpdesk.inbox.modals.report_incident_check_attach_screenshot') }}
            </label>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-incident-submit">{{ __('helpdesk::helpdesk.inbox.modals.report_incident_btn_submit') }}</button>
            <button class="btn-secondary" id="bv-incident-draft">{{ __('helpdesk::helpdesk.inbox.modals.report_incident_btn_draft') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/report-incident.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/report-incident.js')) }}" defer></script>
@endpush
@endonce
