{{-- Modal: Transferir conversación (#02 ve-transfer) --}}
<div class="bv-modal" data-bv-modal-name="transfer">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-right-left"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.transfer_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.transfer_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Agente actual --}}
            <div class="bv-tf-current" id="tfCurrentAgentBanner" style="display:none">
                <div class="bv-tf-current__inner">
                    <span class="bv-tf-current__label">{{ __('helpdesk::helpdesk.inbox.modals.transfer_currently_assigned') }}</span>
                    <span class="bv-tf-current__name" id="tfCurrentAgentName"></span>
                    <i class="fas fa-arrow-right bv-tf-current__arrow"></i>
                </div>
            </div>

            {{-- Búsqueda --}}
            <div class="bv-modal-search">
                <i class="fas fa-magnifying-glass"></i>
                <input id="transferSearch" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.transfer_search_placeholder') }}" autocomplete="off">
            </div>

            {{-- Lista de agentes --}}
            <div id="transferList" class="asgn-list">
                <div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
            </div>
        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-transfer-confirm" disabled>{{ __('helpdesk::helpdesk.inbox.modals.transfer_confirm') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/transfer.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/transfer.js')) }}"></script>
@endpush
@endonce
