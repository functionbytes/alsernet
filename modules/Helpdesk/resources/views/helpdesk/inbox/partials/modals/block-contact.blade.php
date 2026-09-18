{{-- Modal: Confirmar bloqueo de contacto --}}
<div class="bv-modal" data-bv-modal-name="block-contact">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box danger"><i class="fas fa-ban"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.block_contact_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.block_contact_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            @include('helpdesk::helpdesk.inbox.partials.modals._context-card')

            <div class="bv-warn-box">
                <div class="bv-warn-box__body">
                    <strong class="bv-warn-box__title">{{ __('helpdesk::helpdesk.inbox.modals.block_contact_warning_title') }}</strong>
                    <span class="bv-warn-box__desc">{{ __('helpdesk::helpdesk.inbox.modals.block_contact_warning_desc') }}</span>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-brand-solid" id="bv-block-contact-confirm">{{ __('helpdesk::helpdesk.inbox.modals.block_contact_confirm') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/block-contact.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/block-contact.js')) }}" defer></script>
@endpush
@endonce
