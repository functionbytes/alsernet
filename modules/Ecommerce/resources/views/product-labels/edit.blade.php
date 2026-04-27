@extends('layouts.theme')

@section('title', 'Editar etiqueta')

@section('content')
    @include('core::components.card', ['title' => 'Editar etiqueta'])

    <div class="widget-content searchable-container list">
        <form action="{{ route('ecommerce.product-labels.update', $label) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-12 col-lg-8">
                    <div class="card">
                        <div class="card-header p-3 border-bottom">
                            <h5 class="mb-0 fw-bold">Editar etiqueta de producto</h5>
                            <small class="text-muted">Modifica los colores y el estado de la etiqueta</small>
                        </div>
                        <div class="card-body">
                            @include('core::components.alerts')

                            <div class="mb-3">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="label_name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $label->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Color de fondo</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" id="color_picker" value="{{ old('color', $label->color ?? '#90bb13') }}"
                                        class="form-control form-control-color flex-shrink-0">
                                    <input type="text" name="color" id="color_hex"
                                        class="form-control @error('color') is-invalid @enderror"
                                        placeholder="#90bb13" value="{{ old('color', $label->color ?? '#90bb13') }}">
                                </div>
                                @error('color')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Color de texto</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" id="text_color_picker" value="{{ old('text_color', $label->text_color ?? '#ffffff') }}"
                                        class="form-control form-control-color flex-shrink-0">
                                    <input type="text" name="text_color" id="text_color_hex"
                                        class="form-control @error('text_color') is-invalid @enderror"
                                        placeholder="#ffffff" value="{{ old('text_color', $label->text_color ?? '#ffffff') }}">
                                </div>
                                @error('text_color')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Estado</label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror">
                                    <option value="published" {{ old('status', $label->status) === 'published' ? 'selected' : '' }}>Publicado</option>
                                    <option value="draft" {{ old('status', $label->status) === 'draft' ? 'selected' : '' }}>Borrador</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100 mb-2">Actualizar etiqueta</button>
                            <a href="{{ route('ecommerce.product-labels.index') }}" class="btn btn-light w-100">Cancelar</a>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-3">Vista previa</h6>
                            <p class="text-muted small mb-3">Asi se vera la etiqueta en los productos.</p>
                            <div class="d-flex align-items-center gap-2">
                                <span id="label_preview" class="badge rounded-pill px-3 py-2"
                                    style="background-color: {{ $label->color ?? '#90bb13' }}; color: {{ $label->text_color ?? '#ffffff' }}; font-size: 0.9rem;">
                                    {{ $label->name }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var colorPicker = document.getElementById('color_picker');
    var colorHex    = document.getElementById('color_hex');
    var textPicker  = document.getElementById('text_color_picker');
    var textHex     = document.getElementById('text_color_hex');
    var labelName   = document.getElementById('label_name');
    var preview     = document.getElementById('label_preview');

    function isValidHex(val) {
        return /^#[0-9A-Fa-f]{6}$/.test(val);
    }

    function updatePreview() {
        var bg   = isValidHex(colorHex.value) ? colorHex.value : '#90bb13';
        var text = isValidHex(textHex.value)  ? textHex.value  : '#ffffff';
        var name = labelName.value.trim() || 'Etiqueta';
        preview.style.backgroundColor = bg;
        preview.style.color           = text;
        preview.textContent           = name;
    }

    colorPicker.addEventListener('input', function () {
        colorHex.value = this.value;
        updatePreview();
    });

    colorHex.addEventListener('input', function () {
        if (isValidHex(this.value)) {
            colorPicker.value = this.value;
        }
        updatePreview();
    });

    textPicker.addEventListener('input', function () {
        textHex.value = this.value;
        updatePreview();
    });

    textHex.addEventListener('input', function () {
        if (isValidHex(this.value)) {
            textPicker.value = this.value;
        }
        updatePreview();
    });

    labelName.addEventListener('input', updatePreview);
}());
</script>
@endpush
