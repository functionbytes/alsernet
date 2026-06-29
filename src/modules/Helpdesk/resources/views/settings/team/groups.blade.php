@extends('layouts.theme')

@section('title', 'Grupos de Equipo')

@push('styles')
<style>
.hd-avatar-xs { width: 32px; height: 32px; font-size: 0.7rem; font-weight: 600; background-color: #f5f6f8; color: #90bb13; }
.hd-avatar-xs-muted { width: 32px; height: 32px; font-size: 0.7rem; font-weight: 600; background-color: #f5f6f8; color: #6c757d; }
.hd-empty-circle { width: 64px; height: 64px; background-color: #f5f6f8; }
</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Grupos de Equipo'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- System Settings Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Grupos de Equipo</h5>
                        <p class="small mb-0 text-muted">Organiza a tus agentes en grupos para asignación eficiente de tickets</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.helpdesk.team.members') }}" class="btn btn-secondary">
                            Ver miembros
                        </a>
                        <a href="{{ route('settings.helpdesk.team.group.create') }}" class="btn btn-primary">
                            Nuevo grupo
                        </a>
                    </div>
                </div>
            </div>

            <!-- Status Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">
                                            Total grupos
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                        <small class="text-muted">Grupos creados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-success mb-2">
                                            Con miembros
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['with_members'] }}</h4>
                                        <small class="text-muted">Grupos activos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-warning mb-2">
                                           Vacíos
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['empty'] }}</h4>
                                        <small class="text-muted">Sin miembros</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secondary Stats -->
            <div class="card-body border-bottom">
                <div class="mb-3">
                    <h6 class="mb-1 fw-bold">Distribución y configuración</h6>
                    <p class="text-muted mb-0">Estadísticas de miembros y modos de asignación</p>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="mb-3 fw-semibold d-flex align-items-center gap-2">
                                    Miembros
                                </h6>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <div class="text-center p-2 rounded bg-white">
                                            <h5 class="mb-0 fw-bold text-primary">{{ $stats['total_members'] }}</h5>
                                            <small class="text-muted">Total</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-center p-2 rounded bg-white">
                                            <h5 class="mb-0 fw-bold text-success">{{ $stats['primary_members'] }}</h5>
                                            <small class="text-muted">Primarios</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-center p-2 rounded bg-white">
                                            <h5 class="mb-0 fw-bold text-success">{{ $stats['backup_members'] }}</h5>
                                            <small class="text-muted">Backup</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="mb-3 fw-semibold d-flex align-items-center gap-2">
                                    Modos de asignación
                                </h6>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">Round Robin</small>
                                    <h6 class="mb-0 fw-bold">{{ $stats['round_robin'] }}</h6>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">Load Balance</small>
                                    <h6 class="mb-0 fw-bold">{{ $stats['load_balance'] }}</h6>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Priority</small>
                                    <h6 class="mb-0 fw-bold">{{ $stats['priority'] }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div class="card-body border-bottom">
                <form method="GET" id="searchForm">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Buscar grupo</label>
                            <input type="text" name="search" class="form-control"
                                   placeholder="Nombre del grupo..."
                                   value="{{ request('search') }}">
                        </div>
                    </div>
                </form>
            </div>

            <!-- Groups Table -->
            <div class="card-body">
                @if($groups->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="25%">Grupo</th>
                                    <th width="20%">Modo de Asignación</th>
                                    <th width="35%">Miembros</th>
                                    <th width="15%" class="text-center">Distribución</th>
                                    <th width="5%" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groups as $group)
                                    <tr>
                                        <td class="align-middle">
                                            <strong>{{ $group->name }}</strong>
                                            @if($group->default)
                                                <span class="badge bg-light-subtle text-black">Por defecto</span>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            @php
                                                $modeLabels = [
                                                    'round_robin' => 'Round Robin',
                                                    'load_balance' => 'Balanceo de Carga',
                                                    'priority' => 'Por Prioridad',
                                                ];
                                                $modeColors = [
                                                    'round_robin' => 'info',
                                                    'load_balance' => 'success',
                                                    'priority' => 'warning',
                                                ];
                                            @endphp
                                            <span class="badge bg-{{ $modeColors[$group->assignment_mode] ?? 'secondary' }}-subtle text-{{ $modeColors[$group->assignment_mode] ?? 'secondary' }}">
                                                {{ $modeLabels[$group->assignment_mode] ?? ucfirst(str_replace('_', ' ', $group->assignment_mode)) }}
                                            </span>
                                        </td>


                                        <td class="align-middle">
                                            @if($group->users->count() > 0)
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @foreach($group->users->take(4) as $user)
                                                            <div class="hd-avatar-xs rounded-circle d-flex align-items-center justify-content-center"
                                                                 title="{{ $user->full_name }} ({{ $user->pivot->conversation_priority === 'primary' ? 'Primario' : 'Backup' }})">
                                                                {{ strtoupper(substr($user->firstname, 0, 1) . substr($user->lastname, 0, 1)) }}
                                                            </div>
                                                        @endforeach
                                                        @if($group->users->count() > 4)
                                                            <div class="hd-avatar-xs-muted rounded-circle d-flex align-items-center justify-content-center">
                                                                +{{ $group->users->count() - 4 }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <small class="text-muted">{{ $group->users->count() }} total</small>
                                                </div>
                                            @else
                                                <small class="text-muted">Sin miembros</small>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            @php
                                                $primaryCount = $group->users->where('pivot.conversation_priority', 'primary')->count();
                                                $backupCount = $group->users->where('pivot.conversation_priority', 'backup')->count();
                                            @endphp
                                            @if($group->users->count() > 0)
                                                <div class="d-flex justify-content-center gap-2">
                                                    <span class="badge bg-light-subtle text-black">{{ $primaryCount }} P</span>
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ $backupCount }} B</span>
                                                </div>
                                            @else
                                                <small class="text-muted">-</small>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.team.group.edit', $group->id) }}">
                                                            Editar
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" action="{{ route('settings.helpdesk.team.group.destroy', $group->id) }}"
                                                              class="needs-confirm"
                                                              data-confirm-msg="¿Estás seguro de eliminar este grupo? Esta acción no se puede deshacer.">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item">
                                                                Eliminar
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
                        <div class="hd-empty-circle rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                            <i class="fa fa-users fs-3 text-muted"></i>
                        </div>
                        <h6 class="mb-1">No hay grupos disponibles</h6>
                        <p class="text-muted mb-3 small">
                            @if(request('search'))
                                No se encontraron grupos con el criterio de búsqueda
                            @else
                                Crea tu primer grupo para organizar a tu equipo
                            @endif
                        </p>
                        @if(!request('search'))
                            <a href="{{ route('settings.helpdesk.team.group.create') }}" class="btn btn-primary">
                                Crear primer grupo
                            </a>
                        @else
                            <a href="{{ route('settings.helpdesk.team.groups') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            @if($groups->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong>{{ $groups->firstItem() }}</strong> a <strong>{{ $groups->lastItem() }}</strong>
                            de <strong>{{ $groups->total() }}</strong> grupos
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $groups->links() }}
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
    // Auto-submit search on enter
    $('input[name="search"]').on('keypress', function(e) {
        if (e.which === 13) {
            $('#searchForm').submit();
        }
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Exito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif
});
</script>
@endpush
