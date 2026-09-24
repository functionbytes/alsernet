{{-- Gestión (ERP) dentro del chat: núcleo compartido + piezas de interfaz.

     - erp-chat.css / erp-chat.js: base (window.ErpChat, clases .erc-*).
     - #ercConfig: URL base de las rutas manager.helpdesk.erp.chat.* y los
       permisos del agente, para que el JS oculte lo que no puede ver.
     - Cada resources/views/modals/parts/<nombre>.blade.php se incluye aquí
       (su propio modal, CSS y JS): los agentes de interfaz solo sueltan su
       blade en esa carpeta.

     Se incluye desde modals/order-workspace.blade.php, que el inbox carga
     siempre que la integración ERP está activa. --}}
@php
    $ercUser = auth()->user();
    $ercPerms = [
        'view' => (bool) $ercUser?->can('helpdeskerp.view'),
        'orders' => (bool) $ercUser?->can('helpdeskerp.orders.view'),
        'addresses' => (bool) $ercUser?->can('helpdeskerp.addresses.view'),
        'finance' => (bool) $ercUser?->can('helpdeskerp.finance.view'),
        'loyalty' => (bool) $ercUser?->can('helpdeskerp.loyalty.view'),
        'sensitive' => (bool) $ercUser?->can('helpdeskerp.sensitive.view'),
    ];
@endphp

@once
<link rel="stylesheet" href="{{ asset('modules/helpdeskerp/css/erp-chat.css') }}?v={{ @filemtime(public_path('modules/helpdeskerp/css/erp-chat.css')) }}"/>

<div id="ercConfig" hidden
     data-base="{{ url('panel/helpdesk/customers') }}"
     data-perms="{{ json_encode($ercPerms) }}"></div>

@push('scripts')
    <script src="{{ asset('modules/helpdeskerp/js/erp-chat.js') }}?v={{ @filemtime(public_path('modules/helpdeskerp/js/erp-chat.js')) }}" defer></script>
@endpush
@endonce

@foreach (glob(module_path('HelpdeskErp', 'resources/views/modals/parts/*.blade.php')) ?: [] as $ercPartView)
    @include('helpdeskerp::modals.parts.'.basename($ercPartView, '.blade.php'))
@endforeach
