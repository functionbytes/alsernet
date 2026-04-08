@extends('layouts.theme')

@section('content')

    <div class="card w-100">

        <form id="formSlaPolicy" method="POST" action="{{ route('manager.helpdesk.backups.tickets.sla-policies.store') }}">

            {{ csrf_field() }}

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Crear nueva política SLA</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Define un acuerdo de nivel de servicio para gestionar los tiempos de respuesta y resolución de tickets. Las políticas SLA ayudan a garantizar la calidad del soporte.
                </p>

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted mb-3">Define el nombre y la descripción de la política SLA.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="Ej: SLA Soporte Premium">
                            <small class="form-text text-muted">Nombre identificativo de la política</small>
                            @error('name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Descripción opcional de la política">{{ old('description') }}</textarea>
                            <small class="form-text text-muted">Proporciona más contexto sobre esta política SLA</small>
                            @error('description')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- SLA Times -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Tiempos de respuesta (en minutos)</h6>
                        <p class="text-muted mb-3">Define los tiempos máximos para cada tipo de respuesta. Los tiempos se miden en minutos.</p>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Primera respuesta
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="first_response_time" class="form-control" value="{{ old('first_response_time') }}" required min="1" placeholder="Ej: 60">
                            <small class="form-text text-muted">Tiempo máximo para primera respuesta</small>
                            @error('first_response_time')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Siguiente respuesta</label>
                            <input type="number" name="next_response_time" class="form-control" value="{{ old('next_response_time') }}" min="1" placeholder="Ej: 120">
                            <small class="form-text text-muted">Tiempo máximo para respuestas siguientes (opcional)</small>
                            @error('next_response_time')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Tiempo de resolución
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="resolution_time" class="form-control" value="{{ old('resolution_time') }}" required min="1" placeholder="Ej: 480">
                            <small class="form-text text-muted">Tiempo máximo para resolver el ticket</small>
                            @error('resolution_time')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Business Hours -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Horario laboral</h6>
                        <p class="text-muted mb-3">Configura si la política debe aplicarse solo durante horario laboral.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="business_hours_only" value="0">
                                <input type="checkbox" name="business_hours_only" class="form-check-input" id="businessHoursCheck" value="1" {{ old('business_hours_only') ? 'checked' : '' }}>
                                <label class="form-check-label" for="businessHoursCheck">
                                    <strong>Solo horario laboral</strong>
                                    <small class="d-block text-muted">Aplica los tiempos SLA solo durante horario laboral definido</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Zona horaria</label>
                            <select name="timezone" class="form-select">
                                <option value="America/New_York" {{ old('timezone') == 'America/New_York' ? 'selected' : '' }}>America/New York (EST)</option>
                                <option value="America/Los_Angeles" {{ old('timezone') == 'America/Los_Angeles' ? 'selected' : '' }}>America/Los Angeles (PST)</option>
                                <option value="America/Chicago" {{ old('timezone') == 'America/Chicago' ? 'selected' : '' }}>America/Chicago (CST)</option>
                                <option value="Europe/Madrid" {{ old('timezone', 'Europe/Madrid') == 'Europe/Madrid' ? 'selected' : '' }}>Europe/Madrid (CET)</option>
                                <option value="Europe/London" {{ old('timezone') == 'Europe/London' ? 'selected' : '' }}>Europe/London (GMT)</option>
                                <option value="Asia/Tokyo" {{ old('timezone') == 'Asia/Tokyo' ? 'selected' : '' }}>Asia/Tokyo (JST)</option>
                                <option value="UTC" {{ old('timezone') == 'UTC' ? 'selected' : '' }}>UTC</option>
                            </select>
                            <small class="form-text text-muted">Zona horaria de referencia</small>
                            @error('timezone')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Horarios laborales (JSON)</label>
                            <textarea name="business_hours" class="form-control font-monospace" rows="6" placeholder='{"monday":{"start":"09:00","end":"17:00"},"tuesday":{"start":"09:00","end":"17:00"},"wednesday":{"start":"09:00","end":"17:00"},"thursday":{"start":"09:00","end":"17:00"},"friday":{"start":"09:00","end":"17:00"},"saturday":null,"sunday":null}'>{{ old('business_hours') }}</textarea>
                            <small class="form-text text-muted">Define los horarios laborales en formato JSON. Usa null para días no laborables.</small>
                            @error('business_hours')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Priority Multipliers -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Multiplicadores de prioridad</h6>
                        <p class="text-muted mb-3">Define cómo se ajustan los tiempos según la prioridad del ticket.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Multiplicadores (JSON)</label>
                            <textarea name="priority_multipliers" class="form-control font-monospace" rows="5" placeholder='{"urgent":0.25,"high":0.5,"normal":1.0,"low":2.0}'>{{ old('priority_multipliers') }}</textarea>
                            <small class="form-text text-muted">
                                Multiplicadores por prioridad. Ejemplo: urgent=0.25 reduce el tiempo a 25%, low=2.0 duplica el tiempo.
                            </small>
                            @error('priority_multipliers')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Escalation -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración de escalación</h6>
                        <p class="text-muted mb-3">Define si se debe escalar automáticamente cuando se alcance un porcentaje del tiempo SLA.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="enable_escalation" value="0">
                                <input type="checkbox" name="enable_escalation" class="form-check-input" id="escalationCheck" value="1" {{ old('enable_escalation') ? 'checked' : '' }}>
                                <label class="form-check-label" for="escalationCheck">
                                    <strong>Habilitar escalación automática</strong>
                                    <small class="d-block text-muted">Notifica automáticamente cuando se alcance el umbral de tiempo</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Umbral de escalación (%)</label>
                            <input type="number" name="escalation_threshold_percent" class="form-control" value="{{ old('escalation_threshold_percent', 80) }}" min="1" max="100" placeholder="Ej: 80">
                            <small class="form-text text-muted">Porcentaje del tiempo SLA para escalar (1-100)</small>
                            @error('escalation_threshold_percent')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Destinatarios de escalación (JSON)</label>
                            <textarea name="escalation_recipients" class="form-control font-monospace" rows="3" placeholder='["manager@example.com","supervisor@example.com"]'>{{ old('escalation_recipients') }}</textarea>
                            <small class="form-text text-muted">Array de emails en formato JSON que recibirán notificaciones de escalación</small>
                            @error('escalation_recipients')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Estado</h6>
                        <p class="text-muted mb-3">Configura si la política está activa.</p>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="border-bottom pb-3 mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" class="form-check-input" id="activeCheck" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="activeCheck">
                                    <strong>Política activa</strong>
                                    <small class="d-block text-muted">Permite que esta política esté disponible para asignar a tickets.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                </div>

            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-info px-4 waves-effect waves-light mt-2 w-100">
                    Guardar
                </button>
                <a href="{{ route('manager.helpdesk.backups.tickets.sla-policies.index') }}" class="btn btn-secondary px-4 waves-effect waves-light mt-2 w-100">
                    Cancelar
                </a>
            </div>

        </form>

    </div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endsection
