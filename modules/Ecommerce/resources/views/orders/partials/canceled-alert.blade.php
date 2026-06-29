@if($order->status->value === 'cancelled')
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
    <i class="fas fa-ban"></i>
    <div>Esta orden fue cancelada{{ $order->cancelled_at ? ' el ' . $order->cancelled_at->format('d/m/Y') : '' }}.</div>
</div>
@endif
