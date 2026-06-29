<div class="row g-3">

    {{-- Nombre --}}
    <div class="col-12">
        <label class="form-label">
            Nombre <span class="text-danger">*</span>
        </label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $slaPolicy->name ?? '') }}"
            placeholder="Ej: SLA prioritario - 1 hora">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Descripcion --}}
    <div class="col-12">
        <label class="form-label">Descripcion</label>
        <textarea name="description" rows="3"
            class="form-control @error('description') is-invalid @enderror"
            placeholder="Descripcion opcional de la politica SLA">{{ old('description', $slaPolicy->description ?? '') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Tiempos --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Primera respuesta (horas) <span class="text-danger">*</span></label>
        <input type="number" name="first_response_time_hours" min="1" max="8760"
            class="form-control @error('first_response_time_hours') is-invalid @enderror"
            value="{{ old('first_response_time_hours', $slaPolicy->first_response_time_hours ?? '') }}"
            placeholder="Ej: 1">
        <div class="form-text">Ej: 1 = 1 hora, 8 = 8 horas, 24 = 1 dia</div>
        @error('first_response_time_hours')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        <label class="form-label">Resolucion (horas) <span class="text-danger">*</span></label>
        <input type="number" name="resolution_time_hours" min="1" max="8760"
            class="form-control @error('resolution_time_hours') is-invalid @enderror"
            value="{{ old('resolution_time_hours', $slaPolicy->resolution_time_hours ?? '') }}"
            placeholder="Ej: 8">
        <div class="form-text">Ej: 8 = 8 horas, 48 = 2 dias, 168 = 1 semana</div>
        @error('resolution_time_hours')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Umbral de advertencia --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Umbral de advertencia (%)</label>
        <input type="number" name="warning_threshold_percent" min="1" max="100"
            class="form-control @error('warning_threshold_percent') is-invalid @enderror"
            value="{{ old('warning_threshold_percent', $slaPolicy->warning_threshold_percent ?? 80) }}"
            placeholder="80">
        <div class="form-text">Porcentaje del tiempo transcurrido al que se emite una advertencia</div>
        @error('warning_threshold_percent')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Opciones --}}
    <div class="col-12">
        <div class="border rounded p-3">
            <p class="small text-muted mb-3 fw-semibold">Opciones de la politica</p>

            <div class="form-check mb-2">
                <input type="checkbox" name="business_hours_only" value="1"
                    id="business_hours_only"
                    class="form-check-input"
                    @checked(old('business_hours_only', $slaPolicy->business_hours_only ?? false))>
                <label class="form-check-label" for="business_hours_only">
                    Solo horario laboral
                    <small class="text-muted d-block">El conteo de tiempo se pausa fuera del horario de trabajo configurado</small>
                </label>
            </div>

            <div class="form-check">
                <input type="checkbox" name="is_active" value="1"
                    id="is_active"
                    class="form-check-input"
                    @checked(old('is_active', $slaPolicy->is_active ?? true))>
                <label class="form-check-label" for="is_active">
                    Politica activa
                    <small class="text-muted d-block">Habilitar esta politica SLA para asignar a conversaciones</small>
                </label>
            </div>
        </div>
    </div>

</div>
