{{-- Workspace de cliente PrestaShop (pieza "Nuevo · Workspace de cliente").
     Un solo modal por cliente: menú de secciones a la izquierda (248 px,
     agrupado) y el panel de la sección a la derecha; cabecera y pie fijos.
     Lo pinta prestashop-chat.js (openPsCustomerWorkspace) con el contexto que
     ya cargó el tab Tienda — no hace llamadas propias al bridge. --}}
<div class="bv-modal" data-bv-modal-name="ps-customer-workspace">
    <div class="modal psc-ws-modal">

        <div class="modal-head">
            <div class="modal-icon"><i class="fas fa-user"></i></div>
            <div class="modal-title-wrap">
                <div class="modal-label">PrestaShop · Cliente</div>
                <div class="modal-title" id="psWsTitle">Cliente</div>
            </div>
            <button class="modal-close" data-bv-close><i class="fas fa-xmark"></i></button>
        </div>

        <div class="modal-body p-0">
            <div class="ps-ws">
                <nav class="ps-ws-nav" id="psWsNav" aria-label="Secciones del cliente"></nav>
                <div class="ps-ws-pane is-on" id="psWsPane"></div>
            </div>
        </div>

        <div class="modal-foot">
            <button type="button" class="psc-btn psc-btn--primary" id="psWsSummary">Enviar resumen al chat</button>
            <button type="button" class="psc-btn psc-btn--outline" data-bv-close>{{ __('helpdeskprestashop::chat.actions.close') }}</button>
        </div>
    </div>
</div>

@once
@push('scripts')
    {{-- Listado de pedidos, workspace de cliente, avisos en vivo y detección
         de referencias. Depende de window.PscStore (right-panel-prestashop-tabs.js).
         Fuente en modules/HelpdeskPrestashop/public/js/ — copiar a public/modules/ tras editar. --}}
    <script src="{{ asset('modules/helpdeskprestashop/js/prestashop-chat.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/prestashop-chat.js')) }}" defer></script>
@endpush
@endonce
