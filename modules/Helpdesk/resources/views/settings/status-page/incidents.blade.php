@extends('layouts.theme')

@section('title', 'Pagina de estado — Incidentes')

@section('page_header')
    @include('core::components.card', ['title' => 'Pagina de estado'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        {{-- Navigation tabs --}}
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('settings.helpdesk.status.components') }}">
                    Componentes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('settings.helpdesk.status.incidents') }}">
                    Incidentes
                </a>
            </li>
        </ul>

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Incidentes registrados</h5>
                        <p class="small mb-0 text-muted">Historial de interrupciones y problemas del servicio</p>
                    </div>
                    <div class="ms-auto">
                        <a href="{{ route('settings.helpdesk.status.incidents.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo incidente
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['total']) }}</h4>
                                <small class="text-muted">Incidentes registrados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Activos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['active']) }}</h4>
                                <small class="text-muted">Sin resolver</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Resueltos</h6>
                                <h4 class="mb-1 fw-bold">{{ number_format($stats['resolved']) }}</h4>
                                <small class="text-muted">Cerrados</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($incidents->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Titulo</th>
                                    <th scope="col" class="text-center">Severidad</th>
                                    <th scope="col" class="text-center">Estado</th>
                                    <th scope="col">Iniciado</th>
                                    <th scope="col">Resuelto</th>
                                    <th scope="col" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($incidents as $incident)
                                    @php
                                        $severityColor = \Modules\Helpdesk\Models\StatusIncident::SEVERITY_COLORS[$incident->severity] ?? 'secondary';
                                        $statusColor = \Modules\Helpdesk\Models\StatusIncident::STATUS_COLORS[$incident->status] ?? 'secondary';
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $incident->title }}</span>
                                            @if(is_array($incident->affected_components) && count($incident->affected_components) > 0)
                                                <br><small class="text-muted">{{ count($incident->affected_components) }} componentes afectados</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $severityColor }}-subtle text-{{ $severityColor }}">
                                                {{ \Modules\Helpdesk\Models\StatusIncident::SEVERITIES[$incident->severity] ?? $incident->severity }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">
                                                {{ \Modules\Helpdesk\Models\StatusIncident::STATUSES[$incident->status] ?? $incident->status }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $incident->started_at ? $incident->started_at->format('d/m/Y H:i') : '—' }}
                                            </small>
                                        </td>
                                        <td>
                                            @if($incident->resolved_at)
                                                <small class="text-success">{{ $incident->resolved_at->format('d/m/Y H:i') }}</small>
                                            @else
                                                <small class="text-danger">Pendiente</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.status.incidents.edit', $incident->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item delete-btn" href="#"
                                                           data-bs-toggle="modal"
                                                           data-bs-target="#delete-modal"
                                                           data-url="{{ route('settings.helpdesk.status.incidents.destroy', $incident->id) }}"
                                                           data-title="Eliminar incidente: {{ $incident->title }}">
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
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-triangle-exclamation fa-3x mb-3 text-muted opacity-50"></i>
                        <h5 class="fw-bold mb-2">No hay incidentes registrados</h5>
                        <p class="text-muted mb-4">Registra incidentes para mantener informados a tus clientes sobre interrupciones del servicio</p>
                        <a href="{{ route('settings.helpdesk.status.incidents.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nuevo incidente
                        </a>
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($incidents->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando {{ $incidents->firstItem() }} - {{ $incidents->lastItem() }} de {{ $incidents->total() }}
                        </div>
                        <div>
                            {{ $incidents->links() }}
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    @if(session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif
    @if(session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

    $(document).on('click', '.delete-btn', function () {
        $('#delete-modal .modal-title').text($(this).data('title'));
        $('#delete-form').attr('action', $(this).data('url'));
    });
});
</script>
@endpush
