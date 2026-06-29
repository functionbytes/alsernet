@extends('layouts.theme')

@section('title', 'Permisos directos de usuarios')

@section('page_header')
    @include('core::components.card', ['title' => 'Permisos directos de usuarios'])
@endsection

@section('content')

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <div class="card">

            {{-- Header --}}
            <div class="card-header p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Permisos directos de usuarios</h5>
                        <p class="small mb-0 text-muted">Gestiona los permisos asignados directamente a cada usuario</p>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-2">Total</h6>
                                <h4 class="mb-1 fw-bold">{{ $users->total() }}</h4>
                                <small class="text-muted">Usuarios en el sistema</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title mb-2">Con permisos</h6>
                                <h4 class="mb-1 fw-bold">{{ $totalWithPermissions }}</h4>
                                <small class="text-muted">Con permisos directos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-warning mb-2">Sin permisos</h6>
                                <h4 class="mb-1 fw-bold">{{ $totalWithoutPermissions }}</h4>
                                <small class="text-muted">Sin permisos directos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-secondary stat-card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-info mb-2">Roles</h6>
                                <a href="{{ route('settings.roles.index') }}" class="text-info small">
                                    Ver gestión de roles
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Buscador --}}
            <div class="card-body border-bottom">
                <form action="{{ route('settings.user-permissions.index') }}" method="GET">
                    <div class="d-flex flex-column flex-lg-row gap-3 align-items-stretch">
                        <div class="flex-fill">
                            <div class="input-group h-100">
                                <span class="input-group-text bg-white border-end-1">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="search" name="search" class="form-control -0 ps-0"
                                       placeholder="Buscar por nombre, apellido o email..."
                                       value="{{ $search }}">
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            @if($search)
                                <a href="{{ route('settings.user-permissions.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Tabla --}}
            <div class="card-body">
                @if($users->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th class="text-center">Permisos directos</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    <tr>
                                        <td>
                                            <span>{{ $user->firstname }} {{ $user->lastname }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $user->email }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($user->direct_permissions_count > 0)
                                                <span class="badge bg-primary-subtle text-primary">
                                                    {{ $user->direct_permissions_count }}
                                                </span>
                                            @else
                                                <span class="badge bg-light text-muted">0</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a href="#" class="text-muted" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a href="{{ route('settings.user-permissions.show', $user->id) }}" class="dropdown-item">
                                                            Gestionar permisos
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
                        <div class="d-flex flex-column align-items-center">
                            <div class="round-48 rounded-circle bg-light-subtle text-muted mb-3 d-flex align-items-center justify-content-center">
                                <i class="fas fa-users fs-7"></i>
                            </div>
                            <h6 class="mb-1">
                                @if($search)
                                    No se encontraron usuarios
                                @else
                                    No hay usuarios registrados
                                @endif
                            </h6>
                            <p class="text-muted mb-0">
                                @if($search)
                                    No hay resultados para los criterios de búsqueda
                                @else
                                    Los usuarios aparecerán aquí una vez registrados
                                @endif
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Paginación --}}
            @if($users->hasPages())
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Mostrando <strong>{{ $users->firstItem() }}</strong> a <strong>{{ $users->lastItem() }}</strong>
                            de <strong>{{ $users->total() }}</strong> usuarios
                        </div>
                        <nav>{{ $users->appends(request()->input())->links() }}</nav>
                    </div>
                </div>
            @endif

        </div>

    </div>

@endsection
