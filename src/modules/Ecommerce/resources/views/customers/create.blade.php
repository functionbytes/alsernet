@extends('layouts.theme')

@section('title', 'Nuevo cliente')

@section('content')

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('ecommerce.customers.store') }}" method="POST">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nuevo cliente</h5>
                        <small class="text-muted">Registra un cliente manualmente desde el panel.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required autofocus>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            <small class="form-text text-muted">Mínimo 8 caracteres</small>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-select">
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                                <option value="pending">Pendiente</option>
                            </select>
                            <small class="form-text text-muted">Los clientes pendientes deben confirmar su email</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cliente</button>
                        <a href="{{ route('ecommerce.customers.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-2">Crear cliente manualmente</h6>
                    <p class="card-text text-muted">
                        Útil para clientes que llegaron por canales externos (teléfono, presencial) o para pruebas. Los clientes registrados desde la tienda se crean automáticamente.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-2">Estados</h6>
                    <p class="card-text text-muted mb-0">
                        <strong>Activo:</strong> puede iniciar sesión y comprar.<br>
                        <strong>Inactivo:</strong> bloqueado.<br>
                        <strong>Pendiente:</strong> debe confirmar email.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection
