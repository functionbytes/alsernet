@extends('layouts.theme')

@section('title', 'Etiquetas')

@section('content')
    @include('core::components.card', ['title' => 'Ecommerce - Etiquetas'])

    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Etiquetas</h5>
                        <p class="small mb-0 text-muted">Administra las etiquetas de productos</p>
                    </div>
                    <a href="{{ route('ecommerce.tags.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva etiqueta
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($tags->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Descripcion</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tags as $tag)
                                    <tr>
                                        <td>
                                            <a href="{{ route('ecommerce.tags.edit', $tag) }}" class="text-decoration-none fw-semibold">{{ $tag->name }}</a>
                                        </td>
                                        <td><small class="text-muted">{{ Str::limit($tag->description, 50) ?? '—' }}</small></td>
                                        <td><span class="badge bg-{{ $tag->status === 'published' ? 'success' : 'secondary' }}">{{ $tag->status === 'published' ? 'Publicado' : 'Borrador' }}</span></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('ecommerce.tags.edit', $tag) }}">Editar</a></li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.tags.destroy', $tag) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar esta etiqueta?')">Eliminar</button>
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
                        <i class="fas fa-tags fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay etiquetas</h5>
                        <a href="{{ route('ecommerce.tags.create') }}" class="btn btn-primary">Crear primera etiqueta</a>
                    </div>
                @endif
            </div>

            @if($tags->hasPages())
                <div class="card-footer">{{ $tags->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
