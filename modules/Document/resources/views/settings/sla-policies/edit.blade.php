@extends('layouts.theme')

@section('title', 'Editar Política SLA de Documentos')

@section('content')

    <div class="card w-100">

        <form id="formSlaPolicy" method="POST" action="{{ route('settings.documents.sla-policies.update', $policy->id) }}">

            {{ csrf_field() }}
            @method('PATCH')

            <div class="card-body">
                <div class="d-flex no-block align-items-center">
                    <h5 class="mb-0">Editar política SLA</h5>
                </div>
                <p class="card-subtitle mb-3 mt-1">
                    Modifica los tiempos de procesamiento y configuración de la política SLA para documentos.
                </p>

                <div class="row">

                    <!-- Basic Information -->
                    <div class="col-12">
                        <h6 class="mb-1 mt-3 fw-semibold">Información básica</h6>
                        <p class="text-muted small mb-3">Define el nombre y la descripción de la política SLA.</p>
                    </div>

                    <div class="col-12 col-md-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Nombre
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $policy->name) }}" required placeholder="Ej: SLA Estándar">
                            <small class="form-text text-muted">Nombre identificativo de la política</small>
                            @error('name')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Descripción</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Descripción opcional de la política">{{ old('description', $policy->description) }}</textarea>
                            <small class="form-text text-muted">Proporciona más contexto sobre esta política SLA</small>
                            @error('description')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- SLA Times -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Tiempos de procesamiento (en minutos)</h6>
                        <p class="text-muted small mb-3">Define los tiempos máximos para cada etapa del procesamiento de documentos. Los tiempos se miden en minutos.</p>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Tiempo de solicitud de subida
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="upload_request_time" class="form-control" value="{{ old('upload_request_time', $policy->upload_request_time) }}" required min="1" placeholder="Ej: 120">
                            <small class="form-text text-muted">Tiempo máximo para solicitar documentos al cliente</small>
                            @error('upload_request_time')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Tiempo de revisión</label>
                            <input type="number" name="review_time" class="form-control" value="{{ old('review_time', $policy->review_time) }}" min="1" placeholder="Ej: 480">
                            <small class="form-text text-muted">Tiempo máximo para revisar documentos (opcional)</small>
                            @error('review_time')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Tiempo de aprobación
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="approval_time" class="form-control" value="{{ old('approval_time', $policy->approval_time) }}" required min="1" placeholder="Ej: 1440">
                            <small class="form-text text-muted">Tiempo máximo para aprobar/rechazar documentos</small>
                            @error('approval_time')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Business Hours -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Horario de atención</h6>
                        <p class="text-muted small mb-3">Especifica si los tiempos SLA aplican solo en horario comercial o 24/7.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="business_hours_only" class="form-check-input" id="businessHours" {{ old('business_hours_only', $policy->business_hours_only) ? 'checked' : '' }}>
                                <label class="form-check-label" for="businessHours">
                                    Solo horario comercial (Lunes a Viernes 9:00 - 17:00)
                                </label>
                            </div>
                            <small class="form-text text-muted">Desactiva para aplicar SLA 24/7 incluyendo fines de semana</small>
                        </div>
                    </div>

                    <div class="col-12 col-md-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">
                                Zona horaria
                                <span class="text-danger">*</span>
                            </label>
                            <select name="timezone" class="form-control select2" required>
                                <option value="America/Mexico_City" {{ old('timezone', $policy->timezone) === 'America/Mexico_City' ? 'selected' : '' }}>America/Mexico_City</option>
                                <option value="America/New_York" {{ old('timezone', $policy->timezone) === 'America/New_York' ? 'selected' : '' }}>America/New_York</option>
                                <option value="America/Los_Angeles" {{ old('timezone', $policy->timezone) === 'America/Los_Angeles' ? 'selected' : '' }}>America/Los_Angeles</option>
                                <option value="America/Denver" {{ old('timezone', $policy->timezone) === 'America/Denver' ? 'selected' : '' }}>America/Denver</option>
                                <option value="Europe/Madrid" {{ old('timezone', $policy->timezone) === 'Europe/Madrid' ? 'selected' : '' }}>Europe/Madrid</option>
                                <option value="UTC" {{ old('timezone', $policy->timezone) === 'UTC' ? 'selected' : '' }}>UTC</option>
                            </select>
                            @error('timezone')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Escalation Settings -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Configuración de escalamiento</h6>
                        <p class="text-muted small mb-3">Configura notificaciones y escalamientos automáticos cuando se aproxima el vencimiento del SLA.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="enable_escalation" class="form-check-input" id="enableEscalation" {{ old('enable_escalation', $policy->enable_escalation) ? 'checked' : '' }}>
                                <label class="form-check-label" for="enableEscalation">
                                    Activar escalamiento automático
                                </label>
                            </div>
                            <small class="form-text text-muted">Se notificará cuando se alcance el umbral de escalamiento</small>
                        </div>
                    </div>

                    <div class="col-12 col-md-12">
                        <div class="mb-3">
                            <label class="control-label col-form-label">Umbral de escalamiento (%)</label>
                            <input type="number" name="escalation_threshold_percent" class="form-control" value="{{ old('escalation_threshold_percent', $policy->escalation_threshold_percent ?? 80) }}" min="1" max="100" placeholder="80">
                            <small class="form-text text-muted">Porcentaje del tiempo SLA para iniciar escalamiento (ej: 80 = 80%)</small>
                            @error('escalation_threshold_percent')
                                <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="col-12">
                        <hr class="my-4">
                        <h6 class="mb-1 fw-semibold">Estado</h6>
                        <p class="text-muted small mb-3">Especifica si esta política está activa y disponible para usar.</p>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="active" class="form-check-input" id="active" {{ old('active', $policy->active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="active">
                                    Activar política
                                </label>
                            </div>
                            <small class="form-text text-muted">Las políticas desactivadas no se aplicarán a nuevos documentos</small>
                        </div>
                    </div>

                    @if($policy->is_default)
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Esta es la política SLA Por defecto. Se aplicará automáticamente a todos los documentos nuevos.
                            </div>
                        </div>
                    @endif

                </div>
            </div>

            <div class="card-footer bg-light border-top">
                    <button type="submit" class="btn btn-primary w-100 mb-1">
                        Guardar
                    </button>
                    <a href="{{ route('settings.documents.sla-policies.index') }}" class="btn btn-secondary  w-100">
                        Volver
                    </a>
            </div>

        </form>

    </div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        @if (session('success'))
            toastr.success('{{ session('success') }}', 'Éxito');
        @endif

        @if (session('error'))
            toastr.error('{{ session('error') }}', 'Error');
        @endif
    });
</script>
@endpush
