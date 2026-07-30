{{-- Modal: Resumen IA de conversación (#87 ve-ai-summary) --}}
<div class="bv-modal" data-bv-modal-name="ai-summary">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-wand-magic-sparkles"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.ai_summary_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.ai_summary_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div id="aiSummaryLoading" class="bv-cv-loading-msg">
                <i class="fas fa-spinner fa-spin"></i>
            </div>

            <div id="aiSummaryContent" style="display:none">
                <div class="bv-ai-sparkle">
                    <div class="bv-ai-sparkle__ic"><i class="fas fa-wand-magic-sparkles"></i></div>
                    <div>
                        <div class="bv-ai-sparkle__lbl">{{ __('helpdesk::helpdesk.inbox.modals.ai_summary_generated_by') }}</div>
                        <span id="aiSummaryMeta">{{ __('helpdesk::helpdesk.inbox.modals.ai_summary_generated_meta') }}</span>
                    </div>
                </div>

                <div class="bv-ai-section">
                    <div class="bv-ai-section__label">{{ __('helpdesk::helpdesk.inbox.modals.ai_summary_problem') }}</div>
                    <p id="aiSummaryProblem" class="bv-ai-section__text"></p>
                </div>

                <div class="bv-ai-section">
                    <div class="bv-ai-section__label">{{ __('helpdesk::helpdesk.inbox.modals.ai_summary_actions') }}</div>
                    <ul id="aiSummaryActions" class="bv-ai-section__list"></ul>
                </div>

                <div class="bv-ai-section">
                    <div class="bv-ai-section__label">{{ __('helpdesk::helpdesk.inbox.modals.ai_summary_next_step') }}</div>
                    <p id="aiSummaryNext" class="bv-ai-section__text"></p>
                </div>
            </div>

            <div id="aiSummaryError" class="bv-cv-loading-msg" style="display:none">
                <i class="fas fa-triangle-exclamation"></i>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-ai-summary-paste" disabled>{{ __('helpdesk::helpdesk.inbox.modals.ai_summary_paste') }}</button>
            <button class="btn-secondary" id="bv-ai-summary-regen">{{ __('helpdesk::helpdesk.inbox.modals.ai_summary_regenerate') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.ai_summary_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/ai-summary.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/ai-summary.js')) }}"></script>
@endpush
@endonce
