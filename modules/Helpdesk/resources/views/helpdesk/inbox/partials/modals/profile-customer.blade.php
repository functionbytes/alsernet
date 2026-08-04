{{-- Modal: Perfil del cliente (#09 ve-profile-customer) --}}
<div class="bv-modal" data-bv-modal-name="profile-customer">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-user"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.profile_customer_label') }}</span>
                <div class="bv-modal-title"><span>{{ __('helpdesk::helpdesk.inbox.modals.profile_customer_title') }}</span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body" id="cpBody">
            <div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> {{ __('helpdesk::helpdesk.inbox.modals.profile_customer_loading') }}</div>
        </div>

        <div class="bv-modal-foot">
            <button class="btn-primary" id="cpStartConversation" type="button">{{ __('helpdesk::helpdesk.inbox.modals.profile_customer_start_conversation') }}</button>
            <button class="btn-secondary" id="cpFullHistory" type="button">{{ __('helpdesk::helpdesk.inbox.modals.profile_customer_full_history') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.profile_customer_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/profile-customer.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/profile-customer.js')) }}" defer></script>
@endpush
@endonce
