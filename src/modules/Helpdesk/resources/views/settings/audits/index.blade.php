@extends('layouts.theme')

@section('title', 'Auditoría Helpdesk')

@push('styles')
<style>
.hd-filter-md { min-width: 160px; }
.hd-filter-lg { min-width: 180px; }
.hd-filter-date { min-width: 150px; }
.hd-pre-modal { max-height: 400px; overflow-y: auto; white-space: pre-wrap; word-break: break-all; font-size: .8rem; }
</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Auditoría Helpdesk'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        @if(isset($unavailable) && $unavailable)

            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-triangle-exclamation fs-5"></i>
                        </div>
                        <h6 class="mb-1">Registro de auditoría no disponible</h6>
                        <p class="text-muted mb-0">El paquete de registro de actividad no está instalado en este sistema.</p>
                    </div>
                </div>
            </div>

        @else

            <div class="card">

                {{-- Header --}}
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Registro de auditoría</h5>
                            <p class="small mb-0 text-muted">Historial de acciones realizadas en el módulo Helpdesk</p>
                        </div>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="card-body border-bottom">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="card bg-light-secondary h-100">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">Total registros</h6>
                                    <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                    <small class="text-muted">En el módulo Helpdesk</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-secondary h-100">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">Helpdesk general</h6>
                                    <h4 class="mb-1 fw-bold">{{ number_format($stats['helpdesk']) }}</h4>
                                    <small class="text-muted">Configuración y ajustes</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-secondary h-100">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">Conversaciones</h6>
                                    <h4 class="mb-1 fw-bold">{{ number_format($stats['conversations']) }}</h4>
                                    <small class="text-muted">Acciones en conversaciones</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light-secondary h-100">
                                <div class="card-body">
                                    <h6 class="card-title mb-2">Tickets</h6>
                                    <h4 class="mb-1 fw-bold">{{ number_format($stats['tickets']) }}</h4>
                                    <small class="text-muted">Acciones en tickets</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Filtros --}}
                <div class="card-body border-bottom">
                    <form method="GET" action="{{ route('settings.helpdesk.audits.index') }}" id="audits-filter-form">
                        <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                            <div class="flex-fill">
                                <div class="input-group h-100">
                                    <span class="input-group-text bg-white border-end-1">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="search" name="search" class="form-control border-start-0 ps-0"
                                           placeholder="Buscar en descripción..."
                                           value="{{ request('search') }}">
                                </div>
                            </div>
                            <div class="flex-shrink-0 hd-filter-md">
                                <select name="user_id" class="form-select h-100">
                                    <option value="">Todos los agentes</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>
                                            {{ $user->firstname }} {{ $user->lastname }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-shrink-0 hd-filter-lg">
                                <select name="log_name" class="form-select h-100">
                                    <option value="">Todos los módulos</option>
                                    @foreach($logNames as $logName)
                                        <option value="{{ $logName }}" @selected(request('log_name') === $logName)>
                                            {{ $logName }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-shrink-0 hd-filter-date">
                                <input type="date" name="date_from" class="form-control h-100" value="{{ request('date_from') }}">
                            </div>
                            <div class="flex-shrink-0 hd-filter-date">
                                <input type="date" name="date_to" class="form-control h-100" value="{{ request('date_to') }}">
                            </div>
                            <div class="d-flex gap-2 flex-shrink-0">
                                <button type="submit" class="btn btn-primary" aria-label="Buscar">
                                    <i class="fas fa-search"></i>
                                </button>
                                @if(request()->hasAny(['search', 'user_id', 'log_name', 'date_from', 'date_to']))
                                    <a href="{{ route('settings.helpdesk.audits.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>

                {{-- Tabla --}}
                <div class="card-body">
                    @if($activities && $activities->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Fecha</th>
                                        <th scope="col">Agente</th>
                                        <th scope="col">Descripción</th>
                                        <th scope="col">Módulo</th>
                                        <th scope="col" class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activities as $activity)
                                        <tr>
                                            <td>
                                                <div class="small">{{ $activity->created_at->format('d/m/Y H:i') }}</div>
                                                <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                            </td>
                                            <td>
                                                @if($activity->causer)
                                                    <div class="small fw-semibold">{{ $activity->causer->firstname }} {{ $activity->causer->lastname }}</div>
                                                    <small class="text-muted">{{ $activity->causer->email }}</small>
                                                @else
                                                    <span class="text-muted small">Sistema</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="small">{{ $activity->description }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $logBadgeClass = match($activity->log_name) {
                                                        'helpdesk'               => 'bg-primary-subtle text-primary',
                                                        'helpdesk-conversations' => 'bg-info-subtle text-info',
                                                        'helpdesk-tickets'       => 'bg-warning-subtle text-warning',
                                                        'helpdesk-webhooks'      => 'bg-secondary-subtle text-secondary',
                                                        default                  => 'bg-light text-muted',
                                                    };
                                                @endphp
                                                <span class="badge {{ $logBadgeClass }}">{{ $activity->log_name }}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="dropdown">
                                                    <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-auto-close="true" data-bs-boundary="viewport">
                                                        <i class="fas fa-ellipsis-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @if($activity->properties && $activity->properties->isNotEmpty())
                                                            <li>
                                                                <button type="button" class="dropdown-item btn-view-properties"
                                                                    data-properties="{{ json_encode($activity->properties->toArray()) }}"
                                                                    data-description="{{ $activity->description }}">
                                                                    Ver propiedades
                                                                </button>
                                                            </li>
                                                        @else
                                                            <li><span class="dropdown-item text-muted">Sin propiedades</span></li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="d-flex flex-column align-items-center">
                                <h6 class="mb-1">
                                    @if(request()->hasAny(['search', 'user_id', 'log_name', 'date_from', 'date_to']))
                                        No se encontraron resultados
                                    @else
                                        No hay registros de auditoría
                                    @endif
                                </h6>
                                <p class="text-muted mb-3">
                                    @if(request('search'))
                                        No hay resultados para "{{ request('search') }}"
                                    @elseif(request()->hasAny(['user_id', 'log_name', 'date_from', 'date_to']))
                                        No se encontraron registros con los filtros aplicados.
                                    @else
                                        Aún no hay actividad registrada en el módulo Helpdesk.
                                    @endif
                                </p>
                                @if(request()->hasAny(['search', 'user_id', 'log_name', 'date_from', 'date_to']))
                                    <a href="{{ route('settings.helpdesk.audits.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar filtros</a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                @if($activities && $activities->hasPages())
                    <div class="card-footer">{{ $activities->appends(request()->query())->links() }}</div>
                @endif

            </div>

        @endif

    </div>

    {{-- Modal: propiedades del evento --}}
    <div class="modal fade" id="propertiesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Propiedades del evento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2" id="propertiesDescription"></p>
                    <pre class="bg-light rounded p-3 mb-0 hd-pre-modal" id="propertiesContent"></pre>
                </div>
                <div class="modal-footer d-block">
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    $(document).on('click', '.btn-view-properties', function () {
        const raw         = $(this).data('properties');
        const description = $(this).data('description');

        $('#propertiesDescription').text(description);
        $('#propertiesContent').text(JSON.stringify(raw, null, 2));
        $('#propertiesModal').modal('show');
    });
});
</script>
@endpush
