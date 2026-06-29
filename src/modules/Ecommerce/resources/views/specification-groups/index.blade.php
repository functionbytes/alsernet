@extends('layouts.theme')

@section('title', 'Grupos de especificaciones')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Grupos de especificaciones'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Grupos de especificaciones</h5>
                        <p class="small mb-0 text-muted">Agrupa atributos relacionados para organizar las especificaciones de productos</p>
                    </div>
                    <a href="{{ route('ecommerce.specification-groups.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nuevo grupo
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($specificationGroups->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Descripcion</th>
                                    <th>Atributos</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($specificationGroups as $group)
                                    <tr>
                                        <td class="fw-semibold">{{ $group->name }}</td>
                                        <td><small class="text-muted">{{ Str::limit($group->description, 60) ?? '—' }}</small></td>
                                        <td><span class="badge bg-secondary">{{ $group->attributes_count ?? $group->attributes()->count() }}</span></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('ecommerce.specification-groups.edit', $group) }}">Editar</a></li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.specification-groups.destroy', $group) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar este grupo?')">Eliminar</button>
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
                        <i class="fas fa-layer-group fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay grupos de especificaciones</h5>
                        <a href="{{ route('ecommerce.specification-groups.create') }}" class="btn btn-primary">Crear primer grupo</a>
                    </div>
                @endif
            </div>

            @if($specificationGroups->hasPages())
                <div class="card-footer">{{ $specificationGroups->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
