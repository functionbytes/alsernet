<div class="row g-3">

    {{-- Platform slug --}}
    <div class="col-12 col-md-6">
        <label class="form-label">
            Identificador (platform) @if(!isset($provider) || !$provider?->isNative())<span class="text-danger">*</span>@endif
        </label>
        @if(isset($provider) && $provider?->isNative())
            <input type="text" class="form-control" value="{{ $provider->platform }}" disabled>
            <div class="form-text">Los proveedores nativos no pueden cambiar su identificador.</div>
        @else
            <input type="text" name="platform"
                class="form-control @error('platform') is-invalid @enderror"
                value="{{ old('platform', $provider->platform ?? '') }}"
                placeholder="mi-plataforma">
            <div class="form-text">Minúsculas, números, guiones y guiones bajos. No se puede cambiar luego.</div>
        @endif
        @error('platform')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- Label --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Etiqueta <span class="text-danger">*</span></label>
        <input type="text" name="label"
            class="form-control @error('label') is-invalid @enderror"
            value="{{ old('label', $provider->label ?? '') }}"
            placeholder="Mi Plataforma">
        @error('label')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- Icon --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Icono (Font Awesome)</label>
        <div class="input-group">
            <span class="input-group-text" id="providerIconPreview">
                <i class="{{ old('icon', $provider->icon ?? '') ?: 'fas fa-plug' }}"></i>
            </span>
            <input type="text" name="icon" id="providerIconInput"
                class="form-control @error('icon') is-invalid @enderror"
                value="{{ old('icon', $provider->icon ?? '') }}"
                placeholder="fas fa-plug">
        </div>
        <div class="form-text">Clase Font Awesome 6, ej. fas fa-plug, fab fa-shopify.</div>
        @error('icon')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- Color --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Color</label>
        <div class="input-group">
            <input type="color" class="form-control form-control-color" id="providerColorPicker"
                value="{{ old('color', $provider->color ?? '') ?: '#90bb13' }}" title="Elegir color">
            <input type="text" name="color" id="providerColorInput"
                class="form-control @error('color') is-invalid @enderror"
                value="{{ old('color', $provider->color ?? '') }}"
                placeholder="#90bb13">
        </div>
        @error('color')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- Sort order --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Orden</label>
        <input type="number" name="sort_order" min="0"
            class="form-control @error('sort_order') is-invalid @enderror"
            value="{{ old('sort_order', $provider->sort_order ?? 0) }}">
        @error('sort_order')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- Description --}}
    <div class="col-12">
        <label class="form-label">Descripción</label>
        <textarea name="description" rows="2"
            class="form-control @error('description') is-invalid @enderror"
            placeholder="Para qué se usa esta integración">{{ old('description', $provider->description ?? '') }}</textarea>
        @error('description')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- Credentials --}}
    <div class="col-12">
        <label class="form-label">Credenciales</label>
        <div class="row g-2">
            <div class="col-md-4">
                <input type="password" name="credentials[api_key]" class="form-control form-control-sm"
                    placeholder="{{ !empty($provider->credentials['api_key'] ?? null) ? '•••• (sin cambios)' : 'API key' }}"
                    autocomplete="new-password">
            </div>
            <div class="col-md-4">
                <input type="password" name="credentials[api_secret]" class="form-control form-control-sm"
                    placeholder="{{ !empty($provider->credentials['api_secret'] ?? null) ? '•••• (sin cambios)' : 'API secret' }}"
                    autocomplete="new-password">
            </div>
            <div class="col-md-4">
                <input type="text" name="credentials[base_url]" class="form-control form-control-sm"
                    placeholder="URL base" value="{{ old('credentials.base_url', $provider->credentials['base_url'] ?? '') }}">
            </div>
        </div>
        <div class="form-text">Se guardan cifradas. Deja el secret vacío para mantener el valor actual.</div>
    </div>

    {{-- Is active --}}
    <div class="col-12 col-md-6">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active"
                class="form-check-input"
                value="1"
                @checked(old('is_active', $provider->is_active ?? true))>
            <label class="form-check-label" for="is_active">Proveedor activo</label>
            <div class="form-text">Si está inactivo, no aparece en el modal de integraciones del cliente.</div>
        </div>
    </div>

    {{-- Is linkable --}}
    <div class="col-12 col-md-6">
        <div class="form-check">
            <input type="hidden" name="is_linkable" value="0">
            <input type="checkbox" name="is_linkable" id="is_linkable"
                class="form-check-input"
                value="1"
                @checked(old('is_linkable', $provider->is_linkable ?? true))>
            <label class="form-check-label" for="is_linkable">Vinculable manualmente</label>
            <div class="form-text">Permite buscar y vincular clientes a esta plataforma desde el modal.</div>
        </div>
    </div>

    {{-- Is critical --}}
    <div class="col-12">
        <div class="form-check">
            <input type="hidden" name="is_critical" value="0">
            <input type="checkbox" name="is_critical" id="is_critical"
                class="form-check-input"
                value="1"
                @checked(old('is_critical', $provider->is_critical ?? false))>
            <label class="form-check-label" for="is_critical">Integración crítica</label>
            <div class="form-text">Al desvincularla, el agente deberá escribir el nombre exacto de la plataforma para confirmar.</div>
        </div>
    </div>

</div>

@push('scripts')
<script>
$(document).ready(function () {
    $('#providerIconInput').on('input', function () {
        var value = $.trim($(this).val()) || 'fas fa-plug';
        $('#providerIconPreview i').attr('class', value);
    });

    $('#providerColorPicker').on('input', function () {
        $('#providerColorInput').val($(this).val());
    });

    $('#providerColorInput').on('input', function () {
        var value = $(this).val();
        if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
            $('#providerColorPicker').val(value);
        }
    });
});
</script>
@endpush
