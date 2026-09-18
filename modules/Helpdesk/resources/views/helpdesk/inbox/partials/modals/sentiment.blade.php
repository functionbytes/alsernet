{{-- Modal: Sentimiento del cliente (#89 ve-sentiment) --}}
<div class="bv-modal" data-bv-modal-name="sentiment">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-face-smile"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.sentiment_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.sentiment_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div id="sentimentLoading" class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>

            <div id="sentimentContent" style="display:none">

                <div class="bv-sent-bar">
                    <i class="far fa-face-frown bv-sent-bar__icon-neg"></i>
                    <div class="bv-sent-bar__track">
                        <div class="bv-sent-bar__fill bv-x65" id="sentFill"></div>
                    </div>
                    <i class="far fa-face-smile bv-sent-bar__icon-pos"></i>
                    <span class="bv-sent-bar__score" id="sentScore"></span>
                </div>

                <p class="bv-sent-desc" id="sentDescription"></p>

                <div class="bv-form-label bv-x10">{{ __('helpdesk::helpdesk.inbox.modals.sentiment_timeline') }}</div>
                <div id="sentTimeline" class="bv-sent-timeline"></div>

            </div>

            <div id="sentimentError" class="bv-cv-loading-msg" style="display:none">
                <i class="fas fa-triangle-exclamation"></i>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-sentiment-coaching" disabled>{{ __('helpdesk::helpdesk.inbox.modals.sentiment_mark_coaching') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.order_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/sentiment.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/sentiment.js')) }}" defer></script>
@endpush
@endonce
