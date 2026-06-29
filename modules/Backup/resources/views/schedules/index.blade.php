@extends('layouts.theme')

@section('title', 'Copias programadas')

@section('page_header')
    @include('core::components.card', ['title' => 'Copias programadas'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">

            <!-- Header -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Programación de copias de seguridad</h5>
                        <p class="small mb-0 text-muted">Gestiona la programación automática de backups de tu aplicación y base de datos</p>
                    </div>
                    <div class="ms-auto">
                        <div class="btn-group">
                            <button type="button" class="btn bg-primary-subtle text-primary dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Acciones
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{ route('settings.backup.schedules.create') }}">Nueva programación</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('settings.backups.index') }}">Ver backups</a>
                                <a class="dropdown-item" href="{{ route('settings.backups.setup') }}">Configuración del sistema</a>
                                <a class="dropdown-item" href="{{ route('settings.backups.guide') }}">Guía de configuración</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-2">Total programaciones</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                        <small class="text-muted">Programaciones configuradas</small>
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
                                        <h6 class="card-title mb-2">Activas</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                        <small class="text-muted">Ejecutándose automáticamente</small>
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
                                        <h6 class="card-title mb-2">Inactivas</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['inactive']) }}</h4>
                                        <small class="text-muted">Pausadas o deshabilitadas</small>
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
                                        <h6 class="card-title mb-2">Sin ejecutar</h6>
                                        <h4 class="mb-1 fw-bold">{{ number_format($stats['never_run']) }}</h4>
                                        <small class="text-muted">Nunca han generado un backup</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.backup.schedules.index') }}">
                    <div class="row align-items-center g-2">
                        <div class="col-12 col-md">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control"
                                       placeholder="Buscar por nombre..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-6 col-md-auto">
                            <select class="select2 form-select" name="status">
                                <option value="">Todos los estados</option>
                                <option value="active" @selected(request('status') == 'active')>Activo</option>
                                <option value="inactive" @selected(request('status') == 'inactive')>Inactivo</option>
                            </select>
                        </div>
                        <div class="col-auto d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-3">
                                <i class="fas fa-search"></i>
                            </button>
                            @if(request()->hasAny(['search', 'status']))
                                <a href="{{ route('settings.backup.schedules.index') }}"
                                   class="btn btn-outline-secondary" title="Limpiar">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="card-body">
                @if($schedules->isEmpty())
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-calendar-xmark fa-4x text-muted opacity-50"></i>
                        </div>
                        <h5 class="text-muted mb-2">No hay programaciones de backup configuradas</h5>
                        <p class="text-muted mb-3">Crea una programación para que los backups se generen automáticamente.</p>
                        <a href="{{ route('settings.backup.schedules.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus me-1"></i> Crear primera programación
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th>Nombre</th>
                                    <th>Frecuencia</th>
                                    <th>Hora</th>
                                    <th>Último backup</th>
                                    <th>Próximo backup</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($schedules as $schedule)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $schedule->id }}"></td>
                                        <td class="fw-semibold">{{ $schedule->name }}</td>
                                        <td>
                                            @switch($schedule->frequency)
                                                @case('daily') Diario @break
                                                @case('weekly') Semanal @break
                                                @case('monthly') Mensual @break
                                                @case('custom') Personalizado @break
                                            @endswitch
                                        </td>
                                        <td>{{ $schedule->scheduled_time->format('H:i') }}</td>
                                        <td>{{ $schedule->last_run_at ? $schedule->last_run_at->format('Y-m-d H:i') : 'Nunca' }}</td>
                                        <td>{{ $schedule->next_run_at ? $schedule->next_run_at->format('Y-m-d H:i') : '-' }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $schedule->enabled ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary' }}">
                                                {{ $schedule->enabled ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.backup.schedules.edit', $schedule) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item toggle-schedule" href="javascript:void(0)"
                                                           data-url="{{ route('settings.backup.schedules.toggle', $schedule) }}">
                                                            {{ $schedule->enabled ? 'Desactivar' : 'Activar' }}
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item" href="javascript:void(0)"
                                                           data-bs-toggle="modal" data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.backup.schedules.destroy', $schedule) }}"
                                                           data-title="Eliminar: {{ $schedule->name }}">
                                                            Eliminar
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- Bulk toolbar --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none" style="z-index:1050;">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar acción
        </button>
    </div>

    {{-- Bulk modal --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Acción masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicará la acción sobre <strong><span data-bulk-count>0</span> programación(es)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Acción</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Seleccionar acción...</option>
                            <option value="activate">Activar</option>
                            <option value="deactivate">Desactivar</option>
                            <option value="delete">Eliminar</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            const bulk = window.BulkActions.init({ checkbox: '.bulk-checkbox' });

            $('#bulk-action-select').select2({ dropdownParent: $('#bulk-modal'), width: '100%' });

            $('#bulk-modal').on('hide.bs.modal', function () {
                $('#bulk-action-select').val('').trigger('change');
                $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
                bulk.reset();
            });

            $('#bulk-apply-btn').on('click', function () {
                const action = $('#bulk-action-select').val();
                const ids    = bulk.getIds();

                if (!action) { toastr.warning('Selecciona una acción.'); return; }
                if (!ids.length) { toastr.warning('Selecciona al menos una programación.'); return; }
                if (action === 'delete' && !confirm('¿Eliminar las ' + ids.length + ' programación(es) seleccionadas?')) { return; }

                $('#bulk-apply-btn').prop('disabled', true).text('Procesando...');

                $.ajax({
                    url: '{{ route('settings.backup.schedules.bulk-action') }}',
                    method: 'POST',
                    data: JSON.stringify({ action, ids, _token: $('meta[name="csrf-token"]').attr('content') }),
                    contentType: 'application/json',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                        $('#bulk-modal').modal('hide');
                        toastr.success(res.message);
                        setTimeout(() => location.reload(), 800);
                    },
                    error: function (xhr) {
                        toastr.error(xhr.responseJSON?.message ?? 'Error al procesar.');
                        $('#bulk-apply-btn').prop('disabled', false).text('Aplicar');
                    },
                });
            });

            $(document).on('click', '[data-bs-target="#delete-modal"]', function () {
                $('#delete-modal .modal-title').text($(this).data('title'));
                $('#delete-form').attr('action', $(this).data('url'));
            });

            $(document).on('click', '.toggle-schedule', function (e) {
                e.preventDefault();
                const url = $(this).data('url');

                $.post(url, { _token: $('meta[name="csrf-token"]').attr('content') })
                    .done(function (data) {
                        toastr.success(data.message);
                        location.reload();
                    })
                    .fail(function () {
                        toastr.error('Error al cambiar el estado de la programación');
                    });
            });
        });
    </script>
@endpush
