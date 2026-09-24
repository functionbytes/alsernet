{{-- Extensión "orderlink": pedidos de PrestaShop ligados a la conversación.
     No necesita marcado propio: el JS liga el pedido al abrirlo en el
     workspace (psc:order-rendered) o al enviar su tarjeta/seguimiento al
     chat, y pinta los pedidos ligados arriba del tab Tienda (#ps-ext-wrap)
     al recibir psc:store-rendered. --}}
<link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/ext/orderlink.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/ext/orderlink.css')) }}">

@once
@push('scripts')
    <script src="{{ asset('modules/helpdeskprestashop/js/ext/orderlink.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/ext/orderlink.js')) }}" defer></script>
@endpush
@endonce
