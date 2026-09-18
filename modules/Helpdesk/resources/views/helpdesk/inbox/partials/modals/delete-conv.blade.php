{{-- Modal: Confirmar eliminar conversación --}}
<div class="bv-modal" data-bv-modal-name="delete-conv">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box danger"><i class="fas fa-trash"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.confirmation') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.delete_conv_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            @include('helpdesk::helpdesk.inbox.partials.modals._context-card')

            <div class="bv-warn-box">
                <div class="bv-warn-box__body">
                    <strong class="bv-warn-box__title">{{ __('helpdesk::helpdesk.inbox.modals.delete_conv_confirm_question') }}</strong>
                    <span class="bv-warn-box__desc">{{ __('helpdesk::helpdesk.inbox.modals.delete_conv_confirm_desc') }}</span>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-brand-solid" id="bv-delete-conv-confirm">{{ __('helpdesk::helpdesk.inbox.modals.delete_conv_title') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/delete-conv.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/delete-conv.js')) }}" defer></script>
@endpush
@endonce
