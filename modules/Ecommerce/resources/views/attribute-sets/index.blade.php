@extends('layouts.theme')

@section('title', 'Conjuntos de atributos')

@section('page_header')
    @include('core::components.card', ['title' => 'Ecommerce - Conjuntos de atributos'])
@endsection

@section('content')
    <div class="widget-content searchable-container list">
        @include('core::components.alerts')

        <div class="card">
            <div class="card-header p-4 border-bottom border-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">Conjuntos de atributos</h5>
                        <p class="small mb-0 text-muted">Define los conjuntos de atributos de variantes para los productos</p>
                    </div>
                    <a href="{{ route('ecommerce.product-attribute-sets.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Nuevo conjunto de atributos
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if($productAttributeSets->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Titulo</th>
                                    <th>Slug</th>
                                    <th>Layout</th>
                                    <th>Estado</th>
                                    <th class="text-center">Buscable</th>
                                    <th class="text-center">Comparable</th>
                                    <th>Atributos</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($productAttributeSets as $set)
                                    @php
                                        $layoutBadges = ['swatch_dropdown' => 'primary', 'text' => 'secondary', 'color' => 'warning', 'image' => 'info'];
                                        $layoutLabels = ['swatch_dropdown' => 'Swatch', 'text' => 'Texto', 'color' => 'Color', 'image' => 'Imagen'];
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $set->title }}</td>
                                        <td><small class="text-muted">{{ $set->slug }}</small></td>
                                        <td>
                                            <span class="badge bg-{{ $layoutBadges[$set->display_layout] ?? 'secondary' }}">
                                                {{ $layoutLabels[$set->display_layout] ?? $set->display_layout }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $set->status === 'published' ? 'success' : 'secondary' }}">
                                                {{ $set->status === 'published' ? 'Publicado' : 'Borrador' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($set->is_searchable)
                                                <i class="fas fa-check text-success"></i>
                                            @else
                                                <i class="fas fa-minus text-muted"></i>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($set->is_comparable)
                                                <i class="fas fa-check text-success"></i>
                                            @else
                                                <i class="fas fa-minus text-muted"></i>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-secondary">{{ $set->attributes_count ?? $set->attributes()->count() }}</span></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('ecommerce.product-attribute-sets.edit', $set) }}">Editar</a></li>
                                                    <li>
                                                        <form action="{{ route('ecommerce.product-attribute-sets.destroy', $set) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="dropdown-item" onclick="return confirm('¿Eliminar este conjunto?')">Eliminar</button>
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
                        <i class="fas fa-swatchbook fa-4x text-muted opacity-50 mb-4"></i>
                        <h5 class="text-muted mb-2">No hay conjuntos de atributos</h5>
                        <a href="{{ route('ecommerce.product-attribute-sets.create') }}" class="btn btn-primary">Crear primer conjunto</a>
                    </div>
                @endif
            </div>

            @if($productAttributeSets->hasPages())
                <div class="card-footer">{{ $productAttributeSets->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
@endsection
