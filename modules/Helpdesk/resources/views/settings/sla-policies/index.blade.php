@extends('layouts.theme')

@section('title', 'Politicas SLA')

@section('content')

    @include('core::components.alerts')

    <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Politicas SLA configuradas</h5>
                        <p class="small mb-0 text-muted">Acuerdos de nivel de servicio para tiempos de respuesta y resolucion de conversaciones</p>
                    </div>
                    <a href="{{ route('settings.helpdesk.sla-policies.create') }}" class="btn btn-primary">
                        Nueva politica SLA
                    </a>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                <small class="text-muted">Politicas configuradas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-success mb-2">Activas</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                                <small class="text-muted">Politicas habilitadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-warning mb-2">Inactivas</h6>
                                <h4 class="mb-1 fw-bold">{{ $stats['inactive'] }}</h4>
                                <small class="text-muted">Politicas deshabilitadas</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($policies->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th class="text-center">Primera respuesta</th>
                                    <th class="text-center">Resolucion</th>
                                    <th class="text-center">Horario laboral</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($policies as $policy)
                                    <tr>
                                        <td>
                                            <strong>{{ $policy->name }}</strong>
                                            @if($policy->description)
                                                <div>
                                                    <small class="text-muted">{{ Str::limit($policy->description, 60) }}</small>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($policy->first_response_label)
                                                <span class="badge bg-info-subtle text-info">{{ $policy->first_response_label }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($policy->resolution_label)
                                                <span class="badge bg-primary-subtle text-primary">{{ $policy->resolution_label }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($policy->business_hours_only)
                                                <span class="badge bg-warning-subtle text-warning">Solo laboral</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">24/7</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-toggle-status {{ $policy->is_active ? 'btn-success-subtle' : 'btn-secondary-subtle' }}"
                                                data-id="{{ $policy->id }}"
                                                data-url="{{ route('settings.helpdesk.sla-policies.toggle', $policy) }}"
                                                title="{{ $policy->is_active ? 'Desactivar' : 'Activar' }}">
                                                @if($policy->is_active)
                                                    <span class="badge bg-success-subtle text-success">Activo</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                                @endif
                                            </button>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.sla-policies.edit', $policy) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button class="dropdown-item btn-delete"
                                                            data-id="{{ $policy->id }}"
                                                            data-url="{{ route('settings.helpdesk.sla-policies.destroy', $policy) }}"
                                                            data-name="{{ $policy->name }}">
                                                            Eliminar
                                                        </button>
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
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-shield-halved fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay politicas SLA configuradas</h6>
                            <p class="text-muted mb-3">Crea tu primera politica SLA para gestionar los tiempos de respuesta y resolucion</p>
                            <a href="{{ route('settings.helpdesk.sla-policies.create') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Crear primera politica SLA
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            @if($policies->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong>{{ $policies->firstItem() }}</strong> a <strong>{{ $policies->lastItem() }}</strong>
                            de <strong>{{ $policies->total() }}</strong> politicas
                        </div>
                        {{ $policies->appends(request()->input())->links() }}
                    </div>
                </div>
            @endif

    </div>

    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $(document).on('click', '.btn-delete', function () {
        const url = $(this).data('url');
        const name = $(this).data('name');
        $('#deleteForm').attr('action', url);
        $('#deleteItemName').text(name);
        $('#deleteModal').modal('show');
    });

    $(document).on('click', '.btn-toggle-status', function () {
        const btn = $(this);
        const url = btn.data('url');

        $.ajax({
            url: url,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            success: function (response) {
                if (response.is_active) {
                    btn.attr('title', 'Desactivar');
                    btn.html('<span class="badge bg-success-subtle text-success">Activo</span>');
                } else {
                    btn.attr('title', 'Activar');
                    btn.html('<span class="badge bg-secondary-subtle text-secondary">Inactivo</span>');
                }
                toastr.success('Estado actualizado correctamente.', 'Exito');
            },
            error: function () {
                toastr.error('No se pudo actualizar el estado.', 'Error');
            },
        });
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
