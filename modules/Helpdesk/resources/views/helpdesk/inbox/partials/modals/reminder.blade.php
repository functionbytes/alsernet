{{-- Modal: Crear recordatorio personal (#59 ve-reminder) --}}
<div class="bv-modal" data-bv-modal-name="reminder">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-bell"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.reminder_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.reminder_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Contexto --}}
            <div class="bv-info-table" id="reminderContext" style="display:none">
                <div class="bv-info-table__row">
                    <span class="bv-info-table__k">{{ __('helpdesk::helpdesk.inbox.modals.context_card_customer') }}</span>
                    <span class="bv-info-table__v" id="reminderCustomerName">—</span>
                </div>
                <div class="bv-info-table__row">
                    <span class="bv-info-table__k">{{ __('helpdesk::helpdesk.inbox.modals.reminder_conversation') }}</span>
                    <span class="bv-info-table__v bv-x54" id="reminderConvRef">—</span>
                </div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.reminder_field_title') }} <span class="bv-req">*</span></label>
                <input id="reminderTitle" type="text" class="bv-form-input" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.reminder_title_placeholder') }}" autocomplete="off">
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.reminder_when') }}</label>
                <div class="bv-opt-list" id="reminderWhenList">
                    <button type="button" class="bv-opt on" data-remind-preset="30m">
                        <div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.reminder_30m') }}</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-remind-preset="2h">
                        <div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.reminder_2h') }}</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-remind-preset="tomorrow">
                        <div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.reminder_tomorrow') }}</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-remind-preset="custom">
                        <div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.reminder_custom') }}</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                </div>
            </div>

            {{-- Custom date picker (hidden by default) --}}
            <div id="reminderCustomPanel" style="display:none">
                <div class="bv-frow">
                    <div class="bv-form-field">
                        <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.date') }}</label>
                        <input id="reminderDate" type="date" class="bv-form-input">
                    </div>
                    <div class="bv-form-field">
                        <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.snooze_time_label') }}</label>
                        <input id="reminderTime" type="time" class="bv-form-input" value="09:00">
                    </div>
                </div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.reminder_notes') }} <span class="bv-form-hint">{{ __('helpdesk::helpdesk.inbox.modals.priority_reason_optional') }}</span></label>
                <textarea id="reminderNotes" class="bv-form-input" rows="2" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.reminder_notes_placeholder') }}"></textarea>
            </div>

            <label class="bv-check">
                <input type="checkbox" id="reminderEmail" checked>
                {{ __('helpdesk::helpdesk.inbox.modals.reminder_notify_email') }}
            </label>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-reminder-create">{{ __('helpdesk::helpdesk.inbox.modals.reminder_create') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/reminder.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/reminder.js')) }}"></script>
@endpush
@endonce
