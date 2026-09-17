@extends('layouts.theme')

@section('title', 'Miembros del Equipo')

@push('styles')
<style>
    .hd-member-avatar { width: 36px; height: 36px; background-color: #f5f6f8; color: #90bb13; font-weight: 600; font-size: 0.85rem; }
    .tm-filter-badge { font-size: .6rem; }
    .tm-bulk-toolbar { z-index: 1050; }
</style>
@endpush

@section('page_header')
    @include('core::components.card', ['title' => 'Miembros del equipo'])
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
                        <h5 class="mb-1 fw-bold">Miembros del equipo</h5>
                        <p class="small mb-0 text-muted">Gestiona el equipo de soporte, roles y configuraciones de disponibilidad</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.helpdesk.team.groups') }}" class="btn btn-primary">
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
                                        <h6 class="card-title  mb-2">
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
                                        <h6 class="card-title  mb-2">
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
                                        <h6 class="card-title  mb-2">
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
                                            <h6 class="mb-0 fw-bold">{{ $stats['admin'] }}</h6>
                                            <small class="text-muted">Admins</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-2 rounded bg-white">
                                            <h6 class="mb-0 fw-bold">{{ $stats['manager'] }}</h6>
                                            <small class="text-muted">Managers</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-2 rounded bg-white">
                                            <h6 class="mb-0 fw-bold">{{ $stats['support'] }}</h6>
                                            <small class="text-muted">Soporte</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-2 rounded bg-white">
                                            <h6 class="mb-0 fw-bold">{{ $stats['callcenter'] }}</h6>
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
                                    <h4 class="mb-0 fw-bold">{{ $stats['with_unlimited'] }}</h4>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Con límite</h6>
                                        <small class="text-muted">Asignaciones restringidas</small>
                                    </div>
                                    <h4 class="mb-0 fw-bold">{{ $stats['with_limit'] }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Busqueda y filtros -->
            <div class="card-body border-bottom">
                @php
                    $advancedKeys = ['role', 'group_id', 'availability'];
                    $isSet = fn ($k) => request()->filled($k) && request($k) !== 'all';
                    $activeFilterCount = collect($advancedKeys)->filter($isSet)->count();
                    $hasAnyFilter = $activeFilterCount > 0 || request()->filled('search');
                    $availabilityLabels = ['yes' => 'Disponible', 'working_hours' => 'Solo en horario', 'no' => 'No disponible'];
                @endphp

                <form method="GET" action="{{ route('settings.helpdesk.team.members') }}" id="filterForm">
                    <input type="hidden" name="role"         id="filter-role"         value="{{ request('role') }}">
                    <input type="hidden" name="group_id"     id="filter-group"        value="{{ request('group_id') }}">
                    <input type="hidden" name="availability" id="filter-availability" value="{{ request('availability') }}">

                    <div class="d-flex align-items-center gap-2">
                        <input type="search" name="search" class="form-control flex-grow-1"
                               placeholder="Buscar por nombre, apellido o email..."
                               value="{{ request('search') }}">

                        <button type="button" class="btn btn-secondary position-relative flex-shrink-0"
                                data-bs-toggle="modal" data-bs-target="#members-filter-modal" title="Filtros avanzados">
                            <i class="fas fa-filter"></i>
                            @if($activeFilterCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary tm-filter-badge">{{ $activeFilterCount }}</span>
                            @endif
                        </button>

                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="submit" class="btn btn-primary" title="Buscar">
                                <i class="fas fa-magnifying-glass"></i>
                            </button>
                            @if($hasAnyFilter)
                                <a href="{{ route('settings.helpdesk.team.members') }}" class="btn btn-secondary" title="Limpiar filtros">
                                    <i class="fas fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($activeFilterCount > 0)
                        <div class="d-flex gap-2 flex-wrap mt-4 align-items-center">
                            <h6 class="mb-0">Filtrados:</h6>
                            @if($isSet('role'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">Rol: {{ ucfirst(request('role')) }}</span>
                            @endif
                            @if($isSet('group_id'))
                                @php $tmGroup = $groups->firstWhere('id', request('group_id')); @endphp
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">Grupo: {{ $tmGroup->name ?? request('group_id') }}</span>
                            @endif
                            @if($isSet('availability'))
                                <span class="badge bg-primary-subtle text-primary py-1 px-2">Disponibilidad: {{ $availabilityLabels[request('availability')] ?? request('availability') }}</span>
                            @endif
                        </div>
                    @endif
                </form>
            </div>

            <!-- Members Table -->
            <div class="card-body">
                @if($members->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" width="3%"><input type="checkbox" id="select-all" class="form-check-input"></th>
                                    <th scope="col" width="27%">Miembro</th>
                                    <th scope="col" width="15%">Rol</th>
                                    <th scope="col" width="25%">Grupos</th>
                                    <th scope="col" width="15%" class="text-center">Disponibilidad</th>
                                    <th scope="col" width="10%" class="text-center">Límite</th>
                                    <th scope="col" width="5%" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($members as $member)
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input bulk-checkbox" value="{{ $member->id }}"></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
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
                                                $limit = $member->agentSettings->max_concurrent_conversations ?? 0;
                                            @endphp
                                            <span class="badge bg-{{ $limit == 0 ? 'success' : 'info' }}-subtle text-{{ $limit == 0 ? 'success' : 'info' }}">
                                                {{ $limit == 0 ? 'Ilimitado' : $limit }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                 <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-ellipsis-vertical"></i>
                                        </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.helpdesk.team.member.edit', $member->id) }}">
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
                            @if($hasAnyFilter)
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

    {{-- Filtros avanzados --}}
    <div class="modal fade" id="members-filter-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filtros avanzados</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rol</label>
                        <select id="modal-role" class="form-control select2-filter-modal">
                            <option value="">Todos los roles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ ucfirst($role->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Grupo</label>
                        <select id="modal-group" class="form-control select2-filter-modal">
                            <option value="">Todos los grupos</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" @selected(request('group_id') == $group->id)>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Disponibilidad</label>
                        <select id="modal-availability" class="form-control select2-filter-modal">
                            <option value="">Cualquiera</option>
                            @foreach($availabilityLabels as $value => $label)
                                <option value="{{ $value }}" @selected(request('availability') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="members-filter-apply-btn" class="btn btn-primary w-100 mb-1">Aplicar filtros</button>
                    <button type="button" id="members-filter-clear-btn" class="btn btn-secondary w-100">Limpiar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Barra flotante de seleccion --}}
    <div id="bulk-toolbar" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none tm-bulk-toolbar">
        <button type="button" class="btn btn-primary shadow-lg px-4" data-bs-toggle="modal" data-bs-target="#bulk-modal">
            <span data-bulk-count>0</span> seleccionado(s) &mdash; Aplicar accion
        </button>
    </div>

    {{-- Accion masiva --}}
    <div class="modal fade" id="bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Accion masiva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Se aplicara sobre <strong><span data-bulk-count>0</span> miembro(s)</strong>.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Accion</label>
                        <select id="bulk-action-select" class="form-select select2-bulk">
                            <option value="">Seleccionar accion...</option>
                            <option value="availability">Cambiar disponibilidad</option>
                            <option value="add_group">Añadir a un grupo</option>
                            <option value="remove_group">Quitar de un grupo</option>
                        </select>
                    </div>
                    <div class="mb-0 d-none" id="bulk-availability-wrap">
                        <label class="form-label fw-semibold">Disponibilidad</label>
                        <select id="bulk-availability" class="form-select select2-bulk">
                            @foreach($availabilityLabels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0 d-none" id="bulk-group-wrap">
                        <label class="form-label fw-semibold">Grupo</label>
                        <select id="bulk-group" class="form-select select2-bulk">
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="bulk-apply-btn" type="button" class="btn btn-primary w-100 mb-1">Aplicar</button>
                    <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
window.TeamMembersIndexConfig = {
    flash: { success: @json(session('success')), error: @json(session('error')) },
    clearFilterUrl: @json(route('settings.helpdesk.team.members')),
    bulkActionUrl: @json(route('settings.helpdesk.team.members.bulk-action')),
};
</script>
<script src="{{ asset('core/js/bulk.js?v=2') }}"></script>
<script>window.HdSettingsCommonSkipAutoInit = true;</script>
<script src="{{ asset('vendor/helpdesk/settings/settings-common.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/settings-common.js')) }}" defer></script>
<script src="{{ asset('vendor/helpdesk/settings/team-members-index.js') }}?v={{ @filemtime(public_path('vendor/helpdesk/settings/team-members-index.js')) }}" defer></script>
@endpush
