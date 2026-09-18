{{--
    Fragmento inyectado por CustomerEmailLogPanelRenderer en el detalle de un
    email de HelpdeskEmailActivity (vía EntityPanelRegistry). NO extiende ningún
    layout — se embebe dentro de OTRA página de OTRO módulo, estilo neutro
    y mínimo (small, text-muted, sin colores de alarma).

    Variable recibida: $customer (Modules\Helpdesk\Models\Customer).
--}}
<div class="small">
    <div class="fw-semibold mb-1">{{ $customer->name ?: $customer->email ?: ('Cliente #'.$customer->id) }}</div>
    @if($customer->email)
        <div class="text-muted mb-2">{{ $customer->email }}</div>
    @endif

    <ul class="list-unstyled mb-0">
        <li class="mb-1">
            <span class="text-muted">Conversaciones registradas:</span>
            {{ number_format($customer->total_conversations ?? 0) }}
        </li>
        @if($customer->last_seen_at)
            <li class="mb-1">
                <span class="text-muted">Última actividad:</span>
                {{ $customer->last_seen_at->format('Y-m-d H:i') }}
            </li>
        @endif
        @if($customer->is_blocked)
            <li class="mb-1 text-muted">Cliente bloqueado.</li>
        @endif
    </ul>
</div>
