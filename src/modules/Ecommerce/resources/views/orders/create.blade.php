@extends('layouts.theme')

@section('title', 'Nueva orden')

@section('content')

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('ecommerce.orders.store') }}" method="POST">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva orden</h5>
                        <small class="text-muted">Crea manualmente una orden desde el panel.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Cliente</label>
                                <select name="customer_id" class="form-select">
                                    <option value="">Invitado</option>
                                    @foreach($customers as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Deja en blanco para orden de invitado</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado <span class="text-danger">*</span></label>
                                <select name="status" class="form-select">
                                    <option value="pending">Pendiente</option>
                                    <option value="processing">Procesando</option>
                                    <option value="shipped">Enviado</option>
                                    <option value="completed">Completado</option>
                                    <option value="cancelled">Cancelado</option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-3">Productos <span class="text-danger">*</span></h6>
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

                        <hr class="my-3">

                        <h6 class="fw-semibold mb-3">Totales</h6>
                        <div class="row g-3 mb-3">
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

                        <div class="mb-0">
                            <label class="form-label">Método de pago</label>
                            <select name="payment_method" class="form-select">
                                <option value="cash">Efectivo</option>
                                <option value="transfer">Transferencia</option>
                                <option value="card">Tarjeta</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Crear orden</button>
                        <a href="{{ route('ecommerce.orders.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-2">Crear orden manual</h6>
                    <p class="card-text text-muted">
                        Útil para registrar ventas que ocurrieron por canales fuera de la tienda online (teléfono, presencial, redes sociales).
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-2">Cliente vs invitado</h6>
                    <p class="card-text text-muted mb-0">
                        Si el comprador tiene cuenta, asígnaselo para que vea el pedido en su panel. Las órdenes de invitado solo se gestionan desde el admin.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-2">Totales</h6>
                    <p class="card-text text-muted mb-0">
                        Calcula manualmente subtotal, descuento y total. El sistema NO los recalcula automáticamente al guardar.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection
