{{-- Modal: Posponer conversación --}}
<div class="bv-modal" data-bv-modal-name="snooze">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="far fa-clock"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.snooze_eyebrow') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.snooze_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            @include('helpdesk::helpdesk.inbox.partials.modals._context-card')

            <p class="snz-hint">{{ __('helpdesk::helpdesk.inbox.modals.snooze_hint') }}</p>

            <div class="snz-list">
                <button class="snz-opt on" data-snz="1h">
                    <i class="fa-solid fa-stopwatch snz-ic"></i>
                    <div class="snz-body">
                        <b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_1h') }}</b>
                        <span id="snzTime1h">{{ __('helpdesk::helpdesk.inbox.modals.snooze_reappears_placeholder') }}</span>
                    </div>
                    <span class="snz-t" id="snzBadge1h">--:--</span>
                </button>
                <button class="snz-opt" data-snz="4h">
                    <i class="fa-regular fa-clock snz-ic"></i>
                    <div class="snz-body">
                        <b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_4h') }}</b>
                        <span id="snzTime4h">{{ __('helpdesk::helpdesk.inbox.modals.snooze_reappears_placeholder') }}</span>
                    </div>
                    <span class="snz-t" id="snzBadge4h">--:--</span>
                </button>
                <button class="snz-opt" data-snz="tom">
                    <i class="fa-solid fa-sun snz-ic"></i>
                    <div class="snz-body">
                        <b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_tomorrow') }}</b>
                        <span id="snzTimeTom">{{ __('helpdesk::helpdesk.inbox.modals.snooze_tomorrow_placeholder') }}</span>
                    </div>
                    <span class="snz-t" id="snzBadgeTom">09:00</span>
                </button>
                <button class="snz-opt" data-snz="week">
                    <i class="fa-solid fa-calendar-week snz-ic"></i>
                    <div class="snz-body">
                        <b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_week') }}</b>
                        <span id="snzTimeWeek">{{ __('helpdesk::helpdesk.inbox.modals.snooze_week_placeholder') }}</span>
                    </div>
                    <span class="snz-t" id="snzBadgeWeek">--</span>
                </button>
                <button class="snz-opt" data-snz="nextweek">
                    <i class="fa-solid fa-calendar-days snz-ic"></i>
                    <div class="snz-body">
                        <b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_next_week') }}</b>
                        <span id="snzTimeNextWeek">{{ __('helpdesk::helpdesk.inbox.modals.snooze_next_week_placeholder') }}</span>
                    </div>
                    <span class="snz-t" id="snzBadgeNextWeek">--</span>
                </button>
                <button class="snz-opt" data-snz="custom">
                    <i class="fa-solid fa-calendar-plus snz-ic"></i>
                    <div class="snz-body">
                        <b>{{ __('helpdesk::helpdesk.inbox.modals.snooze_custom') }}</b>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.snooze_custom_desc') }}</span>
                    </div>
                </button>
            </div>

            <div id="snzCustomForm" class="snz-custom">
                <div class="snz-custom-row">
                    <label>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.date') }}</span>
                        <input type="date" id="snzCustomDate" class="bv-finput" value="{{ date('Y-m-d', strtotime('+1 day')) }}">
                    </label>
                    <label>
                        <span>{{ __('helpdesk::helpdesk.inbox.modals.snooze_time_label') }}</span>
                        <input type="time" id="snzCustomTime" class="bv-finput" value="09:00">
                    </label>
                </div>
            </div>

            <div class="bv-modal-divider bv-modal-divider--my-14"></div>
            <label class="bv-modal-check">
                <input type="checkbox" id="snzReopenOnReply" checked>
                <span>{{ __('helpdesk::helpdesk.inbox.modals.snooze_reopen_on_reply') }}</span>
            </label>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="snzBtnApply">{{ __('helpdesk::helpdesk.inbox.modals.snooze_title') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/snooze.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/snooze.js')) }}"></script>
@endpush
@endonce
