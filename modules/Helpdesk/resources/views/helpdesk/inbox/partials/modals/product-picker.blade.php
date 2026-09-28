{{-- Modal: Enviar productos del catálogo (live commerce). Busca con la API de
     catálogo del chat web (HelpdeskLivechat AgentCatalogController) y publica
     un carrusel que el widget pinta con botón "Añadir al carrito".
     Lógica en conversations-thread.js (#bv-product-picker-*). --}}
<div class="bv-modal" data-bv-modal-name="product-picker"
     data-search-url="{{ url('/panel/helpdesk/livechat/conversations/__ID__/catalog/search') }}"
     data-share-url="{{ url('/panel/helpdesk/livechat/conversations/__ID__/catalog/share') }}"
     data-i18n="{{ json_encode([
         'empty' => __('helpdesk::helpdesk.inbox.modals.product_picker_empty'),
         'error' => __('helpdesk::helpdesk.inbox.modals.product_picker_error'),
         'sent' => __('helpdesk::helpdesk.inbox.modals.product_picker_sent'),
         'sendError' => __('helpdesk::helpdesk.inbox.modals.product_picker_send_error'),
         'combinations' => __('helpdesk::helpdesk.inbox.modals.product_picker_combinations'),
         'unavailable' => __('helpdesk::helpdesk.inbox.modals.product_picker_unavailable'),
     ]) }}">
    <div class="bv-modal-dialog">
        <div class="bv-modal-head">
            <div class="bv-modal-title">
                <i class="fas fa-bag-shopping"></i> {{ __('helpdesk::helpdesk.inbox.modals.product_picker_title') }}
            </div>
            <button class="bv-modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>
        <div class="bv-modal-body">
            <div class="bv-store-search">
                <i class="fas fa-magnifying-glass"></i>
                <input type="search" id="bv-product-picker-input" autocomplete="off"
                       placeholder="{{ __('helpdesk::helpdesk.inbox.modals.product_picker_placeholder') }}">
            </div>
            <div class="bv-pp-hint" id="bv-product-picker-hint">{{ __('helpdesk::helpdesk.inbox.modals.product_picker_hint') }}</div>
            <div class="bv-pp-list" id="bv-product-picker-list" role="listbox" aria-multiselectable="true"></div>
            <label class="bv-pp-note-label" for="bv-product-picker-note">{{ __('helpdesk::helpdesk.inbox.modals.product_picker_note') }}</label>
            <input type="text" class="bv-pp-note" id="bv-product-picker-note" maxlength="1000" autocomplete="off">
        </div>
        <div class="bv-modal-foot">
            <button class="btn-secondary" data-bv-close>{{ __('helpdesk::helpdesk.inbox.modals.cancel') }}</button>
            <button class="btn-primary" id="bv-product-picker-send" disabled>
                <i class="fas fa-paper-plane"></i>
                {{ __('helpdesk::helpdesk.inbox.modals.product_picker_send') }}
                <span id="bv-product-picker-count"></span>
            </button>
        </div>
    </div>
</div>
