{{-- Gestión (ERP) · modal "Pedidos ERP": lista completa de pedidos del
     cliente en Gestión. Chips de estado con contador y búsqueda (sobre lo
     cargado), filtro de fechas (en servidor: sections/orders?from&to),
     resumen y paginación "Cargar más". Cada fila abre el workspace de pedido
     ([data-erp-order-open], lo implementa erp-chat.js). Se abre desde
     cualquier [data-erp-open="orders"]. Solo lectura.

     Aquí se carga también erp-panel.css, que da estilo a las pestañas ERP del
     panel derecho: este parcial está siempre presente cuando el módulo está
     activo, y el panel derecho se sustituye entero en cada conversación. --}}
@once
<link rel="stylesheet" href="{{ asset('modules/helpdeskerp/css/erp-panel.css') }}?v={{ @filemtime(public_path('modules/helpdeskerp/css/erp-panel.css')) }}"/>

<div class="bv-modal" data-bv-modal-name="erp-orders">
    <div class="bv-modal-dialog lg erc-ord-dialog">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-clipboard-list"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">Gestión · Pedidos</span>
                <div class="bv-modal-title"><span id="ercOrdTitle">Pedidos</span></div>
            </div>
            <span class="erc-count erc-ord-count" id="ercOrdCount"></span>
            <button type="button" class="bv-modal-close" data-bv-close aria-label="Cerrar"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="erc-ord-tools">
            <label class="erc-search">
                <i class="fas fa-magnifying-glass"></i>
                <input type="search" id="ercOrdSearch" placeholder="Buscar por nº, observaciones, origen o catálogo…" autocomplete="off" aria-label="Buscar pedidos">
            </label>
            <div class="erc-range">
                <label class="erc-field">
                    <span class="lbl">Desde</span>
                    <input type="date" id="ercOrdFrom" autocomplete="off">
                </label>
                <label class="erc-field">
                    <span class="lbl">Hasta</span>
                    <input type="date" id="ercOrdTo" autocomplete="off">
                </label>
                <div class="erc-range-actions">
                    <button type="button" class="erc-btn erc-btn--outline erc-btn--sm" data-erc-ord-dates>Aplicar fechas</button>
                    <button type="button" class="erc-link-btn erc-hidden" data-erc-ord-dates-clear>Quitar fechas</button>
                </div>
            </div>
            <div class="erc-chips" id="ercOrdChips"></div>
        </div>

        <div class="bv-modal-body erc-ord-body" id="ercOrdBody">
            <span class="erc-skel"></span>
            <span class="erc-skel"></span>
            <span class="erc-skel"></span>
        </div>

        <div class="bv-modal-foot">
            <button type="button" class="btn-primary w-100" data-erc-ord-summary disabled>Enviar resumen al chat</button>
            <button type="button" class="btn-secondary w-100" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('modules/helpdeskerp/js/erp-orders.js') }}?v={{ @filemtime(public_path('modules/helpdeskerp/js/erp-orders.js')) }}" defer></script>
@endpush
@endonce
