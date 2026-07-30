{{-- Modal: Nueva conversación · wizard 2 pasos (#31 ve-new-conv-wizard) --}}
<div class="bv-modal" data-bv-modal-name="new-conv-wizard">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-comment-dots"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.new_conv_wizard_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.newconv_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Stepper --}}
            <div class="bv-wiz-steps">
                <div class="bv-wiz-step on" id="ncwStep1Dot"><span class="bv-wiz-step__num">1</span><span class="bv-wiz-step__lbl">{{ __('helpdesk::helpdesk.inbox.modals.newconv_step_channel') }}</span></div>
                <div class="bv-wiz-line" id="ncwLine"></div>
                <div class="bv-wiz-step" id="ncwStep2Dot"><span class="bv-wiz-step__num">2</span><span class="bv-wiz-step__lbl">{{ __('helpdesk::helpdesk.inbox.modals.newconv_step_recipient') }}</span></div>
            </div>

            {{-- Paso 1: Canal --}}
            <div id="ncwStep1">
                <div class="bv-form-label bv-x39">{{ __('helpdesk::helpdesk.inbox.modals.newconv_choose_channel') }}</div>
                <div class="bv-ch-pick-grid" id="ncwChannelGrid">
                    <button type="button" class="bv-ch-pick on" data-channel="whatsapp">
                        <div class="bv-ch-pick__ic"><i class="fab fa-whatsapp"></i></div>
                        <div class="bv-ch-pick__body">
                            <span class="bv-ch-pick__nm">WhatsApp</span>
                            <span class="bv-ch-pick__ds">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_whatsapp_desc') }}</span>
                        </div>
                    </button>
                    <button type="button" class="bv-ch-pick" data-channel="messenger">
                        <div class="bv-ch-pick__ic"><i class="fab fa-facebook-messenger"></i></div>
                        <div class="bv-ch-pick__body">
                            <span class="bv-ch-pick__nm">Messenger</span>
                            <span class="bv-ch-pick__ds">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_messenger_desc') }}</span>
                        </div>
                    </button>
                    <button type="button" class="bv-ch-pick" data-channel="instagram">
                        <div class="bv-ch-pick__ic"><i class="fab fa-instagram"></i></div>
                        <div class="bv-ch-pick__body">
                            <span class="bv-ch-pick__nm">Instagram</span>
                            <span class="bv-ch-pick__ds">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_instagram_desc') }}</span>
                        </div>
                    </button>
                    <button type="button" class="bv-ch-pick" data-channel="webchat">
                        <div class="bv-ch-pick__ic"><i class="far fa-comment"></i></div>
                        <div class="bv-ch-pick__body">
                            <span class="bv-ch-pick__nm">{{ __('helpdesk::helpdesk.inbox.modals.filter_channel_web') }}</span>
                            <span class="bv-ch-pick__ds">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_web_desc') }}</span>
                        </div>
                    </button>
                    <button type="button" class="bv-ch-pick" data-channel="email">
                        <div class="bv-ch-pick__ic"><i class="far fa-envelope"></i></div>
                        <div class="bv-ch-pick__body">
                            <span class="bv-ch-pick__nm">Email</span>
                            <span class="bv-ch-pick__ds">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_email_desc') }}</span>
                        </div>
                    </button>
                    <button type="button" class="bv-ch-pick" data-channel="sms">
                        <div class="bv-ch-pick__ic"><i class="fas fa-mobile-screen-button"></i></div>
                        <div class="bv-ch-pick__body">
                            <span class="bv-ch-pick__nm">SMS</span>
                            <span class="bv-ch-pick__ds">{{ __('helpdesk::helpdesk.inbox.modals.new_conv_wizard_sms_desc') }}</span>
                        </div>
                    </button>
                </div>

                <div class="bv-warn-callout" id="ncwChannelWarning" style="display:none">
                    <i class="fas fa-triangle-exclamation"></i>
                    <div id="ncwChannelWarningText"></div>
                </div>
            </div>

            {{-- Paso 2: Destinatario --}}
            <div id="ncwStep2" style="display:none">
                <div class="bv-info-table">
                    <div class="bv-info-table__row">
                        <span class="bv-info-table__k">{{ __('helpdesk::helpdesk.inbox.modals.context_card_channel') }}</span>
                        <span class="bv-info-table__v" id="ncwSelectedChannel"></span>
                    </div>
                </div>

                <div class="bv-form-field">
                    <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.new_conv_wizard_search_contact') }} <span class="bv-req">*</span></label>
                    <div class="bv-modal-search">
                        <i class="fas fa-magnifying-glass"></i>
                        <input id="ncwContactSearch" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.newconv_contact_search_placeholder') }}" autocomplete="off">
                    </div>
                    <div class="bv-x40" id="ncwContactResults"></div>
                </div>

                <div id="ncwHsmSection" style="display:none">
                    <div class="bv-form-field">
                        <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_template_label') }} <span class="bv-req">*</span> <span class="bv-form-hint">{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_required_hint') }}</span></label>
                        <select id="ncwHsmTemplate" class="bv-form-input">
                            <option value="">{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_no_template') }}</option>
                        </select>
                    </div>
                    <div class="bv-form-field" id="ncwHsmPreview" style="display:none">
                        <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_preview_label') }}</label>
                        <div class="bv-x41" id="ncwHsmPreviewText"></div>
                    </div>
                </div>

                <label class="bv-check">
                    <input type="checkbox" id="ncwAssignSelf" checked>
                    {{ __('helpdesk::helpdesk.inbox.modals.new_conv_wizard_assign_self') }}
                </label>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-ncw-next">{{ __('helpdesk::helpdesk.inbox.modals.newconv_continue') }}</button>
            <button class="btn-secondary" id="bv-ncw-back" style="display:none">{{ __('helpdesk::helpdesk.inbox.modals.newconv_back') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/new-conv-wizard.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/new-conv-wizard.js')) }}"></script>
@endpush
@endonce
