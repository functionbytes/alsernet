@extends('ecommerce::layouts.shop-wowy')

@section('title', 'Mi cuenta')

@section('breadcrumb')
<div class="page-header breadcrumb-wrap">
    <div class="container">
        <div class="breadcrumb">
            <a href="{{ route('shop.index') }}" rel="nofollow"><i class="fa fa-home mr-5"></i>{{ __('Inicio') }}</a>
            <span></span> {{ __('Mi cuenta') }}
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
                    <div class="card-body text-center py-4">
                        <div class="mb-2">
                            <i class="fas fa-user-circle fa-4x text-muted"></i>
                        </div>
                        <h6 class="fw-bold mb-0">{{ $customer->name }}</h6>
                        <small class="text-muted">{{ $customer->email }}</small>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="{{ route('account.dashboard') }}" class="list-group-item list-group-item-action active">
                            <i class="fas fa-tachometer-alt me-2"></i>{{ __('Dashboard') }}
                        </a>
                        <a href="{{ route('account.orders') }}" class="list-group-item list-group-item-action">
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

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <h4 class="mb-4">{{ __('Hola,') }} {{ $customer->name }}</h4>

                {{-- Stats --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-body py-4">
                                <i class="fas fa-shopping-bag fa-2x mb-2" style="color:var(--color-brand)"></i>
                                <h4 class="fw-bold mb-1">{{ $recentOrders->count() }}</h4>
                                <small class="text-muted">{{ __('Total de ordenes') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-body py-4">
                                <i class="fas fa-clock fa-2x mb-2 text-warning"></i>
                                <h4 class="fw-bold mb-1">{{ $recentOrders->where('status', 'pending')->count() }}</h4>
                                <small class="text-muted">{{ __('Ordenes pendientes') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-body py-4">
                                <i class="fas fa-map-marker-alt fa-2x mb-2 text-success"></i>
                                <h4 class="fw-bold mb-1">
                                    {{ $customer->addresses()->count() > 0 ? __('Si') : __('No') }}
                                </h4>
                                <small class="text-muted">{{ __('Direccion guardada') }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Recent orders --}}
                <div class="card">
                    <div class="card-header p-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">{{ __('Ultimas ordenes') }}</h6>
                        <a href="{{ route('account.orders') }}" class="small text-decoration-none">{{ __('Ver todas') }}</a>
                    </div>
                    <div class="card-body p-0">
                        @if($recentOrders->isEmpty())
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-box-open fa-3x mb-3 d-block"></i>
                                {{ __('Aun no tienes ordenes.') }}
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('Codigo') }}</th>
                                            <th>{{ __('Fecha') }}</th>
                                            <th>{{ __('Total') }}</th>
                                            <th>{{ __('Estado') }}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentOrders->take(5) as $order)
                                            <tr>
                                                <td class="fw-semibold">{{ $order->code ?? '#'.$order->id }}</td>
                                                <td class="text-muted small">{{ $order->created_at->format('d/m/Y') }}</td>
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
                                                    <a href="{{ route('account.order-detail', $order) }}" class="btn btn-sm btn-outline-secondary">
                                                        {{ __('Ver') }}
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

            </div>

            @include('ecommerce::shop.partials._recommendations-section', [
                'products' => $recommendations,
                'title' => __('Recomendado para ti'),
                'icon' => 'fas fa-magic',
            ])
        </div>
    </div>
</section>
@endsection
