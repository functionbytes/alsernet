{{--
    Context-card reusable: muestra Cliente / Canal / Prioridad de la conversación activa.
    Uso: @include('helpdesk::helpdesk.inbox.partials.modals._context-card')
--}}
@if(!empty($selectedConversation))
    @php
        $ccConv  = $selectedConversation;
        $ccName  = $ccConv->customer?->name;
        $ccEmail = $ccConv->customer?->email;
        $ccIsGuest = !$ccName || strtolower((string) $ccName) === 'guest';

        $ccChLabels = ['whatsapp' => 'WhatsApp', 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'email' => 'Email', 'widget' => 'Widget'];
        $ccChLabel  = $ccChLabels[$ccConv->channel ?? 'widget'] ?? ucfirst($ccConv->channel ?? 'Widget');

        $ccPrio       = $ccConv->priority ?? 'normal';
        $ccPrioLabels = ['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'];
    @endphp
    <div class="info-table">
        <div class="lbl">Cliente</div>
        <div class="val">
            @if(!$ccIsGuest){{ $ccName }}@if($ccEmail) ·&nbsp;@endif@endif
            @if($ccEmail){{ $ccEmail }}@else Sin nombre @endif
        </div>
        <div class="lbl">Canal</div>
        <div class="val">{{ $ccChLabel }}</div>
        <div class="lbl">Prioridad</div>
        <div class="val">{{ $ccPrioLabels[$ccPrio] ?? 'Normal' }}</div>
    </div>
@endif
