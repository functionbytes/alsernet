@extends('layouts.theme')

@section('title', 'Nueva orden')

@section('content')
    @include('core::components.card', ['title' => 'Nueva orden'])
    <div class="widget-content searchable-container list">
        <form action="{{ route('ecommerce.orders.store') }}" method="POST">
            @csrf
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Cliente</label>
                        <select name="customer_id" class="form-select">
                            <option value="">Invitado</option>
                            @foreach($customers as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado <span class="text-danger">*</span></label>
                        <select name="status" class="form-select">
                            <option value="pending">Pendiente</option>
                            <option value="processing">Procesando</option>
                            <option value="shipped">Enviado</option>
                            <option value="completed">Completado</option>
                            <option value="cancelled">Cancelado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Productos <span class="text-danger">*</span></label>
                        <div id="order-items">
                            <div class="row g-2 mb-2 item-row">
                                <div class="col-md-6">
                                    <select name="items[0][product_id]" class="form-select" required>
                                        <option value="">Seleccionar producto</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}" data-price="{{ $product->final_price }}">{{ $product->name }} - ${{ number_format($product->final_price, 2) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="items[0][qty]" class="form-control" placeholder="Cantidad" value="1" min="1" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" step="0.01" name="items[0][price]" class="form-control" placeholder="Precio" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Subtotal <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="sub_total" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Descuento</label>
                            <input type="number" step="0.01" name="discount_amount" class="form-control" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="total" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label">Metodo de pago</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">Efectivo</option>
                            <option value="transfer">Transferencia</option>
                            <option value="card">Tarjeta</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('ecommerce.orders.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </form>
    </div>
@endsection
