@extends('layouts.theme')

@section('title', 'Ecommerce Dashboard')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card bg-light-primary h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-2">Productos</h6>
                        <h4 class="mb-1 fw-bold">{{ number_format($stats['products_count']) }}</h4>
                        <small class="text-muted">En catalogo</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-light-success h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-2">Ordenes</h6>
                        <h4 class="mb-1 fw-bold">{{ number_format($stats['orders_count']) }}</h4>
                        <small class="text-muted">Total ordenes</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-light-info h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-2">Clientes</h6>
                        <h4 class="mb-1 fw-bold">{{ number_format($stats['customers_count']) }}</h4>
                        <small class="text-muted">Registrados</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card bg-light-warning h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-2">Ingresos</h6>
                        <h4 class="mb-1 fw-bold">${{ number_format($stats['total_revenue'], 2) }}</h4>
                        <small class="text-muted">Ventas totales</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Ordenes recientes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Cliente</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($stats['recent_orders'] as $order)
                                        <tr>
                                            <td><a href="{{ route('ecommerce.orders.show', $order) }}" class="fw-semibold">{{ $order->code }}</a></td>
                                            <td>{{ $order->customer->name ?? 'Invitado' }}</td>
                                            <td>${{ number_format($order->total, 2) }}</td>
                                            <td><span class="badge bg-{{ $order->status->value === 'completed' ? 'success' : ($order->status->value === 'pending' ? 'warning' : 'secondary') }}">{{ $order->status->value }}</span></td>
                                            <td><small class="text-muted">{{ $order->created_at->format('d/m/Y') }}</small></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted">No hay ordenes recientes</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Stock bajo</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @forelse($stats['low_stock_products'] as $product)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>{{ $product->name }}</span>
                                    <span class="badge bg-danger">{{ $product->quantity }} restantes</span>
                                </li>
                            @empty
                                <li class="list-group-item text-muted text-center">No hay productos con stock bajo</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
