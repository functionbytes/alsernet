@extends('layouts.theme')

@section('title', 'Estandar y formato')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Estandar y formato'])
    @include('core::components.alerts')

    @php
        $prefix = old('store_order_prefix', $settings['store_order_prefix'] ?? '');
        $suffix = old('store_order_suffix', $settings['store_order_suffix'] ?? '');
    @endphp

    <form action="{{ route('settings.ecommerce.standard-and-format.update') }}" method="POST">
        @csrf
        @method('PATCH')

        <div class="mb-3">
            <h5 class="fw-bold mb-1">Formato estandar</h5>
            <div class="text-muted small">Los estándares y formatos se utilizan para calcular cosas como precios de productos, pesos de envío y tiempos de pedido.</div>
        </div>

        <div class="card mb-4">
            <div class="card-body">

                <h6 class="fw-semibold mb-1">Editar formato de código de pedido (opcional)</h6>
                <p class="text-muted small mb-3">El código de pedido predeterminado comienza en: número. Puede cambiar la cadena de inicio o fin para crear el código de pedido que desee, por ejemplo "DH-:número" o ":número-A"</p>

                <div class="row g-3 mb-2">
                    <div class="col-md-6">
                        <label for="store_order_prefix" class="form-label">Empezar con</label>
                        <input type="text" name="store_order_prefix" id="store_order_prefix"
                            class="form-control @error('store_order_prefix') is-invalid @enderror"
                            value="{{ $prefix }}">
                        @error('store_order_prefix')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="store_order_suffix" class="form-label">Terminar con</label>
                        <input type="text" name="store_order_suffix" id="store_order_suffix"
                            class="form-control @error('store_order_suffix') is-invalid @enderror"
                            value="{{ $suffix }}">
                        @error('store_order_suffix')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="text-muted small mb-4" id="order-code-preview">
                    Se mostrará su código de pedido #{{ $prefix }}10000000{{ $suffix }}
                </div>

                <hr class="my-3">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="store_weight_unit" class="form-label">Unidad de peso</label>
                        <select name="store_weight_unit" id="store_weight_unit"
                            class="form-select @error('store_weight_unit') is-invalid @enderror">
                            <option value="g" {{ old('store_weight_unit', $settings['store_weight_unit'] ?? '') == 'g' ? 'selected' : '' }}>Gramo (g)</option>
                            <option value="kg" {{ old('store_weight_unit', $settings['store_weight_unit'] ?? '') == 'kg' ? 'selected' : '' }}>Kilogramo (kg)</option>
                            <option value="lb" {{ old('store_weight_unit', $settings['store_weight_unit'] ?? '') == 'lb' ? 'selected' : '' }}>Libra (lb)</option>
                            <option value="oz" {{ old('store_weight_unit', $settings['store_weight_unit'] ?? '') == 'oz' ? 'selected' : '' }}>Onza (oz)</option>
                        </select>
                        @error('store_weight_unit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="store_width_height_unit" class="form-label">Longitud / altura de la unidad</label>
                        <select name="store_width_height_unit" id="store_width_height_unit"
                            class="form-select @error('store_width_height_unit') is-invalid @enderror">
                            <option value="cm" {{ old('store_width_height_unit', $settings['store_width_height_unit'] ?? '') == 'cm' ? 'selected' : '' }}>Centímetro (cm)</option>
                            <option value="m" {{ old('store_width_height_unit', $settings['store_width_height_unit'] ?? '') == 'm' ? 'selected' : '' }}>Metro (m)</option>
                            <option value="inch" {{ old('store_width_height_unit', $settings['store_width_height_unit'] ?? '') == 'inch' ? 'selected' : '' }}>Pulgada</option>
                        </select>
                        @error('store_width_height_unit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

            </div>
        </div>

        <div class="mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Guardar ajustes
            </button>
        </div>

    </form>
@endsection

@push('scripts')
<script>
$(function () {
    function updatePreview() {
        var prefix = $('#store_order_prefix').val();
        var suffix = $('#store_order_suffix').val();
        $('#order-code-preview').text('Se mostrará su código de pedido #' + prefix + '10000000' + suffix);
    }

    $('#store_order_prefix, #store_order_suffix').on('input', updatePreview);
});
</script>
@endpush
