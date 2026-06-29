<div class="row g-3">

    {{-- Seccion: Informacion basica --}}
    <div class="col-12">
        <h6 class="fw-bold mb-0 border-bottom pb-2">Informacion basica</h6>
    </div>

    {{-- Nombre --}}
    <div class="col-12">
        <label class="form-label">
            Nombre <span class="text-danger">*</span>
        </label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $company->name ?? '') }}"
            placeholder="Ej: Acme Corporation">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Dominio --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Dominio</label>
        <input type="text" name="domain" class="form-control @error('domain') is-invalid @enderror"
            value="{{ old('domain', $company->domain ?? '') }}"
            placeholder="Ej: acme.com">
        <div class="form-text">Dominio de correo de la empresa para asociar clientes automaticamente</div>
        @error('domain')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Industria --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Industria</label>
        <input type="text" name="industry" class="form-control @error('industry') is-invalid @enderror"
            value="{{ old('industry', $company->industry ?? '') }}"
            placeholder="Ej: Tecnologia, Retail, Salud...">
        @error('industry')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Tamaño --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Tamaño</label>
        <select name="size" class="form-select @error('size') is-invalid @enderror">
            <option value="">Seleccionar tamaño</option>
            @foreach(\Modules\Helpdesk\Models\Company::SIZES as $value => $label)
                <option value="{{ $value }}" @selected(old('size', $company->size ?? '') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('size')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Sitio web --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Sitio web</label>
        <input type="url" name="website" class="form-control @error('website') is-invalid @enderror"
            value="{{ old('website', $company->website ?? '') }}"
            placeholder="https://www.acme.com">
        @error('website')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Seccion: Datos adicionales --}}
    <div class="col-12 mt-2">
        <h6 class="fw-bold mb-0 border-bottom pb-2">Datos adicionales</h6>
    </div>

    {{-- Health score --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Health score</label>
        <input type="number" name="health_score" class="form-control @error('health_score') is-invalid @enderror"
            value="{{ old('health_score', $company->health_score ?? '') }}"
            min="0" max="100" placeholder="0 - 100">
        <div class="form-text">Valor entre 0 y 100 que indica la salud de la relacion con la empresa</div>
        @error('health_score')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Notas --}}
    <div class="col-12">
        <label class="form-label">Notas</label>
        <textarea name="notes" rows="4"
            class="form-control @error('notes') is-invalid @enderror"
            placeholder="Informacion adicional sobre la empresa...">{{ old('notes', $company->notes ?? '') }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

</div>
