@extends('layouts.theme')

@section('title', 'Colecciones')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Colecciones'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Colecciones</h5>
                        <p class="small mb-0 text-muted">Administra las colecciones de productos</p>
                    </div>
                    <a href="{{ route('ecommerce.collections.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva coleccion
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($collections->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Slug</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($collections as $collection)
                                    <tr>
                                        <td>
                                            <a href="{{ route('ecommerce.collections.edit', $collection) }}" class="text-decoration-none fw-semibold">{{ $collection->name }}</a>
                                        </td>
                                        <td><small class="text-muted">{{ $collection->slug }}</small></td>
                                        <td><span class="badge bg-{{ $collection->status === 'published' ? 'success' : 'secondary' }}">{{ $collection->status === 'published' ? 'Publicado' : 'Borrador' }}</span></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('ecommerce.collections.edit', $collection) }}">Editar</a></li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.collections.destroy', $collection) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar esta coleccion?')">Eliminar</button>
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
                        <h5 class="text-muted mb-2">No hay colecciones</h5>
                        <a href="{{ route('ecommerce.collections.create') }}" class="btn btn-primary">Crear primera coleccion</a>
                    </div>
                @endif
            </div>

            @if($collections->hasPages())
                <div class="card-footer">{{ $collections->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
