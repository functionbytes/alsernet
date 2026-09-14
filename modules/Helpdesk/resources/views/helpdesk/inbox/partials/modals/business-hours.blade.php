{{-- Modal: Horario de atención (#77 ve-business-hours). Consume la misma tabla
     y rutas que /panel/settings/helpdesk/business/hours (ver BusinessHoursController). --}}
<div class="bv-modal" data-bv-modal-name="business-hours"
     data-bh-index-url="{{ route('settings.helpdesk.business.hours') }}"
     data-bh-update-url="{{ route('settings.helpdesk.business.hours.update.ajax') }}">
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

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.business_hours_field_timezone') }}</label>
                <select id="bhTimezone" class="bv-form-input"></select>
            </div>

            <div class="bv-bh-days" id="bhDaysList"></div>

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
    <script src="{{ asset('vendor/helpdesk/modals/business-hours.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/business-hours.js')) }}" defer></script>
@endpush
@endonce
