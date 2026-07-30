{{-- Modal: Sugerencias IA de respuesta (#88 ve-ai-suggest) --}}
<div class="bv-modal" data-bv-modal-name="ai-suggest">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-wand-magic-sparkles"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.ai_suggest_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.ai_suggest_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div id="aiSuggestLoading" class="bv-cv-loading-msg">
                <i class="fas fa-spinner fa-spin"></i>
            </div>

            <div id="aiSuggestContent" style="display:none">
                <div class="bv-quote-block" id="aiSuggestOriginalMsg"></div>

                <div class="bv-form-label bv-x10" id="aiSuggestCountLabel"></div>

                <div class="bv-ai-sug" id="aiSuggestList"></div>

                <div class="bv-ai-sparkle">
                    <div class="bv-ai-sparkle__ic"><i class="fas fa-circle-info"></i></div>
                    <div>
                        <div class="bv-ai-sparkle__lbl">{{ __('helpdesk::helpdesk.inbox.modals.ai_suggest_context_label') }}</div>
                        {{ __('helpdesk::helpdesk.inbox.modals.ai_suggest_context_text') }}
                    </div>
                </div>
            </div>

            <div id="aiSuggestError" class="bv-cv-loading-msg" style="display:none">
                <i class="fas fa-triangle-exclamation"></i>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-ai-suggest-insert" disabled>{{ __('helpdesk::helpdesk.inbox.modals.ai_suggest_insert') }}</button>
            <button class="btn-secondary" id="bv-ai-suggest-regen">{{ __('helpdesk::helpdesk.inbox.modals.ai_suggest_regenerate') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/ai-suggest.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/ai-suggest.js')) }}"></script>
@endpush
@endonce
