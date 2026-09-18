<div class="row g-3">

    {{-- Entity type --}}
    <div class="col-12 col-md-6">
        <label class="form-label">
            Entidad <span class="text-danger">*</span>
        </label>
        <select name="entity_type" id="entity_type" class="form-select @error('entity_type') is-invalid @enderror">
            <option value="">Seleccionar entidad</option>
            <option value="customer" @selected(old('entity_type', $field->entity_type ?? '') === 'customer')>Cliente</option>
            <option value="conversation" @selected(old('entity_type', $field->entity_type ?? '') === 'conversation')>Conversacion</option>
        </select>
        @error('entity_type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Type --}}
    <div class="col-12 col-md-6">
        <label class="form-label">
            Tipo <span class="text-danger">*</span>
        </label>
        <select name="type" id="field_type" class="form-select @error('type') is-invalid @enderror">
            <option value="">Seleccionar tipo</option>
            @foreach($types as $typeKey => $typeLabel)
                <option value="{{ $typeKey }}" @selected(old('type', $field->type ?? '') === $typeKey)>{{ $typeLabel }}</option>
            @endforeach
        </select>
        @error('type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Label --}}
    <div class="col-12">
        <label class="form-label">
            Etiqueta <span class="text-danger">*</span>
        </label>
        <input type="text" name="label" id="field_label"
            class="form-control @error('label') is-invalid @enderror"
            value="{{ old('label', $field->label ?? '') }}"
            placeholder="Ej: Numero de cuenta">
        <div class="form-text">
            Clave generada: <code id="key_preview">{{ $field->key ?? '' }}</code>
        </div>
        @error('label')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Options (solo para select/multi-select) --}}
    <div class="col-12 d-none" id="options_section">
        <label class="form-label">
            Opciones <span class="text-danger">*</span>
        </label>
        <textarea name="options" id="field_options" rows="5"
            class="form-control @error('options') is-invalid @enderror"
            placeholder="Una opcion por linea">{{ old('options', isset($field) && $field->options ? implode("\n", $field->options) : '') }}</textarea>
        <div class="form-text">Escribe una opcion por linea. Se usaran como valores de la lista.</div>
        @error('options')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Default value --}}
    <div class="col-12 col-md-8">
        <label class="form-label">Valor por defecto</label>
        <input type="text" name="default_value"
            class="form-control @error('default_value') is-invalid @enderror"
            value="{{ old('default_value', $field->default_value ?? '') }}"
            placeholder="Opcional">
        @error('default_value')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Order --}}
    <div class="col-12 col-md-4">
        <label class="form-label">Orden</label>
        <input type="number" name="order"
            class="form-control @error('order') is-invalid @enderror"
            value="{{ old('order', $field->order ?? 0) }}"
            min="0">
        @error('order')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Is required --}}
    <div class="col-12">
        <div class="form-check">
            <input type="hidden" name="is_required" value="0">
            <input type="checkbox" name="is_required" id="is_required"
                class="form-check-input"
                value="1"
                @checked(old('is_required', $field->is_required ?? false))>
            <label class="form-check-label" for="is_required">
                Campo requerido
            </label>
            <div class="form-text">Si esta activo, los usuarios deberan completar este campo obligatoriamente.</div>
        </div>
    </div>

</div>

@push('scripts')
<script>
$(document).ready(function () {
    const selectTypes = ['select', 'multi-select'];

    function toggleOptions() {
        const type = $('#field_type').val();
        $('#options_section').toggleClass('d-none', ! selectTypes.includes(type));
    }

    function slugify(text) {
        return text
            .toLowerCase()
            .replace(/[^a-z0-9\s_]/g, '')
            .trim()
            .replace(/[\s-]+/g, '_');
    }

    $('#field_type').on('change', toggleOptions);
    toggleOptions();

    $('#field_label').on('input', function () {
        @if(!isset($field) || !$field?->id)
        $('#key_preview').text(slugify($(this).val()) || '');
        @endif
    });
});
</script>
@endpush
