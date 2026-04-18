@extends('layouts.theme')

@section('title', 'Políticas SLA')

@section('content')

    @include('core::components.card', ['title' => 'Políticas SLA'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Políticas SLA disponibles</h5>
                        <p class="small mb-0 text-muted">Define tiempos de respuesta y resolución para tus tickets</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search'))
                            <a href="{{ route('manager.helpdesk.backups.ticket-sla-policies.index') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('manager.helpdesk.backups.ticket-sla-policies.create') }}" class="btn btn-primary">
                            Nueva política SLA
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                        <small class="text-muted">Políticas configuradas</small>
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
                                        <h6 class="card-title text-success mb-2">Activas</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['active'] }}</h4>
                                        <small class="text-muted">Políticas habilitadas</small>
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
                                        <h6 class="card-title text-warning mb-2">Con Escalamiento</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['with_escalation'] }}</h4>
                                        <small class="text-muted">Políticas con escalamiento</small>
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
                                        <h6 class="card-title text-info mb-2">Horario Comercial</h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['business_hours_only'] }}</h4>
                                        <small class="text-muted">Solo horario comercial</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('manager.helpdesk.backups.ticket-sla-policies.index') }}">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control" placeholder="Buscar políticas SLA..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Policies List -->
            <div class="card-body">
                @if($policies->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th width="20%">Nombre</th>
                                <th width="15%">Primera Respuesta</th>
                                <th width="15%">Tiempo Resolución</th>
                                <th width="15%">Horario</th>
                                <th width="15%">Escalamiento</th>
                                <th width="10%" class="text-center">Estado</th>
                                <th width="10%" class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($policies as $policy)
                                <tr>
                                    <td>
                                        <div>
                                            <strong>{{ $policy->name }}</strong>
                                            <small class="d-block text-muted">{{ Str::limit($policy->description, 40) }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info">{{ $policy->first_response_time }} min</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning-subtle text-warning">{{ $policy->resolution_time }} min</span>
                                    </td>
                                    <td>
                                        @if($policy->business_hours_only)
                                            <span class="badge bg-primary-subtle text-primary">Horario Comercial</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">24/7</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($policy->enable_escalation)
                                            <span class="badge bg-danger-subtle text-danger">Activado</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('manager.helpdesk.backups.ticket-sla-policies.toggle', $policy->id) }}" class="toggle-form">
                                            @csrf
                                            @method('PATCH')
                                            <div class="form-check form-switch d-inline-block">
                                                <input type="checkbox" class="form-check-input toggle-checkbox" role="switch"
                                                       {{ $policy->is_active ? 'checked' : '' }}
                                                      >
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.helpdesk.backups.ticket-sla-policies.edit', $policy->id) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('manager.helpdesk.backups.ticket-sla-policies.destroy', $policy->id) }}"
                                                          onsubmit="return confirm('¿Estás seguro de eliminar esta política SLA?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item">Eliminar</button>
                                                    </form>
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
                                <i class="fas fa-clock fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay políticas SLA para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados
                                @else
                                    Crea tu primera política SLA para gestionar tiempos de respuesta
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('manager.helpdesk.backups.ticket-sla-policies.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear Primera Política SLA
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            @if($policies->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong>{{ $policies->firstItem() }}</strong> a <strong>{{ $policies->lastItem() }}</strong>
                            de <strong>{{ $policies->total() }}</strong> políticas
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $policies->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.toggle-form').on('submit', function() {
        $(this).find('.toggle-checkbox').prop('disabled', true);
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
@endsection
