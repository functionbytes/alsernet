{{-- Extensión "rever": cambios de producto gestionados por REVER.

     No necesita marcado propio: el JS pinta la tarjeta "Cambios (Rever)" en
     la pestaña Estado del workspace de pedido al recibir psc:order-rendered,
     y la sección "Cambios gestionados por Rever" en el tab Devoluciones del
     panel derecho (#bv-ps-returns). Solo lecturas. --}}
<link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/ext/rever.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/ext/rever.css')) }}">

@once
@push('scripts')
    <script src="{{ asset('modules/helpdeskprestashop/js/ext/rever.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/ext/rever.js')) }}" defer></script>
@endpush
@endonce
