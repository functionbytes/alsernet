{{-- Modal: Horario de atención (#77 ve-business-hours) --}}
<div class="bv-modal" data-bv-modal-name="business-hours">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-calendar"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_header_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-frow">
                <div class="bv-form-field">
                    <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_field_timezone') }}</label>
                    <select id="bhTimezone" class="bv-form-input">
                        <option value="Europe/Madrid">Europe/Madrid (UTC+1)</option>
                        <option value="America/Mexico_City">America/Mexico_City</option>
                        <option value="America/Bogota">America/Bogota</option>
                        <option value="America/Santiago">America/Santiago</option>
                        <option value="America/Buenos_Aires">America/Buenos_Aires</option>
                        <option value="UTC">UTC</option>
                    </select>
                </div>
                <div class="bv-form-field">
                    <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_field_apply_to') }}</label>
                    <select id="bhApplyTo" class="bv-form-input">
                        <option value="all">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_apply_all') }}</option>
                        <option value="whatsapp">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_apply_whatsapp') }}</option>
                        <option value="email">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_apply_email') }}</option>
                        <option value="webchat">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_apply_webchat') }}</option>
                    </select>
                </div>
            </div>

            <div class="bv-cal-grid" id="bhCalGrid">
                <div class="bv-cal-grid__h"></div>
                <div class="bv-cal-grid__h">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_day_mon') }}</div>
                <div class="bv-cal-grid__h">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_day_tue') }}</div>
                <div class="bv-cal-grid__h">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_day_wed') }}</div>
                <div class="bv-cal-grid__h">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_day_thu') }}</div>
                <div class="bv-cal-grid__h">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_day_fri') }}</div>
                <div class="bv-cal-grid__h">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_day_sat') }}</div>
                <div class="bv-cal-grid__h">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_day_sun') }}</div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_field_off_message') }}</label>
                <textarea id="bhOffMessage" class="bv-form-input" rows="2" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.business_hours_placeholder_off_message') }}"></textarea>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-bh-save">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_btn_save') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/business-hours.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/business-hours.js')) }}"></script>
@endpush
@endonce
