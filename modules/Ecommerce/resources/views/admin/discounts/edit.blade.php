@extends('layouts.theme')

@section('title', 'Editar descuento')

@section('content')
    @include('core::components.card', ['title' => 'Editar descuento'])
    <div class="widget-content searchable-container list">
        <form action="{{ route('ecommerce.discounts.update', $discount) }}" method="POST">
            @csrf @method('PUT')
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Titulo <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $discount->title) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Codigo</label>
                        <input type="text" name="code" class="form-control" value="{{ old('code', $discount->code) }}">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Fecha inicio</label>
                            <input type="datetime-local" name="start_date" class="form-control" value="{{ old('start_date', $discount->start_date?->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha fin</label>
                            <input type="datetime-local" name="end_date" class="form-control" value="{{ old('end_date', $discount->end_date?->format('Y-m-d\TH:i')) }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cantidad maxima de usos</label>
                        <input type="number" name="quantity" class="form-control" value="{{ old('quantity', $discount->quantity) }}">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Valor <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="value" class="form-control" value="{{ old('value', $discount->value) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select name="type" class="form-select">
                                <option value="fixed" {{ old('type', $discount->type->value) === 'fixed' ? 'selected' : '' }}>Monto fijo</option>
                                <option value="percentage" {{ old('type', $discount->type->value) === 'percentage' ? 'selected' : '' }}>Porcentaje</option>
                                <option value="free_shipping" {{ old('type', $discount->type->value) === 'free_shipping' ? 'selected' : '' }}>Envio gratis</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Precio minimo de orden</label>
                        <input type="number" step="0.01" name="min_order_price" class="form-control" value="{{ old('min_order_price', $discount->min_order_price) }}">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $discount->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Activo</label>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('ecommerce.discounts.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </div>
        </form>
    </div>
@endsection
