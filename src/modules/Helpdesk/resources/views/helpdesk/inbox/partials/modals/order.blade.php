{{-- Modal: Detalle de pedido — compartido por PrestaShop y ERP --}}
<div class="bv-modal" data-bv-modal-name="order">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-receipt"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">PEDIDO · DETALLE</span>
                <div class="bv-modal-title">
                    <span>Pedido</span>
                    <span class="bv-modal-chip" id="bv-order-modal-chip">#—</span>
                </div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        {{-- Tabs --}}
        <div class="bv-om-tabs">
            <button class="bv-om-tab is-active" data-bv-om-tab="info">Información</button>
            <button class="bv-om-tab" data-bv-om-tab="address">Dirección</button>
            <button class="bv-om-tab" data-bv-om-tab="tracking">Seguimiento</button>
            <button class="bv-om-tab" data-bv-om-tab="states">Estados</button>
        </div>

        <div class="bv-modal-body">

            {{-- Panel: Información --}}
            <div class="bv-om-panel is-active" data-bv-om-panel="info">
                <div class="info-table">
                    <div class="lbl">Cliente</div>
                    <div class="val"><span id="bv-order-modal-cust-name">—</span></div>
                    <div class="lbl">Nº pedido</div>
                    <div class="val mono" id="bv-order-modal-order-id">—</div>
                    <div class="lbl">Referencia</div>
                    <div class="val mono" id="bv-order-modal-reference">—</div>
                    <div class="lbl">Fecha</div>
                    <div class="val mono" id="bv-order-modal-date">—</div>
                    <div class="lbl">Estado</div>
                    <div class="val"><span class="bv-ov-st" id="bv-order-modal-status">—</span></div>
                    <div class="lbl">Método pago</div>
                    <div class="val"><span id="bv-order-modal-payment">—</span></div>
                </div>

                <div class="field">
                    <div class="flabel">Productos <span class="hint" id="bv-order-modal-products-count"></span></div>
                    <div class="bv-ov-lines" id="bv-order-modal-products">
                        <div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i></div>
                    </div>
                </div>

                <div class="bv-cart-totals">
                    <div class="row"><span class="k">Subtotal</span><span class="v" id="bv-order-modal-subtotal">—</span></div>
                    <div class="row"><span class="k">Envío</span><span class="v" id="bv-order-modal-shipping-val">—</span></div>
                    <div class="row" id="bv-order-modal-tax-row"><span class="k">Impuestos</span><span class="v" id="bv-order-modal-tax">—</span></div>
                    <div class="row bv-hidden" id="bv-order-modal-discount-row"><span class="k">Descuento</span><span class="v" id="bv-order-modal-discount">—</span></div>
                    <div class="row total"><span class="k">Total</span><span class="v" id="bv-order-modal-total">—</span></div>
                </div>
            </div>

            {{-- Panel: Dirección --}}
            <div class="bv-om-panel" data-bv-om-panel="address">
                <div class="bv-hidden" id="bv-order-modal-addr-grid">
                    <div class="bv-om-addr-type" id="bv-om-addr-type-label">
                        <i id="bv-om-addr-type-icon" class="fas fa-truck"></i>
                        <span id="bv-om-addr-type-text">Dirección de envío</span>
                    </div>
                    <div class="info-table">
                        <div class="lbl bv-hidden" id="bv-om-ship-name-lbl">Nombre</div>
                        <div class="val bv-hidden" id="bv-om-ship-name">—</div>
                        <div class="lbl bv-hidden" id="bv-om-ship-company-lbl">Empresa</div>
                        <div class="val bv-hidden" id="bv-om-ship-company">—</div>
                        <div class="lbl bv-hidden" id="bv-om-ship-addr1-lbl">Calle</div>
                        <div class="val bv-hidden" id="bv-om-ship-addr1">—</div>
                        <div class="lbl bv-hidden" id="bv-om-ship-addr2-lbl">Calle 2</div>
                        <div class="val bv-hidden" id="bv-om-ship-addr2">—</div>
                        <div class="lbl bv-hidden" id="bv-om-ship-city-lbl">Ciudad</div>
                        <div class="val bv-hidden" id="bv-om-ship-city">—</div>
                        <div class="lbl bv-hidden" id="bv-om-ship-state-lbl">Provincia</div>
                        <div class="val bv-hidden" id="bv-om-ship-state">—</div>
                        <div class="lbl bv-hidden" id="bv-om-ship-country-lbl">País</div>
                        <div class="val bv-hidden" id="bv-om-ship-country">—</div>
                        <div class="lbl bv-hidden" id="bv-om-ship-phone-lbl">Teléfono</div>
                        <div class="val bv-hidden" id="bv-om-ship-phone">—</div>
                    </div>
                </div>
                <div class="bv-oc-empty bv-hidden" id="bv-order-modal-addr-empty">
                    <i class="fas fa-location-dot"></i>
                    <div class="title">Sin datos de dirección</div>
                </div>
            </div>

            {{-- Panel: Seguimiento --}}
            <div class="bv-om-panel" data-bv-om-panel="tracking">
                <div class="field bv-hidden" id="bv-order-modal-tracking-field">
                    <div class="flabel">Empresa de envío</div>
                    <div id="bv-order-modal-tracking-container"></div>
                </div>
                <div class="bv-oc-empty" id="bv-order-modal-tracking-empty">
                    <i class="fas fa-truck"></i>
                    <div class="title">Sin seguimiento</div>
                    <div class="sub">No hay información de envío disponible</div>
                </div>
            </div>

            {{-- Panel: Estados --}}
            <div class="bv-om-panel" data-bv-om-panel="states">
                <div class="field bv-hidden" id="bv-order-modal-history-field">
                    <div class="flabel">Historial de estados</div>
                    <div class="bv-order-history" id="bv-order-modal-history"></div>
                </div>
                <div class="bv-oc-empty" id="bv-order-modal-states-empty">
                    <i class="fas fa-clock-rotate-left"></i>
                    <div class="title">Sin historial</div>
                </div>
            </div>

        </div>

        <div class="bv-modal-foot">
            <a href="#" target="_blank" rel="noopener" class="btn-primary bv-hidden" id="bv-order-modal-external-link">
                <i class="fas fa-arrow-up-right-from-square"></i> Abrir en tienda
            </a>
            <button class="btn-secondary" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>
