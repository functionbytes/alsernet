@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Mis ordenes')

@section('breadcrumb')
<div class="page-header breadcrumb-wrap">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('shop.index') }}" rel="nofollow"><i class="fa fa-home mr-5"></i>{{ __('Inicio') }}</a>
            <span></span> <a href="{{ route('account.dashboard') }}">{{ __('Mi cuenta') }}</a>
            <span></span> {{ __('Mis ordenes') }}
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
                        <a href="{{ route('account.saved-searches.index') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-bell me-2"></i>{{ __('Busquedas guardadas') }}
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
                <div class="card">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">{{ __('Mis ordenes') }}</h6>
                    </div>
                    <div class="card-body p-0">
                        @if($orders->isEmpty())
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-box-open fa-3x mb-3 d-block"></i>
                                {{ __('Aun no tienes ordenes.') }}
                                <div class="mt-3">
                                    <a href="{{ route('shop.index') }}" class="btn btn-primary btn-sm">{{ __('Ir a la tienda') }}</a>
                                </div>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('Codigo') }}</th>
                                            <th>{{ __('Fecha') }}</th>
                                            <th>{{ __('Productos') }}</th>
                                            <th>{{ __('Total') }}</th>
                                            <th>{{ __('Estado') }}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($orders as $order)
                                            <tr>
                                                <td class="fw-semibold">{{ $order->code ?? '#'.$order->id }}</td>
                                                <td class="text-muted small">{{ $order->created_at->format('d/m/Y') }}</td>
                                                <td class="text-muted small">{{ $order->items->count() }} {{ __('items') }}</td>
                                                <td class="fw-semibold">${{ number_format($order->total, 2) }}</td>
                                                <td>
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
                                                    <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="{{ route('account.order-detail', $order) }}" class="btn btn-sm btn-outline-secondary">
                                                            {{ __('Ver detalle') }}
                                                        </a>
                                                        <form method="POST" action="{{ route('account.order.reorder', $order) }}">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="{{ __('Comprar nuevamente') }}">
                                                                <i class="fas fa-redo me-1"></i>{{ __('Reordenar') }}
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            @if($orders->hasPages())
                                <div class="p-3 border-top">
                                    {{ $orders->links() }}
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection
