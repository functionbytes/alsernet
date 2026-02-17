@extends('layouts.theme')

@section('title', 'Editar Política SLA')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <form action="{{ route('settings.attention.sla-policies.update', $policy->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="card-header border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1 fw-bold">Editar Política SLA</h5>
                                <p class="mb-0 text-muted small">Modifique los tiempos de respuesta, resolución y cierre</p>
                            </div>
                            <a href="{{ route('settings.attention.sla-policies.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Volver
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-12">
                                <h6 class="mb-3 fw-bold">Información Básica</h6>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           name="name" value="{{ old('name', $policy->name) }}"
                                           placeholder="ej: PQRSF Estándar" required>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Zona Horaria <span class="text-danger">*</span></label>
                                    <select class="form-select @error('timezone') is-invalid @enderror" name="timezone" required>
                                        <option value="America/Bogota" {{ old('timezone', $policy->timezone) == 'America/Bogota' ? 'selected' : '' }}>América/Bogotá</option>
                                        <option value="UTC" {{ old('timezone', $policy->timezone) == 'UTC' ? 'selected' : '' }}>UTC</option>
                                    </select>
                                    @error('timezone')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Descripción</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              name="description" rows="2"
                                              placeholder="Descripción de la política">{{ old('description', $policy->description) }}</textarea>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- SLA Times -->
                            <div class="col-12 mt-3">
                                <h6 class="mb-3 fw-bold">Tiempos SLA (en minutos)</h6>
                                <div class="alert alert-info">
                                    <i class="fa fa-circle-info me-2"></i>
                                    <strong>Referencia:</strong> 1 día = 1440 min, 2 días = 2880 min, 10 días = 14400 min
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Tiempo de Respuesta <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('response_time') is-invalid @enderror"
                                           name="response_time" value="{{ old('response_time', $policy->response_time) }}"
                                           min="1" placeholder="2880" required>
                                    <small class="form-text text-muted">Tiempo máximo para primera respuesta</small>
                                    @error('response_time')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Tiempo de Resolución <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('resolution_time') is-invalid @enderror"
                                           name="resolution_time" value="{{ old('resolution_time', $policy->resolution_time) }}"
                                           min="1" placeholder="14400" required>
                                    <small class="form-text text-muted">Tiempo máximo para resolución</small>
                                    @error('resolution_time')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Tiempo de Cierre <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('closure_time') is-invalid @enderror"
                                           name="closure_time" value="{{ old('closure_time', $policy->closure_time) }}"
                                           min="1" placeholder="21600" required>
                                    <small class="form-text text-muted">Tiempo máximo para cierre</small>
                                    @error('closure_time')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Escalation Settings -->
                            <div class="col-12 mt-3">
                                <h6 class="mb-3 fw-bold">Configuración de Escalación</h6>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="enable_escalation"
                                               id="enable_escalation" value="1" {{ old('enable_escalation', $policy->enable_escalation) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="enable_escalation">
                                            Habilitar escalación automática
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <label class="control-label col-form-label">Umbral de Escalación (%)</label>
                                    <input type="number" class="form-control @error('escalation_threshold_percent') is-invalid @enderror"
                                           name="escalation_threshold_percent" value="{{ old('escalation_threshold_percent', $policy->escalation_threshold_percent) }}"
                                           min="1" max="100" placeholder="80">
                                    <small class="form-text text-muted">Porcentaje del tiempo SLA para escalar</small>
                                    @error('escalation_threshold_percent')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Status Settings -->
                            <div class="col-12 mt-3">
                                <h6 class="mb-3 fw-bold">Estado</h6>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="active"
                                               id="active" value="1" {{ old('active', $policy->active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="active">
                                            Política activa
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_default"
                                               id="is_default" value="1" {{ old('is_default', $policy->is_default) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_default">
                                            Establecer como política por defecto
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('settings.attention.sla-policies.index') }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Actualizar Política
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
