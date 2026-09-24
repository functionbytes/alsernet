@extends('layouts.theme')

@section('title', 'Editar política SLA')

@section('page_header')
    @include('core::components.card', ['title' => 'Editar política SLA'])
@endsection

@section('content')

    <div class="row g-3">

        {{-- Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <form id="slaForm" action="{{ route('manager.helpdesk.settings.ticket-sla-policies.update', $policy->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-header border-bottom">
                        <h5 class="mb-0 fw-bold">{{ $policy->name }}</h5>
                        <small class="text-muted">Modifica los parametros de la política SLA</small>
                    </div>

                    <div class="card-body">
                        @include('core::components.alerts')

                        {{-- Información básica --}}
                        <h6 class="fw-semibold mb-1">Información básica</h6>
                        <p class="text-muted small mb-3">Nombre, descripción y zona horaria aplicable a la política</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $policy->name) }}"
                                       required>
                                @error('name')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea name="description"
                                          class="form-control @error('description') is-invalid @enderror"
                                          rows="2">{{ old('description', $policy->description) }}</textarea>
                                @error('description')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Zona horaria <span class="text-danger">*</span></label>
                                <select name="timezone" class="form-select select2 @error('timezone') is-invalid @enderror" required>
                                    @php
                                        $timezones = [
                                            'UTC'                => 'UTC',
                                            'America/Madrid'     => 'America/Madrid',
                                            'Europe/Madrid'      => 'Europe/Madrid',
                                            'Europe/Lisbon'      => 'Europe/Lisbon',
                                            'America/New_York'   => 'America/New_York',
                                            'America/Mexico_City'=> 'America/Mexico_City',
                                        ];
                                        $selected = old('timezone', $policy->timezone ?? 'UTC');
                                    @endphp
                                    @foreach($timezones as $value => $label)
                                        <option value="{{ $value }}" {{ $selected === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('timezone')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Cuándo se aplica (24-sep-2026): una política puede limitarse
                             a una prioridad y/o a clientes VIP. Entre varias que casan
                             gana la más específica (TicketSlaPolicy::resolveForTicket). --}}
                        <h6 class="fw-semibold mb-1">Cuándo se aplica</h6>
                        <p class="text-muted small mb-3">Déjalo en «Cualquiera» para que valga para todos los tickets. Si varias políticas encajan, se usa la que tenga más condiciones.</p>
                        <div class="row g-3 mb-4">
                            @php
                                $sPrio = (string) old('applies_to_priority', $policy->applies_to_priority);
                                $sVip = old('applies_to_vip', is_null($policy->applies_to_vip) ? null : (int) $policy->applies_to_vip);
                                $sVip = $sVip === null || $sVip === '' ? '' : (string) (int) $sVip;
                            @endphp
                            <div class="col-md-6">
                                <label class="form-label" for="applies_to_priority">Prioridad del ticket</label>
                                <select name="applies_to_priority" id="applies_to_priority" class="form-select select2">
                                    <option value="" @selected($sPrio === '')>Cualquiera</option>
                                    <option value="urgent" @selected($sPrio === 'urgent')>Urgente</option>
                                    <option value="high" @selected($sPrio === 'high')>Alta</option>
                                    <option value="normal" @selected($sPrio === 'normal')>Normal</option>
                                    <option value="low" @selected($sPrio === 'low')>Baja</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="applies_to_vip">Tipo de cliente</label>
                                <select name="applies_to_vip" id="applies_to_vip" class="form-select select2">
                                    <option value="" @selected($sVip === '')>Cualquiera</option>
                                    <option value="1" @selected($sVip === '1')>Solo clientes VIP</option>
                                    <option value="0" @selected($sVip === '0')>Solo clientes no VIP</option>
                                </select>
                            </div>
                        </div>

                        {{-- Tiempos de respuesta --}}
                        <h6 class="fw-semibold mb-1">Tiempos de respuesta</h6>
                        <p class="text-muted small mb-3">Limites en minutos para primera respuesta, respuestas siguientes y resolucion del ticket</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12 col-md-4">
                                <label class="form-label">Primera respuesta <span class="text-danger">*</span></label>
                                <input type="number" name="first_response_time"
                                       class="form-control @error('first_response_time') is-invalid @enderror"
                                       value="{{ old('first_response_time', $policy->first_response_time) }}" min="1" required>
                                <div class="form-text">en minutos (ej: 60 = 1 hora)</div>
                                @error('first_response_time')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Siguiente respuesta</label>
                                <input type="number" name="next_response_time"
                                       class="form-control @error('next_response_time') is-invalid @enderror"
                                       value="{{ old('next_response_time', $policy->next_response_time) }}" min="1">
                                <div class="form-text">en minutos (ej: 60 = 1 hora)</div>
                                @error('next_response_time')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Resolucion <span class="text-danger">*</span></label>
                                <input type="number" name="resolution_time"
                                       class="form-control @error('resolution_time') is-invalid @enderror"
                                       value="{{ old('resolution_time', $policy->resolution_time) }}" min="1" required>
                                <div class="form-text">en minutos (ej: 60 = 1 hora)</div>
                                @error('resolution_time')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Horario --}}
                        <h6 class="fw-semibold mb-1">Horario</h6>
                        <p class="text-muted small mb-3">Si aplica todo el dia o solo en horario laboral</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <label for="business_hours_only" class="form-label">Modo de horario</label>
                                <select class="form-select select2 @error('business_hours_only') is-invalid @enderror"
                                        name="business_hours_only" id="business_hours_only">
                                    <option value="0" {{ old('business_hours_only', $policy->business_hours_only ? '1' : '0') == '0' ? 'selected' : '' }}>24/7 — la política aplica todo el tiempo</option>
                                    <option value="1" {{ old('business_hours_only', $policy->business_hours_only ? '1' : '0') == '1' ? 'selected' : '' }}>Solo horario laboral — aplica según horario configurado</option>
                                </select>
                                @error('business_hours_only')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12" id="business-hours-config" style="{{ old('business_hours_only', $policy->business_hours_only ? '1' : '0') == '1' ? '' : 'display:none;' }}">
                                <label class="form-label">Configuración por dia</label>
                                @php
                                    $days = [
                                        'monday'    => 'Lunes',
                                        'tuesday'   => 'Martes',
                                        'wednesday' => 'Miercoles',
                                        'thursday'  => 'Jueves',
                                        'friday'    => 'Viernes',
                                        'saturday'  => 'Sabado',
                                        'sunday'    => 'Domingo',
                                    ];
                                    $savedHours = is_array($policy->business_hours) ? $policy->business_hours : (json_decode($policy->business_hours, true) ?? []);
                                    $defaultEnabled = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                                @endphp
                                <div class="border rounded p-3">
                                    @foreach($days as $key => $label)
                                        @php
                                            $dayData = $savedHours[$key] ?? [];
                                            $isEnabled = !empty($dayData) || in_array($key, $defaultEnabled);
                                        @endphp
                                        <div class="d-flex align-items-center gap-3 mb-2">
                                            <div style="width:100px;">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input"
                                                           name="business_hours[{{ $key }}][enabled]"
                                                           id="bh_{{ $key }}" value="1"
                                                           {{ $isEnabled ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="bh_{{ $key }}">{{ $label }}</label>
                                                </div>
                                            </div>
                                            <input type="time" name="business_hours[{{ $key }}][start]"
                                                   class="form-control form-control-sm" style="width:120px;"
                                                   value="{{ old('business_hours.' . $key . '.start', $dayData['start'] ?? '09:00') }}">
                                            <span class="text-muted small">a</span>
                                            <input type="time" name="business_hours[{{ $key }}][end]"
                                                   class="form-control form-control-sm" style="width:120px;"
                                                   value="{{ old('business_hours.' . $key . '.end', $dayData['end'] ?? '17:00') }}">
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                        </div>

                        {{-- Multiplicadores por prioridad --}}
                        <h6 class="fw-semibold mb-1">Multiplicadores por prioridad</h6>
                        <p class="text-muted small mb-3">Factor aplicado al tiempo según la prioridad del ticket</p>
                        <div class="row g-3 mb-4">

                            @php
                                $savedMultipliers = is_array($policy->priority_multipliers) ? $policy->priority_multipliers : (json_decode($policy->priority_multipliers, true) ?? []);
                                $multipliers = ['urgent' => ['label' => 'Urgente', 'default' => 0.25], 'high' => ['label' => 'Alta', 'default' => 0.5], 'normal' => ['label' => 'Normal', 'default' => 1.0], 'low' => ['label' => 'Baja', 'default' => 2.0]];
                            @endphp
                            @foreach($multipliers as $key => $cfg)
                                <div class="col-6 col-md-3">
                                    <label class="form-label">{{ $cfg['label'] }}</label>
                                    <input type="number" name="priority_multipliers[{{ $key }}]"
                                           class="form-control"
                                           value="{{ old('priority_multipliers.' . $key, $savedMultipliers[$key] ?? $cfg['default']) }}"
                                           step="0.25" min="0.25">
                                </div>
                            @endforeach

                        </div>

                        {{-- Escalamiento --}}
                        <h6 class="fw-semibold mb-1">Escalamiento</h6>
                        <p class="text-muted small mb-3">Notificar cuando el ticket se acerca al vencimiento del SLA</p>
                        <div class="row g-3 mb-4">

                            <div class="col-12">
                                <label for="enable_escalation" class="form-label">Escalamiento</label>
                                <select class="form-select select2 @error('enable_escalation') is-invalid @enderror"
                                        name="enable_escalation" id="enable_escalation">
                                    <option value="0" {{ old('enable_escalation', $policy->enable_escalation ? '1' : '0') == '0' ? 'selected' : '' }}>Desactivado — no se envian alertas de escalamiento</option>
                                    <option value="1" {{ old('enable_escalation', $policy->enable_escalation ? '1' : '0') == '1' ? 'selected' : '' }}>Activo — enviar alertas al alcanzar el umbral</option>
                                </select>
                                @error('enable_escalation')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-12" id="escalation-threshold-wrap" style="{{ old('enable_escalation', $policy->enable_escalation ? '1' : '0') == '1' ? '' : 'display:none;' }}">
                                <label class="form-label">Umbral de escalamiento (%)</label>
                                <input type="number" name="escalation_threshold_percent"
                                       class="form-control @error('escalation_threshold_percent') is-invalid @enderror"
                                       value="{{ old('escalation_threshold_percent', $policy->escalation_threshold_percent ?? 80) }}"
                                       min="1" max="100">
                                <div class="form-text">Notificar cuando se use este porcentaje del tiempo disponible</div>
                                @error('escalation_threshold_percent')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>

                        {{-- Configuración --}}
                        <h6 class="fw-semibold mb-1">Configuración</h6>
                        <p class="text-muted small mb-3">Disponibilidad de esta política</p>
                        <div class="row g-3">

                            <div class="col-12">
                                <label for="active" class="form-label">Estado</label>
                                <select class="form-select select2 @error('active') is-invalid @enderror" name="active" id="active">
                                    <option value="1" {{ old('active', $policy->active ? '1' : '0') == '1' ? 'selected' : '' }}>Activa — disponible para aplicar a tickets</option>
                                    <option value="0" {{ old('active', $policy->active ? '1' : '0') == '0' ? 'selected' : '' }}>Inactiva — no se aplica a nuevos tickets</option>
                                </select>
                                @error('active')
                                    <span class="field-validation-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>

                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 mb-1">Guardar cambios</button>
                        <a href="{{ route('manager.helpdesk.settings.ticket-sla-policies.index') }}" class="btn btn-light w-100">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help panel --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Sobre el SLA</h6>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">
                        Las políticas SLA definen los compromisos de tiempo de respuesta y resolucion que el equipo de soporte debe cumplir con cada ticket.
                    </p>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Buenas practicas</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Define tiempos realistas según la capacidad del equipo</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Usa multiplicadores para diferenciar prioridades</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Activa el escalamiento para recibir alertas antes del vencimiento</li>
                        <li class="mb-0"><i class="fas fa-check-circle text-success me-2"></i> El horario laboral excluye fines de semana y horas no configuradas</li>
                    </ul>
                </div>
            </div>
            <div class="card">
                <div class="card-header border-bottom">
                    <h6 class="mb-0 fw-bold">Información del registro</h6>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-0">
                        <li class="mb-2">Creado: {{ $policy->created_at?->format('d/m/Y H:i') ?? '—' }}</li>
                        <li class="mb-0">Actualizado: {{ $policy->updated_at?->format('d/m/Y H:i') ?? '—' }}</li>
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

    $('#enable_escalation').on('change', function () {
        $('#escalation-threshold-wrap').toggle($(this).val() === '1');
    });

    $('#business_hours_only').on('change', function () {
        $('#business-hours-config').toggle($(this).val() === '1');
    });
});
</script>
@endpush
