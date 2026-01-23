@extends('layouts.theme')

@section('page_title', 'Gestión de Roles')

@section('content')

    @include('core::components.card', ['title' => 'Gestión de Roles'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Roles del sistema</h5>
                        <p class="small mb-0 text-muted">Administra los roles y permisos de usuario</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search'))
                            <a href="{{ route('settings.roles.index') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('settings.roles.matrix') }}" class="btn btn-info">
                            Matriz de permisos
                        </a>
                        <a href="{{ route('settings.roles.create') }}" class="btn btn-primary">
                            Crear rol
                        </a>
                    </div>
                </div>
            </div>

            {{-- Stats Cards --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title  mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $roles->total() }}</h4>
                                        <small class="text-muted">Total roles</small>
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
                                        <h6 class="card-title text-success mb-2">Por defecto</h6>
                                        <h4 class="mb-1 fw-bold">{{ $roles->where('is_default', true)->count() }}</h4>
                                        <small class="text-muted">Roles Por defecto</small>
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
                                        <h6 class="card-title text-warning mb-2">Del sistema</h6>
                                        <h4 class="mb-1 fw-bold">{{ \Spatie\Permission\Models\Role::whereIn('name', ['super-admin', 'customer'])->count() }}</h4>
                                        <small class="text-muted">Roles del sistema</small>
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
                                        <h6 class="card-title text-info mb-2">Usuarios</h6>
                                        <h4 class="mb-1 fw-bold">{{ $roles->sum('users_count') }}</h4>
                                        <small class="text-muted">Usuarios Asignados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search Section --}}
            <div class="card-body border-bottom">
                <form action="{{ route('settings.roles.index') }}" method="GET">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control"
                                       placeholder="Buscar por nombre o descripción..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($roles->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre del rol</th>
                                    <th>Guard</th>
                                    <th  class="text-center">Usuarios</th>
                                    <th  class="text-center">Estado</th>
                                    <th  class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roles as $key => $role)
                                    <tr>

                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <a href="{{ route('settings.roles.edit', $role->id) }}" class="text-decoration-none">
                                                        {{ $role->name }}
                                                    </a>
                                                    @if($role->slug)
                                                        <small class="d-block text-muted">{{ $role->slug }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light-subtle text-black">{{ $role->guard_name }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary">{{ $role->users_count ?? 0 }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($role->is_default)
                                                <span class="badge bg-light-subtle text-black">Por defecto</span>
                                            @else
                                                <span class="badge bg-light-subtle text-black">Normal</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if(!in_array($role->name, ['super-admin', 'customer']))
                                                <div class="dropdown">
                                                    <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-vertical"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a href="{{ route('settings.roles.edit', $role->id) }}" class="dropdown-item">
                                                                Editar
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('settings.roles.show.permissions', $role->id) }}" class="dropdown-item">
                                                                Gestionar permisos
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('settings.roles.show.modules', $role->id) }}" class="dropdown-item">
                                                                Gestionar modulos
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('settings.roles.show.users', $role->id) }}" class="dropdown-item">
                                                                Ver usuarios
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button type="button" class="dropdown-item delete-btn"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#delete-modal"
                                                                data-url="{{ route('settings.roles.destroy', $role->id) }}"
                                                                data-title="Eliminar rol: {{ $role->name }}">
                                                                Eliminar
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                            @else
                                                <span class="badge bg-black">Sistema</span>
                                            @endif
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
                                <i class="fas fa-shield-alt fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay roles para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados
                                @else
                                    Crea tu primer rol para gestionar permisos de usuario
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('settings.roles.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear Primer Rol
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($roles->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $roles->firstItem() }}</strong> a <strong>{{ $roles->lastItem() }}</strong>
                            de <strong>{{ $roles->total() }}</strong> roles
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $roles->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Delete Modal --}}
    @include('core::components.delete')

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Delete modal functionality
    $('.delete-btn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const deleteUrl = $(this).data('url');

        $('#delete-form').attr('action', deleteUrl);

        // Show the modal explicitly
        const deleteModal = new bootstrap.Modal(document.getElementById('delete-modal'));
        deleteModal.show();
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
