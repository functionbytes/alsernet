@extends('layouts.theme')

@section('title', $team->name)

@section('content')

    @include('core::components.alerts')

    <div class="row">
        <div class="col-lg-8">
            <!-- Team Details Card -->
            <div class="card mb-3">
                <div class="card-header p-4 border-bottom border-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1 fw-bold">{{ $team->name }}</h5>
                            @if($team->description)
                                <p class="small mb-0 text-muted">{{ $team->description }}</p>
                            @endif
                        </div>
                        <div>
                            <a href="{{ route('settings.chat.teams.edit', $team) }}" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Configuración</h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Nombre</h6>
                            <p class="mb-0">{{ $team->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Auto-asignación</h6>
                            @if($team->allow_auto_assign)
                                <span class="badge bg-success-subtle text-success">
                                    <i class="fas fa-check-circle"></i> Habilitada
                                </span>
                            @else
                                <span class="badge bg-info-subtle text-info">
                                    <i class="fas fa-times-circle"></i> Deshabilitada
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($team->description)
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">Descripción</h6>
                            <p class="mb-0">{{ $team->description }}</p>
                        </div>
                    @endif

                    <h6 class="fw-bold mb-3 border-bottom pb-2">Miembros del equipo ({{ $team->members->count() }})</h6>

                    @if($team->members->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($team->members as $member)
                                        <tr>
                                            <td>
                                                <strong>{{ $member->firstname }} {{ $member->lastname }}</strong>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $member->email }}</small>
                                            </td>
                                            <td>
                                                @if($member->pivot->is_lead)
                                                    <span class="badge bg-primary-subtle text-primary">
                                                        <i class="fas fa-star"></i> Líder
                                                    </span>
                                                @else
                                                    <span class="badge bg-info-subtle text-info">Miembro</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="d-flex flex-column align-items-center">
                                <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                    <i class="fas fa-users fs-7"></i>
                                </div>
                                <h6 class="mb-1">No hay miembros asignados</h6>
                                <p class="text-muted mb-3">Agrega miembros al equipo para comenzar</p>
                                <a href="{{ route('settings.chat.teams.edit', $team) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Agregar miembros
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Conversations Card -->
            @if($team->conversations && $team->conversations->count() > 0)
                <div class="card">
                    <div class="card-header p-4 border-bottom border-light">
                        <h6 class="mb-0 fw-bold">Conversaciones recientes</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Canal</th>
                                        <th>Estado</th>
                                        <th>Asignado a</th>
                                        <th>Última actividad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($team->conversations as $conversation)
                                        <tr>
                                            <td>
                                                <a href="{{ route('settings.chat.conversation.show', $conversation) }}">
                                                    {{ $conversation->customer->name ?? 'Sin nombre' }}
                                                </a>
                                            </td>
                                            <td>{{ $conversation->inbox->name ?? '-' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $conversation->status === 'open' ? 'success' : 'secondary' }}-subtle text-{{ $conversation->status === 'open' ? 'success' : 'secondary' }}">
                                                    {{ ucfirst($conversation->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $conversation->assignee->firstname ?? 'Sin asignar' }}</td>
                                            <td>
                                                <small class="text-muted">{{ $conversation->last_activity_at?->diffForHumans() ?? '-' }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Statistics Card -->
            <div class="card mb-3">
                <div class="card-header p-4 border-bottom border-light">
                    <h6 class="mb-0 fw-bold">Estadísticas</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Creado</h6>
                        <p class="mb-0">{{ $team->created_at->format('d/m/Y H:i') }}</p>
                        <small class="text-muted">{{ $team->created_at->diffForHumans() }}</small>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Última actualización</h6>
                        <p class="mb-0">{{ $team->updated_at->format('d/m/Y H:i') }}</p>
                        <small class="text-muted">{{ $team->updated_at->diffForHumans() }}</small>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <h6 class="text-muted mb-1">Miembros totales</h6>
                        <h4 class="mb-0">{{ $team->members->count() }}</h4>
                    </div>

                    <div>
                        <h6 class="text-muted mb-1">Conversaciones activas</h6>
                        <h4 class="mb-0">{{ $team->conversations->count() ?? 0 }}</h4>
                    </div>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="card">
                <div class="card-header p-4 border-bottom border-light">
                    <h6 class="mb-0 fw-bold">Acciones</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('settings.chat.teams.edit', $team) }}" class="btn btn-outline-primary">
                            <i class="fas fa-edit"></i> Editar equipo
                        </a>
                        <a href="{{ route('settings.chat.teams.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al listado
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
