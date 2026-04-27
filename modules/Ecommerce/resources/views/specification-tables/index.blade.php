@extends('layouts.theme')

@section('title', 'Tablas de especificaciones')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Tablas de especificaciones'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Tablas de especificaciones</h5>
                        <p class="small mb-0 text-muted">Conjuntos de grupos de especificaciones asignables a productos</p>
                    </div>
                    <a href="{{ route('ecommerce.specification-tables.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva tabla
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($specificationTables->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Descripcion</th>
                                    <th>Grupos asignados</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($specificationTables as $table)
                                    <tr>
                                        <td class="fw-semibold">{{ $table->name }}</td>
                                        <td><small class="text-muted">{{ Str::limit($table->description, 60) ?? '—' }}</small></td>
                                        <td><span class="badge bg-primary">{{ $table->groups_count ?? $table->groups()->count() }}</span></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('ecommerce.specification-tables.edit', $table) }}">Editar</a></li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.specification-tables.destroy', $table) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar esta tabla?')">Eliminar</button>
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
                        <i class="fas fa-table fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay tablas de especificaciones</h5>
                        <a href="{{ route('ecommerce.specification-tables.create') }}" class="btn btn-primary">Crear primera tabla</a>
                    </div>
                @endif
            </div>

            @if($specificationTables->hasPages())
                <div class="card-footer">{{ $specificationTables->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
