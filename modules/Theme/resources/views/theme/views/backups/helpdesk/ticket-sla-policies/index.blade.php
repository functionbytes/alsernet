@extends('layouts.theme')

@section('title', 'Políticas SLA')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-stopwatch me-2 text-primary"></i>
                Políticas SLA
            </h4>
            <p class="text-muted mb-0">Define tiempos de respuesta y resolución para los tickets</p>
        </div>
        <div class="d-flex gap-2">
            @if(request('search'))
                <a href="{{ route('manager.helpdesk.backups.tickets.sla-policies.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i> Limpiar
                </a>
            @endif
            <a href="{{ route('manager.helpdesk.backups.tickets.sla-policies.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> Nueva política
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted mb-1">Total</p>
                    <h4 class="fw-bold mb-0">{{ $stats['total'] }}</h4>
                    <small class="text-muted">políticas</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-success small mb-1">Activas</p>
                    <h4 class="fw-bold mb-0">{{ $stats['active'] }}</h4>
                    <small class="text-muted">habilitadas</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-secondary small mb-1">Inactivas</p>
                    <h4 class="fw-bold mb-0">{{ $stats['inactive'] }}</h4>
                    <small class="text-muted">deshabilitadas</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-warning small mb-1">Con escalación</p>
                    <h4 class="fw-bold mb-0">{{ $stats['with_escalation'] }}</h4>
                    <small class="text-muted">configuradas</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        {{-- Search --}}
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('manager.helpdesk.backups.tickets.sla-policies.index') }}">
                <div class="row g-2 align-items-center">
                    <div class="col-md-9">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="search" name="search" class="form-control border-start-0"
                                   placeholder="Buscar por nombre o descripción..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Buscar</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="card-body p-0">
            @if($policies->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th class="text-center">Primera respuesta</th>
                                <th class="text-center">Resolución</th>
                                <th class="text-center">Horario laboral</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center" width="80">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($policies as $policy)
                                <tr>
                                    <td class="align-middle">
                                        <strong>{{ $policy->name }}</strong>
                                        @if($policy->is_default)
                                            <span class="badge bg-primary-subtle text-primary ms-1">Por defecto</span>
                                        @endif
                                        @if($policy->description)
                                            <small class="d-block text-muted">{{ Str::limit($policy->description, 60) }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="fw-semibold">{{ $policy->first_response_time }}</span>
                                        <small class="text-muted">min</small>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="fw-semibold">{{ $policy->resolution_time }}</span>
                                        <small class="text-muted">min</small>
                                    </td>
                                    <td class="text-center align-middle">
                                        @if($policy->business_hours_only)
                                            <span class="badge bg-info-subtle text-info">
                                                <i class="fas fa-clock me-1"></i> Sí
                                            </span>
                                        @else
                                            <span class="badge bg-light text-secondary">24/7</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        @if($policy->active)
                                            <span class="badge bg-success-subtle text-success">Activa</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('manager.helpdesk.backups.tickets.sla-policies.edit', $policy->id) }}">
                                                        <i class="fas fa-pen me-2"></i> Editar
                                                    </a>
                                                </li>
                                                <li>
                                                    <form method="POST" action="{{ route('manager.helpdesk.backups.tickets.sla-policies.toggle', $policy->id) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fas fa-toggle-{{ $policy->active ? 'off' : 'on' }} me-2"></i>
                                                            {{ $policy->active ? 'Desactivar' : 'Activar' }}
                                                        </button>
                                                    </form>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('manager.helpdesk.backups.tickets.sla-policies.destroy', $policy->id) }}"
                                                          onsubmit="return confirm('¿Eliminar esta política SLA?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="fas fa-trash me-2"></i> Eliminar
                                                        </button>
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
                    <i class="fas fa-stopwatch fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">
                        @if(request('search'))
                            Sin resultados para "{{ request('search') }}"
                        @else
                            No hay políticas SLA configuradas
                        @endif
                    </h6>
                    @if(!request('search'))
                        <a href="{{ route('manager.helpdesk.backups.tickets.sla-policies.create') }}" class="btn btn-primary btn-sm mt-2">
                            <i class="fas fa-plus me-1"></i> Crear primera política
                        </a>
                    @endif
                </div>
            @endif
        </div>

        @if($policies->hasPages())
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Mostrando {{ $policies->firstItem() }}–{{ $policies->lastItem() }} de {{ $policies->total() }}
                </small>
                {{ $policies->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>
@endsection
