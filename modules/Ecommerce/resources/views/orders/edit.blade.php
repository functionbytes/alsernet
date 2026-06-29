@extends('layouts.theme')

@section('title', 'Editar orden')

@section('content')

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('ecommerce.orders.update', $order) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar orden</h5>
                        <small class="text-muted">{{ $order->code ?? '#'.$order->id }}</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Estado de orden <span class="text-danger">*</span></label>
                                <select name="status" class="form-select">
                                    <option value="pending" {{ old('status', $order->status->value) === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="processing" {{ old('status', $order->status->value) === 'processing' ? 'selected' : '' }}>Procesando</option>
                                    <option value="shipped" {{ old('status', $order->status->value) === 'shipped' ? 'selected' : '' }}>Enviado</option>
                                    <option value="completed" {{ old('status', $order->status->value) === 'completed' ? 'selected' : '' }}>Completado</option>
                                    <option value="cancelled" {{ old('status', $order->status->value) === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado de pago <span class="text-danger">*</span></label>
                                <select name="payment_status" class="form-select">
                                    <option value="pending" {{ old('payment_status', $order->payment_status) === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="paid" {{ old('payment_status', $order->payment_status) === 'paid' ? 'selected' : '' }}>Pagado</option>
                                    <option value="failed" {{ old('payment_status', $order->payment_status) === 'failed' ? 'selected' : '' }}>Fallido</option>
                                    <option value="refunded" {{ old('payment_status', $order->payment_status) === 'refunded' ? 'selected' : '' }}>Reembolsado</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Método de envío</label>
                            <input type="text" name="shipping_method" class="form-control" value="{{ old('shipping_method', $order->shipping_method) }}">
                        </div>

                        <hr class="my-3">

                        <div class="mb-3">
                            <label class="form-label">Nota del cliente</label>
                            <textarea name="customer_note" class="form-control" rows="3">{{ old('customer_note', $order->customer_note) }}</textarea>
                            <small class="form-text text-muted">Visible para el cliente</small>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Nota interna (admin)</label>
                            <textarea name="admin_note" class="form-control" rows="3">{{ old('admin_note', $order->admin_note) }}</textarea>
                            <small class="form-text text-muted">Solo visible internamente</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Actualizar</button>
                        <a href="{{ route('ecommerce.orders.show', $order) }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title mb-2">Resumen</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-1"><strong>Cliente:</strong> {{ $order->customer?->name ?? 'Invitado' }}</li>
                        <li class="mb-1"><strong>Total:</strong> ${{ number_format($order->total ?? 0, 2) }}</li>
                        <li class="mb-1"><strong>Creada:</strong> {{ $order->created_at?->format('d/m/Y H:i') }}</li>
                        @if($order->code)
                            <li><strong>Código:</strong> <code>{{ $order->code }}</code></li>
                        @endif
                    </ul>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-2">Cambios de estado</h6>
                    <p class="card-text text-muted mb-0">
                        Al cambiar el estado se notificará al cliente automáticamente si tiene activado el tracking de orden.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-2">Notas</h6>
                    <p class="card-text text-muted mb-0">
                        Las notas del cliente son visibles desde su panel. Las notas internas solo las ve el admin.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection
