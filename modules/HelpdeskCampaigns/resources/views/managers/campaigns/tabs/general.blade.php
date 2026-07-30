{{-- General tab --}}
<form method="POST" action="{{ route('helpdesk.campaigns.update', $campaign) }}" id="general-form">
    @csrf
    @method('PUT')

    {{-- Informacion basica --}}
    <h6 class="fw-semibold mb-1 border-bottom pb-2">Informacion basica</h6>
    <p class="text-muted small mb-3">Nombre y descripcion visible de la campana</p>
    <div class="row g-3 mb-4">

        <div class="col-12">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $campaign->name) }}"
                   placeholder="Ej: Promocion de verano 2025"
                   required>
            @error('name')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-12">
            <label class="form-label">Descripcion</label>
            <textarea name="description" rows="4"
                      class="form-control @error('description') is-invalid @enderror"
                      placeholder="Describe el objetivo de esta campana...">{{ old('description', $campaign->description) }}</textarea>
            @error('description')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

    </div>

    {{-- Configuracion --}}
    <h6 class="fw-semibold mb-1 border-bottom pb-2">Configuracion</h6>
    <p class="text-muted small mb-3">Tipo de visualizacion y estado de la campana</p>
    <div class="row g-3">

        <div class="col-12 col-md-6">
            <label class="form-label">Tipo de campaña <span class="text-danger">*</span></label>
            <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                <option value="">Seleccionar tipo...</option>
                <option value="popup" {{ old('type', $campaign->type) === 'popup' ? 'selected' : '' }}>Pop-up — ventana emergente</option>
                <option value="banner" {{ old('type', $campaign->type) === 'banner' ? 'selected' : '' }}>Banner — barra superior o inferior</option>
                <option value="slide-in" {{ old('type', $campaign->type) === 'slide-in' ? 'selected' : '' }}>Slide-in — deslizar desde esquina</option>
                <option value="full-screen" {{ old('type', $campaign->type) === 'full-screen' ? 'selected' : '' }}>Pantalla completa — overlay</option>
            </select>
            @error('type')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label">Estado</label>
            <select name="status" class="form-select @error('status') is-invalid @enderror">
                <option value="draft" {{ old('status', $campaign->status) === 'draft' ? 'selected' : '' }}>Borrador — sin publicar</option>
                <option value="scheduled" {{ old('status', $campaign->status) === 'scheduled' ? 'selected' : '' }}>Programada — publicacion diferida</option>
                <option value="active" {{ old('status', $campaign->status) === 'active' ? 'selected' : '' }}>Activa — visible para visitantes</option>
                <option value="paused" {{ old('status', $campaign->status) === 'paused' ? 'selected' : '' }}>Pausada — temporalmente detenida</option>
                <option value="ended" {{ old('status', $campaign->status) === 'ended' ? 'selected' : '' }}>Finalizada — campana completada</option>
            </select>
            @error('status')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

    </div>

    {{-- Programación --}}
    <h6 class="fw-semibold mb-1 border-bottom pb-2 mt-4">Programación</h6>
    <p class="text-muted small mb-3">Cuándo se activa y finaliza la campaña</p>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <label class="form-label">Publicar el</label>
            <input type="datetime-local" name="published_at"
                   class="form-control @error('published_at') is-invalid @enderror"
                   value="{{ old('published_at', $campaign->published_at?->format('Y-m-d\TH:i')) }}">
            <small class="text-muted">Vacío = manual. Si futuro, se activa sola.</small>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Finalizar el</label>
            <input type="datetime-local" name="ends_at"
                   class="form-control @error('ends_at') is-invalid @enderror"
                   value="{{ old('ends_at', $campaign->ends_at?->format('Y-m-d\TH:i')) }}">
            <small class="text-muted">Vacío = sin límite. Si pasada, se cierra sola.</small>
        </div>
    </div>

    {{-- Frequency capping --}}
    <h6 class="fw-semibold mb-1 border-bottom pb-2 mt-4">Frecuencia por visitante</h6>
    <p class="text-muted small mb-3">Limita cuántas veces ve la campaña el mismo visitante</p>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <label class="form-label">Vistas máximas por visitante</label>
            <input type="number" name="max_impressions_per_user" min="0"
                   class="form-control @error('max_impressions_per_user') is-invalid @enderror"
                   value="{{ old('max_impressions_per_user', $campaign->max_impressions_per_user) }}"
                   placeholder="Ej: 5 (vacío = sin límite)">
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Tiempo de espera entre vistas (min)</label>
            <input type="number" name="cooldown_minutes" min="0"
                   class="form-control @error('cooldown_minutes') is-invalid @enderror"
                   value="{{ old('cooldown_minutes', $campaign->cooldown_minutes) }}"
                   placeholder="Ej: 60 (vacío = sin cooldown)">
        </div>
    </div>

    {{-- Goal --}}
    <h6 class="fw-semibold mb-1 border-bottom pb-2 mt-4">Objetivo (opcional)</h6>
    <p class="text-muted small mb-3">La campaña se cierra automáticamente al alcanzar este objetivo</p>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <label class="form-label">Métrica del objetivo</label>
            <select name="goal_type" class="form-select">
                <option value="">— Sin objetivo —</option>
                <option value="impressions" {{ old('goal_type', $campaign->goal_type) === 'impressions' ? 'selected' : '' }}>Impresiones</option>
                <option value="clicks" {{ old('goal_type', $campaign->goal_type) === 'clicks' ? 'selected' : '' }}>Clics</option>
            </select>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Valor objetivo</label>
            <input type="number" name="goal_value" min="0"
                   class="form-control @error('goal_value') is-invalid @enderror"
                   value="{{ old('goal_value', $campaign->goal_value) }}"
                   placeholder="Ej: 10000">
        </div>
    </div>

    {{-- Approval workflow --}}
    <h6 class="fw-semibold mb-1 border-bottom pb-2 mt-4">Flujo de aprobación</h6>
    <p class="text-muted small mb-3">Si está activado, alguien con permiso debe aprobar antes de publicar</p>
    <div class="form-check form-switch mb-4">
        <input class="form-check-input" type="checkbox" name="approval_required" value="1" id="approval_required"
               @checked(old('approval_required', $campaign->approval_required))>
        <label class="form-check-label" for="approval_required">Requerir aprobación antes de publicar</label>
        @if($campaign->approved_at)
            <div class="mt-2 small text-success">
                <i class="fas fa-check-circle"></i>
                Aprobada el {{ $campaign->approved_at->format('d/m/Y H:i') }}
            </div>
        @endif
    </div>

    <div class="d-flex justify-content-between border-top pt-3 mt-4">
        <a href="{{ route('helpdesk.campaigns.index') }}" class="btn btn-light">Volver al listado</a>
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
    </div>
</form>
