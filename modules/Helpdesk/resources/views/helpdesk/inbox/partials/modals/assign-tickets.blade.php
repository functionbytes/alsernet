{{-- Modal: Asignar tickets a la conversación (#49 ve-assign-tickets) --}}
<div class="bv-modal" data-bv-modal-name="assign-tickets">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-ticket"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_title') }}</div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-modal-search bv-x14">
                <i class="fas fa-magnifying-glass"></i>
                <input id="assignTicketsSearch" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_search_placeholder') }}" autocomplete="off">
            </div>

            <div class="bv-hist-pills bv-x14">
                <button class="bv-hist-pill on" data-atk-filter="open">{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_filter_open') }} <span class="bv-pill-count" id="atkCountOpen">—</span></button>
                <button class="bv-hist-pill" data-atk-filter="mine">{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_filter_mine') }} <span class="bv-pill-count" id="atkCountMine">—</span></button>
                <button class="bv-hist-pill" data-atk-filter="closed">{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_filter_closed') }} <span class="bv-pill-count" id="atkCountClosed">—</span></button>
            </div>

            {{-- Tickets vinculados a esta conversación --}}
            <div id="atkLinkedSection" style="display:none;margin-bottom:10px">
                <div class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_linked_label') }}</div>
                <div class="bv-x15" id="atkLinkedList"></div>
            </div>

            {{-- Tickets disponibles --}}
            <div class="bv-form-label">{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_available_label') }}</div>
            <div class="bv-x16" id="atkAvailableList">
                <div class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-assign-tickets-link" disabled>{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_link_selected') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.assign_tickets_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/assign-tickets.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/assign-tickets.js')) }}"></script>
@endpush
@endonce
