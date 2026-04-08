{{-- General Campaign Settings Tab --}}
<form method="POST" action="{{ route('manager.helpdesk.campaigns.update', $campaign) }}" id="general-form">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-md-8">
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Nombre de Campaña <span class="text-danger">*</span>
                </label>
                <input type="text"
                       name="name"
                       class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $campaign->name) }}"
                       required
                       placeholder="Ej: Promoción de Verano 2025">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Un nombre descriptivo para identificar tu campaña internamente</small>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Descripción</label>
                <textarea name="description"
                          class="form-control @error('description') is-invalid @enderror"
                          rows="4"
                          placeholder="Describe el objetivo y contenido de esta campaña...">{{ old('description', $campaign->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Opcional: ayuda a tu equipo a entender el propósito de la campaña</small>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-light-info mb-3">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-info-circle"></i> Información de la Campaña
                    </h6>
                    <div class="mb-2">
                        <small class="text-muted">Creada:</small>
                        <div class="fw-semibold">{{ $campaign->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Última actualización:</small>
                        <div class="fw-semibold">{{ $campaign->updated_at->format('d/m/Y H:i') }}</div>
                    </div>
                    @if($campaign->published_at)
                        <div>
                            <small class="text-muted">Publicada:</small>
                            <div class="fw-semibold">{{ $campaign->published_at->format('d/m/Y H:i') }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Tipo de Campaña <span class="text-danger">*</span>
                </label>
                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                    <option value="">Seleccionar tipo...</option>
                    <option value="popup" {{ old('type', $campaign->type) === 'popup' ? 'selected' : '' }}>
                        Pop-up (Ventana emergente)
                    </option>
                    <option value="banner" {{ old('type', $campaign->type) === 'banner' ? 'selected' : '' }}>
                        Banner (Barra superior/inferior)
                    </option>
                    <option value="slide-in" {{ old('type', $campaign->type) === 'slide-in' ? 'selected' : '' }}>
                        Slide-in (Deslizar desde esquina)
                    </option>
                    <option value="full-screen" {{ old('type', $campaign->type) === 'full-screen' ? 'selected' : '' }}>
                        Pantalla Completa (Overlay)
                    </option>
                </select>
                @error('type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Define cómo se mostrará la campaña a los visitantes</small>
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Estado <span class="text-danger">*</span>
                </label>
                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                    <option value="draft" {{ old('status', $campaign->status) === 'draft' ? 'selected' : '' }}>
                        <i class="fas fa-edit"></i> Borrador
                    </option>
                    <option value="scheduled" {{ old('status', $campaign->status) === 'scheduled' ? 'selected' : '' }}>
                        Programada
                    </option>
                    <option value="active" {{ old('status', $campaign->status) === 'active' ? 'selected' : '' }}>
                        Activa
                    </option>
                    <option value="paused" {{ old('status', $campaign->status) === 'paused' ? 'selected' : '' }}>
                        Pausada
                    </option>
                    <option value="ended" {{ old('status', $campaign->status) === 'ended' ? 'selected' : '' }}>
                        Finalizada
                    </option>
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Controla la visibilidad de la campaña</small>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="d-flex justify-content-between border-top pt-3 mt-4">
        <a href="{{ route('manager.helpdesk.campaigns.index') }}" class="btn btn-light">
            <i class="fas fa-arrow-left"></i> Volver al listado
        </a>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('general-form').reset()">
                <i class="fas fa-sync-alt"></i> Restablecer
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="far fa-save"></i> Guardar Cambios
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-save notification
    const form = document.getElementById('general-form');
    let formChanged = false;

    form.querySelectorAll('input, textarea, select').forEach(input => {
        input.addEventListener('change', () => {
            formChanged = true;
        });
    });

    window.addEventListener('beforeunload', (e) => {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = '¿Seguro que quieres salir? Los cambios no guardados se perderán.';
        }
    });

    form.addEventListener('submit', () => {
        formChanged = false;
    });
});
</script>
@endpush
