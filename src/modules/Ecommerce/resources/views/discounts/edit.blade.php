@extends('layouts.theme')

@section('title', 'Editar descuento')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Editar descuento'])
@endsection

@section('content')
    @include('core::components.alerts')

    <form action="{{ route('ecommerce.discounts.update', $discount) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-4 align-items-start">

            {{-- Columna principal --}}
            <div class="col-lg-8">

                {{-- Cupon --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Cupon de descuento</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Titulo <span class="text-danger">*</span></label>
                            <input type="text" name="title"
                                class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title', $discount->title) }}" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Codigo</label>
                            <div class="input-group">
                                <input type="text" name="code" id="discount-code"
                                    class="form-control @error('code') is-invalid @enderror"
                                    value="{{ old('code', $discount->code) }}"
                                    placeholder="Ej: VERANO20"
                                    style="text-transform:uppercase">
                                <button type="button" class="btn btn-outline-secondary" id="btn-generate-code">
                                    Generar codigo
                                </button>
                            </div>
                            <div class="form-text">Los clientes ingresaran este codigo al realizar el pago. Si se deja vacio, el cupon no requiere codigo.</div>
                            @error('code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="uso-ilimitado"
                                    {{ old('quantity', $discount->quantity) === null && !old('quantity') ? 'checked' : '' }}>
                                <label class="form-check-label" for="uso-ilimitado">Uso ilimitado</label>
                            </div>
                            <div id="cantidad-section" class="mt-2" style="display:none">
                                <label class="form-label">Cantidad maxima de usos</label>
                                <input type="number" name="quantity" id="quantity"
                                    class="form-control @error('quantity') is-invalid @enderror"
                                    value="{{ old('quantity', $discount->quantity) }}" min="1">
                                @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Precio minimo de orden</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="min_order_price"
                                    class="form-control @error('min_order_price') is-invalid @enderror"
                                    value="{{ old('min_order_price', $discount->min_order_price) }}" step="0.01" min="0"
                                    placeholder="0.00">
                            </div>
                            <div class="form-text">Dejar en blanco para no requerir minimo.</div>
                            @error('min_order_price')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Descripcion</label>
                            <textarea name="description" rows="3"
                                class="form-control @error('description') is-invalid @enderror"
                                placeholder="Descripcion interna del descuento...">{{ old('description', $discount->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Tipo de cupon --}}
                <div class="card mb-4">
                    <div class="card-header p-3 border-bottom">
                        <h6 class="mb-0 fw-bold">Tipo de cupon</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Tipo <span class="text-danger">*</span></label>
                                <select name="type" id="discount-type"
                                    class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="fixed" {{ old('type', $discount->type->value) === 'fixed' ? 'selected' : '' }}>$ Monto fijo</option>
                                    <option value="percentage" {{ old('type', $discount->type->value) === 'percentage' ? 'selected' : '' }}>% Porcentaje</option>
                                    <option value="free_shipping" {{ old('type', $discount->type->value) === 'free_shipping' ? 'selected' : '' }}>Envio gratis</option>
                                </select>
                                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8" id="value-section">
                                <label class="form-label">Descuento <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="value" id="discount-value"
                                        class="form-control @error('value') is-invalid @enderror"
                                        value="{{ old('value', $discount->value) }}" step="0.01" min="0" required>
                                    <span class="input-group-text" id="value-unit">$</span>
                                </div>
                                @error('value')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- /col-lg-8 --}}

            {{-- Sidebar --}}
            <div class="col-lg-4">
                <div class="sticky-top" style="top:80px">

                    {{-- Publicar --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Publicar</h6>
                        </div>
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100 mb-2">Actualizar descuento</button>
                            <a href="{{ route('ecommerce.discounts.index') }}" class="btn btn-outline-secondary w-100">Cancelar</a>
                        </div>
                    </div>

                    {{-- Estado --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Estado</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                    id="is-active" {{ old('is_active', $discount->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is-active">Descuento activo</label>
                            </div>
                        </div>
                    </div>

                    {{-- Tiempo --}}
                    <div class="card mb-4">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Tiempo</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Fecha de inicio</label>
                                <input type="datetime-local" name="start_date" id="start-date"
                                    class="form-control @error('start_date') is-invalid @enderror"
                                    value="{{ old('start_date', $discount->start_date?->format('Y-m-d\TH:i')) }}">
                                @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3" id="end-date-section">
                                <label class="form-label">Fecha de finalizacion</label>
                                <input type="datetime-local" name="end_date" id="end-date"
                                    class="form-control @error('end_date') is-invalid @enderror"
                                    value="{{ old('end_date', $discount->end_date?->format('Y-m-d\TH:i')) }}">
                                @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="nunca-expira">
                                <label class="form-check-label" for="nunca-expira">Nunca expira</label>
                            </div>
                        </div>
                    </div>

                    {{-- Detalles --}}
                    <div class="card">
                        <div class="card-header p-3 border-bottom">
                            <h6 class="mb-0 fw-bold">Detalles</h6>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0 small">
                                <dt class="col-6 text-muted fw-normal">Usos realizados</dt>
                                <dd class="col-6 mb-2">{{ $discount->total_used ?? 0 }}</dd>
                                <dt class="col-6 text-muted fw-normal">Creado</dt>
                                <dd class="col-6 mb-2">{{ $discount->created_at->translatedFormat('j M, Y') }}</dd>
                                <dt class="col-6 text-muted fw-normal">Actualizado</dt>
                                <dd class="col-6 mb-0">{{ $discount->updated_at->translatedFormat('j M, Y') }}</dd>
                            </dl>
                        </div>
                    </div>

                </div>
            </div>{{-- /col-lg-4 --}}

        </div>
    </form>
@endsection

@push('scripts')
<script>
(function () {
    // Generar código
    $('#btn-generate-code').on('click', function () {
        $.ajax({
            url: '{{ route('ecommerce.discounts.generate-code') }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (data) { $('#discount-code').val(data.code); },
            error: function () { toastr.error('Error al generar codigo'); }
        });
    });

    // Uso ilimitado toggle
    $('#uso-ilimitado').on('change', function () {
        if (this.checked) {
            $('#cantidad-section').hide();
            $('#quantity').val('');
        } else {
            $('#cantidad-section').show();
        }
    });
    // Restaurar estado desde modelo
    @if(old('quantity', $discount->quantity) !== null)
        $('#uso-ilimitado').prop('checked', false).trigger('change');
        $('#quantity').val('{{ old('quantity', $discount->quantity) }}');
    @else
        $('#uso-ilimitado').prop('checked', true).trigger('change');
    @endif

    // Tipo → actualizar unidad y mostrar/ocultar valor
    $('#discount-type').on('change', function () {
        var type = this.value;
        if (type === 'free_shipping') {
            $('#value-section').hide();
            $('#discount-value').removeAttr('required');
        } else {
            $('#value-section').show();
            $('#discount-value').attr('required', true);
            $('#value-unit').text(type === 'percentage' ? '%' : '$');
        }
    }).trigger('change');

    // Nunca expira toggle
    $('#nunca-expira').on('change', function () {
        if (this.checked) {
            $('#end-date-section').hide();
            $('#end-date').val('');
        } else {
            $('#end-date-section').show();
        }
    });
    @if(old('end_date', $discount->end_date) === null)
        $('#nunca-expira').prop('checked', true).trigger('change');
    @endif
}());
</script>
@endpush
