{{-- Extensión "livehints": sin modal propio. livehints.js escucha en el
     canal privado de la conversación los avisos ps.hint.* (stock, bajada de
     precio, carrito abandonado, pedido nuevo, cambio de estado, devolución)
     y los pinta con PscChat.pushHint sobre el composer. --}}
<link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/ext/livehints.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/ext/livehints.css')) }}">

@once
@push('scripts')
    <script src="{{ asset('modules/helpdeskprestashop/js/ext/livehints.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/ext/livehints.js')) }}" defer></script>
@endpush
@endonce
