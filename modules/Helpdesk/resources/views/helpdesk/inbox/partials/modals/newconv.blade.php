{{-- Modal: Nueva conversación (wizard 2 pasos) --}}
<div class="bv-modal" data-bv-modal-name="newconv">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title"><i class="fas fa-comment-medical bv-modal-title-icon"></i> {{ __('helpdesk::helpdesk.inbox.modals.newconv_title') }}</div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Step indicator --}}
            <div class="nc-steps">
                <span class="nc-step active" data-step="1">
                    <span class="nc-step-num">1</span> {{ __('helpdesk::helpdesk.inbox.modals.newconv_step_channel') }}
                </span>
                <span class="nc-step-line"></span>
                <span class="nc-step" data-step="2">
                    <span class="nc-step-num">2</span> {{ __('helpdesk::helpdesk.inbox.modals.newconv_step_recipient') }}
                </span>
            </div>

            {{-- Step 1: Canal --}}
            <div id="ncStep1">
                <div class="mv4-sec-title bv-sec-title-mb10">{{ __('helpdesk::helpdesk.inbox.modals.newconv_choose_channel') }}</div>
                <div class="nc-grid">
                    <div class="nc-channel wa on" data-channel="whatsapp">
                        <div class="logo"><i class="fab fa-whatsapp"></i></div>
                        <div>
                            <div class="t">WhatsApp</div>
                            <div class="s">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_whatsapp_desc') }}</div>
                        </div>
                    </div>
                    <div class="nc-channel fb" data-channel="facebook">
                        <div class="logo"><i class="fab fa-facebook-messenger"></i></div>
                        <div>
                            <div class="t">Messenger</div>
                            <div class="s">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_messenger_desc') }}</div>
                        </div>
                    </div>
                    <div class="nc-channel ig" data-channel="instagram">
                        <div class="logo"><i class="fab fa-instagram"></i></div>
                        <div>
                            <div class="t">Instagram</div>
                            <div class="s">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_instagram_desc') }}</div>
                        </div>
                    </div>
                    <div class="nc-channel widget" data-channel="widget">
                        <div class="logo"><i class="far fa-comment"></i></div>
                        <div>
                            <div class="t">{{ __('helpdesk::helpdesk.inbox.modals.filter_channel_web') }}</div>
                            <div class="s">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_web_desc') }}</div>
                        </div>
                    </div>
                    <div class="nc-channel email" data-channel="email">
                        <div class="logo"><i class="far fa-envelope"></i></div>
                        <div>
                            <div class="t">Email</div>
                            <div class="s">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_email_desc') }}</div>
                        </div>
                    </div>
                    <div class="nc-channel sms" data-channel="sms">
                        <div class="logo"><i class="fas fa-mobile-screen"></i></div>
                        <div>
                            <div class="t">SMS</div>
                            <div class="s">{{ __('helpdesk::helpdesk.inbox.modals.newconv_channel_sms_desc') }}</div>
                        </div>
                    </div>
                </div>

                <div id="ncHsmWarning" class="alert warning">
                    <i class="fas fa-triangle-exclamation lead"></i>
                    <div>{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_warning_pre') }} <b>{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_warning_bold') }}</b> {{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_warning_post') }}</div>
                </div>
            </div>

            {{-- Step 2: Destinatario --}}
            <div id="ncStep2" class="bv-step-hidden">

                {{-- Resumen del canal elegido en el paso 1 --}}
                <div class="info-table nc-summary">
                    <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.newconv_step_channel') }}</div>
                    <div class="val">
                        <span class="nc-chan-tag" id="ncSummaryChannel">
                            <i class="fab fa-whatsapp ic"></i>
                            <span class="nm">WhatsApp</span>
                        </span>
                    </div>
                </div>

                <div class="nc-field">
                    <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.newconv_step_recipient') }}</div>
                    <div class="mv4-search bv-search-mb0">
                        <i class="fas fa-magnifying-glass"></i>
                        <input type="text" id="ncContactSearch" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.newconv_contact_search_placeholder') }}">
                    </div>
                </div>

                <div id="ncContactList">
                    <div class="nc-empty-hint" id="ncContactHint">
                        <i class="fas fa-magnifying-glass"></i>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.newconv_search_hint') }}</span>
                    </div>
                </div>

                <button class="bv-add-contact-btn" id="ncBtnAddContact">
                    <i class="fas fa-user-plus"></i>{{ __('helpdesk::helpdesk.inbox.modals.newconv_add_contact') }}
                </button>

                {{-- New contact inline form (hidden by default) --}}
                <div id="ncNewContactForm" class="nc-field bv-step-hidden">
                    <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.newconv_new_contact_label') }}</div>
                    <input type="text" id="ncNewName" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.newconv_full_name_placeholder') }}" class="nc-input">
                    <input type="email" id="ncNewEmail" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.newconv_email_placeholder') }}" class="nc-input">
                    <input type="tel" id="ncNewPhone" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.newconv_phone_placeholder') }}" class="nc-input">
                </div>

                {{-- Plantilla HSM (solo WhatsApp) — opcional, no bloquea la creación --}}
                <div id="ncHsmBlock" class="bv-step-hidden">
                    <div class="nc-field">
                        <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_template_label') }} <span class="nc-lbl-hint">{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_required_hint') }}</span></div>
                        <select id="ncHsmSelect">
                            <option value="">{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_no_template') }}</option>
                        </select>
                    </div>

                    <div id="ncHsmVars" class="nc-field bv-step-hidden">
                        <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_vars_label') }}</div>
                        <div id="ncHsmVarsGrid" class="nc-vars-grid"></div>
                    </div>

                    <div id="ncHsmPreview" class="nc-field bv-step-hidden">
                        <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.newconv_hsm_preview_label') }}</div>
                        <div class="nc-preview" id="ncHsmPreviewBox"></div>
                    </div>
                </div>

                <div class="nc-field">
                    <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.newconv_first_message_label') }}</div>
                    <textarea id="ncFirstMessage" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.newconv_first_message_placeholder') }}"></textarea>
                </div>
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
