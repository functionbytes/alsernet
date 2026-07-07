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
        $ccPrioLabels = [
            'low' => __('helpdesk::helpdesk.inbox.modals.priority_low'),
            'normal' => __('helpdesk::helpdesk.inbox.modals.priority_normal'),
            'high' => __('helpdesk::helpdesk.inbox.modals.priority_high'),
            'urgent' => __('helpdesk::helpdesk.inbox.modals.priority_urgent'),
        ];
    @endphp
    <div class="info-table">
        <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.context_card_customer') }}</div>
        <div class="val">
            @if(!$ccIsGuest){{ $ccName }}@if($ccEmail) ·&nbsp;@endif@endif
            @if($ccEmail){{ $ccEmail }}@else {{ __('helpdesk::helpdesk.inbox.modals.context_card_no_name') }} @endif
        </div>
        <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.context_card_channel') }}</div>
        <div class="val">{{ $ccChLabel }}</div>
        <div class="lbl">{{ __('helpdesk::helpdesk.inbox.modals.priority_label') }}</div>
        <div class="val">{{ $ccPrioLabels[$ccPrio] ?? __('helpdesk::helpdesk.inbox.modals.priority_normal') }}</div>
    </div>
@endif
