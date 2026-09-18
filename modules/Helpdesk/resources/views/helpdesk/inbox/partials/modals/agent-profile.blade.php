{{-- Modal: Perfil de agente (#10 ve-profile-agent) --}}
<div class="bv-modal" data-bv-modal-name="agent-profile">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-headset"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.agent_profile_label') }}</span>
                <div class="bv-modal-title"><span id="apTitle">{{ __('helpdesk::helpdesk.inbox.modals.agent_profile_title') }}</span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body" id="apBody">
            <div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> {{ __('helpdesk::helpdesk.inbox.modals.agent_profile_loading') }}</div>
        </div>

        <div class="bv-modal-foot">
            <button class="btn-primary" id="apTransfer" type="button">{{ __('helpdesk::helpdesk.inbox.modals.agent_profile_transfer') }}</button>
            <button class="btn-secondary" id="apMessage" type="button">{{ __('helpdesk::helpdesk.inbox.modals.agent_profile_send_internal_message') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.agent_profile_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/agent-profile.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/agent-profile.js')) }}" defer></script>
@endpush
@endonce
