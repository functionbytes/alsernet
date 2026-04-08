{{--
    Variables:
    $campaign  – ReviewRequestCampaign|null (null para crear)
    $locations – collection of ReviewGoogleLocation (id, name)
--}}

<div class="row">
    <div class="col-12">
        <div class="mb-3">
            <label class="form-label">Ubicacion <span class="text-danger">*</span></label>
            @if($campaign)
                <input type="text" class="form-control" value="{{ $campaign->location->name ?? '—' }}" disabled>
                <small class="form-text text-muted">La ubicacion no puede modificarse.</small>
            @else
                <select class="form-select select2 @error('location_id') is-invalid @enderror" name="location_id" required>
                    <option value="">Selecciona una ubicacion...</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
                @error('location_id')
                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                @enderror
            @endif
        </div>
    </div>

    <div class="col-12">
        <div class="mb-3">
            <label class="form-label">Nombre de la campaña <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror"
                   name="name" value="{{ old('name', $campaign?->name) }}"
                   maxlength="150" required>
            @error('name')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <div class="mb-3">
            <label class="form-label">Asunto del correo <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('subject') is-invalid @enderror"
                   name="subject" value="{{ old('subject', $campaign?->subject) }}"
                   maxlength="200" required>
            @error('subject')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <div class="mb-3">
            <label class="form-label">Mensaje <span class="text-danger">*</span></label>
            <small class="form-text text-muted d-block mb-1">Usa <code>{customer_name}</code> para personalizar con el nombre del cliente.</small>
            <textarea class="form-control @error('message') is-invalid @enderror"
                      name="message" rows="5" required>{{ old('message', $campaign?->message) }}</textarea>
            @error('message')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <div class="mb-3">
            <label class="form-label">URL de reseña de Google <span class="text-danger">*</span></label>
            <input type="url" class="form-control @error('review_url') is-invalid @enderror"
                   name="review_url" value="{{ old('review_url', $campaign?->review_url) }}"
                   maxlength="500" required placeholder="https://g.page/r/...">
            @error('review_url')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label">Estado</label>
            <select class="form-select select2 @error('status') is-invalid @enderror" name="status">
                <option value="draft"   {{ old('status', $campaign?->status ?? 'draft') === 'draft'   ? 'selected' : '' }}>Borrador</option>
                <option value="active"  {{ old('status', $campaign?->status) === 'active'             ? 'selected' : '' }}>Activa</option>
                @if($campaign)
                <option value="paused"  {{ old('status', $campaign?->status) === 'paused'             ? 'selected' : '' }}>Pausada</option>
                @endif
            </select>
            @error('status')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-12">
        <div class="mb-3">
            <label class="form-label">Programar envio</label>
            <input type="datetime-local" class="form-control @error('scheduled_at') is-invalid @enderror"
                   name="scheduled_at"
                   value="{{ old('scheduled_at', $campaign?->scheduled_at?->format('Y-m-d\TH:i')) }}">
            <small class="form-text text-muted">Opcional. Deja vacío para no programar.</small>
            @error('scheduled_at')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>
</div>
