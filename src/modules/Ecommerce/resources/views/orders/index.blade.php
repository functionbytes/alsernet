@extends('layouts.theme')

@section('title', 'Ordenes')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Ordenes'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Ordenes</h5>
                        <p class="small mb-0 text-muted">Administra las ordenes de compra</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('ecommerce.orders.export') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-file-export me-1"></i> Exportar
                        </a>
                        <a href="{{ route('ecommerce.orders.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nueva orden
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if($orders->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Codigo</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Pago</th>
                                    <th>Fecha</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $order)
                                    <tr>
                                        <td><a href="{{ route('ecommerce.orders.show', $order) }}" class="fw-semibold text-decoration-none">{{ $order->code }}</a></td>
                                        <td><small class="text-muted">{{ $order->customer->name ?? 'Invitado' }}</small></td>
                                        <td class="fw-semibold">${{ number_format($order->total, 2) }}</td>
                                        <td><span class="{{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span></td>
                                        <td>
                                            @php
                                                $payLabels = ['paid'=>'Pagado','pending'=>'Pendiente','failed'=>'Fallido','refunded'=>'Reembolsado'];
                                                $payColors = ['paid'=>'success','pending'=>'warning','failed'=>'danger','refunded'=>'secondary'];
                                                $ps = $order->payment_status ?? 'pending';
                                            @endphp
                                            <span class="badge bg-{{ $payColors[$ps] ?? 'secondary' }} {{ $ps === 'pending' ? 'text-dark' : '' }}">{{ $payLabels[$ps] ?? $ps }}</span>
                                        </td>
                                        <td><small class="text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</small></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('ecommerce.orders.show', $order) }}">Ver</a></li>
                                                    <li><a class="dropdown-item" href="{{ route('ecommerce.orders.edit', $order) }}">Editar</a></li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.orders.destroy', $order) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar esta orden?')">Eliminar</button>
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
                        <i class="fas fa-receipt fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay ordenes</h5>
                        <a href="{{ route('ecommerce.orders.create') }}" class="btn btn-primary">Crear primera orden</a>
                    </div>
                @endif
            </div>

            @if($orders->hasPages())
                <div class="card-footer">{{ $orders->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
