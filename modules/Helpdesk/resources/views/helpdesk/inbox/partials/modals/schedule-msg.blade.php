{{-- Modal: Programar mensaje (#68 ve-schedule-msg) --}}
<div class="bv-modal" data-bv-modal-name="schedule-msg">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-paper-plane"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.schedule_msg_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.schedule_msg_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.schedule_msg_field_message') }} <span class="bv-req">*</span></label>
                <textarea id="scheduleMsgText" class="bv-form-input bv-x55" rows="3" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.schedule_msg_placeholder') }}"></textarea>
            </div>

            <div class="bv-frow bv-x56">
                <div class="bv-form-field bv-x33">
                    <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.date') }} <span class="bv-req">*</span></label>
                    <input id="scheduleMsgDate" type="date" class="bv-form-input">
                </div>
                <div class="bv-form-field bv-x33">
                    <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.snooze_time_label') }} <span class="bv-req">*</span></label>
                    <input id="scheduleMsgTime" type="time" class="bv-form-input" value="09:00">
                </div>
            </div>

            <label class="bv-check bv-x14">
                <input type="checkbox" id="scheduleMsgRepeat">
                {{ __('helpdesk::helpdesk.inbox.modals.schedule_msg_repeat') }}
            </label>

            {{-- Mensajes programados --}}
            <div class="bv-form-field">
                <div class="bv-form-label bv-x57">{{ __('helpdesk::helpdesk.inbox.modals.schedule_msg_pending') }} <span class="bv-chip-id" id="scheduledCount" style="display:none"></span></div>
                <div class="bv-x58" id="scheduledList">
                    <div class="bv-cv-loading-msg bv-x59"><i class="fas fa-spinner fa-spin"></i></div>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-schedule-msg-go">{{ __('helpdesk::helpdesk.inbox.modals.schedule_msg_go') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/schedule-msg.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/schedule-msg.js')) }}"></script>
@endpush
@endonce
