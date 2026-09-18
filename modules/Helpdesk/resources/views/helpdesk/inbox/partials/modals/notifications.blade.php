{{-- Modal: Panel de notificaciones (#26 ve-notifications) --}}
<div class="bv-modal" data-bv-modal-name="notifications">
    <div class="bv-modal-dialog sm bv-x44">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="far fa-bell"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.notifications_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.notifications_title') }} <span class="bv-chip-id bv-step-hidden" id="notifCount"></span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body bv-x45" id="notifList">
            <div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
        <div class="bv-modal-foot">
            <button class="btn-secondary" id="bv-notif-mark-all">{{ __('helpdesk::helpdesk.inbox.modals.notifications_mark_all') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.notifications_close') }}</button>
        </div>
    </div>
</div>
