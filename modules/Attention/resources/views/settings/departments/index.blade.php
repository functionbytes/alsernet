@extends('layouts.theme')

@section('title', 'Departamentos')

@section('content')

    @include('core::components.card', ['title' => 'Departamentos'])

    <div class="widget-content searchable-container list">

        @include('core::components.alerts')

        <!-- Department Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="mb-1">{{ $stats['total'] }}</h3>
                        <p class="text-muted mb-0 small">Total Departamentos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="mb-1 text-success">{{ $stats['active'] }}</h3>
                        <p class="text-muted mb-0 small">Activos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="mb-1 text-danger">{{ $stats['inactive'] }}</h3>
                        <p class="text-muted mb-0 small">Inactivos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="mb-1 text-primary">{{ $stats['total_members'] }}</h3>
                        <p class="text-muted mb-0 small">Total Usuarios</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Departments Card -->
        <div class="card">
            <!-- Header Section -->
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Departamentos</h5>
                        <p class="small mb-0 text-muted">Gestiona los departamentos para asignación de PQRSF</p>
                    </div>
                    <div class="d-flex gap-2">
                        @if(request('search'))
                            <a href="{{ route('settings.attention.departments.index') }}" class="btn btn-secondary">
                                Limpiar búsqueda
                            </a>
                        @endif
                        <a href="{{ route('settings.attention.departments.create') }}" class="btn btn-primary">
                            Nuevo departamento
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search Section -->
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('settings.attention.departments.index') }}">
                    <div class="row align-items-center g-2">
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text bg-white">
                                    <i class="fa fa-magnifying-glass"></i>
                                </span>
                                <input type="search" name="search" class="form-control"
                                       placeholder="Buscar por nombre, descripción o email..."
                                       value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                Buscar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Departments List -->
            <div class="card-body">
                @if($departments->count() > 0)
                    <div class="alert alert-info mb-3">
                        <i class="fa fa-circle-info me-2"></i>
                        Los departamentos permiten organizar y asignar PQRSF a diferentes áreas de la organización
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Responsable</th>
                                    <th class="text-center">Usuarios</th>
                                    <th class="text-center">PQRSF</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($departments as $department)
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>{{ $department->name }}</strong>
                                                @if($department->description)
                                                    <br>
                                                    <small class="text-muted">{{ Str::limit($department->description, 60) }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($department->email)
                                                <a href="mailto:{{ $department->email }}">{{ $department->email }}</a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($department->responsible_name)
                                                <div>
                                                    {{ $department->responsible_name }}
                                                    @if($department->responsible_email)
                                                        <br>
                                                        <small class="text-muted">{{ $department->responsible_email }}</small>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info">{{ $department->users_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary">{{ $department->attentions_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($department->is_active)
                                                <span class="badge bg-success">Activo</span>
                                            @else
                                                <span class="badge bg-danger">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                                    <i class="fa fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('settings.attention.departments.edit', $department) }}">
                                                            <i class="fa fa-pen me-2"></i> Editar
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="{{ route('settings.attention.departments.toggle', $department) }}">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="fa fa-{{ $department->is_active ? 'toggle-off' : 'toggle-on' }} me-2"></i>
                                                                {{ $department->is_active ? 'Desactivar' : 'Activar' }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" action="{{ route('settings.attention.departments.destroy', $department) }}"
                                                              onsubmit="return confirm('¿Estás seguro de eliminar este departamento?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="fa fa-trash me-2"></i> Eliminar
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

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $departments->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fa fa-building fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay departamentos registrados</p>
                        <a href="{{ route('settings.attention.departments.create') }}" class="btn btn-primary">
                            Crear primer departamento
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
