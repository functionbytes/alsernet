{{--
   Inbox slot del módulo HelpdeskErp.
   Aporta los tabs de ERP (Gestión/Finanzas/Fidelización) al panel derecho del
   inbox. Si el módulo se desactiva, estos tabs desaparecen.

   NOTA: no se invoca ErpContextService server-side aquí porque, si Oracle no es
   alcanzable, el servicio tiene un timeout (~3s) que bloquearía el render del
   panel. Los datos en vivo se cargan de forma diferida (lazy) vía
   ErpContextController cuando la conexión a Oracle está disponible.
   Recibe: $rpCust
--}}

{{-- Tab: Gestión (pedidos ERP) --}}
<div class="bv-right-tab-content bv-tab-hidden" data-bv-tab-content="erp-orders" id="bv-erp-orders"
     data-erp-customer-id="{{ $rpCust?->id }}"
     data-erp-email="{{ $rpCust?->email }}"
     data-erp-context-url="{{ url('panel/helpdesk/erp/context') }}"
     data-erp-order-detail-url-base="{{ url('panel/helpdesk/erp/orders') }}/">
    <div class="bv-tab-empty">
        <i class="fas fa-clipboard-list"></i>
        <div class="bv-tab-empty-title">Gestión (ERP)</div>
        <div class="bv-tab-empty-sub">Sin pedidos de gestión disponibles</div>
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
