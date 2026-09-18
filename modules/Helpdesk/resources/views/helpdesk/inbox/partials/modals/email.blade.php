{{-- Modal: Enviar email --}}
<div class="bv-modal" data-bv-modal-name="email">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-envelope"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.email_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.email_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">
            <div class="mv4-email">

                {{-- Para --}}
                <div class="row">
                    <span class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.email_to_label') }}</span>
                    <input type="email" id="emailTo" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.email_to_placeholder') }}" value="{{ $selectedConversation?->customer?->email ?? '' }}">
                </div>

                {{-- CC (toggle) --}}
                <div class="row bv-hidden" id="emailCcRow">
                    <span class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.email_cc_label') }}</span>
                    <input type="email" id="emailCc" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.email_cc_placeholder') }}">
                </div>

                {{-- Asunto --}}
                <div class="row">
                    <span class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.email_subject_label') }}</span>
                    <input type="text" id="emailSubject" name="subject" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.email_subject_placeholder') }}">
                </div>

                {{-- Plantilla --}}
                <div class="row">
                    <span class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.email_template_label') }}</span>
                    <div class="bv-email-tpl-wrap">
                        <select id="emailTemplate">
                            <option value="">{{ __('helpdesk::helpdesk.inbox.modals.email_template_none') }}</option>
                        </select>
                        <span id="emailTemplateLoading" class="bv-hidden bv-email-tpl-loading">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                    </div>
                </div>

            </div>

            {{-- Toggle CC --}}
            <button id="emailToggleCc" class="bv-email-ccbcc">
                <i class="fas fa-plus bv-icon-xs"></i> {{ __('helpdesk::helpdesk.inbox.modals.email_toggle_cc') }}
            </button>

            {{-- Barra de formato simple --}}
            <div class="bv-email-toolbar">
                <button class="tt bv-fmt-btn bv-fmt-btn-bold" data-tt="{{ __('helpdesk::helpdesk.inbox.modals.email_format_bold_tooltip') }}">B</button>
                <button class="tt bv-fmt-btn bv-fmt-btn-italic" data-tt="{{ __('helpdesk::helpdesk.inbox.modals.email_format_italic_tooltip') }}"><i>I</i></button>
                <div class="bv-toolbar-sep"></div>
                <button class="tt bv-fmt-btn bv-fmt-btn-muted" data-tt="{{ __('helpdesk::helpdesk.inbox.modals.email_format_link_tooltip') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.modals.email_format_link_tooltip') }}"><i class="fas fa-link bv-icon-sm"></i></button>
                <button class="tt bv-fmt-btn bv-fmt-btn-muted" data-tt="{{ __('helpdesk::helpdesk.inbox.modals.email_format_list_tooltip') }}" aria-label="{{ __('helpdesk::helpdesk.inbox.modals.email_format_list_tooltip') }}"><i class="fas fa-list bv-icon-sm"></i></button>
                <div class="bv-spacer"></div>
                <button id="emailScheduleToggle" class="bv-schedule-btn" data-tt="{{ __('helpdesk::helpdesk.inbox.modals.email_schedule_tooltip') }}">
                    <i class="fas fa-clock"></i> {{ __('helpdesk::helpdesk.inbox.modals.email_schedule_button') }}
                </button>
            </div>

            {{-- Programar envío --}}
            <div id="emailScheduleRow" class="bv-email-schedule bv-hidden">
                <label class="bv-email-schedule-label">{{ __('helpdesk::helpdesk.inbox.modals.email_schedule_label') }}</label>
                <input type="datetime-local" value="{{ date('Y-m-d') }}T10:00" class="bv-datetime-input">
            </div>

            {{-- Body --}}
            <textarea id="emailBody" rows="8" class="bv-email-body" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.email_body_placeholder') }}"></textarea>

            {{-- Preview HTML (cuando se usa plantilla) --}}
            <div id="emailHtmlPreviewWrap" class="bv-hidden bv-email-preview-wrap">
                <div class="bv-email-preview-head">
                    <span class="bv-email-preview-label">{{ __('helpdesk::helpdesk.inbox.modals.email_preview_label') }}</span>
                    <button type="button" id="emailTogglePreview" class="bv-email-toggle-btn">{{ __('helpdesk::helpdesk.inbox.modals.email_preview_hide') }}</button>
                </div>
                <iframe id="emailHtmlPreview" sandbox="allow-same-origin" class="bv-email-preview-frame"></iframe>
            </div>

            <input type="hidden" id="emailTemplateId" value="">

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-email-send">{{ __('helpdesk::helpdesk.inbox.modals.email_send') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
            <button class="btn-secondary">{{ __('helpdesk::helpdesk.inbox.modals.email_attach') }}</button>
            <button class="btn-secondary">{{ __('helpdesk::helpdesk.inbox.modals.email_draft') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/email.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/email.js')) }}" defer></script>
@endpush
@endonce
