{{-- Modal: Detectar idioma — sugerencia IA (#90 ve-detect-lang) --}}
<div class="bv-modal" data-bv-modal-name="detect-lang">
    <div class="bv-modal-dialog sm">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-language"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.detect_lang_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.detect_lang_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <p class="bv-x22" id="dlDetectedText"></p>

            <div class="bv-quote-block bv-step-hidden" id="dlSampleQuote"></div>

            <div class="bv-ai-sparkle">
                <div class="bv-ai-sparkle__ic"><i class="fas fa-wand-magic-sparkles"></i></div>
                <div>
                    <div class="bv-ai-sparkle__lbl">{{ __('helpdesk::helpdesk.inbox.modals.detect_lang_suggestion_label') }}</div>
                    {{ __('helpdesk::helpdesk.inbox.modals.detect_lang_suggestion_text') }}
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-dl-activate">{{ __('helpdesk::helpdesk.inbox.modals.detect_lang_activate') }}</button>
            <button class="btn-secondary" id="bv-dl-incoming-only">{{ __('helpdesk::helpdesk.inbox.modals.detect_lang_incoming_only') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.detect_lang_decline') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/detect-lang.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/detect-lang.js')) }}" defer></script>
@endpush
@endonce
