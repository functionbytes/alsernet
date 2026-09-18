{{-- Modal: Aplicar macro (#61 ve-apply-macro · M) --}}
<div class="bv-modal" data-bv-modal-name="macro">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-wand-magic-sparkles"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.macro_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.macro_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close aria-label="{{ __('helpdesk::helpdesk.inbox.modals.macro_close_aria') }}"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-modal-search">
                <i class="fas fa-magnifying-glass"></i>
                <input id="macroSearch" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.macro_search_placeholder') }}" autocomplete="off">
            </div>

            <div class="field">
                <label class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.macro_field_available') }}</label>
                <div id="macroList" class="reason-list bv-macro-list">
                    <div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
                </div>
            </div>

            {{-- Aviso de la macro seleccionada --}}
            <div class="minfo bv-hidden" id="macroInfo">
                <i class="fas fa-circle-info"></i>
                <div id="macroInfoText"></div>
            </div>

            {{-- Detalle de acciones (se muestra con "Ver acciones detalladas") --}}
            <div id="macroPreview" class="field bv-hidden">
                <label class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.macro_field_actions_preview') }}</label>
                <div id="macroPreviewActions" class="bv-macro-actions"></div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-macro-apply" disabled>{{ __('helpdesk::helpdesk.inbox.modals.macro_apply') }}</button>
            <button class="btn-secondary bv-hidden" id="bv-macro-details">{{ __('helpdesk::helpdesk.inbox.modals.macro_view_details') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/macro.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/macro.js')) }}" defer></script>
@endpush
@endonce
