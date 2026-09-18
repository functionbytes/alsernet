{{--
    Toggle booleano reutilizable para la configuración de funcionalidades —
    select en vez de checkbox (convención del proyecto). $field, $label
    y $default (bool) llegan del @include.
--}}
@php $current = old($field, $settings[$field] ?? $default) ? 1 : 0; @endphp
<div class="col-12 col-md-6">
    <label class="form-label">{{ $label }}</label>
    <select name="{{ $field }}" class="form-select select2 @error($field) is-invalid @enderror">
        <option value="1" {{ $current == 1 ? 'selected' : '' }}>Activado</option>
        <option value="0" {{ $current == 0 ? 'selected' : '' }}>Desactivado</option>
    </select>
    @error($field)
        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
    @enderror
</div>
