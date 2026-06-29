@extends('layouts.theme')

@section('title', 'Nueva dirección')

@section('content')

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('ecommerce.customers.addresses.store', $customer) }}" method="POST">
                    @csrf
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva dirección</h5>
                        <small class="text-muted">{{ $customer->name }} · {{ $customer->email }}</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required autofocus>
                            <small class="form-text text-muted">Nombre del destinatario</small>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Dirección <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" required>{{ old('address') }}</textarea>
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Ciudad</label>
                                <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city') }}">
                                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Estado / Provincia</label>
                                <input type="text" name="state" class="form-control @error('state') is-invalid @enderror" value="{{ old('state') }}">
                                @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Código postal</label>
                                <input type="text" name="zip_code" class="form-control @error('zip_code') is-invalid @enderror" value="{{ old('zip_code') }}">
                                @error('zip_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">País</label>
                                <input type="text" name="country" class="form-control @error('country') is-invalid @enderror" value="{{ old('country') }}">
                                @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo</label>
                                <select name="type" class="form-select">
                                    <option value="">— Sin especificar —</option>
                                    <option value="shipping" {{ old('type') === 'shipping' ? 'selected' : '' }}>Envío</option>
                                    <option value="billing" {{ old('type') === 'billing' ? 'selected' : '' }}>Facturación</option>
                                </select>
                                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-0">
                            <div class="form-check">
                                <input type="checkbox" name="is_default" value="1" class="form-check-input" id="is_default" {{ old('is_default') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_default">Marcar como dirección predeterminada</label>
                                <div class="form-text text-muted">Se usará automáticamente en checkout</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar dirección</button>
                        <a href="{{ route('ecommerce.customers.addresses.index', $customer) }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-2">Direcciones del cliente</h6>
                    <p class="card-text text-muted">
                        Cada cliente puede tener múltiples direcciones. La dirección predeterminada se selecciona automáticamente en el checkout.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-2">Tipo</h6>
                    <p class="card-text text-muted mb-0">
                        Diferencia entre dirección de <strong>envío</strong> (a dónde llega el pedido) y <strong>facturación</strong> (datos fiscales).
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection
