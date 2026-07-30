{{-- Modal: SLA por canal (#76 ve-sla-config) --}}
<div class="bv-modal" data-bv-modal-name="sla-config">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-clock"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.sla_config_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.sla_config_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div id="slaConfigLoading" class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>

            <div id="slaConfigContent" style="display:none">
                <div class="bv-sla-rows" id="slaConfigRows"></div>

                <label class="bv-check bv-x31">
                    <input type="checkbox" id="slaPauseOffHours" checked>
                    {{ __('helpdesk::helpdesk.inbox.modals.sla_config_pause_off_hours') }}
                </label>
                <label class="bv-check">
                    <input type="checkbox" id="slaNotifySupervisor" checked>
                    {{ __('helpdesk::helpdesk.inbox.modals.sla_config_notify_supervisor') }}
                </label>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-sla-save" disabled>{{ __('helpdesk::helpdesk.inbox.modals.status_save') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/sla-config.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/sla-config.js')) }}"></script>
@endpush
@endonce
