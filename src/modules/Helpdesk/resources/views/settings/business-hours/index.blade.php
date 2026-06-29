@extends('layouts.theme')

@section('title', 'Horarios de atención')

@section('content')

    @include('core::components.alerts')

    <form action="{{ route('settings.helpdesk.business-hours.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card">

                {{-- Header --}}
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Horarios de atención</h5>
                            <p class="small mb-0 text-muted">Define los días y horas en que tu equipo está disponible para atender solicitudes</p>
                        </div>
                        <a href="{{ route('settings.helpdesk.business-hours.reset') }}"
                            class="btn btn-outline-secondary btn-sm"
                            onclick="event.preventDefault(); window.__confirm('¿Restablecer los horarios a los valores predeterminados?', function () { window.location.href = '{{ route('settings.helpdesk.business-hours.reset') }}'; });">
                            <i class="fas fa-rotate-left me-1"></i> Restablecer
                        </a>
                    </div>
                </div>

                {{-- Timezone selector --}}
                <div class="card-body border-bottom">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-4">
                            <label for="timezone" class="form-label fw-semibold">
                                <i class="fas fa-globe me-1 text-muted"></i> Zona horaria
                            </label>
                            <p class="small text-muted mb-0">Se aplica a todos los días configurados</p>
                        </div>
                        <div class="col-md-8">
                            <select name="timezone" id="timezone" class="form-select @error('timezone') is-invalid @enderror">
                                @foreach($timezones as $value => $label)
                                    <option value="{{ $value }}" {{ ($hours->first()?->timezone ?? 'America/Mexico_City') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('timezone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Days table --}}
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Día</th>
                                    <th class="text-center">Abierto</th>
                                    <th>Hora de apertura</th>
                                    <th>Hora de cierre</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($hours as $hour)
                                    <tr class="business-hour-row" data-day="{{ $hour->day_of_week }}">
                                        <td class="ps-3">
                                            <span class="fw-semibold">{{ $hour->day_name }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-flex justify-content-center mb-0">
                                                <input
                                                    class="form-check-input day-toggle"
                                                    type="checkbox"
                                                    name="hours[{{ $hour->day_of_week }}][is_open]"
                                                    id="is_open_{{ $hour->day_of_week }}"
                                                    value="1"
                                                    data-day="{{ $hour->day_of_week }}"
                                                    {{ $hour->is_open ? 'checked' : '' }}>
                                            </div>
                                        </td>
                                        <td>
                                            <input
                                                type="time"
                                                class="form-control time-input"
                                                name="hours[{{ $hour->day_of_week }}][opens_at]"
                                                id="opens_at_{{ $hour->day_of_week }}"
                                                value="{{ $hour->opens_at ? \Illuminate\Support\Str::substr($hour->opens_at, 0, 5) : '09:00' }}"
                                                {{ ! $hour->is_open ? 'disabled' : '' }}>
                                            @error("hours.{$hour->day_of_week}.opens_at")
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input
                                                type="time"
                                                class="form-control time-input"
                                                name="hours[{{ $hour->day_of_week }}][closes_at]"
                                                id="closes_at_{{ $hour->day_of_week }}"
                                                value="{{ $hour->closes_at ? \Illuminate\Support\Str::substr($hour->closes_at, 0, 5) : '18:00' }}"
                                                {{ ! $hour->is_open ? 'disabled' : '' }}>
                                            @error("hours.{$hour->day_of_week}.closes_at")
                                                <div class="text-danger small mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="card-footer bg-white border-top p-3">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-save me-1"></i> Guardar horarios
                    </button>
                    <a href="{{ route('manager.helpdesk.settings.tickets.general') }}" class="btn btn-light w-100">
                        Cancelar
                    </a>
                </div>

            </div>
    </form>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    function toggleDayInputs(day, isOpen) {
        var $row = $('[data-day="' + day + '"]').closest('tr');
        $row.find('.time-input').prop('disabled', !isOpen);

        if (isOpen) {
            $row.removeClass('table-secondary');
        } else {
            $row.addClass('table-secondary');
        }
    }

    // Initialize state on load
    $('.day-toggle').each(function () {
        toggleDayInputs($(this).data('day'), $(this).is(':checked'));
    });

    // Toggle on change
    $(document).on('change', '.day-toggle', function () {
        toggleDayInputs($(this).data('day'), $(this).is(':checked'));
    });

    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
