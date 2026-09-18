{{-- Modal: Reportar bug o sugerencia (#85 ve-feedback) --}}
<div class="bv-modal" data-bv-modal-name="feedback">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-bug"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.feedback_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.feedback_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.feedback_field_type') }}</label>
                <div class="bv-prio-bar" id="feedbackTypeBar">
                    <button type="button" class="bv-prio-card on" data-fb-type="bug">
                        <div class="bv-prio-card__ic"><i class="fas fa-bug"></i></div>
                        <span class="bv-prio-card__lbl">{{ __('helpdesk::helpdesk.inbox.modals.feedback_type_bug') }}</span>
                    </button>
                    <button type="button" class="bv-prio-card" data-fb-type="idea">
                        <div class="bv-prio-card__ic"><i class="fas fa-lightbulb"></i></div>
                        <span class="bv-prio-card__lbl">{{ __('helpdesk::helpdesk.inbox.modals.feedback_type_idea') }}</span>
                    </button>
                    <button type="button" class="bv-prio-card" data-fb-type="praise">
                        <div class="bv-prio-card__ic"><i class="far fa-thumbs-up"></i></div>
                        <span class="bv-prio-card__lbl">{{ __('helpdesk::helpdesk.inbox.modals.feedback_type_praise') }}</span>
                    </button>
                </div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.feedback_field_description') }} <span class="bv-req">*</span></label>
                <textarea id="feedbackDescription" class="bv-form-input" rows="4" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.feedback_description_placeholder') }}"></textarea>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.feedback_field_attach') }}</label>
                <div class="bv-dropzone-mini" id="feedbackDropzone">
                    <i class="far fa-image"></i> {{ __('helpdesk::helpdesk.inbox.modals.feedback_attach_hint') }}
                    <input type="file" id="feedbackFile" accept="image/*" style="display:none">
                </div>
                <div id="feedbackFilePreview" style="display:none;font-size:11.5px;margin-top:5px">
                    <i class="fas fa-image me-1"></i><span id="feedbackFileName"></span>
                    <button class="bv-x27" type="button" id="feedbackFileRemove"><i class="fas fa-xmark"></i></button>
                </div>
            </div>

            <label class="bv-check">
                <input type="checkbox" id="feedbackIncludeTechInfo" checked>
                {{ __('helpdesk::helpdesk.inbox.modals.feedback_include_tech_info') }}
            </label>
            <label class="bv-check">
                <input type="checkbox" id="feedbackContactMe">
                {{ __('helpdesk::helpdesk.inbox.modals.feedback_contact_me') }}
            </label>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-feedback-submit">{{ __('helpdesk::helpdesk.inbox.modals.feedback_submit') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/feedback.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/feedback.js')) }}" defer></script>
@endpush
@endonce
