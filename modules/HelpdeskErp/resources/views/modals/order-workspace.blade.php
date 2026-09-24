{{-- Workspace de pedido ERP (bv-modal) — SOLO LECTURA (el ERP no permite
     mutar pedidos desde el helpdesk). Mismo esqueleto que el workspace de
     pedido de PrestaShop: columna principal con líneas y totales + panel
     lateral con pestañas Info/Envío/Pagos/Historial/Cliente. Los detalles
     (albarán, factura) se abren como hoja interna .erc-sheet dentro del
     cuerpo del modal: nunca un modal encima de otro.
     Datos: ErpChat.orderDetail() → /panel/helpdesk/customers/{id}/erp/orders/{orderId}
     (detalle + historial + envío); respaldo en la ruta vieja
     /panel/helpdesk/erp/orders/{erpId}/{orderId}. --}}

{{-- CSS del panel/tarjetas ERP (.rp3-points/.rp3-stat), movido desde el core.
     Se carga aquí (modal siempre presente si el módulo está activo) para cubrir
     el tab del panel derecho y este modal. --}}
<link rel="stylesheet" href="{{ asset('modules/helpdeskerp/css/erp-inbox.css') }}?v={{ @filemtime(public_path('modules/helpdeskerp/css/erp-inbox.css')) }}"/>
<link rel="stylesheet" href="{{ asset('modules/helpdeskerp/css/erp-order.css') }}?v={{ @filemtime(public_path('modules/helpdeskerp/css/erp-order.css')) }}"/>

{{-- Motor del panel ERP (carga lazy de tabs + detalle de pedido), movido desde
     conversations.js del core. Vía @push('scripts') para cargarse al final del body,
     DESPUÉS de jQuery/conversations.js (un <script> directo aquí correría antes y
     saldría por el guard `typeof jQuery === undefined`). Guard idempotente en el JS. --}}
@once
@push('scripts')
    <script src="{{ asset('modules/helpdeskerp/js/erp-inbox.js') }}?v={{ @filemtime(public_path('modules/helpdeskerp/js/erp-inbox.js')) }}" defer></script>
@endpush
@endonce

<div class="bv-modal" data-bv-modal-name="erp-order-workspace">
    <div class="bv-modal-dialog xxl bv-po-dialog erc-ow-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-clipboard-list"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label"><i class="fas fa-database"></i> Gestión · Solo lectura</span>
                <div class="bv-modal-title erc-ow-title" id="erpowTitle">Pedido</div>
            </div>
            <span class="bv-po-status erc-ow-head-status" id="erpowStatus"></span>
            <button type="button" class="bv-modal-close" data-bv-close aria-label="Cerrar"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body bv-po-body erc-ow-body" id="erpowBody">
            {{-- Carga / error / bloqueado: lo pinta ErpChat.stateHtml() --}}
            <div class="erc-ow-state" id="erpowState"></div>

            <div class="bv-po-grid erc-ow-grid erc-hidden" id="erpowGrid">
                {{-- ── Columna principal: contenido del pedido ── --}}
                <div class="bv-po-main">
                    <div class="bv-po-card">
                        <div class="erc-ow-meta" id="erpowMeta"></div>
                    </div>

                    <div class="bv-po-card">
                        <div class="bv-po-card-h">
                            <div class="bv-po-card-ht">
                                <span class="t">Contenido del pedido</span>
                                <span class="s" id="erpowSummary"></span>
                            </div>
                        </div>
                        <div id="erpowLines"></div>
                        <div class="erc-ow-totals" id="erpowTotals"></div>
                        <div class="erc-ow-obs" id="erpowObs"></div>
                    </div>
                </div>

                {{-- ── Panel lateral: pestañas ── --}}
                <div class="bv-po-side">
                    <div class="bv-po-tabs erc-ow-tabs" id="erpowTabs" role="tablist">
                        <button type="button" class="bv-po-tab on" data-erow-tab="info" role="tab"><i class="fas fa-circle-info"></i><span class="erc-ow-tab-lbl">Info</span></button>
                        <button type="button" class="bv-po-tab" data-erow-tab="envio" role="tab"><i class="fas fa-truck"></i><span class="erc-ow-tab-lbl">Envío</span></button>
                        <button type="button" class="bv-po-tab" data-erow-tab="pagos" role="tab"><i class="fas fa-credit-card"></i><span class="erc-ow-tab-lbl">Pagos</span></button>
                        <button type="button" class="bv-po-tab" data-erow-tab="historial" role="tab"><i class="fas fa-clock-rotate-left"></i><span class="erc-ow-tab-lbl">Historial</span></button>
                        <button type="button" class="bv-po-tab" data-erow-tab="cliente" role="tab"><i class="far fa-address-card"></i><span class="erc-ow-tab-lbl">Cliente</span></button>
                    </div>
                    <div class="bv-po-panel" data-erow-panel="info" id="erpowPanelInfo"></div>
                    <div class="bv-po-panel erc-hidden" data-erow-panel="envio" id="erpowPanelEnvio"></div>
                    <div class="bv-po-panel erc-hidden" data-erow-panel="pagos" id="erpowPanelPagos"></div>
                    <div class="bv-po-panel erc-hidden" data-erow-panel="historial" id="erpowPanelHistorial"></div>
                    <div class="bv-po-panel erc-hidden" data-erow-panel="cliente" id="erpowPanelCliente"></div>
                </div>
            </div>
        </div>

        <div class="bv-modal-foot">
            <button type="button" class="btn-primary w-100" id="erpowInsert" disabled>Insertar resumen en el chat</button>
            <button type="button" class="btn-secondary w-100" id="erpowCopy" disabled>Copiar nº de pedido</button>
            <button type="button" class="btn-secondary w-100" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a fichero propio: se cachea en el navegador en vez de
         re-descargarse en cada render del inbox. Fuente en
         modules/HelpdeskErp/public/js/ — copiar a public/modules/ tras editar. --}}
    <script src="{{ asset('modules/helpdeskerp/js/order-workspace.js') }}?v={{ @filemtime(public_path('modules/helpdeskerp/js/order-workspace.js')) }}" defer></script>
@endpush
@endonce

{{-- Gestión en el chat: núcleo (ErpChat) + piezas de modals/parts/. --}}
@include('helpdeskerp::modals.erp-index')
