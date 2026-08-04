{{-- Modal: Lista de tickets del cliente (#21 ve-tickets-panel) --}}
<div class="bv-modal" data-bv-modal-name="tickets-panel">
    <div class="bv-modal-dialog sm">
        <div class="bv-modal-head bv-x67">
            <div class="bv-tk-panel-head">
                <span class="bv-tk-panel-head__count" id="tpCount">0</span>
                <div class="bv-tk-panel-head__meta">
                    <span class="bv-tk-panel-head__lbl">{{ __('helpdesk::helpdesk.inbox.modals.ticket_tickets_panel_label') }}</span>
                    <span class="bv-tk-panel-head__sub" id="tpSubtitle">—</span>
                </div>
                <button type="button" class="bv-tk-panel-head__add" id="bv-tp-new" title="{{ __('helpdesk::helpdesk.inbox.modals.ticket_tickets_panel_new') }}"><i class="fas fa-plus"></i></button>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-hist-pills" id="ticketsPanelFilter">
                <button class="bv-hist-pill on" data-tpf="all">{{ __('helpdesk::helpdesk.inbox.modals.all_label') }} <span class="bv-pill-count" id="tpCountAll">0</span></button>
                <button class="bv-hist-pill" data-tpf="open">{{ __('helpdesk::helpdesk.inbox.modals.ticket_tickets_panel_open') }} <span class="bv-pill-count" id="tpCountOpen">0</span></button>
                <button class="bv-hist-pill" data-tpf="closed">{{ __('helpdesk::helpdesk.inbox.modals.ticket_tickets_panel_closed') }} <span class="bv-pill-count" id="tpCountClosed">0</span></button>
            </div>

            <div id="ticketsPanelLoading" class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
            <div id="ticketsPanelList" class="bv-tk-list" style="display:none"></div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-tp-new-btn">{{ __('helpdesk::helpdesk.inbox.modals.ticket_tickets_panel_new') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.order_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/tickets-panel.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/tickets-panel.js')) }}" defer></script>
@endpush
@endonce
