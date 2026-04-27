@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Orden #' . ($order->code ?? $order->id))

@push('styles')
<style>
.order-timeline { padding: 16px 0; }
.timeline-steps {
    display: flex;
    align-items: flex-start;
    overflow-x: auto;
    padding: 8px 0 16px;
}
.timeline-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 0 0 auto;
    min-width: 100px;
    text-align: center;
}
.timeline-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #e9ecef;
    color: #adb5bd;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-bottom: 8px;
    transition: all 0.3s ease;
}
.timeline-step.completed .timeline-icon { background: #90bb13; color: #fff; }
.timeline-step.current .timeline-icon {
    background: #fff;
    color: #90bb13;
    border: 3px solid #90bb13;
    box-shadow: 0 0 0 4px rgba(144, 187, 19, 0.2);
    animation: pulse-current 2s infinite;
}
@keyframes pulse-current {
    0%, 100% { box-shadow: 0 0 0 4px rgba(144, 187, 19, 0.2); }
    50%       { box-shadow: 0 0 0 8px rgba(144, 187, 19, 0); }
}
.timeline-step.pending { opacity: 0.4; }
.timeline-label { font-size: 0.875rem; }
.timeline-connector {
    flex: 1 1 auto;
    height: 3px;
    background: #e9ecef;
    margin: 23px 4px 0;
    min-width: 30px;
    transition: background 0.3s;
}
.timeline-connector.completed { background: #90bb13; }
@media (max-width: 575px) {
    .timeline-step { min-width: 80px; }
    .timeline-icon { width: 36px; height: 36px; font-size: 1rem; }
    .timeline-label { font-size: 0.75rem; }
}
</style>
@endpush

@section('breadcrumb')
<div class="page-header breadcrumb-wrap">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('shop.index') }}" rel="nofollow"><i class="fa fa-home mr-5"></i>{{ __('Inicio') }}</a>
            <span></span> <a href="{{ route('account.dashboard') }}">{{ __('Mi cuenta') }}</a>
            <span></span> <a href="{{ route('account.orders') }}">{{ __('Mis ordenes') }}</a>
            <span></span> {{ $order->code ?? '#'.$order->id }}
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="mt-60 mb-60">
    <div class="container">
        <div class="row">

            {{-- Sidebar --}}
            <div class="col-lg-3 mb-4">
                <div class="card">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('account.dashboard') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-tachometer-alt me-2"></i>{{ __('Dashboard') }}
                        </a>
                        <a href="{{ route('account.orders') }}" class="list-group-item list-group-item-action active">
                            <i class="fas fa-box me-2"></i>{{ __('Mis ordenes') }}
                        </a>
                        <a href="{{ route('account.profile') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-user me-2"></i>{{ __('Mi perfil') }}
                        </a>
                        <a href="{{ route('account.addresses') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-map-marker-alt me-2"></i>{{ __('Mis direcciones') }}
                        </a>
                        <a href="{{ route('ecommerce.logout') }}"
                           class="list-group-item list-group-item-action text-danger"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt me-2"></i>{{ __('Cerrar sesion') }}
                        </a>
                        <form id="logout-form" action="{{ route('ecommerce.logout') }}" method="POST" class="d-none">@csrf</form>
                    </div>
                </div>
            </div>

            {{-- Main content --}}
            <div class="col-lg-9">

                {{-- Header --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0 fw-bold">{{ __('Orden') }} #{{ $order->code ?? $order->id }}</h5>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                @php
                                    $statusValue = is_object($order->status) ? $order->status->value : $order->status;
                                    $statusLabel = is_object($order->status) && method_exists($order->status, 'label') ? $order->status->label() : ucfirst($statusValue);
                                    $badgeClass = match($statusValue) {
                                        'pending' => 'bg-warning',
                                        'processing' => 'bg-info',
                                        'shipped' => 'bg-primary',
                                        'completed', 'delivered' => 'bg-success',
                                        'cancelled', 'refunded' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }} fs-6">{{ $statusLabel }}</span>
                                <small class="text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</small>
                                <form method="POST" action="{{ route('account.order.reorder', $order) }}" class="mb-0">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-cart-plus me-1"></i>{{ __('Volver a comprar') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pb-0">
                        @include('ecommerce::shop.partials._order-timeline', ['order' => $order])
                    </div>
                </div>

                {{-- Products --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">{{ __('Productos') }}</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th colspan="2">{{ __('Producto') }}</th>
                                        <th class="text-center">{{ __('Cantidad') }}</th>
                                        <th class="text-end">{{ __('Precio') }}</th>
                                        <th class="text-end">{{ __('Subtotal') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                        <tr>
                                            <td style="width:60px">
                                                @if($item->product && $item->product->thumbnail)
                                                    <img src="{{ asset('storage/'.$item->product->thumbnail) }}"
                                                         alt="{{ $item->product->name }}"
                                                         class="rounded"
                                                         style="width:50px;height:50px;object-fit:cover">
                                                @else
                                                    <div class="rounded bg-light d-flex align-items-center justify-content-center"
                                                         style="width:50px;height:50px">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="fw-semibold">{{ $item->product->name ?? $item->name ?? '—' }}</span>
                                                @if(!empty($item->options))
                                                    <small class="d-block text-muted">{{ is_array($item->options) ? implode(', ', $item->options) : $item->options }}</small>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $item->qty }}</td>
                                            <td class="text-end">${{ number_format($item->price, 2) }}</td>
                                            <td class="text-end fw-semibold">${{ number_format($item->price * $item->qty, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row g-4">

                    {{-- Shipping address --}}
                    @if(isset($order->shippingAddress) && $order->shippingAddress)
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header p-3 border-bottom">
                                    <h6 class="mb-0 fw-bold">{{ __('Direccion de envio') }}</h6>
                                </div>
                                <div class="card-body">
                                    <address class="mb-0 text-muted small">
                                        <strong class="text-dark">{{ $order->shippingAddress->name }}</strong><br>
                                        @if($order->shippingAddress->phone)
                                            {{ $order->shippingAddress->phone }}<br>
                                        @endif
                                        {{ $order->shippingAddress->address }}<br>
                                        {{ $order->shippingAddress->city }}
                                        @if($order->shippingAddress->zip_code), {{ $order->shippingAddress->zip_code }}@endif<br>
                                        {{ $order->shippingAddress->country }}
                                    </address>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Financial summary --}}
                    <div class="col-md-{{ isset($order->shippingAddress) && $order->shippingAddress ? '6' : '12' }}">
                        <div class="card h-100">
                            <div class="card-header p-3 border-bottom">
                                <h6 class="mb-0 fw-bold">{{ __('Resumen') }}</h6>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-7 fw-normal text-muted">{{ __('Subtotal') }}</dt>
                                    <dd class="col-5 text-end">${{ number_format($order->subtotal ?? 0, 2) }}</dd>

                                    <dt class="col-7 fw-normal text-muted">{{ __('Envio') }}</dt>
                                    <dd class="col-5 text-end">${{ number_format($order->shipping_cost ?? 0, 2) }}</dd>

                                    @if(($order->tax ?? 0) > 0)
                                        <dt class="col-7 fw-normal text-muted">{{ __('Impuesto') }}</dt>
                                        <dd class="col-5 text-end">${{ number_format($order->tax, 2) }}</dd>
                                    @endif

                                    @if(($order->discount ?? 0) > 0)
                                        <dt class="col-7 fw-normal text-muted">{{ __('Descuento') }}</dt>
                                        <dd class="col-5 text-end text-success">-${{ number_format($order->discount, 2) }}</dd>
                                    @endif

                                    <dt class="col-7 pt-2 border-top fw-bold">{{ __('Total') }}</dt>
                                    <dd class="col-5 text-end pt-2 border-top fw-bold">${{ number_format($order->total, 2) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Order history --}}
                @if(isset($order->histories) && $order->histories->count())
                    <div class="card mt-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">{{ __('Historial de estados') }}</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                @foreach($order->histories->sortByDesc('created_at') as $history)
                                    <li class="d-flex gap-3 mb-3">
                                        <div class="flex-shrink-0">
                                            <span class="badge bg-secondary rounded-circle p-2">
                                                <i class="fas fa-circle" style="font-size:8px"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="fw-semibold">{{ ucfirst($history->status) }}</span>
                                            @if($history->note)
                                                <span class="text-muted">— {{ $history->note }}</span>
                                            @endif
                                            <small class="d-block text-muted">{{ $history->created_at->format('d/m/Y H:i') }}</small>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <div class="mt-4">
                    <a href="{{ route('account.orders') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>{{ __('Volver a mis ordenes') }}
                    </a>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
