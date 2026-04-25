@extends('layouts.theme')

@section('title', 'Categorias')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Categorias'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Categorias</h5>
                        <p class="small mb-0 text-muted">Administra las categorias de productos</p>
                    </div>
                    <a href="{{ route('ecommerce.categories.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva categoria
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($categories->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Padre</th>
                                    <th>Orden</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categories as $category)
                                    <tr>
                                        <td>
                                            <a href="{{ route('ecommerce.categories.edit', $category) }}" class="text-decoration-none fw-semibold">{{ $category->name }}</a>
                                        </td>
                                        <td><small class="text-muted">{{ $category->parent?->name ?? '—' }}</small></td>
                                        <td>{{ $category->order }}</td>
                                        <td><span class="badge bg-{{ $category->status === 'published' ? 'success' : 'secondary' }}">{{ $category->status }}</span></td>
                                        <td class="text-center">
                                            <a href="{{ route('ecommerce.categories.edit', $category) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('ecommerce.categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Eliminar esta categoria?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-tags fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay categorias</h5>
                        <a href="{{ route('ecommerce.categories.create') }}" class="btn btn-primary">Crear primera categoria</a>
                    </div>
                @endif
            </div>

            @if($categories->hasPages())
                <div class="card-footer">{{ $categories->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
