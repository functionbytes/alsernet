{{--
    Tienda (PrestaShop) y Gestión (ERP) en la vista de ticket.

    Lo empuja al stack 'scripts' de la pantalla de tickets el listener
    Modules\HelpdeskErp\Listeners\ErpTicketsViewBridge (config/ext/tickets.php),
    sin tocar HelpdeskTickets. Monta los mismos modales que el inbox:

    - Gestión: helpdeskerp::modals.order-workspace, que trae erp-index
      (window.ErpChat, #ercConfig) y todas las piezas de modals/parts/
      (ficha de cliente, pedidos, finanzas, fidelización…).
    - Tienda (si el agente puede): los modales de HelpdeskPrestashop, con la
      misma lista que el puente de Contactos 360.

    Los JS de esos módulos leen el cliente de un .bv-right[data-customer-id]:
    erp-tickets.js lo crea (oculto) para el cliente del ticket abierto y lo
    rehace al cambiar de ticket, con el tab oculto de la tienda que sirve
    manager.helpdesk.erp.tickets.host. "Insertar en el chat" escribe en la
    respuesta del ticket a través de .erc-tkt-composer.

    Recibe: $ercTktErp (bool), $ercTktPs (bool).
--}}
@once
@php
    $ercTktErp = (bool) ($ercTktErp ?? false);
    $ercTktPs = (bool) ($ercTktPs ?? false);
@endphp

@push('css')
    {{-- Estilos de los .bv-modal del inbox (la pantalla de tickets no los carga):
         mismo trío que la ficha de Contactos 360. --}}
    <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations-identity.css') }}?v={{ @filemtime(public_path('vendor/helpdesk/conversations-identity.css')) }}">
    <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations.css') }}?v={{ @filemtime(public_path('vendor/helpdesk/conversations.css')) }}">
    <link rel="stylesheet" href="{{ asset('vendor/helpdesk/conversations-commerce.css') }}?v={{ @filemtime(public_path('vendor/helpdesk/conversations-commerce.css')) }}">
    <link rel="stylesheet" href="{{ asset('modules/helpdeskerp/css/erp-tickets.css') }}?v={{ @filemtime(public_path('modules/helpdeskerp/css/erp-tickets.css')) }}">
@endpush

<div id="ercTktConfig" hidden
     data-erp="{{ $ercTktErp ? 1 : 0 }}"
     data-ps="{{ $ercTktPs ? 1 : 0 }}"
     data-host-url="{{ \Illuminate\Support\Facades\Route::has('manager.helpdesk.erp.tickets.host') ? url('panel/helpdesk/erp/tickets').'/__TICKET__/host' : '' }}"></div>

{{-- "Composer" para los JS del inbox: lo que escriben aquí pasa a la
     respuesta del ticket (erp-tickets.js). Fuera de pantalla, no oculto:
     ErpChat.insert() solo escribe en un composer visible. --}}
<textarea class="bv-composer-input erc-tkt-composer" tabindex="-1" aria-hidden="true"></textarea>

{{-- HDCommerce: abrir/cerrar .bv-modal y leer el cliente del .bv-right. --}}
@include('helpdesk::helpdesk.inbox.partials.modals._commerce-js')

@if($ercTktPs)
    {{-- JS del tab oculto de la tienda (window.PscStore). En el inbox y en
         Contactos 360 lo empuja el propio tab al renderizarse en servidor;
         aquí el tab llega por AJAX (erp-tickets.js), así que se carga aquí,
         después de HDCommerce y antes de los modales que usan PscStore. --}}
    @push('scripts')
        <script src="{{ asset('modules/helpdeskprestashop/js/right-panel-prestashop-tabs.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/right-panel-prestashop-tabs.js')) }}" defer></script>
    @endpush
    @include('helpdeskprestashop::modals.cart-build')
    @include('helpdeskprestashop::modals.order-workspace')
    @include('helpdeskprestashop::modals.product-recommend')
    @include('helpdeskprestashop::modals.ps-wishlist')
    @include('helpdeskprestashop::modals.ps-customer-workspace')
    @include('helpdeskprestashop::modals.ps-voucher-create')
    @include('helpdeskprestashop::modals.ext-index')
@endif

@if($ercTktErp)
    @include('helpdeskerp::modals.order-workspace')
@endif

@push('scripts')
    <script src="{{ asset('modules/helpdeskerp/js/erp-tickets.js') }}?v={{ @filemtime(public_path('modules/helpdeskerp/js/erp-tickets.js')) }}" defer></script>
@endpush
@endonce
