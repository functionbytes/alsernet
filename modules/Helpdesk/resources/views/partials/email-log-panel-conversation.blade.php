{{--
    Fragmento inyectado por ConversationEmailLogPanelRenderer en el detalle
    de un email de HelpdeskEmailActivity (vía EntityPanelRegistry). NO extiende
    ningún layout — fragmento neutro embebido en otra página.

    Variable recibida: $conversation (Modules\Helpdesk\Models\Conversation,
    con customer/status ya cargados).
--}}
<div class="small">
    <div class="fw-semibold mb-1">{{ $conversation->channel_info['label'] }}</div>

    <ul class="list-unstyled mb-0">
        @if($conversation->status)
            <li class="mb-1">
                <span class="text-muted">Estado:</span>
                {{ $conversation->status->name }}
            </li>
        @endif
        @if($conversation->customer)
            <li class="mb-1">
                <span class="text-muted">Cliente:</span>
                {{ $conversation->customer->name ?: $conversation->customer->email }}
            </li>
        @endif
        <li class="mb-1">
            <span class="text-muted">Iniciada:</span>
            {{ $conversation->created_at?->format('Y-m-d H:i') }}
        </li>
    </ul>
</div>
