{{-- Modal: Detalle de ticket (#22 ve-ticket-detail) --}}
<div class="bv-modal" data-bv-modal-name="ticket">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head">
            <div class="bv-modal-title">
                <i class="fas fa-ticket bv-modal-title-icon"></i>
                <div class="bv-tk-head-main">
                    <span class="bv-tk-head-label" id="bv-ticket-modal-num">T-—</span>
                    <span class="bv-tk-head-title" id="bv-ticket-modal-title">—</span>
                </div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">
            <div class="mv4-ticket-meta" id="bv-ticket-modal-pills">
                {{-- Pills (prioridad, estado, categoría) inyectadas vía JS --}}
            </div>

            <div class="bv-tk-section">
                <div class="mv4-sec-title">{{ __('helpdesk::helpdesk.inbox.modals.ticket_description') }}</div>
                <div class="bv-tk-text" id="bv-ticket-modal-desc">—</div>
            </div>

            <div class="bv-tk-section">
                <div class="mv4-sec-title">{{ __('helpdesk::helpdesk.inbox.modals.ticket_linked_conversations') }}</div>
                <div class="bv-tk-link-row">
                    <span class="bv-tk-link-ico">
                        <i class="fas fa-comment-dots"></i>
                    </span>
                    <div class="bv-tk-link-body">
                        <b>{{ __('helpdesk::helpdesk.inbox.modals.ticket_channel_widget') }} · <span id="bv-ticket-modal-cust-name">—</span></b>
                        <span id="bv-ticket-modal-convo-meta">—</span>
                    </div>
                    <button class="bv-btn bv-btn-ghost bv-btn-sm" type="button"><i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <div class="bv-tk-section">
                <div class="mv4-sec-title">{{ __('helpdesk::helpdesk.inbox.modals.ticket_customer') }}</div>
                <div class="bv-tk-cust">
                    <div class="bv-tk-cust-av" id="bv-ticket-modal-avatar">??</div>
                    <div class="bv-tk-cust-body">
                        <b id="bv-ticket-modal-side-name">—</b>
                        <span id="bv-ticket-modal-side-meta">—</span>
                    </div>
                </div>
            </div>

            <div class="bv-tk-section">
                <div class="mv4-sec-title">{{ __('helpdesk::helpdesk.inbox.modals.ticket_details') }}</div>
                <div class="info-table">
                    <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.ticket_order') }}</div>
                    <div class="val" id="bv-ticket-modal-related-order">—</div>
                    <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.ticket_assigned') }}</div>
                    <div class="val" id="bv-ticket-modal-assignee">{{ __('helpdesk::helpdesk.inbox.modals.ticket_unassigned') }}</div>
                    <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.ticket_team') }}</div>
                    <div class="val" id="bv-ticket-modal-group">—</div>
                    <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.ticket_created') }}</div>
                    <div class="val" id="bv-ticket-modal-created">—</div>
                    <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.ticket_last_update') }}</div>
                    <div class="val" id="bv-ticket-modal-updated">—</div>
                    <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.ticket_origin') }}</div>
                    <div class="val" id="bv-ticket-modal-origin">—</div>
                    <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.ticket_priority') }}</div>
                    <div class="val" id="bv-ticket-modal-priority">—</div>
                </div>
            </div>

            <div class="bv-tk-section">
                <div class="mv4-sec-title">{{ __('helpdesk::helpdesk.inbox.modals.ticket_activity') }}</div>
                <div class="mv4-tl" id="bv-ticket-modal-activity">
                    {{-- Timeline inyectado vía JS (mv4-tl-item) --}}
                </div>
            </div>
        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-ticket-modal-resolve"><i class="fas fa-check"></i> {{ __('helpdesk::helpdesk.inbox.modals.ticket_resolve') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.ticket_close') }}</button>
        </div>
    </div>
</div>
