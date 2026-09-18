{{-- Modal: Historial de conversaciones (#29 history-modal) --}}
<div class="bv-modal" data-bv-modal-name="history">
    <div class="bv-modal-dialog bv-modal-dialog--history">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-clock-rotate-left"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.history_label') }}</span>
                <div class="bv-modal-title">
                    {{ __('helpdesk::helpdesk.inbox.modals.history_title') }} <span class="bv-chip" id="histModalCount"></span>
                </div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body">
            <div class="bv-hist-search">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" id="histSearchInput" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.history_search_placeholder') }}">
            </div>
            <div class="bv-hist-filter-row">
                <span class="bv-media-pill bv-hist-pill on" data-bv-hist-filter="all">
                    {{ __('helpdesk::helpdesk.inbox.modals.history_filter_all') }} <span class="c" id="histCountAll">0</span>
                </span>
                <span class="bv-media-pill bv-hist-pill" data-bv-hist-filter="open">
                    {{ __('helpdesk::helpdesk.inbox.modals.history_filter_open') }} <span class="c" id="histCountOpen">0</span>
                </span>
                <span class="bv-media-pill bv-hist-pill" data-bv-hist-filter="closed">
                    {{ __('helpdesk::helpdesk.inbox.modals.history_filter_closed') }} <span class="c" id="histCountClosed">0</span>
                </span>
            </div>
            <div class="bv-hist-list" id="histList">
                <div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
            </div>
        </div>

        <div class="bv-modal-foot">
            <button class="btn btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.history_close') }}</button>
        </div>
    </div>
</div>
