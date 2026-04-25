@extends('layouts.theme')

@section('title', 'Editar cliente')

@section('content')
    @include('core::components.card', ['title' => 'Editar cliente'])
    <div class="widget-content searchable-container list">
        <form action="{{ route('ecommerce.customers.update', $customer) }}" method="POST">
            @csrf @method('PUT')
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $customer->email) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contrasena (dejar en blanco para no cambiar)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefono</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Estado</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ old('status', $customer->status->value) === 'active' ? 'selected' : '' }}>Activo</option>
                            <option value="inactive" {{ old('status', $customer->status->value) === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                            <option value="pending" {{ old('status', $customer->status->value) === 'pending' ? 'selected' : '' }}>Pendiente</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('ecommerce.customers.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </div>
        </form>
    </div>
@endsection
