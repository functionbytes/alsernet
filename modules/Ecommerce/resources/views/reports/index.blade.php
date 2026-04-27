@extends('layouts.theme')

@section('title', 'Reportes')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Reportes'])

    @include('core::components.alerts')

    {{-- Report navigation --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <ul class="nav nav-pills nav-fill">
                <li class="nav-item"><a class="nav-link active" href="{{ route('ecommerce.reports.index') }}">Resumen</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.comparison') }}">Comparativa</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.funnel') }}">Embudo</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.customer-ltv') }}">LTV clientes</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.abandoned-carts') }}">Carritos abandonados</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('ecommerce.reports.search') }}">Busquedas</a></li>
            </ul>
        </div>
    </div>

    {{-- Date range filter --}}
    <div class="card mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('ecommerce.reports.index') }}" class="d-flex align-items-end gap-3 flex-wrap">
                <div>
                    <label class="form-label mb-1">Fecha inicio</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div>
                    <label class="form-label mb-1">Fecha fin</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                    <a href="{{ route('ecommerce.reports.index') }}" class="btn btn-secondary ms-1">Hoy mes</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Stats cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded d-flex align-items-center justify-content-center flex-shrink-0 bg-success bg-opacity-10" style="width:48px;height:48px">
                            <i class="fas fa-dollar-sign fa-lg text-success"></i>
                        </div>
                        <div>
                            <div class="fs-5 fw-bold">${{ number_format($totalRevenue, 2) }}</div>
                            <div class="small text-muted">Ingresos totales</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded d-flex align-items-center justify-content-center flex-shrink-0 bg-primary bg-opacity-10" style="width:48px;height:48px">
                            <i class="fas fa-receipt fa-lg text-primary"></i>
                        </div>
                        <div>
                            <div class="fs-5 fw-bold">{{ number_format($totalOrders) }}</div>
                            <div class="small text-muted">Total ordenes</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded d-flex align-items-center justify-content-center flex-shrink-0 bg-success bg-opacity-10" style="width:48px;height:48px">
                            <i class="fas fa-check-circle fa-lg text-success"></i>
                        </div>
                        <div>
                            <div class="fs-5 fw-bold">{{ number_format($paidOrders) }}</div>
                            <div class="small text-muted">Ordenes pagadas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded d-flex align-items-center justify-content-center flex-shrink-0 bg-warning bg-opacity-10" style="width:48px;height:48px">
                            <i class="fas fa-clock fa-lg text-warning"></i>
                        </div>
                        <div>
                            <div class="fs-5 fw-bold">{{ number_format($pendingOrders) }}</div>
                            <div class="small text-muted">Ordenes pendientes</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Status breakdown --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header p-4 border-bottom">
                    <h6 class="fw-bold mb-0">Ordenes por estado</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @php
                            $statusItems = [
                                ['label' => 'Pendientes',   'value' => $pendingOrders,    'color' => 'warning'],
                                ['label' => 'Procesando',   'value' => $processingOrders, 'color' => 'info'],
                                ['label' => 'Completadas',  'value' => $completedOrders,  'color' => 'success'],
                                ['label' => 'Canceladas',   'value' => $cancelledOrders,  'color' => 'danger'],
                            ];
                        @endphp
                        @foreach ($statusItems as $item)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                                <span class="text-muted">{{ $item['label'] }}</span>
                                <span class="badge bg-{{ $item['color'] }} rounded-pill">{{ $item['value'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header p-4 border-bottom">
                    <h6 class="fw-bold mb-0">Productos con stock bajo</h6>
                </div>
                <div class="card-body p-0">
                    @if ($lowStockProducts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-center">Stock</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lowStockProducts as $product)
                                        <tr>
                                            <td>
                                                <a href="{{ route('ecommerce.products.edit', $product) }}" class="text-decoration-none fw-semibold">{{ $product->name }}</a>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $product->quantity > 0 ? 'warning' : 'danger' }}">{{ $product->quantity }}</span>
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('ecommerce.products.edit', $product) }}" class="btn btn-sm btn-light">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-2x text-success opacity-50 mb-2"></i>
                            <p class="text-muted mb-0 small">Todos los productos tienen stock suficiente</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Top selling products --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header p-4 border-bottom">
                    <h6 class="fw-bold mb-0">Productos mas vendidos</h6>
                </div>
                <div class="card-body p-0">
                    @if ($topProducts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-center">Cant.</th>
                                        <th class="text-end">Ingresos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($topProducts as $item)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    @if ($item->product_image)
                                                        <img src="{{ $item->product_image }}" class="rounded object-fit-cover flex-shrink-0" width="32" height="32">
                                                    @else
                                                        <div class="bg-light rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px">
                                                            <i class="fas fa-box fa-xs text-muted"></i>
                                                        </div>
                                                    @endif
                                                    <span class="fw-semibold small">{{ $item->product_name }}</span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary rounded-pill">{{ $item->total_qty }}</span>
                                            </td>
                                            <td class="text-end fw-semibold">${{ number_format($item->total_revenue, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-box-open fa-2x text-muted opacity-50 mb-2"></i>
                            <p class="text-muted mb-0 small">Sin ventas en el periodo seleccionado</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Recent orders --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header p-4 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">Ordenes recientes</h6>
                    <a href="{{ route('ecommerce.orders.index') }}" class="btn btn-sm btn-light">Ver todas</a>
                </div>
                <div class="card-body p-0">
                    @if ($recentOrders->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Cliente</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentOrders as $order)
                                        <tr>
                                            <td>
                                                <a href="{{ route('ecommerce.orders.show', $order) }}" class="fw-semibold text-decoration-none small">{{ $order->code }}</a>
                                            </td>
                                            <td><small class="text-muted">{{ $order->customer->name ?? 'Invitado' }}</small></td>
                                            <td class="fw-semibold small">${{ number_format($order->total, 2) }}</td>
                                            <td>
                                                <span class="badge bg-{{ $order->status->value === 'completed' ? 'success' : ($order->status->value === 'pending' ? 'warning' : ($order->status->value === 'cancelled' ? 'danger' : 'info')) }}">
                                                    {{ $order->status->value }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-receipt fa-2x text-muted opacity-50 mb-2"></i>
                            <p class="text-muted mb-0 small">Sin ordenes en el periodo seleccionado</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
