{{-- Gestión (ERP) · ayudas en el chat. SOLO LECTURA. Sin modal propio.

     erp-assist.js:
     - Pista de pedido de Gestión detectado en los mensajes del cliente
       (sobre el composer, o vía PscChat.pushHint si está el módulo
       PrestaShop), con "Abrir pedido" ([data-erp-order-open]).
     - Variables erp_* en respuestas rápidas (envuelve
       window.bvReplacePlaceholders del core).
     - Bloque "Respuestas rápidas" al final de la pestaña Gestión
       (#erc-assist-replies).
     El contexto ERP de las sugerencias de IA va en servidor
     (Listeners/ErpAssistAiReplyContext, config/ext/assist.php).

     Lo incluye modals/erp-index.blade.php (glob de modals/parts). --}}
@once('erc-assist-css')
<link rel="stylesheet" href="{{ asset('modules/helpdeskerp/css/erp-assist.css') }}?v={{ @filemtime(public_path('modules/helpdeskerp/css/erp-assist.css')) }}"/>
@endonce

@once
@push('scripts')
    <script src="{{ asset('modules/helpdeskerp/js/erp-assist.js') }}?v={{ @filemtime(public_path('modules/helpdeskerp/js/erp-assist.js')) }}" defer></script>
@endpush
@endonce
