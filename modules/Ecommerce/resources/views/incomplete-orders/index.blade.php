@extends('layouts.theme')

@section('title', 'Ordenes incompletas')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Ordenes incompletas'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 fw-bold">Ordenes incompletas</h5>
                    <p class="small mb-0 text-muted">Carritos abandonados durante el proceso de pago</p>
                </div>
                <form class="d-flex gap-2" method="GET" action="{{ route('ecommerce.incomplete-orders.index') }}">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar por código..." value="{{ request('search') }}" style="width:220px">
                    <button class="btn btn-sm btn-outline-secondary">Buscar</button>
                    @if(request('search'))
                        <a href="{{ route('ecommerce.incomplete-orders.index') }}" class="btn btn-sm btn-outline-danger">Limpiar</a>
                    @endif
                </form>
            </div>

            <div class="card-body p-0">
                @if ($orders->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">ID</th>
                                    <th>Cliente</th>
                                    <th>Monto</th>
                                    <th>Creado en</th>
                                    <th class="text-center" style="width:100px">Operaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders as $order)
                                    <tr>
                                        <td class="ps-3 fw-semibold">{{ $order->id }}</td>
                                        <td>
                                            @if($order->customer)
                                                <span class="fw-semibold small">{{ $order->customer->name }}</span>
                                                <div><small class="text-muted">{{ $order->customer->email }}</small></div>
                                            @else
                                                <span class="text-muted small">Invitado</span>
                                            @endif
                                        </td>
                                        <td class="fw-semibold">${{ number_format($order->total, 2) }}</td>
                                        <td><small class="text-muted">{{ $order->created_at->format('Y-m-d') }}</small></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('ecommerce.incomplete-orders.show', $order) }}">Ver detalles</a></li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.orders.destroy', $order) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar esta orden incompleta?')">Eliminar</button>
                                                        </form>
                                                    </li>
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
                        <i class="fas fa-basket-shopping fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay ordenes incompletas</h5>
                        <p class="text-muted small">Todas las ordenes han sido procesadas correctamente</p>
                    </div>
                @endif
            </div>

            @if ($orders->hasPages())
                <div class="card-footer">{{ $orders->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
