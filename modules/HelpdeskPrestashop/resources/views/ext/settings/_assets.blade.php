{{-- CSS/JS de la pantalla «Ajustes del chat». prestashop-chat.css aporta el
     vocabulario .psc-* (campos, notas, botones); settings.css solo lo propio
     (.psc-settings-*). settings.js lleva el repetidor de motivos y de
     respuestas rápidas (en el inbox, el mismo fichero define el proveedor
     de respuestas rápidas). --}}
@push('styles')
    <link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/prestashop-chat.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/prestashop-chat.css')) }}">
    <link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/ext/settings.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/ext/settings.css')) }}">
@endpush

@push('scripts')
    <script src="{{ asset('modules/helpdeskprestashop/js/ext/settings.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/ext/settings.js')) }}" defer></script>
@endpush
