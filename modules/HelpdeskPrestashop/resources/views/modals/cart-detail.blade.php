{{-- Modal: Detalle de carrito (#38c ve-cart-detail) --}}
<div class="bv-modal" data-bv-modal-name="cart-detail">
    <div class="bv-modal-dialog lg">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-cart-shopping"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CARRITO · DETALLE</span>
                <div class="bv-modal-title"><span id="cdTitle">Carrito</span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body" id="cdBody">
            <div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>
        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="cdConvert">Convertir a pedido</button>
            <button class="btn-secondary" id="cdEdit">Editar carrito</button>
            <button class="btn-secondary" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a fichero propio: se cachea en el navegador en vez de
         re-descargarse en cada render del inbox. Fuente en
         modules/HelpdeskPrestashop/public/js/ — copiar a public/modules/ tras editar. --}}
    <script src="{{ asset('modules/helpdeskprestashop/js/cart-detail.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/cart-detail.js')) }}" defer></script>
@endpush
@endonce
