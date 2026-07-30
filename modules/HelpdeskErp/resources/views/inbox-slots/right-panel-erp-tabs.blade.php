{{--
   Inbox slot del módulo HelpdeskErp.
   Aporta los tabs de ERP (Gestión/Finanzas/Fidelización) al panel derecho del
   inbox. Si el módulo se desactiva, estos tabs desaparecen.

   NOTA: no se invoca ErpContextService server-side aquí porque, si Oracle no es
   alcanzable, el servicio tiene un timeout (~3s) que bloquearía el render del
   panel. Los datos en vivo se cargan de forma diferida (lazy) vía
   ErpContextController cuando la conexión a Oracle está disponible.
   Recibe: $rpCust
   El CSS del módulo (erp-inbox.css) se carga desde modals/order-workspace.blade.php,
   que siempre está presente cuando el módulo está activo (cubre modal + este tab).
--}}

{{-- Tab: Gestión (pedidos ERP). La lista se carga en diferido al abrir el tab
     (Oracle puede tardar), y cada pedido abre el workspace de pedido ERP. --}}
<div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="erp-orders" id="bv-erp-orders"
     data-erp-customer-id="{{ $rpCust?->id }}"
     data-erp-email="{{ $rpCust?->email }}"
     data-erp-context-url="{{ url('panel/helpdesk/erp/context') }}"
     data-erp-order-detail-url-base="{{ url('panel/helpdesk/erp/orders') }}/">
    {{-- La lista de pedidos ERP la pinta el JS del inbox (conversations.js) como
         filas .rp3-erp-order. Aquí solo interceptamos su click para abrir el
         workspace de pedido ERP en vez del modal genérico. --}}
    <div class="bv-tab-empty">
        <i class="fas fa-clipboard-list"></i>
        <div class="bv-tab-empty-title">Gestión (ERP)</div>
        <div class="bv-tab-empty-sub">Abre esta pestaña para consultar los pedidos del cliente en el ERP.</div>
    </div>
</div>

{{-- Tab: Finanzas --}}
<div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="erp-finance" id="bv-erp-finance">
    <div class="bv-tab-empty">
        <i class="fas fa-coins"></i>
        <div class="bv-tab-empty-title">Finanzas (ERP)</div>
        <div class="bv-tab-empty-sub">Sin datos financieros disponibles</div>
    </div>
</div>

{{-- Tab: Fidelización --}}
<div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="erp-loyalty" id="bv-erp-loyalty">
    <div class="bv-tab-empty">
        <i class="fas fa-star"></i>
        <div class="bv-tab-empty-title">Fidelización (ERP)</div>
        <div class="bv-tab-empty-sub">Sin datos de fidelización disponibles</div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    // El inbox (conversations.js) ya carga la lista de pedidos ERP y pinta filas
    // .rp3-erp-order[data-order-platform="erp"]. Interceptamos su click en fase
    // de captura para abrir el WORKSPACE de pedido ERP en vez del modal genérico
    // nativo, sin duplicar la carga de la lista.
    document.addEventListener('click', function (e) {
        var row = e.target.closest && e.target.closest('.rp3-erp-order');
        if (!row) { return; }
        if (typeof window.openErpOrderWorkspace !== 'function') { return; }

        var oid = row.getAttribute('data-order-id');
        var cid = row.getAttribute('data-erp-customer-id') ||
            (document.getElementById('bv-erp-orders') || {}).getAttribute
                ? (document.getElementById('bv-erp-orders') || {}).getAttribute('data-erp-customer-id')
                : null;
        if (!oid || !cid) { return; }

        // Ganar al handler nativo (también en captura) y no dejar que abra el modal viejo.
        e.stopImmediatePropagation();
        e.preventDefault();
        window.openErpOrderWorkspace(cid, oid);
    }, true);
})();
</script>
@endpush
@endonce
