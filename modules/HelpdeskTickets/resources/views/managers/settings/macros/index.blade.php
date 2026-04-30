@extends('layouts.theme')

@section('title', 'Macros')

@section('page_header')
    @include('core::components.card', ['title' => 'Macros'])
@endsection

@section('content')
<div class="widget-content searchable-container list">
    @include('core::components.alerts')

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">Macros</h5>
                    <small class="text-muted">Acciones predefinidas que un agente puede aplicar manualmente en un ticket</small>
                </div>
                <a href="{{ route('manager.helpdesk.settings.macros.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Nueva macro
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
                        <h6 class="text-muted mb-1">Compartidas</h6>
                        <h4 class="fw-bold mb-0">{{ $stats['shared'] }}</h4>
                    </div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-secondary h-100"><div class="card-body">
                        <h6 class="text-muted mb-1">Usos totales</h6>
                        <h4 class="fw-bold mb-0">{{ $stats['total_uses'] }}</h4>
                    </div></div>
                </div>
            </div>

            <div class="mb-3">
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control form-control-sm w-auto"
                           placeholder="Buscar macro..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-sm btn-light">Buscar</button>
                    @if(request('search'))
                        <a href="{{ route('manager.helpdesk.settings.macros.index') }}" class="btn btn-sm btn-light">Limpiar</a>
                    @endif
                </form>
            </div>

            @if($macros->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Acciones</th>
                                <th>Visibilidad</th>
                                <th>Usos</th>
                                <th>Ultimo uso</th>
                                <th>Estado</th>
                                <th class="text-end">Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($macros as $macro)
                            <tr>
                                <td>
                                    <strong>{{ $macro->name }}</strong>
                                    @if($macro->description)
                                        <div><small class="text-muted">{{ \Str::limit($macro->description, 60) }}</small></div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info">{{ count($macro->actions ?? []) }} acciones</span>
                                </td>
                                <td>
                                    @if($macro->is_shared)
                                        <span class="badge bg-primary-subtle text-primary">Compartida</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Personal</span>
                                    @endif
                                </td>
                                <td>{{ $macro->usage_count }}</td>
                                <td><small class="text-muted">{{ $macro->last_used_at?->diffForHumans() ?? '—' }}</small></td>
                                <td>
                                    @if($macro->is_active)
                                        <span class="badge bg-success-subtle text-success">Activa</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Inactiva</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <a class="text-muted" href="#" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-vertical"></i></a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="{{ route('manager.helpdesk.settings.macros.edit', $macro) }}">Editar</a></li>
                                            <li>
                                                <form action="{{ route('manager.helpdesk.settings.macros.destroy', $macro) }}" method="POST" class="d-inline needs-confirm" data-confirm-msg="¿Eliminar esta macro?">
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
                {{ $macros->appends(request()->input())->links() }}
            @else
                <div class="text-center py-5">
                    <i class="fas fa-bolt fa-3x opacity-50 text-muted mb-3"></i>
                    <h6 class="text-muted">No hay macros configuradas</h6>
                    <p class="small text-muted">Crea macros para que los agentes puedan aplicar acciones predefinidas con un clic.</p>
                    <a href="{{ route('manager.helpdesk.settings.macros.create') }}" class="btn btn-primary">Crear la primera</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
