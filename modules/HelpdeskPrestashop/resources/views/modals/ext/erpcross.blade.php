{{-- Extensión "erpcross" (la pone HelpdeskErp): "Abrir en Gestión" en la
     tarjeta "En Gestión (ERP)" (erpbridge) del workspace de pedido.

     Sin marcado ni CSS propios: el JS añade el botón a las acciones de la
     tarjeta de erpbridge. Solo se carga si HelpdeskErp está activo (el botón
     abre su workspace de pedido). --}}
@if (function_exists('helpdesk_erp_enabled') ? helpdesk_erp_enabled() : false)
@once
@push('scripts')
    <script src="{{ asset('modules/helpdeskprestashop/js/ext/erpcross.js') }}?v={{ @filemtime(public_path('modules/helpdeskprestashop/js/ext/erpcross.js')) }}" defer></script>
@endpush
@endonce
@endif
