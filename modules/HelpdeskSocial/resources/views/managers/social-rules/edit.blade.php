@extends('layouts.theme')

@section('title', 'Editar regla')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar regla'])
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('helpdesksocial.rules.update', $rule) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">{{ $rule->name }}</h5>
                        <small class="text-muted">Modifica condiciones, acciones o vigencia de la regla.</small>
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
                                       value="{{ old('name', $rule->name) }}"
                                       required>
                                @error('name')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          name="description"
                                          rows="3">{{ old('description', $rule->description) }}</textarea>
                                @error('description')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Plataforma</label>
                                <select name="platform" class="form-select select2 @error('platform') is-invalid @enderror">
                                    <option value="">Todas las plataformas</option>
                                    @foreach(['facebook' => 'Facebook', 'instagram' => 'Instagram', 'whatsapp' => 'WhatsApp', 'tiktok' => 'TikTok', 'x' => 'X', 'linkedin' => 'LinkedIn'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('platform', $rule->platform) === $value)>{{ $label }}</option>
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
                                       value="{{ old('priority', $rule->priority) }}"
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
                                    <option value="1" @selected(old('is_active', $rule->is_active ? '1' : '0') == '1')>Activa</option>
                                    <option value="0" @selected(old('is_active', $rule->is_active ? '1' : '0') == '0')>Inactiva</option>
                                </select>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3 border-bottom pb-2">Condiciones y acciones</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label">Condiciones (JSON) <span class="text-danger">*</span></label>
                                <textarea class="form-control font-monospace @error('conditions') is-invalid @enderror"
                                          name="conditions"
                                          rows="8">{{ old('conditions', is_array($rule->conditions) ? json_encode($rule->conditions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (string) $rule->conditions) }}</textarea>
                                <small class="form-text text-muted">Array JSON con objetos <code>{ field, operator, value }</code>.</small>
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
                                          rows="8">{{ old('actions', is_array($rule->actions) ? json_encode($rule->actions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (string) $rule->actions) }}</textarea>
                                <small class="form-text text-muted">Array JSON con objetos <code>{ type, params }</code>.</small>
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
                                       value="{{ old('valid_from', optional($rule->valid_from)->format('Y-m-d')) }}">
                                @error('valid_from')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Válida hasta</label>
                                <input type="date"
                                       class="form-control @error('valid_until') is-invalid @enderror"
                                       name="valid_until"
                                       value="{{ old('valid_until', optional($rule->valid_until)->format('Y-m-d')) }}">
                                @error('valid_until')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="stop_processing" value="0">
                                    <input type="checkbox" name="stop_processing" id="stop_processing" value="1" class="form-check-input" @checked(old('stop_processing', $rule->stop_processing))>
                                    <label for="stop_processing" class="form-check-label">Detener procesamiento de reglas posteriores</label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-12">
                                <div class="alert alert-light border small mb-0">
                                    <strong>Disparos:</strong> {{ number_format($rule->trigger_count) }}
                                    @if($rule->last_triggered_at)
                                        · Último: {{ $rule->last_triggered_at->diffForHumans() }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('helpdesksocial.rules.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Resumen</h6>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li><strong>Plataforma:</strong> {{ $rule->platform ? ucfirst($rule->platform) : 'Todas' }}</li>
                        <li><strong>Prioridad:</strong> {{ $rule->priority }}</li>
                        <li><strong>Creada:</strong> {{ $rule->created_at?->format('d/m/Y H:i') }}</li>
                        <li><strong>Actualizada:</strong> {{ $rule->updated_at?->diffForHumans() }}</li>
                    </ul>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <form action="{{ route('helpdesksocial.rules.destroy', $rule) }}" method="POST" id="deleteRuleForm">
                        @csrf
                        @method('DELETE')
                        <button type="button" id="deleteRuleBtn" class="btn btn-outline-danger w-100">
                            <i class="fas fa-trash me-1"></i> Eliminar regla
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    $('#deleteRuleBtn').on('click', function () {
        if (confirm('¿Seguro que quieres eliminar esta regla? Esta acción no se puede deshacer.')) {
            $('#deleteRuleForm').submit();
        }
    });
});
</script>
@endpush
