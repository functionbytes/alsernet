{{--
    Fragmento inyectado por DocumentEmailLogPanelRenderer en el detalle de un
    email de HelpdeskEmailActivity (vía EntityPanelRegistry). NO extiende ningún
    layout — fragmento neutro embebido en otra página.

    Variable recibida: $document (Modules\Document\Entities\Document, con
    documentType/status ya cargados).
--}}
<div class="small">
    <div class="fw-semibold mb-1">
        {{ $document->documentType?->label ?: 'Documento #'.$document->id }}
    </div>

    <ul class="list-unstyled mb-0">
        @if($document->status)
            <li class="mb-1">
                <span class="text-muted">Estado:</span>
                {{ $document->status->label }}
            </li>
        @endif
        @if($document->customer_firstname || $document->customer_lastname)
            <li class="mb-1">
                <span class="text-muted">Cliente:</span>
                {{ trim($document->customer_firstname.' '.$document->customer_lastname) }}
            </li>
        @endif
        @if($document->order_reference)
            <li class="mb-1">
                <span class="text-muted">Pedido:</span>
                {{ $document->order_reference }}
            </li>
        @endif
        <li class="mb-1">
            <span class="text-muted">Creado:</span>
            {{ $document->created_at?->format('Y-m-d H:i') }}
        </li>
    </ul>
</div>
