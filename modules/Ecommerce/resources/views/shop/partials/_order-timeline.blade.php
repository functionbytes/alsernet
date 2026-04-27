@php
    $steps = [
        ['key' => 'created',    'label' => 'Orden creada',      'icon' => 'fa-shopping-cart', 'date_field' => 'created_at'],
        ['key' => 'paid',       'label' => 'Pago confirmado',   'icon' => 'fa-credit-card',   'date_field' => null],
        ['key' => 'processing', 'label' => 'En preparacion',    'icon' => 'fa-box',            'date_field' => null],
        ['key' => 'shipped',    'label' => 'Enviado',           'icon' => 'fa-truck',          'date_field' => null],
        ['key' => 'completed',  'label' => 'Entregado',         'icon' => 'fa-check-circle',   'date_field' => 'completed_at'],
    ];

    $orderStatus = is_object($order->status) ? $order->status->value : $order->status;
    $isCancelled = in_array($orderStatus, ['cancelled', 'refunded']);

    $reachedStep = match ($orderStatus) {
        'pending'               => 0,
        'processing'            => 2,
        'shipped'               => 3,
        'completed', 'delivered' => 4,
        default                 => 0,
    };

    if ($order->payment_status === 'paid' && $reachedStep < 1) {
        $reachedStep = 1;
    }
@endphp

<div class="order-timeline {{ $isCancelled ? 'cancelled' : '' }}">
    @if($isCancelled)
        <div class="alert alert-danger d-flex align-items-center">
            <i class="fas fa-times-circle fa-2x me-3"></i>
            <div>
                <strong>{{ __('Orden') }} {{ $orderStatus === 'refunded' ? __('reembolsada') : __('cancelada') }}</strong>
                <small class="d-block text-muted">{{ __('Esta orden no continuara el flujo normal de procesamiento.') }}</small>
            </div>
        </div>
    @else
        <div class="timeline-steps">
            @foreach($steps as $idx => $step)
                @php
                    $isCompleted = $idx < $reachedStep;
                    $isCurrent   = $idx === $reachedStep;
                @endphp
                <div class="timeline-step {{ $isCompleted ? 'completed' : ($isCurrent ? 'current' : 'pending') }}">
                    <div class="timeline-icon">
                        <i class="fas {{ $isCompleted ? 'fa-check' : $step['icon'] }}"></i>
                    </div>
                    <div class="timeline-label">
                        <strong>{{ __($step['label']) }}</strong>
                        @if($isCompleted && $step['date_field'] && $order->{$step['date_field']})
                            <small class="d-block text-muted">{{ $order->{$step['date_field']}->format('d/m/Y H:i') }}</small>
                        @elseif($isCurrent)
                            <small class="d-block text-success">{{ __('En proceso') }}</small>
                        @endif
                    </div>
                </div>
                @if($idx < count($steps) - 1)
                    <div class="timeline-connector {{ $idx < $reachedStep ? 'completed' : '' }}"></div>
                @endif
            @endforeach
        </div>
    @endif

    @if(! $isCancelled && $order->shipments->count() > 0)
        @foreach($order->shipments as $shipment)
            @if($shipment->tracking_id)
                <div class="alert alert-info mt-3 mb-0">
                    <strong>{{ __('Numero de seguimiento') }}:</strong> {{ $shipment->tracking_id }}
                    @if($shipment->shipping_company_name)
                        <br><small>{{ __('Transportadora') }}: {{ $shipment->shipping_company_name }}</small>
                    @endif
                    @if($shipment->tracking_link)
                        <br>
                        <a href="{{ $shipment->tracking_link }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-info mt-2">
                            <i class="fas fa-external-link-alt me-1"></i>{{ __('Rastrear envio') }}
                        </a>
                    @endif
                </div>
            @endif
        @endforeach
    @endif
</div>
