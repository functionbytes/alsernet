@extends('layouts.theme')

@section('title', 'Nueva política SLA')

@section('content')
<div class="container-fluid">

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('manager.helpdesk.backups.tickets.sla-policies.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
        <div>
            <h4 class="mb-0 fw-bold">Nueva política SLA</h4>
            <p class="text-muted mb-0">Define tiempos de respuesta y resolución</p>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ route('manager.helpdesk.backups.tickets.sla-policies.store') }}">
            @csrf

            <div class="card-body">
                <div class="row g-3">

                    <div class="col-12">
                        <h6 class="fw-semibold text-uppercase text-muted">Informacion general</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="Ej: SLA Estándar" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Zona horaria <span class="text-danger">*</span></label>
                        <select name="timezone" class="form-select @error('timezone') is-invalid @enderror" required>
                            @foreach(timezone_identifiers_list() as $tz)
                                <option value="{{ $tz }}" {{ old('timezone', config('app.timezone')) === $tz ? 'selected' : '' }}>
                                    {{ $tz }}
                                </option>
                            @endforeach
                        </select>
                        @error('timezone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Descripcion <small class="text-muted fw-normal">(opcional)</small></label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                  rows="2" placeholder="Describe el propósito de esta política">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12"><hr class="my-2"></div>
                    <div class="col-12">
                        <h6 class="fw-semibold text-uppercase text-muted">Tiempos (en minutos)</h6>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Primera respuesta <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="first_response_time"
                                   class="form-control @error('first_response_time') is-invalid @enderror"
                                   value="{{ old('first_response_time', 60) }}" min="1" required>
                            <span class="input-group-text">min</span>
                        </div>
                        @error('first_response_time')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Siguiente respuesta <small class="text-muted fw-normal">(opcional)</small></label>
                        <div class="input-group">
                            <input type="number" name="next_response_time"
                                   class="form-control @error('next_response_time') is-invalid @enderror"
                                   value="{{ old('next_response_time') }}" min="1">
                            <span class="input-group-text">min</span>
                        </div>
                        @error('next_response_time')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Resolución <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="resolution_time"
                                   class="form-control @error('resolution_time') is-invalid @enderror"
                                   value="{{ old('resolution_time', 480) }}" min="1" required>
                            <span class="input-group-text">min</span>
                        </div>
                        @error('resolution_time')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12"><hr class="my-2"></div>
                    <div class="col-12">
                        <h6 class="fw-semibold text-uppercase text-muted">Escalación</h6>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input type="hidden" name="enable_escalation" value="0">
                            <input type="checkbox" name="enable_escalation" class="form-check-input" id="enableEscalation"
                                   value="1" {{ old('enable_escalation') ? 'checked' : '' }}>
                            <label class="form-check-label" for="enableEscalation">
                                <strong>Habilitar escalación</strong>
                                <small class="d-block text-muted">Notificar cuando se acerca el vencimiento</small>
                            </label>
                        </div>
                    </div>

                    <div class="col-md-6" id="escalationThresholdWrap" style="{{ old('enable_escalation') ? '' : 'display:none;' }}">
                        <label class="form-label fw-semibold">Umbral de escalación (%)</label>
                        <div class="input-group">
                            <input type="number" name="escalation_threshold_percent"
                                   class="form-control @error('escalation_threshold_percent') is-invalid @enderror"
                                   value="{{ old('escalation_threshold_percent', 80) }}" min="1" max="100">
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="form-text">Notificar cuando se use este porcentaje del tiempo disponible</div>
                        @error('escalation_threshold_percent')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12"><hr class="my-2"></div>

                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input type="hidden" name="business_hours_only" value="0">
                            <input type="checkbox" name="business_hours_only" class="form-check-input" id="businessHours"
                                   value="1" {{ old('business_hours_only') ? 'checked' : '' }}>
                            <label class="form-check-label" for="businessHours">
                                <strong>Solo horario laboral</strong>
                                <small class="d-block text-muted">Calcular SLA solo durante horas de trabajo</small>
                            </label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input type="hidden" name="active" value="0">
                            <input type="checkbox" name="active" class="form-check-input" id="active"
                                   value="1" {{ old('active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">
                                <strong>Activa</strong>
                                <small class="d-block text-muted">La política está disponible para asignar</small>
                            </label>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Guardar
                </button>
                <a href="{{ route('manager.helpdesk.backups.tickets.sla-policies.index') }}" class="btn btn-secondary px-4">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#enableEscalation').on('change', function () {
        $('#escalationThresholdWrap').toggle(this.checked);
    });
});
</script>
@endpush
