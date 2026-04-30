{{--
    Context-card reusable: muestra Cliente / Canal / Prioridad de la conversación activa.
    Uso: @include('helpdesk::managers.inbox.partials.modals._context-card')
--}}
@if(!empty($selectedConversation))
    @php
        $ccConv = $selectedConversation;
        $ccCh = $ccConv->channel ?? 'widget';
        $ccChMap = [
            'whatsapp' => ['cls' => 'wa', 'icon' => 'fab fa-whatsapp', 'label' => 'WhatsApp'],
            'facebook' => ['cls' => 'fb', 'icon' => 'fab fa-facebook-messenger', 'label' => 'Facebook'],
            'instagram'=> ['cls' => 'ig', 'icon' => 'fab fa-instagram', 'label' => 'Instagram'],
            'email'    => ['cls' => 'email', 'icon' => 'far fa-envelope', 'label' => 'Email'],
            'widget'   => ['cls' => 'widget', 'icon' => 'far fa-comment-dots', 'label' => 'Widget'],
        ];
        $ccChInfo = $ccChMap[$ccCh] ?? $ccChMap['widget'];
        $ccPrio = $ccConv->priority ?? 'normal';
        $ccPrioLabels = ['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'];
        $ccPrioColors = ['low' => 'muted', 'normal' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
    @endphp
    <div class="bv-field-group">
        <div class="bv-fg-row">
            <span class="lbl">Cliente</span>
            <span class="val">{{ $ccConv->customer?->name ?? 'Sin nombre' }}@if($ccConv->customer?->email) · <span class="bv-kbd">{{ $ccConv->customer->email }}</span>@endif</span>
        </div>
        <div class="bv-fg-row">
            <span class="lbl">Canal</span>
            <span class="val">
                <span class="bv-tag-ch {{ $ccChInfo['cls'] }}">
                    <i class="{{ $ccChInfo['icon'] }}"></i>
                    {{ $ccChInfo['label'] }}
                </span>
            </span>
        </div>
        <div class="bv-fg-row">
            <span class="lbl">Prioridad</span>
            <span class="val">
                <span class="dot bv-dot-{{ $ccPrioColors[$ccPrio] ?? 'muted' }}"></span> {{ $ccPrioLabels[$ccPrio] ?? 'Normal' }}
            </span>
        </div>
    </div>
@endif
