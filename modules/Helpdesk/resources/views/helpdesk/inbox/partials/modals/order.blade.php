{{-- Modal: Detalle de pedido --}}
<div class="bv-modal" data-bv-modal-name="order">
    <div class="bv-modal-dialog xl">
        <div class="bv-modal-head">
            <div class="bv-modal-title">
                <i class="fad fa-box bv-modal-title-icon"></i>
                <span id="bv-order-modal-ref">Pedido #—</span>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">
            <div class="mv4-order-head">
                <div>
                    <div class="mv4-order-num" id="bv-order-modal-title">Pedido #—</div>
                    <div class="mv4-order-sub" id="bv-order-modal-sub">— · —</div>
                </div>
                <div class="mv4-order-status">
                    <span class="dot" id="bv-order-modal-status-dot"></span>
                    <span id="bv-order-modal-status">—</span>
                </div>
            </div>
            <div class="mv4-order-grid">
                <div>
                    {{-- Productos --}}
                    <div class="mv4-section">
                        <div class="mv4-sec-title">Productos</div>
                        <div id="bv-order-modal-products">
                            <div class="bv-tab-loading"><i class="fas fa-spinner fa-spin"></i></div>
                        </div>
                        <div class="mv4-totals">
                            <div>
                                <span>Subtotal</span>
                                <span id="bv-order-modal-subtotal">—</span>
                            </div>
                            <div>
                                <span>Envío</span>
                                <span id="bv-order-modal-shipping-val">—</span>
                            </div>
                            <div>
                                <span>Impuestos</span>
                                <span id="bv-order-modal-tax">—</span>
                            </div>
                            <div class="grand">
                                <span>Total</span>
                                <span id="bv-order-modal-total">—</span>
                            </div>
                        </div>
                    </div>

                    {{-- Tracking --}}
                    <div class="mv4-section">
                        <div class="mv4-sec-title">Seguimiento</div>
                        <div id="bv-order-modal-tracking-container">
                            <span class="bv-text-muted-12">Sin tracking disponible</span>
                        </div>
                    </div>
                </div>

                <aside class="mv4-order-side">
                    <div class="mv4-side-block">
                        <div class="lbl">Cliente</div>
                        <div class="mv4-cust">
                            <div class="av c1" id="bv-order-modal-avatar">??</div>
                            <div>
                                <b id="bv-order-modal-cust-name">—</b>
                                <span id="bv-order-modal-cust-email">—</span>
                            </div>
                        </div>
                    </div>
                    <div class="mv4-side-block">
                        <div class="lbl">Pago</div>
                        <div id="bv-order-modal-payment-container">
                            <span class="bv-text-muted-12">Sin datos de pago</span>
                        </div>
                    </div>
                    <div class="mv4-side-block">
                        <div class="lbl">Dirección de envío</div>
                        <div class="mv4-addr" id="bv-order-modal-addr">—</div>
                    </div>
                </aside>
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
