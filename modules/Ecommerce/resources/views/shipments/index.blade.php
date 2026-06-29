@extends('layouts.theme')

@section('title', 'Envíos')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Envíos'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        {{-- Tarjetas de resumen --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold">{{ $counts->sum() }}</div>
                        <div class="small text-muted">Total</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold text-warning">{{ $counts['pending'] ?? 0 }}</div>
                        <div class="small text-muted">Pendientes</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold text-info">{{ $counts['delivering'] ?? 0 }}</div>
                        <div class="small text-muted">En camino</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center h-100">
                    <div class="card-body py-3">
                        <div class="fs-4 fw-bold text-success">{{ $counts['delivered'] ?? 0 }}</div>
                        <div class="small text-muted">Entregados</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h5 class="mb-1 fw-bold">Envíos</h5>
                        <p class="small mb-0 text-muted">Administra los envíos de órdenes</p>
                    </div>
                    <form class="d-flex gap-2 flex-wrap align-items-center" method="GET" action="{{ route('ecommerce.shipments.index') }}">
                        <input type="text" name="search" class="form-control form-control-sm"
                            placeholder="Buscar por código de orden..." value="{{ request('search') }}" style="width:220px">
                        <select name="status" class="form-select form-select-sm" style="width:150px">
                            <option value="">Todos los estados</option>
                            <option value="pending"    {{ request('status') === 'pending'    ? 'selected' : '' }}>Pendiente</option>
                            <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Procesando</option>
                            <option value="delivering" {{ request('status') === 'delivering' ? 'selected' : '' }}>En camino</option>
                            <option value="delivered"  {{ request('status') === 'delivered'  ? 'selected' : '' }}>Entregado</option>
                            <option value="cancelled"  {{ request('status') === 'cancelled'  ? 'selected' : '' }}>Cancelado</option>
                        </select>
                        <button class="btn btn-sm btn-primary">Buscar</button>
                        @if(request('search') || request('status'))
                            <a href="{{ route('ecommerce.shipments.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
                        @endif
                    </form>
                </div>
            </div>

            <div class="card-body p-0">
                @if($shipments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">ID</th>
                                    <th>Orden</th>
                                    <th>Cliente</th>
                                    <th>Empresa</th>
                                    <th>Tracking</th>
                                    <th>Monto envío</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th class="text-center" style="width:80px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($shipments as $shipment)
                                    @php
                                        $badgeClass = match($shipment->status) {
                                            'delivered'  => 'bg-success',
                                            'delivering' => 'bg-info',
                                            'processing' => 'bg-primary',
                                            'cancelled'  => 'bg-danger',
                                            default      => 'bg-warning text-dark',
                                        };
                                        $statusLabel = match($shipment->status) {
                                            'delivered'  => 'Entregado',
                                            'delivering' => 'En camino',
                                            'processing' => 'Procesando',
                                            'cancelled'  => 'Cancelado',
                                            default      => 'Pendiente',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="ps-3">
                                            <small class="text-muted">{{ $shipment->shipment_id ?? '#' . $shipment->id }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('ecommerce.orders.show', $shipment->order) }}"
                                                class="text-decoration-none fw-semibold">
                                                {{ $shipment->order?->code ?? '—' }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($shipment->order?->customer)
                                                <div class="fw-semibold small">{{ $shipment->order->customer->name }}</div>
                                                <small class="text-muted">{{ $shipment->order->customer->email }}</small>
                                                @if($shipment->order->customer->phone)
                                                    <div><small class="text-muted">{{ $shipment->order->customer->phone }}</small></div>
                                                @endif
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $shipment->shipping_company_name ?? '—' }}</small>
                                        </td>
                                        <td>
                                            @if($shipment->tracking_id)
                                                @if($shipment->tracking_link)
                                                    <a href="{{ $shipment->tracking_link }}" target="_blank"
                                                        class="small text-decoration-none">{{ $shipment->tracking_id }}</a>
                                                @else
                                                    <small class="text-muted">{{ $shipment->tracking_id }}</small>
                                                @endif
                                            @else
                                                <small class="text-muted">No disponible</small>
                                            @endif
                                        </td>
                                        <td class="fw-semibold">${{ number_format($shipment->price, 2) }}</td>
                                        <td><span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span></td>
                                        <td><small class="text-muted">{{ $shipment->created_at->format('d/m/Y') }}</small></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('ecommerce.shipments.show', $shipment) }}">Ver</a></li>
                                                    <li><a class="dropdown-item" href="{{ route('ecommerce.shipments.edit', $shipment) }}">Editar</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-truck fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay envíos</h5>
                        <p class="text-muted small">Los envíos aparecerán aquí cuando se creen desde una orden</p>
                    </div>
                @endif
            </div>

            @if($shipments->hasPages())
                <div class="card-footer">{{ $shipments->appends(request()->input())->links() }}</div>
            @endif
        </div>
    </div>
@endsection
