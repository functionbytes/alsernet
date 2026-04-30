@extends('layouts.theme')

@section('title', 'Horarios de negocio')

@section('content')
<div class="container-fluid">
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-semibold mb-2">Horarios de negocio</h4>
                    <p class="text-muted mb-0">Configura los horarios de atención para que los clientes sepan cuándo está disponible tu equipo</p>
                </div>
                <form action="{{ route('settings.chat.hours.reset') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-secondary" onclick="return confirm('¿Restablecer horarios por defecto (lunes a viernes, 9:00-17:00)?')">
                        <i class="fas fa-undo me-1"></i> Restablecer
                    </button>
                </form>
            </div>
        </div>
    </div>

    @include('core::components.alerts')

    @php
        $currentTimezone = $businessHours->first()?->first()?->timezone ?? config('app.timezone');
        $now = \Carbon\Carbon::now($currentTimezone);
        $currentDay = $now->dayOfWeek;
        $currentHour = $businessHours->get($currentDay)?->first();
    @endphp

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        @if($currentHour && $currentHour->is_enabled && $currentHour->isWithinBusinessHours($now))
                            <span class="badge bg-success-subtle text-success fs-6 me-3">
                                <i class="fas fa-check-circle me-1"></i> Abierto ahora
                            </span>
                            <div>
                                <p class="mb-0">Horario de hoy: {{ \Carbon\Carbon::parse($currentHour->open_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($currentHour->close_time)->format('H:i') }}</p>
                                <small class="text-muted">Hora actual: {{ $now->format('H:i') }} ({{ $currentTimezone }})</small>
                            </div>
                        @else
                            <span class="badge bg-info-subtle text-info fs-6 me-3">
                                <i class="fas fa-times-circle me-1"></i> Cerrado ahora
                            </span>
                            <div>
                                @if($currentHour && $currentHour->is_enabled)
                                    <p class="mb-0">Horario de hoy: {{ \Carbon\Carbon::parse($currentHour->open_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($currentHour->close_time)->format('H:i') }}</p>
                                @else
                                    <p class="mb-0">Cerrado hoy</p>
                                @endif
                                <small class="text-muted">Hora actual: {{ $now->format('H:i') }} ({{ $currentTimezone }})</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('settings.chat.hours.update') }}" method="POST" id="hoursForm">
        @csrf
        @method('PUT')

        <div class="card mb-4">
            <div class="card-header border-bottom p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Configuración de horarios</h5>
                    <button type="submit" class="btn btn-primary" id="saveBtn">
                        <i class="fas fa-save me-1"></i> Guardar cambios
                    </button>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="control-label col-form-label">Zona horaria</label>
                        <select name="timezone" id="timezone" class="form-control select2" required>
                            @php $timezones = timezone_identifiers_list(); @endphp
                            @foreach($timezones as $tz)
                                <option value="{{ $tz }}" {{ $tz === $currentTimezone ? 'selected' : '' }}>{{ $tz }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Todos los horarios se mostrarán en esta zona horaria</small>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 200px;">Día</th>
                                <th style="width: 120px;">Estado</th>
                                <th>Hora apertura</th>
                                <th>Hora cierre</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $dayNames = [
                                    0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes',
                                    3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado',
                                ];
                            @endphp
                            @foreach($daysOfWeek as $dayNum => $dayName)
                                @php $hour = $businessHours->get($dayNum)?->first(); @endphp
                                @if($hour)
                                <tr>
                                    <td>
                                        <strong>{{ $dayNames[$dayNum] ?? $dayName }}</strong>
                                        <input type="hidden" name="business_hours[{{ $loop->index }}][id]" value="{{ $hour->id }}">
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input day-toggle"
                                                   type="checkbox" role="switch"
                                                   id="enabled_{{ $dayNum }}"
                                                   name="business_hours[{{ $loop->index }}][is_enabled]"
                                                   value="1"
                                                   data-day="{{ $dayNum }}"
                                                   {{ $hour->is_enabled ? 'checked' : '' }}>
                                            <label class="form-check-label" for="enabled_{{ $dayNum }}">
                                                <span class="badge {{ $hour->is_enabled ? 'bg-success' : 'bg-secondary' }}" id="badge_{{ $dayNum }}">
                                                    {{ $hour->is_enabled ? 'Abierto' : 'Cerrado' }}
                                                </span>
                                            </label>
                                        </div>
                                        @if(!$hour->is_enabled)
                                            <input type="hidden" name="business_hours[{{ $loop->index }}][is_enabled]" value="0">
                                        @endif
                                    </td>
                                    <td>
                                        <input type="time" class="form-control time-input"
                                               name="business_hours[{{ $loop->index }}][open_time]"
                                               id="open_{{ $dayNum }}"
                                               value="{{ \Carbon\Carbon::parse($hour->open_time)->format('H:i') }}"
                                               {{ !$hour->is_enabled ? 'disabled' : '' }} required>
                                    </td>
                                    <td>
                                        <input type="time" class="form-control time-input"
                                               name="business_hours[{{ $loop->index }}][close_time]"
                                               id="close_{{ $dayNum }}"
                                               value="{{ \Carbon\Carbon::parse($hour->close_time)->format('H:i') }}"
                                               {{ !$hour->is_enabled ? 'disabled' : '' }} required>
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="enableWeekdays">
                            <i class="fas fa-check-circle me-1"></i> Activar días laborables
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="disableWeekends">
                            <i class="fas fa-times-circle me-1"></i> Desactivar fines de semana
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="set9to5">
                            <i class="fas fa-clock me-1"></i> Establecer 9:00 - 17:00
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('.day-toggle').on('change', function() {
        var day = $(this).data('day');
        var isChecked = $(this).is(':checked');
        var $badge = $('#badge_' + day);
        var $open = $('#open_' + day);
        var $close = $('#close_' + day);

        $open.prop('disabled', !isChecked);
        $close.prop('disabled', !isChecked);

        if (isChecked) {
            $badge.text('Abierto').removeClass('bg-secondary').addClass('bg-success');
        } else {
            $badge.text('Cerrado').removeClass('bg-success').addClass('bg-secondary');
        }
    });

    $('#enableWeekdays').on('click', function() {
        [1, 2, 3, 4, 5].forEach(function(day) {
            var $toggle = $('#enabled_' + day);
            if ($toggle.length && !$toggle.is(':checked')) {
                $toggle.prop('checked', true).trigger('change');
            }
        });
    });

    $('#disableWeekends').on('click', function() {
        [0, 6].forEach(function(day) {
            var $toggle = $('#enabled_' + day);
            if ($toggle.length && $toggle.is(':checked')) {
                $toggle.prop('checked', false).trigger('change');
            }
        });
    });

    $('#set9to5').on('click', function() {
        $('.time-input').each(function() {
            if (!$(this).prop('disabled')) {
                if (this.id.startsWith('open_')) {
                    $(this).val('09:00');
                } else if (this.id.startsWith('close_')) {
                    $(this).val('17:00');
                }
            }
        });
    });
});
</script>
@endpush
