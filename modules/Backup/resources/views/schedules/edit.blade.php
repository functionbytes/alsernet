@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' =>  $pageTitle ])


    <!-- Form -->
    <div class="row g-3">
        <div class="col-lg-8">
            <form action="{{ route('settings.backup.schedules.update', $schedule->id) }}" method="POST"
                  class="needs-validation" novalidate>
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="card-body">
                        <div class="mb-0">
                            <h5 class="mb-0">Información general</h5>
                            <p class="text-muted mb-3">Datos de conexión a la base de datos de PrestaShop</p>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="name" class="form-label fw-semibold">Nombre del Schedule <span
                                                class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" placeholder="ej: Backup Diario"
                                           value="{{ old('name', $schedule->name) }}" required>
                                    <small class="text-muted d-block mt-1">
                                        <i class="fa fa-circle-question"></i> Nombre descriptivo para identificar el
                                        schedule
                                    </small>
                                    @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-12">

                                    <div class="form-check form-switch">
                                        <input type="hidden" name="enabled" value="0">
                                        <input class="form-check-input" type="checkbox" id="enabled" name="enabled"
                                               value="1" {{ old('enabled', $schedule->enabled) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="enabled">
                                            <span class="fw-semibold">Activar schedule automáticamente</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <hr class="">

                            <div class="mb-0">
                                <h5 class="mb-0">Programación</h5>
                                <p class="text-muted mb-3">Datos de conexión a la base de datos de PrestaShop</p>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="frequency" class="form-label fw-semibold">Frecuencia <span
                                                    class="text-danger">*</span></label>
                                        <select class="form-select @error('frequency') is-invalid @enderror"
                                                id="frequency" name="frequency" required
                                                onchange="updateFrequencyOptions()">
                                            <option value="">Seleccione una frecuencia</option>
                                            @foreach ($frequencies as $key => $label)
                                                <option value="{{ $key }}" {{ old('frequency', $schedule->frequency) === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-block mt-1">
                                            <i class="fa fa-circle-question"></i> Elige cómo se ejecutará el backup
                                        </small>
                                        @error('frequency')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="scheduled_time" class="form-label fw-semibold">Hora del Backup <span
                                                    class="text-danger">*</span></label>
                                        <input type="time"
                                               class="form-control @error('scheduled_time') is-invalid @enderror"
                                               id="scheduled_time" name="scheduled_time"
                                               value="{{ old('scheduled_time', $schedule->scheduled_time->format('H:i')) }}"
                                               required>
                                        <small class="text-muted d-block mt-1">
                                            <i class="fa fa-circle-question"></i> La hora se ejecutará automáticamente
                                            cuando se alcance. Recomendado: 2:00 AM - 4:00 AM
                                        </small>
                                        @error('scheduled_time')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Weekly Options -->
                                    <div class="col-md-6 mb-3" id="weeklyOptions" style="display: none;">
                                        <label class="form-label fw-semibold">Días de la Semana <span
                                                    class="text-danger">*</span></label>
                                        <div class="row">
                                            @php
                                                $daysOfWeek = [
                                                    0 => 'Domingo',
                                                    1 => 'Lunes',
                                                    2 => 'Martes',
                                                    3 => 'Miércoles',
                                                    4 => 'Jueves',
                                                    5 => 'Viernes',
                                                    6 => 'Sábado',
                                                ];
                                                $selectedDays = old('days_of_week', $schedule->days_of_week ?? []);
                                            @endphp
                                            @foreach ($daysOfWeek as $day => $label)
                                                <div class="col-sm-6 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                               id="day_{{ $day }}" name="days_of_week[]"
                                                               value="{{ $day }}"
                                                                {{ in_array($day, $selectedDays) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="day_{{ $day }}">
                                                            {{ $label }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            <i class="fa fa-circle-question"></i> Selecciona uno o más días para
                                            ejecutar el backup
                                        </small>
                                    </div>

                                    <!-- Monthly Options -->
                                    <div class="col-md-6 mb-3" id="monthlyOptions" style="display: none;">
                                        <label class="form-label fw-semibold">Días del Mes <span
                                                    class="text-danger">*</span></label>
                                        <div class="row">
                                            @php
                                                $selectedMonthDays = old('days_of_month', $schedule->days_of_month ?? []);
                                            @endphp
                                            @for ($day = 1; $day <= 31; $day++)
                                                <div class="col-sm-4 col-md-3 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                               id="day_{{ $day }}" name="days_of_month[]"
                                                               value="{{ $day }}"
                                                                {{ in_array($day, $selectedMonthDays) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="day_{{ $day }}">
                                                            Día {{ $day }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endfor
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            <i class="fa fa-circle-question"></i> Selecciona uno o más días del mes
                                        </small>
                                    </div>

                                    <!-- Custom Interval -->
                                    <div class="col-md-6 mb-3" id="customOptions" style="display: none;">
                                        <label for="custom_interval_hours" class="form-label fw-semibold">Intervalo
                                            (horas) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control"
                                               id="custom_interval_hours" name="custom_interval_hours"
                                               min="1" max="8760"
                                               value="{{ old('custom_interval_hours', $schedule->custom_interval_hours ?? 24) }}"
                                               placeholder="Cada X horas">
                                        <small class="text-muted d-block mt-1">
                                            <i class="fa fa-circle-question"></i> Ejecutará el backup cada X horas desde
                                            el último backup (mín: 1, máx: 8760)
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <hr class="">

                            <div class="mb-0">
                                <h5 class="mb-0">Tipos de Backup</h5>
                                <p class="text-muted mb-3">Datos de conexión a la base de datos de PrestaShop</p>
                                <div class="d-flex justify-content-between align-items-center pb-3">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" id="selectAllBackupTypes"
                                                class="btn btn-sm btn-outline-secondary">
                                            <i class="fa fa-check me-1"></i> Todos
                                        </button>
                                        <button type="button" id="deselectAllBackupTypes"
                                                class="btn btn-sm btn-outline-secondary">
                                            <i class="fa fa-xmark me-1"></i> Ninguno
                                        </button>
                                    </div>
                                </div>

                                <p class="text-muted small mb-4">
                                    <i class="fa fa-circle-info"></i> Selecciona qué elementos incluir en cada backup
                                    programado
                                </p>

                                @php
                                    $backupIconMap = [
                                        'app_code' => 'fa-code',
                                        'config' => 'fa-gear',
                                        'routes' => 'fa-map',
                                        'resources' => 'fa-palette',
                                        'migrations' => 'fa-database',
                                        'storage' => 'fa-folder',
                                        'database' => 'fa-database',
                                    ];
                                    $selectedTypes = old('backup_types', $schedule->backup_types ?? []);
                                @endphp

                                <div class="backup-options">
                                    @foreach ($backupOptions as $key => $label)
                                        @php
                                            $icon = $backupIconMap[$key] ?? 'fa-file';
                                        @endphp
                                        <div class="d-flex align-items-center justify-content-between mb-3 p-3 border rounded-2">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="text-bg-light-secondary rounded-1 p-3 d-flex align-items-center justify-content-center"
                                                     style="width: 50px; height: 50px;">
                                                    <i class="fa {{ $icon }} text-dark fs-6"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1 fw-semibold">{{ $label }}</h6>
                                                    @switch($key)
                                                        @case('app_code')
                                                            <small class="text-muted">Código de aplicación (Controllers,
                                                                Models, etc.)</small>
                                                            @break
                                                        @case('config')
                                                            <small class="text-muted">Configuración de
                                                                aplicación</small>
                                                            @break
                                                        @case('routes')
                                                            <small class="text-muted">Definición de rutas</small>
                                                            @break
                                                        @case('resources')
                                                            <small class="text-muted">Vistas, CSS, JS y assets</small>
                                                            @break
                                                        @case('migrations')
                                                            <small class="text-muted">Historial de cambios de BD</small>
                                                            @break
                                                        @case('storage')
                                                            <small class="text-muted">Archivos cargados y
                                                                documentos</small>
                                                            @break
                                                        @case('database')
                                                            <small class="text-muted">Dump completo de MySQL</small>
                                                            @break
                                                    @endswitch
                                                </div>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input backup-type-checkbox" type="checkbox"
                                                       id="type_{{ $key }}" name="backup_types[]" value="{{ $key }}"
                                                       {{ in_array($key, $selectedTypes) ? 'checked' : '' }} role="switch">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @error('backup_types')
                                <div class="alert alert-danger mt-3 mb-0">
                                    <i class="fa fa-circle-exclamation me-2"></i> {{ $message }}
                                </div>
                                @enderror
                            </div>

                            <hr class="">

                            <div class="mb-0">
                                <h5 class="mb-0">Información de Ejecución</h5>
                                <p class="text-muted mb-3">Datos de conexión a la base de datos de PrestaShop</p>

                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <small class="text-muted">Último Backup:</small>
                                        <p class="mb-0">
                                            @if ($schedule->last_run_at)
                                                {{ $schedule->last_run_at->format('Y-m-d H:i:s') }}
                                            @else
                                                <span class="text-muted">Nunca ejecutado</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <small class="text-muted">Próximo Backup:</small>
                                        <p class="mb-0">
                                            @if ($schedule->next_run_at)
                                                {{ $schedule->next_run_at->format('Y-m-d H:i:s') }}
                                            @else
                                                <span class="text-muted">Pendiente de cálculo</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary mb-2 w-100">
                            Guardar
                        </button>
                        <a href="{{ route('settings.backup.schedules.index') }}"
                           class="btn btn-secondary w-100">
                            Volver
                        </a>
                    </div>
                </div>

            </form>
        </div>

        <!-- Help Panel -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header  border-bottom">
                    <h6 class="mb-0 fw-semibold">Ayuda</h6>
                </div>
                <div class="card-body">
                    <h6 class="mb-2">Tipos de Frecuencia:</h6>
                    <ul class="">
                        <li><strong>Diario:</strong> Se ejecuta cada día a la hora especificada</li>
                        <li><strong>Semanal:</strong> Se ejecuta en los días seleccionados</li>
                        <li><strong>Mensual:</strong> Se ejecuta en los días del mes especificados</li>
                        <li><strong>Personalizado:</strong> Se ejecuta cada X horas</li>
                    </ul>

                    <hr>

                    <h6 class="mb-2">Horario Recomendado:</h6>
                    <p class=" text-muted">
                        Los backups grandes son mejores entre las 2 AM y 4 AM para evitar afectar las operaciones
                        normales.
                    </p>

                    <hr>

                    <h6 class="mb-2">Requisitos:</h6>
                    <p class=" text-muted">
                        El comando <code>php artisan schedule:run</code> debe estar en tu crontab para que los backups
                        se ejecuten automáticamente.
                    </p>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        function updateFrequencyOptions() {
            const frequency = document.getElementById('frequency').value;

            // Hide all frequency-specific options
            const weeklyOpts = document.getElementById('weeklyOptions');
            const monthlyOpts = document.getElementById('monthlyOptions');
            const customOpts = document.getElementById('customOptions');

            weeklyOpts.style.display = 'none';
            monthlyOpts.style.display = 'none';
            customOpts.style.display = 'none';

            // Show relevant options based on selection
            switch (frequency) {
                case 'weekly':
                    weeklyOpts.style.display = 'block';
                    break;
                case 'monthly':
                    monthlyOpts.style.display = 'block';
                    break;
                case 'custom':
                    customOpts.style.display = 'block';
                    break;
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function () {
            updateFrequencyOptions();

            // Select/Deselect all backup types
            const selectAllBtn = document.getElementById('selectAllBackupTypes');
            const deselectAllBtn = document.getElementById('deselectAllBackupTypes');
            const backupTypeCheckboxes = document.querySelectorAll('.backup-type-checkbox');

            if (selectAllBtn) {
                selectAllBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    backupTypeCheckboxes.forEach(checkbox => {
                        checkbox.checked = true;
                    });
                });
            }

            if (deselectAllBtn) {
                deselectAllBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    backupTypeCheckboxes.forEach(checkbox => {
                        checkbox.checked = false;
                    });
                });
            }
        });
    </script>
@endpush
