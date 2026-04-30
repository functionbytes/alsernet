@extends('layouts.theme')

@section('title', 'Venta flash')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Venta flash'])
@endsection

@section('content')
    @include('core::components.alerts')

    <form action="{{ route('settings.ecommerce.flash-sale.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="mb-3">
            <h5 class="fw-bold mb-1">Venta flash</h5>
            <div class="text-muted small">Ver y actualizar la configuración de venta flash</div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="flash_sale_enabled" value="1"
                        id="flash_sale_enabled"
                        {{ old('flash_sale_enabled', $settings['flash_sale_enabled'] ?? '') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="flash_sale_enabled">Habilitar venta flash</label>
                </div>
                @error('flash_sale_enabled')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Guardar ajustes
            </button>
        </div>

    </form>
@endsection
