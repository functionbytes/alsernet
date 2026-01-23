@extends('layouts.theme')

@section('content')

    @include('core::components.card', ['title' => 'Listado de programación de copias'])

    <div class="widget-content searchable-container list">
        @if ($message = Session::get('success'))
            <div class="alert bg-light-secondary text-black alert-dismissible fade show" role="alert">
                <i class="fa fa-circle-check"></i> {{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if ($message = Session::get('error'))
            <div class="alert bg-danger text-black  alert-dismissible fade show" role="alert">
                <i class="fa fa-circle-xmark"></i> {{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card card-body">
            <div class="row">
                <div class="col-md-12 col-xl-12">
                    <form class="position-relative form-search" action="{{ request()->fullUrl() }}" method="GET">
                        <div class="row justify-content-between g-2 ">
                            <div class="col-auto flex-grow-1">
                                <div class="tt-search-box">
                                    <div class="input-group">
                                        <span class="position-absolute top-50 start-0 translate-middle-y ms-2"> <i
                                                    class="fa fa-magnifying-glass"></i></span>
                                        <input class="form-control rounded-start w-100" type="text" id="search"
                                               name="search" placeholder="Buscar por nombre"
                                               @isset($searchKey) value="{{ $searchKey }}" @endisset>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="input-group">
                                    <select class="form-select select2" name="status"
                                            data-minimum-results-for-search="Infinity">
                                        <option value="">Seleccionar estado</option>
                                        <option value="active" @if(request('status') == 'active') selected @endif>
                                            Activo
                                        </option>
                                        <option value="inactive" @if(request('status') == 'inactive') selected @endif>
                                            Inactivo
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip"
                                        data-bs-placement="top" data-bs-original-title="Buscar">
                                    <i class="fa fa-magnifying-glass"></i>
                                </button>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('settings.backup.schedules.create') }}"
                                   class="btn btn-primary">
                                    <i class="fa fa-plus"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if ($schedules->isEmpty())
            <div class="card card-body text-center py-5">
                <i class="fa fa-calendar-xmark fa-3x text-muted mb-3"></i>
                <p class="text-muted">No hay schedules de backup configurados.</p>
                <a href="{{ route('settings.backup.schedules.create') }}" class="btn btn-primary btn-sm">
                    Crear el Primero
                </a>
            </div>
        @else
            <div class="card card-body">
                <div class="table-responsive">
                    <table class="table search-table align-middle text-nowrap">
                        <thead class="header-item">
                        <tr>
                            <th>Nombre</th>
                            <th>Frecuencia</th>
                            <th>Hora</th>
                            <th>Últi. Backup</th>
                            <th>Próx. Backup</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($schedules as $schedule)
                            <tr class="search-items">
                                <td>
                                    <span class="usr-email-addr"
                                          data-email="{{ $schedule->name }}">{{ $schedule->name }}</span>
                                </td>
                                <td>
                                    @switch($schedule->frequency)
                                        @case('daily')
                                            <span class="badge bg-light-info rounded-3 py-2 text-info fw-semibold fs-2 d-inline-flex align-items-center gap-1">Diario</span>
                                            @break
                                        @case('weekly')
                                            <span class="badge bg-light-secondary  rounded-3 py-2 text-primary fw-semibold fs-2 d-inline-flex align-items-center gap-1">Semanal</span>
                                            @break
                                        @case('monthly')
                                            <span class="badge bg-light-warning rounded-3 py-2 text-warning fw-semibold fs-2 d-inline-flex align-items-center gap-1">Mensual</span>
                                            @break
                                        @case('custom')
                                            <span class="badge bg-light-secondary  rounded-3 py-2 text-secondary fw-semibold fs-2 d-inline-flex align-items-center gap-1">Personalizado</span>
                                            @break
                                    @endswitch
                                </td>
                                <td>
                                    <span class="usr-ph-no"
                                          data-phone="{{ $schedule->scheduled_time->format('H:i') }}">{{ $schedule->scheduled_time->format('H:i') }}</span>
                                </td>
                                <td>
                                    @if ($schedule->last_run_at)
                                        {{ $schedule->last_run_at->format('Y-m-d H:i') }}
                                    @else
                                        Nunca
                                    @endif
                                </td>
                                <td>
                                    @if ($schedule->next_run_at)
                                        {{ $schedule->next_run_at->format('Y-m-d H:i') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                      <span class="badge {{ $schedule->enabled ? 'bg-light-success' : 'bg-light-danger' }} rounded-3 py-2 {{ $schedule->enabled ? 'text-success' : 'text-danger' }} fw-semibold fs-2 d-inline-flex align-items-center gap-1">
                           {{ $schedule->enabled ? 'Activo' : 'Inactivo' }}
                      </span>
                                </td>
                                <td class="text-left">
                                    <div class="dropdown dropstart">
                                        <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown"
                                           aria-expanded="false">
                                            <i class="fa fa-ellipsis"></i>
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-3"
                                                   href="{{ route('settings.backup.schedules.edit', $schedule->id) }}">Editar</a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-3 toggle-schedule"
                                                   href="#"
                                                   data-id="{{ $schedule->id }}">{{ $schedule->enabled ? 'Desactivar' : 'Activar' }}</a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-3 delete-schedule"
                                                   href="#" data-id="{{ $schedule->id }}">Eliminar</a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Delete schedule
            document.querySelectorAll('.delete-schedule').forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const scheduleId = this.dataset.id;
                    const deleteLink = document.getElementById('delete-link');
                    const modal = new bootstrap.Modal(document.getElementById('delete-modal'));

                    // Set the delete link to perform the DELETE request
                    deleteLink.href = '#';
                    deleteLink.onclick = function (e) {
                        e.preventDefault();
                        fetch(`/settings/backup-schedules/${scheduleId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    location.reload();
                                } else {
                                    alert('Error al eliminar: ' + (data.message || 'Desconocido'));
                                    modal.hide();
                                }
                            })
                            .catch(error => {
                                alert('Error al eliminar: ' + error);
                                modal.hide();
                            });
                    };

                    modal.show();
                });
            });

            // Toggle schedule
            document.querySelectorAll('.toggle-schedule').forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const scheduleId = this.dataset.id;

                    fetch(`/settings/backup-schedules/${scheduleId}/toggle`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            }
                        })
                        .catch(error => alert('Error: ' + error));
                });
            });
        });
    </script>
@endpush
