@extends('layouts.theme')

@section('title', 'Pago contra entrega')

@section('content')
    @include('core::components.alerts')

    <div class="card">
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-1 fw-bold">Pago contra entrega (COD)</h5>
                    <p class="small mb-0 text-muted">El cliente paga al momento de recibir el pedido</p>
                </div>
                <a href="{{ route('ecommerce-payment.methods.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Métodos de pago
                </a>
            </div>
        </div>

        <div class="card-body p-4">
            <form action="{{ route('ecommerce-payment.cod.settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Estado</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="1" {{ $settings['status'] == '1' ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ $settings['status'] == '0' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Nombre del método</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $settings['name']) }}"
                               placeholder="Pago contra entrega">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripcion corta</label>
                        <input type="text" name="description" class="form-control @error('description') is-invalid @enderror"
                               value="{{ old('description', $settings['description']) }}"
                               placeholder="Texto visible en el checkout">
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Instrucciones para el cliente</label>
                        <textarea name="instructions" rows="4"
                                  class="form-control @error('instructions') is-invalid @enderror"
                                  placeholder="Texto que verá el cliente al confirmar la orden">{{ old('instructions', $settings['instructions']) }}</textarea>
                        <div class="form-text">Se muestra en la página de confirmación de la orden.</div>
                        @error('instructions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <hr class="my-4">
                <h6 class="fw-bold mb-3">Cargo adicional (opcional)</h6>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tipo de cargo</label>
                        <select name="fee_type" id="fee_type" class="form-select @error('fee_type') is-invalid @enderror">
                            <option value="none" {{ $settings['fee_type'] == 'none' ? 'selected' : '' }}>Sin cargo</option>
                            <option value="fixed" {{ $settings['fee_type'] == 'fixed' ? 'selected' : '' }}>Monto fijo</option>
                            <option value="percentage" {{ $settings['fee_type'] == 'percentage' ? 'selected' : '' }}>Porcentaje</option>
                        </select>
                        @error('fee_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4" id="fee_value_wrap">
                        <label class="form-label fw-semibold">Valor del cargo</label>
                        <div class="input-group">
                            <span class="input-group-text" id="fee_symbol">$</span>
                            <input type="number" name="fee_value" step="0.01" min="0"
                                   class="form-control @error('fee_value') is-invalid @enderror"
                                   value="{{ old('fee_value', $settings['fee_value']) }}">
                        </div>
                        @error('fee_value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar configuracion
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    function toggleFee() {
        var type = $('#fee_type').val();
        var $wrap = $('#fee_value_wrap');
        var $sym = $('#fee_symbol');
        if (type === 'none') {
            $wrap.hide();
        } else {
            $wrap.show();
            $sym.text(type === 'percentage' ? '%' : '$');
        }
    }
    toggleFee();
    $('#fee_type').on('change', toggleFee);
});
</script>
@endpush
