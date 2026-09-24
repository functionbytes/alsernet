{{-- Extensión "erpbridge": tarjeta "En Gestión (ERP)" del workspace de pedido.

     No necesita marcado propio: el JS añade la tarjeta a la pestaña Pago
     (#powPanelPago) al recibir psc:order-rendered y pide los datos al abrir
     esa pestaña. Aquí solo se cargan su CSS y su JS. --}}
<link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/ext/erpbridge.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/ext/erpbridge.css')) }}">

@once
@push('scripts')
    <script src="{{ asset('modules/helpdeskprestashop/js/ext/erpbridge.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/ext/erpbridge.js')) }}" defer></script>
@endpush
@endonce
