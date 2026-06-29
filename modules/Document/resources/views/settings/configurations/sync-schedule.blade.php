@extends('layouts.theme')

@section('title', 'Programación de Sincronización de Bloqueos')

@section('content')

    @include('core::components.card', ['title' => 'Programación de Sincronización de Bloqueos'])

    @if ($message = session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($message = session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-exclamation me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-exclamation me-2"></i> Por favor, corrige los siguientes errores:
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('settings.documents.configurations.sync-schedule.update') }}"
          method="POST" class="needs-validation" novalidate>
        @csrf
        @method('PATCH')

        <div class="card">
            <!-- Estado actual -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $syncSettings['sync_count'] ?? '0' }}</h4>
                                        <small class="text-muted">Bloqueos sincronizados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Productos únicos</h6>
                                        <h4 class="mb-1 fw-bold">{{ $syncSettings['unique_products'] ?? '0' }}</h4>
                                        <small class="text-muted">Productos bloqueados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-warning mb-2">Próxima ejecución</h6>
                                        <h6 class="mb-1 fw-bold">{{ $syncSettings['next_run'] ?? 'No programado' }}</h6>
                                        <small class="text-muted">Fecha estimada</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-info mb-2">Última sincronización</h6>
                                        <h6 class="mb-1 fw-bold text-truncate" title="{{ $syncSettings['last_sync_full'] ?? '' }}">
                                            {{ $syncSettings['last_sync'] ?? 'Nunca' }}
                                        </h6>
                                        <small class="text-muted">{{ $syncSettings['last_sync_full'] ?? '' }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">

                <!-- Habilitar sincronización automática -->
                <div class="mb-4">
                    <h6 class="mb-1 fw-bold text-dark">Sincronización automática</h6>
                    <p class="text-muted small mb-3">
                        Activa esta opción para que el sistema ejecute automáticamente la sincronización de bloqueos
                        desde PrestaShop según el horario configurado.
                    </p>

                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="blockadeSyncEnabled"
                               name="blockade_sync_enabled" value="1"
                               {{ ($syncSettings['blockade_sync_enabled'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="blockadeSyncEnabled">
                            <strong>Habilitar</strong> sincronización automática programada
                        </label>
                    </div>
                </div>

                <hr class="my-4" id="scheduleSectionHr" style="{{ ($syncSettings['blockade_sync_enabled'] ?? false) ? '' : 'display:none' }}">

                <!-- Frecuencia -->
                <div class="mb-4" id="scheduleSection" style="{{ ($syncSettings['blockade_sync_enabled'] ?? false) ? '' : 'display:none' }}">
                    <h6 class="mb-1 fw-bold text-dark">Frecuencia de ejecución</h6>
                    <p class="text-muted small mb-3">
                        Selecciona cada cuánto tiempo debe ejecutarse la sincronización automáticamente.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Frecuencia</label>
                        <select class="form-select select2" id="blockadeSyncFrequency" name="blockade_sync_frequency" style="width:100%">
                            <option value="manual"
                                {{ ($syncSettings['blockade_sync_frequency'] ?? 'manual') === 'manual' ? 'selected' : '' }}>
                                Solo manual (no programar)
                            </option>
                            <option value="hourly"
                                {{ ($syncSettings['blockade_sync_frequency'] ?? '') === 'hourly' ? 'selected' : '' }}>
                                Cada hora
                            </option>
                            <option value="every_2_hours"
                                {{ ($syncSettings['blockade_sync_frequency'] ?? '') === 'every_2_hours' ? 'selected' : '' }}>
                                Cada 2 horas
                            </option>
                            <option value="every_6_hours"
                                {{ ($syncSettings['blockade_sync_frequency'] ?? '') === 'every_6_hours' ? 'selected' : '' }}>
                                Cada 6 horas
                            </option>
                            <option value="every_12_hours"
                                {{ ($syncSettings['blockade_sync_frequency'] ?? '') === 'every_12_hours' ? 'selected' : '' }}>
                                Cada 12 horas
                            </option>
                            <option value="daily"
                                {{ ($syncSettings['blockade_sync_frequency'] ?? '') === 'daily' ? 'selected' : '' }}>
                                Diariamente a una hora específica
                            </option>
                            <option value="weekly"
                                {{ ($syncSettings['blockade_sync_frequency'] ?? '') === 'weekly' ? 'selected' : '' }}>
                                Semanalmente (domingo)
                            </option>
                            <option value="custom"
                                {{ ($syncSettings['blockade_sync_frequency'] ?? '') === 'custom' ? 'selected' : '' }}>
                                Expresión cron personalizada
                            </option>
                        </select>
                    </div>

                    <!-- Hora para todas las frecuencias excepto manual y custom -->
                    <div id="hourSection" class="mb-3"
                         style="{{ in_array($syncSettings['blockade_sync_frequency'] ?? 'manual', ['manual', 'custom']) ? 'display:none' : '' }}">
                        <label class="form-label fw-bold">Hora de ejecución</label>
                        <input type="time" class="form-control" name="blockade_sync_hour"
                               id="blockadeSyncHour"
                               value="{{ $syncSettings['blockade_sync_hour'] ?? '08:00' }}">
                        <small class="text-muted" id="hourHint">Hora a la que se ejecutará la sincronización.</small>
                    </div>

                    <!-- Expresión cron personalizada -->
                    <div id="cronSection" class="mb-3"
                         style="{{ ($syncSettings['blockade_sync_frequency'] ?? '') === 'custom' ? '' : 'display:none' }}">
                        <label class="form-label fw-bold">Expresión cron</label>
                        <input type="text" class="form-control font-monospace" name="blockade_sync_cron"
                               id="blockadeSyncCron"
                               placeholder="0 8 * * *"
                               value="{{ $syncSettings['blockade_sync_cron'] ?? '' }}">
                        <small class="text-muted">
                            Formato: <code>minuto hora día-mes mes día-semana</code>.
                            Ejemplo: <code>0 8 * * 1</code> = todos los lunes a las 8:00.
                        </small>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Tipo de sincronización -->
                <div class="mb-0">
                    <h6 class="mb-1 fw-bold text-dark">Tipo de sincronización</h6>
                    <p class="text-muted small mb-3">
                        Define si la sincronización programada debe truncar los datos existentes y reimportarlos
                        completamente, o solo añadir los registros nuevos (incremental).
                    </p>

                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="blockadeSyncFresh"
                               name="blockade_sync_fresh" value="1"
                               {{ ($syncSettings['blockade_sync_fresh'] ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="blockadeSyncFresh">
                            <strong>Sincronización completa</strong> — borrar y reimportar todos los bloqueos
                            <br>
                            <small class="text-muted">
                                Si está desactivado, solo se añaden registros nuevos (incremental, recomendado).
                            </small>
                        </label>
                    </div>
                </div>

            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary w-100 mb-1">
                    Guardar programación
                </button>
                <a href="{{ route('settings.documents.configurations') }}" class="btn btn-secondary w-100">
                    Volver
                </a>
            </div>
        </div>

    </form>

@endsection

@push('scripts')
<script>
(function () {
    var enabledToggle   = document.getElementById('blockadeSyncEnabled');
    var scheduleSection = document.getElementById('scheduleSection');
    var scheduleSectionHr = document.getElementById('scheduleSectionHr');
    var frequencySelect = document.getElementById('blockadeSyncFrequency');
    var hourSection    = document.getElementById('hourSection');
    var cronSection    = document.getElementById('cronSection');
    var hourHint       = document.getElementById('hourHint');

    function updateScheduleVisibility() {
        var show = enabledToggle.checked;
        scheduleSection.style.display = show ? '' : 'none';
        scheduleSectionHr.style.display = show ? '' : 'none';
        if (show && typeof $ !== 'undefined') {
            $('#blockadeSyncFrequency').select2({ width: '100%' });
        }
    }

    enabledToggle.addEventListener('change', updateScheduleVisibility);

    var hints = {
        hourly:         'Los minutos de esta hora se usarán como offset dentro de cada hora (p.ej. 08:30 → se ejecuta a los :30 de cada hora).',
        every_2_hours:  'Los minutos de esta hora se usarán como offset (p.ej. 08:30 → se ejecuta a las 00:30, 02:30, 04:30…).',
        every_6_hours:  'Los minutos de esta hora se usarán como offset (p.ej. 08:30 → se ejecuta a las 00:30, 06:30, 12:30, 18:30).',
        every_12_hours: 'Se ejecutará a la hora indicada y 12 horas después (p.ej. 08:00 → 08:00 y 20:00).',
        daily:          'Se ejecutará una vez al día a la hora indicada.',
        weekly:         'Se ejecutará cada domingo a la hora indicada.',
    };

    function updateVisibility() {
        var val = frequencySelect.value;
        var hideHour = (val === 'manual' || val === 'custom' || val === '');
        hourSection.style.display = hideHour ? 'none' : '';
        cronSection.style.display = (val === 'custom') ? '' : 'none';
        if (hourHint && hints[val]) {
            hourHint.textContent = hints[val];
        }
    }

    frequencySelect.addEventListener('change', updateVisibility);
    if (typeof $ !== 'undefined') {
        $(frequencySelect).on('change.select2', updateVisibility);
    }
    updateVisibility();
})();
</script>
@endpush
