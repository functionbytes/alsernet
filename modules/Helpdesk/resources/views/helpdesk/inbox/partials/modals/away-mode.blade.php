{{-- Modal: Modo ausente / disponibilidad (#60 ve-away-mode) --}}
<div class="bv-modal" data-bv-modal-name="away-mode" data-current-user-id="{{ auth()->id() }}">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-moon"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close aria-label="{{ __('helpdesk::helpdesk.inbox.modals.away_mode_close_aria') }}"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="field">
                <label class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_field_current_status') }}</label>
                <div class="reason-list" id="awayModeList">
                    <button type="button" class="reason" data-bv-value="available">
                        <div class="ic"><i class="fas fa-circle bv-st-available"></i></div>
                        <div class="body">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_status_available_title') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_status_available_desc') }}</span>
                        </div>
                        <div class="radio"></div>
                    </button>
                    <button type="button" class="reason" data-bv-value="away">
                        <div class="ic"><i class="far fa-moon"></i></div>
                        <div class="body">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_status_away_title') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_status_away_desc') }}</span>
                        </div>
                        <div class="radio"></div>
                    </button>
                    <button type="button" class="reason" data-bv-value="busy">
                        <div class="ic"><i class="fas fa-mug-hot"></i></div>
                        <div class="body">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_status_busy_title') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_status_busy_desc') }}</span>
                        </div>
                        <div class="radio"></div>
                    </button>
                    <button type="button" class="reason" data-bv-value="offline">
                        <div class="ic"><i class="fas fa-circle-stop bv-st-dnd"></i></div>
                        <div class="body">
                            <span class="t">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_status_offline_title') }}</span>
                            <span class="s">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_status_offline_desc') }}</span>
                        </div>
                        <div class="radio"></div>
                    </button>
                </div>
            </div>

            <div class="field">
                <label class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_field_auto_message') }}</label>
                <textarea class="finput" id="awayMessage" rows="3"
                          placeholder="{{ __('helpdesk::helpdesk.inbox.modals.away_mode_auto_message_placeholder') }}"></textarea>
            </div>

            <div class="frow">
                <div class="field">
                    <label class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_field_auto_return') }}</label>
                    <select class="fselect" id="awayAutoReturn">
                        <option value="manual">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_auto_return_manual') }}</option>
                        <option value="1h">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_auto_return_1h') }}</option>
                        <option value="4h">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_auto_return_4h') }}</option>
                        <option value="tomorrow">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_auto_return_tomorrow') }}</option>
                    </select>
                </div>
                <div class="field">
                    <label class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_field_reassign') }}</label>
                    <select class="fselect" id="awayReassign">
                        <option value="keep">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_reassign_keep') }}</option>
                        <option value="team">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_reassign_team') }}</option>
                        <optgroup label="{{ __('helpdesk::helpdesk.inbox.modals.away_mode_reassign_agent_group') }}" id="awayReassignAgentsGroup"></optgroup>
                    </select>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-away-mode-confirm">{{ __('helpdesk::helpdesk.inbox.modals.away_mode_save') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/. --}}
    <script src="{{ asset('vendor/helpdesk/modals/away-mode.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/away-mode.js')) }}"></script>
@endpush
@endonce
