{{-- Modal: Adjuntar contacto --}}
<div class="bv-modal" data-bv-modal-name="attach-contact">
    <div class="modal w-md">
        <div class="modal-head">
            <div class="modal-icon"><i class="fa-regular fa-id-card"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">{{ __('helpdesk::helpdesk.inbox.modals.attach_contact_label') }}</div>
                <div class="modal-title">{{ __('helpdesk::helpdesk.inbox.modals.attach_contact_title') }}</div>
            </div>
            <button class="modal-close" data-bv-close><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">

            <div class="minfo">
                <i class="fa-solid fa-circle-info"></i>
                <div>{{ __('helpdesk::helpdesk.inbox.modals.attach_contact_info') }}</div>
            </div>

            <div class="search-field">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input id="attach-contact-search" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.attach_contact_search_placeholder') }}" autocomplete="off">
            </div>

            <div class="bv-modal-list-label">{{ __('helpdesk::helpdesk.inbox.modals.attach_contact_list_label') }}</div>
            <div id="attach-contact-list">
                <div class="nc-empty-hint">
                    <i class="fas fa-magnifying-glass"></i>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.attach_contact_empty_hint') }}</span>
                </div>
            </div>

        </div>
        <div class="modal-foot">
            <button class="btn btn-primary" id="attach-contact-send" disabled>{{ __('helpdesk::helpdesk.inbox.modals.attach_contact_send') }}</button>
            <button class="btn btn-outline" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/attach-contact.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/attach-contact.js')) }}" defer></script>
@endpush
@endonce
