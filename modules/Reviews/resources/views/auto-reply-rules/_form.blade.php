{{--
    Variables expected:
    $locations  – collection of ReviewGoogleLocation (id, name)
    $templates  – collection of ReviewReplyTemplate (id, name)
    $rule       – ReviewAutoReplyRule|null (null for create)
--}}

@php
    $conditionType = old('condition_type', $rule?->conditions
        ? (isset($rule->conditions['keyword']) ? 'keyword_match'
            : (isset($rule->conditions['has_comment']) && $rule->conditions['has_comment'] === false ? 'has_no_reply'
                : 'star_rating'))
        : 'star_rating');
@endphp

<div class="row">
    <div class="col-12">
        <div class="mb-3">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror"
                   name="name" value="{{ old('name', $rule?->name) }}"
                   maxlength="150" required placeholder="Ej: Responder reseñas de 1 estrella">
            @error('name')
                <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <div class="mb-3">
            <label class="form-label">Ubicacion <span class="text-danger">*</span></label>
            @if($rule)
                <input type="text" class="form-control" value="{{ $rule->location->name ?? '—' }}" disabled>
                <small class="form-text text-muted">La ubicacion no puede modificarse</small>
            @else
                <select class="form-select select2" name="location_id" required>
                    <option value="">Seleccionar ubicacion...</option>
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
            <label class="form-label">Tipo de condicion</label>
            <select class="form-select select2" id="condition-type" name="condition_type">
                <option value="star_rating"   {{ $conditionType === 'star_rating'   ? 'selected' : '' }}>Por calificacion (estrellas)</option>
                <option value="has_no_reply"  {{ $conditionType === 'has_no_reply'  ? 'selected' : '' }}>Sin respuesta previa</option>
                <option value="keyword_match" {{ $conditionType === 'keyword_match' ? 'selected' : '' }}>Contiene palabra clave</option>
            </select>
        </div>
    </div>

    <div id="rating-fields" class="col-12 {{ $conditionType !== 'star_rating' ? 'd-none' : '' }}">
        <div class="row g-3 mb-3">
            <div class="col-6">
                <label class="form-label">Estrellas minimas</label>
                <input type="number" class="form-control" name="min_rating"
                       min="1" max="5" placeholder="1"
                       value="{{ old('min_rating', $rule?->conditions['min_rating'] ?? '') }}">
            </div>
            <div class="col-6">
                <label class="form-label">Estrellas maximas</label>
                <input type="number" class="form-control" name="max_rating"
                       min="1" max="5" placeholder="5"
                       value="{{ old('max_rating', $rule?->conditions['max_rating'] ?? '') }}">
            </div>
        </div>
    </div>

    <div id="keyword-field" class="col-12 mb-3 {{ $conditionType !== 'keyword_match' ? 'd-none' : '' }}">
        <label class="form-label">Palabra clave</label>
        <input type="text" class="form-control" name="condition_value"
               placeholder="Ej: excelente"
               value="{{ old('condition_value', $rule?->conditions['keyword'] ?? '') }}">
    </div>

    <div class="col-12">
        <div class="mb-3">
            <label class="form-label">Plantilla de respuesta</label>
            <select class="form-select select2" name="template_id">
                <option value="">Sin plantilla (usar texto personalizado)</option>
                @foreach($templates as $template)
                    <option value="{{ $template->id }}"
                        {{ old('template_id', $rule?->template_id) == $template->id ? 'selected' : '' }}>
                        {{ $template->name }}
                    </option>
                @endforeach
            </select>
            <div class="form-text">Si no seleccionas una plantilla, puedes escribir un texto personalizado.</div>
        </div>
    </div>

    <div class="col-12">
        <div class="mb-3">
            <label class="form-label">Estado</label>
            <select class="form-select select2" name="is_active">
                <option value="1" {{ old('is_active', $rule ? ($rule->is_active ? '1' : '0') : '1') === '1' ? 'selected' : '' }}>Activa</option>
                <option value="0" {{ old('is_active', $rule ? ($rule->is_active ? '1' : '0') : '1') === '0' ? 'selected' : '' }}>Inactiva</option>
            </select>
        </div>
    </div>
</div>
