{{-- Extensión "cross" de Gestión en el chat: pane "Actividad" (línea de
     tiempo única del cliente) en la ficha de cliente de Gestión.

     Sin marcado propio: erp-cross.js registra el pane con
     window.ErpCustomerWorkspace.registerPane y pinta dentro de la ficha
     (las hojas de detalle van en su cuerpo con ErpChat.sheet). El cruce
     pedido de Gestión → pedido de la tienda vive en order-workspace.js, y el
     de la tienda → Gestión en HelpdeskPrestashop/public/js/ext/erpcross.js.

     Fuente del JS/CSS en modules/HelpdeskErp/public/ — copiar a
     public/modules/helpdeskerp/ tras editar. --}}
@once
<link rel="stylesheet" href="{{ asset('modules/helpdeskerp/css/erp-cross.css') }}?v={{ @filemtime(public_path('modules/helpdeskerp/css/erp-cross.css')) }}"/>

@push('scripts')
    <script src="{{ asset('modules/helpdeskerp/js/erp-cross.js') }}?v={{ @filemtime(public_path('modules/helpdeskerp/js/erp-cross.js')) }}" defer></script>
@endpush
@endonce
