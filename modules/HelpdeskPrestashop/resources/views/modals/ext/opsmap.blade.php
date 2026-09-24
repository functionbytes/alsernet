{{-- Extensión "opsmap" en el inbox: no pinta ningún modal. Solo carga el JS
     que añade la conversación abierta a las escrituras contra la tienda,
     para que la auditoría de acciones (pieza 40) sepa desde dónde se
     hicieron. Sus pantallas viven en resources/views/ext/opsmap/. --}}
<link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/ext/opsmap.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/ext/opsmap.css')) }}">
@once
    @push('scripts')
        <script src="{{ asset('modules/helpdeskprestashop/js/ext/opsmap.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/ext/opsmap.js')) }}" defer></script>
    @endpush
@endonce
