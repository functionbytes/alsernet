@extends('layouts.theme')

@section('title', 'Configuración SLA')

@section('page_header')
    @include('core::components.card', ['title' => 'Políticas de Nivel de Servicio (SLA)'])
@endsection

@section('content')
    <div class="widget-content">
        @include('core::components.alerts')

        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Acuerdos de Nivel de Servicio</h5>
                        <p class="small mb-0 text-muted">Configura los tiempos máximos de respuesta y resolución de casos</p>
                    </div>
                </div>
            </div>

            <!-- Statistics Section -->
            @if($slaStats && $slaStats['sla_enabled'])
                <div class="card-body border-bottom">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-info mb-2">Cumplimiento Respuesta</h6>
                                    <h4 class="mb-0 fw-bold">{{ $slaStats['response_sla_compliance'] ?? 0 }}%</h4>
                                    <small class="text-muted">Meta: {{ $slaStats['response_hours_target'] ?? 24 }} horas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">Cumplimiento Resolución</h6>
                                    <h4 class="mb-0 fw-bold">{{ $slaStats['resolution_sla_compliance'] ?? 0 }}%</h4>
                                    <small class="text-muted">Meta: {{ $slaStats['resolution_hours_target'] ?? 72 }} horas</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light-secondary stat-card h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-warning mb-2">Estado del SLA</h6>
                                    <p class="mb-0">
                                        <span class="badge bg-success-subtle text-success fw-bold">SLA Habilitado</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Configuration Form -->
            <div class="card-body">
                <form action="{{ route('settings.attention.configurations.sla.update') }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <!-- Enable SLA -->
                    <div class="mb-4">
                        <div class="mb-2">
                            <label class="form-label" for="sla_enabled">SLA</label>
                            <select class="form-select" name="sla_enabled" id="sla_enabled">
                                <option value="yes" {{ ($settings['sla']['sla_enabled']['value'] ?? 'no') === 'yes' ? 'selected' : '' }}>Activado</option>
                                <option value="no" {{ ($settings['sla']['sla_enabled']['value'] ?? 'no') === 'no' ? 'selected' : '' }}>Desactivado</option>
                            </select>
                            <small class="text-muted">Activa el seguimiento de acuerdos de nivel de servicio</small>
                        </div>
                    </div>

                    <hr>

                    <!-- Response and Resolution Times -->
                    <h6 class="fw-bold mb-3">Tiempos objetivo</h6>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="sla_response_hours" class="form-label">
                                Horas para primera respuesta
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="sla_response_hours" name="sla_response_hours"
                                min="1" max="720" value="{{ $settings['sla']['sla_response_hours']['value'] ?? 24 }}" required>
                            <small class="text-muted">Horas máximas para responder a un caso (1-720)</small>
                        </div>

                        <div class="col-md-6">
                            <label for="sla_resolution_hours" class="form-label">
                                Horas para resolución
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="sla_resolution_hours" name="sla_resolution_hours"
                                min="1" max="720" value="{{ $settings['sla']['sla_resolution_hours']['value'] ?? 72 }}" required>
                            <small class="text-muted">Horas máximas para resolver un caso (1-720)</small>
                        </div>
                    </div>

                    <hr>

                    <!-- Business Hours -->
                    <h6 class="fw-bold mb-3">Horario laboral</h6>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sla_business_hours_only" name="sla_business_hours_only"
                                {{ ($settings['sla']['sla_business_hours_only']['value'] ?? 'no') === 'yes' ? 'checked' : '' }}>
                            <label class="form-check-label" for="sla_business_hours_only">
                                Solo horario laboral
                            </label>
                            <small class="d-block text-muted ms-4">Calcula SLA solo durante horario laboral</small>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="sla_business_hours_start" class="form-label">
                                Inicio horario laboral
                                <span class="text-danger">*</span>
                            </label>
                            <input type="time" class="form-control" id="sla_business_hours_start" name="sla_business_hours_start"
                                value="{{ $settings['sla']['sla_business_hours_start']['value'] ?? '09:00' }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="sla_business_hours_end" class="form-label">
                                Fin horario laboral
                                <span class="text-danger">*</span>
                            </label>
                            <input type="time" class="form-control" id="sla_business_hours_end" name="sla_business_hours_end"
                                value="{{ $settings['sla']['sla_business_hours_end']['value'] ?? '17:00' }}" required>
                        </div>
                    </div>

                    <!-- Weekends Exclusion -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sla_exclude_weekends" name="sla_exclude_weekends"
                                {{ ($settings['sla']['sla_exclude_weekends']['value'] ?? 'no') === 'yes' ? 'checked' : '' }}>
                            <label class="form-check-label" for="sla_exclude_weekends">
                                Excluir fines de semana
                            </label>
                            <small class="d-block text-muted ms-4">No cuenta sábados y domingos para el cálculo del SLA</small>
                        </div>
                    </div>

                    <hr>

                    <!-- Auto Escalation -->
                    <h6 class="fw-bold mb-3">Escalamiento automático</h6>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sla_auto_escalate" name="sla_auto_escalate"
                                {{ ($settings['sla']['sla_auto_escalate']['value'] ?? 'no') === 'yes' ? 'checked' : '' }}>
                            <label class="form-check-label" for="sla_auto_escalate">
                                Escalamiento automático
                            </label>
                            <small class="d-block text-muted ms-4">Escala automáticamente cuando se excede el SLA</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="sla_escalation_email" class="form-label">
                            Email de escalamiento
                            <small class="text-muted">(opcional)</small>
                        </label>
                        <input type="email" class="form-control" id="sla_escalation_email" name="sla_escalation_email"
                            value="{{ $settings['sla']['sla_escalation_email']['value'] ?? '' }}" placeholder="correo@ejemplo.com">
                        <small class="text-muted">Email donde se notificarán los escalamientos</small>
                    </div>

                    <hr>

                    <!-- Submit Button -->
                    <div class="d-grid gap-2 d-sm-flex gap-2 justify-content-end">
                        <a href="{{ route('settings.attention.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
