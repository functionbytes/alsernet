@extends('layouts.theme')

@section('title', $role->name . ' - Roles')

@section('content')

    @include('core::components.card', ['title' => 'Detalles del rol'])

    @include('core::components.alerts')

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Role Header Card -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width: 70px; height: 70px; background: linear-gradient(135deg, #90bb13 0%, #6d8f0f 100%);">
                            <i class="fas fa-user-shield fs-2 text-white"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h4 class="mb-2 fw-bold">{{ $role->name }}</h4>
                            <p class="text-muted mb-3">{{ $role->description ?? 'Sin descripción disponible' }}</p>

                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge bg-primary">
                                    <i class="fas fa-shield-alt me-1"></i> {{ ucfirst($role->guard_name) }}
                                </span>

                                @if($role->is_default)
                                    <span class="badge bg-success">
                                        <i class="fas fa-star me-1"></i> Por defecto
                                    </span>
                                @endif

                                @if(in_array($role->name, ['super-admin', 'customer']))
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-lock me-1"></i> Sistema
                                    </span>
                                @endif
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('settings.roles.edit', $role) }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-pen me-1"></i> Editar rol
                                </a>
                                <a href="{{ route('settings.roles.show.permissions', $role) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-lock me-1"></i> Gestionar permisos
                                </a>
                                <a href="{{ route('settings.roles.show.users', $role) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-users me-1"></i> Ver usuarios
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-3">
                <div class="col-md-4 mb-3">
                    <div class="card border-start border-primary border-4 h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-1">Total usuarios</h6>
                                    <h2 class="mb-0 fw-bold text-primary">{{ $role->users()->count() }}</h2>
                                </div>
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                     style="width: 50px; height: 50px; background-color: rgba(144, 187, 19, 0.1);">
                                    <i class="fas fa-users fs-4 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="card border-start border-success border-4 h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-1">Permisos activos</h6>
                                    <h2 class="mb-0 fw-bold text-success">{{ $role->permissions()->count() }}</h2>
                                </div>
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                     style="width: 50px; height: 50px; background-color: rgba(19, 198, 114, 0.1);">
                                    <i class="fas fa-check-circle fs-4 text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="card border-start border-info border-4 h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-muted mb-1">Módulos</h6>
                                    @php
                                        $moduleCount = $role->permissions()
                                            ->get()
                                            ->groupBy(fn($p) => explode('.', $p->name)[0])
                                            ->count();
                                    @endphp
                                    <h2 class="mb-0 fw-bold text-info">{{ $moduleCount }}</h2>
                                </div>
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                     style="width: 50px; height: 50px; background-color: rgba(83, 109, 254, 0.1);">
                                    <i class="fas fa-cubes fs-4 text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permissions by Module -->
            <div class="card mb-3">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Permisos asignados</h5>
                            <p class="text-muted small mb-0">Desglose de permisos por módulo</p>
                        </div>
                        <a href="{{ route('settings.roles.show.permissions', $role) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit me-1"></i> Gestionar
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @php
                        $groupedPermissions = $role->permissions()
                            ->get()
                            ->groupBy(fn($perm) => explode('.', $perm->name)[0]);
                    @endphp

                    @if($groupedPermissions->count() > 0)
                        <div class="row g-3">
                            @foreach($groupedPermissions as $module => $permissions)
                                <div class="col-md-6">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-3">
                                                <h6 class="mb-0 fw-bold text-uppercase">
                                                    {{ ucwords(str_replace(['_', '-'], ' ', $module)) }}
                                                </h6>
                                                <span class="badge bg-primary rounded-pill">{{ $permissions->count() }}</span>
                                            </div>
                                            <div class="permission-list" style="max-height: 150px; overflow-y: auto;">
                                                @foreach($permissions->take(10) as $permission)
                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                        <i class="fas fa-check-circle text-success small"></i>
                                                        <small class="text-muted">{{ str_replace($module . '.', '', $permission->name) }}</small>
                                                    </div>
                                                @endforeach
                                                @if($permissions->count() > 10)
                                                    <small class="text-muted fst-italic">
                                                        + {{ $permissions->count() - 10 }} más...
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                                 style="width: 64px; height: 64px; background-color: #f5f6f8;">
                                <i class="fas fa-lock-open fs-3 text-muted"></i>
                            </div>
                            <h6 class="mb-1">Sin permisos asignados</h6>
                            <p class="text-muted mb-3 small">Este rol no tiene ningún permiso configurado</p>
                            <a href="{{ route('settings.roles.show.permissions', $role) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i> Asignar permisos
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Users -->
            @php
                $recentUsers = $role->users()->latest()->limit(5)->get();
            @endphp

            @if($recentUsers->count() > 0)
                <div class="card mb-3">
                    <div class="card-header bg-white border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">Usuarios recientes</h5>
                                <p class="text-muted small mb-0">Últimos usuarios asignados a este rol</p>
                            </div>
                            <a href="{{ route('settings.roles.show.users', $role) }}" class="btn btn-sm btn-outline-info">
                                Ver todos
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            @foreach($recentUsers as $user)
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                             style="width: 40px; height: 40px; background-color: #90bb13; color: white; font-weight: bold;">
                                            {{ strtoupper(substr($user->firstname ?? $user->first_name ?? 'U', 0, 1)) }}{{ strtoupper(substr($user->lastname ?? $user->last_name ?? 'S', 0, 1)) }}
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">{{ $user->firstname ?? $user->first_name }} {{ $user->lastname ?? $user->last_name }}</h6>
                                            <small class="text-muted">{{ $user->email }}</small>
                                        </div>
                                        @if(isset($user->is_active))
                                            @if($user->is_active)
                                                <span class="badge bg-success-subtle text-success">Activo</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Inactivo</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Role Details -->
            <div class="card mb-3">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Detalles del rol</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small mb-1">Nombre</label>
                        <p class="mb-0 fw-semibold">{{ $role->name }}</p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small mb-1">Slug</label>
                        <p class="mb-0">
                            <code class="bg-light px-2 py-1 rounded">{{ $role->slug ?? 'N/A' }}</code>
                        </p>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small mb-1">Guard</label>
                        <p class="mb-0">
                            @if($role->guard_name === 'web')
                                <span class="badge bg-success">Web (Navegador)</span>
                            @elseif($role->guard_name === 'api')
                                <span class="badge bg-info">API (Token)</span>
                            @else
                                <span class="badge bg-secondary">{{ $role->guard_name }}</span>
                            @endif
                        </p>
                    </div>

                    <div class="mb-0">
                        <label class="form-label text-muted small mb-1">Estado</label>
                        <p class="mb-0">
                            @if($role->is_default)
                                <span class="badge bg-success">
                                    <i class="fas fa-star me-1"></i> Rol Por defecto
                                </span>
                            @else
                                <span class="badge bg-secondary">Normal</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="card mb-3">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Historial</h5>
                </div>
                <div class="card-body">
                    <div class="timeline-widget">
                        <div class="timeline-item d-flex position-relative mb-3">
                            <div class="timeline-badge-wrap d-flex flex-column align-items-center">
                                <div class="timeline-badge rounded-circle d-flex align-items-center justify-content-center"
                                     style="width: 35px; height: 35px; background-color: rgba(144, 187, 19, 0.1);">
                                    <i class="fas fa-plus text-primary"></i>
                                </div>
                            </div>
                            <div class="timeline-desc fs-3 text-dark ms-3">
                                <h6 class="mb-1">Creado</h6>
                                <p class="text-muted mb-0 small">{{ $role->created_at->format('d/m/Y H:i') }}</p>
                                <small class="text-muted">{{ $role->created_at->diffForHumans() }}</small>
                            </div>
                        </div>

                        @if($role->updated_at->ne($role->created_at))
                            <div class="timeline-item d-flex position-relative">
                                <div class="timeline-badge-wrap d-flex flex-column align-items-center">
                                    <div class="timeline-badge rounded-circle d-flex align-items-center justify-content-center"
                                         style="width: 35px; height: 35px; background-color: rgba(83, 109, 254, 0.1);">
                                        <i class="fas fa-edit text-info"></i>
                                    </div>
                                </div>
                                <div class="timeline-desc fs-3 text-dark ms-3">
                                    <h6 class="mb-1">Última actualización</h6>
                                    <p class="text-muted mb-0 small">{{ $role->updated_at->format('d/m/Y H:i') }}</p>
                                    <small class="text-muted">{{ $role->updated_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Acciones rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('settings.roles.matrix') }}" class="btn btn-outline-primary">
                            <i class="fas fa-table me-2"></i> Ver matriz de permisos
                        </a>

                        @if(!in_array($role->name, ['super-admin', 'customer']))
                            <button type="button" class="btn btn-outline-warning" onclick="alert('Función duplicar en desarrollo')">
                                <i class="fas fa-copy me-2"></i> Duplicar rol
                            </button>

                            <hr class="my-2">

                            <button type="button" class="btn btn-outline-danger"
                                    onclick="if(confirm('¿Estás seguro de eliminar este rol?')) { window.location='{{ route('settings.roles.destroy', $role) }}' }">
                                <i class="fas fa-trash me-2"></i> Eliminar rol
                            </button>
                        @endif

                        <a href="{{ route('settings.roles.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Volver a la lista
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .timeline-badge-wrap::before {
        content: '';
        position: absolute;
        left: 17px;
        top: 35px;
        height: calc(100% - 35px);
        width: 2px;
        background: #e9ecef;
    }

    .timeline-item:last-child .timeline-badge-wrap::before {
        display: none;
    }

    .permission-list::-webkit-scrollbar {
        width: 5px;
    }

    .permission-list::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .permission-list::-webkit-scrollbar-thumb {
        background: #90bb13;
        border-radius: 10px;
    }
</style>
@endpush
