{{-- Modal: Listar carritos del cliente (#38b ve-carts-list) --}}
<div class="bv-modal" data-bv-modal-name="carts-list">
    <div class="bv-modal-dialog md">
        <div class="bv-modal-head bv-modal-head--with-icon">
            <div class="bv-modal-icon-box primary"><i class="fas fa-cart-shopping"></i></div>
            <div class="bv-modal-title-wrap">
                <span class="bv-modal-label">CHAT · CARRITOS</span>
                <div class="bv-modal-title"><span>Carritos del cliente</span></div>
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">

            {{-- Filter pills --}}
            <div class="bv-oc-filter-row" id="clFilterRow">
                <span class="bv-media-pill on" data-cf="all">Todos <span class="c" id="clCountAll">0</span></span>
                <span class="bv-media-pill" data-cf="active">Activos <span class="c" id="clCountActive">0</span></span>
                <span class="bv-media-pill" data-cf="abandoned">Abandonados <span class="c" id="clCountAbandoned">0</span></span>
                <span class="bv-media-pill" data-cf="converted">Convertidos <span class="c" id="clCountConverted">0</span></span>
            </div>

            {{-- List --}}
            <div id="clList">
                <div class="bv-oc-loading"><i class="fas fa-spinner fa-spin"></i> Cargando…</div>
            </div>

        </div>
        <div class="bv-modal-foot">
            <button class="btn-primary" id="clNew">Crear carrito nuevo</button>
            <button class="btn-secondary" data-bv-close>Cerrar</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS extraido a fichero propio: se cachea en el navegador en vez de
         re-descargarse en cada render del inbox. Fuente en
         modules/HelpdeskPrestashop/public/js/ — copiar a public/modules/ tras editar. --}}
    <script src="{{ asset('modules/helpdeskprestashop/js/carts-list.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/carts-list.js')) }}" defer></script>
@endpush
@endonce
