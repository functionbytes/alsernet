@extends('layouts.theme')

@section('title', 'Automatizaciones')

@section('content')
@include('core::components.card', ['title' => 'Automatizaciones'])

<div class="widget-content searchable-container list">
    @include('core::components.alerts')

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">Automatizaciones</h5>
                    <small class="text-muted">Reglas automaticas que se ejecutan cuando un ticket cumple condiciones</small>
                </div>
                <a href="{{ route('manager.helpdesk.settings.automations.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Nueva automatizacion
                </a>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card bg-light-secondary h-100"><div class="card-body">
                        <h6 class="text-muted mb-1">Total</h6>
                        <h4 class="fw-bold mb-0">{{ $stats['total'] }}</h4>
                    </div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-secondary h-100"><div class="card-body">
                        <h6 class="text-muted mb-1">Activas</h6>
                        <h4 class="fw-bold mb-0">{{ $stats['active'] }}</h4>
                    </div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-secondary h-100"><div class="card-body">
                        <h6 class="text-muted mb-1">Inactivas</h6>
                        <h4 class="fw-bold mb-0">{{ $stats['inactive'] }}</h4>
                    </div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-secondary h-100"><div class="card-body">
                        <h6 class="text-muted mb-1">Ejecuciones</h6>
                        <h4 class="fw-bold mb-0">{{ $stats['total_runs'] }}</h4>
                    </div></div>
                </div>
            </div>

            @if($automations->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Trigger</th>
                                <th>Ejecuciones</th>
                                <th>Ultima ejecucion</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($automations as $auto)
                            <tr>
                                <td>
                                    <strong>{{ $auto->name }}</strong>
                                    @if($auto->description)
                                        <div><small class="text-muted">{{ \Str::limit($auto->description, 60) }}</small></div>
                                    @endif
                                </td>
                                <td><span class="badge bg-info-subtle text-info">{{ $auto->trigger_event }}</span></td>
                                <td>{{ $auto->run_count }}</td>
                                <td><small class="text-muted">{{ $auto->last_run_at?->diffForHumans() ?? '—' }}</small></td>
                                <td>
                                    @if($auto->is_active)
                                        <span class="badge bg-success-subtle text-success">Activa</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <a class="text-muted" href="#" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-vertical"></i></a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="{{ route('manager.helpdesk.settings.automations.edit', $auto) }}">Editar</a></li>
                                            <li>
                                                <form action="{{ route('manager.helpdesk.settings.automations.destroy', $auto) }}" method="POST" class="d-inline needs-confirm" data-confirm-msg="¿Eliminar automatizacion?">
                                                    @csrf @method('DELETE')
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
                {{ $automations->links() }}
            @else
                <div class="text-center py-5">
                    <i class="fas fa-magic fa-3x opacity-50 text-muted mb-3"></i>
                    <h6 class="text-muted">No hay automatizaciones configuradas</h6>
                    <p class="small text-muted">Crea reglas para que se ejecuten automaticamente cuando un ticket cumpla condiciones.</p>
                    <a href="{{ route('manager.helpdesk.settings.automations.create') }}" class="btn btn-primary">Crear la primera</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
