@extends('layouts.theme')

@section('title', 'Crear regla')

@section('page_header')
    @include('core::components.card', ['title' => 'Crear regla'])
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('helpdesksocial.rules.store') }}" method="POST">
                    @csrf

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Nueva regla automática</h5>
                        <small class="text-muted">Configura las condiciones y acciones que se ejecutarán automáticamente.</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <h6 class="fw-bold mb-3 border-bottom pb-2">Información básica</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       name="name"
                                       value="{{ old('name') }}"
                                       placeholder="ej: Auto-responder fuera de horario"
                                       required>
                                @error('name')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          name="description"
                                          rows="3"
                                          placeholder="Describe brevemente qué hace esta regla">{{ old('description') }}</textarea>
                                @error('description')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Plataforma</label>
                                <select name="platform" class="form-select select2 @error('platform') is-invalid @enderror">
                                    <option value="">Todas las plataformas</option>
                                    @foreach(['facebook' => 'Facebook', 'instagram' => 'Instagram', 'whatsapp' => 'WhatsApp', 'tiktok' => 'TikTok', 'x' => 'X', 'linkedin' => 'LinkedIn'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('platform') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('platform')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-3">
                                <label class="form-label">Prioridad</label>
                                <input type="number"
                                       class="form-control @error('priority') is-invalid @enderror"
                                       name="priority"
                                       value="{{ old('priority', 100) }}"
                                       min="0"
                                       max="9999">
                                <small class="form-text text-muted">Menor = mayor prioridad</small>
                                @error('priority')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-3">
                                <label class="form-label">Estado</label>
                                <select name="is_active" class="form-select select2">
                                    <option value="1" @selected(old('is_active', '1') == '1')>Activa</option>
                                    <option value="0" @selected(old('is_active') === '0')>Inactiva</option>
                                </select>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3 border-bottom pb-2">Condiciones y acciones</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label">Condiciones (JSON) <span class="text-danger">*</span></label>
                                <textarea class="form-control font-monospace @error('conditions') is-invalid @enderror"
                                          name="conditions"
                                          rows="6"
                                          placeholder='[{"field": "body", "operator": "contains", "value": "precio"}]'>{{ old('conditions', '[]') }}</textarea>
                                <small class="form-text text-muted">Array JSON. Cada item necesita <code>field</code>, <code>operator</code> y <code>value</code>.</small>
                                @error('conditions')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                                @error('conditions.*.field')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Acciones (JSON) <span class="text-danger">*</span></label>
                                <textarea class="form-control font-monospace @error('actions') is-invalid @enderror"
                                          name="actions"
                                          rows="6"
                                          placeholder='[{"type": "auto_reply", "params": {"template_id": 1}}]'>{{ old('actions', '[]') }}</textarea>
                                <small class="form-text text-muted">Array JSON. Cada item necesita <code>type</code> y opcionalmente <code>params</code>.</small>
                                @error('actions')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3 border-bottom pb-2">Vigencia y comportamiento</h6>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Válida desde</label>
                                <input type="date"
                                       class="form-control @error('valid_from') is-invalid @enderror"
                                       name="valid_from"
                                       value="{{ old('valid_from') }}">
                                @error('valid_from')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Válida hasta</label>
                                <input type="date"
                                       class="form-control @error('valid_until') is-invalid @enderror"
                                       name="valid_until"
                                       value="{{ old('valid_until') }}">
                                @error('valid_until')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="stop_processing" value="0">
                                    <input type="checkbox" name="stop_processing" id="stop_processing" value="1" class="form-check-input" @checked(old('stop_processing', false))>
                                    <label for="stop_processing" class="form-check-label">Detener procesamiento de reglas posteriores</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar regla</button>
                        <a href="{{ route('helpdesksocial.rules.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">¿Cómo funcionan las reglas?</h6>
                    <p class="card-text text-muted small">
                        Cada regla evalúa un comentario o mensaje entrante. Si todas las condiciones se cumplen, se ejecutan las acciones definidas.
                        Las reglas se evalúan en orden de prioridad (menor número primero).
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Operadores disponibles</h6>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li><code>equals</code>, <code>not_equals</code></li>
                        <li><code>contains</code>, <code>not_contains</code></li>
                        <li><code>starts_with</code>, <code>ends_with</code></li>
                        <li><code>regex</code></li>
                    </ul>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Tipos de acción</h6>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li><code>auto_reply</code> — responder con plantilla</li>
                        <li><code>assign</code> — asignar a usuario</li>
                        <li><code>tag</code> — etiquetar</li>
                        <li><code>escalate</code> — escalar</li>
                        <li><code>mark_spam</code> — marcar como spam</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });
});
</script>
@endpush
