{{-- Modal: Silenciar notificaciones del chat (#51 ve-mute-chat) --}}
<div class="bv-modal" data-bv-modal-name="mute-chat">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box bv-modal-icon-box--danger"><i class="fas fa-bell-slash"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.mute_chat_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.mute_chat_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-info-table" id="muteInfoTable">
                <div class="bv-info-table__row">
                    <span class="bv-info-table__k">{{ __('helpdesk::helpdesk.inbox.modals.context_card_customer') }}</span>
                    <span class="bv-info-table__v" id="muteCustomerName">—</span>
                </div>
                <div class="bv-info-table__row">
                    <span class="bv-info-table__k">{{ __('helpdesk::helpdesk.inbox.modals.context_card_channel') }}</span>
                    <span class="bv-info-table__v" id="muteChannel">—</span>
                </div>
            </div>

            <div class="bv-warn-box">
                <div class="bv-warn-box__body">
                    <i class="fas fa-triangle-exclamation"></i>
                    <div><b>{{ __('helpdesk::helpdesk.inbox.modals.mute_chat_warning_bold') }}</b> {{ __('helpdesk::helpdesk.inbox.modals.mute_chat_warning_rest') }}</div>
                </div>
            </div>

            <div class="bv-form-field">
                <label class="bv-form-label bv-x38">{{ __('helpdesk::helpdesk.inbox.modals.mute_chat_duration_label') }}</label>
                <div class="bv-opt-list" id="muteDurationList">
                    <button type="button" class="bv-opt on" data-mute-duration="60">
                        <div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.mute_chat_1h') }}</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-mute-duration="480">
                        <div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.mute_chat_8h') }}</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-mute-duration="1440">
                        <div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.mute_chat_24h') }}</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                    <button type="button" class="bv-opt" data-mute-duration="0">
                        <div class="bv-opt__body"><span class="bv-opt__t">{{ __('helpdesk::helpdesk.inbox.modals.mute_chat_until_manual') }}</span></div>
                        <div class="bv-opt__radio"></div>
                    </button>
                </div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-brand" id="bv-mute-confirm">{{ __('helpdesk::helpdesk.inbox.modals.mute_chat_confirm') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/mute-chat.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/mute-chat.js')) }}" defer></script>
@endpush
@endonce
