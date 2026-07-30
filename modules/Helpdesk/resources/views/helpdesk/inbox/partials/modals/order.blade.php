{{-- Modal: Detalle de pedido — compartido por PrestaShop y ERP --}}
<div class="bv-modal" data-bv-modal-name="order">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-receipt"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">{{ __('helpdesk::helpdesk.inbox.modals.order_label') }}</span>
                <div class="bv-modal-title">
                    <span>{{ __('helpdesk::helpdesk.inbox.modals.order_title') }}</span>
                    <span class="bv-modal-chip" id="bv-order-modal-chip">#—</span>
                </div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        {{-- Tabs --}}
        <div class="bv-om-tabs">
            <button class="bv-om-tab is-active" data-bv-om-tab="info">{{ __('helpdesk::helpdesk.inbox.modals.order_tab_info') }}</button>
            <button class="bv-om-tab" data-bv-om-tab="address">{{ __('helpdesk::helpdesk.inbox.modals.order_tab_address') }}</button>
            <button class="bv-om-tab" data-bv-om-tab="tracking">{{ __('helpdesk::helpdesk.inbox.modals.order_tab_tracking') }}</button>
            <button class="bv-om-tab" data-bv-om-tab="states">{{ __('helpdesk::helpdesk.inbox.modals.order_tab_states') }}</button>
        </div>

        <div class="bv-modal-body">

            {{-- Panel: Información --}}
            <div class="bv-om-panel is-active" data-bv-om-panel="info">
                <div class="info-table">
                    <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.order_field_customer') }}</div>
                    <div class="val"><span id="bv-order-modal-cust-name">—</span></div>
                    <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.order_field_order_number') }}</div>
                    <div class="val mono" id="bv-order-modal-order-id">—</div>
                    <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.order_field_reference') }}</div>
                    <div class="val mono" id="bv-order-modal-reference">—</div>
                    <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.order_field_date') }}</div>
                    <div class="val mono" id="bv-order-modal-date">—</div>
                    <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.order_field_status') }}</div>
                    <div class="val"><span class="bv-ov-st" id="bv-order-modal-status">—</span></div>
                    <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.order_field_payment_method') }}</div>
                    <div class="val"><span id="bv-order-modal-payment">—</span></div>
                </div>

                <div class="field">
                    <div class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.order_field_products') }} <span class="hint" id="bv-order-modal-products-count"></span></div>
                    <div class="bv-ov-lines" id="bv-order-modal-products">
                        <div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i></div>
                    </div>
                </div>

                <div class="bv-cart-totals">
                    <div class="row"><span class="k">{{ __('helpdesk::helpdesk.inbox.modals.order_field_subtotal') }}</span><span class="v" id="bv-order-modal-subtotal">—</span></div>
                    <div class="row"><span class="k">{{ __('helpdesk::helpdesk.inbox.modals.order_field_shipping') }}</span><span class="v" id="bv-order-modal-shipping-val">—</span></div>
                    <div class="row" id="bv-order-modal-tax-row"><span class="k">{{ __('helpdesk::helpdesk.inbox.modals.order_field_tax') }}</span><span class="v" id="bv-order-modal-tax">—</span></div>
                    <div class="row bv-hidden" id="bv-order-modal-discount-row"><span class="k">{{ __('helpdesk::helpdesk.inbox.modals.order_field_discount') }}</span><span class="v" id="bv-order-modal-discount">—</span></div>
                    <div class="row total"><span class="k">{{ __('helpdesk::helpdesk.inbox.modals.order_field_total') }}</span><span class="v" id="bv-order-modal-total">—</span></div>
                </div>
            </div>

            {{-- Panel: Dirección --}}
            <div class="bv-om-panel" data-bv-om-panel="address">
                <div class="bv-hidden" id="bv-order-modal-addr-grid">
                    <div class="bv-om-addr-type" id="bv-om-addr-type-label">
                        <i id="bv-om-addr-type-icon" class="fas fa-truck"></i>
                        <span id="bv-om-addr-type-text">{{ __('helpdesk::helpdesk.inbox.modals.order_address_shipping_label') }}</span>
                    </div>
                    <div class="info-table">
                        <div class="lbl bv-hidden" id="bv-om-ship-name-lbl">{{ __('helpdesk::helpdesk.inbox.modals.order_field_name') }}</div>
                        <div class="val bv-hidden" id="bv-om-ship-name">—</div>
                        <div class="lbl bv-hidden" id="bv-om-ship-company-lbl">{{ __('helpdesk::helpdesk.inbox.modals.order_field_company') }}</div>
                        <div class="val bv-hidden" id="bv-om-ship-company">—</div>
                        <div class="lbl bv-hidden" id="bv-om-ship-addr1-lbl">{{ __('helpdesk::helpdesk.inbox.modals.order_field_street') }}</div>
                        <div class="val bv-hidden" id="bv-om-ship-addr1">—</div>
                        <div class="lbl bv-hidden" id="bv-om-ship-addr2-lbl">{{ __('helpdesk::helpdesk.inbox.modals.order_field_street2') }}</div>
                        <div class="val bv-hidden" id="bv-om-ship-addr2">—</div>
                        <div class="lbl bv-hidden" id="bv-om-ship-city-lbl">{{ __('helpdesk::helpdesk.inbox.modals.order_field_city') }}</div>
                        <div class="val bv-hidden" id="bv-om-ship-city">—</div>
                        <div class="lbl bv-hidden" id="bv-om-ship-state-lbl">{{ __('helpdesk::helpdesk.inbox.modals.order_field_state') }}</div>
                        <div class="val bv-hidden" id="bv-om-ship-state">—</div>
                        <div class="lbl bv-hidden" id="bv-om-ship-country-lbl">{{ __('helpdesk::helpdesk.inbox.modals.order_field_country') }}</div>
                        <div class="val bv-hidden" id="bv-om-ship-country">—</div>
                        <div class="lbl bv-hidden" id="bv-om-ship-phone-lbl">{{ __('helpdesk::helpdesk.inbox.modals.order_field_phone') }}</div>
                        <div class="val bv-hidden" id="bv-om-ship-phone">—</div>
                    </div>
                </div>
                <div class="bv-oc-empty bv-hidden" id="bv-order-modal-addr-empty">
                    <i class="fas fa-location-dot"></i>
                    <div class="title">{{ __('helpdesk::helpdesk.inbox.modals.order_address_empty_title') }}</div>
                </div>
            </div>

            {{-- Panel: Seguimiento --}}
            <div class="bv-om-panel" data-bv-om-panel="tracking">
                <div class="field bv-hidden" id="bv-order-modal-tracking-field">
                    <div class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.order_field_shipping_company') }}</div>
                    <div id="bv-order-modal-tracking-container"></div>
                </div>
                <div class="bv-oc-empty" id="bv-order-modal-tracking-empty">
                    <i class="fas fa-truck"></i>
                    <div class="title">{{ __('helpdesk::helpdesk.inbox.modals.order_tracking_empty_title') }}</div>
                    <div class="sub">{{ __('helpdesk::helpdesk.inbox.modals.order_tracking_empty_sub') }}</div>
                </div>
            </div>

            {{-- Panel: Estados --}}
            <div class="bv-om-panel" data-bv-om-panel="states">
                <div class="field bv-hidden" id="bv-order-modal-history-field">
                    <div class="flabel">{{ __('helpdesk::helpdesk.inbox.modals.order_field_status_history') }}</div>
                    <div class="bv-order-history" id="bv-order-modal-history"></div>
                </div>
                <div class="bv-oc-empty" id="bv-order-modal-states-empty">
                    <i class="fas fa-clock-rotate-left"></i>
                    <div class="title">{{ __('helpdesk::helpdesk.inbox.modals.order_states_empty_title') }}</div>
                </div>
            </div>

        </div>

        <div class="bv-modal-foot">
            <a href="#" target="_blank" rel="noopener" class="btn-primary bv-hidden" id="bv-order-modal-external-link">
                <i class="fas fa-arrow-up-right-from-square"></i> {{ __('helpdesk::helpdesk.inbox.modals.order_open_in_store') }}
            </a>
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.order_close') }}</button>
        </div>
    </div>
</div>
