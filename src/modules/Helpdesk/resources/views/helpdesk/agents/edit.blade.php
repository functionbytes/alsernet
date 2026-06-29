@extends('layouts.theme')

@section('title', 'Editar agente - ' . ($agent->firstname ?? '') . ' ' . ($agent->lastname ?? ''))

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-user-edit me-2 text-primary"></i>
                Configuración del agente
            </h4>
            <p class="text-muted mb-0">
                <a href="{{ route('manager.helpdesk.agents.index') }}" class="text-muted text-decoration-none">Agentes</a>
                <i class="fas fa-chevron-right mx-1 bv-text-small"></i>
                <a href="{{ route('manager.helpdesk.agents.show', $agent) }}" class="text-muted text-decoration-none">
                    {{ $agent->firstname }} {{ $agent->lastname }}
                </a>
                <i class="fas fa-chevron-right mx-1 bv-text-small"></i>
                Editar
            </p>
        </div>
    </div>

    @include('core::components.alerts')

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-sliders-h me-2 text-muted"></i>
                        Disponibilidad y límites
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('manager.helpdesk.agents.update', $agent) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Accepts conversations --}}
                        <div class="mb-4">
                            <label for="accepts_conversations" class="form-label fw-semibold">
                                Disponibilidad
                            </label>
                            <select name="accepts_conversations" id="accepts_conversations"
                                    class="form-select @error('accepts_conversations') is-invalid @enderror">
                                <option value="yes" @selected(($agentSettings->accepts_conversations ?? 'yes') === 'yes')>
                                    Siempre disponible
                                </option>
                                <option value="working_hours" @selected(($agentSettings->accepts_conversations ?? '') === 'working_hours')>
                                    Solo en horario laboral
                                </option>
                                <option value="no" @selected(($agentSettings->accepts_conversations ?? '') === 'no')>
                                    No disponible
                                </option>
                            </select>
                            @error('accepts_conversations')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted">
                                Controla si el agente recibe nuevas conversaciones asignadas automáticamente.
                            </div>
                        </div>

                        {{-- Schedule block: visible only when accepts_conversations = working_hours --}}
                        @php
                            $wh = $agentSettings->working_hours ?? null;
                            $days = [
                                'monday'    => ['label' => 'Lunes',      'default_enabled' => true],
                                'tuesday'   => ['label' => 'Martes',     'default_enabled' => true],
                                'wednesday' => ['label' => 'Miércoles',  'default_enabled' => true],
                                'thursday'  => ['label' => 'Jueves',     'default_enabled' => true],
                                'friday'    => ['label' => 'Viernes',    'default_enabled' => true],
                                'saturday'  => ['label' => 'Sábado',     'default_enabled' => false],
                                'sunday'    => ['label' => 'Domingo',    'default_enabled' => false],
                            ];
                        @endphp

                        <div id="schedule-block" class="mb-4" style="display:none">
                            <label class="form-label fw-semibold">Horario semanal</label>
                            <p class="text-muted small mb-3">Define los días y horas en que el agente acepta conversaciones</p>

                            @foreach ($days as $key => $day)
                                @php
                                    $enabled = $wh[$key]['enabled'] ?? $day['default_enabled'];
                                    $start   = $wh[$key]['start']   ?? '09:00';
                                    $end     = $wh[$key]['end']     ?? '18:00';
                                @endphp
                                <div class="row g-2 align-items-center mb-2 border-bottom pb-2">
                                    <div class="col-md-3 d-flex align-items-center gap-2">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input day-toggle" type="checkbox"
                                                   name="working_hours[{{ $key }}][enabled]" value="1"
                                                   id="wh_{{ $key }}_enabled"
                                                   {{ $enabled ? 'checked' : '' }}>
                                        </div>
                                        <label class="form-check-label fw-semibold" for="wh_{{ $key }}_enabled">
                                            {{ $day['label'] }}
                                        </label>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="time" class="form-control form-control-sm day-time"
                                               name="working_hours[{{ $key }}][start]"
                                               value="{{ $start }}"
                                               {{ ! $enabled ? 'disabled' : '' }}>
                                    </div>
                                    <div class="col-auto d-flex align-items-center text-muted">a</div>
                                    <div class="col-md-4">
                                        <input type="time" class="form-control form-control-sm day-time"
                                               name="working_hours[{{ $key }}][end]"
                                               value="{{ $end }}"
                                               {{ ! $enabled ? 'disabled' : '' }}>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Max concurrent conversations --}}
                        <div class="mb-4">
                            <label for="max_concurrent_conversations" class="form-label fw-semibold">
                                Máximo de conversaciones simultáneas
                            </label>
                            <input type="number" name="max_concurrent_conversations" id="max_concurrent_conversations"
                                   class="form-control @error('max_concurrent_conversations') is-invalid @enderror"
                                   min="1" max="100"
                                   value="{{ old('max_concurrent_conversations', $agentSettings->max_concurrent_conversations ?? 5) }}">
                            @error('max_concurrent_conversations')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted">
                                Número máximo de conversaciones abiertas asignadas a este agente al mismo tiempo.
                            </div>
                        </div>

                        {{-- Vacation mode --}}
                        <div class="mb-4">
                            <label for="vacation_until" class="form-label fw-semibold">
                                Modo vacaciones
                            </label>
                            <input type="datetime-local" name="vacation_until" id="vacation_until"
                                   class="form-control @error('vacation_until') is-invalid @enderror"
                                   value="{{ old('vacation_until', $agentSettings->vacation_until ? $agentSettings->vacation_until->format('Y-m-d\TH:i') : '') }}">
                            @error('vacation_until')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted">
                                El agente no recibirá nuevas conversaciones hasta esta fecha y hora. Deja en blanco para desactivar.
                            </div>
                            @if($agentSettings->isOnVacation())
                                <div class="alert alert-warning mt-2 py-2">
                                    <i class="fas fa-umbrella-beach me-1"></i>
                                    El agente está actualmente en modo vacaciones hasta {{ $agentSettings->vacation_until->format('d/m/Y H:i') }}.
                                </div>
                            @endif
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Guardar cambios
                            </button>
                            <a href="{{ route('manager.helpdesk.agents.show', $agent) }}" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(function () {
    const $sel   = $('#accepts_conversations');
    const $block = $('#schedule-block');

    function toggleSchedule() {
        $block.toggle($sel.val() === 'working_hours');
    }

    function toggleDayInputs($row, enabled) {
        $row.find('.day-time').prop('disabled', !enabled);
    }

    // Bind day toggle switches
    $(document).on('change', '.day-toggle', function () {
        toggleDayInputs($(this).closest('.row'), this.checked);
    });

    toggleSchedule();
    $sel.on('change', toggleSchedule);
});
</script>
@endpush
