{{-- Carrito en construcción — WORKSPACE (2 columnas) fiel al mockup Alvarez
     "carrito-workspace": contenido editable + eventos a la izquierda, y cliente
     360 (Info/Carritos/Pedidos/Notas/Actividad) a la derecha. Datos reales vía
     AssistedCartService. Reusa .bv-po-* (líneas/totales/tabs) + .bv-cw-* (360). --}}

{{-- CSS de fichas/pedidos PrestaShop (.ps-*/.po-*/.combo-row), movido desde el core.
     Se carga aquí (primer modal PrestaShop, siempre presente si el módulo está activo)
     para cubrir tanto los modales como el tab del panel derecho. --}}
<link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/prestashop-inbox.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/prestashop-inbox.css')) }}"/>

<div class="bv-modal" data-bv-modal-name="cart-build">
    <div class="bv-modal-dialog xxl bv-po-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-cart-shopping"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label"><i class="fas fa-comments"></i> Chat · carrito en vivo</span>
                <div class="bv-modal-title"><span>Carrito</span> <span class="bv-po-chip" id="cbCartId">—</span> <span class="bv-po-crumb" id="cbCustName"></span></div>
            </div>
            <span class="bv-po-status" id="cbStatus"></span>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body bv-po-body">

            {{-- ═══ Vista principal: carrito + cliente 360 ═══ --}}
            <div class="bv-po-grid" id="cbGrid">

                {{-- ── Columna izquierda ── --}}
                <div class="bv-po-main">

                    {{-- Contenido del carrito --}}
                    <div class="bv-po-card">
                        <div class="bv-po-card-h">
                            <div class="bv-po-card-ht"><span class="t">Contenido del carrito</span><span class="s" id="cbSummary">—</span></div>
                            <button type="button" class="bv-cw-btn-icon" id="cbAddToggle" title="Añadir producto"><i class="fas fa-plus"></i></button>
                        </div>
                        <div class="search-field bv-hidden" id="cbSearchWrap">
                            <i class="fas fa-magnifying-glass"></i>
                            <input type="text" id="cbProductSearch" placeholder="Buscar producto para añadir…" autocomplete="off">
                        </div>
                        <div class="bv-oc-search-results bv-hidden" id="cbSearchResults"></div>
                        <div id="cbLines"></div>
                        <div class="po-coupon" id="cbCoupon">
                            <div id="cbDiscountApplied"></div>
                            <div class="bv-oc-discount-row">
                                <input type="text" class="bv-oc-input" id="cbDiscountCode" placeholder="Código de cupón…">
                                <button class="btn-secondary" id="cbApplyDiscount" type="button">Aplicar</button>
                            </div>
                        </div>
                        <div class="bv-cart-totals" id="cbTotals"></div>
                    </div>

                    {{-- Eventos del carrito --}}
                    <div class="bv-po-card">
                        <div class="bv-po-card-h">
                            <span class="bv-po-sec-ic"><i class="fas fa-wave-square"></i></span>
                            <div class="bv-po-card-ht"><span class="t">Eventos del carrito</span><span class="s" id="cbEventsSub">Actividad de la sesión</span></div>
                            <span class="bv-cev-live"><span class="dot"></span> En vivo</span>
                        </div>
                        <div class="bv-cev" id="cbEvents"></div>
                    </div>

                </div>

                {{-- ── Columna derecha: cliente 360 ── --}}
                <div class="bv-po-side">
                    <div class="bv-po-tabs" id="cwTabs">
                        <button type="button" class="bv-po-tab" data-cw-tab="info" title="Info"><i class="fas fa-circle-info"></i></button>
                        <button type="button" class="bv-po-tab on" data-cw-tab="carritos" title="Carritos"><i class="fas fa-cart-shopping"></i></button>
                        <button type="button" class="bv-po-tab" data-cw-tab="pedidos" title="Pedidos"><i class="fas fa-box"></i></button>
                        <button type="button" class="bv-po-tab" data-cw-tab="notas" title="Notas"><i class="fas fa-pen-to-square"></i></button>
                        <button type="button" class="bv-po-tab" data-cw-tab="actividad" title="Actividad"><i class="fas fa-clock-rotate-left"></i></button>
                    </div>
                    <div class="bv-po-panel bv-hidden" data-cw-panel="info" id="cwInfo"></div>
                    <div class="bv-po-panel" data-cw-panel="carritos" id="cwCarritos"></div>
                    <div class="bv-po-panel bv-hidden" data-cw-panel="pedidos" id="cwPedidos"></div>
                    <div class="bv-po-panel bv-hidden" data-cw-panel="notas" id="cwNotas"></div>
                    <div class="bv-po-panel bv-hidden" data-cw-panel="actividad" id="cwActividad"></div>
                </div>
            </div>

            {{-- ═══ Paso de checkout (datos de envío) ═══ --}}
            <div id="cbCheckoutView" class="bv-hidden bv-cw-checkout">
                <div class="bv-oc-section-title" id="cbCheckoutTitle">Datos de envío</div>
                <div class="bv-oc-field">
                    <label>Nombre</label>
                    <input type="text" class="bv-oc-input" id="cbName">
                </div>
                <div class="bv-oc-checkout-grid">
                    <div class="bv-oc-field"><label>Correo</label><input type="email" class="bv-oc-input" id="cbEmail"></div>
                    <div class="bv-oc-field"><label>Teléfono</label><input type="text" class="bv-oc-input" id="cbPhone"></div>
                </div>
                <div class="bv-oc-field">
                    <label>Dirección</label>
                    <input type="text" class="bv-oc-input" id="cbAddress" placeholder="Calle, número, piso…">
                </div>
                <div class="bv-oc-checkout-grid">
                    <div class="bv-oc-field"><label>Ciudad</label><input type="text" class="bv-oc-input" id="cbCity"></div>
                    <div class="bv-oc-field"><label>Provincia</label><input type="text" class="bv-oc-input" id="cbState"></div>
                </div>
                <div class="bv-oc-checkout-grid">
                    <div class="bv-oc-field"><label>Código postal</label><input type="text" class="bv-oc-input" id="cbZip"></div>
                    <div class="bv-oc-field"><label>País</label><input type="text" class="bv-oc-input" id="cbCountry"></div>
                </div>
            </div>
        </div>

        <div class="bv-modal-foot">
            <div id="cbFootCart" class="bv-cw-foot">
                <button class="btn-primary w-100" id="cbGenerateOrder" type="button">Generar pedido</button>
                <button class="btn-secondary w-100" id="cbSendLink" type="button">Enviar link de pago</button>
                <button class="btn-secondary w-100" id="cbClear" type="button">Vaciar carrito</button>
                <button class="btn-secondary w-100" id="cbCancel" type="button">Cancelar carrito</button>
                <button class="btn-secondary w-100" data-bv-close>Cerrar</button>
            </div>
            <div id="cbFootCheckout" class="bv-hidden bv-cw-foot">
                <button class="btn-primary w-100" id="cbConfirm" type="button">Confirmar</button>
                <button class="btn-secondary w-100" id="cbBack" type="button">Volver al carrito</button>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a fichero propio: se cachea en el navegador en vez de
         re-descargarse en cada render del inbox. Fuente en
         modules/HelpdeskPrestashop/public/js/ — copiar a public/modules/ tras editar. --}}
    <script src="{{ asset('modules/helpdeskprestashop/js/cart-build.js') }}?v={{ @filemtime(base_path('modules/HelpdeskPrestashop/public/js/cart-build.js')) }}" defer></script>
@endpush
@endonce
