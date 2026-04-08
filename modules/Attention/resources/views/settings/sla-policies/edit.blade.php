@extends('layouts.theme')

@section('title', 'Editar política SLA')

@section('content')

    @include('core::components.card', ['title' => 'Editar política SLA'])

    <div class="row g-3">

        {{-- Formulario --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form action="{{ route('settings.attention.sla-policies.update', $policy->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="card-header border-bottom p-3">
                        <h5 class="mb-0 fw-bold">Editar política SLA</h5>
                        <small class="text-muted">Modifique los tiempos de respuesta, resolución y cierre.</small>
                    </div>
                    <div class="card-body">
                        @include('core::components.alerts')

                        <div class="row">
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           name="name" value="{{ old('name', $policy->name) }}"
                                           placeholder="ej: PQRSF Estándar" required>
                                    @error('name')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Zona horaria <span class="text-danger">*</span></label>
                                    <select class="form-select @error('timezone') is-invalid @enderror select2" name="timezone" required>
                                        <option value="America/Bogota" {{ old('timezone', $policy->timezone) == 'America/Bogota' ? 'selected' : '' }}>América/Bogotá</option>
                                        <option value="UTC" {{ old('timezone', $policy->timezone) == 'UTC' ? 'selected' : '' }}>UTC</option>
                                    </select>
                                    @error('timezone')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-4">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              name="description" rows="2"
                                              placeholder="Descripción de la política">{{ old('description', $policy->description) }}</textarea>
                                    @error('description')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <h6 class="fw-semibold mb-1 text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    Tiempos SLA (en minutos)
                                </h6>
                                <p class="text-muted mb-3">Define los plazos máximos en minutos para cada etapa de atención de la solicitud.</p>
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-circle-info me-2"></i>
                                    <strong>Referencia:</strong> 1 día = 1440 min &nbsp;|&nbsp; 2 días = 2880 min &nbsp;|&nbsp; 10 días = 14400 min
                                </div>
                            </div>

                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Tiempo de respuesta <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('response_time') is-invalid @enderror"
                                           name="response_time" value="{{ old('response_time', $policy->response_time) }}"
                                           min="1" placeholder="2880" required>
                                    <small class="form-text text-muted">Tiempo máximo para primera respuesta</small>
                                    @error('response_time')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Tiempo de resolución <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('resolution_time') is-invalid @enderror"
                                           name="resolution_time" value="{{ old('resolution_time', $policy->resolution_time) }}"
                                           min="1" placeholder="14400" required>
                                    <small class="form-text text-muted">Tiempo máximo para resolución</small>
                                    @error('resolution_time')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-12">
                                <div class="mb-4">
                                    <label class="form-label">Tiempo de cierre <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('closure_time') is-invalid @enderror"
                                           name="closure_time" value="{{ old('closure_time', $policy->closure_time) }}"
                                           min="1" placeholder="21600" required>
                                    <small class="form-text text-muted">Tiempo máximo para cierre</small>
                                    @error('closure_time')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12"><hr class="my-2"></div>

                            <div class="col-12">
                                <h6 class="fw-semibold mb-1 text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    Escalación
                                </h6>
                                <p class="text-muted mb-3">Configura el comportamiento automático cuando una solicitud se acerca al límite del tiempo SLA.</p>
                            </div>

                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Habilitar escalación automática</label>
                                    <select class="form-select select2" name="enable_escalation">
                                        <option value="0" {{ old('enable_escalation', $policy->enable_escalation ? '1' : '0') == '0' ? 'selected' : '' }}>No</option>
                                        <option value="1" {{ old('enable_escalation', $policy->enable_escalation ? '1' : '0') == '1' ? 'selected' : '' }}>Sí</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 col-md-12">
                                <div class="mb-4">
                                    <label class="form-label">Umbral de escalación (%)</label>
                                    <input type="number" class="form-control @error('escalation_threshold_percent') is-invalid @enderror"
                                           name="escalation_threshold_percent" value="{{ old('escalation_threshold_percent', $policy->escalation_threshold_percent) }}"
                                           min="1" max="100" placeholder="80">
                                    <small class="form-text text-muted">Porcentaje del tiempo SLA para escalar</small>
                                    @error('escalation_threshold_percent')
                                        <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12"><hr class="my-2"></div>

                            <div class="col-12">
                                <h6 class="fw-semibold mb-1 text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                    Estado
                                </h6>
                                <p class="text-muted mb-3">Controla si esta política está activa y si se aplica por defecto a las nuevas solicitudes.</p>
                            </div>

                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select select2" name="active">
                                        <option value="1" {{ old('active', $policy->active) == 1 ? 'selected' : '' }}>Activo</option>
                                        <option value="0" {{ old('active', $policy->active) == 0 ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Política por defecto</label>
                                    <select class="form-select select2" name="is_default">
                                        <option value="0" {{ old('is_default', $policy->is_default ? '1' : '0') == '0' ? 'selected' : '' }}>No</option>
                                        <option value="1" {{ old('is_default', $policy->is_default ? '1' : '0') == '1' ? 'selected' : '' }}>Sí</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                            <a href="{{ route('settings.attention.sla-policies.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel informativo --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">¿Qué es una política SLA?</h6>
                    <p class="card-text text-muted">
                        Una política SLA (Acuerdo de Nivel de Servicio) define los tiempos máximos para responder, resolver y cerrar solicitudes PQRSF.
                    </p>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Tiempos en minutos</h6>
                    <ul class="text-muted ps-3 mb-0">
                        <li class="mb-2"><strong>Respuesta:</strong> tiempo para dar la primera respuesta al ciudadano</li>
                        <li class="mb-2"><strong>Resolución:</strong> tiempo para resolver completamente la solicitud</li>
                        <li><strong>Cierre:</strong> tiempo máximo antes del cierre automático</li>
                    </ul>
                </div>
                <hr class="my-0">
                <div class="card-body">
                    <h6 class="card-title mb-3">Escalación automática</h6>
                    <p class="card-text text-muted mb-0">
                        Cuando se alcanza el umbral configurado (% del tiempo SLA), el sistema escala la solicitud automáticamente para evitar incumplimientos.
                    </p>
                </div>
            </div>
        </div>

    </div>

@endsection
