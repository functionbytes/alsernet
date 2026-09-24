{{-- Mini-modal "Enviar de la lista de deseos": el agente busca en la wishlist
     de PrestaShop del cliente y prepara un producto en el composer del chat
     (no lo envía). Mismo sistema de modal v2 que "Recomendar producto"
     (.bv-modal/.modal/.modal-head/.modal-foot), en una sola columna.
     Lo llena ps-wishlist.js (Wish.load) al abrirse via bv:modal:open. --}}
<div class="bv-modal" data-bv-modal-name="ps-wishlist-send">
    <div class="modal w-md">

        <div class="modal-head">
            <div class="modal-icon"><i class="fas fa-heart"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">{{ __('helpdeskprestashop::chat.wishlist.label') }}</div>
                <div class="modal-title">{{ __('helpdeskprestashop::chat.wishlist.title') }}</div>
            </div>
            <button class="modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="modal-body">
            <div class="search-field">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" class="finput" id="psWishSearch"
                       placeholder="{{ __('helpdeskprestashop::chat.wishlist.search') }}" autocomplete="off">
            </div>

            <div class="ps-prc-list ps-prc-list--full" id="psWishList">
                <div class="bv-oc-loading">
                    <i class="fas fa-spinner fa-spin"></i> {{ __('helpdeskprestashop::chat.states.loading') }}
                </div>
            </div>

            <div class="ps-link-preview bv-hidden" id="psWishPreview">
                <div class="ps-link-preview-icon"><i class="fas fa-link"></i></div>
                <div class="ps-link-preview-body">
                    <div class="ps-link-preview-title">{{ __('helpdeskprestashop::chat.wishlist.preview') }}</div>
                    <div class="ps-link-preview-url" id="psWishUrl"></div>
                    <div class="ps-link-preview-caption">{{ __('helpdeskprestashop::chat.wishlist.preview_sub') }}</div>
                </div>
                <button type="button" class="ps-copy-btn ps-link-preview-copy" data-copy-target="psWishUrl"
                        title="Copiar enlace" aria-label="Copiar enlace">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>

        <div class="modal-foot">
            <button type="button" class="psc-btn psc-btn--primary is-disabled" id="psWishInsert" disabled>
                {{ __('helpdeskprestashop::chat.wishlist.insert') }}
            </button>
            <button type="button" class="psc-btn psc-btn--outline" data-bv-close>
                {{ __('helpdeskprestashop::chat.actions.close') }}
            </button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- JS en fichero propio, mismo criterio de cache-busting que product-recommend.js:
         se sirve el .min.js solo si existe Y es mas reciente que la fuente.
         Fuente en modules/HelpdeskPrestashop/public/js/ — copiar a public/modules/ tras editar. --}}
    @php
        $wishlistSrcMtime = @filemtime(base_path('modules/HelpdeskPrestashop/public/js/ps-wishlist.js'));
        $wishlistMinMtime = @filemtime(base_path('modules/HelpdeskPrestashop/public/js/ps-wishlist.min.js'));
        $useWishlistMin = $wishlistMinMtime !== false && $wishlistSrcMtime !== false && $wishlistMinMtime >= $wishlistSrcMtime;
    @endphp
    @if ($useWishlistMin)
    <script src="{{ asset('modules/helpdeskprestashop/js/ps-wishlist.min.js') }}?v={{ $wishlistMinMtime }}" defer></script>
    @else
    <script src="{{ asset('modules/helpdeskprestashop/js/ps-wishlist.js') }}?v={{ $wishlistSrcMtime }}" defer></script>
    @endif
@endpush
@endonce
