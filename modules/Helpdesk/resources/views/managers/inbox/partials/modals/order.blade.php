{{-- Modal: Detalle de pedido --}}
<div class="bv-modal" data-bv-modal-name="order">
    <div class="bv-modal-dialog lg" style="max-width: 820px;">
        <div class="modal-head">
            <div class="t">
                <i class="fa-solid fa-box"></i>
                <span id="bv-order-modal-ref">Pedido #—</span>
            </div>
            <button class="x" data-bv-close><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="mv4-order-head">
                <div>
                    <div class="mv4-order-num" id="bv-order-modal-title">Pedido #—</div>
                    <div class="mv4-order-sub" id="bv-order-modal-sub">— · —</div>
                </div>
                <div class="mv4-order-status">
                    <span class="dot" id="bv-order-modal-status-dot" style="background: var(--info);"></span>
                    <span id="bv-order-modal-status">—</span>
                </div>
            </div>
            <div class="mv4-order-grid">
                <div>
                    {{-- Productos --}}
                    <div class="mv4-section">
                        <div class="mv4-sec-title">Productos</div>
                        <div id="bv-order-modal-products">
                            {{-- Se rellena vía JS --}}
                        </div>
                        <div class="mv4-totals">
                            <div>
                                <span>Subtotal</span>
                                <span id="bv-order-modal-subtotal">—</span>
                            </div>
                            <div>
                                <span>Envío</span>
                                <span>Gratis</span>
                            </div>
                            <div>
                                <span>IVA (21%)</span>
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
                        <div class="mv4-sec-title">Tracking</div>
                        <div class="mv4-track-id">
                            <i class="fa-solid fa-truck"></i>
                            <span>Nacex · <b>ES0000-NC</b></span>
                            <button class="btn ghost sm"><i class="fa-regular fa-copy"></i></button>
                        </div>
                        <div class="mv4-track">
                            <div class="mv4-track-step done"><span class="bullet"></span><div><b>Pedido recibido</b><span>—</span></div></div>
                            <div class="mv4-track-step done"><span class="bullet"></span><div><b>Pago confirmado</b><span>—</span></div></div>
                            <div class="mv4-track-step done"><span class="bullet"></span><div><b>Preparando</b><span>—</span></div></div>
                            <div class="mv4-track-step done current"><span class="bullet"></span><div><b>Enviado</b><span>—</span></div></div>
                            <div class="mv4-track-step"><span class="bullet"></span><div><b>En reparto</b><span>pendiente</span></div></div>
                            <div class="mv4-track-step"><span class="bullet"></span><div><b>Entregado</b><span>pendiente</span></div></div>
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
                        <div class="mv4-pay">
                            <i class="fa-brands fa-cc-visa" style="font-size: 18px; color: rgb(26, 31, 113);"></i>
                            <div>
                                <b>Visa ••4242</b>
                                <span>Pagado · —</span>
                            </div>
                        </div>
                    </div>
                    <div class="mv4-side-block">
                        <div class="lbl">Envío</div>
                        <div class="mv4-addr">—</div>
                    </div>
                    <div class="mv4-side-block">
                        <div class="lbl">Acciones rápidas</div>
                        <button class="mv4-quick"><i class="fa-regular fa-envelope"></i>Reenviar confirmación</button>
                        <button class="mv4-quick"><i class="fa-solid fa-receipt"></i>Descargar factura</button>
                        <button class="mv4-quick"><i class="fa-solid fa-headset"></i>Crear ticket de soporte</button>
                    </div>
                </aside>
            </div>
        </div>
        <div class="modal-foot">
            <button class="btn"><i class="fa-solid fa-print"></i>Imprimir</button>
            <button class="btn"><i class="fa-solid fa-rotate-left"></i>Reembolso</button>
            <span style="flex: 1 1 0%;"></span>
            <button class="btn" data-bv-close>Cerrar</button>
            <a href="#" target="_blank" rel="noopener" class="btn primary" id="bv-order-modal-external-link" style="display:none;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>Abrir en tienda
            </a>
        </div>
    </div>
</div>
