{{-- Workspace de pedido ERP (bv-modal) — READ-ONLY (el ERP no permite mutar
     pedidos como PrestaShop). Datos reales vía el manager/Oracle. El shape es
     el del contrato de ErpCustomerDataService::getOrderDetail(). --}}

{{-- CSS del panel/tarjetas ERP (.rp3-points/.rp3-stat), movido desde el core.
     Se carga aquí (modal siempre presente si el módulo está activo) para cubrir
     el tab del panel derecho y este modal. --}}
<link rel="stylesheet" href="{{ asset('modules/helpdeskerp/css/erp-inbox.css') }}?v={{ @filemtime(public_path('modules/helpdeskerp/css/erp-inbox.css')) }}"/>

{{-- Motor del panel ERP (carga lazy de tabs + detalle de pedido), movido desde
     conversations.js del core. Vía @push('scripts') para cargarse al final del body,
     DESPUÉS de jQuery/conversations.js (un <script> directo aquí correría antes y
     saldría por el guard `typeof jQuery === undefined`). Guard idempotente en el JS. --}}
@push('scripts')
    <script src="{{ asset('modules/helpdeskerp/js/erp-inbox.js') }}?v={{ @filemtime(public_path('modules/helpdeskerp/js/erp-inbox.js')) }}" defer></script>
@endpush

<div class="bv-modal" data-bv-modal-name="erp-order-workspace">
    <div class="bv-modal-dialog xxl bv-po-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-clipboard-list"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label"><i class="fas fa-database"></i> ERP · Gestión</span>
                <div class="bv-modal-title"><span id="erpowTitle">Pedido</span></div>
            </div>
            <span class="bv-po-status" id="erpowStatus"></span>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="bv-modal-body bv-po-body">
            <div class="bv-po-loading" id="erpowLoading"><i class="fas fa-spinner fa-spin"></i> Cargando pedido del ERP… <span class="bv-po-loading-hint">La consulta a Oracle puede tardar unos segundos.</span></div>
            <div class="bv-po-error bv-hidden" id="erpowError"><i class="fas fa-triangle-exclamation"></i> <span></span></div>

            <div class="bv-po-grid bv-hidden" id="erpowGrid">
                {{-- Columna principal: contenido --}}
                <div class="bv-po-main">
                    <div class="bv-po-card">
                        <div class="bv-po-card-h">
                            <div class="bv-po-card-ht">
                                <span class="t">Contenido del pedido</span>
                                <span class="s" id="erpowSummary"></span>
                            </div>
                        </div>
                        <div id="erpowLines"></div>
                        <div class="bv-po-totals" id="erpowTotals"></div>
                        <div id="erpowObs"></div>
                    </div>
                </div>

                {{-- Panel lateral: pestañas --}}
                <div class="bv-po-side">
                    <div class="bv-po-tabs" id="erpowTabs">
                        <button type="button" class="bv-po-tab on" data-po-tab="info" title="Información"><i class="fas fa-circle-info"></i></button>
                        <button type="button" class="bv-po-tab" data-po-tab="cliente" title="Cliente"><i class="far fa-address-card"></i></button>
                    </div>
                    <div class="bv-po-panel" data-po-panel="info" id="erpowPanelInfo"></div>
                    <div class="bv-po-panel bv-hidden" data-po-panel="cliente" id="erpowPanelCliente"></div>
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
         modules/HelpdeskErp/public/js/ — copiar a public/modules/ tras editar. --}}
    <script src="{{ asset('modules/helpdeskerp/js/order-workspace.js') }}?v={{ @filemtime(public_path('modules/helpdeskerp/js/order-workspace.js')) }}" defer></script>
@endpush
@endonce
