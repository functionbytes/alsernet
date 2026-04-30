@extends('layouts.theme')

@section('title', 'Roles de equipo')

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Roles de equipo</h5>
                        <p class="small mb-0 text-muted">Gestiona los roles y permisos de los miembros del equipo</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search'))
                            <a href="{{ route('settings.chat.team-roles.index') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('settings.chat.team-roles.create') }}" class="btn btn-primary">
                            Nuevo rol
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div>
                                        <h6 class="card-title text-primary mb-2">Total</h6>
                                        <h4 class="mb-1 fw-bold">{{ count($roles) }}</h4>
                                        <small class="text-muted">Roles configurados</small>
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
                                        <h6 class="card-title mb-2">Por defecto</h6>
                                        <h4 class="mb-1 fw-bold">{{ collect($roles)->where('is_default', true)->count() }}</h4>
                                        <small class="text-muted">Roles asignados automáticamente</small>
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
                                        <h6 class="card-title text-warning mb-2">Con usuarios</h6>
                                        <h4 class="mb-1 fw-bold">{{ collect($roles)->where('users_count', '>', 0)->count() }}</h4>
                                        <small class="text-muted">Roles con usuarios asignados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.chat.team-roles.index') }}">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="search" name="search" class="form-control" placeholder="Buscar roles..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Roles List -->
            <div class="card-body">
                @if(count($roles) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th width="25%">Nombre</th>
                                <th width="25%">Descripción</th>
                                <th width="15%" class="text-center">Permisos</th>
                                <th width="10%" class="text-center">Usuarios</th>
                                <th width="10%" class="text-center">Por defecto</th>
                                <th width="15%" class="text-center">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($roles as $role)
                                <tr>
                                    <td>
                                        <div>
                                            <a href="{{ route('settings.chat.team-roles.show', $role) }}" class="text-decoration-none">
                                                <strong>{{ $role->name }}</strong>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ Str::limit($role->description ?? 'Sin descripción', 50) }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info">{{ count($role->permissions ?? []) }} permisos</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info">{{ $role->users_count ?? 0 }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($role->is_default)
                                            <span class="badge bg-success-subtle text-success">Por defecto</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" class="text-muted" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-vertical"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.chat.team-roles.show', $role) }}">
                                                        Ver detalles
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('settings.chat.team-roles.edit', $role) }}">
                                                        Editar
                                                    </a>
                                                </li>
                                                @if(($role->users_count ?? 0) == 0)
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <button type="button"
                                                            class="dropdown-item text-danger delete-btn"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#delete-modal"
                                                            data-url="{{ route('settings.chat.team-roles.destroy', $role) }}"
                                                            data-title="Eliminar rol: {{ $role->name }}">
                                                            Eliminar
                                                        </button>
                                                    </li>
                                                @endif
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
                                <i class="fas fa-shield-halved fs-7"></i>
                            </div>
                            <h6 class="mb-1">No hay roles para mostrar</h6>
                            <p class="text-muted mb-3">
                                @if(request('search'))
                                    No se encontraron resultados
                                @else
                                    Crea tu primer rol para gestionar permisos del equipo
                                @endif
                            </p>
                            @if(!request('search'))
                                <a href="{{ route('settings.chat.team-roles.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Crear primer rol
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

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
                const deleteTitle = $(this).data('title');

                $('#delete-modal .modal-title').text(deleteTitle);
                $('#delete-form').attr('action', deleteUrl);

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
