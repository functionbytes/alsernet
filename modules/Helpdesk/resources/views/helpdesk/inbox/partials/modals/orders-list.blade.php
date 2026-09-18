{{-- Modal: Listar pedidos del cliente (#36 ve-orders-list) --}}
<div class="bv-modal" data-bv-modal-name="orders-list">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box"><i class="fas fa-cart-shopping"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.profile_customer_label') }}</span>
                <div class="bv-modal-title">{{ __('helpdesk::helpdesk.inbox.modals.orders_list_title') }} <span class="bv-chip" id="ordersListCount"></span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            <div class="bv-modal-search bv-x10">
                <i class="fas fa-magnifying-glass"></i>
                <input id="ordersListSearch" type="text" placeholder="{{ __('helpdesk::helpdesk.inbox.modals.orders_list_search_placeholder') }}" autocomplete="off">
            </div>

            <div class="bv-hist-pills" id="ordersListFilter">
                <button class="bv-hist-pill on" data-olf="all">{{ __('helpdesk::helpdesk.inbox.modals.all_label') }} <span class="bv-pill-count" id="olfCountAll">0</span></button>
                <button class="bv-hist-pill" data-olf="pending">{{ __('helpdesk::helpdesk.inbox.modals.orders_list_pending') }} <span class="bv-pill-count" id="olfCountPending">0</span></button>
                <button class="bv-hist-pill" data-olf="shipped">{{ __('helpdesk::helpdesk.inbox.modals.orders_list_shipped') }} <span class="bv-pill-count" id="olfCountShipped">0</span></button>
                <button class="bv-hist-pill" data-olf="delivered">{{ __('helpdesk::helpdesk.inbox.modals.orders_list_delivered') }} <span class="bv-pill-count" id="olfCountDelivered">0</span></button>
            </div>

            <div id="ordersListLoading" class="bv-cv-loading-msg"><i class="fas fa-spinner fa-spin"></i></div>
            <div id="ordersListContent" style="display:none;flex-direction:column;gap:7px"></div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="bv-orders-new">{{ __('helpdesk::helpdesk.inbox.modals.orders_list_new') }}</button>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.order_close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a public/vendor/helpdesk/modals/: se cachea en el navegador
         en vez de re-descargarse en cada render del inbox. --}}
    <script src="{{ asset('vendor/helpdesk/modals/orders-list.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/modals/orders-list.js')) }}" defer></script>
@endpush
@endonce
