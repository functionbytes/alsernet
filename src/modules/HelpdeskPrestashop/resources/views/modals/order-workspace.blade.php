{{-- Workspace de pedido PrestaShop (bv-modal) — datos reales vía el bridge.
     Diseño fiel al mockup Alvarez "pedido-workspace": columna principal con el
     contenido del pedido + panel lateral con pestañas Estado/Envío/Cliente/Pago/
     Historial y las acciones de cambio de estado y asignación de seguimiento. --}}
<div class="bv-modal" data-bv-modal-name="ps-order-workspace">
    <div class="bv-modal-dialog xxl bv-po-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-bag-shopping"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label"><i class="fas fa-store"></i> PrestaShop</span>
                <div class="bv-modal-title"><span id="powTitle">Pedido</span></div>
            </div>
            <span class="bv-po-status" id="powStatus"></span>
            <button type="button" class="btn-secondary btn-sm bv-po-doc-btn bv-hidden" id="powInvoiceBtn"><i class="fas fa-file-invoice"></i> Factura</button>
            <button type="button" class="btn-secondary btn-sm bv-po-doc-btn bv-hidden" id="powSlipBtn"><i class="fas fa-file-lines"></i> Albarán</button>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body bv-po-body">
            {{-- Estado de carga / error --}}
            <div class="bv-po-loading" id="powLoading"><i class="fas fa-spinner fa-spin"></i> Cargando pedido…</div>
            <div class="bv-po-error bv-hidden" id="powError"><i class="fas fa-triangle-exclamation"></i> <span></span></div>

            <div class="bv-po-grid bv-hidden" id="powGrid">
                {{-- ── Columna principal: contenido del pedido ── --}}
                <div class="bv-po-main">
                    <div class="bv-po-card">
                        <div class="bv-po-card-h">
                            <div class="bv-po-card-ht">
                                <span class="t">Contenido del pedido</span>
                                <span class="s" id="powSummary"></span>
                            </div>
                            <a class="bv-po-store-link bv-hidden" id="powStoreLink" href="#" target="_blank" rel="noopener" title="Ver en la tienda"><i class="fas fa-store"></i></a>
                        </div>
                        <div id="powLines"></div>
                        <div class="bv-po-totals" id="powTotals"></div>
                    </div>
                </div>

                {{-- ── Panel lateral: pestañas ── --}}
                <div class="bv-po-side" id="powSide">
                    <div class="bv-po-tabs" id="powTabs">
                        <button type="button" class="bv-po-tab" data-po-tab="cliente" title="Cliente"><i class="far fa-address-card"></i></button>
                        <button type="button" class="bv-po-tab on" data-po-tab="estado" title="Estado"><i class="fas fa-circle-info"></i></button>
                        <button type="button" class="bv-po-tab" data-po-tab="envio" title="Envío"><i class="fas fa-truck"></i></button>
                        <button type="button" class="bv-po-tab" data-po-tab="pago" title="Pago"><i class="fas fa-credit-card"></i></button>
                        <button type="button" class="bv-po-tab" data-po-tab="correos" title="Correos"><i class="fas fa-paper-plane"></i></button>
                        <button type="button" class="bv-po-tab" data-po-tab="notas" title="Notas"><i class="fas fa-pen-to-square"></i></button>
                        <button type="button" class="bv-po-tab" data-po-tab="historial" title="Historial"><i class="fas fa-clock-rotate-left"></i></button>
                    </div>
                    <div class="bv-po-panel bv-hidden" data-po-panel="cliente" id="powPanelCliente"></div>
                    <div class="bv-po-panel" data-po-panel="estado" id="powPanelEstado"></div>
                    <div class="bv-po-panel bv-hidden" data-po-panel="envio" id="powPanelEnvio"></div>
                    <div class="bv-po-panel bv-hidden" data-po-panel="pago" id="powPanelPago"></div>
                    <div class="bv-po-panel bv-hidden" data-po-panel="correos" id="powPanelCorreos"></div>
                    <div class="bv-po-panel bv-hidden" data-po-panel="notas" id="powPanelNotas"></div>
                    <div class="bv-po-panel bv-hidden" data-po-panel="historial" id="powPanelHistorial"></div>
                </div>
            </div>
        </div>

        <div class="bv-modal-foot">
            <button class="btn-secondary w-100" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a fichero propio: se cachea en el navegador en vez de
         re-descargarse en cada render del inbox. Fuente en
         modules/HelpdeskPrestashop/public/js/ — copiar a public/modules/ tras editar. --}}
    <script src="{{ asset('modules/helpdeskprestashop/js/order-workspace.js') }}?v={{ @filemtime(base_path('modules/HelpdeskPrestashop/public/js/order-workspace.js')) }}"></script>
@endpush
@endonce
