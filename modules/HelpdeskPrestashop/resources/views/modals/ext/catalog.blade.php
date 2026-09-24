{{-- Extensión "catalog" · piezas 10 (ps-product-compare), 16 (ps-stock-eta),
     25 (ps-stock-wh) y 26 (ps-price-group).
     - 16/25/26 no tienen modal propio: catalog.js pinta sus bloques dentro de
       la ficha del modal "Recomendar producto" (detrás de #prVolBlock) cada
       vez que el agente elige un producto o una combinación.
     - 10 es este modal: se abre desde el botón "Comparar" que catalog.js
       añade al pie de "Recomendar producto", cerrando antes ese modal (nunca
       un modal encima de otro). Los permisos solo deciden qué se ofrece: el
       controlador los vuelve a exigir. --}}
<link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/ext/catalog.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/ext/catalog.css')) }}">

<div class="bv-modal" data-bv-modal-name="ps-product-compare">
    <div class="modal w-md psc-catalog-cmp">

        <div class="modal-head">
            <div class="modal-icon psc-catalog-icon"><i class="fas fa-scale-balanced"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">Chat · Productos</div>
                <div class="modal-title">Comparar productos</div>
            </div>
            <button class="modal-close" data-bv-close aria-label="Cerrar"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="modal-body psc-catalog-cmp-body">
            <div class="psc-note psc-note--warn bv-hidden" id="pscCatalogCmpError"><span class="psc-note-txt"></span></div>

            <div class="psc-catalog-cmp-cards" id="pscCatalogCmpCards"></div>

            <div class="psc-catalog-cmp-rows" id="pscCatalogCmpRows"></div>

            <button type="button" class="psc-btn psc-btn--dashed" id="pscCatalogCmpAdd">Añadir un tercer producto</button>

            <div class="psc-catalog-cmp-picker bv-hidden" id="pscCatalogCmpPicker">
                <div class="psc-field">
                    <span class="lbl">Buscar producto</span>
                    <input type="text" class="finput" id="pscCatalogCmpSearch" placeholder="Nombre, referencia o EAN…" autocomplete="off">
                </div>
                <div class="psc-catalog-cmp-sub" id="pscCatalogCmpResultsTitle">Del mismo fabricante o categoría</div>
                <div class="psc-catalog-cmp-results" id="pscCatalogCmpResults"></div>
            </div>
        </div>

        <div class="modal-foot">
            <button type="button" class="psc-btn psc-btn--primary is-disabled" id="pscCatalogCmpSend" disabled>Enviar comparación al chat</button>
            <button type="button" class="psc-btn psc-btn--outline" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    <script src="{{ asset('modules/helpdeskprestashop/js/ext/catalog.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/ext/catalog.js')) }}" defer></script>
@endpush
@endonce
