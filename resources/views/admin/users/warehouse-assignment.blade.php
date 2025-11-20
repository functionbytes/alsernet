@extends('layouts.app')

@section('title', 'Asignar Almacenes a Usuarios')

@section('content')
<div class="container-fluid px-4">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Asignación de Almacenes</h1>
                    <p class="text-muted mt-2">Gestiona qué almacenes pueden ver y usar los usuarios de inventario</p>
                </div>
                <div>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.warehouse-assignment.index') }}" class="row g-3">
                        <div class="col-md-6">
                            <label for="search" class="form-label">Buscar Usuario</label>
                            <input
                                type="text"
                                class="form-control"
                                id="search"
                                name="search"
                                placeholder="Nombre, email..."
                                value="{{ $search }}"
                            >
                        </div>
                        <div class="col-md-6 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <a href="{{ route('admin.warehouse-assignment.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-redo"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de usuarios -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-users"></i> Usuarios de Inventario</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Almacenes Asignados</th>
                                <th>Almacén Predeterminado</th>
                                <th style="width: 150px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img
                                                src="{{ $user->image }}"
                                                alt="{{ $user->full_name }}"
                                                class="rounded-circle me-2"
                                                width="32"
                                                height="32"
                                            >
                                            <strong>{{ $user->full_name }}</strong>
                                        </div>
                                    </td>
                                    <td>
                                        <code>{{ $user->email }}</code>
                                    </td>
                                    <td>
                                        @php
                                            $warehouses = $user->warehouses()->count();
                                        @endphp
                                        @if ($warehouses > 0)
                                            <span class="badge bg-success">{{ $warehouses }}</span>
                                        @else
                                            <span class="badge bg-danger">Sin asignar</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $default = $user->defaultWarehouse();
                                        @endphp
                                        @if ($default)
                                            <span class="badge bg-info">{{ $default->code }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a
                                                href="{{ route('admin.warehouse-assignment.edit', $user->id) }}"
                                                class="btn btn-outline-primary"
                                                title="Editar asignaciones"
                                            >
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <p>No hay usuarios de inventario registrados</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($users->hasPages())
                    <div class="card-footer">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Información -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle"></i>
                <strong>Nota:</strong> Los usuarios de inventario solo podrán ver y trabajar con los almacenes que les asignes aquí.
                Puedes asignar múltiples almacenes a cada usuario y definir un almacén predeterminado.
            </div>
        </div>
    </div>
</div>

<style>
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }

    .badge {
        font-size: 0.85rem;
        padding: 0.4rem 0.6rem;
    }
</style>
@endsection
