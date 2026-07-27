{{-- Modal: Actividad de la conversación (audit log) --}}
<div class="bv-modal" data-bv-modal-name="audit-log">
    <div class="bv-modal-dialog lg bv-modal-dialog--audit">
        <div class="bv-modal-head">
            <div class="bv-modal-icon-box"><i class="fas fa-clock-rotate-left"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.audit_log_label') }}</span>
                <div class="bv-modal-title">
                    {{ __('helpdesk::helpdesk.inbox.modals.audit_log_title') }}
                    <span class="bv-audit-chip" id="alConvChip"></span>
                </div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body">
            {{-- Loading --}}
            <div class="bv-al-loading" id="alLoading">
                <i class="fas fa-spinner fa-spin"></i> {{ __('helpdesk::helpdesk.inbox.modals.audit_log_loading') }}
            </div>

            {{-- Filter pills --}}
            <div class="bv-al-pills bv-hidden" id="alPills">
                <span class="media-pill on" data-al-cat="all">{{ __('helpdesk::helpdesk.inbox.modals.audit_log_filter_all') }} <span class="c" id="alCntAll">0</span></span>
                <span class="media-pill" data-al-cat="assign">{{ __('helpdesk::helpdesk.inbox.modals.audit_log_filter_assign') }} <span class="c" id="alCntAssign">0</span></span>
                <span class="media-pill" data-al-cat="state">{{ __('helpdesk::helpdesk.inbox.modals.audit_log_filter_state') }} <span class="c" id="alCntState">0</span></span>
                <span class="media-pill" data-al-cat="message">{{ __('helpdesk::helpdesk.inbox.modals.audit_log_filter_message') }} <span class="c" id="alCntMessage">0</span></span>
                <span class="media-pill" data-al-cat="tag">{{ __('helpdesk::helpdesk.inbox.modals.audit_log_filter_tag') }} <span class="c" id="alCntTag">0</span></span>
                <span class="media-pill" data-al-cat="update">{{ __('helpdesk::helpdesk.inbox.modals.audit_log_filter_update') }} <span class="c" id="alCntUpdate">0</span></span>
            </div>

            {{-- Audit rows --}}
            <div class="bv-al-list bv-hidden" id="alList"></div>
        </div>

        <div class="bv-modal-foot">
            <button class="btn-primary" id="alBtnExport"><i class="fas fa-file-arrow-down"></i> {{ __('helpdesk::helpdesk.inbox.modals.audit_log_export') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.audit_log_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/audit-log.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/audit-log.js')) }}"></script>
@endpush
@endonce
