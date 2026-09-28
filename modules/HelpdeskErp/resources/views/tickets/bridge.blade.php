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
         mismo trío que la ficha de Contactos 360.

         Sin bloquear el primer pintado (QA 28-sep-2026): son ~600 KB de CSS
         que solo visten modales y hojas que arrancan cerradas. El <style> de
         delante replica las dos únicas reglas que importan antes de que
         lleguen —conversations.css ya define .bv-modal { display:none } (se
         abre con .bv-modal.on) y .bv-hidden { display:none !important }—
         para que ese marcado no asome mientras cargan. Comprobado: con las
         cuatro hojas desactivadas y solo estas dos reglas, ningún elemento
         de la pantalla cambia de visibilidad. --}}
    <style>.bv-modal{display:none}.bv-hidden{display:none!important}</style>
    @foreach (['vendor/helpdesk/conversations-identity.css', 'vendor/helpdesk/conversations.css', 'vendor/helpdesk/conversations-commerce.css', 'modules/helpdeskerp/css/erp-tickets.css'] as $ercTktCss)
        <link rel="stylesheet" href="{{ asset($ercTktCss) }}?v={{ @filemtime(public_path($ercTktCss)) }}" media="print" onload="this.media='all'">
        <noscript><link rel="stylesheet" href="{{ asset($ercTktCss) }}?v={{ @filemtime(public_path($ercTktCss)) }}"></noscript>
    @endforeach
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

{{-- Los modales de abajo (compartidos con el inbox y Contactos 360) traen
     sus <link rel="stylesheet"> en línea, dentro del <body> y DELANTE de
     tickets-app.min.js: un script síncrono espera a las hojas que tiene
     antes, así que el panel no arrancaba hasta descargar las 23 (QA
     28-sep-2026). Aquí solo visten modales cerrados —comprobado: sin ellas no
     cambia la visibilidad ni el tamaño de nada en pantalla—, así que en esta
     pantalla se cargan sin bloquear. Se reescribe la salida en vez de tocar
     las vistas porque en el inbox sí pintan paneles visibles. --}}
@php ob_start(); @endphp
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
{!! preg_replace('/<link\s+rel="stylesheet"(?![^>]*\smedia=)/i', '<link rel="stylesheet" media="print" onload="this.media=\'all\'"', ob_get_clean()) !!}

@push('scripts')
    <script src="{{ asset('modules/helpdeskerp/js/erp-tickets.js') }}?v={{ @filemtime(public_path('modules/helpdeskerp/js/erp-tickets.js')) }}" defer></script>
@endpush
@endonce
