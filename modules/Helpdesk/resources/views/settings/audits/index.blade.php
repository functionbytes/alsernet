@extends('layouts.theme')

@section('title', 'Registro de auditoria')

@push('styles')
<style>
.audit-properties-pre {
    max-height: 400px;
    overflow-y: auto;
    white-space: pre-wrap;
    word-break: break-all;
    font-size: 0.8rem;
}
</style>
@endpush

@section('content')

    @include('core::components.alerts')

    @if(isset($unavailable) && $unavailable)
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                            <i class="fas fa-triangle-exclamation fs-5"></i>
                        </div>
                        <h6 class="mb-1">Registro de auditoria no disponible</h6>
                        <p class="text-muted mb-0">El paquete de registro de actividad no esta instalado en este sistema.</p>
                    </div>
                </div>
            </div>
        @else
            <div class="card">

                {{-- Header --}}
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">Registro de auditoria</h5>
                            <p class="small mb-0 text-muted">Historial de acciones realizadas en el modulo Helpdesk</p>
                        </div>
                        @if($activities)
                            <span class="badge bg-secondary-subtle text-secondary fs-6">
                                {{ number_format($activities->total()) }} registros
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Filters --}}
                <div class="card-body border-bottom">
                    <form method="GET" action="{{ route('settings.helpdesk.audits.index') }}">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label form-label-sm text-muted mb-1">Buscar descripcion</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="search" name="search" class="form-control form-control-sm"
                                        placeholder="Descripcion de la accion..."
                                        value="{{ request('search') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label form-label-sm text-muted mb-1">Agente</label>
                                <select name="user_id" class="form-select form-select-sm">
                                    <option value="">Todos los agentes</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>
                                            {{ $user->firstname }} {{ $user->lastname }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label form-label-sm text-muted mb-1">Modulo</label>
                                <select name="log_name" class="form-select form-select-sm">
                                    <option value="">Todos los modulos</option>
                                    @foreach($logNames as $logName)
                                        <option value="{{ $logName }}" @selected(request('log_name') === $logName)>
                                            {{ $logName }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label form-label-sm text-muted mb-1">Desde</label>
                                <input type="date" name="date_from" class="form-control form-control-sm"
                                    value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label form-label-sm text-muted mb-1">Hasta</label>
                                <input type="date" name="date_to" class="form-control form-control-sm"
                                    value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-1 d-flex gap-1">
                                <button type="submit" class="btn btn-primary btn-sm w-100">Filtrar</button>
                            </div>
                        </div>
                        @if(request()->hasAny(['search', 'user_id', 'log_name', 'date_from', 'date_to']))
                            <div class="mt-2">
                                <a href="{{ route('settings.helpdesk.audits.index') }}" class="btn btn-light btn-sm">
                                    <i class="fas fa-times me-1"></i>Limpiar filtros
                                </a>
                            </div>
                        @endif
                    </form>
                </div>

                {{-- Table --}}
                <div class="card-body">
                    @if($activities && $activities->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 130px">Fecha/Hora</th>
                                        <th>Agente</th>
                                        <th>Descripcion</th>
                                        <th>Modulo</th>
                                        <th class="text-center" style="width: 80px">Propiedades</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activities as $activity)
                                        <tr>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $activity->created_at->format('d/m/Y H:i') }}
                                                </small>
                                            </td>
                                            <td>
                                                @if($activity->causer)
                                                    <div class="fw-semibold">
                                                        {{ $activity->causer->firstname }} {{ $activity->causer->lastname }}
                                                    </div>
                                                    <small class="text-muted">{{ $activity->causer->email }}</small>
                                                @else
                                                    <span class="text-muted">Sistema</span>
                                                @endif
                                            </td>
                                            <td>{{ $activity->description }}</td>
                                            <td>
                                                @php
                                                    $logBadgeClass = match($activity->log_name) {
                                                        'helpdesk' => 'bg-primary-subtle text-primary',
                                                        'helpdesk-conversations' => 'bg-info-subtle text-info',
                                                        'helpdesk-tickets' => 'bg-warning-subtle text-warning',
                                                        'helpdesk-webhooks' => 'bg-secondary-subtle text-secondary',
                                                        default => 'bg-light text-muted',
                                                    };
                                                @endphp
                                                <span class="badge {{ $logBadgeClass }}">
                                                    {{ $activity->log_name }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                @if($activity->properties && $activity->properties->isNotEmpty())
                                                    <button type="button" class="btn btn-light btn-sm btn-view-properties"
                                                        data-properties="{{ json_encode($activity->properties->toArray()) }}"
                                                        data-description="{{ $activity->description }}">
                                                        Ver
                                                    </button>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="d-flex flex-column align-items-center">
                                <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-clipboard-list fs-5"></i>
                                </div>
                                <h6 class="mb-1">Sin registros de auditoria</h6>
                                <p class="text-muted mb-0">
                                    @if(request()->hasAny(['search', 'user_id', 'log_name', 'date_from', 'date_to']))
                                        No se encontraron registros con los filtros aplicados.
                                    @else
                                        No hay actividad registrada en el modulo Helpdesk todavia.
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif
                </div>

                @if($activities && $activities->hasPages())
                    <div class="card-footer bg-white border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted">
                                Mostrando <strong>{{ $activities->firstItem() }}</strong> a <strong>{{ $activities->lastItem() }}</strong>
                                de <strong>{{ $activities->total() }}</strong> registros
                            </div>
                            {{ $activities->appends(request()->query())->links() }}
                        </div>
                    </div>
                @endif

            </div>
    @endif

    {{-- Modal: ver propiedades JSON --}}
    <div class="modal fade" id="propertiesModal" tabindex="-1" aria-labelledby="propertiesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="propertiesModalLabel">Propiedades del evento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2" id="propertiesDescription"></p>
                    <pre class="bg-light rounded p-3 mb-0 audit-properties-pre" id="propertiesContent"></pre>
                </div>
                <div class="modal-footer d-block">
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $(document).on('click', '.btn-view-properties', function () {
        const raw = $(this).data('properties');
        const description = $(this).data('description');
        const formatted = JSON.stringify(raw, null, 2);

        $('#propertiesDescription').text(description);
        $('#propertiesContent').text(formatted);
        $('#propertiesModal').modal('show');
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
