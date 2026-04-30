@extends('layouts.theme')

@section('title', 'Opciones de producto')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Opciones de producto'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Opciones de producto</h5>
                        <p class="small mb-0 text-muted">Administra las opciones como talla, color, material, etc.</p>
                    </div>
                    <a href="{{ route('ecommerce.product-options.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nueva opcion
                    </a>
                </div>
            </div>

            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('ecommerce.product-options.index') }}" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control" placeholder="Buscar por nombre..." value="{{ $search ?? '' }}">
                    <button type="submit" class="btn btn-outline-secondary">Buscar</button>
                    @if($search ?? false)
                        <a href="{{ route('ecommerce.product-options.index') }}" class="btn btn-light">Limpiar</a>
                    @endif
                </form>
            </div>

            <div class="card-body">
                @if($options->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Valores</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($options as $option)
                                    <tr>
                                        <td>
                                            <a href="{{ route('ecommerce.product-options.edit', $option) }}" class="text-decoration-none fw-semibold">
                                                {{ $option->name }}
                                            </a>
                                        </td>
                                        <td>
                                            @forelse($option->values as $value)
                                                <span class="badge bg-light text-dark border me-1">{{ $value->value ?? $value }}</span>
                                            @empty
                                                <small class="text-muted">Sin valores</small>
                                            @endforelse
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('ecommerce.product-options.edit', $option) }}">Editar</a>
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.product-options.destroy', $option) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar esta opcion?')">Eliminar</button>
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
                        <i class="fas fa-sliders fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay opciones de producto</h5>
                        @if($search ?? false)
                            <p class="text-muted mb-3">No se encontraron resultados para "{{ $search }}"</p>
                            <a href="{{ route('ecommerce.product-options.index') }}" class="btn btn-outline-secondary">Ver todas</a>
                        @else
                            <a href="{{ route('ecommerce.product-options.create') }}" class="btn btn-primary">Crear primera opcion</a>
                        @endif
                    </div>
                @endif
            </div>

            @if($options->hasPages())
                <div class="card-footer">{{ $options->appends(request()->only('search'))->links() }}</div>
            @endif
        </div>
    </div>
@endsection
