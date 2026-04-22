{{-- General tab --}}
<form method="POST" action="{{ route('manager.helpdesk-campaigns.update', $campaign) }}" id="general-form">
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

    <div class="d-flex justify-content-between border-top pt-3 mt-4">
        <a href="{{ route('manager.helpdesk-campaigns.index') }}" class="btn btn-light">Volver al listado</a>
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
    </div>
</form>
