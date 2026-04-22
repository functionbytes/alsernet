@extends('layouts.theme')

@section('title', 'Miembros del Equipo')

@section('content')

    @include('core::components.card', ['title' => 'Miembros del equipo'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- System Settings Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Miembros del equipo</h5>
                        <p class="small mb-0 text-muted">Gestiona el equipo de soporte, roles y configuraciones de disponibilidad</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search') || request('role') != 'all' || request('group_id') != 'all')
                            <a href="{{ route('manager.helpdesk.settings.tickets.team.members') }}" class="btn btn-secondary">
                                Limpiar filtros
                            </a>
                        @endif
                        <a href="{{ route('manager.helpdesk.settings.ticket-groups.index') }}" class="btn btn-primary">
                            Ver grupos
                        </a>
                    </div>
                </div>
            </div>

            <!-- Status Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">
                                            Total
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['total'] }}</h4>
                                        <small class="text-muted">Miembros del equipo</small>
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
                                        <h6 class="card-title text-success mb-2">
                                            Disponibles
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['available'] }}</h4>
                                        <small class="text-muted">Siempre activos</small>
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
                                        <h6 class="card-title text-warning mb-2">
                                            Horario
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['working_hours'] }}</h4>
                                        <small class="text-muted">Horario laboral</small>
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
                                        <h6 class="card-title text-warning mb-2">
                                            Inactivos
                                        </h6>
                                        <h4 class="mb-1 fw-bold">{{ $stats['unavailable'] }}</h4>
                                        <small class="text-muted">No disponibles</small>
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
                    <h6 class="mb-1 fw-bold">Distribución del equipo</h6>
                    <p class="text-muted mb-0">Estadísticas de roles y configuraciones</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="mb-3 fw-semibold">Roles</h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="text-center p-2 rounded bg-white">
                                            <h6 class="mb-0 fw-bold text-info">{{ $stats['admin'] }}</h6>
                                            <small class="text-muted">Admins</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-2 rounded bg-white">
                                            <h6 class="mb-0 fw-bold text-info">{{ $stats['manager'] }}</h6>
                                            <small class="text-muted">Managers</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-2 rounded bg-white">
                                            <h6 class="mb-0 fw-bold text-info">{{ $stats['support'] }}</h6>
                                            <small class="text-muted">Soporte</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-2 rounded bg-white">
                                            <h6 class="mb-0 fw-bold text-info">{{ $stats['callcenter'] }}</h6>
                                            <small class="text-muted">Call Center</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <h6 class="mb-3 fw-semibold">Límites de asignación</h6>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="mb-0">Sin límite</h6>
                                        <small class="text-muted">Tickets ilimitados</small>
                                    </div>
                                    <h4 class="mb-0 fw-bold text-success">{{ $stats['with_unlimited'] }}</h4>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Con límite</h6>
                                        <small class="text-muted">Asignaciones restringidas</small>
                                    </div>
                                    <h4 class="mb-0 fw-bold text-info">{{ $stats['with_limit'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('manager.helpdesk.settings.tickets.team.members') }}" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Buscar</label>
                            <input type="text" name="search" class="form-control"
                                   placeholder="Nombre, apellido o email..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Rol</label>
                            <select name="role" class="form-select select2">
                                <option value="all">Todos los roles</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                                        {{ ucfirst($role->name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Grupo</label>
                            <select name="group_id" class="form-select select2">
                                <option value="all">Todos los grupos</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Members Table -->
            <div class="card-body">
                @if($members->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="30%">Miembro</th>
                                    <th width="15%">Rol</th>
                                    <th width="25%">Grupos</th>
                                    <th width="15%" class="text-center">Disponibilidad</th>
                                    <th width="10%" class="text-center">Límite</th>
                                    <th width="5%" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($members as $member)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                                     style="width: 36px; height: 36px; background-color: #f5f6f8; color: #90bb13; font-weight: 600; font-size: 0.85rem;">
                                                    {{ strtoupper(substr($member->firstname, 0, 1) . substr($member->lastname, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <strong>{{ $member->full_name }}</strong>
                                                    <div><small class="text-muted">{{ $member->email }}</small></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $roleName = $member->roles->first()?->name ?? 'Sin rol';
                                                $roleColors = [
                                                    'admin' => 'danger',
                                                    'manager' => 'warning',
                                                    'support' => 'success',
                                                    'callcenter' => 'info',
                                                ];
                                                $color = $roleColors[$roleName] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">
                                                {{ ucfirst($roleName) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($member->groups->count() > 0)
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($member->groups->take(3) as $group)
                                                        <span class="badge bg-light-subtle text-black">
                                                            {{ $group->name }}
                                                        </span>
                                                    @endforeach
                                                    @if($member->groups->count() > 3)
                                                        <span class="badge bg-light-subtle text-black">
                                                            +{{ $member->groups->count() - 3 }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @else
                                                <small class="text-muted">Sin grupos</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $accepts = $member->agentSettings->accepts_conversations ?? 'yes';
                                            @endphp
                                            <span class="badge bg-{{ $accepts === 'yes' ? 'success' : ($accepts === 'working_hours' ? 'warning' : 'danger') }}-subtle text-{{ $accepts === 'yes' ? 'success' : ($accepts === 'working_hours' ? 'warning' : 'danger') }}">
                                                @if($accepts === 'yes')
                                                    Siempre
                                                @elseif($accepts === 'working_hours')
                                                    Horario
                                                @else
                                                    Inactivo
                                                @endif
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $limit = $member->agentSettings->assignment_limit ?? 0;
                                            @endphp
                                            <span class="badge bg-{{ $limit == 0 ? 'success' : 'info' }}-subtle text-{{ $limit == 0 ? 'success' : 'info' }}">
                                                {{ $limit == 0 ? 'Ilimitado' : $limit }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                 <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-duotone fa-solid fa-ellipsis"></i>
                                        </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('manager.helpdesk.settings.tickets.team.member.edit', $member->id) }}">
                                                            Editar
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
                        <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center mx-auto">
                            <i class="fas fa-users fs-7"></i>
                        </div>
                        <h6 class="mb-1">No hay miembros encontrados</h6>
                        <p class="text-muted mb-0">
                            @if(request('search') || request('role') != 'all' || request('group_id') != 'all')
                                No se encontraron resultados para los filtros aplicados
                            @else
                                No hay miembros del equipo registrados
                            @endif
                        </p>
                    </div>
                @endif
            </div>

            <!-- Pagination -->
            @if($members->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong>{{ $members->firstItem() }}</strong> a <strong>{{ $members->lastItem() }}</strong>
                            de <strong>{{ $members->total() }}</strong> miembros
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $members->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>

    </div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {

    $('.select2').select2({
        allowClear: false,
        language: {
            noResults: function() {
                return 'Sin resultados';
            },
            searching: function() {
                return 'Buscando...';
            }
        }
    });

    $('.select2').on('change', function() {
        $('#filterForm').submit();
    });

    // Search on enter
    $('input[name="search"]').on('keypress', function(e) {
        if (e.which === 13) {
            $('#filterForm').submit();
        }
    });

    @if (session('success'))
        toastr.success('{{ session('success') }}', 'Éxito');
    @endif

    @if (session('error'))
        toastr.error('{{ session('error') }}', 'Error');
    @endif

});
</script>

@endsection
