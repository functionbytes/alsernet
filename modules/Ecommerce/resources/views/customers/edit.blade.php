@extends('layouts.theme')

@section('title', 'Editar cliente')

@section('content')

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('ecommerce.customers.update', $customer) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar cliente</h5>
                        <small class="text-muted">{{ $customer->name }} · {{ $customer->email }}</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $customer->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $customer->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                            <small class="form-text text-muted">Dejar en blanco para no cambiar</small>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $customer->phone) }}">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-select">
                                <option value="active" {{ old('status', $customer->status->value) === 'active' ? 'selected' : '' }}>Activo</option>
                                <option value="inactive" {{ old('status', $customer->status->value) === 'inactive' ? 'selected' : '' }}>Inactivo</option>
                                <option value="pending" {{ old('status', $customer->status->value) === 'pending' ? 'selected' : '' }}>Pendiente</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Actualizar</button>
                        <a href="{{ route('ecommerce.customers.show', $customer) }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title mb-2">Accesos rápidos</h6>
                    <div class="d-grid gap-2">
                        <a href="{{ route('ecommerce.customers.show', $customer) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-user me-1"></i> Ver perfil
                        </a>
                        <a href="{{ route('ecommerce.customers.addresses.index', $customer) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-map-marker-alt me-1"></i> Direcciones
                        </a>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-2">Cambio de email</h6>
                    <p class="card-text text-muted mb-0">
                        Si cambias el email, asegúrate de comunicárselo al cliente. Su próximo inicio de sesión deberá hacerse con el nuevo email.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-2">Contraseña</h6>
                    <p class="card-text text-muted mb-0">
                        Dejar en blanco mantiene la contraseña actual. Si la cambias, comunícasela al cliente de forma segura.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection
