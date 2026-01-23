@extends('layouts.theme')

@section('page_title', 'Gestión de Permisos')

@section('content')

    @include('core::components.card', ['title' => 'Gestión de Permisos'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Permisos del sistema</h5>
                        <p class="small mb-0 text-muted">Administra los permisos disponibles para los roles</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('settings.permissions.create') }}" class="btn btn-primary">
                            Crear permiso
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
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ $permissions->total() }}</h4>
                                        <small class="text-muted">Total Permisos</small>
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
                                        <h6 class="card-title text-success mb-2">Permisos web</h6>
                                        <h4 class="mb-1 fw-bold">{{ \Spatie\Permission\Models\Permission::where('guard_name', 'web')->count() }}</h4>
                                        <small class="text-muted">Guard Web</small>
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
                                        <h6 class="card-title text-info mb-2">Permisos API</h6>
                                        <h4 class="mb-1 fw-bold">{{ \Spatie\Permission\Models\Permission::where('guard_name', 'api')->count() }}</h4>
                                        <small class="text-muted">Guard API</small>
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
                                        <h6 class="card-title text-warning mb-2">Roles</h6>
                                        <h4 class="mb-1 fw-bold">{{ \Spatie\Permission\Models\Role::count() }}</h4>
                                        <small class="text-muted">Roles Totales</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Search Section --}}
            <div class="card-body border-bottom">
                <form action="{{ route('settings.permissions.index') }}" method="GET">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="input-group flex-grow-1">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="search" name="search" class="form-control"
                                   placeholder="Buscar por nombre de permiso..."
                                   value="{{ request('search') }}">
                        </div>
                        <button type="submit" class="btn btn-primary" style="min-width: 100px;">
                            <i class="fas fa-search me-2"></i>
                        </button>
                        @if(request('search'))
                            <a href="{{ route('settings.permissions.index') }}" class="btn btn-secondary" style="min-width: 100px;">
                                <i class="fas fa-times me-2"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="card-body">
                @if($permissions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre del Permiso</th>
                                    <th>Guard</th>
                                    <th class="text-center">Roles Asignados</th>
                                    <th>Fecha de Creación</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($permissions as $key => $permission)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <a href="{{ route('settings.permissions.edit', $permission->id) }}" class="text-decoration-none">
                                                        {{ $permission->name }}
                                                    </a>
                                                    @if($permission->description)
                                                        <small class="d-block text-muted">{{ Str::limit($permission->description, 50) }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($permission->guard_name === 'web')
                                                <span class="badge bg-info-subtle text-success">
                                                    Web
                                                </span>
                                            @elseif($permission->guard_name === 'api')
                                                <span class="badge bg-info-subtle text-info">
                                                    API
                                                </span>
                                            @else
                                                <span class="badge bg-light-subtle text-black">{{ $permission->guard_name }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary">{{ $permission->roles()->count() }}</span>
                                        </td>
                                        <td>
                                            {{ $permission->created_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @can('permissions.edit')
                                                        <li>
                                                            <a href="{{ route('settings.permissions.edit', $permission->id) }}" class="dropdown-item">
                                                                Editar
                                                            </a>
                                                        </li>
                                                    @endcan
                                                    @can('permissions.delete')
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button type="button" class="dropdown-item delete-btn"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#delete-modal"
                                                                data-url="{{ route('settings.permissions.destroy', $permission->id) }}"
                                                                data-title="Eliminar permiso: {{ $permission->name }}">
                                                                Eliminar
                                                            </button>
                                                        </li>
                                                    @endcan
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
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-key fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay permisos para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados
                                @else
                                    Crea tu primer permiso para gestionar accesos del sistema
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('settings.permissions.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear Primer Permiso
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- Pagination --}}
            @if($permissions->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Mostrando <strong>{{ $permissions->firstItem() }}</strong> a <strong>{{ $permissions->lastItem() }}</strong>
                            de <strong>{{ $permissions->total() }}</strong> permisos
                        </div>
                        <nav aria-label="Page navigation">
                            {{ $permissions->links() }}
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
