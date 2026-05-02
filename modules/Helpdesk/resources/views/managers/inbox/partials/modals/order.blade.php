{{-- Modal: Detalle de pedido --}}
<div class="bv-modal" data-bv-modal-name="order">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head">
            <div class="bv-modal-title">
                <i class="fas fa-box bv-modal-title-icon"></i>
                Pedido #1234
                <span class="bv-modal-title-badge bv-modal-title-badge-success">Entregado</span>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Timeline horizontal --}}
            <div class="bv-htl-wrap">
                <div class="bv-htl-track">
                    {{-- Progress line --}}
                    <div class="bv-htl-line">
                        <div class="bv-htl-fill"></div>
                    </div>

                    @foreach([
                        ['Creado', '15 abr 10:23'],
                        ['Pagado', '15 abr 10:25'],
                        ['Preparando', '15 abr 14:12'],
                        ['Enviado', '16 abr 09:00'],
                        ['Entregado', '17 abr 11:45'],
                    ] as $step)
                    <div class="bv-htl-step">
                        <div class="bv-htl-dot">
                            <i class="fas fa-check bv-htl-check"></i>
                        </div>
                        <div class="bv-htl-step-text">
                            <div class="bv-htl-step-name">{{ $step[0] }}</div>
                            <div class="bv-htl-step-date">{{ $step[1] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Grid: products + side --}}
            <div class="bv-order-grid">

                {{-- Left --}}
                <div>
                    {{-- Products --}}
                    <div class="bv-right-section-title bv-rst-mb8">Productos</div>
                    <div class="bv-product-list">
                        <div class="bv-product-item">
                            <div class="bv-product-thumb">
                                <i class="fas fa-shoe-prints bv-product-icon"></i>
                            </div>
                            <div class="bv-spacer">
                                <div class="bv-product-name">Zapatillas Nike Air Max 270</div>
                                <div class="bv-product-sku">SKU: NK-AM270-42 · Talla 42 · Negro</div>
                            </div>
                            <div class="bv-product-qty">×1</div>
                            <div class="bv-product-price">€89.00</div>
                        </div>
                        <div class="bv-product-item">
                            <div class="bv-product-thumb">
                                <i class="fas fa-shirt bv-product-icon"></i>
                            </div>
                            <div class="bv-spacer">
                                <div class="bv-product-name">Calcetines deportivos (pack 3)</div>
                                <div class="bv-product-sku">SKU: CALC-DEP-3 · Talla M</div>
                            </div>
                            <div class="bv-product-qty">×1</div>
                            <div class="bv-product-price">€12.90</div>
                        </div>
                    </div>

                    {{-- Totals --}}
                    <div class="bv-order-totals">
                        <div class="bv-total-row">
                            <span>Subtotal</span><span>€101.90</span>
                        </div>
                        <div class="bv-total-row">
                            <span>IVA (21%)</span><span>€21.40</span>
                        </div>
                        <div class="bv-total-row">
                            <span>Envío</span><span class="bv-shipping-free">Gratis</span>
                        </div>
                        <div class="bv-total-row-final">
                            <span>Total</span><span>€123.30</span>
                        </div>
                    </div>

                    {{-- Tracking --}}
                    <div class="bv-right-section-title bv-rst-mt16-mb8">Número de seguimiento</div>
                    <div class="bv-tracking-row">
                        <i class="fas fa-truck bv-icon-muted"></i>
                        <span class="bv-tracking-text">Nacex · <strong>ES1234-NC</strong></span>
                        <button type="button" class="bv-btn-ghost-sm bv-ml-auto">
                            <i class="far fa-copy"></i> Copiar
                        </button>
                        <a href="#" class="bv-link-accent">
                            <i class="fas fa-arrow-up-right-from-square bv-icon-xs"></i> Rastrear
                        </a>
                    </div>
                </div>

                {{-- Right sidebar --}}
                <aside>
                    <div class="bv-flex-col-14">

                        {{-- Customer --}}
                        <div>
                            <div class="bv-field-label-mb6">Cliente</div>
                            <div class="bv-flex-row">
                                <div class="bv-av c1">CP</div>
                                <div>
                                    <div class="bv-aside-name">Carmen Pérez</div>
                                    <div class="bv-payment-sub">carmen@email.com</div>
                                </div>
                            </div>
                        </div>

                        {{-- Payment --}}
                        <div>
                            <div class="bv-field-label-mb6">Pago</div>
                            <div class="bv-flex-row">
                                <i class="fab fa-cc-visa bv-icon-visa"></i>
                                <div>
                                    <div class="bv-payment-name">Visa ••4242</div>
                                    <div class="bv-payment-sub">Pagado · 15 abr</div>
                                </div>
                            </div>
                        </div>

                        {{-- Shipping address --}}
                        <div>
                            <div class="bv-field-label-mb6">Dirección de envío</div>
                            <div class="bv-shipping-address">
                                Calle Mayor 42, 3ºB<br>
                                28013 Madrid · España
                            </div>
                        </div>

                        {{-- Quick actions --}}
                        <div>
                            <div class="bv-field-label-mb6">Acciones</div>
                            <div class="bv-order-actions">
                                <button type="button" class="bv-order-action-btn">
                                    <i class="far fa-envelope bv-icon-w14"></i> Reenviar confirmación
                                </button>
                                <button type="button" class="bv-order-action-btn">
                                    <i class="fas fa-receipt bv-icon-w14"></i> Descargar factura
                                </button>
                                <button type="button" class="bv-order-action-btn-danger">
                                    <i class="fas fa-rotate-left bv-icon-w14"></i> Solicitar reembolso
                                </button>
                            </div>
                        </div>

                    </div>
                </aside>

            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary">Seguir envío</button>
            <button class="btn-secondary">Solicitar devolución</button>
            <button class="btn-secondary">Descargar factura</button>
        </div>
    </div>
</div>
