{{-- Extensión "account": secciones de cuenta del workspace de cliente
     (editar ficha, grupo y descuento, acceso a la cuenta, RGPD). No añade
     modales propios: todo se pinta dentro del workspace (PscChat.registerPane)
     para no abrir un modal encima de otro. Los permisos llegan con la ficha
     (/ps/account → can) y el controlador los vuelve a comprobar. --}}
<link rel="stylesheet" href="{{ asset('modules/helpdeskprestashop/css/ext/account.css') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/css/ext/account.css')) }}">

@once
@push('scripts')
    {{-- Fuente en modules/HelpdeskPrestashop/public/js/ext/ — copiar a public/modules/ tras editar. --}}
    <script src="{{ asset('modules/helpdeskprestashop/js/ext/account.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/ext/account.js')) }}" defer></script>
@endpush
@endonce
