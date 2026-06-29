@extends('layouts.theme')

@section('title', 'Venta flash')

@section('content')

    <form action="{{ route('settings.ecommerce.flash-sale.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="card">
            <div class="card-header border-bottom p-3">
                <h5 class="mb-0 fw-bold">Venta flash</h5>
                <small class="text-muted">Ver y actualizar la configuración de venta flash</small>
            </div>
            <div class="card-body">
                @include('core::components.alerts')

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="flash_sale_enabled" value="1"
                        id="flash_sale_enabled"
                        {{ old('flash_sale_enabled', $settings['flash_sale_enabled'] ?? '') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="flash_sale_enabled">Habilitar venta flash</label>
                    <div class="form-text text-muted">Permite crear ofertas temporales con cuenta regresiva</div>
                </div>
                @error('flash_sale_enabled')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar ajustes
                </button>
            </div>
        </div>

    </form>

@endsection
