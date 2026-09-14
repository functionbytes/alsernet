{{-- Modal: Agendar evento --}}
<div class="bv-modal" data-bv-modal-name="schedule">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="far fa-calendar"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.schedule_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.schedule_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Tipo de evento --}}
            <input type="hidden" id="schedTypeHidden" name="type" value="callback">

            <div class="mv4-sched-type">
                <button class="mv4-sched-opt on" data-sched-type="callback">
                    <i class="fas fa-phone"></i>
                    <div><b>{{ __('helpdesk::helpdesk.inbox.modals.schedule_type_call_title') }}</b><span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_type_call_desc') }}</span></div>
                </button>
                <button class="mv4-sched-opt" data-sched-type="meeting">
                    <i class="fas fa-video"></i>
                    <div><b>{{ __('helpdesk::helpdesk.inbox.modals.schedule_type_meeting_title') }}</b><span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_type_meeting_desc') }}</span></div>
                </button>
                <button class="mv4-sched-opt" data-sched-type="task">
                    <i class="fas fa-list-check"></i>
                    <div><b>{{ __('helpdesk::helpdesk.inbox.modals.schedule_type_task_title') }}</b><span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_type_task_desc') }}</span></div>
                </button>
                <button class="mv4-sched-opt" data-sched-type="message">
                    <i class="far fa-clock"></i>
                    <div><b>{{ __('helpdesk::helpdesk.inbox.modals.schedule_type_message_title') }}</b><span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_type_message_desc') }}</span></div>
                </button>
            </div>

            {{-- Formulario callback/llamada --}}
            <div class="mv4-sched-form" id="schedFormCallback">
                <div class="row">
                    <label>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_field_date') }}</span>
                        <input type="date" id="schedCallbackDate" value="{{ date('Y-m-d', strtotime('+2 days')) }}">
                    </label>
                    <label>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_field_time') }}</span>
                        <input type="time" id="schedCallbackTime" value="10:30">
                    </label>
                    <label>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_field_duration') }}</span>
                        <select>
                            <option>{{ __('helpdesk::helpdesk.inbox.modals.schedule_duration_15') }}</option>
                            <option selected>{{ __('helpdesk::helpdesk.inbox.modals.schedule_duration_30') }}</option>
                            <option>{{ __('helpdesk::helpdesk.inbox.modals.schedule_duration_45') }}</option>
                            <option>{{ __('helpdesk::helpdesk.inbox.modals.schedule_duration_60') }}</option>
                        </select>
                    </label>
                </div>
                <div class="row">
                    <label class="bv-sched-label-flex">
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_field_phone') }}</span>
                        <input type="tel" id="schedCallbackPhone" value="+34 612 345 678">
                    </label>
                    <label class="bv-sched-label-flex">
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_field_assigned_to') }}</span>
                        <select id="schedCallbackAgent" class="bv-sched-agents-select">
                            <option value="">{{ __('helpdesk::helpdesk.inbox.modals.schedule_loading') }}</option>
                        </select>
                    </label>
                </div>
                <label>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_field_notes') }}</span>
                    <textarea id="schedCallbackNotes" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.schedule_callback_notes_placeholder') }}"></textarea>
                </label>
                <label class="check">
                    <input type="checkbox" checked> {{ __('helpdesk::helpdesk.inbox.modals.schedule_remind_before') }}
                </label>
            </div>

            {{-- Formulario videollamada --}}
            <div class="mv4-sched-form bv-hidden" id="schedFormMeeting">
                <div class="row">
                    <label>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_field_date') }}</span>
                        <input type="date" value="{{ date('Y-m-d', strtotime('+2 days')) }}">
                    </label>
                    <label>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_field_time') }}</span>
                        <input type="time" value="11:00">
                    </label>
                </div>
                <div class="row">
                    <label class="bv-sched-label-flex">
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_field_platform') }}</span>
                        <select id="schedPlatform">
                            <option value="meet">Google Meet</option>
                            <option value="zoom">Zoom</option>
                            <option value="teams">Microsoft Teams</option>
                        </select>
                    </label>
                </div>
                <div>
                    <label><span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_field_link_generated') }}</span></label>
                    <div class="bv-meet-link-row">
                        <i class="fas fa-link bv-meet-link-icon"></i>
                        <span id="schedMeetLink" class="bv-meet-link-text">meet.google.com/abc-defg-hij</span>
                        <button class="bv-meet-copy-btn" title="Copiar enlace" aria-label="Copiar enlace"><i class="far fa-copy"></i></button>
                    </div>
                </div>
                <label class="check">
                    <input type="checkbox" checked> {{ __('helpdesk::helpdesk.inbox.modals.schedule_send_invitation') }}
                </label>
            </div>

            {{-- Formulario tarea --}}
            <div class="mv4-sched-form bv-hidden" id="schedFormTask">
                <label class="bv-sched-label-flex">
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_field_task_title') }}</span>
                    <input type="text" id="schedTaskTitle" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.schedule_task_title_placeholder') }}">
                </label>
                <label>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_field_description') }}</span>
                    <textarea id="schedTaskNotes" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.schedule_task_notes_placeholder') }}"></textarea>
                </label>
                <div class="row">
                    <label>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_field_due_date') }}</span>
                        <input type="date" id="schedTaskDate" value="{{ date('Y-m-d', strtotime('+3 days')) }}">
                    </label>
                    <label class="bv-sched-label-flex">
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_field_assign_to') }}</span>
                        <select id="schedTaskAgent" class="bv-sched-agents-select">
                            <option value="">{{ __('helpdesk::helpdesk.inbox.modals.schedule_loading') }}</option>
                        </select>
                    </label>
                </div>
            </div>

            {{-- Formulario mensaje programado --}}
            <div class="mv4-sched-form bv-hidden" id="schedFormMessage">
                <label>
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_field_message') }}</span>
                    <textarea id="schedMessageBody" rows="4" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.schedule_message_placeholder') }}"></textarea>
                </label>
                <div class="row">
                    <label>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_field_send_date') }}</span>
                        <input type="date" id="schedMessageDate" value="{{ date('Y-m-d', strtotime('+1 day')) }}">
                    </label>
                    <label>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.schedule_field_time') }}</span>
                        <input type="time" id="schedMessageTime" value="09:00">
                    </label>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-sched-save">{{ __('helpdesk::helpdesk.inbox.modals.schedule_create_event') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/schedule.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/schedule.js')) }}" defer></script>
@endpush
@endonce
