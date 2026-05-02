@extends('theme::layouts.admin')

@section('title', 'Reglas automáticas')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Reglas automáticas</h1>
        <a href="{{ route('helpdesksocial.rules.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nueva regla
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Prioridad</th>
                            <th>Nombre</th>
                            <th>Plataforma</th>
                            <th>Condiciones</th>
                            <th>Acciones</th>
                            <th>Estado</th>
                            <th>Disparos</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rules as $rule)
                        <tr>
                            <td>{{ $rule->priority }}</td>
                            <td>
                                <div class="fw-semibold">{{ $rule->name }}</div>
                                <small class="text-muted">{{ $rule->description }}</small>
                            </td>
                            <td>
                                @if($rule->platform)
                                <span class="badge bg-secondary">{{ ucfirst($rule->platform) }}</span>
                                @else
                                <span class="badge bg-light text-dark">Todas</span>
                                @endif
                            </td>
                            <td>
                                @foreach($rule->conditions ?? [] as $condition)
                                <span class="badge bg-info text-dark">
                                    {{ $condition['field'] }} {{ $condition['operator'] }} {{ $condition['value'] }}
                                </span>
                                @endforeach
                            </td>
                            <td>
                                @foreach($rule->actions ?? [] as $action)
                                <span class="badge bg-success">
                                    {{ $action['type'] }}
                                </span>
                                @endforeach
                            </td>
                            <td>
                                @if($rule->is_active)
                                <span class="badge bg-success">Activa</span>
                                @else
                                <span class="badge bg-secondary">Inactiva</span>
                                @endif
                                @if($rule->stop_processing)
                                <span class="badge bg-warning text-dark" title="Detiene procesamiento">Stop</span>
                                @endif
                            </td>
                            <td>{{ $rule->trigger_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('helpdesksocial.rules.edit', $rule) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No hay reglas configuradas
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $rules->links() }}
        </div>
    </div>
</div>
@endsection
