@extends('layouts.theme')

@section('title', 'Orden ' . $order->code)

@section('content')
    @include('core::components.card', ['title' => 'Orden ' . $order->code])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="row g-3">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Productos</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-end">Precio</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($item->product_image)
                                                    <img src="{{ $item->product_image }}" class="rounded" width="40" height="40" style="object-fit:cover;">
                                                @endif
                                                <span class="fw-semibold">{{ $item->product_name }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">{{ $item->qty }}</td>
                                        <td class="text-end">${{ number_format($item->price, 2) }}</td>
                                        <td class="text-end fw-semibold">${{ number_format($item->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end">Subtotal</td>
                                    <td class="text-end fw-semibold">${{ number_format($order->sub_total, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end">Envio</td>
                                    <td class="text-end">${{ number_format($order->shipping_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end">Impuestos</td>
                                    <td class="text-end">${{ number_format($order->tax_amount, 2) }}</td>
                                </tr>
                                @if($order->discount_amount > 0)
                                    <tr>
                                        <td colspan="3" class="text-end">Descuento</td>
                                        <td class="text-end text-danger">-${{ number_format($order->discount_amount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Total</td>
                                    <td class="text-end fw-bold">${{ number_format($order->total, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">Informacion</h5></div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Cliente:</strong> {{ $order->customer->name ?? 'Invitado' }}</p>
                        <p class="mb-1"><strong>Email:</strong> {{ $order->customer->email ?? '—' }}</p>
                        <p class="mb-1"><strong>Metodo de pago:</strong> {{ $order->payment_method ?? '—' }}</p>
                        <p class="mb-1"><strong>Estado de pago:</strong> <span class="badge bg-{{ $order->payment_status === 'paid' ? 'success' : 'warning' }}">{{ $order->payment_status }}</span></p>
                        <p class="mb-0"><strong>Fecha:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Actualizar estado</h5></div>
                    <div class="card-body">
                        <form action="{{ route('ecommerce.orders.status', $order) }}" method="POST">
                            @csrf
                            <select name="status" class="form-select mb-2">
                                <option value="pending" {{ $order->status->value === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                <option value="processing" {{ $order->status->value === 'processing' ? 'selected' : '' }}>Procesando</option>
                                <option value="shipped" {{ $order->status->value === 'shipped' ? 'selected' : '' }}>Enviado</option>
                                <option value="completed" {{ $order->status->value === 'completed' ? 'selected' : '' }}>Completado</option>
                                <option value="cancelled" {{ $order->status->value === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                            </select>
                            <button type="submit" class="btn btn-primary w-100">Actualizar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
