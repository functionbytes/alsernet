{{--
    Puente con el panel PrestaShop del chat (HelpdeskPrestashop): monta en la
    ficha 360 los mismos modales y extensiones que usa el inbox — espacio de
    trabajo de pedido (repetir pedido, reembolso parcial, documentos, notas,
    cobro, incidencia de envío, cambios Rever…), espacio de trabajo de cliente
    (cuenta de la tienda, grupo, acceso, RGPD), vale de compensación, cupones y
    recomendar producto. No se modifica ningún fichero de ese módulo:

    - El slot del panel derecho se incluye fuera de pantalla dentro de un
      .bv-right con los data-customer-* que leen HDCommerce y sus JS, así
      window.PscStore es el real (una llamada a /ps/orders, igual que el chat).
    - En la ficha no hay composer: un textarea .bv-composer-input fuera de
      pantalla recoge lo que esos JS "insertan" y ps-chat-bridge.js lo copia
      al portapapeles.
--}}
@php
    // Lo calcula show.blade.php (integración, módulo y permisos del agente).
    $psBridgeOn = $psBridgeOn ?? false;
    // Gestión (ERP) en el chat: mismos cimientos (.bv-right con el id del
    // contacto que lee ErpChat.customerId(), HDCommerce, cierre de .bv-modal).
    $erpBridgeOn = $erpBridgeOn ?? false;
@endphp
@if($psBridgeOn || $erpBridgeOn)
    <div class="bv-right c360-ps-host" aria-hidden="true"
         @if($erpBridgeOn) data-has-erp="1" @endif
         data-customer-id="{{ $customer->id }}"
         data-customer-name="{{ $customer->name }}"
         data-customer-email="{{ $customer->email }}"
         data-customer-phone="{{ $customer->phone ?: $customer->whatsapp_phone }}"
         data-customer-city="{{ $customer->city }}"
         data-customer-state="{{ $customer->state }}"
         data-customer-country="{{ $customer->country ?: 'ES' }}"
         data-customer-zip="{{ $customer->postal_code }}"
         data-customer-language="{{ $customer->language }}"
         data-customer-timezone="{{ $customer->timezone }}"
         data-update-url="{{ route('contacts.update', $customer) }}">
        @if($psBridgeOn)
            @include('helpdeskprestashop::inbox-slots.right-panel-prestashop-tabs', ['rpCust' => $customer])
        @endif
        @if($erpBridgeOn && view()->exists('helpdeskerp::inbox-slots.right-panel-erp-tabs'))
            @include('helpdeskerp::inbox-slots.right-panel-erp-tabs', ['rpCust' => $customer])
        @endif
    </div>
    <textarea class="bv-composer-input c360-composer-bridge" tabindex="-1" aria-hidden="true"></textarea>

    @include('helpdesk::helpdesk.inbox.partials.modals._commerce-js')
    @if($psBridgeOn)
        @include('helpdeskprestashop::modals.cart-build')
        @include('helpdeskprestashop::modals.order-workspace')
        @include('helpdeskprestashop::modals.product-recommend')
        @include('helpdeskprestashop::modals.ps-wishlist')
        @include('helpdeskprestashop::modals.ps-customer-workspace')
        @include('helpdeskprestashop::modals.ps-voucher-create')
        @include('helpdeskprestashop::modals.ext-index')
    @endif
    @if($erpBridgeOn)
        {{-- Carga erp-inbox/erp-index → erp-chat y las piezas modals/parts/*
             (pedidos, ficha de cliente, finanzas, fidelización). --}}
        @include('helpdeskerp::modals.order-workspace')
    @endif

    @push('scripts')
        <script src="{{ asset('modules/contacts/js/ps-chat-bridge.js') }}?v={{ @filemtime(public_path('modules/contacts/js/ps-chat-bridge.js')) }}" defer></script>
    @endpush
@endif
