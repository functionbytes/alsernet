@extends('layouts.theme')

@section('title', 'Editar ubicacion')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Editar ubicacion'])

    <div class="card">
        <div class="card-body">
            <form action="{{ route('ecommerce.store-locators.update', $storeLocator) }}" method="POST">
                @csrf @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $storeLocator->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Direccion <span class="text-danger">*</span></label>
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" required>{{ old('address', $storeLocator->address) }}</textarea>
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Telefono <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $storeLocator->phone) }}" required>
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $storeLocator->email) }}">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Ciudad</label>
                        <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $storeLocator->city) }}">
                        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Estado / Provincia</label>
                        <input type="text" name="state" class="form-control @error('state') is-invalid @enderror" value="{{ old('state', $storeLocator->state) }}">
                        @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Pais</label>
                        <input type="text" name="country" class="form-control @error('country') is-invalid @enderror" value="{{ old('country', $storeLocator->country) }}">
                        @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_primary" value="1" class="form-check-input" id="is_primary" {{ old('is_primary', $storeLocator->is_primary) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_primary">Ubicacion principal</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_shipping_location" value="1" class="form-check-input" id="is_shipping_location" {{ old('is_shipping_location', $storeLocator->is_shipping_location) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_shipping_location">Ubicacion de envio</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('ecommerce.store-locators.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar ubicacion</button>
                </div>
            </form>
        </div>
    </div>
@endsection
