{{-- CSS/JS de las pantallas de administración de opsmap. prestashop-chat.css
     aporta el vocabulario .psc-* (filas de mapeo, chips, notas); opsmap.css
     solo lo propio de estas pantallas. --}}
@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/prestashop-chat.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/prestashop-chat.css')) }}">
    <link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/ext/opsmap.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/ext/opsmap.css')) }}">
@endpush

@push('scripts')
    <script src="{{ asset('modules/helpdeskprestashop/js/ext/opsmap-admin.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/ext/opsmap-admin.js')) }}" defer></script>
@endpush
