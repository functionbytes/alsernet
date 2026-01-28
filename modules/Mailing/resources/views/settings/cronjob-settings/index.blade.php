@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Configuración de trabajos programados'])

    @if ($message = session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($message = session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-exclamation me-2"></i> {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Overview Card -->
    <div class="card mb-3">
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold">Información general</h6>
                <span class="badge bg-info">{{ count($cronjobs) }} configurados</span>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                            <i class="fa fa-clock text-primary fs-5"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block">Activos</small>
                            <strong class="fs-5">{{ count($cronjobs->where('is_active', true)) }}</strong>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 p-3 rounded me-3">
                            <i class="fa fa-pause text-warning fs-5"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block">Pausados</small>
                            <strong class="fs-5">{{ count($cronjobs->where('is_active', false)) }}</strong>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 p-3 rounded me-3">
                            <i class="fa fa-check text-success fs-5"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block">Exitosas</small>
                            <strong class="fs-5">{{ count($cronjobs->where('last_status', 'success')) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create New Cronjob Card -->
    <div class="card mb-3">
        <div class="card-header p-4 border-bottom border-light">
            <div class="d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold">Crear nuevo trabajo programado</h6>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#formCreateCronjob">
                    <i class="fa fa-chevron-down me-2"></i>Mostrar formulario
                </button>
            </div>
        </div>

        <div id="formCreateCronjob" class="collapse">
            <div class="card-body">
                <form method="POST" action="{{ route('settings.mailing.settings.cronjobs.store') }}" id="formCronjob">
                    @csrf

                    <div class="row">
                        {{-- Name --}}
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">
                                    Nombre <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name') }}"
                                       placeholder="Ej: Sincronizar suscriptores"
                                       required>
                                @error('name')
                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Command --}}
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="command" class="form-label">
                                    Comando Artisan <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('command') is-invalid @enderror"
                                        id="command"
                                        name="command"
                                        required>
                                    <option value="">Selecciona un comando</option>
                                    <option value="mailing:sync-subscribers" {{ old('command') === 'mailing:sync-subscribers' ? 'selected' : '' }}>Sincronizar suscriptores</option>
                                    <option value="mailing:send-campaigns" {{ old('command') === 'mailing:send-campaigns' ? 'selected' : '' }}>Enviar campañas</option>
                                    <option value="mailing:process-bounces" {{ old('command') === 'mailing:process-bounces' ? 'selected' : '' }}>Procesar rebotes</option>
                                    <option value="mailing:update-stats" {{ old('command') === 'mailing:update-stats' ? 'selected' : '' }}>Actualizar estadísticas</option>
                                    <option value="mailing:cleanup-logs" {{ old('command') === 'mailing:cleanup-logs' ? 'selected' : '' }}>Limpiar registros</option>
                                    <option value="custom" {{ old('command') === 'custom' ? 'selected' : '' }}>Personalizado</option>
                                </select>
                                @error('command')
                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Custom Command (hidden by default) --}}
                        <div class="col-12 col-md-6" id="customCommandDiv" style="display: none;">
                            <div class="mb-3">
                                <label for="custom_command" class="form-label">
                                    Comando personalizado <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('custom_command') is-invalid @enderror"
                                       id="custom_command"
                                       name="custom_command"
                                       value="{{ old('custom_command') }}"
                                       placeholder="Ej: mailing:custom-task">
                                @error('custom_command')
                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Frequency --}}
                        <div class="col-12 col-md-6">
                            <div class="mb-3">
                                <label for="frequency" class="form-label">
                                    Frecuencia <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('frequency') is-invalid @enderror"
                                        id="frequency"
                                        name="frequency"
                                        required>
                                    <option value="">Selecciona frecuencia</option>
                                    <option value="* * * * *" {{ old('frequency') === '* * * * *' ? 'selected' : '' }}>Cada minuto</option>
                                    <option value="*/5 * * * *" {{ old('frequency') === '*/5 * * * *' ? 'selected' : '' }}>Cada 5 minutos</option>
                                    <option value="*/15 * * * *" {{ old('frequency') === '*/15 * * * *' ? 'selected' : '' }}>Cada 15 minutos</option>
                                    <option value="*/30 * * * *" {{ old('frequency') === '*/30 * * * *' ? 'selected' : '' }}>Cada 30 minutos</option>
                                    <option value="0 * * * *" {{ old('frequency') === '0 * * * *' ? 'selected' : '' }}>Cada hora</option>
                                    <option value="0 0 * * *" {{ old('frequency') === '0 0 * * *' ? 'selected' : '' }}>Diariamente (medianoche)</option>
                                    <option value="0 2 * * *" {{ old('frequency') === '0 2 * * *' ? 'selected' : '' }}>Diariamente (2 AM)</option>
                                    <option value="0 0 * * 0" {{ old('frequency') === '0 0 * * 0' ? 'selected' : '' }}>Semanalmente (domingo)</option>
                                    <option value="0 0 1 * *" {{ old('frequency') === '0 0 1 * *' ? 'selected' : '' }}>Mensualmente</option>
                                    <option value="custom" {{ old('frequency') === 'custom' ? 'selected' : '' }}>Personalizado (Cron)</option>
                                </select>
                                @error('frequency')
                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Custom Frequency (hidden by default) --}}
                        <div class="col-12 col-md-6" id="customFrequencyDiv" style="display: none;">
                            <div class="mb-3">
                                <label for="custom_frequency" class="form-label">
                                    Expresión Cron personalizada <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('custom_frequency') is-invalid @enderror"
                                       id="custom_frequency"
                                       name="custom_frequency"
                                       value="{{ old('custom_frequency') }}"
                                       placeholder="0 0 * * * (minuto hora día mes día_semana)">
                                @error('custom_frequency')
                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                                <small class="text-muted">Formato: minuto hora día mes día_semana</small>
                            </div>
                        </div>

                        {{-- Description --}}
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="description" class="form-label">
                                    Descripción
                                </label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                          id="description"
                                          name="description"
                                          rows="2"
                                          placeholder="Descripción de qué hace este trabajo">{{ old('description') }}</textarea>
                                @error('description')
                                    <span class="field-validation-error"><i class="fa fa-circle-exclamation"></i> {{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Active --}}
                        <div class="col-12 col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="is_active"
                                       name="is_active"
                                       value="1"
                                       {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Activar inmediatamente
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="fa fa-redo me-2"></i>Limpiar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save me-2"></i>Crear trabajo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cronjobs List -->
    <div class="card">
        <div class="card-header p-4 border-bottom border-light">
            <h6 class="mb-0 fw-bold">Trabajos programados</h6>
        </div>

        <div class="table-responsive">
            @if($cronjobs->isEmpty())
                <div class="text-center py-5">
                    <i class="fa fa-inbox fs-1 text-muted mb-3 d-block"></i>
                    <p class="text-muted">No hay trabajos programados creados.</p>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#formCreateCronjob">
                        <i class="fa fa-plus me-2"></i>Crear el primero
                    </button>
                </div>
            @else
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Comando</th>
                            <th>Frecuencia</th>
                            <th>Última ejecución</th>
                            <th>Estado</th>
                            <th>Activo</th>
                            <th width="150">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cronjobs as $cronjob)
                            <tr>
                                <td>
                                    <strong>{{ $cronjob->name }}</strong>
                                    @if($cronjob->description)
                                        <br><small class="text-muted">{{ $cronjob->description }}</small>
                                    @endif
                                </td>
                                <td>
                                    <code class="bg-light p-2 rounded d-inline-block">
                                        {{ $cronjob->custom_command ?? $cronjob->command }}
                                    </code>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <i class="fa fa-clock me-1"></i>{{ $cronjob->frequency }}
                                    </small>
                                </td>
                                <td>
                                    @if($cronjob->last_run_at)
                                        <small class="text-muted">
                                            {{ $cronjob->last_run_at->diffForHumans() }}
                                        </small>
                                    @else
                                        <span class="badge bg-secondary">Nunca</span>
                                    @endif
                                </td>
                                <td>
                                    @if($cronjob->last_status === 'success')
                                        <span class="badge bg-success">
                                            <i class="fa fa-check me-1"></i>Exitoso
                                        </span>
                                    @elseif($cronjob->last_status === 'failed')
                                        <span class="badge bg-danger">
                                            <i class="fa fa-times me-1"></i>Error
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            <i class="fa fa-minus me-1"></i>Pendiente
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($cronjob->is_active)
                                        <span class="badge bg-success">Activo</span>
                                    @else
                                        <span class="badge bg-warning">Pausado</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('settings.mailing.settings.cronjobs.edit', $cronjob->id) }}"
                                           class="btn btn-outline-primary"
                                           title="Editar">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-outline-info"
                                                title="Ejecutar ahora"
                                                onclick="runCronjob({{ $cronjob->id }})">
                                            <i class="fa fa-play"></i>
                                        </button>
                                        <form method="POST"
                                              action="{{ route('settings.mailing.settings.cronjobs.destroy', $cronjob->id) }}"
                                              style="display: inline;"
                                              onsubmit="return confirm('¿Estás seguro de que deseas eliminar este trabajo programado?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            {{-- Execution History (collapsible) --}}
                            <tr class="table-light">
                                <td colspan="7">
                                    <div class="accordion accordion-flush" id="history-{{ $cronjob->id }}">
                                        <div class="accordion-item border-0">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $cronjob->id }}">
                                                    <small class="text-muted">Historial de ejecuciones</small>
                                                </button>
                                            </h2>
                                            <div id="collapse-{{ $cronjob->id }}" class="accordion-collapse collapse" data-bs-parent="#history-{{ $cronjob->id }}">
                                                <div class="accordion-body p-3">
                                                    @if($cronjob->executions && $cronjob->executions->count())
                                                        <div class="table-responsive">
                                                            <table class="table table-sm table-borderless mb-0">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Fecha</th>
                                                                        <th>Duración</th>
                                                                        <th>Estado</th>
                                                                        <th>Mensaje</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($cronjob->executions->take(5) as $execution)
                                                                        <tr>
                                                                            <td><small>{{ $execution->executed_at->format('Y-m-d H:i:s') }}</small></td>
                                                                            <td><small>{{ $execution->duration_ms }}ms</small></td>
                                                                            <td>
                                                                                @if($execution->status === 'success')
                                                                                    <span class="badge bg-success bg-opacity-10 text-success">Exitoso</span>
                                                                                @else
                                                                                    <span class="badge bg-danger bg-opacity-10 text-danger">Error</span>
                                                                                @endif
                                                                            </td>
                                                                            <td><small class="text-muted">{{ Str::limit($execution->output, 50) }}</small></td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    @else
                                                        <p class="text-muted mb-0">Sin registros de ejecución</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show/hide custom command input
    const commandSelect = document.getElementById('command');
    const customCommandDiv = document.getElementById('customCommandDiv');

    if (commandSelect) {
        commandSelect.addEventListener('change', function() {
            customCommandDiv.style.display = this.value === 'custom' ? 'block' : 'none';
        });

        // Trigger on page load if custom is selected
        if (commandSelect.value === 'custom') {
            customCommandDiv.style.display = 'block';
        }
    }

    // Show/hide custom frequency input
    const frequencySelect = document.getElementById('frequency');
    const customFrequencyDiv = document.getElementById('customFrequencyDiv');

    if (frequencySelect) {
        frequencySelect.addEventListener('change', function() {
            customFrequencyDiv.style.display = this.value === 'custom' ? 'block' : 'none';
        });

        // Trigger on page load if custom is selected
        if (frequencySelect.value === 'custom') {
            customFrequencyDiv.style.display = 'block';
        }
    }
});

function runCronjob(cronjobId) {
    if (!confirm('¿Ejecutar este trabajo ahora?')) {
        return;
    }

    const btn = event.target.closest('button');
    btn.disabled = true;
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

    fetch(`{{ route('settings.mailing.settings.cronjobs.run', '') }}/${cronjobId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('¡Trabajo ejecutado correctamente!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Algo salió mal'));
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    });
}
</script>
@endpush
