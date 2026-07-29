{{-- Modal: Nueva conversación (wizard 2 pasos) — diseño de referencia #31 "ve-new-conv-wizard" --}}
<div class="bv-modal" data-bv-modal-name="newconv">
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

            {{-- Step indicator --}}
            <div class="bv-wiz-steps">
                <div class="bv-wiz-step on" id="ncStep1Dot"><span class="bv-wiz-step__num">1</span><span class="bv-wiz-step__lbl">{{ __('helpdesk::helpdesk.inbox.modals.newconv_step_channel') }}</span></div>
                <div class="bv-wiz-line" id="ncLine"></div>
                <div class="bv-wiz-step" id="ncStep2Dot"><span class="bv-wiz-step__num">2</span><span class="bv-wiz-step__lbl">{{ __('helpdesk::helpdesk.inbox.modals.newconv_step_recipient') }}</span></div>
            </div>

            {{-- Step 1: Canal --}}
            <div id="ncStep1">
                <div class="bv-form-label bv-x39">{{ __('helpdesk::helpdesk.inbox.modals.newconv_choose_channel') }}</div>
                <div class="bv-ch-pick-grid" id="ncChannelGrid">
                    <button type="button" class="bv-ch-pick on" data-channel="whatsapp">
                        <div class="bv-ch-pick__ic"><i class="fab fa-whatsapp"></i></div>
                        <div class="bv-ch-pick__body">
                            <span class="bv-ch-pick__nm">WhatsApp</span>
                            <span class="bv-ch-pick__ds">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_whatsapp_desc') }}</span>
                        </div>
                    </button>
                    <button type="button" class="bv-ch-pick disabled" title="{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_not_active') }}">
                        <div class="bv-ch-pick__ic"><i class="fab fa-facebook-messenger"></i></div>
                        <div class="bv-ch-pick__body">
                            <span class="bv-ch-pick__nm">Messenger</span>
                            <span class="bv-ch-pick__ds">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_not_active') }}</span>
                        </div>
                    </button>
                    <button type="button" class="bv-ch-pick disabled" title="{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_not_active') }}">
                        <div class="bv-ch-pick__ic"><i class="fab fa-instagram"></i></div>
                        <div class="bv-ch-pick__body">
                            <span class="bv-ch-pick__nm">Instagram</span>
                            <span class="bv-ch-pick__ds">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_not_active') }}</span>
                        </div>
                    </button>
                    <button type="button" class="bv-ch-pick disabled" title="{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_not_active') }}">
                        <div class="bv-ch-pick__ic"><i class="far fa-comment"></i></div>
                        <div class="bv-ch-pick__body">
                            <span class="bv-ch-pick__nm">{{ __('helpdesk::helpdesk.inbox.modals.filter_channel_web') }}</span>
                            <span class="bv-ch-pick__ds">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_not_active') }}</span>
                        </div>
                    </button>
                    <button type="button" class="bv-ch-pick disabled" title="{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_not_active') }}">
                        <div class="bv-ch-pick__ic"><i class="far fa-envelope"></i></div>
                        <div class="bv-ch-pick__body">
                            <span class="bv-ch-pick__nm">Email</span>
                            <span class="bv-ch-pick__ds">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_not_active') }}</span>
                        </div>
                    </button>
                    <button type="button" class="bv-ch-pick disabled" title="{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_not_active') }}">
                        <div class="bv-ch-pick__ic"><i class="fas fa-mobile-screen"></i></div>
                        <div class="bv-ch-pick__body">
                            <span class="bv-ch-pick__nm">SMS</span>
                            <span class="bv-ch-pick__ds">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_not_active') }}</span>
                        </div>
                    </button>
                </div>

                <div class="bv-warn-callout" id="ncHsmWarning">
                    <i class="fas fa-triangle-exclamation"></i>
                    <div>{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_warning_pre') }} <b>{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_warning_bold') }}</b> {{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_warning_post') }}</div>
                </div>
            </div>

            {{-- Step 2: Destinatario --}}
            <div id="ncStep2" class="bv-step-hidden">

                {{-- Resumen del canal elegido en el paso 1 --}}
                <div class="bv-info-table">
                    <div class="bv-info-table__row">
                        <span class="bv-info-table__k">{{ __('helpdesk::helpdesk.inbox.modals.context_card_channel') }}</span>
                        <span class="bv-info-table__v" id="ncSummaryChannel">WhatsApp</span>
                    </div>
                </div>

                <div class="bv-form-field">
                    <label class="bv-form-label bv-form-label--plain">{{ __('helpdesk::helpdesk.inbox.modals.new_conv_wizard_search_contact') }} <span class="bv-req">*</span></label>
                    <div class="bv-modal-search bv-search-mb0">
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="text" id="ncContactSearch" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.newconv_contact_search_placeholder') }}" autocomplete="off">
                    </div>
                </div>

                <div id="ncContactList" class="bv-x40">
                    <div class="nc-empty-hint" id="ncContactHint">
                        <i class="fas fa-magnifying-glass"></i>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.newconv_search_hint') }}</span>
                    </div>
                </div>

                {{-- New contact inline form (hidden by default) --}}
                <div id="ncNewContactForm" class="bv-step-hidden">
                    <div class="bv-form-field">
                        <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.newconv_new_contact_label') }}</label>
                        <input type="text" id="ncNewName" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.newconv_full_name_placeholder') }}" class="bv-form-input">
                    </div>
                    <div class="bv-form-field">
                        <input type="email" id="ncNewEmail" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.newconv_email_placeholder') }}" class="bv-form-input">
                    </div>
                    <div class="bv-form-field">
                        <input type="tel" id="ncNewPhone" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.newconv_phone_placeholder') }}" class="bv-form-input">
                    </div>
                </div>

                {{-- Plantilla HSM (solo WhatsApp) — opcional, no bloquea la creación --}}
                <div id="ncHsmBlock" class="bv-step-hidden">
                    <div class="bv-form-field">
                        <label class="bv-form-label bv-form-label--plain">{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_template_label') }} <span class="bv-req">*</span> <span class="bv-form-hint">{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_required_hint') }}</span></label>
                        <select id="ncHsmSelect" class="bv-form-input">
                            <option value="">{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_no_template') }}</option>
                        </select>
                    </div>

                    <div id="ncHsmVars" class="bv-step-hidden bv-form-field">
                        <label class="bv-form-label bv-form-label--plain">{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_vars_label') }}</label>
                        <div id="ncHsmVarsGrid" class="nc-vars-grid"></div>
                    </div>

                    <div id="ncHsmPreview" class="bv-step-hidden bv-form-field">
                        <label class="bv-form-label bv-form-label--plain">{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_preview_label') }}</label>
                        <div class="nc-preview" id="ncHsmPreviewBox"></div>
                    </div>
                </div>

                <div class="bv-form-field" id="ncFirstMessageField">
                    <label class="bv-form-label bv-form-label--plain">{{ __('helpdesk::helpdesk.inbox.modals.newconv_first_message_label') }}</label>
                    <textarea id="ncFirstMessage" class="bv-form-input" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.newconv_first_message_placeholder') }}"></textarea>
                </div>

                <label class="bv-check">
                    <input type="checkbox" id="ncAssignSelf" checked>
                    {{ __('helpdesk::helpdesk.inbox.modals.new_conv_wizard_assign_self') }}
                </label>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="ncBtnNext">{{ __('helpdesk::helpdesk.inbox.modals.newconv_continue') }}</button>
            <button class="btn-secondary bv-step-hidden" id="ncBtnBack">{{ __('helpdesk::helpdesk.inbox.modals.newconv_back') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/. --}}
    <script src="{{ asset('vendor/helpdesk/modals/newconv.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/newconv.js')) }}"></script>
@endpush
@endonce
