<div class="row g-3">

    {{-- Seccion informacion basica --}}
    <div class="col-12">
        <h6 class="fw-bold mb-0 border-bottom pb-2">Informacion basica</h6>
    </div>

    {{-- Nombre --}}
    <div class="col-12 col-md-6">
        <label class="form-label">
            Nombre <span class="text-danger">*</span>
        </label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $brand->name ?? '') }}"
            placeholder="Ej: Soporte Principal">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Dominio --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Dominio</label>
        <input type="text" name="domain" class="form-control @error('domain') is-invalid @enderror"
            value="{{ old('domain', $brand->domain ?? '') }}"
            placeholder="Ej: soporte.miempresa.com">
        <div class="form-text">Dominio desde el que se identifica esta marca automaticamente</div>
        @error('domain')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Seccion branding --}}
    <div class="col-12 mt-2">
        <h6 class="fw-bold mb-0 border-bottom pb-2">Branding</h6>
    </div>

    {{-- Color primario --}}
    <div class="col-12 col-md-4">
        <label class="form-label">Color primario</label>
        <div class="input-group">
            <input type="color" name="primary_color" id="primary_color"
                class="form-control form-control-color @error('primary_color') is-invalid @enderror"
                value="{{ old('primary_color', $brand->primary_color ?? '#90bb13') }}">
            <input type="text" id="primary_color_text" class="form-control"
                value="{{ old('primary_color', $brand->primary_color ?? '#90bb13') }}"
                placeholder="#90bb13" maxlength="7">
        </div>
        <div class="form-text">Color usado en el widget y elementos de la marca</div>
        @error('primary_color')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- Logo URL --}}
    <div class="col-12 col-md-8">
        <label class="form-label">URL del logo</label>
        <input type="url" name="logo_url" class="form-control @error('logo_url') is-invalid @enderror"
            value="{{ old('logo_url', $brand->logo_url ?? '') }}"
            placeholder="https://cdn.miempresa.com/logo.png">
        <div class="form-text">URL publica de la imagen del logo (PNG, SVG recomendado)</div>
        @error('logo_url')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Seccion email --}}
    <div class="col-12 mt-2">
        <h6 class="fw-bold mb-0 border-bottom pb-2">Email</h6>
    </div>

    {{-- Email from name --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Nombre del remitente</label>
        <input type="text" name="email_from_name" class="form-control @error('email_from_name') is-invalid @enderror"
            value="{{ old('email_from_name', $brand->email_from_name ?? '') }}"
            placeholder="Ej: Soporte Tecnico">
        <div class="form-text">Nombre visible en el campo "De:" de los correos</div>
        @error('email_from_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Email from address --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Correo del remitente</label>
        <input type="email" name="email_from_address" class="form-control @error('email_from_address') is-invalid @enderror"
            value="{{ old('email_from_address', $brand->email_from_address ?? '') }}"
            placeholder="soporte@miempresa.com">
        <div class="form-text">Direccion de correo desde la que se envian los mensajes</div>
        @error('email_from_address')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Widget token (solo en edicion) --}}
    @if(isset($brand) && $brand->exists && $brand->widget_token)
        <div class="col-12 mt-2">
            <h6 class="fw-bold mb-0 border-bottom pb-2">Token del widget</h6>
        </div>
        <div class="col-12">
            <label class="form-label">Widget token</label>
            <div class="input-group">
                <input type="text" class="form-control font-monospace" value="{{ $brand->widget_token }}" readonly>
                <button type="button" class="btn btn-outline-secondary btn-copy-token" title="Copiar token">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            <div class="form-text">Token unico para integrar el widget de chat en tu sitio web</div>
        </div>
    @endif

    {{-- Estado --}}
    <div class="col-12">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" class="form-check-input" name="is_active" value="1"
                id="is_active"
                @checked(old('is_active', $brand->is_active ?? true))>
            <label class="form-check-label" for="is_active">
                Marca activa
            </label>
            <div class="form-text">Las marcas inactivas no se pueden asignar a nuevas conversaciones</div>
        </div>
    </div>

</div>
