{{--
    Variables: $conversation
    This partial renders plain conversation metadata with no CSS.
    PDF and print views each apply their own styles around this partial.
--}}
<div class="conversation-info">
    <div class="info-row">
        <span class="info-label">Cliente:</span>
        <span class="info-value">{{ $conversation->customer->name }}</span>
    </div>

    @if($conversation->customer->email)
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span class="info-value">{{ $conversation->customer->email }}</span>
        </div>
    @endif

    @if($conversation->customer->phone_number)
        <div class="info-row">
            <span class="info-label">Telefono:</span>
            <span class="info-value">{{ $conversation->customer->phone_number }}</span>
        </div>
    @endif

    <div class="info-row">
        <span class="info-label">Canal:</span>
        <span class="info-value">{{ $conversation->inbox->name }}</span>
    </div>

    <div class="info-row">
        <span class="info-label">Estado:</span>
        <span class="info-value">
            <span class="status-badge status-{{ $conversation->status?->slug }}">
                {{ $conversation->status?->name ?? ucfirst($conversation->status) }}
            </span>
        </span>
    </div>

    @if($conversation->priority)
        <div class="info-row">
            <span class="info-label">Prioridad:</span>
            <span class="info-value">
                <span class="priority-badge priority-{{ $conversation->priority?->slug }}">
                    {{ $conversation->priority?->name ?? ucfirst($conversation->priority) }}
                </span>
            </span>
        </div>
    @endif

    @if($conversation->assignee)
        <div class="info-row">
            <span class="info-label">Asignado a:</span>
            <span class="info-value">{{ $conversation->assignee->name }}</span>
        </div>
    @endif

    @if($conversation->team)
        <div class="info-row">
            <span class="info-label">Equipo:</span>
            <span class="info-value">{{ $conversation->team->name }}</span>
        </div>
    @endif

    <div class="info-row">
        <span class="info-label">Creada:</span>
        <span class="info-value">{{ $conversation->created_at->format('d/m/Y H:i') }}</span>
    </div>

    <div class="info-row">
        <span class="info-label">Ultima actividad:</span>
        <span class="info-value">{{ $conversation->last_activity_at->format('d/m/Y H:i') }}</span>
    </div>

    @if($conversation->resolved_at)
        <div class="info-row">
            <span class="info-label">Resuelta:</span>
            <span class="info-value">{{ $conversation->resolved_at->format('d/m/Y H:i') }}</span>
        </div>
    @endif

    @if($conversation->cached_label_list)
        <div class="info-row">
            <span class="info-label">Etiquetas:</span>
            <span class="info-value">{{ str_replace(',', ', ', $conversation->cached_label_list) }}</span>
        </div>
    @endif
</div>
