@extends('layouts.theme')

@section('title', 'Editar orden')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar orden'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <form action="{{ route('ecommerce.orders.update', $order) }}" method="POST">
            @csrf @method('PUT')
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Estado <span class="text-danger">*</span></label>
                        <select name="status" class="form-select">
                            <option value="pending" {{ old('status', $order->status->value) === 'pending' ? 'selected' : '' }}>Pendiente</option>
                            <option value="processing" {{ old('status', $order->status->value) === 'processing' ? 'selected' : '' }}>Procesando</option>
                            <option value="shipped" {{ old('status', $order->status->value) === 'shipped' ? 'selected' : '' }}>Enviado</option>
                            <option value="completed" {{ old('status', $order->status->value) === 'completed' ? 'selected' : '' }}>Completado</option>
                            <option value="cancelled" {{ old('status', $order->status->value) === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Metodo de envio</label>
                        <input type="text" name="shipping_method" class="form-control" value="{{ old('shipping_method', $order->shipping_method) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado de pago <span class="text-danger">*</span></label>
                        <select name="payment_status" class="form-select">
                            <option value="pending" {{ old('payment_status', $order->payment_status) === 'pending' ? 'selected' : '' }}>Pendiente</option>
                            <option value="paid" {{ old('payment_status', $order->payment_status) === 'paid' ? 'selected' : '' }}>Pagado</option>
                            <option value="failed" {{ old('payment_status', $order->payment_status) === 'failed' ? 'selected' : '' }}>Fallido</option>
                            <option value="refunded" {{ old('payment_status', $order->payment_status) === 'refunded' ? 'selected' : '' }}>Reembolsado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nota del cliente</label>
                        <textarea name="customer_note" class="form-control" rows="2">{{ old('customer_note', $order->customer_note) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nota admin</label>
                        <textarea name="admin_note" class="form-control" rows="2">{{ old('admin_note', $order->admin_note) }}</textarea>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('ecommerce.orders.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </div>
        </form>
    </div>
@endsection
